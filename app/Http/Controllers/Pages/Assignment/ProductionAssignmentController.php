<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Pages\Report\DailyReportController;
use Illuminate\Support\Facades\Log;

class ProductionAssignmentController extends Controller
{
    public function index(Request $request)
    {

        $production_code = session('user')['production_code'];
        $user_group_name = session('user')['group_name'];
        $reportedDate = $request->reportedDate ?? Carbon::now()->format('Y-m-d');

        $startDate = Carbon::parse($reportedDate)->setTime(6, 0, 0);
        $endDate = $startDate->copy()->addDays(1);

        // 1. Lấy danh sách các tổ có trong bộ phận
        // $groups = DB::table('room')
        //     ->where('deparment_code', $production_code)
        //     ->where('stage_code', '!=', 8)
        //     ->whereNotNull('group_code')
        //     ->select('group_code', 'production_group')
        //     ->distinct()
        //     ->orderBy('group_code')
        //     ->get();

        // dd($groups);


        $groups = collect([
            1 => "Trung Tâm Cân",
            3 => "Pha Chế",
            4 => "Văn Phòng",
            5 => "Định Hình",
            6 => "Bao Phim",
            7 => "ĐGSC",
            8 => "ĐGTC",
            9 => "VSCN + Kho BTP"
        ])->map(function ($name, $id) {
            return (object) ['group_code' => $id, 'production_group' => $name];
        })->values();



        // 2. Logic khóa tổ theo tên (group_name):
        $isLocked = false;
        $active_group_code = $request->group_code;

        // Tìm xem group_name của user có khớp với tổ nào trong bộ phận này không
        if ($user_group_name) {
            $matchedGroup = $groups->first(function ($g) use ($user_group_name) {
                return trim($g->production_group) == trim($user_group_name);
            });

            if ($matchedGroup) {
                $active_group_code = $matchedGroup->group_code;
                $isLocked = true;
            }
        }

        // 3. Lấy danh sách phòng (có lọc theo tổ)
        $roomQuery = DB::table('room')
            ->where('deparment_code', $production_code)
            ->where('only_maintenance', 0);

        if ($active_group_code) {
            if ($active_group_code == 7 || $active_group_code == 8) {
                $roomQuery->whereIn('group_code', [7, 8]);
            } else {
                $roomQuery->where('group_code', $active_group_code);
            }
        }

        $rooms = $roomQuery->orderBy('group_code')->orderBy('order_by')->get();

        // 4. Lấy dữ liệu công việc lý thuyết trong ngày
        $stagePlanQuery = DB::table('stage_plan as sp')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('product_name', 'fpc.product_name_id', 'product_name.id')
            ->where('sp.deparment_code', $production_code)
            ->where('pl.type', 1);

        if ($rooms->isNotEmpty()) {
            $stagePlanQuery->whereIn('sp.resourceId', $rooms->pluck('id'));
        }

        $stagePlans = $stagePlanQuery->where('sp.active', 1)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($q1) use ($startDate, $endDate) {
                    $q1->where('sp.start', '<', $endDate)
                       ->where('sp.end', '>', $startDate);
                })->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->whereNotNull('sp.start_clearning')
                       ->where('sp.start_clearning', '<', $endDate)
                       ->where('sp.end_clearning', '>', $startDate);
                });
            })
            ->select(
                'sp.id',
                'sp.resourceId as room_id',
                'sp.start',
                'sp.end',
                'sp.start_clearning',
                'sp.end_clearning',
                'sp.title',
                'sp.title_clearning',
                'sp.stage_code',
                'pm.batch',
                'product_name.name as product_name'
            )
            ->get()
            ->groupBy('room_id');

        // 5. Lấy dữ liệu đã phân công (Assignments)
        $assignmentQuery = DB::table('assignments as a')
            ->leftJoin('user_management as u', 'a.assigned_by', '=', 'u.userName')
            ->select('a.*', 'u.fullname as assigner_name')
            ->where('a.deparment_code', $production_code)
            ->whereDate('a.start', $reportedDate)
            ->where('a.active', 1);

        if ($active_group_code) {
            if ($active_group_code == 7 || $active_group_code == 8) {
                $assignmentQuery->whereIn('a.stage_groups_code', [7, 8]);
            } else {
                $assignmentQuery->where('a.stage_groups_code', $active_group_code);
            }
        }

        $allAssignments = $assignmentQuery->get()->groupBy('room_id');

        // Pre-fetch tất cả assignment_personnel để tránh N+1 Query
        $allAssignmentIds = $allAssignments->flatten()->pluck('id')->filter()->toArray();
        $allPersonnelData = collect();
        if (!empty($allAssignmentIds)) {
            $allPersonnelData = DB::table('assignment_personnel')
                ->whereIn('assignment_id', $allAssignmentIds)
                ->select('assignment_id', 'personnel_id', 'notification', 'operation_type', 'start', 'end', 'display_order')
                ->orderBy('display_order', 'asc')
                ->get()
                ->groupBy('assignment_id');
        }

        // 6. Lấy dữ liệu báo cáo hoạt động thực tế (Actual Detail) từ DailyReportController
        //$dailyReportController = app(DailyReportController::class);
        // $reportData = $dailyReportController->yield_actual_detial($startDate, $endDate, 'resourceId');
        // $actualDetails = collect($reportData['actual_detail'])->groupBy('resourceId');

        // 7. Tổ chức lại dữ liệu theo từng phòng
        $tasks = $rooms->map(function ($room) use ($stagePlans, $allAssignments, $reportedDate, $active_group_code, $allPersonnelData, $startDate, $endDate) {
            $plans = $stagePlans->get($room->id) ?? collect();
            $assignments = $allAssignments->get($room->id) ?? collect();

            // Tạo chuỗi hiển thị lịch lý thuyết (Theory Display)
            $theoryDisplay = '';
            $spIds = [];
            
            $displayItems = [];
            foreach ($plans as $p) {
                // Production event
                if ($p->start && $p->end) {
                    if ($p->start < $endDate && $p->end > $startDate) {
                        $isCleaning = (stripos($p->title, 'vệ sinh') !== false || stripos($p->title, 'VS-') !== false || stripos($p->title, 'VS ') !== false);
                        if ($isCleaning) {
                            $cleanTitle = 'Vệ sinh';
                            if (stripos($p->title, 'cấp 2') !== false || stripos($p->title, 'cấp II') !== false || stripos($p->title_clearning, 'VS-II') !== false) {
                                $cleanTitle = 'Vệ sinh cấp II';
                            } elseif (stripos($p->title, 'cấp 1') !== false || stripos($p->title, 'cấp I') !== false || stripos($p->title_clearning, 'VS-I') !== false) {
                                $cleanTitle = 'Vệ sinh cấp I';
                            }
                            if ($p->product_name && $p->product_name !== 'NA') {
                                $displayText = "{$cleanTitle} ({$p->product_name} - {$p->batch})";
                            } else {
                                $displayText = $cleanTitle;
                            }
                        } else {
                            $displayText = $p->product_name ? "{$p->product_name} - {$p->batch}" : strip_tags($p->title);
                        }
                        
                        $displayItems[] = [
                            'sp_id' => $p->id,
                            'start' => $p->start,
                            'end' => $p->end,
                            'text' => $displayText
                        ];
                    }
                }
                
                // Cleaning event
                if ($p->start_clearning && $p->end_clearning) {
                    if ($p->start_clearning < $endDate && $p->end_clearning > $startDate) {
                        $cleanTitle = 'Vệ sinh';
                        if ($p->title_clearning == 'VS-II') $cleanTitle = 'Vệ sinh cấp II';
                        elseif ($p->title_clearning == 'VS-I') $cleanTitle = 'Vệ sinh cấp I';
                        elseif ($p->title_clearning == 'VS') $cleanTitle = 'Vệ sinh';
                        
                        $productPart = $p->product_name ? "{$p->product_name} - {$p->batch}" : strip_tags($p->title);
                        $displayText = "{$cleanTitle} ({$productPart})";
                        
                        $displayItems[] = [
                            'sp_id' => $p->id,
                            'start' => $p->start_clearning,
                            'end' => $p->end_clearning,
                            'text' => $displayText
                        ];
                    }
                }
            }

            usort($displayItems, function($a, $b) {
                return strtotime($a['start']) <=> strtotime($b['start']);
            });

            foreach ($displayItems as $index => $item) {
                $stt = $index + 1;
                if (!in_array($item['sp_id'], $spIds)) {
                    $spIds[] = $item['sp_id'];
                }
                $timeDisp = Carbon::parse($item['start'])->format('H:i') . '-' . Carbon::parse($item['end'])->format('H:i');
                
                $theoryDisplay .= "<div class='plan-item mb-1 pb-1 border-bottom position-relative hover-show-btn' data-start='{$item['start']}'><div class='plan-text' style='font-size: 0.8rem; line-height: 1.2;'><b>{$stt}. {$item['text']} <span class='time-text'>| ({$timeDisp})</span></b></div><button class='btn btn-xs btn-primary btn-copy-plan' title='Chép mục này' style='position: absolute; right: 0; top: 0; padding: 0 4px; font-size: 10px; display: none;'> >></button></div>";
            }
            if ($theoryDisplay == '') {
                $theoryDisplay = '<span class="text-muted italic">Không có lịch</span>';
                $spIdString = '';
            } else {
                sort($spIds);
                $spIdString = implode(',', $spIds);
            }

            foreach ($assignments as $a) {
                $a->is_foreign = ($active_group_code && $a->stage_groups_code != $active_group_code);
                $a->is_scheduled = !empty($a->stage_plan_id);
                $a->personnel_data = $allPersonnelData->get($a->id) ?? collect();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
            }

            // Lọc ra các assignment của chính tổ này (bỏ foreign)
            $localAssignments = $assignments->filter(function($a) {
                return !$a->is_foreign;
            });
            $foreignAssignments = $assignments->filter(function($a) {
                return $a->is_foreign;
            });

            // Tự động tạo gợi ý nếu chưa có phân công cho tổ này
            if ($localAssignments->isEmpty()) {
                if ($foreignAssignments->isNotEmpty()) {
                    foreach ($foreignAssignments as $fa) {
                        $assignments->push((object)[
                            'id' => null,
                            'Sheet' => $fa->Sheet,
                            'start' => $fa->start,
                            'end' => $fa->end,
                            'Job_description' => $fa->Job_description,
                            'number_of_employes' => 1,
                            'Num_of_per_Level_3' => 0,
                            'personnel_data' => collect([(object)['personnel_id' => null, 'notification' => null, 'start' => null, 'end' => null, 'operation_type' => null]]),
                            'start_time_display' => $fa->start_time_display,
                            'end_time_display' => $fa->end_time_display,
                            'is_foreign' => false,
                            'is_scheduled' => false
                        ]);
                    }
                    $assignments = $assignments->sortBy('start');
                } elseif ($plans->isNotEmpty()) {
                    $dayStart = Carbon::parse($reportedDate)->setTime(6, 0, 0);
                $shiftItems = ['1' => [], '2' => [], '3' => []];

                foreach ($displayItems as $item) {
                    $pStart = Carbon::parse($item['start']);
                    $pEnd = Carbon::parse($item['end']);
                    $displayText = $item['text'];

                    $s1S = $dayStart->copy();
                    $s1E = $dayStart->copy()->addHours(8);
                    if ($pStart->lt($s1E) && $pEnd->gt($s1S)) $shiftItems['1'][] = $displayText;
                    
                    $s2S = $s1E->copy();
                    $s2E = $s2S->copy()->addHours(8);
                    if ($pStart->lt($s2E) && $pEnd->gt($s2S)) $shiftItems['2'][] = $displayText;
                    
                    $s3S = $s2E->copy();
                    $s3E = $s3S->copy()->addHours(8);
                    if ($pStart->lt($s3E) && $pEnd->gt($s3S)) $shiftItems['3'][] = $displayText;
                }

                foreach ($shiftItems as $code => $items) {
                    if (empty($items)) continue;
                    $unique_items = array_values(array_unique($items));
                    $jobDescription = "";
                    foreach ($unique_items as $idx => $item) {
                        $jobDescription .= ($idx + 1) . ". " . $item . "\n";
                    }
                    $sTime = $dayStart->copy()->addHours(($code - 1) * 8);
                    $eTime = $sTime->copy()->addHours(8);
                    $shiftCode = $code;
                    $roomCol = 'number_of_employes_on_sheet' . $shiftCode;
                    if ($shiftCode == '4') $roomCol = 'number_of_employes_on_sheet_regular';
                    if ($shiftCode == '6') $roomCol = 'number_of_employes_on_sheet4';
                    $suggestedCount = 1;

                    $assignments->push((object)[
                        'id' => null,
                        'Sheet' => $code,
                        'start' => $sTime->toDateTimeString(),
                        'end' => $eTime->toDateTimeString(),
                        'Job_description' => trim($jobDescription),
                        'number_of_employes' => $suggestedCount,
                        'Num_of_per_Level_3' => 1,
                        'personnel_data' => collect([(object)['personnel_id' => null, 'notification' => null, 'start' => null, 'end' => null, 'operation_type' => null]]),
                        'start_time_display' => $sTime->format('H:i'),
                        'end_time_display' => $eTime->format('H:i'),
                        'is_foreign' => false,
                        'is_scheduled' => true
                    ]);
                }
                $assignments = $assignments->sortBy('start');
            }
        }

        return (object)[
                'sp_id' => $spIdString,
                'room_id' => $room->id,
                'group_code' => $room->group_code,
                'room_code' => $room->code,
                'room_name' => $room->name,
                'main_equiment_name' => $room->main_equiment_name,
                'theory_display' => $theoryDisplay,
                'assignments' => $assignments,
                'number_of_employes_on_sheet1' => $room->number_of_employes_on_sheet1,
                'number_of_employes_on_sheet2' => $room->number_of_employes_on_sheet2,
                'number_of_employes_on_sheet3' => $room->number_of_employes_on_sheet3,
                'number_of_employes_on_sheet4' => $room->number_of_employes_on_sheet4,
                'number_of_employes_on_sheet_regular' => $room->number_of_employes_on_sheet_regular,
                'theory_start' => '07:15',
                'theory_end' => '16:00',
            ];
        });

        // 7.5 Thêm các công việc ngoài lịch không có phòng
        $noRoomAssignments = $allAssignments->get("") ?? collect();
        if ($noRoomAssignments->isNotEmpty()) {
            // Nhóm theo sp_id (EXT_...) để gộp các ca của cùng 1 công việc tùy chỉnh
            $noRoomGroups = $noRoomAssignments->groupBy('stage_plan_id');
            foreach ($noRoomGroups as $spId => $groupAssignments) {
                foreach ($groupAssignments as $a) {
                    $a->is_foreign = ($active_group_code && $a->stage_groups_code != $active_group_code);
                    $a->is_scheduled = !empty($a->stage_plan_id);
                    $a->personnel_data = $allPersonnelData->get($a->id) ?? collect();
                    $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                    $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
                }

                $tasks->push((object)[
                    'sp_id' => $spId,
                    'room_id' => null,
                    'group_code' => $active_group_code,
                    'room_code' => 'NA',
                    'room_name' => $groupAssignments->first()->work_location ?? 'Công tác khác',
                    'main_equiment_name' => null,
                    'theory_display' => '<span class="text-danger font-weight-bold">NA</span>',
                    'assignments' => $groupAssignments->sortBy('start'),
                    'number_of_employes_on_sheet1' => 0,
                    'number_of_employes_on_sheet2' => 0,
                    'number_of_employes_on_sheet3' => 0,
                    'number_of_employes_on_sheet4' => 0,
                    'number_of_employes_on_sheet_regular' => 0,
                    'theory_start' => '07:15',
                    'theory_end' => '16:00',
                ]);
            }
        }

        $personnelQuery = DB::table('employees as e')
            ->where('e.active', 1)
            ->where(function ($q) {
                $q->whereNull('e.resign')
                    ->orWhere('e.resign', 0);
            })
            ->whereExists(function ($query) use ($production_code) {
                $query->select(DB::raw(1))
                    ->from('employee_assignments as ea')
                    ->whereColumn('ea.employees_id', 'e.id')
                    ->where('ea.production_code', $production_code)
                    ->where('ea.active', 1);
            });

        if ($active_group_code && $active_group_code != 'HC') {
            $personnelQuery->whereExists(function ($query) use ($active_group_code) {
                $query->select(DB::raw(1))
                    ->from('employee_assignments as ea2')
                    ->leftJoin('stage_groups as sg', 'ea2.group_id', '=', 'sg.id')
                    ->whereColumn('ea2.employees_id', 'e.id')
                    ->where(function ($q) use ($active_group_code) {
                        $q->where('sg.code', $active_group_code)
                            ->orWhere('ea2.group_id', $active_group_code);
                    })
                    ->where('ea2.active', 1);
            });
        }

        $personnel = $personnelQuery->select('e.*')
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(room_id, ':', level, ':', COALESCE(priority_level, 1)) SEPARATOR '|') FROM employee_assignments WHERE employees_id = e.id AND active = 1 AND room_id IS NOT NULL AND room_id > 0) as allowed_rooms_with_levels"))
            ->orderBy('e.name')
            ->get();

        // Tạo mapping skills từ danh sách personnel đã lấy
        $skills = $personnel->keyBy('id');

        // Tạo danh sách ID nhân sự được phép để lọc sidebar ở client
        $allowedPersonnelCodes = $personnel->pluck('code')->toArray();

        session()->put(['title' => 'LỊCH CÔNG TÁC SẢN XUẤT']);

        $allRooms = DB::table('room')
            ->where('deparment_code', $production_code)
            ->orderBy('group_code')
            ->orderBy('order_by')
            ->get();

        $dbAssignments = $this->getDbAssignments($reportedDate);

        $suggestions = DB::table('assignment_suggestions')
            ->where('target_date', $reportedDate)
            ->where('deparment_code', $production_code)
            ->get();

        $isOvertimeApproved = DB::table('overtime_approvals')
            ->where('reported_date', $reportedDate)
            ->where('production_code', $production_code)
            ->where('group_code', $active_group_code ?? '')
            ->exists();

        return view('pages.assignment.production.index', [
            'tasks' => $tasks,
            'reportedDate' => $reportedDate,
            'group_code' => $active_group_code,
            'groups' => $groups,
            'isLocked' => $isLocked,
            'personnel' => $personnel,
            'skills' => $skills, // Truyền dữ liệu bậc kỹ năng
            'allowedPersonnelCodes' => $allowedPersonnelCodes,
            'rooms' => $rooms,
            'allRooms' => $allRooms,
            'dbAssignments' => $dbAssignments,
            'suggestions' => $suggestions,
            'isOvertimeApproved' => $isOvertimeApproved
        ]);
    }

    public function approveOvertime(Request $request)
    {
        $reportedDate = $request->reportedDate;
        $production_code = $request->production_code;
        $group_code = $request->group_code ?? '';

        if (!$reportedDate || !$production_code) {
            return response()->json(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
        }

        DB::table('overtime_approvals')->updateOrInsert(
            [
                'reported_date' => Carbon::parse($reportedDate)->format('Y-m-d'),
                'production_code' => $production_code,
                'group_code' => $group_code,
            ],
            [
                'approved_by' => session('user')['fullName'] ?? 'System',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function getPersonnelShifts(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $departmentId = $request->department;

        $url = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department={$departmentId}";

        try {
            $data = file_get_contents($url);
            $personnelData = json_decode($data, true) ?: [];

            if ($departmentId == 15) {
                try {
                    $url17 = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department=17";
                    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                    $data17 = @file_get_contents($url17, false, $ctx);
                    if ($data17) {
                        $personnelData17 = json_decode($data17, true) ?: [];
                        if (is_array($personnelData17)) {
                            foreach ($personnelData17 as &$p17) {
                                if (isset($p17['employeeName'])) {
                                    $p17['employeeName'] = trim($p17['employeeName']) . ' - WH';
                                }
                            }
                            $personnelData = array_merge($personnelData, $personnelData17);
                        }
                    }
                } catch (\Exception $ex17) {
                }
            }

            if (is_array($personnelData)) {
                // Filter out resigned employees
                $resignedCodes = DB::table('employees')->where('resign', 1)->pluck('code')->toArray();
                $resignedCodesSet = array_flip($resignedCodes);
                $filteredPersonnelData = [];
                foreach ($personnelData as $person) {
                    $code = $person['employeeId'] ?? $person['code'] ?? null;
                    if ($code && isset($resignedCodesSet[$code])) {
                        continue;
                    }
                    $filteredPersonnelData[] = $person;
                }
                $personnelData = $filteredPersonnelData;

                // Lấy dữ liệu đi ca của tháng tiếp theo (month + 1) để điền cho day21 - day31
                $nextMonth = intval($month) + 1;
                $nextYear = intval($year);
                if ($nextMonth > 12) {
                    $nextMonth = 1;
                    $nextYear += 1;
                }

                $urlNext = "http://s-webdev:5070/api/shifts/by-department?month={$nextMonth}&year={$nextYear}&department={$departmentId}";
                $personnelDataNext = [];
                try {
                    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                    $dataNext = @file_get_contents($urlNext, false, $ctx);
                    if ($dataNext) {
                        $personnelDataNext = json_decode($dataNext, true) ?: [];
                    }
                } catch (\Exception $exNext) {
                    // Bỏ qua lỗi lấy dữ liệu tháng tiếp theo nếu chưa có lịch
                }

                if ($departmentId == 15) {
                    try {
                        $urlNext17 = "http://s-webdev:5070/api/shifts/by-department?month={$nextMonth}&year={$nextYear}&department=17";
                        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                        $dataNext17 = @file_get_contents($urlNext17, false, $ctx);
                        if ($dataNext17) {
                            $personnelDataNext17 = json_decode($dataNext17, true) ?: [];
                            if (is_array($personnelDataNext17)) {
                                foreach ($personnelDataNext17 as &$p17) {
                                    if (isset($p17['employeeName'])) {
                                        $p17['employeeName'] = trim($p17['employeeName']) . ' - WH';
                                    }
                                }
                                $personnelDataNext = array_merge($personnelDataNext, $personnelDataNext17);
                            }
                        }
                    } catch (\Exception $exNext17) {
                    }
                }

                // Index nhân sự tháng tiếp theo theo employeeId / code
                $nextMonthEmployees = [];
                foreach ($personnelDataNext as $person) {
                    $code = $person['employeeId'] ?? $person['code'] ?? null;
                    if ($code) {
                        $nextMonthEmployees[$code] = $person;
                    }
                }

                // Lấy thông tin hasAssignment từ bảng employees local
                $localEmployees = DB::table('employees')->select('code', 'hasAssignment', 'on_maternity_leave')->get()->keyBy('code');

                foreach ($personnelData as &$person) {
                    $code = $person['employeeId'] ?? $person['code'] ?? null;

                    // Ghép logic: day1-day20 từ tháng $month, day21-day31 từ tháng $month+1
                    $originalDays = $person['days'] ?? [];
                    $newDays = [];

                    for ($i = 1; $i <= 20; $i++) {
                        $dayKey = 'day' . $i;
                        $newDays[$dayKey] = $originalDays[$dayKey] ?? null;
                    }

                    if ($code && isset($nextMonthEmployees[$code])) {
                        $nextPersonDays = $nextMonthEmployees[$code]['days'] ?? [];
                        for ($i = 21; $i <= 31; $i++) {
                            $dayKey = 'day' . $i;
                            $newDays[$dayKey] = $nextPersonDays[$dayKey] ?? null;
                        }
                    } else {
                        // Fallback: nếu không lấy được tháng tiếp theo thì giữ nguyên dữ liệu gốc
                        for ($i = 21; $i <= 31; $i++) {
                            $dayKey = 'day' . $i;
                            $newDays[$dayKey] = $originalDays[$dayKey] ?? null;
                        }
                    }

                    $person['days'] = $newDays;

                    // Gán hasAssignment
                    if ($code && isset($localEmployees[$code])) {
                        $person['hasAssignment'] = $localEmployees[$code]->hasAssignment;
                        $person['on_maternity_leave'] = $localEmployees[$code]->on_maternity_leave;
                    } else {
                        $person['hasAssignment'] = 1; // Mặc định là 1 (có sắp lịch)
                        $person['on_maternity_leave'] = 0;
                    }
                }
            }

            return response()->json($personnelData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateHasAssignment(Request $request)
    {
        try {
            $code = $request->code;
            $hasAssignment = $request->hasAssignment ? 1 : 0;

            DB::table('employees')
                ->where('code', $code)
                ->update(['hasAssignment' => $hasAssignment, 'updated_at' => now()]);

            return response()->json(['success' => true, 'message' => 'Đã cập nhật trạng thái']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        //Log::info($request->all());
        $spIdString = $request->sp_id;
        if ($spIdString === 'undefined' || $spIdString === 'null' || trim($spIdString) === '') {
            $spIdString = null;
        }
        $room_id = $request->room_id;
        $work_location = null;

        // Nếu room_id không phải số (ví dụ: text tự do), ta gán cho work_location và set room_id null
        if ($room_id !== "" && !is_numeric($room_id)) {
            $work_location = $room_id;
            $room_id = null;
        } else if ($room_id === "") {
            $room_id = null;
        }
        $reportedDate = $request->reportedDate;
        $stage_groups_code = $request->stage_groups_code;
        if (empty($stage_groups_code)) $stage_groups_code = 0;

        $assignments_data = $request->assignments ?? [];
        $production_code = $request->production_code ?? session('user')['production_code'] ?? 'PXV1';

        if (!$room_id && !($spIdString && str_starts_with($spIdString, 'EXT_'))) {
            return response()->json(['success' => false, 'message' => 'Thiếu ID phòng']);
        }

        try {
            DB::beginTransaction();

            // 1. Xóa (đánh dấu active=0) các phân công cũ
            $deleteQuery = DB::table('assignments')
                ->where('deparment_code', $production_code)
                ->whereDate('start', $reportedDate)
                ->where('active', 1);

            // Cũng xóa các gợi ý nhân sự đã được render để chúng không xuất hiện lại nếu người dùng đã xóa trên UI
            $deleteSuggestQuery = DB::table('assignment_suggestions')
                ->where('deparment_code', $production_code)
                ->whereDate('target_date', $reportedDate);

            if ($spIdString && str_starts_with($spIdString, 'EXT_') && !$room_id) {
                // Đối với công việc ngoài lịch có ID định danh riêng và không có phòng cố định
                $deleteQuery->where('stage_plan_id', $spIdString);
                if ($work_location) {
                    $deleteSuggestQuery->where('work_location', $work_location);
                } else {
                    $deleteSuggestQuery->whereRaw('1 = 0');
                }
            } else {
                // Đối với công việc theo phòng (có hoặc không có sp_id)
                if ($room_id) {
                    $deleteQuery->where('room_id', $room_id);
                    $deleteSuggestQuery->where('room_id', $room_id);
                } else if ($work_location) {
                    $deleteQuery->where('work_location', $work_location);
                    $deleteSuggestQuery->where('work_location', $work_location);
                    if ($spIdString) {
                        $deleteQuery->where('stage_plan_id', $spIdString);
                    }
                } else {
                    // Trường hợp không có cả room_id và sp_id (không nên xảy ra với EXT_ logic mới)
                    // nhưng nếu có thì xóa theo các tiêu chí khác để tránh xóa nhầm
                    return response()->json(['success' => false, 'message' => 'Không thể định danh công việc để lưu']);
                }
            }

            // Nếu có lọc theo tổ, chỉ xóa phân công của tổ đó
            if ($stage_groups_code) {
                $deleteQuery->where('stage_groups_code', $stage_groups_code);
                $deleteSuggestQuery->where('stage_groups_code', $stage_groups_code);
            }

            $deleteQuery->update(['active' => 0, 'updated_at' => now()]);
            $deleteSuggestQuery->delete();

            // 2. Thêm mới các phân công
            $prodGroups = [
                1 => "Trung Tâm Cân",
                3 => "Pha Chế",
                4 => "Văn Phòng",
                5 => "Định Hình",
                6 => "Bao Phim",
                7 => "ĐGSC",
                8 => "ĐGTC",
                9 => "VSCN + Kho BTP"
            ];

            if (!empty($assignments_data)) {
                foreach ($assignments_data as $row) {
                    $p_data = $row['personnel_list'] ?? [];

                    if (empty($row['start_time']) || empty($row['end_time'])) {
                        continue; // Bỏ qua nếu thiếu thời gian
                    }

                    $startDt = $reportedDate . ' ' . $row['start_time'];
                    $endDt = $reportedDate . ' ' . $row['end_time'];

                    // Xử lý ca đêm (kết thúc vào ngày hôm sau)
                    if ($row['end_time'] < $row['start_time']) {
                        $endDt = Carbon::parse($endDt)->addDay()->format('Y-m-d H:i:s');
                    }

                    // Kiểm tra trùng lịch của từng nhân sự
                    foreach ($p_data as $p) {
                        if (empty($p['personnel_id'])) continue;

                        $pStart = empty($p['start']) ? $startDt : Carbon::parse($reportedDate . ' ' . $p['start'])->format('Y-m-d H:i:s');
                        $pEnd = empty($p['end']) ? $endDt : Carbon::parse($reportedDate . ' ' . $p['end'])->format('Y-m-d H:i:s');
                        if ($pEnd < $pStart) {
                            $pEnd = Carbon::parse($pEnd)->addDay()->format('Y-m-d H:i:s');
                        }

                        $overlap = DB::table('assignments as a')
                            ->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')
                            ->leftJoin('room as r', 'a.room_id', '=', 'r.id')
                            ->leftJoin('stage_groups as sg', 'a.stage_groups_code', '=', 'sg.code')
                            ->leftJoin('employees as e', 'ap.personnel_id', '=', 'e.id')
                            ->where('ap.personnel_id', $p['personnel_id'])
                            ->where('a.active', 1)
                            ->whereRaw('COALESCE(ap.start, a.start) < ?', [$pEnd])
                            ->whereRaw('COALESCE(ap.end, a.end) > ?', [$pStart])
                            ->select('a.start', 'a.end', 'ap.start as p_start', 'ap.end as p_end', 'a.stage_groups_code', 'r.name as room_name', 'sg.name as group_name', 'a.deparment_code', 'e.name as employee_name')
                            ->first();

                        if ($overlap) {
                            $grpName = $overlap->group_name;
                            if ($overlap->deparment_code == 'EN') {
                                $grpName = 'Bảo trì';
                            } elseif ($overlap->deparment_code == 'QA') {
                                $grpName = 'Hiệu chuẩn';
                            } else {
                                $grpName = $prodGroups[$overlap->stage_groups_code] ?? $overlap->group_name ?? ('Tổ ' . $overlap->stage_groups_code);
                            }
                            $roomName = $overlap->room_name ?: 'Công tác khác';
                            $olStart = $overlap->p_start ?: $overlap->start;
                            $olEnd = $overlap->p_end ?: $overlap->end;
                            $timeRange = Carbon::parse($olStart)->format('H:i') . ' - ' . Carbon::parse($olEnd)->format('H:i');

                            // throw new \Exception("Nhân sự {$overlap->employee_name} đã được phân công tại {$grpName} ({$roomName}) trong khoảng thời gian {$timeRange}.");
                        }
                    }

                    $assignmentId = DB::table('assignments')->insertGetId([
                        'stage_plan_id' => $spIdString,
                        'room_id' => $room_id,
                        'work_location' => $work_location,
                        'deparment_code' => $production_code,
                        'stage_groups_code' => $stage_groups_code,
                        'Sheet' => $row['shift'],
                        'start' => $startDt,
                        'end' => $endDt,
                        'Job_description' => isset($row['job_description']) ? trim($row['job_description']) : null,
                        'number_of_employes' => $row['number_of_employes'] ?? 0,
                        'Num_of_per_Level_3' => $row['num_of_per_level_3'] ?? 0,
                        'off_stream' => $row['off_stream'] ?? 0,
                        'assigned_by' => session('user')['userName'] ?? 'System',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'active' => 1
                    ]);


                    // Lưu danh sách nhân sự
                    $unique_p_data = collect($p_data)->unique('personnel_id');
                    $displayOrder = 1;
                    foreach ($unique_p_data as $p) {
                        if (empty($p['personnel_id'])) continue;
                        
                        $pStart = empty($p['start']) ? $startDt : Carbon::parse($reportedDate . ' ' . $p['start'])->format('Y-m-d H:i:s');
                        $pEnd = empty($p['end']) ? $endDt : Carbon::parse($reportedDate . ' ' . $p['end'])->format('Y-m-d H:i:s');
                        if ($pEnd < $pStart) {
                            $pEnd = Carbon::parse($pEnd)->addDay()->format('Y-m-d H:i:s');
                        }

                        DB::table('assignment_personnel')->insert([
                            'assignment_id' => $assignmentId,
                            'personnel_id' => $p['personnel_id'],
                            'notification' => $p['notification'] ?? null,
                            'operation_type' => $p['operation_type'] ?? 'thủ công',
                            'start' => $pStart,
                            'end' => $pEnd,
                            'display_order' => $displayOrder++
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã lưu phân công']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lưu dữ liệu: ' . $e->getMessage(),
                'debug' => [
                    'room_id' => $room_id,
                    'production_code' => $production_code,
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('assignments')
                ->where('id', $id)
                ->update(['active' => 0, 'updated_at' => now()]);

            return response()->json(['success' => true, 'message' => 'Đã xóa ca này']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function cloneCustomTask(Request $request)
    {
        $room_id = $request->room_id;
        $work_location = null;
        if ($room_id !== "" && !is_numeric($room_id)) {
            $work_location = $room_id;
            $room_id = null;
        } else if ($room_id === "") {
            $room_id = null;
        }

        $stage_groups_code = $request->stage_groups_code;
        if (empty($stage_groups_code)) $stage_groups_code = 0;

        $assignments_data = $request->assignments ?? [];
        $production_code = $request->production_code ?? session('user')['production_code'] ?? 'PXV1';
        $target_dates = $request->target_dates ?? []; // Array of dates: ['2026-06-02', '2026-06-03']
        $is_suggestion = filter_var($request->is_suggestion, FILTER_VALIDATE_BOOLEAN);

        if (empty($target_dates)) {
            return response()->json(['success' => false, 'message' => 'Vui lòng chọn ít nhất 1 ngày để nhân bản.']);
        }

        try {
            DB::beginTransaction();

            $prodGroups = [
                1 => "Trung Tâm Cân",
                3 => "Pha Chế",
                4 => "Văn Phòng",
                5 => "Định Hình",
                6 => "Bao Phim",
                7 => "ĐGSC",
                8 => "ĐGTC",
                9 => "VSCN + Kho BTP"
            ];

            foreach ($target_dates as $targetDate) {
                // Generate a new EXT ID for the cloned task on this date
                $spIdString = 'EXT_CLONE_' . time() . '_' . rand(1000, 9999);

                foreach ($assignments_data as $row) {
                    $p_data = $row['personnel_list'] ?? [];

                    if (empty($row['start_time']) || empty($row['end_time'])) {
                        continue;
                    }

                    if ($is_suggestion) {
                        // Delete old suggestion for same room/shift
                        DB::table('assignment_suggestions')
                            ->where('target_date', $targetDate)
                            ->where('room_id', $room_id)
                            ->where('work_location', $work_location)
                            ->where('deparment_code', $production_code)
                            ->where('stage_groups_code', $stage_groups_code)
                            ->where('shift', $row['shift'])
                            ->delete();

                        DB::table('assignment_suggestions')->insert([
                            'target_date' => $targetDate,
                            'room_id' => $room_id,
                            'work_location' => $work_location,
                            'deparment_code' => $production_code,
                            'stage_groups_code' => $stage_groups_code,
                            'shift' => $row['shift'],
                            'start_time' => $row['start_time'],
                            'end_time' => $row['end_time'],
                            'personnel_data' => json_encode($p_data),
                            'created_by' => session('user')['userName'] ?? 'System',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        continue;
                    }

                    $startDt = $targetDate . ' ' . $row['start_time'];
                    $endDt = $targetDate . ' ' . $row['end_time'];

                    if ($row['end_time'] < $row['start_time']) {
                        $endDt = Carbon::parse($endDt)->addDay()->format('Y-m-d H:i:s');
                    }

                    foreach ($p_data as $p) {
                        if (empty($p['personnel_id'])) continue;

                        $overlap = DB::table('assignments as a')
                            ->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')
                            ->leftJoin('room as r', 'a.room_id', '=', 'r.id')
                            ->leftJoin('stage_groups as sg', 'a.stage_groups_code', '=', 'sg.code')
                            ->leftJoin('employees as e', 'ap.personnel_id', '=', 'e.id')
                            ->where('ap.personnel_id', $p['personnel_id'])
                            ->where('a.active', 1)
                            ->where('a.start', '<', $endDt)
                            ->where('a.end', '>', $startDt)
                            ->select('a.start', 'a.end', 'a.stage_groups_code', 'r.name as room_name', 'sg.name as group_name', 'a.deparment_code', 'e.name as employee_name')
                            ->first();

                        if ($overlap) {
                            $grpName = $overlap->group_name;
                            if ($overlap->deparment_code == 'EN') {
                                $grpName = 'Bảo trì';
                            } elseif ($overlap->deparment_code == 'QA') {
                                $grpName = 'Hiệu chuẩn';
                            } else {
                                $grpName = $prodGroups[$overlap->stage_groups_code] ?? $overlap->group_name ?? ('Tổ ' . $overlap->stage_groups_code);
                            }
                            $roomName = $overlap->room_name ?: 'Công tác khác';
                            $timeRange = Carbon::parse($overlap->start)->format('H:i') . ' - ' . Carbon::parse($overlap->end)->format('H:i');

                            $formattedTargetDate = Carbon::parse($targetDate)->format('d/m/Y');
                            // throw new \Exception("Ngày {$formattedTargetDate}: Nhân sự {$overlap->employee_name} đã được phân công tại {$grpName} ({$roomName}) trong khoảng thời gian {$timeRange}.");
                        }
                    }

                    $assignmentId = DB::table('assignments')->insertGetId([
                        'stage_plan_id' => $spIdString,
                        'room_id' => $room_id,
                        'work_location' => $work_location,
                        'deparment_code' => $production_code,
                        'stage_groups_code' => $stage_groups_code,
                        'Sheet' => $row['shift'],
                        'start' => $startDt,
                        'end' => $endDt,
                        'Job_description' => isset($row['job_description']) ? trim($row['job_description']) : null,
                        'number_of_employes' => $row['number_of_employes'] ?? 0,
                        'Num_of_per_Level_3' => $row['num_of_per_level_3'] ?? 0,
                        'off_stream' => $row['off_stream'] ?? 0,
                        'assigned_by' => session('user')['userName'] ?? 'System',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'active' => 1
                    ]);

                    $unique_p_data = collect($p_data)->unique('personnel_id');
                    foreach ($unique_p_data as $p) {
                        if (empty($p['personnel_id'])) continue;
                        DB::table('assignment_personnel')->insert([
                            'assignment_id' => $assignmentId,
                            'personnel_id' => $p['personnel_id'],
                            'notification' => $p['notification'] ?? null,
                            'operation_type' => $p['operation_type'] ?? 'thủ công'
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã nhân bản công tác thành công.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhân bản dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatePersonnelTime(Request $request)
    {
        try {
            $assignmentId = $request->input('assignment_id');
            $personnelId = $request->input('personnel_id');
            $start = $request->input('start');
            $end = $request->input('end');
            
            $assignment = DB::table('assignments')->where('id', $assignmentId)->first();
            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy phân công']);
            }
            
            $reportedDate = $request->input('reportedDate') ?? Carbon::parse($assignment->start)->format('Y-m-d');
            
            $pStart = Carbon::parse($reportedDate . ' ' . $start)->format('Y-m-d H:i:s');
            $pEnd = Carbon::parse($reportedDate . ' ' . $end)->format('Y-m-d H:i:s');
            if ($pEnd < $pStart) {
                $pEnd = Carbon::parse($pEnd)->addDay()->format('Y-m-d H:i:s');
            }

            DB::table('assignment_personnel')
                ->where('assignment_id', $assignmentId)
                ->where('personnel_id', $personnelId)
                ->update([
                    'start' => $pStart,
                    'end' => $pEnd
                ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function publicView(Request $request)
    {
        $production_code = $request->production_code ?? 'PXV1'; // Mặc định PXV1 nếu không có
        $reportedDate = $request->reportedDate ?? Carbon::now()->format('Y-m-d');
        $group_code = $request->group_code;

        $startDate = Carbon::parse($reportedDate)->setTime(6, 0, 0);
        $endDate = $startDate->copy()->addDays(1);

        // 1. Lấy danh sách các tổ có trong bộ phận
        $groups = DB::table('room')
            ->where('deparment_code', $production_code)
            ->where('only_maintenance', 0)
            ->whereNotNull('group_code')
            ->select('group_code', 'production_group')
            ->distinct()
            ->orderBy('group_code')
            ->get();

        // Split ĐGSC and ĐGTC
        $hasGroup7 = false;
        foreach ($groups as $g) {
            if ($g->group_code == 7) {
                $g->production_group = 'ĐGSC';
                $hasGroup7 = true;
            }
        }
        if ($hasGroup7 && !$groups->contains('group_code', 8)) {
            $groups->push((object)[
                'group_code' => 8,
                'production_group' => 'ĐGTC'
            ]);
            $groups = $groups->sortBy('group_code')->values();
        }

        // 2. Lấy danh sách phòng (có lọc theo tổ)
        $roomQuery = DB::table('room')
            ->where('deparment_code', $production_code)
            ->where('only_maintenance', 0);

        if ($group_code) {
            if ($group_code == 7 || $group_code == 8) {
                $roomQuery->whereIn('group_code', [7, 8]);
            } else {
                $roomQuery->where('group_code', $group_code);
            }
        }

        $rooms = $roomQuery->orderBy('group_code')->orderBy('order_by')->get();

        // 3. Lấy dữ liệu công việc lý thuyết trong ngày
        $stagePlanQuery = DB::table('stage_plan as sp')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('product_name', 'fpc.product_name_id', 'product_name.id')
            ->where('sp.deparment_code', $production_code)
            ->where('pl.type', 1)
            ->where('sp.active', 1)
            ->whereRaw('(sp.start < ? AND sp.end > ?)', [$endDate, $startDate]);

        if ($rooms->isNotEmpty()) {
            $stagePlanQuery->whereIn('sp.resourceId', $rooms->pluck('id'));
        }

        $stagePlans = $stagePlanQuery->select(
            'sp.id',
            'sp.resourceId as room_id',
            'sp.start',
            'sp.end',
            'sp.title',
            'sp.title_clearning',
            'sp.stage_code',
            'pm.batch',
            'product_name.name as product_name'
        )
            ->get()
            ->groupBy('room_id');

        // 4. Lấy dữ liệu đã phân công
        $assignmentsQuery = DB::table('assignments as a')
            ->leftJoin('user_management as u', 'a.assigned_by', '=', 'u.userName')
            ->select('a.*', 'u.fullname as assigner_name')
            ->where('a.deparment_code', $production_code)
            ->whereDate('a.start', $reportedDate)
            ->where('a.active', 1);

        if ($group_code) {
            if ($group_code == 7 || $group_code == 8) {
                $assignmentsQuery->whereIn('a.stage_groups_code', [7, 8]);
            } else {
                $assignmentsQuery->where('a.stage_groups_code', $group_code);
            }
        }

        $allAssignments = $assignmentsQuery->get()->groupBy('room_id');

        // 5. Lấy dữ liệu báo cáo hoạt động thực tế (Actual Detail) từ DailyReportController
        $dailyReportController = app(DailyReportController::class);
        $reportData = $dailyReportController->yield_actual_detial($startDate, $endDate, 'resourceId', $production_code);
        $actualDetails = collect($reportData['actual_detail'])->groupBy('resourceId');

        // 6. Tổ chức lại dữ liệu
        $tasks = $rooms->map(function ($room) use ($stagePlans, $allAssignments, $actualDetails, $startDate, $endDate) {
            $plans = $stagePlans->get($room->id) ?? collect();
            $assignments = $allAssignments->get($room->id) ?? collect();
            $actuals = $actualDetails->get($room->id) ?? collect();

            $theoryDisplay = '';
            
            $displayItems = [];
            foreach ($plans as $p) {
                // Production event
                if ($p->start && $p->end) {
                    if ($p->start < $endDate && $p->end > $startDate) {
                        $isCleaning = (stripos($p->title, 'vệ sinh') !== false || stripos($p->title, 'VS-') !== false || stripos($p->title, 'VS ') !== false);
                        if ($isCleaning) {
                            $cleanTitle = 'Vệ sinh';
                            if (stripos($p->title, 'cấp 2') !== false || stripos($p->title, 'cấp II') !== false || stripos($p->title_clearning ?? '', 'VS-II') !== false) {
                                $cleanTitle = 'Vệ sinh cấp II';
                            } elseif (stripos($p->title, 'cấp 1') !== false || stripos($p->title, 'cấp I') !== false || stripos($p->title_clearning ?? '', 'VS-I') !== false) {
                                $cleanTitle = 'Vệ sinh cấp I';
                            }
                            if ($p->product_name && $p->product_name !== 'NA') {
                                $displayText = "{$cleanTitle} ({$p->product_name} - {$p->batch})";
                            } else {
                                $displayText = $cleanTitle;
                            }
                        } else {
                            $displayText = $p->product_name ? "{$p->product_name} - {$p->batch}" : strip_tags($p->title);
                        }
                        
                        $displayItems[] = [
                            'start' => $p->start,
                            'end' => $p->end,
                            'text' => $displayText
                        ];
                    }
                }
                
                // Cleaning event
                if (!empty($p->start_clearning) && !empty($p->end_clearning)) {
                    if ($p->start_clearning < $endDate && $p->end_clearning > $startDate) {
                        $cleanTitle = 'Vệ sinh';
                        $titleClearning = $p->title_clearning ?? '';
                        if ($titleClearning == 'VS-II') $cleanTitle = 'Vệ sinh cấp II';
                        elseif ($titleClearning == 'VS-I') $cleanTitle = 'Vệ sinh cấp I';
                        elseif ($titleClearning == 'VS') $cleanTitle = 'Vệ sinh';
                        
                        $productPart = $p->product_name ? "{$p->product_name} - {$p->batch}" : strip_tags($p->title);
                        $displayText = "{$cleanTitle} ({$productPart})";
                        
                        $displayItems[] = [
                            'start' => $p->start_clearning,
                            'end' => $p->end_clearning,
                            'text' => $displayText
                        ];
                    }
                }
            }

            usort($displayItems, function($a, $b) {
                return strtotime($a['start']) <=> strtotime($b['start']);
            });

            foreach ($displayItems as $index => $item) {
                $stt = $index + 1;
                $timeDisp = Carbon::parse($item['start'])->format('H:i') . '-' . Carbon::parse($item['end'])->format('H:i');
                $theoryDisplay .= "<div class='plan-item mb-1 pb-1 border-bottom text-left'><div class='plan-text' style='font-size: 0.8rem; line-height: 1.2;'><b>{$stt}. {$item['text']} | ({$timeDisp})</b></div></div>";
            }
            if ($theoryDisplay == '') $theoryDisplay = '<span class="text-muted italic">Không có lịch</span>';

            foreach ($assignments as $a) {
                $a->personnel_data = DB::table('assignment_personnel')
                    ->where('assignment_id', $a->id)
                    ->select('personnel_id', 'notification', 'operation_type', 'start', 'end')->get();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
            }

            return (object)[
                'room_id' => $room->id,
                'room_code' => $room->code,
                'room_name' => $room->name,
                'main_equiment_name' => $room->main_equiment_name ?? null,
                'theory_display' => $theoryDisplay,
                'assignments' => $assignments,
                'actual_details' => $actuals
            ];
        });

        // 6.5 Thêm các công việc ngoài lịch không có phòng
        $noRoomAssignments = $allAssignments->get("") ?? collect();
        if ($noRoomAssignments->isNotEmpty()) {
            $noRoomGroups = $noRoomAssignments->groupBy('stage_plan_id');
            foreach ($noRoomGroups as $spId => $groupAssignments) {
                foreach ($groupAssignments as $a) {
                    $a->personnel_data = DB::table('assignment_personnel')
                        ->where('assignment_id', $a->id)
                        ->select('personnel_id', 'notification', 'operation_type', 'start', 'end')->get();
                    $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                    $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
                }

                $tasks->push((object)[
                    'room_id' => null,
                    'room_code' => 'NA',
                    'room_name' => $groupAssignments->first()->work_location ?? 'Công tác khác',
                    'main_equiment_name' => null,
                    'theory_display' => '<span class="text-danger font-weight-bold">NA</span>',
                    'assignments' => $groupAssignments->sortBy('start'),
                    'actual_details' => collect()
                ]);
            }
        }

        $personnelQuery = DB::table('employees as e')
            ->where('e.active', 1)
            ->whereExists(function ($query) use ($production_code) {
                $query->select(DB::raw(1))
                    ->from('employee_assignments as ea')
                    ->whereColumn('ea.employees_id', 'e.id')
                    ->where('ea.production_code', $production_code)
                    ->where('ea.active', 1);
            });

        if ($group_code && $group_code != 'HC') {
            $personnelQuery->whereExists(function ($query) use ($group_code) {
                $query->select(DB::raw(1))
                    ->from('employee_assignments as ea2')
                    ->leftJoin('stage_groups as sg', 'ea2.group_id', '=', 'sg.id')
                    ->whereColumn('ea2.employees_id', 'e.id')
                    ->where(function ($q) use ($group_code) {
                        $q->where('sg.code', $group_code)
                            ->orWhere('ea2.group_id', $group_code);
                    })
                    ->where('ea2.active', 1);
            });
        }

        $allowedPersonnelCodes = $personnelQuery->pluck('e.code')->toArray();

        $personnel = DB::table('employees')->where('active', 1)->get();

        $dbAssignments = $this->getDbAssignments($reportedDate);

        $suggestions = DB::table('assignment_suggestions')
            ->where('target_date', $reportedDate)
            ->where('deparment_code', $production_code)
            ->get();

        return view('pages.assignment.production.publicView', [
            'tasks' => $tasks,
            'reportedDate' => $reportedDate,
            'production_code' => $production_code,
            'group_code' => $group_code,
            'groups' => $groups,
            'personnel' => $personnel,
            'allowedPersonnelCodes' => $allowedPersonnelCodes,
            'dbAssignments' => $dbAssignments,
            'suggestions' => $suggestions
        ]);
    }

    private function getDbAssignments($reportedDate)
    {
        $dailyAssignments = DB::table('assignments as a')
            ->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')
            ->leftJoin('room as r', 'a.room_id', '=', 'r.id')
            ->leftJoin('stage_groups as sg', 'a.stage_groups_code', '=', 'sg.code')
            ->whereDate('a.start', $reportedDate)
            ->where('a.active', 1)
            ->select(
                'ap.personnel_id',
                'a.id as assignment_id',
                'a.start',
                'a.end',
                'ap.start as p_start',
                'ap.end as p_end',
                'a.stage_groups_code',
                'a.deparment_code',
                'a.stage_plan_id',
                'a.work_location',
                'sg.name as group_name',
                'r.name as room_name',
                'r.code as room_code'
            )
            ->get();

        $prodGroups = [
            1 => "Trung Tâm Cân",
            3 => "Pha Chế",
            4 => "Văn Phòng",
            5 => "Định Hình",
            6 => "Bao Phim",
            7 => "ĐGSC",
            8 => "ĐGTC",
            9 => "VSCN + Kho BTP"
        ];

        $dbAssignments = [];
        foreach ($dailyAssignments as $ass) {
            $pId = $ass->personnel_id;
            $actualStart = $ass->p_start ?: $ass->start;
            $actualEnd = $ass->p_end ?: $ass->end;
            $startDisplay = $actualStart ? Carbon::parse($actualStart)->format('H:i') : '';
            $endDisplay = $actualEnd ? Carbon::parse($actualEnd)->format('H:i') : '';

            $groupName = '';
            if ($ass->deparment_code == 'EN') {
                $groupName = 'Bảo trì';
            } elseif ($ass->deparment_code == 'QA') {
                $groupName = 'Hiệu chuẩn';
            } else {
                $groupName = $prodGroups[$ass->stage_groups_code] ?? $ass->group_name ?? ('Tổ ' . $ass->stage_groups_code);
            }

            $spId = $ass->stage_plan_id ?: ('EXT_EXISTING_' . $ass->assignment_id);
            $roomName = $ass->work_location ?? ($ass->room_name ?: 'Công tác khác');

            $dbAssignments[$pId][] = (object) [
                'assignment_id' => $ass->assignment_id,
                'sp_id' => $spId,
                'room_code' => $ass->room_code,
                'room_name' => $roomName,
                'start' => $startDisplay,
                'end' => $endDisplay,
                'stage_groups_code' => $ass->stage_groups_code,
                'deparment_code' => $ass->deparment_code,
                'group_name' => $groupName
            ];
        }

        return $dbAssignments;
    }

    public function portal()
    {
        $groups = collect([
            1 => "Trung Tâm Cân",
            3 => "Pha Chế",
            4 => "Văn Phòng",
            5 => "Định Hình",
            6 => "Bao Phim",
            7 => "ĐGSC",
            8 => "ĐGTC",
            9 => "VSCN + Kho BTP"
        ])->map(function ($name, $id) {
            return (object) ['group_code' => $id, 'production_group' => $name];
        })->values();

        session()->put(['title' => 'CỔNG PHÂN CÔNG SẢN XUẤT']);
        return view('pages.assignment.production.portal', compact('groups'));
    }

    public function chartIndex(Request $request)
    {
        session()->put(['title' => 'PHÂN CÔNG SẢN XUẤT - CHART']);
        return view('app');
    }

    public function chartView(Request $request)
    {
        $production_code = session('user')['production_code'] ?? 'PXV1';
        $group_code = $request->group_code;
        $startDate = Carbon::parse($request->startDate)->format('Y-m-d H:i:s');
        $endDate = Carbon::parse($request->endDate)->format('Y-m-d H:i:s');

        $roomQuery = DB::table('room')
            ->where('deparment_code', $production_code)
            ->where('only_maintenance', 0);
        if ($group_code) {
            if ($group_code == 7 || $group_code == 8) {
                $roomQuery->whereIn('group_code', [7, 8]);
            } else {
                $roomQuery->where('group_code', $group_code);
            }
        }
        $rooms = $roomQuery->orderBy('order_by')->get();

        $resources = [];
        foreach ($rooms as $room) {
            $resources[] = [
                'id' => (string)$room->id,
                'title' => $room->code . ' - ' . $room->name,
                'stage_name' => $room->production_group ?: 'Phòng Sản Xuất',
                'order_by' => $room->order_by,
                'code' => $room->code,
                'main_equiment_name' => $room->main_equiment_name,
                'is_personnel_sub' => false
            ];
            $resources[] = [
                'id' => 'personnel-' . $room->id,
                'parentId' => (string)$room->id,
                'title' => '👥 Nhân sự trực',
                'stage_name' => $room->production_group ?: 'Phòng Sản Xuất',
                'order_by' => $room->order_by,
                'code' => $room->code,
                'is_personnel_sub' => true
            ];
        }

        $stagePlanQuery = DB::table('stage_plan as sp')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('product_name', 'fpc.product_name_id', 'product_name.id')
            ->where('sp.deparment_code', $production_code)
            ->where('pl.type', 1)
            ->where('sp.active', 1)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($q1) use ($startDate, $endDate) {
                    $q1->where('sp.start', '<', $endDate)
                       ->where('sp.end', '>', $startDate);
                })->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->whereNotNull('sp.start_clearning')
                       ->where('sp.start_clearning', '<', $endDate)
                       ->where('sp.end_clearning', '>', $startDate);
                });
            });

        if ($rooms->isNotEmpty()) {
            $stagePlanQuery->whereIn('sp.resourceId', $rooms->pluck('id'));
        }
        $plans = $stagePlanQuery->select(
            'sp.id',
            'sp.resourceId as room_id',
            'sp.start',
            'sp.end',
            'sp.start_clearning',
            'sp.end_clearning',
            'sp.title',
            'sp.title_clearning',
            'sp.stage_code',
            'pm.batch',
            'product_name.name as product_name'
        )->get();

        $events = [];
        foreach ($plans as $p) {
            // Production event
            if ($p->start && $p->end) {
                if ($p->start < $endDate && $p->end > $startDate) {
                    $isCleaning = (stripos($p->title, 'vệ sinh') !== false || stripos($p->title, 'VS-') !== false || stripos($p->title, 'VS ') !== false);
                    if ($isCleaning) {
                        $cleanTitle = 'Vệ sinh';
                        if (stripos($p->title, 'cấp 2') !== false || stripos($p->title, 'cấp II') !== false || stripos($p->title_clearning, 'VS-II') !== false) {
                            $cleanTitle = 'Vệ sinh cấp II';
                        } elseif (stripos($p->title, 'cấp 1') !== false || stripos($p->title, 'cấp I') !== false || stripos($p->title_clearning, 'VS-I') !== false) {
                            $cleanTitle = 'Vệ sinh cấp I';
                        }
                        if ($p->product_name && $p->product_name !== 'NA') {
                            $displayText = "{$cleanTitle} ({$p->product_name} - {$p->batch})";
                        } else {
                            $displayText = $cleanTitle;
                        }
                        $events[] = [
                            'id' => 'plan-' . $p->id,
                            'resourceId' => (string)$p->room_id,
                            'start' => $p->start,
                            'end' => $p->end,
                            'title' => "🧹 " . $displayText,
                            'color' => '#d1d5db',
                            'textColor' => '#374151',
                            'borderColor' => '#9ca3af',
                            'editable' => false,
                            'is_plan' => true
                        ];
                    } else {
                        $events[] = [
                            'id' => 'plan-' . $p->id,
                            'resourceId' => (string)$p->room_id,
                            'start' => $p->start,
                            'end' => $p->end,
                            'title' => "📦 " . ($p->product_name ?: $p->title) . " - Ca: " . ($p->batch ?: ''),
                            'color' => '#86efac',
                            'textColor' => '#166534',
                            'borderColor' => '#bbf7d0',
                            'editable' => false,
                            'is_plan' => true
                        ];
                    }
                }
            }
            
            // Cleaning event
            if ($p->start_clearning && $p->end_clearning) {
                if ($p->start_clearning < $endDate && $p->end_clearning > $startDate) {
                    $cleanTitle = 'Vệ sinh';
                    if ($p->title_clearning == 'VS-II') $cleanTitle = 'Vệ sinh cấp II';
                    elseif ($p->title_clearning == 'VS-I') $cleanTitle = 'Vệ sinh cấp I';
                    elseif ($p->title_clearning == 'VS') $cleanTitle = 'Vệ sinh';
                    
                    $productPart = $p->product_name ? "{$p->product_name} - {$p->batch}" : strip_tags($p->title);
                    $displayText = "{$cleanTitle} ({$productPart})";
                    
                    $events[] = [
                        'id' => 'plan-clean-' . $p->id,
                        'resourceId' => (string)$p->room_id,
                        'start' => $p->start_clearning,
                        'end' => $p->end_clearning,
                        'title' => "🧹 " . $displayText,
                        'color' => '#d1d5db',
                        'textColor' => '#374151',
                        'borderColor' => '#9ca3af',
                        'editable' => false,
                        'is_plan' => true
                    ];
                }
            }
        }

        $assignmentQuery = DB::table('assignments as a')
            ->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')
            ->join('employees as e', 'ap.personnel_id', '=', 'e.id')
            ->where('a.deparment_code', $production_code)
            ->where('a.active', 1)
            ->whereRaw('(a.start < ? AND a.end > ?)', [$endDate, $startDate]);

        if ($group_code) {
            if ($group_code == 7 || $group_code == 8) {
                $assignmentQuery->whereIn('a.stage_groups_code', [7, 8]);
            } else {
                $assignmentQuery->where('a.stage_groups_code', $group_code);
            }
        }

        $assignments = $assignmentQuery->select(
            'a.id as assignment_id',
            'a.room_id',
            'a.start',
            'a.end',
            'a.Job_description',
            'a.Sheet',
            'e.id as personnel_id',
            'e.name as employee_name',
            'ap.notification'
        )->get()->groupBy('assignment_id');

        foreach ($assignments as $assId => $items) {
            $first = $items->first();
            if (!$first->room_id) continue;

            $names = $items->pluck('employee_name')->implode(', ');
            $personnelIds = $items->map(function ($i) {
                return ['personnel_id' => $i->personnel_id, 'notification' => $i->notification];
            })->toArray();

            $events[] = [
                'id' => 'assign-' . $assId,
                'resourceId' => 'personnel-' . $first->room_id,
                'start' => $first->start,
                'end' => $first->end,
                'title' => '👥 ' . $names . ($first->Job_description ? ' (' . $first->Job_description . ')' : ''),
                'color' => '#dbeafe',
                'textColor' => '#1e40af',
                'borderColor' => '#bfdbfe',
                'editable' => true,
                'is_assignment' => true,
                'extendedProps' => [
                    'assignment_id' => $assId,
                    'room_id' => $first->room_id,
                    'sheet' => $first->Sheet,
                    'job_description' => $first->Job_description,
                    'personnel_list' => $personnelIds
                ]
            ];
        }

        $personnelQuery = DB::table('employees as e')
            ->where('e.active', 1)
            ->where(function ($q) {
                $q->whereNull('e.resign')
                    ->orWhere('e.resign', 0);
            })
            ->whereExists(function ($query) use ($production_code) {
                $query->select(DB::raw(1))
                    ->from('employee_assignments as ea')
                    ->whereColumn('ea.employees_id', 'e.id')
                    ->where('ea.production_code', $production_code)
                    ->where('ea.active', 1);
            });

        if ($group_code) {
            $personnelQuery->whereExists(function ($query) use ($group_code) {
                $query->select(DB::raw(1))
                    ->from('employee_assignments as ea2')
                    ->leftJoin('stage_groups as sg', 'ea2.group_id', '=', 'sg.id')
                    ->whereColumn('ea2.employees_id', 'e.id')
                    ->where(function ($q) use ($group_code) {
                        $q->where('sg.code', $group_code)
                            ->orWhere('ea2.group_id', $group_code);
                    })
                    ->where('ea2.active', 1);
            });
        }

        $personnel = $personnelQuery->select('e.*')
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(room_id, ':', level, ':', COALESCE(priority_level, 1)) SEPARATOR '|') FROM employee_assignments WHERE employees_id = e.id AND active = 1 AND room_id IS NOT NULL AND room_id > 0) as allowed_rooms_with_levels"))
            ->orderBy('e.name')
            ->get();

        $authorization = session('user')['userGroup'];
        $dbAssignments = $this->getDbAssignments(Carbon::parse($startDate)->format('Y-m-d'));

        return response()->json([
            'resources' => $resources,
            'events' => $events,
            'personnel' => $personnel,
            'dbAssignments' => $dbAssignments,
            'authorization' => $authorization,
            'production' => $production_code,
            'department' => session('user')['department'] ?? ''
        ]);
    }

    public function chartStore(Request $request)
    {
        $production_code = session('user')['production_code'] ?? 'PXV1';
        $group_code = $request->group_code;
        $reportedDate = $request->reportedDate;
        $assignments_data = $request->assignments ?? [];

        try {
            DB::beginTransaction();

            $deleteQuery = DB::table('assignments')
                ->where('deparment_code', $production_code)
                ->whereDate('start', $reportedDate)
                ->where('active', 1);

            if ($group_code) {
                if ($group_code == 7 || $group_code == 8) {
                    $deleteQuery->whereIn('stage_groups_code', [7, 8]);
                } else {
                    $deleteQuery->where('stage_groups_code', $group_code);
                }
            }
            $deleteQuery->update(['active' => 0, 'updated_at' => now()]);

            $prodGroups = [
                1 => "Trung Tâm Cân",
                3 => "Pha Chế",
                4 => "Văn Phòng",
                5 => "Định Hình",
                6 => "Bao Phim",
                7 => "ĐGSC",
                8 => "ĐGTC",
                9 => "VSCN + Kho BTP"
            ];

            foreach ($assignments_data as $row) {
                $p_data = $row['personnel_list'] ?? [];
                if (empty($row['start']) || empty($row['end'])) {
                    continue;
                }

                $startDt = Carbon::parse($row['start'])->format('Y-m-d H:i:s');
                $endDt = Carbon::parse($row['end'])->format('Y-m-d H:i:s');
                $roomId = $row['room_id'] ?? null;
                if ($roomId === "") $roomId = null;

                foreach ($p_data as $p) {
                    if (empty($p['personnel_id'])) continue;

                    $overlap = DB::table('assignments as a')
                        ->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')
                        ->leftJoin('room as r', 'a.room_id', '=', 'r.id')
                        ->leftJoin('stage_groups as sg', 'a.stage_groups_code', '=', 'sg.code')
                        ->leftJoin('employees as e', 'ap.personnel_id', '=', 'e.id')
                        ->where('ap.personnel_id', $p['personnel_id'])
                        ->where('a.active', 1)
                        ->where('a.start', '<', $endDt)
                        ->where('a.end', '>', $startDt)
                        ->select('a.start', 'a.end', 'a.stage_groups_code', 'r.name as room_name', 'sg.name as group_name', 'a.deparment_code', 'e.name as employee_name')
                        ->first();

                    if ($overlap) {
                        $grpName = $overlap->group_name;
                        if ($overlap->deparment_code == 'EN') {
                            $grpName = 'Bảo trì';
                        } elseif ($overlap->deparment_code == 'QA') {
                            $grpName = 'Hiệu chuẩn';
                        } else {
                            $grpName = $prodGroups[$overlap->stage_groups_code] ?? $overlap->group_name ?? ('Tổ ' . $overlap->stage_groups_code);
                        }
                        $roomName = $overlap->room_name ?: 'Công tác khác';
                        $timeRange = Carbon::parse($overlap->start)->format('H:i') . ' - ' . Carbon::parse($overlap->end)->format('H:i');

                        // throw new \Exception("Nhân sự {$overlap->employee_name} đã được phân công tại {$grpName} ({$roomName}) trong khoảng thời gian {$timeRange}.");
                    }
                }

                $assignmentId = DB::table('assignments')->insertGetId([
                    'room_id' => $roomId,
                    'deparment_code' => $production_code,
                    'stage_groups_code' => $group_code,
                    'Sheet' => $row['sheet'] ?? 1,
                    'start' => $startDt,
                    'end' => $endDt,
                    'Job_description' => isset($row['job_description']) ? trim($row['job_description']) : null,
                    'number_of_employes' => count($p_data),
                    'assigned_by' => session('user')['userName'] ?? 'System',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'active' => 1
                ]);

                $unique_p_data = collect($p_data)->unique('personnel_id');
                foreach ($unique_p_data as $p) {
                    if (empty($p['personnel_id'])) continue;
                    DB::table('assignment_personnel')->insert([
                        'assignment_id' => $assignmentId,
                        'personnel_id' => $p['personnel_id'],
                        'notification' => $p['notification'] ?? null,
                        'operation_type' => $p['operation_type'] ?? 'thủ công'
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã lưu phân công thành công.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
