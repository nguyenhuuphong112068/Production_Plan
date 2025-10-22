<?php

namespace App\Http\Controllers\Pages\Quarantine;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuarantineRoomController extends Controller
{
        // public function index(Request $request){

        //     $stage_code = $request->stage_code ?? 1;
        //     $production = session('user')['production_code'];
                
        //         // map stage -> column boolean trong intermediate_category
        //     $subquery = DB::table('stage_plan as sp1')
        //         ->select('sp1.plan_master_id', DB::raw('MAX(sp1.stage_code) as max_stage_code'))
        //         ->where('sp1.finished', 1)
        //         ->where('sp1.active', 1)
        //         ->whereNotNull('sp1.yields')
        //         ->groupBy('sp1.plan_master_id');

        //     $datas = DB::table('quarantine_room as qr')
        //         ->leftJoin('stage_plan as sp', function($join) {
        //             $join->on('qr.code', '=', 'sp.quarantine_room_code');
        //         })
        //         ->leftJoinSub($subquery, 'sub', function ($join) {
        //             $join->on('sub.plan_master_id', '=', 'sp.plan_master_id')
        //                 ->on('sub.max_stage_code', '=', 'sp.stage_code');
        //         })
        //         ->select(
        //             'qr.code',
        //             'qr.name',
        //             'qr.deparment_code',
        //             DB::raw('COALESCE(SUM(sp.yields), 0) as total_yields')
        //         )
        //         ->where('qr.deparment_code', $production)
        //         ->where('qr.active', true)
        //         ->groupBy('qr.code', 'qr.name', 'qr.deparment_code')
        //         ->get();


        //         dd ($datas);
                        
             
        //         session()->put(['title' => 'BIỆT TRỮ BÁN THÀNH PHẨM']);               
        //         return view('pages.quarantine.room.list', [
        //                 'datas' => [], // $datas,
        //                 'stage_code' => $stage_code,
        //         ]);
        // }

        public function index(Request $request){
            $stage_code = $request->stage_code ?? 1;
            $production = session('user')['production_code'];

            // Subquery: lấy stage cao nhất đã hoàn thành cho từng plan_master_id
       $subquery = DB::table('stage_plan as sp1')
    ->select('sp1.plan_master_id', DB::raw('MAX(sp1.stage_code) as max_stage_code'))
    ->where('sp1.finished', 1)
    ->where('sp1.active', 1)
    ->groupBy('sp1.plan_master_id');

$details = DB::table('quarantine_room as qr')
    ->leftJoin('stage_plan as sp', 'qr.code', '=', 'sp.quarantine_room_code')
    ->leftJoinSub($subquery, 'sub', function ($join) {
        $join->on('sub.plan_master_id', '=', 'sp.plan_master_id')
             ->on('sub.max_stage_code', '=', 'sp.stage_code');
    })
    ->leftJoin('plan_master as pm', 'pm.id', '=', 'sp.plan_master_id')
    ->leftJoin('finished_product_category as fpc', 'fpc.id', '=', 'sp.product_caterogy_id') // ✅ nối bảng category
    ->leftJoin('product_name as pn', 'pn.id', '=', 'fpc.product_name_id') // ✅ nối bảng tên sản phẩm
    ->where('qr.deparment_code', $production)
    ->where('qr.active', true)
    ->whereNotNull('sub.max_stage_code')
    ->select(
        'qr.code',
        'qr.name',
        'qr.deparment_code',
        'sp.plan_master_id',
        'sp.stage_code',
        'sp.finished',
        'sp.yields',
        'sp.start',
        'sp.end',
        'pm.batch',
        'pn.name as product_name' // ✅ tên sản phẩm cuối cùng
    )
    ->orderBy('qr.code')
    ->get();

$datas = $details->groupBy('code')->map(function ($items) {
    return [
        'code'           => $items->first()->code,
        'name'           => $items->first()->name,
        'deparment_code' => $items->first()->deparment_code,
        'total_yields'   => $items->sum('yields'),
        'details'        => $items->map(function ($item) {
            return [
                'plan_master_id' => $item->plan_master_id,
                'batch'          => $item->batch,
                'product_name'   => $item->product_name, // ✅ hiển thị tên sản phẩm
                'stage_code'     => $item->stage_code,
                'yields'         => $item->yields,
                'start'          => $item->start,
                'end'            => $item->end,
            ];
        })->values(),
    ];
})->values();

dd($datas);


dd($datas);
 // loại bỏ key groupBy để trả về dạng array



            // Debug nếu cần
           

            session()->put(['title' => 'BIỆT TRỮ BÁN THÀNH PHẨM']);               

            return view('pages.quarantine.room.list', [
                'datas' => $datas,
            ]);
        }

}
