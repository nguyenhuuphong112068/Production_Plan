<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class MaintenanceAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $production_code = session('user')['production_code'];
        $reportedDateInput = $request->reportedDate ?? Carbon::now()->format('Y-m-d');

        // Báo cáo bắt đầu từ 06:00 sáng và kết thúc lúc 06:00 sáng hôm sau
        $startDate = Carbon::parse($reportedDateInput)->setTime(6, 0, 0);
        $endDate = $startDate->copy()->addDays(1);

        // 1. Lấy TOÀN BỘ phòng thuộc phân xưởng và Join với dữ liệu bảo trì (nếu có)
        $tasks = DB::table('room as r')
            ->leftJoin('stage_plan as sp', function ($join) use ($startDate, $endDate) {
                $join->on('r.id', '=', 'sp.resourceId')
                    ->where('sp.stage_code', '=', 8)
                    ->where('sp.tank', '=', 1)
                    ->whereBetween('sp.start', [$startDate, $endDate]);
            })
            ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
            ->where('r.deparment_code', $production_code)
            ->select(
                'r.id as room_id',
                'r.name as room_name',
                'sp.id as sp_id',
                'plan_master.batch',
                'sp.start',
                'sp.end',
                'qm.inst_name',
                'qm.Eqp_name',
                DB::raw("CASE WHEN qm.block LIKE 'TI-%' THEN 'Tiện ích' 
                              WHEN qm.block LIKE 'HC-%' THEN 'Hiệu chuẩn' 
                              ELSE 'Bảo Trì' END as type_name")
            )
            ->orderBy('r.order_by')
            ->orderBy('r.code')
            ->get();

        // 2. Lấy thông tin nhân sự, ca và ghi chú đã phân công
        foreach ($tasks as $task) {
            if ($task->sp_id) {
                $assignments = DB::table('maintenance_assignments as ma')
                    ->join('personnel', 'ma.personnel_id', '=', 'personnel.id')
                    ->where('ma.stage_plan_id', $task->sp_id)
                    ->select('personnel.id', 'personnel.name', 'personnel.code', 'ma.shift', 'ma.note')
                    ->get();

                $task->assigned_personnel = $assignments;
                $task->current_shift = $assignments->first()->shift ?? 'HC';
                $task->current_note = $assignments->first()->note ?? '';
            } else {
                $task->assigned_personnel = collect();
                $task->current_shift = 'HC';
                $task->current_note = '';
            }
        }

        // 3. Lấy danh sách nhân sự để chọn
        $personnel = DB::table('personnel')->where('active', true)->get();

        $displayDate = Carbon::parse($reportedDateInput)->format('d/m/Y');
        session()->put(['title' => "PHÂN CÔNG BẢO TRÌ NGÀY $displayDate"]);

        return view('pages.assignment.maintenance.index', [
            'tasks' => $tasks,
            'personnel' => $personnel,
            'reportedDate' => $reportedDateInput
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stage_plan_id' => 'required',
            'personnel_ids' => 'array',
            'shift' => 'required',
            'note' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        }

        try {
            DB::beginTransaction();

            $stage_plan_id = $request->stage_plan_id;
            $personnel_ids = $request->personnel_ids;
            $shift = $request->shift;
            $note = $request->note;

            // Xóa phân công cũ của task này
            DB::table('maintenance_assignments')
                ->where('stage_plan_id', $stage_plan_id)
                ->delete();

            // Nếu có nhân sự được chọn, thêm mới
            if (!empty($personnel_ids)) {
                $dataToInsert = [];
                foreach ($personnel_ids as $pId) {
                    $dataToInsert[] = [
                        'stage_plan_id' => $stage_plan_id,
                        'personnel_id' => $pId,
                        'shift' => $shift,
                        'note' => $note,
                        'assigned_by' => session('user')['userName'] ?? 'System',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                DB::table('maintenance_assignments')->insert($dataToInsert);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã lưu phân công']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }
}
