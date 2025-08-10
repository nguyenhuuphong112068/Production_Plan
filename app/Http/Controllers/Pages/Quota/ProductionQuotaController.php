<?php

namespace App\Http\Controllers\Pages\Quota;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionQuotaController extends Controller
{
        public function index(Request $request ){
                //dd ( $request->all());
                $stage_code = $request->stage_code?? 1;
                $production = session('user')['production'];
                $datas = DB::table('quota')
                ->select(
                        'quota.id',
                        'quota.intermediate_code',
                        'quota.finished_product_code',
                        'quota.room_id',
                        'quota.p_time',
                        'quota.m_time',
                        'quota.C1_time',
                        'quota.C2_time',
                        'quota.stage_code',
                        'quota.maxofbatch_campaign',
                        'quota.deparment_code',
                        'quota.note',
                        'quota.active',
                        'quota.created_at',
                        'quota.prepared_by',
                        'room.name as room_name',
                        'room.code as room_code',
                        'finished_product_category.name as finished_product_name',
                        'intermediate_category.name as intermediate_name',
                        'intermediate_category.name as batch_qty',
                        'intermediate_category.name as unit_batch_qty'
                )
                ->where('quota.active', 1)
                ->where('quota.stage_code', $stage_code)
                ->where('quota.deparment_code', $production)
                ->leftJoin('room', 'quota.room_id', '=', 'room.id')
                ->leftJoin('finished_product_category', 'quota.finished_product_code', '=', 'finished_product_category.finished_product_code')
                ->leftJoin('intermediate_category', 'quota.intermediate_code', '=', 'intermediate_category.intermediate_code')
                ->orderBy('quota.created_at', 'desc')
                ->get();
               // dd ($datas);
               
                session()->put(['title'=> 'Định Mức Sản Xuất']);
                return view('pages.quota.production.list',['datas' => $datas, 'stage_code' => $stage_code ]);
        }
}
