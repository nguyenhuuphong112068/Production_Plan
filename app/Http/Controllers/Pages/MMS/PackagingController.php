<?php

namespace App\Http\Controllers\Pages\MMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackagingController extends Controller
{
        public function index(){
  
                $datas = DB::connection('mms')
                        ->table('yf_RMPMStockOverview_pms as s')
                        ->where('s.MatTY1', 'PS')

                ->get();
                
                session()->put(['title'=> 'TỒN KHO BAO Bì']);
            
                return view('pages.MMS.packaging.list',['datas' => $datas]);
        }
}
