<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\EmployeeRosterSync;
use App\Services\ShiftApiService;

class DashBoardController extends Controller
{
    /**
     * Ánh xạ mã phân xưởng -> mã bộ phận của eO2 PMS.
     *
     * Dùng chung cho `getData` (đọc) và `warmCache` (nạp sẵn) để hai bên không
     * bao giờ hỏi hai bộ phận khác nhau: lệch nhau thì nút Đồng bộ sẽ nạp cache
     * cho một bộ phận mà trang không hề đọc tới, bấm xong vẫn thấy số cũ.
     *
     * LƯU Ý: bảng này KHÁC `EmployeeRosterSync::DEPARTMENTS` ở mã QA (18 so với
     * 9). Khác biệt có sẵn từ trước, chưa rõ bên nào đúng, nên ở đây giữ nguyên
     * đúng bảng mà Dashboard vốn dùng thay vì tự ý đổi.
     */
    public const DEPARTMENT_MAP = [
        'EN' => 3,
        'PXTN' => 6,
        'PXV1' => 15,
        'WH' => 17,
        'PXVH' => 30,
        'PXDN' => 34,
        'PXV2' => 32,
        'QA' => 18,
    ];

    public function index(Request $request)
    {
        // View for Dashboard
        session()->put(['title' => 'DASHBOARD TÌNH HÌNH NHÂN SỰ']);

        // Departments list
        $departments = [
            'PXV1' => 'Phân xưởng Viên 1',
            'PXTN' => 'Phân xưởng Thuốc Nước',
            'PXV2' => 'Phân xưởng Viên 2',
            'PXDN' => 'Phân xưởng Dùng Ngoài',
            'PXVH' => 'Phân xưởng Viên H',
            'EN'   => 'Kỹ Thuật Bảo Trì',
            'QA'   => 'Hiệu chuẩn',

        ];

        // Không load groups mặc định nữa, sẽ load qua API getData
        $groups = [];

        return view('pages.assignment.DashBoard.index', compact('departments', 'groups'));
    }

