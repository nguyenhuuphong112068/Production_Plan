<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ABCDController extends Controller
{
        public function index(Request $request) {

           
            session()->put(['title' => "PHÂN CÔNG CÔNG VIỆC"]);
                
            return view('pages.assignment.production.list', [
                
            ]);

    }
}
