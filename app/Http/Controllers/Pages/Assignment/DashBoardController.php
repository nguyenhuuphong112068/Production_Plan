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
            'WH'   => 'Kho'
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
            ->leftJoin('stage_groups as sg', 'ea.group_id', '=', 'sg.code')
            ->where('ea.production_code', $production_code)
            ->where('ea.active', 1);

        if ($group_id) {
            $personnelQuery->where('ea.group_id', $group_id);
        }

        $personnelList = $personnelQuery
            ->select('e.id', 'e.code', 'e.name', DB::raw('GROUP_CONCAT(DISTINCT sg.name SEPARATOR ", ") as group_names'))
            ->groupBy('e.id', 'e.code', 'e.name')
            ->get();
            
        $employees = [];
        foreach ($personnelList as $emp) {
            $employees[$emp->id] = $emp;
        }
        $employeeIds = array_keys($employees);

        if (empty($employeeIds)) {
            return response()->json([
                'success' => true,
                'total_personnel' => 0,
                'stats' => ['unassigned' => 0, 'under_8h' => 0, 'exact_8h' => 0, 'over_8h' => 0],
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

        // 3. Classify personnel
        $stats = [
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
                $stats['unassigned']++;
                $status = 'Chưa phân công';
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
        usort($details, function($a, $b) {
            return $a['total_hours'] <=> $b['total_hours'];
        });

        // 4. Lấy danh sách tất cả các tổ khả dụng trong phân xưởng này (không bị ảnh hưởng bởi filter group_id)
        $availableGroups = DB::table('employee_assignments as ea')
            ->join('employees as e', 'ea.employees_id', '=', 'e.id')
            ->where('e.active', 1)
            ->where(function ($q) {
                $q->whereNull('e.resign')->orWhere('e.resign', 0);
            })
            ->where('ea.production_code', $production_code)
            ->where('ea.active', 1)
            ->whereNotNull('ea.group_id')
            ->join('stage_groups as sg', 'ea.group_id', '=', 'sg.code')
            ->select('sg.code', 'sg.name')
            ->distinct()
            ->orderBy('sg.name')
            ->get();

        return response()->json([
            'success' => true,
            'total_personnel' => count($employees),
            'stats' => $stats,
            'details' => $details,
            'available_groups' => $availableGroups,
            'period' => [
                'start' => $startDate->format('Y-m-d H:i'),
                'end' => $endDate->format('Y-m-d H:i'),
                'days' => $daysInPeriod
            ]
        ]);
    }
}
