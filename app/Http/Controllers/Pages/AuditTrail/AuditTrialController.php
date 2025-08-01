<?php

namespace App\Http\Controllers\Pages\AuditTrail;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AuditTrialController extends Controller
{
    public function index(){

        $datas = DB::table('audittriallog')
        ->select('audittriallog.*', 'user_management.fullName')
        ->join('user_management', 'audittriallog.userName', '=', 'user_management.userName')
        ->orderBy('created_at','desc')->get();
                
        session()->put(['title'=> 'Audit Trial Log']);
           
        return view('pages.AuditTrail.list',['datas' => $datas]);
    }

    public static function log($action, $table, $recordId, $old = null, $new = null)
    {
        
        DB::table('audittriallog')->insert([
            'userName'     =>  session('user')['userName'] ?? 'NA',
            'action'      => $action,
            'table_Audit'       => $table,
            'record_Id_AuditTrial'    => $recordId,
            'old_values'  => $old,
            'new_values'  => $new,
            'ip_address'  => request()->ip(),
            'created_at'  => now(),
        ]);
    }


}
