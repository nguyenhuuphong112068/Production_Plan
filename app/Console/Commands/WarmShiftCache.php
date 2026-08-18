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
 * Command này gánh toàn bộ phần chờ đó ở chạy nền, tuần tự từng bộ phận nên
 * không bao giờ dồn tải. Chạy nền thì chậm bao lâu cũng không ai phải đợi.
 */
class WarmShiftCache extends Command
{
    protected $signature = 'shifts:warm-cache
                            {--department= : Chỉ nạp một bộ phận (VD: PXV1). Bỏ trống = tất cả}
                            {--months=2 : Số tháng nạp, tính từ tháng hiện tại trở đi}';

    protected $description = 'Nạp sẵn cache lịch trực từ API eO2 PMS để trang Lịch công tác không phải chờ API';

    public function handle(ShiftApiService $shiftApi): int
    {
        $only = $this->option('department');
        $months = max(1, (int) $this->option('months'));

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

                $this->line("Đang nạp {$label} ...");
                $started = microtime(true);

                try {
                    // Bỏ cache nóng trước, nếu không thì lần chạy này chỉ đọc lại
                    // bản cũ và không có tác dụng làm mới.
                    $shiftApi->forgetMonth($month, $year, $depId, $mergeWarehouse);
                    $data = $shiftApi->monthlyByDayKey($month, $year, $depId, $mergeWarehouse);
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("  {$label}: lỗi - " . $e->getMessage());
                    Log::warning('shifts:warm-cache that bai', [
                        'department' => $code,
                        'month' => $month,
                        'year' => $year,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }

                $elapsed = round(microtime(true) - $started, 1);

                // null = API lỗi VÀ hết bản sao lưu. Không có gì để cache.
                if ($data === null) {
                    $failed++;
                    $this->warn("  {$label}: API không trả dữ liệu ({$elapsed}s) - xem laravel.log");
                    continue;
                }

                $ok++;
                $this->info("  {$label}: " . count($data) . " nhân sự ({$elapsed}s)");
            }
        }

        $total = round(microtime(true) - $startedAll, 1);
        $this->line("Xong: {$ok} thành công, {$failed} thất bại, tổng {$total}s");

        return $failed > 0 && $ok === 0 ? self::FAILURE : self::SUCCESS;
    }
}
