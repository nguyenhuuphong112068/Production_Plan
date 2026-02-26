<?php

namespace App\Http\Controllers\Pages\MMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
        public function index(){


                $datas = DB::connection('mms')
                        ->table('yf_RMPMStockOverview_pms as s')
                        ->where('s.MatTY1', 'RA')

                ->get();

                session()->put(['title'=> 'TỒN KHO NGUYÊN LIỆU']);
            
                return view('pages.MMS.material.list',['datas' => $datas]);
        }
}
