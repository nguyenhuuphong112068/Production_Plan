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
        $trackings = ValidationTracking::with(['intermediateCategories.intermediateCategory.productName'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.plan.validation_tracking.list', compact('trackings'));
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
    public function checkValidation(Request $request)
    {
        $ic_id = $request->intermediate_category_id;
        if (!$ic_id) {
            return response()->json([]);
        }

        $trackings = ValidationTrackingIntermediateCategory::with('validationTracking')
            ->where('intermediate_category_id', $ic_id)
            ->whereColumn('num_of_finished_batch', '<', 'num_of_tracking_batch')
            ->whereHas('validationTracking', function($q) {
                $q->where('status', 'Đang theo dõi');
            })
            ->get();

        return response()->json($trackings);
    }
}

