<?php

namespace App\Http\Controllers\Pages\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatisticProductController extends Controller
{
            public function index(){
               
                $datas = []; // DB::table('user_management')->where ('isActive',1)->orderBy('created_at','desc')->get();
                
                session()->put(['title'=> 'THÔNG KÊ SẢN LƯỢNG THEO SẢN PHẤM']);
           
                return view('pages.statistics.product.list',['datas' => $datas]);
            }
}
