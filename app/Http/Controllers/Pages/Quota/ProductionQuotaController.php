<?php

namespace App\Http\Controllers\Pages\Quota;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionQuotaController extends Controller
{
        public function index(Request $request ){
                $stage_code = $request->stage_code?? 1;
                $production = $request->ptodution?? "PXV1";

        
                $datas = DB::table('quota')
                ->select ('quota.*',
                          'room.name',
                          'room.code',
                          'finished_product_category.name as finished_product_name' ,
                           'intermediate_category.name as intermediate_name',


                )
                ->where ('quota.active',1)->where ('quota.stage_code',$stage_code)->where ('quota.deparment_code',$production)
                ->leftJoin('room', 'quota.instrument_id', 'room.id')
                ->leftJoin('finished_product_category', 'quota.finished_product_code', 'finished_product_category.finished_product_code')
                ->leftJoin('intermediate_category', 'quota.intermediate_code', 'intermediate_category.intermediate_code')
                ->orderBy('created_at','desc')->get();

                dd ($datas);
                session()->put(['title'=> 'Định Mức Sản Xuất']);
        
                return view('pages.quota.production.list',['datas' => $datas ]);
        }


}