    /**
     * Nút "Đồng bộ dữ liệu e-o" trên Dashboard. Làm HAI việc, vì hai nguồn số
     * liệu của trang này hoàn toàn tách rời nhau:
     *
     *   1. Lịch trực (ca, tăng ca, nghỉ phép) - nạp lại cache tháng đang xem.
     *   2. Danh sách nhân sự - ghi xuống `employees` + `employee_assignments`.
     *
     * Trước đây nút chỉ làm việc (1). Hậu quả gặp thực tế 10/09/2026: nhân sự
     * 23220 đã có trên eO2 và hiện đúng ở sidebar Tình Hình Nhân Sự (sidebar đọc
     * thẳng API), nhưng Dashboard và trang định mức đọc từ DB nên không thấy -
     * mà bấm nút bao nhiêu lần cũng vô ích vì nút không hề đụng tới DB. Người
     * dùng đọc nhãn "Đồng bộ dữ liệu e-o" thì đương nhiên hiểu là đồng bộ MỌI
     * dữ liệu e-office, nên nút phải làm đúng như tên gọi.
     *
     * Chỉ một phân xưởng, không phải cả 7, vì mỗi phân xưởng đã tốn 3-6 request
     * nặng (~10-40s). Muốn nạp đủ cả 7 thì để `shifts:warm-cache` (lịch trực) và
     * `employees:sync-roster` (nhân sự, 05:00) chạy nền lo - hai command đó có
     * giãn nhịp và biết lùi lại khi eO2 trả 429, còn một HTTP request thì không
     * đủ thời gian.
     */
    public function warmCache(Request $request, ShiftApiService $shiftApi, EmployeeRosterSync $rosterSync)
    {
        $code = (string) $request->input('production_code');
        $depId = self::DEPARTMENT_MAP[$code] ?? null;

        if (!$depId) {
            return response()->json([
                'error' => "Phân xưởng '{$code}' không có mã bộ phận tương ứng.",
            ], 422);
        }

        // Bám theo ô ngày trên Dashboard: người dùng đang xem tháng nào thì nạp
        // lại đúng tháng đó, không phải lúc nào cũng là tháng hiện tại.
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : Carbon::now();
        $month = (int) $date->month;
        $year = (int) $date->year;
        $mergeWarehouse = $depId === 15;

        // Cùng khoá 60s với nút Đồng bộ ở sidebar Lịch công tác. Cache nằm trên
        // server và dùng chung, nên một người bấm là mọi người cùng có số mới;
        // đổi lại phải chặn bấm dồn, nếu không eO2 sẽ trả HTTP 429.
        $lockKey = "shiftapi:refresh:{$year}:{$month}:{$depId}";
        if (!Cache::add($lockKey, 1, 60)) {
            return response()->json([
                'error' => 'Phân xưởng này vừa được đồng bộ. Vui lòng chờ khoảng 1 phút rồi thử lại.',
            ], 429);
        }

        // PXV1 nặng nhất: 6 request cho lịch trực + 2 cho danh sách nhân sự, và
        // riêng lượt danh sách của PXV1 đo được ~88s. Mặc định 30s của PHP không đủ.
        @set_time_limit(300);

        // --- 1. Lịch trực ---
        $shiftApi->forgetMonth($month, $year, $depId, $mergeWarehouse);
        $data = $shiftApi->monthlyByDayKey($month, $year, $depId, $mergeWarehouse);

        // `monthlyByDayKey` vẫn trả về mảng khi phải rơi về bản sao lưu 24h, nên
        // chỉ nhìn giá trị trả về sẽ báo thành công nhầm. Hỏi cache nóng mới
        // biết lượt này có thật sự lấy được số liệu mới hay không.
        $shiftFresh = $shiftApi->hasFreshMonth($month, $year, $depId, $mergeWarehouse);

        // --- 2. Danh sách nhân sự ---
        //
        // Chạy SAU lịch trực và không được phép làm hỏng phần trên: nếu lịch
        // trực đã nạp xong mà bước này ném lỗi thì công sức 40s vừa rồi mất
        // trắng. Vì vậy `syncRoster` nuốt mọi Throwable và trả về trạng thái để
        // báo riêng, thay vì để lỗi thoát ra ngoài.
        //
        // Chạy cả khi lịch trực thất bại: hai việc dùng endpoint khác nhau, hỏng
        // cái này không có nghĩa cái kia cũng hỏng - và đây mới đúng là lúc
        // người dùng cần bảng nhân sự được vá.
        $roster = $this->syncRoster($code, $rosterSync);

        if (!$shiftFresh) {
            $wait = $shiftApi->rateLimitedFor();

            return response()->json([
                // $wait có giá trị ở cả hai trường hợp: eO2 trả 429, hoặc hạn
                // ngạch nội bộ đã hết nên hệ thống chủ động không gọi.
                'error' => $wait
                    ? "Đang tạm dừng gọi eO2 để tránh bị chặn. Thử lại sau khoảng {$wait}s."
                    : 'Máy chủ eO2 không trả dữ liệu. Xem storage/logs/laravel.log để biết chi tiết.',
                'roster' => $roster,
            ], 503);
        }

        return response()->json([
            'success' => true,
            'department' => $code,
            'month' => sprintf('%02d/%d', $month, $year),
            'employees' => count($data ?? []),
            'roster' => $roster,
        ]);
    }

