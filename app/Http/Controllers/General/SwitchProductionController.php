<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SwitchProductionController extends Controller{
    public function switchProduction(Request $request){
        
       
        $production = DB::table ('production')->where ('code',$request->production_code??"PXV1")->first();
        $user = $request->session()->get('user', []);

       
        $request->session()->put('user', [
            'userId'          => $user['userId']?? null,
            'userName'        => $user['userName'] ?? null,
            'fullName'        => $user['fullName'] ?? null,
            'userGroup'       => $user['userGroup'] ?? null,
            'department'      => $user['department'] ?? null,
            'passWord'        => $user['passWord'] ?? null,
            'production_code' => $production->code,
            'production_name' => $production->name,
        ]);
                
    session()->put(['title'=> 'KẾ HOẠCH SẢN XUẤT']);
     // Nếu có redirect URL thì quay lại đó
    if ($request->has('redirect')) {
        return redirect($request->redirect);
    }
    return view('pages.general.home');}
}
