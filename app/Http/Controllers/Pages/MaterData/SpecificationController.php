<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpecificationController extends Controller
{
            public function index(){
                dd ("SpecificationController");
                $datas = DB::table('dosage')->orderBy('name','asc')->get();
                session()->put(['title'=> 'Dạng Bào Chế']);
                return view('pages.materData.Dosage.list',['datas' => $datas]);
        }
}
