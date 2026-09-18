<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cổng giao tiếp duy nhất với API lịch trực của eO2 PMS.
 *
 * Hệ thống nguồn đã bỏ endpoint `by-department?month=&year=` và thay bằng 3
 * endpoint nhận khoảng ngày thật (`range`, `leave`, `overtime`). Nhờ vậy quy tắc
 * "day21..day31 thuộc tháng trước" của API cũ không còn nữa: mọi thứ ở đây đều
 * làm việc trên ngày lịch thật (Y-m-d).
 *
 * Cách hợp nhất 3 nguồn cho một ngày:
 *   - `range`    cho mã ca (C1/C2/C3/C4/HC...) và cờ ngày lễ.
 *   - `leave`    là nguồn DUY NHẤT quyết định một ngày có phải nghỉ phép hay
 *                không (xem `isCountedLeaveStatus`): có đơn được tính thì ghi đè
 *                mã ca thành 'P'. Mã 'P' do `range` tự trả về mà `leave` không
 *                xác nhận sẽ bị quy về 'HC' - xem giải thích trong `buildIndex`.
 *   - `overtime` cho số giờ tăng ca, không xét status theo yêu cầu nghiệp vụ.
 */
class ShiftApiService
{
    /**
     * Khoá cache giữ mốc thời gian được phép gọi eO2 trở lại sau khi bị trả 429.
     *
     * Nằm trong bảng `cache` nên MỌI tiến trình cùng thấy: đây là điểm mà
     * `$rateLimitedFor` không làm được vì nó chết theo request.
     */
    private const BREAKER_KEY = 'shiftapi:blocked_until';

    /**
     * Số lần bị 429 liên tiếp, dùng để nhân đôi thời gian chờ. Nằm trong cache
     * vì mỗi lần thử lại là một tiến trình khác nhau.
     */
    private const BREAKER_STREAK_KEY = 'shiftapi:blocked_streak';

    /**
     * Thời gian chờ khi eO2 KHÔNG gửi `Retry-After` (thực tế nó chưa bao giờ
     * gửi), nhân đôi sau mỗi lần bị chặn liên tiếp: 60s, 120s, 240s, 480s.
     *
     * Đo thực tế 18/09/2026 - lý do phải nhân đôi thay vì giữ 60s: lượt 11:10:02
     * chỉ 1 trong 6 endpoint bị 429, nhưng hai lượt thử lại sau đúng 60s
     * (11:11:02 và 11:12:02) thì CẢ 6 đều 429. Tức 60s là quá ngắn và thử lại
     * quá sớm làm eO2 gia hạn chặn - càng thử càng chặn nặng.
     *
     * Chặn trên 480s để `shifts:warm-cache` không ngủ quá lâu giữa hai lượt.
     */
    private const BREAKER_BASE_WAIT = 60;
    private const BREAKER_MAX_WAIT = 480;

    /** Cache trong RAM cho vòng đời một request, tránh giải nén lại nhiều lần. */
    private array $memo = [];

    /**
     * Số giây cần chờ do máy chủ nguồn trả HTTP 429 ở lần `fetchAll` gần nhất,
     * null nếu không bị chặn. Đặt lại ở đầu mỗi `fetchAll`.
     */
    private ?int $rateLimitedFor = null;

    /**
     * Bảng tra ca trực theo NGÀY LỊCH THẬT cho một khoảng ngày bất kỳ.
     *
     * Trả về null khi KHÔNG tháng nào lấy được dữ liệu (API lỗi và cũng không
     * còn bản sao lưu) để nơi gọi phân biệt được "API hỏng" với "bộ phận rỗng".
     *
     * @return array|null [employeeCode => [
     *     'name' => string, 'group' => string|null,
     *     'is_warehouse' => bool,
     *     'in_roster' => bool,   // có mặt trong endpoint `range` hay không
     *     'days' => ['Y-m-d' => [
     *         'shift' => string|null, 'is_holiday' => bool,
     *         'overtime' => float, 'leave' => float,
     *         'leave_status' => string|null, 'leave_pending' => bool,
     *         'regular_working_Hours' => float,
     *     ]],
     * ]]
     */
    public function shiftIndex($from, $to, $department, bool $mergeWarehouse = false): ?array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();
        if ($to->lt($from)) {
            return [];
        }

        // Dữ liệu được lấy và cache trọn từng tháng lịch để mọi màn hình
        // (sidebar, dashboard, portal) dùng chung một bản cache.
        $specs = [];
        foreach ($this->monthsBetween($from, $to) as [$year, $month]) {
            foreach ($this->monthSpecs($year, $month, (int) $department, $mergeWarehouse) as $spec) {
                $specs[] = $spec;
            }
        }

        // Nạp TẤT CẢ tháng × bộ phận trong một mẻ song song duy nhất.
        $loaded = $this->loadMonthIndexes($specs);

        $index = [];
        $anyLoaded = false;
        foreach ($specs as $spec) {
            $part = $loaded[$this->specKey($spec)] ?? null;
            if ($part === null) {
                continue; // Kho lỗi thì bỏ qua, không kéo theo cả bộ phận chính.
            }
            if (!$spec['wh']) {
                $anyLoaded = true;
            }
            $this->mergeInto($index, $part);
        }

        if (!$anyLoaded) {
            return null;
        }

