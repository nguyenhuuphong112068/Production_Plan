<?php

namespace App\Http\Controllers\Pages\Quota;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MoldController extends Controller
{
    public function index()
    {
        // Get all finished products and join with market and specification
        $datas = DB::table('finished_product_category as fpc')
            ->select(
                'fpc.id',
                'fpc.finished_product_code',
                'fpc.batch_qty',
                'fpc.unit_batch_qty',
                'pn.name as product_name',
                'm.name as market_name',
                's.name as specification_name',
                DB::raw('GROUP_CONCAT(fpm.blister_mold_id) as mold_ids'),
                DB::raw('MAX(fpm.created_at) as mold_created_at'),
                DB::raw('GROUP_CONCAT(DISTINCT fpm.created_by) as mold_created_by'),
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(r.name, "|", IFNULL(r.blister_type_code, "")) SEPARATOR "::") as quota_rooms')
            )
            ->leftJoin('product_name as pn', 'fpc.product_name_id', '=', 'pn.id')
            ->leftJoin('market as m', 'fpc.market_id', '=', 'm.id')
            ->leftJoin('specification as s', 'fpc.specification_id', '=', 's.id')
            ->leftJoin('finished_product_mold as fpm', 'fpc.id', '=', 'fpm.finished_product_category_id')
            ->leftJoin('quota as q', function ($join) {
                $join->on('fpc.finished_product_code', '=', 'q.finished_product_code')
                    ->on('fpc.intermediate_code', '=', 'q.intermediate_code')
                    ->where('q.stage_code', '=', 7)
                    ->where('q.active', '=', 1);
            })
            ->leftJoin('room as r', 'q.room_id', '=', 'r.id')
            ->where('fpc.active', 1)
            ->where('fpc.cancel', 0)
            ->where('fpc.deparment_code', session('user')['production_code'])
            ->groupBy('fpc.id', 'fpc.finished_product_code', 'fpc.batch_qty', 'fpc.unit_batch_qty', 'pn.name', 'm.name', 's.name')
            ->get();

        // Get all available molds for the multi-select
        $blister_types = DB::table('blister_type')->where('active', true)->get();
        $molds = DB::table('blister_mold')
            ->where('active', 1)
            ->select('id', 'code', 'blister_type_code')
            ->get();

        foreach ($molds as $mold) {
            $codes = json_decode($mold->blister_type_code, true);
            if (!is_array($codes)) {
                $codes = [$mold->blister_type_code];
            }
            $mold->blister_type_code = $codes;
            $mold->type_name = $blister_types->whereIn('code', $codes)->pluck('name')->join(', ');
        }

        session()->put(['title' => 'ĐỊNH MỨC - KHUÔN MẪU']);

        return view('pages.quota.mold.list', [
            'datas' => $datas,
            'molds' => $molds,
            'blister_types' => $blister_types
        ]);
    }

    public function update(Request $request)
    {
        try {
            $fpc_id = $request->id;
            $mold_ids = $request->mold_ids ?? []; // This should be an array from the multi-select

            DB::beginTransaction();

            // Delete existing relations
            DB::table('finished_product_mold')
                ->where('finished_product_category_id', $fpc_id)
                ->delete();

            // Insert new relations
            if (!empty($mold_ids)) {
                $insertData = [];
                foreach ($mold_ids as $mold_id) {
                    $insertData[] = [
                        'finished_product_category_id' => $fpc_id,
                        'blister_mold_id' => $mold_id,
                        'created_by' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('finished_product_mold')->insert($insertData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật khuôn mẫu thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating product mold: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
