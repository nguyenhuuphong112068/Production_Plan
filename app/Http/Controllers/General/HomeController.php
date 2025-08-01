<?php

namespace App\Http\Controllers\General;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller{
    public function showHomeForm()
    {
    session()->put(['title'=> 'KẾ HOẠCH KIỂM NGHIỆM']);
    return view('pages.general.home');}
}
