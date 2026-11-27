<?php

namespace App\Console\Commands;

use App\Services\RerouteBusyException;
use App\Services\ScheduleRerouteService;
use App\Services\SchedulingLock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Quét lô quá giờ chưa Nhận phòng (user yêu cầu 26/11/2026).
 *
 * Phân xưởng đang bật công tắc tịnh tuyến (schedule_reroute_settings.realtime_reroute): lô Cân → Đóng gói chưa Nhận phòng mà
 * giờ bắt đầu lý thuyết đã qua từ 30 phút, trong 2 ngày gần nhất, ở phòng đang KHÔNG mở (không có lô / lịch BT đã Nhận phòng
 * chưa Trả phòng), được dời tới giờ quét và CHỈ đẩy lui các lô sau (ScheduleRerouteService::rerouteOverdue). Xét lần lượt theo
 * giờ bắt đầu: lô đã bị lần dời trước đẩy qua giờ quét thì bỏ qua, nên mỗi phòng thường chỉ dời lô quá giờ sớm nhất.
 *
 * Lịch: cron gọi mỗi 15 phút, nhưng mỗi giờ chỉ quét 1 lần (lần quét trọn vẹn đầu tiên trong giờ, ghi ở cache DONE_KEY).
 * Lượt quét trùng 1 trong 3 trường hợp khóa thì bỏ qua (không ghi đã quét) và thử lại ở lượt 15 phút kế tiếp:
 *   - người sắp lịch đang sắp lịch thủ công (Khóa TTSX, kể cả khóa riêng phòng cân của người sắp lịch cân),
 *   - phân xưởng đang chạy sắp lịch tự động / Kiểm soát tồn BTP,
 *   - đang có Nhận / Trả phòng (hay tịnh tuyến khác) chưa xong: khóa tịnh tuyến đang bị giữ, không chờ.
 * Gặp khóa giữa chừng thì dừng ngay; các lô đã dời trước đó giữ nguyên, lần thử lại xét tiếp phần còn lại.
 */
class RerouteOverdueBatches extends Command
{
    protected $signature = 'reroute:overdue
                            {--department= : Chỉ quét 1 phân xưởng (vd. PXV1)}
                            {--force : Quét ngay dù giờ này đã quét xong}
                            {--dry-run : Tính và in kết quả rồi hoàn tác, không lưu}
                            {--at= : Giả lập giờ quét (chỉ dùng với --dry-run), vd. "2026-11-18 15:00"}';

    protected $description = 'Mỗi giờ: dời lô quá giờ chưa Nhận phòng (phòng không mở) tới giờ quét, chỉ đẩy lui các lô sau; trùng khóa thì thử lại sau 15 phút';

    /** Tên người sửa ghi vào lịch sử đổi lịch / nhật ký tịnh tuyến. */
    public const ACTOR = 'Tự động (quét lô quá giờ)';

    /** cache: giờ quét trọn vẹn gần nhất của phân xưởng */
    public const DONE_KEY = 'reroute_overdue:done:';

