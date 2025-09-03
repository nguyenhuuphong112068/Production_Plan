<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
        public function index(){
               
                $datas = DB::table('Market')->orderBy('name','asc')->get();
                session()->put(['title'=> 'Dữ Liệu Gốc Thị Trường']);
                return view('pages.materData.Market.list',['datas' => $datas]);
        }
}
