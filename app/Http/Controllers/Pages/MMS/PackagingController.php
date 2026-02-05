<?php

namespace App\Http\Controllers\Pages\MMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackagingController extends Controller
{
        public function index(){
              
                $datas = DB::connection('sqlsrv_mms')->table('yf_RMPMStockOverview')->where ('MatTY1', 'PS')->get();
                //dd ($datas->first());
                session()->put(['title'=> 'TỒN KHO BAO Bì']);
            
                return view('pages.MMS.packaging.list',['datas' => $datas]);
        }
}
