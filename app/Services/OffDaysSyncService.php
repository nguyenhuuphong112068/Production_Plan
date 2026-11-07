<?php

namespace App\Services;

use App\Support\OffDays;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Đồng bộ lịch nghỉ công ty (bảng `off_days`) từ API ca trực eO2 PMS.
 *
 * Từ 06/11/2026 `off_days` không còn nhập tay (đã bỏ màn hình Dữ Liệu Gốc ›
 * Cập nhật ngày nghỉ). Nguồn duy nhất là cờ `days.<dd/mm/yyyy>.is_holiday` của
 * endpoint `range`:
 *
 *   - `is_holiday` là lịch nghỉ CHUNG của công ty: đo 06/11/2026 trên 10 bộ phận
 *     (1..10) cho 6 tháng tới, mọi bộ phận ra cùng một tập 46 ngày và trong một
 *     bộ phận mọi nhân viên cùng một giá trị cho từng ngày. Cờ có sẵn cho cả
 *     những tháng chưa phân ca.
 *   - Vì thế chỉ cần hỏi bộ phận NHẸ nhất: bộ phận 4 (3 nhân viên, ~25KB, ~1s cho
 *     6 tháng; PXTN 6 là ~275KB, bộ phận 10 là ~1.8MB/27s). Nếu bộ phận đó không
 *     còn nhân viên thì thử lần lượt các bộ phận dự phòng.
 *   - Quy tắc chung "Chủ nhật luôn nghỉ" (user chốt 06/11/2026) thắng API: API
 *     có đánh dấu vài Chủ nhật làm bù (22/11/2026, 21/02/2027, 21/03/2027) nhưng
 *     PMS vẫn ghi các ngày đó là ngày nghỉ, khớp `OffDays::isRestDay()`.
 *
 * Chỉ đối chiếu trong cửa sổ [hôm nay, hôm nay + N tháng] và chỉ những ngày API
 * thực sự trả về; ngày đã qua giữ nguyên làm lịch sử. Gọi API lỗi hoặc không có
 * dữ liệu thì ném lỗi, KHÔNG xoá gì.
 */
class OffDaysSyncService
{
    /** Bộ phận hỏi API, theo thứ tự ưu tiên (nhẹ nhất trước). */
    public const DEPARTMENTS = [4, 5, 7, 1, 8, 6];

    public const REASON = 'Ngày Nghỉ Công Ty';

