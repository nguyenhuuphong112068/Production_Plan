<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Xem tình trạng cache của dữ liệu lịch trực / nhân sự.
 *
 * Bảng `cache` của Laravel chỉ có 3 cột `key`, `value`, `expiration` — KHÔNG có
 * cột thời điểm ghi. Nhưng mỗi loại khoá dùng một TTL cố định nên suy ngược được:
 *
 *      thời điểm ghi = expiration - TTL
 *
 * Command này làm sẵn phép trừ đó, đồng thời giải nén giá trị để đếm số nhân sự.
 */
class ShiftApiCacheStatus extends Command
{
    protected $signature = 'shiftapi:cache-status {--all : Hiện cả những khoá đã hết hạn}';

    protected $description = 'Xem dữ liệu lịch trực / nhân sự trong cache được cập nhật lúc nào';

    /** Khoá -> [nhãn, TTL giây]. TTL phải khớp với lúc ghi trong code. */
    private function ttlFor(string $key): array
    {
        $cacheTtl = (int) config('shiftapi.cache_ttl', 120);
        $backupTtl = (int) config('shiftapi.backup_ttl', 86400);
        $rosterTtl = (int) config('shiftapi.roster_cache_ttl', 21600);
        $syncTtl = (int) ((float) config('shiftapi.login_sync_interval_hours', 12) * 3600);

        return match (true) {
            str_contains($key, 'shiftapi:month_backup:') => ['Lịch trực (bản sao lưu)', $backupTtl],
            str_contains($key, 'shiftapi:month:') => ['Lịch trực (cache nóng)', $cacheTtl],
            str_contains($key, 'shiftapi:roster_backup:') => ['Danh sách NS (bản sao lưu)', $backupTtl],
            str_contains($key, 'shiftapi:roster:') => ['Danh sách nhân sự', $rosterTtl],
            str_contains($key, 'employee_sync_last_run:') => ['Mốc đã đồng bộ NS', $syncTtl],
            str_contains($key, 'employee_sync_stale_warned:') => ['Cảnh báo cache rỗng', 3600],
            // Khoá chống bấm "làm mới" dồn dập (Cache::add ... 60 giây)
            str_contains($key, 'shiftapi:refresh:') => ['Khoá chống làm mới dồn', 60],
            // Bộ đếm hạn ngạch gọi API theo cửa sổ thời gian
            str_contains($key, 'shiftapi:quota:') => ['Hạn ngạch gọi API', max(1, (int) config('shiftapi.rate_window', 300)) + 60],
            default => ['Khác', 0],
        };
    }

    /** Bảng `cache` đã có cột `updated_at` do MySQL tự ghi hay chưa. */
    private bool $hasUpdatedAt = false;

    public function handle(): int
    {
        $prefix = (string) config('cache.prefix');
        $now = time();

        $this->hasUpdatedAt = \Illuminate\Support\Facades\Schema::hasColumn('cache', 'updated_at');

        $rows = DB::table('cache')
            ->where('key', 'like', $prefix . 'shiftapi:%')
            ->orWhere('key', 'like', $prefix . 'employee_sync_%')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('Cache trống. Chạy: php artisan employees:sync-roster');
            return self::SUCCESS;
        }

        $out = [];
        foreach ($rows as $row) {
            $key = str_starts_with($row->key, $prefix) ? substr($row->key, strlen($prefix)) : $row->key;
            [$label, $ttl] = $this->ttlFor($key);

            $expired = $row->expiration <= $now;
            if ($expired && !$this->option('all')) {
                continue;
            }

            // Ưu tiên cột `updated_at` (do MySQL tự ghi) vì luôn chính xác.
            // Chỉ suy ngược từ (expiration - TTL) khi chưa chạy migration thêm cột,
            // hoặc với khoá không biết TTL.
            if ($this->hasUpdatedAt && !empty($row->updated_at)) {
                $writtenAt = strtotime($row->updated_at);
            } else {
                $writtenAt = $ttl > 0 ? $row->expiration - $ttl : null;
            }

            $out[] = [
                'key' => $key,
                'label' => $label,
                'written' => $writtenAt ? date('d/m/Y H:i:s', $writtenAt) : '?',
                'age' => $writtenAt ? $this->humanAge($now - $writtenAt) : '?',
                'expires' => date('d/m/Y H:i:s', $row->expiration)
                    . ($expired ? ' (HẾT HẠN)' : ''),
                'detail' => $this->describe($key, $row->value),
                'sort' => $writtenAt ?: $row->expiration,
            ];
        }

        if (empty($out)) {
            $this->warn('Không có khoá nào còn hiệu lực. Thêm --all để xem cả khoá đã hết hạn.');
            return self::SUCCESS;
        }

        usort($out, fn($a, $b) => $b['sort'] <=> $a['sort']);

        $this->table(
            ['Khoá', 'Loại', 'Cập nhật lúc', 'Cách đây', 'Hết hạn lúc', 'Nội dung'],
            array_map(fn($r) => [$r['key'], $r['label'], $r['written'], $r['age'], $r['expires'], $r['detail']], $out)
        );

        $this->newLine();
        $this->line($this->hasUpdatedAt
            ? 'Cột "Cập nhật lúc" lấy trực tiếp từ `cache`.`updated_at` (MySQL tự ghi).'
            : 'Bảng `cache` chưa có cột `updated_at` — "Cập nhật lúc" đang được suy ra từ (expiration - TTL).'
              . ' Chạy migration 2026_08_19_090000_add_updated_at_to_cache_table để có số liệu chính xác.');

        return self::SUCCESS;
    }

    private function humanAge(int $seconds): string
    {
        if ($seconds < 0) return '0 giây';
        if ($seconds < 60) return $seconds . ' giây';
        if ($seconds < 3600) return floor($seconds / 60) . ' phút';
        if ($seconds < 86400) return floor($seconds / 3600) . ' giờ ' . floor(($seconds % 3600) / 60) . ' phút';
        return floor($seconds / 86400) . ' ngày ' . floor(($seconds % 86400) / 3600) . ' giờ';
    }

    /** Giải mã giá trị để hiện thông tin có ích (số nhân sự, mốc thời gian...). */
    private function describe(string $key, $raw): string
    {
        $value = @unserialize($raw);
        if ($value === false && $raw !== serialize(false)) {
            $value = $raw;
        }

        if (is_string($value) && str_starts_with($value, 'gz:')) {
            $json = @gzuncompress(base64_decode(substr($value, 3)));
            $data = $json === false ? null : json_decode($json, true);
            if (is_array($data)) {
                $n = count($data);
                $kb = round(strlen($raw) / 1024);
                return str_contains($key, 'roster')
                    ? "{$n} nhân sự ({$kb}KB)"
                    : "{$n} nhân sự, dữ liệu cả tháng ({$kb}KB)";
            }
            return 'nén, không giải mã được';
        }

        if (is_array($value)) {
            return count($value) . ' mục';
        }

        if (str_contains($key, 'shiftapi:quota:') && is_numeric($value)) {
            return $value . ' / ' . config('shiftapi.rate_limit', 18) . ' lượt gọi API';
        }

        return is_scalar($value) ? (string) $value : gettype($value);
    }
}
