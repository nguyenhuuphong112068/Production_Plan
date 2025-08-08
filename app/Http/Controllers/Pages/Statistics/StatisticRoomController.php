<?php

namespace App\Http\Controllers\Pages\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatisticRoomController extends Controller
{
        public function index(){
              
                $datas = []; // DB::table('user_management')->where ('isActive',1)->orderBy('created_at','desc')->get();
                
                session()->put(['title'=> 'THỐNG KÊ THỜI GIAN HOẠT ĐỘNG PHÒNG SẢN XUẤT']);
           
                return view('pages.statistics.room.list',['datas' => $datas]);
        }
}