    /**
     * @return array{department:int, from:string, to:string, holidays:string[],
     *               added:string[], removed:string[], dry_run:bool}
     */
    public function sync(int $months = 6, bool $dryRun = false, ?callable $log = null): array
    {
        $log ??= fn(string $msg) => null;

        $from = Carbon::today();
        $to = $from->copy()->addMonthsNoOverflow($months);

        [$department, $days] = $this->fetchHolidayFlags($from, $to, $log);

        // Ngày nghỉ = API báo nghỉ, cộng mọi Chủ nhật (quy tắc chung)
        $wanted = [];
        foreach ($days as $date => $isHoliday) {
            if ($isHoliday || Carbon::parse($date)->isSunday()) {
                $wanted[$date] = true;
            }
        }

        $existing = DB::table('off_days')
            ->whereBetween('off_date', [$from->toDateString(), $to->toDateString()])
            ->pluck('off_date')
            ->map(fn($d) => substr((string) $d, 0, 10))
            ->all();
        $existing = array_fill_keys($existing, true);

        $added = array_keys(array_diff_key($wanted, $existing));
        // Chỉ xoá ngày API có trả về và báo đi làm, ngày API không nhắc tới thì để nguyên
        $removed = array_keys(array_filter(
            array_diff_key($existing, $wanted),
            fn($v, $date) => array_key_exists($date, $days),
            ARRAY_FILTER_USE_BOTH
        ));
        sort($added);
        sort($removed);

        if (!$dryRun && ($added || $removed)) {
            DB::transaction(function () use ($added, $removed) {
                if ($removed) {
                    DB::table('off_days')->whereIn('off_date', $removed)->delete();
                }
                if ($added) {
                    DB::table('off_days')->insertOrIgnore(array_map(fn($d) => [
                        'off_date' => $d,
                        'reason' => self::REASON,
                        'created_at' => now(),
                    ], $added));
                }
            });
            OffDays::flush();
        }

        $holidays = array_keys(array_filter($days));
        sort($holidays);

        return [
            'department' => $department,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'holidays' => $holidays,
            'added' => $added,
            'removed' => $removed,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Gọi `range` cho bộ phận đầu tiên có nhân viên.
     *
     * @return array{0:int, 1:array<string,bool>} [bộ phận, ['Y-m-d' => là ngày nghỉ]]
     */
    private function fetchHolidayFlags(Carbon $from, Carbon $to, callable $log): array
    {
        $errors = [];
        foreach (self::DEPARTMENTS as $department) {
            try {
                $payload = $this->fetchRange($from, $to, $department, $log);
            } catch (RuntimeException $e) {
                $errors[] = "bộ phận {$department}: " . $e->getMessage();
                $log("Bộ phận {$department}: " . $e->getMessage());
                continue;
            }

            $days = $this->holidayFlags($payload);
            if ($days) {
                return [$department, $days];
            }
            $errors[] = "bộ phận {$department}: không có nhân viên";
            $log("Bộ phận {$department} không có nhân viên, thử bộ phận kế tiếp");
        }

        throw new RuntimeException('Không lấy được lịch nghỉ từ eO2 (' . implode('; ', $errors) . ')');
    }

    private function fetchRange(Carbon $from, Carbon $to, int $department, callable $log): array
    {
        $url = rtrim((string) config('shiftapi.base_url'), '/') . '/range';
        $query = [
            'fromdate' => $from->toDateString(),
            'todate' => $to->toDateString(),
            'department' => $department,
        ];

        // eO2 vẫn trả 429 khi bị gọi dồn (đo 06/11/2026: ~10 lượt liên tiếp) nên
        // chờ theo Retry-After rồi thử lại, tối đa 2 lần.
        for ($attempt = 0; ; $attempt++) {
            try {
                $response = Http::withOptions(['verify' => (bool) config('shiftapi.verify_tls', false)])
                    ->timeout((int) config('shiftapi.timeout', 90))
                    ->acceptJson()
                    ->get($url, $query);
            } catch (\Throwable $e) {
                throw new RuntimeException('lỗi kết nối: ' . $e->getMessage(), 0, $e);
            }

            if ($response->status() === 429 && $attempt < 2) {
                $wait = min(180, max(30, (int) $response->header('Retry-After') ?: 65));
                $log("eO2 trả 429, chờ {$wait}s rồi thử lại");
                sleep($wait);
                continue;
            }
            if (!$response->successful()) {
                throw new RuntimeException('HTTP ' . $response->status());
            }

            $json = $response->json();
            if (!is_array($json)) {
                throw new RuntimeException('dữ liệu trả về không phải JSON');
            }
            return $json;
        }
    }

    /**
     * Quy dữ liệu từng nhân viên về từng ngày: nghỉ khi quá nửa số nhân viên có
     * ngày đó mang `is_holiday = true` (thực tế luôn là tất cả hoặc không ai).
     *
     * @return array<string,bool>
     */
    private function holidayFlags(array $payload): array
    {
        $total = [];
        $holiday = [];
        foreach ($payload as $person) {
            foreach ((array) ($person['days'] ?? []) as $rawDay => $day) {
                $date = $this->toDate((string) $rawDay);
                if ($date === null) {
                    continue;
                }
                $total[$date] = ($total[$date] ?? 0) + 1;
                if (is_array($day) && !empty($day['is_holiday'])) {
                    $holiday[$date] = ($holiday[$date] ?? 0) + 1;
                }
            }
        }

        $flags = [];
        foreach ($total as $date => $count) {
            $flags[$date] = ($holiday[$date] ?? 0) * 2 > $count;
        }
        ksort($flags);
        return $flags;
    }

    /** "DD/MM/YYYY" (hoặc "YYYY-MM-DD") → "Y-m-d" */
    private function toDate(string $raw): ?string
    {
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', trim($raw), $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('#^\d{4}-\d{2}-\d{2}#', $raw)) {
            return substr($raw, 0, 10);
        }
        return null;
    }
}
