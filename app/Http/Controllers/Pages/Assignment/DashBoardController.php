<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    public function getData(Request $request)
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
            ->select('e.id', 'e.code', 'e.name', DB::raw('GROUP_CONCAT(DISTINCT ea.group_id SEPARATOR ",") as group_ids'))
            ->groupBy('e.id', 'e.code', 'e.name')
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
            9 => "VSCN + Kho BTP"
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
            ->where('a.start', '<', $endDate)
            ->where('a.end', '>', $startDate)
            ->whereIn('ap.personnel_id', $employeeIds)
            ->select('ap.personnel_id', 'a.start', 'a.end', 'r.name as room_name', 'r.id as room_id', 'a.work_location', 'a.Sheet')
            ->get();

        // Calculate hours per employee and room
        $employeeDailyHours = [];
        $employeeDailyLeave = [];
        $roomHoursMap = []; // [room_name => total_hours]
        $empCodeToId = [];
        foreach ($employeeIds as $id) {
            $employeeDailyHours[$id] = array_fill(0, $daysInPeriod, 0);
            $employeeDailyLeave[$id] = array_fill(0, $daysInPeriod, false);
            $empCodeToId[$employees[$id]->code] = $id;
        }

        foreach ($assignments as $assignment) {
            $aStart = Carbon::parse($assignment->start);
            $aEnd = Carbon::parse($assignment->end);

            // Limit to the selected period
            if ($aStart->lt($startDate)) {
                $aStart = $startDate->copy();
            }
            if ($aEnd->gt($endDate)) {
                $aEnd = $endDate->copy();
            }

            $isNoLunchBreakShift = false;
            // Sheet: 1=C1, 2=C2, 3=C3, 6=C4. Chỉ trừ nghỉ trưa cho 4=HC, 5=Khác
            if (in_array($assignment->Sheet, [1, 2, 3, 6])) {
                $isNoLunchBreakShift = true;
            }

            for ($d = 0; $d < $daysInPeriod; $d++) {
                $dayStart = $startDate->copy()->addDays($d);
                $dayEnd = $dayStart->copy()->addDays(1);

                $overlapStart = $aStart->copy()->max($dayStart);
                $overlapEnd = $aEnd->copy()->min($dayEnd);

                if ($overlapStart->lt($overlapEnd)) {
                    $durationMin = $overlapStart->diffInMinutes($overlapEnd);

                    if (!$isNoLunchBreakShift) {
                        $lunchStart = $dayStart->copy()->setTime(11, 30, 0);
                        $lunchEnd = $dayStart->copy()->setTime(12, 15, 0);

                        $lOverlapStart = $overlapStart->copy()->max($lunchStart);
                        $lOverlapEnd = $overlapEnd->copy()->min($lunchEnd);

                        if ($lOverlapStart->lt($lOverlapEnd)) {
                            $durationMin -= $lOverlapStart->diffInMinutes($lOverlapEnd);
                        }
                    }

                    $hours = $durationMin / 60;
                    if (isset($employeeDailyHours[$assignment->personnel_id])) {
                        $employeeDailyHours[$assignment->personnel_id][$d] += $hours;
                    }
                }
            }

            $roomName = $assignment->room_name ?? $assignment->work_location ?? 'Khác';
            if (!isset($roomHoursMap[$roomName])) {
                $roomHoursMap[$roomName] = 0;
            }
            $roomHoursMap[$roomName] += $hours;
        }

        // --- Fetch Shifts to determine Leave (P) AND collect overtime ---
        $month = $startDate->format('m');
        $year = $startDate->format('Y');

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
            $url = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department={$departmentId}";
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 3]]);
                $data = @file_get_contents($url, false, $ctx);
                if ($data) {
                    $personnelData = json_decode($data, true) ?: [];

                    if ($departmentId == 15) {
                        try {
                            $url17 = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department=17";
                            $data17 = @file_get_contents($url17, false, $ctx);
                            if ($data17) {
                                $personnelData17 = json_decode($data17, true) ?: [];
                                if (is_array($personnelData17)) {
                                    $personnelData = array_merge($personnelData, $personnelData17);
                                }
                            }
                        } catch (\Exception $ex) {
                        }
                    }

                    foreach ($personnelData as $person) {
                        $code = $person['employeeId'] ?? $person['code'] ?? null;
                        if (!$code) continue;

                        $pCount = 0;
                        $totalOT = 0;
                        $shifts = [];
                        $totalEoffice = 0;

                        for ($d = 0; $d < $daysInPeriod; $d++) {
                            $currentDay = $startDate->copy()->addDays($d);
                            if ($currentDay->month == $month) {
                                $dayKey = 'day' . $currentDay->day;
                                $dayData = $person['days'][$dayKey] ?? null;

                                // Hỗ trợ cả cấu trúc API cũ (string) và mới (object)
                                if (is_array($dayData)) {
                                    $shiftCode = strtoupper(trim($dayData['shift'] ?? ''));
                                    $ot = floatval($dayData['overtime'] ?? 0);
                                    $eoffice = floatval($dayData['regular_working_Hours'] ?? 0);
                                } else {
                                    $shiftCode = strtoupper(trim($dayData ?? ''));
                                    $ot = 0;
                                    $eoffice = 0; // Cũ không có giờ làm việc e-office
                                }

                                // Reset regular working hours nếu rơi vào ngày nghỉ (off-date)
                                $dayStr = $currentDay->format('Y-m-d');
                                if (isset($offDatesMap[$dayStr])) {
                                    $eoffice = 0;
                                }

                                if ($shiftCode === 'P') {
                                    $pCount++;
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
                        }

                        if (isset($employeeOvertimeHours[$code])) {
                            $employeeOvertimeHours[$code] += $totalOT;
                            $employeeRegisteredShifts[$code] = array_merge($employeeRegisteredShifts[$code], $shifts);
                            $employeeEofficeHours[$code] += $totalEoffice;
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }

        $stats_laps = [
            'on_leave' => 0,
            'unassigned' => 0,
            'under_8h' => 0,
            'exact_8h' => 0,
            'over_8h' => 0,
            'total_ot_hours' => 0,
        ];

        $stats_people = [
            'on_leave' => 0,
            'unassigned' => 0,
            'under_8h' => 0,
            'exact_8h' => 0,
            'over_8h' => 0,
            'total_ot_hours' => 0,
        ];

        $details = [];
        $groupOvertimeMap = []; // [group_name => total_ot]

        $stats_daily = [];
        for ($d = 0; $d < $daysInPeriod; $d++) {
            $stats_daily[$d] = [
                'date' => $startDate->copy()->addDays($d)->format('d/m/Y'),
                'on_leave' => 0,
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
                    $stats_daily[$d]['total_ot_hours'] += $employeeDailyOT[$empCode][$d];
                }
            }

            $totalHours = array_sum($dailyHours);
            $avgHoursPerDay = $daysInPeriod > 0 ? ($totalHours / $daysInPeriod) : 0;
            
            $assignedDays = 0;
            $leaveDays = 0;

            for ($d = 0; $d < $daysInPeriod; $d++) {
                $h = $dailyHours[$d];
                if ($h == 0) {
                    if (!empty($employeeDailyLeave[$empId][$d])) {
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
            if ($totalHours == 0) {
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

            if ($daysInPeriod == 1) {
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
                $groupOvertimeMap[$groupName] = ['name' => $groupName, 'ot_hours' => 0, 'count' => 0];
            }
            $groupOvertimeMap[$groupName]['ot_hours'] += $empOT;
            $groupOvertimeMap[$groupName]['count']++;
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
                return ['name' => $g['name'], 'ot_hours' => round($g['ot_hours'], 2), 'count' => $g['count']];
            }, $groupOvertimeMap),
            fn($g) => $g['ot_hours'] > 0 || $g['count'] > 0
        ));
        usort($overtimeByGroup, fn($a, $b) => $b['ot_hours'] <=> $a['ot_hours']);

        // Format overtime by room (from assignment data)
        arsort($roomHoursMap);
        $overtimeByRoom = [];
        foreach ($roomHoursMap as $rName => $rHours) {
            $overtimeByRoom[] = ['name' => $rName, 'total_hours' => round($rHours, 2)];
        }

        // 4. Lấy danh sách tất cả các tổ khả dụng trong phân xưởng này
        $availableGroupsRaw = DB::table('employee_assignments as ea')
            ->join('employees as e', 'ea.employees_id', '=', 'e.id')
            ->where('e.active', 1)
            ->where(function ($q) {
                $q->whereNull('e.resign')->orWhere('e.resign', 0);
            })
            ->where('ea.production_code', $production_code)
            ->where('ea.active', 1)
            ->whereNotNull('ea.group_id')
            ->select('ea.group_id as code')
            ->distinct()
            ->get();

        $availableGroupsArray = [];
        foreach ($availableGroupsRaw as $g) {
            $code = $g->code;
            if ($isENorQA) {
                $name = $dbGroups[$code] ?? 'NA';
            } else {
                $name = $hardcodedGroups[$code] ?? 'NA';
            }
            if ($name !== 'NA') {
                $availableGroupsArray[] = ['code' => $code, 'name' => $name];
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


