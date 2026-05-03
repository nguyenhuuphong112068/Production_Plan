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
                DB::raw('GROUP_CONCAT(DISTINCT fpm.created_by) as mold_created_by')
            )
            ->leftJoin('product_name as pn', 'fpc.product_name_id', '=', 'pn.id')
            ->leftJoin('market as m', 'fpc.market_id', '=', 'm.id')
            ->leftJoin('specification as s', 'fpc.specification_id', '=', 's.id')
            ->leftJoin('finished_product_mold as fpm', 'fpc.id', '=', 'fpm.finished_product_category_id')
            ->where('fpc.active', 1)
            ->where('fpc.cancel', 0)
            ->groupBy('fpc.id', 'fpc.finished_product_code', 'fpc.batch_qty', 'fpc.unit_batch_qty', 'pn.name', 'm.name', 's.name')
            ->get();

        // Get all available molds for the multi-select
        $molds = DB::table('blister_mold')
            ->where('active', 1)
            ->select('id', 'code', 'name')
            ->get();

        session()->put(['title' => 'ĐỊNH MỨC - KHUÔN MẪU']);

        return view('pages.quota.mold.list', [
            'datas' => $datas,
            'molds' => $molds
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
