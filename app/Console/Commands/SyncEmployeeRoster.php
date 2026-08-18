<?php

namespace App\Console\Commands;

use App\Services\EmployeeRosterSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Đồng bộ danh sách nhân sự từ eO2 PMS về DB.
 *
 * Trước đây việc này chạy chặn ngay trong luồng đăng nhập, nhưng máy chủ nguồn
 * mất ~9.5s (PXTN) đến ~88s (PXV1) cho MỖI request nên không thể để người dùng
 * chờ. Command này gánh phần chờ đó ở chạy nền, đồng thời nạp sẵn cache để lần
 * đăng nhập sau đồng bộ tức thì mà không cần gọi API.
 */
class SyncEmployeeRoster extends Command
{
    protected $signature = 'employees:sync-roster
                            {--department= : Chỉ đồng bộ một bộ phận (VD: PXV1). Bỏ trống = tất cả}
                            {--timeout= : Timeout cho mỗi lượt gọi API (giây). Mặc định lấy từ shiftapi.manual_sync_timeout}';

    protected $description = 'Đồng bộ danh sách nhân sự từ API eO2 PMS về bảng employees / employee_assignments';

    public function handle(EmployeeRosterSync $sync): int
    {
        $only = $this->option('department');
        $timeout = (int) ($this->option('timeout') ?: config('shiftapi.manual_sync_timeout', 180));

        $departments = array_keys(EmployeeRosterSync::DEPARTMENTS);
        if ($only) {
            if (!isset(EmployeeRosterSync::DEPARTMENTS[$only])) {
                $this->error("Bộ phận '{$only}' không có trong bảng ánh xạ. Hợp lệ: " . implode(', ', $departments));
                return self::FAILURE;
            }
            $departments = [$only];
        }

        $ok = 0;
        $failed = 0;

        foreach ($departments as $code) {
            $this->line("Đang đồng bộ {$code} ...");
            $started = microtime(true);

            try {
                // Chạy lần lượt từng bộ phận, KHÔNG song song: máy chủ nguồn có
                // rate limit, dồn nhiều request nặng cùng lúc sẽ bị trả HTTP 429.
                $result = $sync->refresh($code, $timeout);
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  {$code}: lỗi - " . $e->getMessage());
                Log::warning('employees:sync-roster that bai', ['department' => $code, 'error' => $e->getMessage()]);
                continue;
            }

            $elapsed = round(microtime(true) - $started, 1);

            if ($result['synced']) {
                $ok++;
                $this->info("  {$code}: {$result['employees']} nhân sự ({$elapsed}s)");
            } else {
                $failed++;
                $this->warn("  {$code}: bỏ qua - {$result['reason']} ({$elapsed}s)");
            }
        }

        $this->newLine();
        $this->line("Xong: {$ok} thành công, {$failed} không đồng bộ được.");

        return $failed > 0 && $ok === 0 ? self::FAILURE : self::SUCCESS;
    }
}
