<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class WeeklyReportController extends Controller
{
        public function index(Request $request) {
           
            $department = DB::table('user_management')->where('userName', session('user')['userName'])->value('deparment');



            session()->put(['title' => "BÁO CÁO TUẤN "]);
                
            return view('pages.report.weekly_report.list', [
               
            ]);

    }
}
