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
            $employees[$emp->id] = $emp;
        }
        $employeeIds = array_keys($employees);

        if (empty($employeeIds)) {
            return response()->json([
                'success' => true,
                'total_personnel' => 0,
                'stats' => ['on_leave' => 0, 'unassigned' => 0, 'under_8h' => 0, 'exact_8h' => 0, 'over_8h' => 0],
                'details' => [],
                'period' => ['start' => $startDate->format('Y-m-d H:i'), 'end' => $endDate->format('Y-m-d H:i'), 'days' => $daysInPeriod]
            ]);
        }

        // 2. Get assignments in period
        $assignments = DB::table('assignments as a')
            ->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')
            ->where('a.deparment_code', $production_code)
            ->where('a.active', 1)
            ->where('a.start', '<', $endDate)
            ->where('a.end', '>', $startDate)
            ->whereIn('ap.personnel_id', $employeeIds)
            ->select('ap.personnel_id', 'a.start', 'a.end')
            ->get();

        // Calculate hours per employee
        $employeeHours = [];
        foreach ($employeeIds as $id) {
            $employeeHours[$id] = 0;
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

            $hours = $aStart->diffInMinutes($aEnd) / 60;
            if (isset($employeeHours[$assignment->personnel_id])) {
                $employeeHours[$assignment->personnel_id] += $hours;
            }
        }

        // --- Fetch Shifts to determine Leave (P) ---
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
        $leaveEmployees = [];

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
                        for ($d = 0; $d < $daysInPeriod; $d++) {
                            $currentDay = $startDate->copy()->addDays($d);
                            if ($currentDay->month == $month) {
                                $dayKey = 'day' . $currentDay->day;
                                if (isset($person['days'][$dayKey]) && trim($person['days'][$dayKey]) === 'P') {
                                    $pCount++;
                                }
                            }
                        }

                        if ($pCount > 0) {
                            foreach ($employees as $empId => $emp) {
                                if ($emp->code == $code) {
                                    $leaveEmployees[$empId] = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }

        // 3. Classify personnel
        $stats = [
            'on_leave' => 0,
            'unassigned' => 0,
            'under_8h' => 0,
            'exact_8h' => 0,
            'over_8h' => 0,
        ];

        $details = [];

        foreach ($employeeHours as $empId => $totalHours) {
            $avgHoursPerDay = $daysInPeriod > 0 ? ($totalHours / $daysInPeriod) : 0;

            $status = '';
            // Allow small floating point rounding
            if ($totalHours == 0) {
                if (isset($leaveEmployees[$empId])) {
                    $stats['on_leave']++;
                    $status = 'Nghỉ phép (P)';
                } else {
                    $stats['unassigned']++;
                    $status = 'Chưa phân công';
                }
            } elseif ($avgHoursPerDay < 7.9) {
                $stats['under_8h']++;
                $status = '< 8h';
            } elseif ($avgHoursPerDay <= 8.1) {
                $stats['exact_8h']++;
                $status = 'Đủ 8h';
            } else {
                $stats['over_8h']++;
                $status = '> 8h';
            }

            $details[] = [
                'code' => $employees[$empId]->code,
                'name' => $employees[$empId]->name,
                'group' => $employees[$empId]->group_names,
                'total_hours' => round($totalHours, 2),
                'avg_hours_per_day' => round($avgHoursPerDay, 2),
                'status' => $status
            ];
        }

        // Sort details by total_hours ascending
        usort($details, function ($a, $b) {
            return $a['total_hours'] <=> $b['total_hours'];
        });

        // 4. Lấy danh sách tất cả các tổ khả dụng trong phân xưởng này (không bị ảnh hưởng bởi filter group_id)
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
            'stats' => $stats,
            'details' => $details,
            'available_groups' => $availableGroupsArray,
            'period' => [
                'start' => $startDate->format('Y-m-d H:i'),
                'end' => $endDate->format('Y-m-d H:i'),
                'days' => $daysInPeriod
            ]
        ]);
    }
}
