<?php

namespace App\Http\Controllers\Pages\MMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BOMController extends Controller
{
        public function index(){
              
                $datas = DB::connection('sqlsrv_mms')->table('yfBOM_BOMItemHP')->get();
                dd ($datas->first());
                session()->put(['title'=> 'CÔNG THỨC HIỆN HÀNH']);
            
                return view('pages.MMS.BOM.list',['datas' => $datas]);
        }
}