        // Cắt về đúng khoảng ngày được hỏi.
        $wanted = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $wanted[$d->format('Y-m-d')] = true;
        }
        foreach ($index as $code => $person) {
            $index[$code]['days'] = array_intersect_key($person['days'], $wanted);
        }

        return $index;
    }

    /**
     * Trả dữ liệu theo đúng hình dạng mà giao diện cũ đang dùng:
     * danh sách nhân sự với `days.day1 .. days.dayN`, trong đó `dayN` là NGÀY N
     * CỦA CHÍNH THÁNG được hỏi (không còn trò lệch tháng của API cũ).
     *
     * Trả về null khi API lỗi (xem `shiftIndex`).
     *
     * @return array|null danh sách ['employeeId','employeeName','group','days'=>[...]]
     */
    public function monthlyByDayKey($month, $year, $department, bool $mergeWarehouse = false): ?array
    {
        $month = (int) $month;
        $year = (int) $year;
        if ($month < 1 || $month > 12 || $year < 2000) {
            return [];
        }

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->startOfDay();
        $index = $this->shiftIndex($start, $end, $department, $mergeWarehouse);
        if ($index === null) {
            return null;
        }

        $daysInMonth = (int) $start->daysInMonth;
        $result = [];

        foreach ($index as $code => $person) {
            // Người chỉ xuất hiện ở đơn phép/tăng ca mà không có trong bảng phân
            // ca thì không thuộc danh sách trực của bộ phận -> bỏ khỏi sidebar.
            if (empty($person['in_roster'])) {
                continue;
            }

            $days = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $dayData = $person['days'][$dateKey] ?? null;
                $days['day' . $d] = $dayData === null ? null : [
                    'shift' => $dayData['shift'],
                    'is_holiday' => $dayData['is_holiday'],
                    'overtime' => $dayData['overtime'],
                    'leave_pending' => $dayData['leave_pending'],
                ];
            }

            $result[] = [
                // Ép chuỗi: PHP tự đổi khoá mảng dạng số thành int, mà giao diện
                // gọi thẳng personCode.toLowerCase() nên nhận số sẽ lỗi JS.
                'employeeId' => (string) $code,
                'employeeName' => $person['name'],
                'group' => $person['group'],
                'days' => $days,
            ];
        }

        return $result;
    }

    /**
     * Danh sách nhân sự (chỉ mã + tên) của một hoặc nhiều bộ phận.
     *
     * Dùng cho các luồng CHỈ cần biết "bộ phận này có những ai" — đồng bộ nhân
     * sự lúc đăng nhập, nút Sync ở trang Quản lý nhân sự. Khác `shiftIndex` ở
     * ba điểm quan trọng cho tốc độ:
     *   - chỉ gọi endpoint `range`, bỏ hẳn `leave` + `overtime` (1 request thay vì 3),
     *   - hỏi đúng MỘT ngày thay vì trọn tháng,
     *   - gộp mọi bộ phận vào một mẻ song song duy nhất,
     *   - cache riêng với TTL dài (danh sách nhân sự rất ít đổi).
     *
     * @param  array $departments  danh sách mã bộ phận
     * @param  bool  $fresh  bỏ qua cache 6h, bắt buộc hỏi lại eO2. Dành cho thao
     *         tác do người dùng chủ động yêu cầu (nút Đồng bộ trên Dashboard):
     *         người ta bấm chính vì nghi số liệu cũ, trả lại đúng bản cache đang
     *         nghi ngờ thì nút thành vô dụng. Bản sao lưu vẫn được dùng nếu API
     *         lỗi, nên bật cờ này không làm mất lưới an toàn.
     * @return array [department => [employeeCode => employeeName]]
     *               Bộ phận nào lỗi thì KHÔNG có mặt trong kết quả (khác với
     *               có mặt nhưng rỗng), để nơi gọi không vô hiệu hoá nhầm nhân sự.
     */
    public function roster(array $departments, $date = null, ?int $timeout = null, bool $fresh = false): array
    {
        $day = $date ? Carbon::parse($date)->startOfDay() : Carbon::now()->startOfDay();
        $dayKey = $day->format('Y-m-d');
        $ttl = (int) config('shiftapi.roster_cache_ttl', 21600);

        $result = [];
        $urls = [];
        $backupKeys = [];

        foreach (array_unique(array_map('intval', $departments)) as $dept) {
            $cacheKey = "shiftapi:roster:{$dayKey}:{$dept}";
            $backupKeys[$dept] = "shiftapi:roster_backup:{$dept}";

            $cached = $fresh ? null : $this->cacheGet($cacheKey);
            if (is_array($cached)) {
                $result[$dept] = $cached;
                continue;
            }
            $urls[$dept] = $this->url('range', $day, $day, $dept);
        }

        if (empty($urls)) {
            return $result;
        }

        $responses = $this->fetchAll($urls, $timeout);

        foreach ($urls as $dept => $url) {
            $payload = $responses[$dept] ?? null;

            if (!is_array($payload)) {
                // API lỗi: dùng bản sao lưu nếu có, còn không thì BỎ HẲN bộ phận
                // này khỏi kết quả để nơi gọi biết là "không lấy được".
                $backup = $this->cacheGet($backupKeys[$dept]);
                if (is_array($backup)) {
                    $result[$dept] = $backup;
                }
                continue;
            }

            $roster = [];
            foreach ($payload as $person) {
                $code = $this->codeOf($person);
                if ($code) {
                    $roster[$code] = trim((string) ($person['employeeName'] ?? ''));
                }
            }

            $this->cachePut("shiftapi:roster:{$dayKey}:{$dept}", $roster, $ttl);
            $this->cachePut($backupKeys[$dept], $roster, (int) config('shiftapi.backup_ttl', 86400));
            $result[$dept] = $roster;
        }

        return $result;
    }

    /**
     * Danh sách nhân sự lấy từ CACHE, tuyệt đối không gọi HTTP.
     *
     * Dành cho những luồng không được phép chờ — cụ thể là đăng nhập. Máy chủ
     * nguồn mất ~9.5s đến ~88s cho mỗi request tuỳ bộ phận, nên bất kỳ lời gọi
     * API nào trong luồng đăng nhập cũng là không chấp nhận được.
     *
     * Chấp nhận cả bản sao lưu (24h) vì danh sách nhân sự thay đổi rất chậm.
     *
     * @return array [department => [employeeCode => employeeName]]
     */
    public function cachedRoster(array $departments, $date = null): array
    {
        $dayKey = ($date ? Carbon::parse($date) : Carbon::now())->format('Y-m-d');

        $result = [];
        foreach (array_unique(array_map('intval', $departments)) as $dept) {
            $roster = $this->cacheGet("shiftapi:roster:{$dayKey}:{$dept}");
            if (!is_array($roster)) {
                $roster = $this->cacheGet("shiftapi:roster_backup:{$dept}");
            }
            if (is_array($roster)) {
                $result[$dept] = $roster;
            }
        }

        return $result;
    }

    /**
     * Đơn nghỉ phép có được tính là nghỉ hay không.
     *
     * Đã duyệt (Approved) và mọi trạng thái chờ duyệt đều tính; Rejected và
     * Cancelled thì không. Chuỗi chờ duyệt do hệ thống nguồn trả về không cố
     * định ("Waiting TLE Approval/Chờ tổ trưởng duyệt", "Waiting DH Approval/...",
     * "Waiting BOD Approval/...") nên khớp theo từ khoá thay vì so bằng.
     */
    public function isCountedLeaveStatus(?string $status): bool
    {
        $status = trim((string) $status);
        if ($status === '') {
            return false;
        }

        if (strcasecmp($status, (string) config('shiftapi.leave_approved_status', 'approved')) === 0) {
            return true;
        }

        foreach ((array) config('shiftapi.leave_pending_tokens', ['waiting', 'approval']) as $token) {
            if (stripos($status, $token) === false) {
                return false;
            }
        }

        return true;
    }

    /** Đơn nghỉ đang chờ duyệt (được tính là nghỉ nhưng chưa được duyệt). */
    public function isPendingLeaveStatus(?string $status): bool
    {
        return $this->isCountedLeaveStatus($status)
            && strcasecmp(trim((string) $status), (string) config('shiftapi.leave_approved_status', 'approved')) !== 0;
    }

    // ---------------------------------------------------------------------
    // Nội bộ
    // ---------------------------------------------------------------------

    /** @return array<int, array{0:int,1:int}> danh sách [year, month] mà khoảng ngày chạm tới */
    private function monthsBetween(Carbon $from, Carbon $to): array
    {
        $months = [];
        $cursor = $from->copy()->startOfMonth();
        $last = $to->copy()->startOfMonth();
        while ($cursor->lte($last)) {
            $months[] = [(int) $cursor->year, (int) $cursor->month];
            $cursor->addMonth();
        }
        return $months;
    }

    /** Gộp bảng tra của một bộ phận vào bảng tra tổng (ngày của người đã có thì cộng dồn). */
    private function mergeInto(array &$index, array $part): void
    {
        foreach ($part as $code => $person) {
            if (!isset($index[$code])) {
                $index[$code] = $person;
                continue;
            }
            // Bộ phận chính thắng nếu cùng một mã nhân sự xuất hiện ở cả hai.
            $index[$code]['days'] = $index[$code]['days'] + $person['days'];
            $index[$code]['in_roster'] = $index[$code]['in_roster'] || $person['in_roster'];
        }
    }

    /** Khoá định danh một "ô" dữ liệu: một tháng lịch của một bộ phận. */
    private function specKey(array $spec): string
    {
        return "{$spec['year']}-{$spec['month']}-{$spec['dept']}-" . (int) $spec['wh'];
    }

    /**
     * Các "ô" dữ liệu cần có để dựng một tháng của một bộ phận.
     *
     * Tách riêng để phía ĐỌC (`shiftIndex`) và phía XOÁ (`forgetMonth`) luôn
     * nhìn cùng một tập khoá. Nếu hai bên tự dựng spec riêng rồi lệch nhau thì
     * nút Đồng bộ sẽ xoá hụt phần Kho và người dùng bấm xong vẫn thấy số cũ.
     */
    private function monthSpecs(int $year, int $month, int $department, bool $mergeWarehouse): array
    {
        $warehouseId = (int) config('shiftapi.warehouse_department', 17);

        $specs = [['year' => $year, 'month' => $month, 'dept' => $department, 'wh' => false]];

        // Nếu chính nó đã là bộ phận Kho thì không gộp thêm lần nữa.
        if ($mergeWarehouse && $department !== $warehouseId) {
            $specs[] = ['year' => $year, 'month' => $month, 'dept' => $warehouseId, 'wh' => true];
        }

        return $specs;
    }

    /**
     * Bỏ cache nóng của một tháng để lần gọi sau bắt buộc hỏi lại eO2.
     *
     * Dùng cho nút Đồng bộ ở sidebar Lịch công tác và cho command
     * `shifts:warm-cache`. Gọi xong phải gọi tiếp `monthlyByDayKey`/`shiftIndex`
     * thì mới thực sự nạp lại.
     *
     * CỐ Ý không đụng tới bản sao lưu 24h: nếu lần gọi lại này dính 429 hoặc
     * timeout thì `loadMonthIndexes` vẫn còn bản đủ để rơi về, thay vì để người
     * dùng bấm Đồng bộ xong lại nhận trang trống.
     */
    public function forgetMonth(int $month, int $year, int $department, bool $mergeWarehouse = false): void
    {
        foreach ($this->monthSpecs($year, $month, $department, $mergeWarehouse) as $spec) {
            unset($this->memo[$this->specKey($spec)]);
            try {
                Cache::forget($this->cacheKeyFor($spec, false));
            } catch (\Throwable $e) {
                Log::warning('Khong xoa duoc cache lich truc: ' . $e->getMessage(), [
                    'key' => $this->cacheKeyFor($spec, false),
                ]);
            }
        }
    }

    /**
     * Xoá cache nóng để nạp lại, NHƯNG chỉ khi tháng đó đang có đủ dữ liệu.
     *
     * Đây là điểm mà nút Đồng bộ và `shifts:warm-cache` phải giống nhau, nên
     * quy tắc nằm ở đây thay vì viết lại ở hai nơi rồi lệch nhau.
     *
     * Vì sao không xoá khi đang khuyết: một tháng của PXV1 gồm 2 "ô" (bộ phận
     * 15 và Kho 17) × 3 endpoint, mà eO2 chỉ cho qua khoảng 5 request một mẻ -
     * đo 18/09/2026, cả lượt `max_concurrency` 3 và lượt hạ xuống 2 đều chỉ chết
     * ĐÚNG MỘT endpoint trong 6. Ô nào đủ 3 endpoint thì đã được ghi cache nóng.
     * Nếu lượt sau lại xoá sạch thì chính là ném đúng phần vừa lấy được rồi hỏi
     * lại cả 6 - mẻ nào cũng đủ lớn để lại bị chặn, nên bấm mãi không bao giờ
     * xong. Giữ phần đã có thì lượt sau chỉ còn hỏi 3 endpoint của ô còn thiếu,
     * đủ nhỏ để đi qua.
     *
     * Đánh đổi: nếu tháng đang khuyết thì ô đã có sẽ không được làm mới ở lượt
     * này. Nó tự sửa ở lượt kế tiếp - lúc đó tháng đã đủ nên lại được xoá sạch
     * và nạp mới toàn bộ.
     */
    public function forgetMonthIfComplete(int $month, int $year, int $department, bool $mergeWarehouse = false): void
    {
        if ($this->hasFreshMonth($month, $year, $department, $mergeWarehouse)) {
            $this->forgetMonth($month, $year, $department, $mergeWarehouse);
            return;
        }

        // Không xoá cache, nhưng vẫn phải quên bản nhớ trong RAM để lượt này
        // thực sự hỏi lại eO2 về ô còn thiếu.
        $this->forgetMemo($month, $year, $department, $mergeWarehouse);
    }

    /**
     * Quên bản nhớ trong RAM của một tháng, GIỮ NGUYÊN cache nóng.
     *
     * Dành cho lượt thử lại của `shifts:warm-cache`. `loadMonthIndexes` nhớ cả
     * kết quả LỖI trong `$memo` để không gọi lại API đã hỏng nhiều lần trong
     * cùng một request, nên nếu không xoá bản nhớ đó thì lượt thử lại không gọi
     * gì cả. Khác `forgetMonth` ở chỗ không đụng cache nóng: ô nào đã nạp xong ở
     * lượt trước vẫn được dùng lại, lượt này chỉ hỏi eO2 về ô còn thiếu.
     */
    public function forgetMemo(int $month, int $year, int $department, bool $mergeWarehouse = false): void
    {
        foreach ($this->monthSpecs($year, $month, $department, $mergeWarehouse) as $spec) {
            unset($this->memo[$this->specKey($spec)]);
        }
    }

    /**
     * Cache nóng của tháng này đã có ĐỦ chưa?
     *
     * `monthlyByDayKey` trả về mảng cả khi phải rơi về bản sao lưu 24h, nên chỉ
     * nhìn giá trị trả về thì không phân biệt được "vừa nạp mới" với "dùng lại
     * số cũ". Command nạp nền cần phân biệt để báo cáo trung thực và để biết có
     * phải thử lại hay không.
     */
    public function hasFreshMonth(int $month, int $year, int $department, bool $mergeWarehouse = false): bool
    {
        foreach ($this->monthSpecs($year, $month, $department, $mergeWarehouse) as $spec) {
            if (!is_array($this->cacheGet($this->cacheKeyFor($spec, false)))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Số giây cần chờ trước khi gọi lại eO2, null nếu lần gọi vừa rồi không bị
     * chặn. Gộp hai nguồn: máy chủ nguồn trả 429, hoặc hạn ngạch nội bộ đã hết.
     * Chỉ có ý nghĩa ngay sau một lượt nạp.
     */
    public function rateLimitedFor(): ?int
    {
        return $this->rateLimitedFor;
    }

    /**
     * Giữ chỗ trong hạn ngạch dùng chung trước khi gọi eO2.
     *
     * Bộ đếm vẫn chia theo ô thời gian cố định (mặc định 5 phút, khớp cửa sổ
     * tính hạn mức của eO2) nhưng được ĐỌC theo cửa sổ TRƯỢT: lưu lượng tính
     * được là toàn bộ ô hiện tại cộng phần ô trước còn nằm trong 5 phút vừa qua.
     *
     * Đọc theo ô cố định là một lỗ hổng thật, không phải lo xa: 18 request lúc
     * 10:04:59 rồi 18 request nữa lúc 10:05:00 đều "hợp lệ" vì sang ô mới bộ đếm
     * về 0 — thành 36 request trong 2 giây, vượt xa mức ~24 request/5 phút mà
     * eO2 chịu được (đo 18/08/2026). Đó chính là kiểu burst dẫn tới loạt 429 lúc
     * 10:22:21 ngày 18/09/2026.
     *
     * Khoá nằm trong bảng `cache` nên mọi tiến trình web và command đều cộng vào
     * cùng một con số — đây là điểm mà `max_concurrency` không làm được vì nó
     * chỉ có tác dụng nội bộ một tiến trình.
     *
     * `Cache::increment` trên database store chạy trong transaction kèm
     * `lockForUpdate()` nên hai tiến trình không thể cùng giữ một chỗ.
     *
     * @return bool false = hết hạn ngạch, KHÔNG được gọi API
     */
    private function reserveQuota(int $need): bool
    {
        $limit = (int) config('shiftapi.rate_limit', 18);
        if ($limit <= 0 || $need <= 0) {
            return true; // 0 = tắt cơ chế
        }

        $window = max(1, (int) config('shiftapi.rate_window', 300));
        $now = time();
        $slot = intdiv($now, $window);
        $key = "shiftapi:quota:{$slot}";

        try {
            // TTL phải phủ TRỌN ô kế tiếp, vì suốt ô đó bộ đếm này còn được đọc
            // với vai trò "ô trước". Đặt $window + 60 là không đủ: với cửa sổ
            // 300s, bộ đếm chết ở giây thứ 60 của ô sau, `carriedOver` đọc ra 0
            // và cửa sổ trượt âm thầm tụt về đúng cách đếm theo ô cố định vừa bỏ.
            Cache::add($key, 0, 2 * $window + 60);
            $used = Cache::increment($key, $need);
            $prevUsed = max(0, (int) Cache::get('shiftapi:quota:' . ($slot - 1), 0));
        } catch (\Throwable $e) {
            // Cache hỏng thì không được vì thế mà chặn luôn tính năng.
            Log::warning('Khong dat duoc han ngach Shift API: ' . $e->getMessage());
            return true;
        }

        if ($used === false) {
            return true;
        }

        $used = (int) $used;
        $effective = $used + $this->carriedOver($prevUsed, $window, $now);

        if ($effective <= $limit) {
            return true;
        }

        // Trả lại phần vừa giữ: lượt này không gọi nên không được tính. Nhờ vậy
        // một mẻ nhỏ vẫn có thể lọt qua sau khi mẻ lớn bị từ chối.
        try {
            Cache::decrement($key, $need);
        } catch (\Throwable $e) {
            // Đếm dư một chút thì chỉ thận trọng hơn, không sao.
        }

        $resetIn = $this->quotaFreeIn(max(0, $used - $need), $prevUsed, $need, $limit, $window, $now);
        $this->rateLimitedFor = $resetIn;

        Log::warning('Tam dung goi Shift API do het han ngach noi bo', [
            'can' => $need,
            'dang_dung' => $effective,
            'han_ngach' => $limit,
            'cua_so' => $window . 's (truot)',
            'cho_lai' => $resetIn . 's',
        ]);

        return false;
    }

    /** Phần lưu lượng của ô trước còn nằm trong cửa sổ trượt tại thời điểm $at. */
    private function carriedOver(int $prevUsed, int $window, int $at): int
    {
        $elapsed = $at - intdiv($at, $window) * $window;

        return (int) round($prevUsed * (($window - $elapsed) / $window));
    }

    /**
     * Sau bao nhiêu giây thì cửa sổ trượt còn đủ chỗ cho $need request.
     *
     * Dò từng giây thay vì giải công thức vì lưu lượng tính được KHÔNG giảm đơn
     * điệu theo thời gian: ngay lúc sang ô mới, ô hiện tại trở thành "ô trước"
     * và được tính TRỌN VẸN, nên có thời điểm chờ thêm lại tệ hơn. Chặn trên hai
     * cửa sổ là lúc cả hai bộ đếm chắc chắn đã rời đi.
     */
    private function quotaFreeIn(int $curUsed, int $prevUsed, int $need, int $limit, int $window, int $now): int
    {
        $slot = intdiv($now, $window);

        for ($wait = 1; $wait <= 2 * $window; $wait++) {
            $at = $now + $wait;
            $slotsAhead = intdiv($at, $window) - $slot;

            if ($slotsAhead >= 2) {
                return $wait;
            }

            [$cur, $prev] = $slotsAhead === 0 ? [$curUsed, $prevUsed] : [0, $curUsed];

            if ($cur + $this->carriedOver($prev, $window, $at) + $need <= $limit) {
                return $wait;
            }
        }

        return 2 * $window;
    }

    /**
     * Nạp bảng tra của NHIỀU tháng × NHIỀU bộ phận trong MỘT mẻ song song.
     *
     * Đây là điểm mấu chốt về tốc độ: nếu gọi lần lượt từng tháng thì thời gian
     * cộng dồn (PXV1 mất ~60s cho mỗi endpoint `range`). Gom tất cả URL còn
     * thiếu vào một `curl_multi` nên tổng thời gian ≈ request chậm nhất, bất kể
     * đang hỏi 1 tháng hay 2 tháng, có gộp Kho hay không.
     *
     * Cache ghi theo nguyên tắc tất-cả-hoặc-không: chỉ tháng nào lấy đủ cả 3
     * endpoint mới được ghi, xem giải thích ở vòng lặp ghép kết quả bên dưới.
     *
     * @param array $specs list of ['year'=>int,'month'=>int,'dept'=>int,'wh'=>bool]
     * @return array [specKey => array|null]  (null = API lỗi và hết bản sao lưu)
     */
    private function loadMonthIndexes(array $specs): array
    {
        $result = [];
        $pending = [];  // specKey => spec  (phải gọi API)
        $urls = [];     // "specKey\0endpoint" => url

        foreach ($specs as $spec) {
            $key = $this->specKey($spec);
            if (array_key_exists($key, $result) || isset($pending[$key])) {
                continue; // đã xử lý spec trùng
            }

            // array_key_exists: nhớ cả kết quả null để không gọi lại API đã lỗi
            // nhiều lần trong cùng một request.
            if (array_key_exists($key, $this->memo)) {
                $result[$key] = $this->memo[$key];
                continue;
            }

            $cached = $this->cacheGet($this->cacheKeyFor($spec, false));
            if (is_array($cached)) {
                $result[$key] = $this->memo[$key] = $cached;
                continue;
            }

            $pending[$key] = $spec;
            $start = Carbon::create($spec['year'], $spec['month'], 1)->startOfDay();
            $end = $start->copy()->endOfMonth();
            foreach (['range', 'leave', 'overtime'] as $endpoint) {
                $urls[$key . "\0" . $endpoint] = $this->url($endpoint, $start, $end, $spec['dept']);
            }
        }

        if (empty($pending)) {
            return $result;
        }

        $responses = $this->fetchAll($urls);

        foreach ($pending as $key => $spec) {
            $range = $responses[$key . "\0range"] ?? null;
            $leave = $responses[$key . "\0leave"] ?? null;
            $overtime = $responses[$key . "\0overtime"] ?? null;

            // Chỉ bản dựng từ ĐỦ 3 endpoint mới được ghi cache.
            //
            // Thiếu `range` thì bản dựng vô nghĩa. Nhưng thiếu `leave` hoặc
            // `overtime` cũng nguy hiểm không kém mà lại lặng lẽ: bản dựng vẫn
            // ra bảng đầy đủ người, chỉ có ngày nghỉ / giờ tăng ca hụt về 0.
            // Nếu ghi cache bản khuyết đó thì một cú HTTP 429 lẻ tẻ sẽ đè lên
            // số liệu đủ của lần gọi trước và giữ nguyên suốt `backup_ttl`
            // (24h) mà không ai biết. Nên coi thiếu bất kỳ endpoint nào là
            // thất bại và ưu tiên bản sao lưu cũ - dữ liệu cũ nhưng ĐỦ vẫn
            // đúng hơn dữ liệu mới nhưng KHUYẾT.
            if (!is_array($range) || !is_array($leave) || !is_array($overtime)) {
                $backup = $this->cacheGet($this->cacheKeyFor($spec, true));
                if (is_array($backup)) {
                    $result[$key] = $this->memo[$key] = $backup;
                    continue;
                }

                // Hết bản sao lưu. Còn `range` thì vẫn dựng bản khuyết để giao
                // diện không trống, nhưng KHÔNG cache: lần gọi sau phải thử lại.
                $result[$key] = $this->memo[$key] = is_array($range)
                    ? $this->buildIndex(
                        $range,
                        is_array($leave) ? $leave : [],
                        is_array($overtime) ? $overtime : [],
                        (bool) $spec['wh']
                    )
                    : null;

                Log::warning('Shift API thieu endpoint, khong ghi cache thang nay', [
                    'spec' => $key,
                    'missing' => array_keys(array_filter([
                        'range' => !is_array($range),
                        'leave' => !is_array($leave),
                        'overtime' => !is_array($overtime),
                    ])),
                    'fallback' => is_array($range) ? 'ban khuyet (khong cache)' : 'khong co du lieu',
                ]);
                continue;
            }

            $index = $this->buildIndex($range, $leave, $overtime, (bool) $spec['wh']);

            $this->cachePut($this->cacheKeyFor($spec, false), $index, (int) config('shiftapi.cache_ttl', 120));
            $this->cachePut($this->cacheKeyFor($spec, true), $index, (int) config('shiftapi.backup_ttl', 86400));

            $result[$key] = $this->memo[$key] = $index;
        }

        return $result;
    }

    private function cacheKeyFor(array $spec, bool $backup): string
    {
        $prefix = $backup ? 'shiftapi:month_backup' : 'shiftapi:month';
        return "{$prefix}:{$spec['year']}:{$spec['month']}:{$spec['dept']}:" . (int) $spec['wh'];
    }

    /** Hợp nhất 3 payload thành bảng tra theo ngày lịch thật. */
    private function buildIndex(array $range, array $leave, array $overtime, bool $isWarehouse): array
    {
        $index = [];

        foreach ($range as $person) {
            $code = $this->codeOf($person);
            if (!$code) {
                continue;
            }

            $name = trim((string) ($person['employeeName'] ?? ''));
            $index[$code] = [
                'name' => $isWarehouse && $name !== '' ? $name . ' - WH' : $name,
                'group' => $person['group'] ?? null,
                'is_warehouse' => $isWarehouse,
                'in_roster' => true,
                'days' => [],
            ];

            // `days` của endpoint range là object khoá "DD/MM/YYYY".
            foreach ((array) ($person['days'] ?? []) as $rawDay => $dayData) {
                $dateKey = $this->toDateKey($rawDay);
                if (!$dateKey) {
                    continue;
                }
                $shift = is_array($dayData) ? ($dayData['shift'] ?? null) : $dayData;
                $shift = ($shift === null || trim((string) $shift) === '') ? null : strtoupper(trim((string) $shift));

                $index[$code]['days'][$dateKey] = [
                    'shift' => $shift,
                    'is_holiday' => (bool) (is_array($dayData) ? ($dayData['is_holiday'] ?? false) : false),
                    'overtime' => 0.0,
                    'leave' => 0.0,
                    'leave_status' => null,
                    'leave_pending' => false,
                    'regular_working_Hours' => 0.0, // tính lại ở cuối buildIndex
                ];
            }
        }

        // `days` của endpoint leave/overtime là MẢNG các phần tử {day, ..., status}.
        foreach ($leave as $person) {
            $code = $this->codeOf($person);
            if (!$code) {
                continue;
            }
            foreach ((array) ($person['days'] ?? []) as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $dateKey = $this->toDateKey($entry['day'] ?? null);
                if (!$dateKey) {
                    continue;
                }
                $hours = (float) ($entry['leave'] ?? 0);
                $status = $entry['status'] ?? null;
                if ($hours <= 0 || !$this->isCountedLeaveStatus($status)) {
                    continue;
                }

                $this->touchDay($index, $code, $dateKey, $person, $isWarehouse);
                $index[$code]['days'][$dateKey]['leave'] = $hours;
                $index[$code]['days'][$dateKey]['leave_status'] = $status;
                $index[$code]['days'][$dateKey]['leave_pending'] = $this->isPendingLeaveStatus($status);
                // Nghỉ phép đè lên mã ca: phép đã duyệt thì `range` đã trả 'P'
                // sẵn, phép chờ duyệt thì vẫn còn mã ca gốc nên phải ghi đè.
                $index[$code]['days'][$dateKey]['shift'] = 'P';
            }
        }

        // `range` đánh 'P' cho cả những ngày mà `leave` KHÔNG có đơn nào.
        //
        // Đo thực tế 19/08/2026, PXDN (34) tháng 8: 19 ngày mang mã 'P', trong đó
        // 9 ngày khớp đơn nghỉ - toàn bộ là ngày ĐÃ QUA; 10 ngày còn lại không có
        // đơn nào - toàn bộ là ngày TƯƠNG LAI. Không một ngoại lệ. Tức endpoint
        // `leave` chỉ trả đơn của ngày đã qua, còn `range` đánh 'P' ngay từ lúc
        // đăng ký. Hai nguồn của eO2 mâu thuẫn nhau ở phần ngày tương lai.
        //
        // Quyết định nghiệp vụ: tình trạng nghỉ phép CHỈ lấy theo endpoint
        // `leave`. Mã 'P' không có đơn tương ứng (kể cả đơn Rejected/Cancelled
        // vì những đơn đó không được ghi vào `leave` ở vòng lặp trên) được coi là
        // ngày làm việc bình thường và quy về 'HC'.
        //
        // Phải chạy TRƯỚC vòng tính `regular_working_Hours` bên dưới, nhờ vậy
        // nhánh 'P' ở đó chỉ còn gặp ngày nghỉ đã được `leave` xác nhận.
        foreach ($index as $code => $person) {
            foreach ($person['days'] as $dateKey => $day) {
                if ($day['shift'] === 'P' && (float) $day['leave'] <= 0) {
                    $index[$code]['days'][$dateKey]['shift'] = 'HC';
                }
            }
        }

        foreach ($overtime as $person) {
            $code = $this->codeOf($person);
            if (!$code) {
                continue;
            }
            foreach ((array) ($person['days'] ?? []) as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $dateKey = $this->toDateKey($entry['day'] ?? null);
                if (!$dateKey) {
                    continue;
                }
                // Theo yêu cầu nghiệp vụ: tăng ca KHÔNG xét status.
                $hours = (float) ($entry['overtime'] ?? 0);
                if ($hours <= 0) {
                    continue;
                }

                $this->touchDay($index, $code, $dateKey, $person, $isWarehouse);
                $index[$code]['days'][$dateKey]['overtime'] += $hours;
            }
        }

        // Giờ làm việc chuẩn: bộ 3 endpoint mới không trả `regular_working_Hours`
        // nên suy ra theo quy ước — mặc định 8h, ngày nghỉ phép thì trừ đi số giờ
        // nghỉ. Phải tính SAU khi đã phủ nghỉ phép lên mã ca ở trên.
        $standard = (float) config('shiftapi.standard_working_hours', 8);
        foreach ($index as $code => $person) {
            foreach ($person['days'] as $dateKey => $day) {
                if ($day['shift'] === null) {
                    // Không có ca trong bảng phân ca -> không tính giờ công.
                    $regular = 0.0;
                } elseif ($day['shift'] === 'P') {
                    // Nghỉ phép: trừ số giờ nghỉ (hiện dữ liệu nguồn chỉ có nghỉ
                    // trọn ngày 8h -> ra 0, nhưng vẫn đúng nếu sau này có nghỉ nửa ngày).
                    $regular = max(0.0, $standard - (float) $day['leave']);
                } else {
                    $regular = $standard;
                }
                $index[$code]['days'][$dateKey]['regular_working_Hours'] = $regular;
            }
        }

        return $index;
    }

    /** Đảm bảo tồn tại ô [code][days][date] trước khi ghi dữ liệu phép/tăng ca vào. */
    private function touchDay(array &$index, string $code, string $dateKey, array $person, bool $isWarehouse): void
    {
        if (!isset($index[$code])) {
            $name = trim((string) ($person['employeeName'] ?? ''));
            $index[$code] = [
                'name' => $isWarehouse && $name !== '' ? $name . ' - WH' : $name,
                'group' => $person['group'] ?? null,
                'is_warehouse' => $isWarehouse,
                'in_roster' => false,
                'days' => [],
            ];
        }

        if (!isset($index[$code]['days'][$dateKey])) {
            $index[$code]['days'][$dateKey] = [
                'shift' => null,
                'is_holiday' => false,
                'overtime' => 0.0,
                'leave' => 0.0,
                'leave_status' => null,
                'leave_pending' => false,
                'regular_working_Hours' => 0.0,
            ];
        }
    }

    private function codeOf(array $person): ?string
    {
        $code = $person['employeeId'] ?? $person['code'] ?? null;
        $code = trim((string) $code);
        return $code === '' ? null : $code;
    }

    /** "DD/MM/YYYY" (định dạng API) -> "Y-m-d". Trả null nếu không nhận dạng được. */
    private function toDateKey($raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $raw, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        if (preg_match('#^\d{4}-\d{2}-\d{2}#', $raw)) {
            return substr($raw, 0, 10);
        }
        return null;
    }

    private function url(string $endpoint, Carbon $from, Carbon $to, int $department): string
    {
        return rtrim((string) config('shiftapi.base_url'), '/') . '/' . $endpoint . '?' . http_build_query([
            'fromdate' => $from->format('Y-m-d'),
            'todate' => $to->format('Y-m-d'),
            'department' => $department,
        ]);
    }

    /**
     * Gọi nhiều endpoint: chia thành các mẻ nhỏ, mỗi mẻ chạy song song.
     *
     * Trong một mẻ thì song song, vì gọi tuần tự từng cái thì tổng thời gian của
     * PXV1 (~60s + ~28s + ...) vượt xa giới hạn chấp nhận được. Nhưng KHÔNG dồn
     * tất cả vào một mẻ: eO2 chặn theo số request của mẻ (xem `$chunkSize`).
     *
     * @param array<string,string> $urls [key => url]
     * @param int|null $timeoutOverride timeout riêng (giây) cho luồng không được
     *        phép chờ lâu, ví dụ đồng bộ nhân sự lúc đăng nhập.
     * @return array<string,array|null> [key => payload đã decode, hoặc null nếu lỗi]
     */
    private function fetchAll(array $urls, ?int $timeoutOverride = null): array
    {
        $result = array_fill_keys(array_keys($urls), null);
        if (empty($urls)) {
            return $result;
        }

        // Đặt lại ở đây, trước cổng hạn ngạch, để giá trị của lượt gọi trước
        // không lẫn sang lượt này.
        $this->rateLimitedFor = null;

        // Cầu dao: eO2 vừa trả 429 thì phải im lặng cho tới hết thời gian nó
        // yêu cầu. Kiểm tra TRƯỚC cổng hạn ngạch để lượt bị chặn không tiêu chỗ
        // của hạn ngạch - chỗ đó dành cho lúc eO2 mở lại thì hữu ích hơn.
        $blockedFor = $this->breakerWait();
        if ($blockedFor !== null) {
            $this->rateLimitedFor = $blockedFor;

            Log::warning('Bo qua goi Shift API vi eO2 dang chan', [
                'so_request' => count($urls),
                'cho_lai' => $blockedFor . 's',
            ]);

            return $result;
        }

        // Cổng hạn ngạch dùng chung: chặn TRƯỚC khi mở kết nối. Đợi eO2 trả 429
        // rồi mới biết thì đã muộn - hạn mức đã bị tiêu và cả hệ thống bị chặn
        // tới hết cửa sổ. Tự dừng sớm thì luồng web rơi về bản sao lưu ngay lập
        // tức (0s) thay vì chờ rồi vẫn hỏng.
        if (!$this->reserveQuota(count($urls))) {
            return $result;
        }

        $timeout = $timeoutOverride ?: (int) config('shiftapi.timeout', 90);

        // Cắt thành nhiều mẻ nhỏ chạy lần lượt, có nghỉ giữa các mẻ.
        //
        // KHÔNG phải để tránh 429 - đã thử, không tránh được (xem `max_batch`
        // trong config). Mục đích là đảm bảo TIẾN TRIỂN: thứ tự URL của
        // `loadMonthIndexes` là từng "ô" dữ liệu một, mỗi ô 3 endpoint, nên một
        // mẻ 3 là trọn một ô và ghi được cache. Gộp cả 6 thì một cú 429 có thể
        // làm khuyết mỗi ô một endpoint và không ô nào được ghi - mất trắng.
        $chunkSize = (int) config('shiftapi.max_batch', 3);
        $chunkSize = $chunkSize > 0 ? $chunkSize : count($urls); // 0 = tắt cắt mẻ
        $chunkPause = max(0, (int) config('shiftapi.batch_pause', 10));

        $anyOk = false;
        $explicitWait = 0;  // số giây eO2 nói rõ qua header `Retry-After`
        $blindBlocks = 0;   // số lượt 429 mà eO2 không nói phải chờ bao lâu

        foreach (array_chunk($urls, $chunkSize, true) as $i => $chunk) {
            if ($i > 0 && $chunkPause > 0) {
                sleep($chunkPause);
            }

            $batch = $this->fetchBatch($chunk, $timeout);

            foreach ($batch['results'] as $key => $payload) {
                $result[$key] = $payload;
            }
            $anyOk = $anyOk || $batch['anyOk'];
            $explicitWait = max($explicitWait, $batch['explicitWait']);
            $blindBlocks += $batch['blindBlocks'];

            // Đã bị chặn thì dừng, không gửi những mẻ còn lại: chúng chắc chắn
            // cũng bị từ chối và chỉ làm eO2 gia hạn chặn.
            if ($batch['explicitWait'] > 0 || $batch['blindBlocks'] > 0) {
                break;
            }
        }

        // Ngắt cầu dao SAU khi đã đọc hết phản hồi: trong cùng một mẻ có thể vài
        // request kịp trả 200, số đó vẫn phải được dùng.
        if ($explicitWait > 0 || $blindBlocks > 0) {
            $this->rateLimitedFor = $this->tripBreaker($explicitWait);
        } elseif ($anyOk) {
            // Có request đi qua được = eO2 đã mở lại, xoá chuỗi bị chặn để lần
            // sau bắt đầu lại từ mức chờ thấp nhất. Chỉ tính khi thật sự có 200:
            // một mẻ toàn timeout không chứng minh được điều gì.
            $this->resetBreakerStreak();
        }

        return $result;
    }

    /**
     * Gọi song song MỘT mẻ endpoint bằng curl_multi.
     *
     * @param array<string,string> $urls [key => url]
     * @return array{results: array<string,array|null>, anyOk: bool, explicitWait: int, blindBlocks: int}
     */
    private function fetchBatch(array $urls, int $timeout): array
    {
        $result = array_fill_keys(array_keys($urls), null);
        $verify = (bool) config('shiftapi.verify_tls', false);

        $multi = curl_multi_init();
        // Xếp hàng hết mọi handle nhưng giới hạn số kết nối thực sự mở cùng lúc,
        // tránh dội quá nhiều request đồng thời vào máy chủ nguồn.
        $maxConn = (int) config('shiftapi.max_concurrency', 2);
        if ($maxConn > 0) {
            curl_multi_setopt($multi, CURLMOPT_MAX_TOTAL_CONNECTIONS, $maxConn);
        }
        $handles = [];
        $retryAfter = [];   // key => số giây máy chủ nguồn yêu cầu chờ (header Retry-After)

        foreach ($urls as $key => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
                CURLOPT_ENCODING => '',
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                // Chỉ quan tâm `Retry-After` của phản hồi 429: nó cho biết CHÍNH
                // XÁC phải chờ bao lâu, thay vì để command nạp nền ngồi đoán.
                CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$retryAfter, $key) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2 && strtolower(trim($parts[0])) === 'retry-after') {
                        $retryAfter[$key] = trim($parts[1]);
                    }
                    return strlen($line);
                },
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$key] = $ch;
        }

        do {
            $status = curl_multi_exec($multi, $running);
            if ($running) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running && $status === CURLM_OK);

        $anyOk = false;
        $explicitWait = 0;  // số giây eO2 nói rõ qua header `Retry-After`
        $blindBlocks = 0;   // số lượt 429 mà eO2 không nói phải chờ bao lâu

        foreach ($handles as $key => $ch) {
            $body = curl_multi_getcontent($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);

            if ($httpCode === 200 && is_string($body) && $body !== '') {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $result[$key] = $decoded;
                    $anyOk = true;
                } else {
                    Log::warning('Shift API tra ve JSON khong hop le', ['url' => $urls[$key]]);
                }
            } elseif ($httpCode === 429) {
                // Rate limit của máy chủ nguồn. Luồng web rơi về bản sao lưu 24h
                // nên giao diện vẫn chạy, nhưng số liệu là dữ liệu cũ.
                //
                // Dấu hiệu nhận biết: 429 trả về TỨC THÌ (0s) vì server từ chối
                // mà không xử lý. Thấy một loạt lượt nạp mất 0s là đã bị chặn,
                // không phải mạng chậm.
                //
                // Thời gian chờ tính SAU vòng lặp: cả mẻ chỉ ngắt cầu dao một
                // lần, và mức chờ còn phụ thuộc số lần bị chặn liên tiếp.
                if (isset($retryAfter[$key]) && is_numeric($retryAfter[$key])) {
                    $explicitWait = max($explicitWait, max(1, (int) $retryAfter[$key]));
                } else {
                    $blindBlocks++;
                }

                Log::warning('Shift API bi chan do rate limit (429)', [
                    'url' => $urls[$key],
                    'max_concurrency' => $maxConn,
                    'retry_after' => $retryAfter[$key] ?? '(khong co header)',
                ]);
            } else {
                Log::warning('Shift API loi', [
                    'url' => $urls[$key],
                    'http_code' => $httpCode,
                    'error' => $err,
                ]);
            }

            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);

        return [
            'results' => $result,
            'anyOk' => $anyOk,
            'explicitWait' => $explicitWait,
            'blindBlocks' => $blindBlocks,
        ];
    }

    /**
     * Số giây còn lại của lệnh tạm dừng toàn hệ thống, null nếu đang được phép
     * gọi eO2.
     */
    private function breakerWait(): ?int
    {
        try {
            $until = Cache::get(self::BREAKER_KEY);
        } catch (\Throwable $e) {
            // Cache hỏng thì không được vì thế mà chặn luôn tính năng.
            return null;
        }

        if (!is_numeric($until)) {
            return null;
        }

        $remaining = (int) $until - time();

        return $remaining > 0 ? $remaining : null;
    }

    /**
     * Ghi lệnh tạm dừng cho MỌI tiến trình sau khi eO2 trả 429.
     *
     * Trước khi có cầu dao này, `$rateLimitedFor` chết theo request nên lượt gọi
     * kế tiếp không hề biết eO2 đang chặn và vẫn bắn tiếp - xem loạt 7 request
     * cùng một giây đều 429 lúc 10:22:21 ngày 18/09/2026. Bắn vào server đang
     * chặn vừa vô ích vừa làm nó gia hạn chặn.
     *
     * @param int $explicitWait số giây eO2 yêu cầu qua header, 0 nếu nó không nói
     * @return int số giây phải chờ, để nơi gọi báo lại cho người dùng
     */
    private function tripBreaker(int $explicitWait): int
    {
        // eO2 nói rõ phải chờ bao lâu thì tin nó, không tự nhân thêm.
        $wait = $explicitWait > 0
            ? $explicitWait
            : min(self::BREAKER_MAX_WAIT, self::BREAKER_BASE_WAIT * (2 ** ($this->bumpBreakerStreak() - 1)));

        $until = time() + $wait;

        try {
            // Không rút ngắn lệnh tạm dừng đang có hiệu lực dài hơn.
            $current = Cache::get(self::BREAKER_KEY);
            if (is_numeric($current) && (int) $current >= $until) {
                return max(1, (int) $current - time());
            }

            Cache::put(self::BREAKER_KEY, $until, $wait + 60);
        } catch (\Throwable $e) {
            Log::warning('Khong ghi duoc lenh tam dung Shift API: ' . $e->getMessage());
        }

        return $wait;
    }

    /** Tăng số lần bị 429 liên tiếp và trả về giá trị mới (tối thiểu 1). */
    private function bumpBreakerStreak(): int
    {
        // TTL dài hơn mức chờ tối đa, nếu không thì chuỗi bị chặn hết hạn giữa
        // hai lần thử và backoff tụt về 60s - đúng cái nhịp đang bị eO2 chặn.
        $ttl = self::BREAKER_MAX_WAIT + 300;

        try {
            Cache::add(self::BREAKER_STREAK_KEY, 0, $ttl);
            $streak = Cache::increment(self::BREAKER_STREAK_KEY);
            $streak = $streak === false ? 1 : max(1, (int) $streak);
            // `increment` không làm mới TTL, phải ghi lại để chuỗi sống đủ lâu.
            Cache::put(self::BREAKER_STREAK_KEY, $streak, $ttl);
        } catch (\Throwable $e) {
            return 1;
        }

        return $streak;
    }

    private function resetBreakerStreak(): void
    {
        try {
            Cache::forget(self::BREAKER_STREAK_KEY);
        } catch (\Throwable $e) {
            // Chuỗi còn lại chỉ làm lần chặn sau chờ lâu hơn, không sao.
        }
    }

    /**
     * Bảng tra một tháng của phân xưởng lớn vượt `max_allowed_packet` (1MB mặc
     * định của MySQL) nếu ghi thẳng -> nén gzip + base64 trước khi cache.
     * Lỗi cache không được phép làm hỏng request.
     */
    private function cachePut(string $key, array $value, int $ttl): void
    {
        try {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE);
            Cache::put($key, 'gz:' . base64_encode(gzcompress($json, 6)), $ttl);
        } catch (\Throwable $e) {
            Log::warning('Khong ghi duoc cache lich truc: ' . $e->getMessage(), ['key' => $key]);
        }
    }

    private function cacheGet(string $key): ?array
    {
        try {
            $cached = Cache::get($key);
        } catch (\Throwable $e) {
            Log::warning('Khong doc duoc cache lich truc: ' . $e->getMessage(), ['key' => $key]);
            return null;
        }

        if (is_string($cached) && str_starts_with($cached, 'gz:')) {
            $json = @gzuncompress(base64_decode(substr($cached, 3)));
            if ($json !== false) {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return null;
    }
}
