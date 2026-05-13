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
            ->where('stage_code', '!=', 8);

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
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('product_name', 'fpc.product_name_id', 'product_name.id')
            ->where('sp.deparment_code', $production_code);

        if ($active_group_code == 8) {
            $stagePlanQuery->whereIn('sp.stage_code', [7, 8]);
        } else {
            $stagePlanQuery->where('sp.stage_code', '!=', 8);
        }

        $stagePlans = $stagePlanQuery->where('sp.active', 1)
            ->whereRaw('(sp.start < ? AND sp.end > ?)', [$endDate, $startDate])
            ->select(
                'sp.id',
                'sp.resourceId as room_id',
                'sp.start',
                'sp.end',
                'sp.title',
                'sp.stage_code',
                'pm.batch',
                'product_name.name as product_name'
            )
            ->get()
            ->groupBy('room_id');

        // 5. Lấy dữ liệu đã phân công (Assignments)
        $assignmentQuery = DB::table('assignments as a')
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

        // 6. Lấy dữ liệu báo cáo hoạt động thực tế (Actual Detail) từ DailyReportController
        //$dailyReportController = app(DailyReportController::class);
        // $reportData = $dailyReportController->yield_actual_detial($startDate, $endDate, 'resourceId');
        // $actualDetails = collect($reportData['actual_detail'])->groupBy('resourceId');

        // 7. Tổ chức lại dữ liệu theo từng phòng
        $tasks = $rooms->map(function ($room) use ($stagePlans, $allAssignments, $reportedDate, $active_group_code) {
            $plans = $stagePlans->get($room->id) ?? collect();
            $assignments = $allAssignments->get($room->id) ?? collect();

            // Tạo chuỗi hiển thị lịch lý thuyết (Theory Display)
            $theoryDisplay = '';
            $spIds = [];
            foreach ($plans as $index => $p) {
                $stt = $index + 1;
                $spIds[] = $p->id;
                $timeDisp = Carbon::parse($p->start)->format('H:i') . '-' . Carbon::parse($p->end)->format('H:i');
                $theoryDisplay .= "<div class='plan-item mb-1 pb-1 border-bottom position-relative hover-show-btn' data-start='{$p->start}'><div class='plan-text' style='font-size: 0.8rem; line-height: 1.2;'><b>{$stt}. {$p->product_name} - {$p->batch} <span class='time-text'>| ({$timeDisp})</span></b></div><button class='btn btn-xs btn-primary btn-copy-plan' title='Chép mục này' style='position: absolute; right: 0; top: 0; padding: 0 4px; font-size: 10px; display: none;'> >></button></div>";
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
                $a->personnel_data = DB::table('assignment_personnel')
                    ->where('assignment_id', $a->id)
                    ->select('personnel_id', 'notification')->get();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
            }

            // Tự động tạo gợi ý nếu chưa có phân công
            if ($assignments->isEmpty() && $plans->isNotEmpty()) {
                $dayStart = Carbon::parse($reportedDate)->setTime(6, 0, 0);
                $shiftItems = ['1' => [], '2' => [], '3' => []];

                foreach ($plans as $p) {
                    $pStart = Carbon::parse($p->start);
                    $pEnd = Carbon::parse($p->end);
                    $s1S = $dayStart->copy();
                    $s1E = $dayStart->copy()->addHours(8);
                    if ($pStart->lt($s1E) && $pEnd->gt($s1S)) $shiftItems['1'][] = "{$p->product_name} - {$p->batch}";
                    $s2S = $s1E->copy();
                    $s2E = $s2S->copy()->addHours(8);
                    if ($pStart->lt($s2E) && $pEnd->gt($s2S)) $shiftItems['2'][] = "{$p->product_name} - {$p->batch}";
                    $s3S = $s2E->copy();
                    $s3E = $s3S->copy()->addHours(8);
                    if ($pStart->lt($s3E) && $pEnd->gt($s3S)) $shiftItems['3'][] = "{$p->product_name} - {$p->batch}";
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
                    $suggestedCount = $room->$roomCol ?? 0;

                    $assignments->push((object)[
                        'id' => null,
                        'Sheet' => $code,
                        'start' => $sTime->toDateTimeString(),
                        'end' => $eTime->toDateTimeString(),
                        'Job_description' => trim($jobDescription),
                        'number_of_employes' => $suggestedCount,
                        'personnel_data' => collect([(object)['personnel_id' => null, 'notification' => null]]),
                        'start_time_display' => $sTime->format('H:i'),
                        'end_time_display' => $eTime->format('H:i'),
                        'is_foreign' => false,
                        'is_scheduled' => true
                    ]);
                }
                $assignments = $assignments->sortBy('start');
            }

            return (object)[
                'sp_id' => $spIdString,
                'room_id' => $room->id,
                'group_code' => $room->group_code,
                'room_code' => $room->code,
                'room_name' => $room->name,
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
                    $a->personnel_data = DB::table('assignment_personnel')
                        ->where('assignment_id', $a->id)
                        ->select('personnel_id', 'notification')->get();
                    $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                    $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
                }

                $tasks->push((object)[
                    'sp_id' => $spId,
                    'room_id' => null,
                    'group_code' => $active_group_code,
                    'room_code' => 'NA',
                    'room_name' => 'Công tác khác',
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
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(room_id, ':', level) SEPARATOR '|') FROM employee_assignments WHERE employees_id = e.id AND active = 1 AND room_id IS NOT NULL) as allowed_rooms_with_levels"))
            ->orderBy('e.name')
            ->get();

        // Tạo mapping skills từ danh sách personnel đã lấy
        $skills = $personnel->keyBy('id');

        // Tạo danh sách ID nhân sự được phép để lọc sidebar ở client
        $allowedPersonnelCodes = $personnel->pluck('code')->toArray();

        session()->put(['title' => 'LỊCH CÔNG TÁC SẢN XUẤT']);

        return view('pages.assignment.production.index', [
            'tasks' => $tasks,
            'reportedDate' => $reportedDate,
            'group_code' => $active_group_code,
            'groups' => $groups,
            'isLocked' => $isLocked,
            'personnel' => $personnel,
            'skills' => $skills, // Truyền dữ liệu bậc kỹ năng
            'allowedPersonnelCodes' => $allowedPersonnelCodes,
            'rooms' => $rooms
        ]);
    }

    public function getPersonnelShifts(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $departmentId = $request->department;

        $url = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department={$departmentId}";

        try {
            $data = file_get_contents($url);
            $personnelData = json_decode($data, true);

            // Lấy thông tin hasAssignment từ bảng employees local
            $localEmployees = DB::table('employees')->select('code', 'hasAssignment')->get()->keyBy('code');

            foreach ($personnelData as &$person) {
                $code = $person['employeeId'] ?? $person['code'] ?? null;
                if ($code && isset($localEmployees[$code])) {
                    $person['hasAssignment'] = $localEmployees[$code]->hasAssignment;
                } else {
                    $person['hasAssignment'] = 1; // Mặc định là 1 (có sắp lịch)
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
        $room_id = $request->room_id;
        if ($room_id === "") $room_id = null;
        $reportedDate = $request->reportedDate;
        $stage_groups_code = $request->stage_groups_code;
        if (empty($stage_groups_code)) $stage_groups_code = null;

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

            if ($spIdString && str_starts_with($spIdString, 'EXT_')) {
                // Đối với công việc ngoài lịch có ID định danh riêng
                $deleteQuery->where('stage_plan_id', $spIdString);
            } else {
                // Đối với công việc theo phòng (có hoặc không có sp_id)
                if ($room_id) {
                    $deleteQuery->where('room_id', $room_id);
                    if ($spIdString) {
                        $deleteQuery->where('stage_plan_id', $spIdString);
                    } else {
                        $deleteQuery->whereNull('stage_plan_id');
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
            }

            $deleteQuery->update(['active' => 0, 'updated_at' => now()]);

            // 2. Thêm mới các phân công
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

                    $assignmentId = DB::table('assignments')->insertGetId([
                        'stage_plan_id' => $spIdString,
                        'room_id' => $room_id,
                        'deparment_code' => $production_code,
                        'stage_groups_code' => $stage_groups_code,
                        'Sheet' => $row['shift'],
                        'start' => $startDt,
                        'end' => $endDt,
                        'Job_description' => isset($row['job_description']) ? trim($row['job_description']) : null,
                        'number_of_employes' => $row['number_of_employes'] ?? 0,
                        'off_stream' => $row['off_stream'] ?? 0,
                        'assigned_by' => session('user')['userName'] ?? 'System',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'active' => 1
                    ]);

                    // Cập nhật lại định mức ở bảng room tương ứng với ca
                    $shiftCode = $row['shift'];
                    $roomCol = null;
                    if ($shiftCode == '1') $roomCol = 'number_of_employes_on_sheet1';
                    elseif ($shiftCode == '2') $roomCol = 'number_of_employes_on_sheet2';
                    elseif ($shiftCode == '3') $roomCol = 'number_of_employes_on_sheet3';
                    elseif ($shiftCode == '6') $roomCol = 'number_of_employes_on_sheet4';
                    elseif ($shiftCode == '4') $roomCol = 'number_of_employes_on_sheet_regular';

                    if ($roomCol && isset($row['number_of_employes'])) {
                        DB::table('room')->where('id', $room_id)->update([
                            $roomCol => $row['number_of_employes']
                        ]);
                    }

                    // Lưu danh sách nhân sự
                    $unique_p_data = collect($p_data)->unique('personnel_id');
                    foreach ($unique_p_data as $p) {
                        if (empty($p['personnel_id'])) continue;
                        DB::table('assignment_personnel')->insert([
                            'assignment_id' => $assignmentId,
                            'personnel_id' => $p['personnel_id'],
                            'notification' => $p['notification'] ?? null
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
            ->where('stage_code', '!=', 8)
            ->whereNotNull('group_code')
            ->select('group_code', 'production_group')
            ->distinct()
            ->orderBy('group_code')
            ->get();

        // 2. Lấy danh sách phòng (có lọc theo tổ)
        $roomQuery = DB::table('room')
            ->where('deparment_code', $production_code)
            ->where('stage_code', '!=', 8);

        if ($group_code) {
            $roomQuery->where('group_code', $group_code);
        }

        $rooms = $roomQuery->orderBy('group_code')->orderBy('order_by')->get();

        // 3. Lấy dữ liệu công việc lý thuyết trong ngày
        $stagePlans = DB::table('stage_plan as sp')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('product_name', 'fpc.product_name_id', 'product_name.id')
            ->where('sp.deparment_code', $production_code)
            ->where('sp.stage_code', '!=', 8)
            ->where('sp.active', 1)
            ->whereRaw('(sp.start < ? AND sp.end > ?)', [$endDate, $startDate])
            ->select(
                'sp.id',
                'sp.resourceId as room_id',
                'sp.start',
                'sp.end',
                'sp.title',
                'sp.stage_code',
                'pm.batch',
                'product_name.name as product_name'
            )
            ->get()
            ->groupBy('room_id');

        // 4. Lấy dữ liệu đã phân công
        $allAssignments = DB::table('assignments as a')
            ->where('a.deparment_code', $production_code)
            ->whereDate('a.start', $reportedDate)
            ->where('a.active', 1)
            ->get()
            ->groupBy('room_id');

        // 5. Lấy dữ liệu báo cáo hoạt động thực tế (Actual Detail) từ DailyReportController
        $dailyReportController = app(DailyReportController::class);
        $reportData = $dailyReportController->yield_actual_detial($startDate, $endDate, 'resourceId', $production_code);
        $actualDetails = collect($reportData['actual_detail'])->groupBy('resourceId');

        // 6. Tổ chức lại dữ liệu
        $tasks = $rooms->map(function ($room) use ($stagePlans, $allAssignments, $actualDetails) {
            $plans = $stagePlans->get($room->id) ?? collect();
            $assignments = $allAssignments->get($room->id) ?? collect();
            $actuals = $actualDetails->get($room->id) ?? collect();

            $theoryDisplay = '';
            foreach ($plans as $index => $p) {
                $stt = $index + 1;
                $timeDisp = Carbon::parse($p->start)->format('H:i') . '-' . Carbon::parse($p->end)->format('H:i');
                $theoryDisplay .= "<div class='plan-item mb-1 pb-1 border-bottom text-left'><div class='plan-text' style='font-size: 0.8rem; line-height: 1.2;'><b>{$stt}. {$p->product_name} - {$p->batch} | ({$timeDisp})</b></div></div>";
            }
            if ($theoryDisplay == '') $theoryDisplay = '<span class="text-muted italic">Không có lịch</span>';

            foreach ($assignments as $a) {
                $a->personnel_data = DB::table('assignment_personnel')
                    ->where('assignment_id', $a->id)
                    ->select('personnel_id', 'notification')->get();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
            }

            return (object)[
                'room_id' => $room->id,
                'room_code' => $room->code,
                'room_name' => $room->name,
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
                        ->select('personnel_id', 'notification')->get();
                    $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                    $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
                }

                $tasks->push((object)[
                    'room_id' => null,
                    'room_code' => 'NA',
                    'room_name' => 'Công tác khác',
                    'theory_display' => '<span class="text-danger font-weight-bold">NA</span>',
                    'assignments' => $groupAssignments->sortBy('start'),
                    'actual_details' => collect()
                ]);
            }
        }

        $personnel = DB::table('employees')->where('active', 1)->get();

        return view('pages.assignment.production.publicView', [
            'tasks' => $tasks,
            'reportedDate' => $reportedDate,
            'production_code' => $production_code,
            'group_code' => $group_code,
            'groups' => $groups,
            'personnel' => $personnel
        ]);
    }
}
