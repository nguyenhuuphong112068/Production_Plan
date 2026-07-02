<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ValidationTracking;
use App\Models\ValidationTrackingIntermediateCategory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ValidationTrackingController extends Controller
{
    public function index()
    {
        $trackings = ValidationTracking::with(['intermediateCategories.intermediateCategory.productName', 'planMasters.planMaster'])
            ->orderBy('created_at', 'desc')
            ->get();

        $products = \App\Models\IntermediateCategory::with(['productName', 'validationTrackings.validationTracking'])
            ->whereHas('validationTrackings')
            ->get();

        return view('pages.plan.validation_tracking.list', compact('trackings', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'MatID' => 'required|string|max:50',
            'MaterialName' => 'required|string|max:255',
            'intermediate_category_ids' => 'required|array',
            'intermediate_category_ids.*' => 'integer',
        ]);

        try {
            DB::beginTransaction();

            $tracking = ValidationTracking::create([
                'MatID' => $request->MatID,
                'MaterialName' => $request->MaterialName,
                'purpose' => $request->purpose,
                'CC_num' => $request->CC_num,
                'status' => 'Chờ phê duyệt',
                'note' => $request->note,
                'created_by' => session('user.username') ?? 'System',
            ]);

            foreach ($request->intermediate_category_ids as $key => $ic_id) {
                ValidationTrackingIntermediateCategory::create([
                    'validation_tracking_id' => $tracking->id,
                    'intermediate_category_id' => $ic_id,
                    'num_of_tracking_batch' => $request->num_of_tracking_batches[$key] ?? 1,
                    'note' => $request->ic_notes[$key] ?? null,
                    'updated_by' => session('user.username') ?? 'System',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Thêm mới thành công']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'MatID' => 'required|string|max:50',
            'MaterialName' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $tracking = ValidationTracking::findOrFail($request->id);
            $tracking->update([
                'MatID' => $request->MatID,
                'MaterialName' => $request->MaterialName,
                'purpose' => $request->purpose,
                'CC_num' => $request->CC_num,
                'note' => $request->note,
            ]);

            if ($request->has('intermediate_category_ids')) {
                // Update or Create pivot records
                $existingIds = $tracking->intermediateCategories()->pluck('intermediate_category_id')->toArray();
                $newIds = $request->intermediate_category_ids;

                // Remove deleted
                $toDelete = array_diff($existingIds, $newIds);
                if (count($toDelete) > 0) {
                    ValidationTrackingIntermediateCategory::where('validation_tracking_id', $tracking->id)
                        ->whereIn('intermediate_category_id', $toDelete)
                        ->delete();
                }

                // Add or update
                foreach ($request->intermediate_category_ids as $key => $ic_id) {
                    ValidationTrackingIntermediateCategory::updateOrCreate(
                        [
                            'validation_tracking_id' => $tracking->id,
                            'intermediate_category_id' => $ic_id,
                        ],
                        [
                            'num_of_tracking_batch' => $request->num_of_tracking_batches[$key] ?? 1,
                            'note' => $request->ic_notes[$key] ?? null,
                            'updated_by' => session('user.username') ?? 'System',
                        ]
                    );
                }
            } else {
                // If no intermediates are sent, clear all
                $tracking->intermediateCategories()->delete();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Cập nhật thành công']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function approve(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        try {
            $tracking = ValidationTracking::findOrFail($request->id);
            $tracking->update([
                'status' => 'Đang theo dõi',
                'approved' => 1,
                'approved_at' => Carbon::now(),
                'approved_by' => session('user.username') ?? 'System',
            ]);

            return response()->json(['success' => true, 'message' => 'Duyệt thành công']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function checkValidation(Request $request)
    {
        $ic_id = $request->intermediate_category_id;
        if (!$ic_id) {
            return response()->json([]);
        }

        $trackings = ValidationTrackingIntermediateCategory::with('validationTracking')
            ->where('intermediate_category_id', $ic_id)
            ->whereColumn('num_of_finished_batch', '<', 'num_of_tracking_batch')
            ->whereHas('validationTracking', function ($q) {
                $q->where('status', 'Đang theo dõi');
            })
            ->get();

        return response()->json($trackings);
    }

    public function getPlanMasters(Request $request, $tracking_id)
    {
        $ic_id = $request->query('ic_id');
        
        $planMasterIds = DB::table('validation_tracking_plan_master')
            ->where('validation_tracking_id', $tracking_id)
            ->pluck('plan_master_id')
            ->toArray();

        if (empty($planMasterIds)) {
            return response()->json(['html' => '<div class="text-center p-3">Không có lô sản xuất nào được gắn</div>']);
        }

        $maxStageFinished = DB::table('stage_plan')
            ->where('finished', 1)
            ->where('stage_code', '!=', 8)
            ->select('plan_master_id', DB::raw('MAX(stage_code) as max_stage_code'))
            ->groupBy('plan_master_id');

        $maxPossibleStage = DB::table('stage_plan')
            ->where('active', 1)
            ->where('stage_code', '!=', 8)
            ->select('plan_master_id', DB::raw('MAX(stage_code) as max_possible_stage_code'))
            ->groupBy('plan_master_id');

        $query = DB::table('plan_master')
            ->select(
                'plan_master.*',
                'finished_product_category.intermediate_code',
                'finished_product_category.finished_product_code',
                'finished_product_category.IsHypothesis',
                DB::raw('fp_name.name AS finished_product_name'),
                DB::raw('im_name.name AS intermediate_product_name'),
                'finished_product_category.batch_qty',
                'finished_product_category.unit_batch_qty',
                'finished_product_category.deparment_code',
                'market.name as market_name',
                'specification.name as specification',
                DB::raw("CASE
                        WHEN plan_master.cancel = 1 THEN 'Hủy'
                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = sp_possible.max_possible_stage_code THEN 'Hoàn Tất'
                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 1 THEN 'Đã Cân'
                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 3 THEN 'Đã Pha chế'
                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 4 THEN 'Đã THT'
                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 5 THEN 'Đã định hình'
                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 6 THEN 'Đã Bao phim'
                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 7 THEN 'Hoàn Tất ĐG'
                        ELSE 'Chưa làm' END AS status")
            )
            ->whereIn('plan_master.id', $planMasterIds)
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
            ->leftJoin('product_name as fp_name', 'finished_product_category.product_name_id', '=', 'fp_name.id')
            ->leftJoin('product_name as im_name', 'intermediate_category.product_name_id', '=', 'im_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
            ->leftJoin('specification', 'finished_product_category.specification_id', '=', 'specification.id')
            ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                $join->on('plan_master.main_parkaging_id', '=', 'sp_max.plan_master_id');
            })
            ->leftJoinSub($maxPossibleStage, 'sp_possible', function ($join) {
                $join->on('plan_master.main_parkaging_id', '=', 'sp_possible.plan_master_id');
            })
            ->leftJoin('stage_plan', function ($join) {
                $join->on('plan_master.main_parkaging_id', '=', 'stage_plan.plan_master_id')
                        ->on('stage_plan.stage_code', '=', 'sp_max.max_stage_code');
            });

        if ($ic_id) {
            $ic = DB::table('intermediate_category')->where('id', $ic_id)->first();
            if ($ic) {
                $query->where('finished_product_category.intermediate_code', $ic->intermediate_code);
            }
        }

        $datas = $query->orderBy('plan_master.created_at', 'desc')->get();
        $html = view('pages.plan.validation_tracking.plan_masters_table', compact('datas'))->render();

        return response()->json(['html' => $html]);
    }
}
