<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        $groups = DB::table('room')
            ->where('deparment_code', $production_code)
            ->where('stage_code', '!=', 8)
            ->whereNotNull('group_code')
            ->select('group_code', 'production_group')
            ->distinct()
            ->orderBy('group_code')
            ->get();

        // 2. Logic khóa tổ theo tên (group_name):
        $isLocked = false;
        $active_group_code = $request->group_code;

        // Tìm xem group_name của user có khớp với tổ nào trong bộ phận này không
        if ($user_group_name) {
            $matchedGroup = $groups->first(function($g) use ($user_group_name) {
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
            $roomQuery->where('group_code', $active_group_code);
        }

        $rooms = $roomQuery->orderBy('group_code')->orderBy('order_by')->get();

        // 4. Lấy dữ liệu công việc lý thuyết trong ngày
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

        // 5. Lấy dữ liệu đã phân công (Assignments)
        $allAssignments = DB::table('assignments as a')
            ->where('a.deparment_code', $production_code)
            ->whereDate('a.start', $reportedDate)
            ->where('a.active', 1)
            ->get()
            ->groupBy('room_id');

        // 6. Tổ chức lại dữ liệu theo từng phòng
        $tasks = $rooms->map(function ($room) use ($stagePlans, $allAssignments, $reportedDate) {
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

            // Lấy thông tin nhân viên cho mỗi assignment
            foreach ($assignments as $a) {
                $a->personnel_data = DB::table('assignment_personnel')
                    ->where('assignment_id', $a->id)
                    ->select('personnel_id', 'notification')->get();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
            }

            return (object)[
                'sp_id' => $spIdString, // Dùng để lưu vết các stage_plan liên quan
                'room_id' => $room->id,
                'room_code' => $room->code,
                'room_name' => $room->name,
                'theory_display' => $theoryDisplay,
                'assignments' => $assignments,
                'theory_start' => '07:15', // Giá trị mặc định khi thêm ca mới
                'theory_end' => '16:00',
            ];
        });

        $personnelQuery = DB::table('personnel')
            ->where('deparment_code', $production_code)
            ->where('active', 1);

        if ($user_group_name) {
            $personnelQuery->where('group_name', $user_group_name);
        }

        $personnel = $personnelQuery->orderBy('name')->get();

        session()->put(['title' => 'PHÂN CÔNG SẢN XUẤT']);

        return view('pages.assignment.production.index', [
            'tasks' => $tasks,
            'reportedDate' => $reportedDate,
            'group_code' => $active_group_code,
            'groups' => $groups,
            'isLocked' => $isLocked,
            'personnel' => $personnel,
            'rooms' => $rooms
        ]);
    }

    public function store(Request $request)
    {
        $spIdString = $request->sp_id;
        $room_id = $request->room_id;
        $reportedDate = $request->reportedDate;
        $stage_groups_code = $request->stage_groups_code ?? null;
        $assignments_data = $request->assignments ?? [];
        $production_code = session('user')['production_code'];

        try {
            DB::beginTransaction();

            $deleteQuery = DB::table('assignments')
                ->where('room_id', $room_id)
                ->where('deparment_code', $production_code)
                ->whereDate('start', $reportedDate)
                ->where('active', 1);
            if ($stage_groups_code) {
                $deleteQuery->where('stage_groups_code', $stage_groups_code);
            }
            $deleteQuery->update(['active' => 0, 'updated_at' => now()]);

            if (!empty($assignments_data)) {
                foreach ($assignments_data as $row) {
                    $p_data = $row['personnel_list'] ?? [];
                    if (empty($p_data)) continue;

                    $startDt = $reportedDate . ' ' . $row['start_time'];
                    $endDt = $reportedDate . ' ' . $row['end_time'];
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
                        'Job_description' => $row['job_description'] ?? null,
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
                            'notification' => $p['notification'] ?? null
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã lưu phân công']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
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

        // 5. Tổ chức lại dữ liệu
        $tasks = $rooms->map(function ($room) use ($stagePlans, $allAssignments) {
            $plans = $stagePlans->get($room->id) ?? collect();
            $assignments = $allAssignments->get($room->id) ?? collect();

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
                'assignments' => $assignments
            ];
        });

        $personnel = DB::table('personnel')->where('active', 1)->get();

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