    /**
     * Ghi danh sách nhân sự của một phân xưởng xuống `employees` +
     * `employee_assignments`. Bọc kín lỗi để nút Đồng bộ không đổ vì bước này.
     *
     * `fresh: true` là bắt buộc: `roster()` có cache riêng 6 giờ, mà người dùng
     * bấm nút chính vì nghi số liệu đang cũ. Trả lại đúng bản cache đang bị nghi
     * ngờ thì nút không sửa được gì - đúng cái bẫy đã làm mất thời gian truy vết
     * ngày 10/09/2026.
     *
     * @return array{synced:bool, employees:int, reason:?string}
     */
    private function syncRoster(string $code, EmployeeRosterSync $rosterSync): array
    {
        // `DEPARTMENT_MAP` ở trên rộng hơn `EmployeeRosterSync::DEPARTMENTS`:
        // 'WH' (Kho) là bộ phận của eO2 chứ không phải phân xưởng có bảng phân
        // công riêng, nên không có gì để đồng bộ. Bỏ qua trong im lặng thay vì
        // báo lỗi - lịch trực của nó vẫn nạp bình thường ở bước 1.
        if (!isset(EmployeeRosterSync::DEPARTMENTS[$code])) {
            return [
                'synced' => false,
                'employees' => 0,
                'reason' => "Phân xưởng {$code} không có danh sách nhân sự riêng để đồng bộ",
            ];
        }

        try {
            return $rosterSync->refresh(
                $code,
                (int) config('shiftapi.manual_sync_timeout', 180),
                true
            );
        } catch (\Throwable $e) {
            Log::warning('Dong bo nhan su tu nut Dashboard that bai', [
                'department' => $code,
                'error' => $e->getMessage(),
            ]);

            return ['synced' => false, 'employees' => 0, 'reason' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * Bảng tra cứu ca trực theo NGÀY LỊCH THỰC TẾ.
     *
     * API mới (`range`/`leave`/`overtime`) đã trả theo ngày lịch thật nên quy tắc
     * lệch tháng "day21..day31 thuộc tháng trước" của API `by-department` cũ đã
     * bị bỏ. Với PXV1 (dep 15) gộp thêm Kho (dep 17).
     *
     * @return array [employeeCode => ['Y-m-d' => dayData]]
     */
    private function buildShiftIndex(Carbon $startDate, $daysInPeriod, $departmentId, ShiftApiService $shiftApi)
    {
        $from = $startDate->copy()->startOfDay();
        $to = $from->copy()->addDays(max(0, $daysInPeriod - 1));

        $shiftIndex = $shiftApi->shiftIndex($from, $to, (int) $departmentId, (int) $departmentId === 15) ?? [];

        $index = [];
        foreach ($shiftIndex as $code => $person) {
            $index[(string) $code] = $person['days'];
        }

        return $index;
    }

    public function getData(Request $request, ShiftApiService $shiftApi)
    {
        $production_code = $request->production_code ?? session('user')['production_code'] ?? 'PXV1';
        $type = $request->type ?? 'day'; // day, week, month
        $date = $request->date ?? Carbon::now()->format('Y-m-d');
        $group_id = $request->group_id; // Thêm lọc tổ

        $carbonDate = Carbon::parse($date);

        if ($type == 'day') {
            $startDate = $carbonDate->copy()->setTime(6, 0, 0);
            $endDate = $startDate->copy()->addDays(1);
            $daysInPeriod = 1;
        } elseif ($type == 'week') {
            $startDate = $carbonDate->copy()->startOfWeek()->setTime(6, 0, 0);
            $endDate = $startDate->copy()->addDays(7);
            $daysInPeriod = 7;
        } else { // month
            $startDate = $carbonDate->copy()->startOfMonth()->setTime(6, 0, 0);
            $endDate = $carbonDate->copy()->endOfMonth()->addDays(1)->setTime(6, 0, 0);
            $daysInPeriod = $startDate->diffInDays($endDate);
        }

        // Ngày lịch của từng ô trong kỳ. Ngày công chạy 06:00 hôm nay -> 06:00
        // hôm sau, nên ô thứ $d mang ngày lịch $startDate + $d ngày (giống hệt
        // cách vòng lặp lấy ca trực bên dưới tính $dayStr).
        $periodDates = [];
        for ($d = 0; $d < $daysInPeriod; $d++) {
            $periodDates[$d] = $startDate->copy()->addDays($d)->format('Y-m-d');
        }
        $lastDateOfPeriod = $periodDates[$daysInPeriod - 1];

        // 1. Get total personnel in department (and optional group)
        //
        // `employees.created_at` được coi là NGÀY VÀO LÀM: đây là thời điểm nhân
        // sự xuất hiện trong danh sách của eO2 và được `employees:sync-roster`
        // ghi xuống. Trước ngày đó người này chưa thuộc phân xưởng nên không
        // được vào bất kỳ con số thống kê nào.
        //
        // Không lọc thì danh sách nhân sự là ảnh chụp "ai đang thuộc phân xưởng
        // LÚC NÀY" và bị đem áp ngược cho mọi ngày quá khứ. Gặp thực tế
        // 10/09/2026: nhân sự 23220 vừa được thêm đã hiện "Chưa xếp lịch" suốt
        // từ 01/09 - những ngày cậu ta còn chưa vào công ty.
        $personnelQuery = DB::table('employees as e')
            ->where('e.active', 1)
            ->where(function ($q) {
                $q->whereNull('e.resign')->orWhere('e.resign', 0);
            })
            // So sánh ở mức NGÀY, không phải mốc thời gian: người được tạo lúc
            // 14:19 ngày 10/09 vẫn phải được tính trọn ngày công 10/09 (bắt đầu
            // từ 06:00 sáng hôm đó), nếu không sẽ hụt mất chính ngày vào làm.
            ->whereRaw('DATE(e.created_at) <= ?', [$lastDateOfPeriod])
            ->join('employee_assignments as ea', 'e.id', '=', 'ea.employees_id')
            ->where('ea.production_code', $production_code)
            ->where('ea.active', 1);

        if ($group_id) {
            $personnelQuery->where('ea.group_id', $group_id);
        }

        $personnelList = $personnelQuery
            ->select('e.id', 'e.code', 'e.name', 'e.on_maternity_leave', 'e.on_long_leave', DB::raw('DATE(e.created_at) as joined_on'), DB::raw('GROUP_CONCAT(DISTINCT ea.group_id SEPARATOR ",") as group_ids'))
            ->groupBy('e.id', 'e.code', 'e.name', 'e.on_maternity_leave', 'e.on_long_leave', 'joined_on')
            ->get();

        $isENorQA = in_array($production_code, ['EN', 'QA']);
        $hardcodedGroups = [
            1 => "Trung Tâm Cân",
            3 => "Pha Chế",
            4 => "Văn Phòng",
            5 => "Định Hình",
            6 => "Bao Phim",
            7 => "ĐGSC",
            8 => "ĐGTC",
            9 => "VSCN + Kho BTP",
            10 => "Mã Hoá BB"
        ];
        $dbGroups = [];
        if ($isENorQA) {
            $dbGroups = DB::table('stage_groups')->pluck('name', 'code')->toArray();
        }

        $employees = [];
        foreach ($personnelList as $emp) {
            $ids = array_filter(explode(',', $emp->group_ids), 'strlen');
            $names = [];
            foreach ($ids as $gid) {
                if ($isENorQA) {
                    $names[] = $dbGroups[$gid] ?? 'NA';
                } else {
                    $names[] = $hardcodedGroups[$gid] ?? 'NA';
                }
            }
            $emp->group_names = count($names) > 0 ? implode(', ', array_unique($names)) : '-';
            $emp->group_ids_arr = $ids;
            $employees[$emp->id] = $emp;
        }
        $employeeIds = array_keys($employees);

        if (empty($employeeIds)) {
            return response()->json([
                'success' => true,
                'total_personnel' => 0,
                'stats' => ['on_leave' => 0, 'unassigned' => 0, 'under_8h' => 0, 'exact_8h' => 0, 'over_8h' => 0, 'total_ot_hours' => 0],
                'details' => [],
                'overtime_by_group' => [],
                'overtime_by_room' => [],
                'period' => ['start' => $startDate->format('Y-m-d H:i'), 'end' => $endDate->format('Y-m-d H:i'), 'days' => $daysInPeriod]
            ]);
        }

        // 2. Get assignments in period
        $assignments = DB::table('assignments as a')
            ->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')
            ->leftJoin('room as r', 'a.room_id', '=', 'r.id')
            ->where('a.deparment_code', $production_code)
            ->where('a.active', 1)
            ->where('a.start', '>=', $startDate)
            ->where('a.start', '<', $endDate)
            ->whereIn('ap.personnel_id', $employeeIds)
            ->select(
                'ap.personnel_id',
                'a.start',
                'a.end',
                // Giờ riêng của từng nhân sự mới là giờ thực tế được phân công
                // (trang Lịch công tác và panel Tình Hình Nhân Sự đều dùng giờ này)
                DB::raw('COALESCE(ap.start, a.start) as p_start'),
                DB::raw('COALESCE(ap.end, a.end) as p_end'),
                'r.name as room_name',
                'r.id as room_id',
                'a.work_location',
                'a.Sheet'
            )
            ->get();

        // Calculate hours per employee and room
        $employeeDailyHours = [];
        $employeeDailyLeave = [];
        $roomHoursMap = []; // [room_name => total_hours]
        // [personnel_id][chỉ số ngày][room_name] => số giờ, dùng để quy giờ tăng
        // ca của một người trong ngày về đúng (các) phòng người đó đã làm.
        $empDayRoomHours = [];
        $empCodeToId = [];
        foreach ($employeeIds as $id) {
            $employeeDailyHours[$id] = array_fill(0, $daysInPeriod, 0);
            $employeeDailyLeave[$id] = array_fill(0, $daysInPeriod, false);
            $empCodeToId[$employees[$id]->code] = $id;
        }

        foreach ($assignments as $assignment) {
            // Ngày công tác lấy theo giờ của công tác, để trùng đúng tập bản ghi
            // mà trang Lịch công tác hiển thị cho ngày đó.
            $aStart = Carbon::parse($assignment->start);

            // Số giờ thì lấy theo giờ riêng của nhân sự, vì đó mới là giờ thực tế
            // người này được phân công (UI cho phép chỉnh riêng từng người).
            $pStart = Carbon::parse($assignment->p_start);
            $pEnd = Carbon::parse($assignment->p_end);

            if ($pEnd->lte($pStart)) {
                continue;
            }

            // Mỗi công tác thuộc trọn về ngày công tác chứa giờ bắt đầu của nó,
            // giống hệt cách trang Lịch công tác hiển thị: không cắt phần tràn
            // qua mốc 06:00 hôm sau, nếu không số giờ đó sẽ biến mất khỏi mọi ngày.
            $d = (int) floor(($aStart->getTimestamp() - $startDate->getTimestamp()) / 86400);
            if ($d < 0 || $d >= $daysInPeriod) {
                continue;
            }

            $durationMin = $pStart->diffInMinutes($pEnd);

            // Sheet: 1=C1, 2=C2, 3=C3, 6=C4. Chỉ trừ nghỉ trưa cho 4=HC, 5=Khác
            if (!in_array($assignment->Sheet, [1, 2, 3, 6])) {
                $lunchStart = $pStart->copy()->setTime(11, 30, 0);
                $lunchEnd = $pStart->copy()->setTime(12, 15, 0);

                $lOverlapStart = $pStart->copy()->max($lunchStart);
                $lOverlapEnd = $pEnd->copy()->min($lunchEnd);

                if ($lOverlapStart->lt($lOverlapEnd)) {
                    $durationMin -= $lOverlapStart->diffInMinutes($lOverlapEnd);
                }
            }

            $hours = $durationMin / 60;

            if (isset($employeeDailyHours[$assignment->personnel_id])) {
                $employeeDailyHours[$assignment->personnel_id][$d] += $hours;
            }

            $roomName = $assignment->room_name ?? $assignment->work_location ?? 'Khác';
            if (!isset($roomHoursMap[$roomName])) {
                $roomHoursMap[$roomName] = 0;
            }
            $roomHoursMap[$roomName] += $hours;

            if (!isset($empDayRoomHours[$assignment->personnel_id][$d][$roomName])) {
                $empDayRoomHours[$assignment->personnel_id][$d][$roomName] = 0;
            }
            $empDayRoomHours[$assignment->personnel_id][$d][$roomName] += $hours;
        }

        // --- Fetch Shifts to determine Leave (P) AND collect overtime ---
        $departmentId = self::DEPARTMENT_MAP[$production_code] ?? null;

        // Lấy danh sách ngày nghỉ (off-dates)
        $offDates = DB::table('off_days')
            ->whereDate('off_date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('off_date', '<=', $endDate->format('Y-m-d'))
            ->pluck('off_date')->toArray();
        $offDatesMap = [];
        foreach ($offDates as $od) {
            $offDatesMap[substr($od, 0, 10)] = true;
        }

        $employeeOvertimeHours = []; // total overtime for period
        $employeeDailyOT = []; // overtime hours per day
        $employeeRegisteredShifts = [];
        $employeeEofficeHours = [];
        foreach ($employees as $emp) {
            $employeeOvertimeHours[$emp->code] = 0;
            $employeeRegisteredShifts[$emp->code] = [];
            $employeeEofficeHours[$emp->code] = 0;
        }

        if ($departmentId) {
            $shiftIndex = $this->buildShiftIndex($startDate, $daysInPeriod, $departmentId, $shiftApi);

            foreach ($shiftIndex as $code => $daysByDate) {
                $totalOT = 0;
                $shifts = [];
                $totalEoffice = 0;

                for ($d = 0; $d < $daysInPeriod; $d++) {
                    $currentDay = $startDate->copy()->addDays($d);
                    $dayStr = $currentDay->format('Y-m-d');
                    $dayData = $daysByDate[$dayStr] ?? null;

                    // ShiftApiService luôn trả về mảng đã chuẩn hoá cho mỗi ngày.
                    // Giờ làm việc e-office (`regular_working_Hours`) không có
                    // trong bộ 3 endpoint mới nên tạm để 0.
                    $shiftCode = strtoupper(trim((string) ($dayData['shift'] ?? '')));
                    $ot = floatval($dayData['overtime'] ?? 0);
                    $eoffice = floatval($dayData['regular_working_Hours'] ?? 0);

                    // Reset regular working hours nếu rơi vào ngày nghỉ (off-date)
                    if (isset($offDatesMap[$dayStr])) {
                        $eoffice = 0;
                    }

                    if ($shiftCode === 'P') {
                        if (isset($empCodeToId[$code])) {
                            $empId = $empCodeToId[$code];
                            $employeeDailyLeave[$empId][$d] = true;
                        }
                    }
                    if ($shiftCode && $shiftCode !== 'OFF' && $shiftCode !== '') {
                        if ($daysInPeriod == 1) {
                            $shifts[] = $shiftCode;
                        } else {
                            $shifts[] = $currentDay->format('d/m') . ': ' . $shiftCode;
                        }
                    }
                    $totalOT += $ot;
                    $totalEoffice += $eoffice;

                    if (isset($employeeOvertimeHours[$code]) && $ot > 0) {
                        if (!isset($employeeDailyOT[$code])) {
                            $employeeDailyOT[$code] = array_fill(0, $daysInPeriod, 0);
                        }
                        $employeeDailyOT[$code][$d] += $ot;
                    }
                }

                if (isset($employeeOvertimeHours[$code])) {
                    $employeeOvertimeHours[$code] += $totalOT;
                    $employeeRegisteredShifts[$code] = array_merge($employeeRegisteredShifts[$code], $shifts);
                    $employeeEofficeHours[$code] += $totalEoffice;
                }
            }
        }

        $stats_laps = [
            'on_leave' => 0,
            'maternity_leave' => 0,
            'long_leave' => 0,
            'unassigned' => 0,
            'under_8h' => 0,
            'exact_8h' => 0,
            'over_8h' => 0,
            'total_ot_hours' => 0,
        ];

        $stats_people = [
            'on_leave' => 0,
            'maternity_leave' => 0,
            'long_leave' => 0,
            'unassigned' => 0,
            'under_8h' => 0,
            'exact_8h' => 0,
            'over_8h' => 0,
            'total_ot_hours' => 0,
        ];

        $details = [];
        $groupOvertimeMap = []; // [group_name => total_ot]
        $roomOvertimeMap = []; // [room_name => ['ot_hours' => float, 'people' => [code => true]]]

        $stats_daily = [];
        for ($d = 0; $d < $daysInPeriod; $d++) {
            $stats_daily[$d] = [
                'date' => $startDate->copy()->addDays($d)->format('d/m/Y'),
                'on_leave' => 0,
                'maternity_leave' => 0,
                'long_leave' => 0,
                'unassigned' => 0,
                'under_8h' => 0,
                'exact_8h' => 0,
                'over_8h' => 0,
                'total_ot_hours' => 0,
            ];
        }

        foreach ($employeeDailyHours as $empId => $dailyHours) {
            $empCode = $employees[$empId]->code;

            // Ô đầu tiên trong kỳ mà người này đã vào làm. Kỳ nhiều ngày (tuần /
            // tháng) có thể chứa cả quãng trước ngày vào làm — phần đó không
            // được tính vào bất kỳ con số nào, kể cả mẫu số giờ trung bình.
            //
            // Tính ngay đầu vòng lặp để mọi con số phía dưới (tăng ca, phân
            // loại theo ngày, giờ trung bình) cùng dùng một mốc.
            $joinedOn = $employees[$empId]->joined_on ?? null;
            $firstDay = 0;
            if ($joinedOn) {
                while ($firstDay < $daysInPeriod && $periodDates[$firstDay] < $joinedOn) {
                    $firstDay++;
                }
            }
            $daysEmployed = $daysInPeriod - $firstDay;

            // Người vào làm sau kỳ đang xem đã bị loại từ câu truy vấn, nhưng
            // vẫn chặn ở đây để tránh chia cho 0 nếu sau này ai đó nới bộ lọc.
            if ($daysEmployed <= 0) {
                continue;
            }

            $empOT = round($employeeOvertimeHours[$empCode] ?? 0, 2);
            $stats_laps['total_ot_hours'] += $empOT;
            $stats_people['total_ot_hours'] += $empOT;

            if (isset($employeeDailyOT[$empCode])) {
                for ($d = $firstDay; $d < $daysInPeriod; $d++) {
                    $otOfDay = $employeeDailyOT[$empCode][$d];
                    $stats_daily[$d]['total_ot_hours'] += $otOfDay;

                    if ($otOfDay <= 0) {
                        continue;
                    }

                    // Quy giờ tăng ca của ngày về phòng người này đã làm hôm đó.
                    // Làm nhiều phòng thì chia theo tỉ lệ số giờ ở từng phòng;
                    // không có phân công nào thì gom vào "Chưa phân công".
                    $roomsOfDay = $empDayRoomHours[$empId][$d] ?? [];
                    $roomTotal = array_sum($roomsOfDay);
                    if ($roomTotal <= 0) {
                        $roomsOfDay = ['Chưa phân công' => 1];
                        $roomTotal = 1;
                    }

                    foreach ($roomsOfDay as $rName => $rHours) {
                        if (!isset($roomOvertimeMap[$rName])) {
                            $roomOvertimeMap[$rName] = ['ot_hours' => 0, 'people' => []];
                        }
                        $roomOvertimeMap[$rName]['ot_hours'] += $otOfDay * ($rHours / $roomTotal);
                        $roomOvertimeMap[$rName]['people'][$empCode] = true;
                    }
                }
            }

            $totalHours = array_sum($dailyHours);
            $avgHoursPerDay = $totalHours / $daysEmployed;

            $assignedDays = 0;
            $leaveDays = 0;

            $isMaternity = !empty($employees[$empId]->on_maternity_leave);
            $isLongLeave = !empty($employees[$empId]->on_long_leave);

            for ($d = $firstDay; $d < $daysInPeriod; $d++) {
                $h = $dailyHours[$d];
                if ($h == 0) {
                    if ($isMaternity) {
                        $stats_laps['maternity_leave']++;
                        $stats_daily[$d]['maternity_leave']++;
                    } elseif ($isLongLeave) {
                        $stats_laps['long_leave']++;
                        $stats_daily[$d]['long_leave']++;
                    } elseif (!empty($employeeDailyLeave[$empId][$d])) {
                        $stats_laps['on_leave']++;
                        $stats_daily[$d]['on_leave']++;
                        $leaveDays++;
                    } else {
                        $stats_laps['unassigned']++;
                        $stats_daily[$d]['unassigned']++;
                    }
                } elseif ($h < 7.9) {
                    $stats_laps['under_8h']++;
                    $stats_daily[$d]['under_8h']++;
                    $assignedDays++;
                } elseif ($h <= 8.1) {
                    $stats_laps['exact_8h']++;
                    $stats_daily[$d]['exact_8h']++;
                    $assignedDays++;
                } else {
                    $stats_laps['over_8h']++;
                    $stats_daily[$d]['over_8h']++;
                    $assignedDays++;
                }
            }

            // People Classification (Dành cho các ô Inner theo yêu cầu)
            if ($isMaternity) {
                $stats_people['maternity_leave']++;
            } elseif ($isLongLeave) {
                $stats_people['long_leave']++;
            } elseif ($totalHours == 0) {
                if ($leaveDays > 0) {
                    $stats_people['on_leave']++;
                } else {
                    $stats_people['unassigned']++;
                }
            } elseif ($avgHoursPerDay < 7.9) {
                $stats_people['under_8h']++;
            } elseif ($avgHoursPerDay <= 8.1) {
                $stats_people['exact_8h']++;
            } else {
                $stats_people['over_8h']++;
            }

            if ($isMaternity) {
                $status = 'Thai sản';
            } elseif ($isLongLeave) {
                $status = 'Phép dài hạn';
            } elseif ($daysInPeriod == 1) {
                if ($totalHours == 0) {
                    $status = $leaveDays > 0 ? 'Nghỉ phép (P)' : 'Chưa phân công';
                } elseif ($totalHours < 7.9) {
                    $status = '< 8h';
                } elseif ($totalHours <= 8.1) {
                    $status = 'Đủ 8h';
                } else {
                    $status = '> 8h';
                }
            } else {
                // Mẫu số là số ngày người này THỰC SỰ đã vào làm trong kỳ, không
                // phải độ dài cả kỳ: người vào làm giữa tháng mà ghi "Đã xếp
                // 5/30 ngày" thì đọc như đang bỏ bê, trong khi 5/5 mới là đúng.
                $status = $daysEmployed < $daysInPeriod
                    ? "Vào làm từ " . Carbon::parse($joinedOn)->format('d/m')
                    : '';

                if ($assignedDays == 0) {
                    $status = ($leaveDays == $daysEmployed ? 'Nghỉ phép hết kỳ' : "Chưa xếp lịch ($leaveDays ngày phép)")
                        . ($status ? " — {$status}" : '');
                } else {
                    $status = "Đã xếp $assignedDays / $daysEmployed ngày" . ($status ? " — {$status}" : '');
                }
            }

            $details[] = [
                'code' => $employees[$empId]->code,
                'name' => $employees[$empId]->name,
                'group' => $employees[$empId]->group_names,
                'registered_shifts' => array_values(array_unique($employeeRegisteredShifts[$empCode] ?? [])),
                'total_hours' => round($totalHours, 2),
                'eoffice_hours' => round($employeeEofficeHours[$empCode] ?? 0, 2),
                'overtime_hours' => $empOT,
                'status' => $status
            ];

            // Tổng hợp OT theo tổ
            $groupName = $employees[$empId]->group_names;
            if (!isset($groupOvertimeMap[$groupName])) {
                $groupOvertimeMap[$groupName] = ['name' => $groupName, 'ot_hours' => 0, 'count' => 0, 'ot_people_count' => 0];
            }
            $groupOvertimeMap[$groupName]['ot_hours'] += $empOT;
            $groupOvertimeMap[$groupName]['count']++;
            if ($empOT > 0) {
                $groupOvertimeMap[$groupName]['ot_people_count']++;
            }
        }

        $stats_laps['total_ot_hours'] = round($stats_laps['total_ot_hours'], 2);
        $stats_people['total_ot_hours'] = round($stats_people['total_ot_hours'], 2);
        for ($d = 0; $d < $daysInPeriod; $d++) {
            $stats_daily[$d]['total_ot_hours'] = round($stats_daily[$d]['total_ot_hours'], 2);
        }

        // Sort details by total_hours ascending
        usort($details, function ($a, $b) {
            return $a['total_hours'] <=> $b['total_hours'];
        });

        // Format overtime by group
        $overtimeByGroup = array_values(array_filter(
            array_map(function ($g) {
                return [
                    'name' => $g['name'],
                    'ot_hours' => round($g['ot_hours'], 2),
                    'count' => $g['count'],
                    'ot_people_count' => $g['ot_people_count']
                ];
            }, $groupOvertimeMap),
            fn($g) => $g['ot_hours'] > 0 || $g['count'] > 0
        ));
        usort($overtimeByGroup, fn($a, $b) => $b['ot_hours'] <=> $a['ot_hours']);

        // Tăng ca theo phòng: giờ TC lấy từ API (đã quy về phòng ở trên),
        // total_hours là tổng giờ phân công của phòng để đối chiếu.
        $roomNames = array_unique(array_merge(array_keys($roomHoursMap), array_keys($roomOvertimeMap)));
        $overtimeByRoom = [];
        foreach ($roomNames as $rName) {
            $overtimeByRoom[] = [
                'name' => $rName,
                'ot_hours' => round($roomOvertimeMap[$rName]['ot_hours'] ?? 0, 2),
                'ot_people_count' => count($roomOvertimeMap[$rName]['people'] ?? []),
                'total_hours' => round($roomHoursMap[$rName] ?? 0, 2),
            ];
        }
        usort($overtimeByRoom, fn($a, $b) => [$b['ot_hours'], $b['total_hours']] <=> [$a['ot_hours'], $a['total_hours']]);

        // 4. Lấy danh sách tất cả các tổ khả dụng trong phân xưởng này
        $availableGroupsArray = [];
        
        if ($isENorQA) {
            foreach ($dbGroups as $code => $name) {
                if ($name !== 'NA') {
                    $availableGroupsArray[] = ['code' => $code, 'name' => $name];
                }
            }
        } else {
            foreach ($hardcodedGroups as $code => $name) {
                if ($name !== 'NA') {
                    $availableGroupsArray[] = ['code' => $code, 'name' => $name];
                }
            }
        }

        usort($availableGroupsArray, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return response()->json([
            'success' => true,
            'total_personnel' => count($employees),
            'stats_people' => $stats_people,
            'stats_laps' => $stats_laps,
            'stats_daily' => $stats_daily,
            'stats' => $stats_people, // fallback for legacy code
            'details' => $details,
            'overtime_by_group' => $overtimeByGroup,
            'overtime_by_room' => $overtimeByRoom,
            'available_groups' => $availableGroupsArray,
            'period' => [
                'start' => $startDate->format('Y-m-d H:i'),
                'end' => $endDate->format('Y-m-d H:i'),
                'days' => $daysInPeriod
            ]
        ]);
    }
}
