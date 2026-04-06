<?php

namespace App\Http\Controllers\Pages\History;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MaintenanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::now()->subMonth(1)->toDateString();
        $toDate   = $request->to_date ?? Carbon::now()->addDays(1)->toDateString();
        $main_type = 'maintenance';
        $maintenanceType = $request->maintenance_type ?? 'HC';
        $production = session('user')['production_code'];

        $query = DB::table('stage_plan as sp')
            ->leftJoin('room', 'sp.resourceId', '=', 'room.id')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
            ->select(
                'sp.*',
                'room.name as room_name',
                'room.code as room_code',
                'room.stage as stage',
                'pm.expected_date',
                'pm.is_val',
                'qm.inst_id as instrument_code'
            )
            ->where('sp.deparment_code', $production)
            ->whereBetween('sp.actual_start', [$fromDate, $toDate])
            ->where('sp.active', 1)
            ->where('sp.stage_code', 8)
            ->where('sp.finished', 1);

        if ($maintenanceType === 'TB') {
            $query->where(function ($q) {
                $q->where('sp.code', 'like', '%_TB')
                    ->orWhere('sp.code', 'like', '%_8');
            });
        } else {
            $query->where('sp.code', 'like', '%_' . $maintenanceType);
        }

        $datas = $query->get();

        // Fetch instrument names for stage 8
        $instIds = $datas->pluck('instrument_code')->filter()->unique()->toArray();
        $instruments = collect();

        if (!empty($instIds)) {
            $connections = ['cal1', 'cal2'];
            $suffixes = [1, 2, 3];
            foreach ($connections as $conn) {
                foreach ($suffixes as $suffix) {
                    try {
                        $result = DB::connection($conn)
                            ->table("Inst_Master_{$suffix} as Ins")
                            ->leftJoin("Eqp_mst_{$suffix} as Eqp", 'Eqp.Eqp_ID', '=', 'Ins.Parent_Equip_id')
                            ->whereIn('Ins.Inst_id', $instIds)
                            ->select('Ins.Inst_id', 'Ins.Inst_Name', 'Ins.Parent_Equip_id', 'Eqp.Eqp_name')
                            ->get()
                            ->keyBy('Inst_id');
                        $instruments = $instruments->merge($result);
                    } catch (\Exception $e) {
                        // Suppress error
                    }
                }
            }

            $datas->map(function ($item) use ($instruments) {
                $inst = $instruments[$item->instrument_code] ?? null;
                $item->name = $inst->Inst_Name ?? $item->title;
                $item->parent_instrument_code = $inst->Parent_Equip_id ?? '';
                $item->parent_instrument_name = $inst->Eqp_name ?? '';
                return $item;
            });
        }

        $title = 'LỊCH SỬ BẢO TRÌ - HIỆU CHUẨN';
        session()->put(['title' => $title]);

        return view('pages.History.list', [
            'datas' => $datas,
            'main_type' => $main_type,
            'maintenanceType' => $maintenanceType
        ]);
    }

    public function returnStage(Request $request)
    {
        DB::beginTransaction();
        try {
            if (!$request->has('stage_plan_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stage plan id is required'
                ], 400);
            }

            // ✅ Reset trạng thái stage
            DB::table('stage_plan')
                ->where('id', $request->stage_plan_id)
                ->update([
                    'finished' => 0,
                    'actual_start' => null,
                    'actual_end' => null,
                    'actual_start_clearning' => null,
                    'actual_end_clearning' => null,
                    'yields' => null,
                    'finished_by' => null,
                    'finished_date' => null,
                ]);

            // ✅ Xoá toàn bộ yields liên quan
            DB::table('yields')
                ->where('stage_plan_id', $request->stage_plan_id)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Trả Về Thành Công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error while returning stage',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
