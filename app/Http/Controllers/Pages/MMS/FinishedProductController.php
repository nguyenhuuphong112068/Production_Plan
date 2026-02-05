<?php

namespace App\Http\Controllers\Pages\MMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinishedProductController extends Controller
{
        public function index(){
              
                $datas = DB::connection('sqlsrv_mms')->table('yfFG_StockOverview')->get();
                //dd ($datas);
                session()->put(['title'=> 'TỒN KHO THÀNH PHẨM']);
            
                return view('pages.MMS.finished_product.list',['datas' => $datas]);
        }
}
