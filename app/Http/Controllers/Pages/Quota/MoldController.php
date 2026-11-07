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
        // Chỉ khuôn ép vỉ (ĐG); khuôn Định Hình khai báo theo BTP ở trang forming()
        $molds = DB::table('blister_mold')
            ->where('active', 1)
            ->where('stage_code', 7)
            ->where('deparment_code', session('user')['production_code'])
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

        session()->put(['title' => 'ĐỊNH MỨC - KHUÔN EV']);

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
            $mold_ids = array_values(array_filter((array) ($request->mold_ids ?? []))); // This should be an array from the multi-select

            // Chỉ nhận khuôn ép vỉ đang hoạt động của phân xưởng đang chọn
            $validCount = DB::table('blister_mold')
                ->whereIn('id', $mold_ids ?: [0])
                ->where('stage_code', 7)
                ->where('active', 1)
                ->where('deparment_code', session('user')['production_code'])
                ->count();
            if ($validCount !== count($mold_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có khuôn không phải khuôn ép vỉ của phân xưởng hoặc đã ngưng hoạt động'
                ], 422);
            }

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

    /**
     * Định mức khuôn Định Hình (CĐ 5) theo BTP. Chỉ liệt kê BTP có định mức sản xuất
     * ở phòng ĐH đã khai Loại Máy (room.blister_type_code) – phòng có kiểm soát khuôn.
     */
    public function forming()
    {
        $production = session('user')['production_code'];

        $datas = DB::table('intermediate_category as ic')
            ->join('quota as q', function ($join) {
                $join->on('ic.intermediate_code', '=', 'q.intermediate_code')
                    ->where('q.stage_code', '=', 5)
                    ->where('q.active', '=', 1);
            })
            ->join('room as r', function ($join) {
                $join->on('q.room_id', '=', 'r.id')
                    ->whereNotNull('r.blister_type_code');
            })
            ->leftJoin('product_name as pn', 'ic.product_name_id', '=', 'pn.id')
            ->leftJoin('intermediate_mold as im', function ($join) use ($production) {
                // Chỉ hiện khuôn của phân xưởng đang chọn
                $join->on('ic.intermediate_code', '=', 'im.intermediate_code')
                    ->whereIn('im.blister_mold_id', DB::table('blister_mold')->where('deparment_code', $production)->select('id'));
            })
            ->where('ic.active', 1)
            ->where('ic.deparment_code', $production)
            ->select(
                'ic.id',
                'ic.intermediate_code',
                'ic.batch_size',
                'ic.unit_batch_size',
                'pn.name as product_name',
                DB::raw('GROUP_CONCAT(DISTINCT im.blister_mold_id) as mold_ids'),
                DB::raw('MAX(im.created_at) as mold_created_at'),
                DB::raw('GROUP_CONCAT(DISTINCT im.created_by) as mold_created_by'),
                DB::raw("GROUP_CONCAT(DISTINCT r.code ORDER BY r.code SEPARATOR ', ') as quota_rooms"),
                DB::raw('GROUP_CONCAT(DISTINCT r.blister_type_code) as room_types')
            )
            ->groupBy('ic.id', 'ic.intermediate_code', 'ic.batch_size', 'ic.unit_batch_size', 'pn.name')
            ->orderBy('ic.intermediate_code')
            ->get();

        $molds = DB::table('blister_mold')
            ->where('active', 1)
            ->where('stage_code', 5)
            ->where('deparment_code', $production)
            ->orderBy('code')
            ->select('id', 'code', 'amount', 'blister_type_code')
            ->get();

        // Loại máy lắp được khuôn: để lọc khuôn theo loại máy của phòng đã định mức
        $typeNames = DB::table('blister_type')->pluck('name', 'code');
        foreach ($molds as $mold) {
            $codes = json_decode((string) $mold->blister_type_code, true);
            if (! is_array($codes)) {
                $codes = $mold->blister_type_code !== null && $mold->blister_type_code !== '' ? [$mold->blister_type_code] : [];
            }
            $mold->blister_type_code = array_values(array_map('strval', $codes));
            $mold->type_name = collect($codes)->map(fn ($c) => $typeNames[$c] ?? $c)->join(', ');
        }

        session()->put(['title' => 'ĐỊNH MỨC - KHUÔN ĐỊNH HÌNH']);

        return view('pages.quota.mold.forming', [
            'datas' => $datas,
            'molds' => $molds,
        ]);
    }

    public function formingUpdate(Request $request)
    {
        $production = session('user')['production_code'];
        $intermediateCode = $request->intermediate_code;
        $moldIds = array_filter((array) ($request->mold_ids ?? []));

        if (empty($intermediateCode)) {
            return response()->json(['success' => false, 'message' => 'Thiếu mã BTP'], 422);
        }

        // Chỉ nhận khuôn Định Hình đang hoạt động
        $validIds = DB::table('blister_mold')
            ->whereIn('id', $moldIds ?: [0])
            ->where('stage_code', 5)
            ->where('active', 1)
            ->where('deparment_code', $production)
            ->pluck('id')
            ->all();

        if (count($validIds) !== count($moldIds)) {
            return response()->json(['success' => false, 'message' => 'Có khuôn không phải khuôn Định Hình của phân xưởng hoặc đã ngưng hoạt động'], 422);
        }

        try {
            DB::transaction(function () use ($intermediateCode, $validIds, $production) {
                // Chỉ thay khuôn của phân xưởng đang chọn, không đụng khai báo của phân xưởng khác
                DB::table('intermediate_mold')
                    ->where('intermediate_code', $intermediateCode)
                    ->whereIn('blister_mold_id', DB::table('blister_mold')->where('deparment_code', $production)->select('id'))
                    ->delete();

                $by = session('user')['fullName'] ?? 'Admin';
                $rows = array_map(fn ($id) => [
                    'intermediate_code' => $intermediateCode,
                    'blister_mold_id' => $id,
                    'created_by' => $by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $validIds);

                if ($rows) {
                    DB::table('intermediate_mold')->insert($rows);
                }
            });

            return response()->json(['success' => true, 'message' => 'Cập nhật khuôn Định Hình thành công']);
        } catch (\Exception $e) {
            Log::error('Error updating intermediate mold: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