    public function handle(ScheduleRerouteService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($at = $this->option('at')) {
            if (!$dryRun) {
                $this->error('--at chỉ dùng cùng --dry-run');

                return self::INVALID;
            }
            Carbon::setTestNow(Carbon::parse($at));
        }

        // ScheduleRerouteService / StagePlanHistory lấy người sửa từ session
        session()->put('user', ['fullName' => self::ACTOR, 'userName' => 'SYSTEM']);

        $stamp = now()->format('Y-m-d H:i');
        $departments = DB::table('schedule_reroute_settings')
            ->where('realtime_reroute', 1)
            ->when($this->option('department'), fn($q, $d) => $q->where('deparment_code', $d))
            ->pluck('deparment_code');

        foreach ($departments as $department) {
            // Giờ này đã quét xong thì chờ giờ sau (chạy thử / --force thì luôn quét)
            $done = Cache::get(self::DONE_KEY . $department);
            if (!$dryRun && !$this->option('force') && $done && Carbon::parse($done)->gte(now()->startOfHour())) {
                continue;
            }

            if ($block = SchedulingLock::executionBlock($department)) {
                $this->line("[{$stamp}] {$department}: bỏ qua, thử lại sau 15 phút — " . strip_tags($block));

                continue;
            }

            if ($dryRun) {
                DB::beginTransaction();
            }
            try {
                $completed = $this->scanDepartment($service, $department, $stamp);
            } finally {
                if ($dryRun) {
                    DB::rollBack();
                }
            }

            if ($completed && !$dryRun) {
                Cache::forever(self::DONE_KEY . $department, now()->toDateTimeString());
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return bool  true = quét trọn vẹn; false = gặp khóa, thử lại ở lượt 15 phút sau
     */
    private function scanDepartment(ScheduleRerouteService $service, string $department, string $stamp): bool
    {
        $now = now();
        $candidates = DB::table('stage_plan as sp')
            ->join('room as r', 'r.id', '=', 'sp.resourceId')
            ->where('sp.deparment_code', $department)
            ->where('sp.active', 1)
            ->where('sp.finished', 0)
            ->whereNull('sp.actual_start')
            ->whereIn('sp.stage_code', ScheduleRerouteService::OVERDUE_STAGES)
            ->where('r.only_maintenance', 0)
            ->whereNotNull('sp.end')
            ->whereBetween('sp.start', [
                $now->copy()->subSeconds(ScheduleRerouteService::OVERDUE_LOOKBACK_SECONDS),
                $now->copy()->subSeconds(ScheduleRerouteService::RECEIVE_MIN_LATE_SECONDS),
            ])
            ->orderBy('sp.start')
            ->orderBy('sp.id')
            ->get(['sp.id', 'sp.resourceId', 'sp.stage_code', 'sp.start', 'sp.title', 'r.code as room_code']);

        // Phòng đang mở thì không dời (rerouteOverdue kiểm lại trong khóa tịnh tuyến, phòng khi vừa Nhận phòng)
        $open = ScheduleRerouteService::openRoomIds($candidates->pluck('resourceId')->map(fn($id) => (int) $id)->all());
        $openRooms = $candidates->whereIn('resourceId', $open)->pluck('room_code')->unique()->sort()->values();
        $candidates = $candidates->whereNotIn('resourceId', $open)->values();

        $moved = 0;
        $shifted = 0;
        $completed = true;
        foreach ($candidates as $c) {
            // Khóa bật giữa chừng (sắp lịch thủ công / tự động): dừng, thử lại sau 15 phút
            if ($block = SchedulingLock::executionBlock($department)) {
                $this->line("[{$stamp}] {$department}: dừng giữa chừng, thử lại sau 15 phút — " . strip_tags($block));
                $completed = false;

                break;
            }
            // Người sắp lịch cân đang khóa riêng phòng cân: bỏ qua lô cân, cả lượt thử lại sau 15 phút
            if (SchedulingLock::executionBlock($department, $c->stage_code)) {
                $completed = false;

                continue;
            }

            try {
                $r = $service->rerouteOverdue((int) $c->id);
            } catch (RerouteBusyException $e) {
                $this->line("[{$stamp}] {$department}: đang có Nhận / Trả phòng chưa xong, dừng, thử lại sau 15 phút");
                $completed = false;

                break;
            } catch (Throwable $e) {
                $this->error("[{$stamp}] {$department}: lỗi lô #{$c->id} — " . $e->getMessage());
                Log::error('[Reroute] Quét lô quá giờ thất bại cho stage_plan ' . $c->id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                continue;
            }

            if (!$r['run_code']) {
                continue;
            }

            $moved++;
            $shifted += count($r['changes']);
            $source = collect($r['changes'])->firstWhere('id', (int) $c->id);
            $this->line(sprintf(
                '[%s] %s %s: %s (#%d) %s → %s, trễ %d phút, dịch %d lô · %s',
                $stamp,
                $department,
                $c->room_code,
                strip_tags(str_replace(['<br/>', '<br>'], ' ', (string) $c->title)),
                $c->id,
                Carbon::parse($c->start)->format('H:i d/m'),
                $source ? Carbon::parse($source['new']['start'])->format('H:i d/m') : '?',
                $r['delta_minutes'],
                count($r['changes']),
                $r['run_code']
            ));
        }

        $this->info(sprintf('[%s] %s%s: %d lô quá giờ chưa Nhận phòng ở phòng không mở, dời %d lô gốc, tổng %d lô bị dịch%s%s',
            $stamp, $this->option('dry-run') ? '(chạy thử) ' : '', $department, $candidates->count(), $moved, $shifted,
            $openRooms->isEmpty() ? '' : '; bỏ qua phòng đang mở: ' . $openRooms->implode(', '),
            $completed ? '' : '; CHƯA xong, thử lại sau 15 phút'));

        return $completed;
    }
}
