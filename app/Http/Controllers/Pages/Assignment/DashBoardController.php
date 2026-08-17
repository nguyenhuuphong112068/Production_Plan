<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\ShiftApiService;

class DashBoardController extends Controller
{
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

        // 1. Get total personnel in department (and optional group)
        $personnelQuery = DB::table('employees as e')
            ->where('e.active', 1)
            ->where(function ($q) {
                $q->whereNull('e.resign')->orWhere('e.resign', 0);
            })
            ->join('employee_assignments as ea', 'e.id', '=', 'ea.employees_id')
            ->where('ea.production_code', $production_code)
            ->where('ea.active', 1);

        if ($group_id) {
            $personnelQuery->where('ea.group_id', $group_id);
        }

        $personnelList = $personnelQuery
            ->select('e.id', 'e.code', 'e.name', 'e.on_maternity_leave', 'e.on_long_leave', DB::raw('GROUP_CONCAT(DISTINCT ea.group_id SEPARATOR ",") as group_ids'))
            ->groupBy('e.id', 'e.code', 'e.name', 'e.on_maternity_leave', 'e.on_long_leave')
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
        $deptMapping = [
            'EN' => 3,
            'PXTN' => 6,
            'PXV1' => 15,
            'WH' => 17,
            'PXVH' => 30,
            'PXDN' => 34,
            'PXV2' => 32,
            'QA' => 18,
        ];

        $departmentId = $deptMapping[$production_code] ?? null;

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
            $empOT = round($employeeOvertimeHours[$empCode] ?? 0, 2);
            $stats_laps['total_ot_hours'] += $empOT;
            $stats_people['total_ot_hours'] += $empOT;

            if (isset($employeeDailyOT[$empCode])) {
                for ($d = 0; $d < $daysInPeriod; $d++) {
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
            $avgHoursPerDay = $daysInPeriod > 0 ? ($totalHours / $daysInPeriod) : 0;

            $assignedDays = 0;
            $leaveDays = 0;

            $isMaternity = !empty($employees[$empId]->on_maternity_leave);
            $isLongLeave = !empty($employees[$empId]->on_long_leave);

            for ($d = 0; $d < $daysInPeriod; $d++) {
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
                if ($assignedDays == 0) {
                    $status = $leaveDays == $daysInPeriod ? 'Nghỉ phép hết kỳ' : "Chưa xếp lịch ($leaveDays ngày phép)";
                } else {
                    $status = "Đã xếp $assignedDays / $daysInPeriod ngày";
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
