<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ABCDController extends Controller
{
        public function index(Request $request) {

            dd ("sa");
            session()->put(['title' => "BÁO CÁO NGÀY"]);
                
            return view('pages.report.daily_report.list', [
                
            ]);

    }
}
