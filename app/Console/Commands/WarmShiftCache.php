<?php

namespace App\Console\Commands;

use App\Services\EmployeeRosterSync;
use App\Services\ShiftApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Nạp sẵn cache lịch trực để luồng web không phải gọi eO2 PMS.
 *
 * Lý do tồn tại: mỗi lần dựng một tháng của một bộ phận tốn 3 request tới máy
 * chủ nguồn (6 với PXV1 vì gộp thêm Kho), mỗi request ~9.5s. Nếu để người dùng
 * gánh phần chờ đó thì trang mất ~20s, và tệ hơn: nhiều người cùng gặp cache
 * miss sẽ dội hàng chục request đồng thời vào eO2 và bị trả HTTP 429 — giới hạn
 * `shiftapi.max_concurrency` chỉ chặn được trong PHẠM VI MỘT tiến trình PHP,
 * không chặn được giữa các request web song song.
 *
 * TUẦN TỰ THÔI LÀ CHƯA ĐỦ. Đo thực tế ngày 18/08/2026 trên server: chạy liền
 * mạch hết bộ phận này tới bộ phận khác thì sau ~24 request trong ~175s, eO2 bắt
 * đầu trả 429 cho mọi thứ còn lại — nhận ra ngay vì các lượt đó thất bại trong
 * 0s (server từ chối tức thì, không xử lý). Nên command còn phải:
 *   - nghỉ giữa mỗi lượt (`--pause`) để trải đều tải theo thời gian,
 *   - khi bị chặn thì lùi lại đúng số giây `Retry-After` mà eO2 yêu cầu rồi thử
 *     lại (`--retries`), thay vì bỏ luôn cả bộ phận.
 *
 * Chạy nền nên chậm bao lâu cũng không ai phải đợi.
 */
class WarmShiftCache extends Command
{
    protected $signature = 'shifts:warm-cache
                            {--department= : Chỉ nạp một bộ phận (VD: PXV1). Bỏ trống = tất cả}
                            {--months=1 : Số tháng nạp, tính từ tháng hiện tại trở đi}
                            {--pause=15 : Số giây nghỉ giữa hai lượt nạp, để không dồn tải lên eO2}
                            {--retries=2 : Số lần thử lại khi bị rate limit}';

    protected $description = 'Nạp sẵn cache lịch trực từ API eO2 PMS để trang Lịch công tác không phải chờ API';

    public function handle(ShiftApiService $shiftApi): int
    {
        $only = $this->option('department');
        $months = max(1, (int) $this->option('months'));
        $pause = max(0, (int) $this->option('pause'));
        $retries = max(0, (int) $this->option('retries'));

        $departments = EmployeeRosterSync::DEPARTMENTS;
        if ($only) {
            if (!isset($departments[$only])) {
                $this->error("Bộ phận '{$only}' không có trong bảng ánh xạ. Hợp lệ: " . implode(', ', array_keys($departments)));
                return self::FAILURE;
            }
            $departments = [$only => $departments[$only]];
        }

        $ok = 0;
        $failed = 0;
        $first = true;
        $startedAll = microtime(true);

        foreach ($departments as $code => $depId) {
            // PXV1 có một số nhân sự Kho làm tại Trung Tâm Cân -> phải nạp kèm,
            // đúng cờ mà controller truyền vào để trúng cùng khoá cache.
            $mergeWarehouse = $depId === 15;

            for ($i = 0; $i < $months; $i++) {
                $cursor = Carbon::now()->startOfMonth()->addMonths($i);
                $month = (int) $cursor->month;
                $year = (int) $cursor->year;
                $label = sprintf('%s %02d/%d', $code, $month, $year);

                // Nghỉ giữa các lượt để trải tải, nhưng không nghỉ trước lượt đầu.
                if (!$first && $pause > 0) {
                    sleep($pause);
                }
                $first = false;

                if ($this->warmOne($shiftApi, $label, $code, $month, $year, $depId, $mergeWarehouse, $retries)) {
                    $ok++;
                } else {
                    $failed++;
                }
            }
        }

        $total = round(microtime(true) - $startedAll, 1);
        $this->line("Xong: {$ok} thành công, {$failed} thất bại, tổng {$total}s");

        return $failed > 0 && $ok === 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Nạp một tháng của một bộ phận, thử lại khi bị rate limit.
     *
     * @return bool true nếu cache nóng thực sự được ghi mới
     */
    private function warmOne(
        ShiftApiService $shiftApi,
        string $label,
        string $code,
        int $month,
        int $year,
        int $depId,
        bool $mergeWarehouse,
        int $retries
    ): bool {
        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            $this->line($attempt === 0
                ? "Đang nạp {$label} ..."
                : "Đang nạp lại {$label} (lần {$attempt}/{$retries}) ...");

            $started = microtime(true);

            try {
                // Bỏ cache nóng trước, nếu không thì lần chạy này chỉ đọc lại
                // bản cũ và không có tác dụng làm mới.
                $shiftApi->forgetMonth($month, $year, $depId, $mergeWarehouse);
                $data = $shiftApi->monthlyByDayKey($month, $year, $depId, $mergeWarehouse);
            } catch (\Throwable $e) {
                $this->error("  {$label}: lỗi - " . $e->getMessage());
                Log::warning('shifts:warm-cache that bai', [
                    'department' => $code,
                    'month' => $month,
                    'year' => $year,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }

            $elapsed = round(microtime(true) - $started, 1);

            // Cache nóng có đủ = lượt này thực sự nạp được số liệu mới.
            // Chỉ nhìn $data thì không đủ: nó vẫn ra mảng khi rơi về bản sao lưu.
            if ($shiftApi->hasFreshMonth($month, $year, $depId, $mergeWarehouse)) {
                $this->info("  {$label}: " . count($data ?? []) . " nhân sự ({$elapsed}s)");
                return true;
            }

            $wait = $shiftApi->rateLimitedFor();

            if ($wait === null) {
                // Không phải rate limit -> timeout hoặc lỗi thật. Thử lại ngay
                // cũng vô ích, chi tiết đã nằm trong laravel.log.
                $this->warn("  {$label}: API không trả dữ liệu ({$elapsed}s) - xem laravel.log");
                return false;
            }

            if ($attempt === $retries) {
                $this->error("  {$label}: bị eO2 chặn (429), đã thử {$retries} lần - bỏ qua");
                Log::warning('shifts:warm-cache bi rate limit, het luot thu lai', [
                    'department' => $code,
                    'month' => $month,
                    'year' => $year,
                ]);
                return false;
            }

            $this->warn("  {$label}: bị eO2 chặn (429), chờ {$wait}s rồi thử lại");
            sleep($wait);
        }

        return false;
    }
}
