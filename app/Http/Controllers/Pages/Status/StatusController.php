<?php

namespace App\Http\Controllers\Pages\Status;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
        public function index(){
                $production =  session('user')['production_code'];
                $datas = DB::table('room')
                ->where ('active',1)
                ->where ('deparment_code',$production)
                ->orderBy('order_by','asc')
                ->get();
        
                session()->put(['title'=> "TRANG THÁI PHÒNG SẢN XUẤT"]);
              
                return view('pages.status.list',[
                        'datas' =>  $datas 
                        
                ]);
        }
}
