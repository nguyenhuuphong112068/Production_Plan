<?php

namespace App\Plugins\WipControl\Services;

use App\Http\Controllers\Pages\Schedual\SchedualController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bộ sắp lịch tự động của lõi, cộng thêm mốc "không sớm hơn" cho từng lô.
 *
 * Kế thừa SchedualController để gọi lại đúng các hàm sắp lịch đang dùng
 * (bán thành phẩm → cảnh báo NL/BB → nhạy cảm → thương mại → khuyến mãi) mà
 * không phải chép hay sửa controller. Chỗ duy nhất khác là extraEarliestStart():
 * lõi trả về null, ở đây trả về mốc lùi đầu nguồn plugin đã tính cho lô đó.
 *
 * Mỗi vòng lặp dùng một đối tượng mới để không mang theo bộ nhớ đệm phòng/khuôn
 * của vòng trước.
 */
class WipAwareScheduler extends SchedualController
{
    /** @var array<int, Carbon> plan_master_id => mốc sớm nhất */
    protected array $wipHints = [];

    /** Mã công đoạn theo tên bước trong modal, giống runScheduleAll */
    public const STEP_CODES = [
        'PC'  => 3,
        'THT' => 4,
        'ĐH'  => 5,
        'BP'  => 6,
        'ĐG'  => 7,
    ];

    /**
     * Thời gian chờ kết quả kiểm nghiệm (phút) trước khi vào công đoạn, đúng
     * mặc định Auto_scheduler_Stage_Forward đang dùng trong runScheduleAll.
     *
     * @return array<int, array{normal: int, val: int}>
     */
    public static function waitTimes(Request $request): array
    {
        $days = fn($key, $default) => (float) ($request->input($key) ?? $default) * 24 * 60;

        return [
            3 => ['normal' => 0, 'val' => 0],
            4 => ['normal' => (int) $days('wt_bleding', 0),   'val' => (int) $days('wt_bleding_val', 1)],
            5 => ['normal' => (int) $days('wt_forming', 0),   'val' => (int) $days('wt_forming_val', 5)],
            6 => ['normal' => (int) $days('wt_coating', 0),   'val' => (int) $days('wt_coating_val', 5)],
            7 => ['normal' => (int) $days('wt_blitering', 0), 'val' => (int) $days('wt_blitering_val', 5)],
        ];
    }

    /** Nạp đúng các tham số runScheduleAll đọc từ modal */
    public function configure(Request $request, int $selectedStep, array $hints): static
    {
        $this->selectedDates = $request->selectedDates ?? [];
        $this->work_sunday = $request->work_sunday ?? false;
        $this->reason = 'Kiểm soát tồn BTP: ' . ($request->reason ?? 'NA');
        $this->prev_orderBy = $request->prev_orderBy ?? false;
        $this->limit_mold_change = filter_var($request->limit_mold_change ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->mold_change_tolerance = max(0, (float) ($request->mold_change_tolerance ?? 72));
        $this->max_Step = $selectedStep;
        $this->wipHints = $hints;

        $this->loadOffDate('asc');

        return $this;
    }

    /**
     * Sắp lại các lô chưa có lịch theo đúng trình tự phần sắp lịch theo công đoạn
     * của runScheduleAll (giống đoạn cuối runScheduleAllPass2).
     */
    public function rescheduleUnscheduled(Request $request, Carbon $startDate, int $selectedStep): void
    {
        $waits = self::waitTimes($request);

        $stageCodes = DB::table('stage_plan as sp')
            ->distinct()
            ->where('sp.stage_code', '>=', 3)
            ->where('sp.stage_code', '<=', $selectedStep)
            ->where('sp.deparment_code', session('user.production_code'))
            ->orderBy('sp.stage_code')
            ->pluck('sp.stage_code');

        for ($i = $selectedStep; $i >= 3; $i--) {
            $this->scheduleIntermediate($i, 0, 0, $startDate);
        }

        for ($i = $selectedStep; $i >= 3; $i--) {
            $this->scheduleWarningMR($i, 0, 0, $startDate);
        }

        for ($i = 3; $i <= $selectedStep; $i++) {
            $this->scheduleSensitiveProduct($i, 0, 0, $startDate);
        }

        foreach ([0, 1] as $promotionalFilter) {
            foreach ($stageCodes as $i) {
                $wait = $waits[(int) $i] ?? ['normal' => 0, 'val' => 0];

                $this->Auto_scheduler_Stage_Forward((int) $i, $wait['normal'], $wait['val'], $startDate, $promotionalFilter);
            }
        }
    }

    /**
     * Bản sao lưu lịch của phân xưởng bằng đúng nút "Tạo bản sao lưu" trong modal,
     * nên khôi phục được ngay từ danh sách "Khôi phục". Trả về mã bản sao lưu.
     */
    public function makeBackup(): ?string
    {
        return $this->backup_schedualer()->getData()->bkcCode ?? null;
    }

    /** Campaign đang bị quá hạn biệt trữ, giống Pass 2 dùng làm VIP */
    public function overdueCampaignCodes(): array
    {
        return array_values(array_filter(array_column($this->scanOverdueTasks(), 'campaign_code')));
    }

    protected function extraEarliestStart($task): ?Carbon
    {
        $id = (int) ($task->plan_master_id ?? 0);

        return isset($this->wipHints[$id]) ? $this->wipHints[$id]->copy() : null;
    }
}
