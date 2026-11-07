<?php

namespace App\Console\Commands;

use App\Services\OffDaysSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Đồng bộ bảng `off_days` từ cờ `is_holiday` của API eO2 (xem OffDaysSyncService).
 * Chạy mỗi ngày lúc 05:30 (routes/console.php).
 */
class SyncOffDays extends Command
{
    protected $signature = 'offdays:sync
                            {--months=6 : Số tháng tính từ hôm nay}
                            {--dry-run : Chỉ in chênh lệch, không ghi CSDL}';

    protected $description = 'Đồng bộ ngày nghỉ công ty (off_days) từ API ca trực eO2 PMS';

    public function handle(OffDaysSyncService $service): int
    {
        $months = max(1, (int) $this->option('months'));
        $dryRun = (bool) $this->option('dry-run');
        $stamp = now()->format('Y-m-d H:i:s');

        try {
            $r = $service->sync($months, $dryRun, fn(string $msg) => $this->line("[{$stamp}] {$msg}"));
        } catch (Throwable $e) {
            $this->error("[{$stamp}] Đồng bộ ngày nghỉ thất bại: " . $e->getMessage());
            Log::error('offdays:sync thất bại', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        $this->info(sprintf(
            '[%s] %sBộ phận %d, %s → %s: API báo %d ngày nghỉ; thêm %d, xoá %d.',
            $stamp,
            $dryRun ? '(chạy thử) ' : '',
            $r['department'],
            $r['from'],
            $r['to'],
            count($r['holidays']),
            count($r['added']),
            count($r['removed'])
        ));
        if ($r['added']) {
            $this->line('  + ' . implode(', ', $r['added']));
        }
        if ($r['removed']) {
            $this->line('  - ' . implode(', ', $r['removed']));
        }

        if (!$dryRun && ($r['added'] || $r['removed'])) {
            Log::info('offdays:sync', ['added' => $r['added'], 'removed' => $r['removed']]);
        }

        return self::SUCCESS;
    }
}
