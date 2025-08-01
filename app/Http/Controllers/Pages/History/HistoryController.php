<?php

namespace App\Http\Controllers\Pages\History;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class HistoryController extends Controller
{
        public function index(){
       
               $datas = DB::table('history')
                ->select(
                        'history.*',
                        'product_category.code','product_category.name',
                        'product_category.testing',
                        'import.batch_no','import.stage',
                        'instrument.name as instrument_name'
                )
                ->join('schedules', 'history.schedual_id', '=', 'schedules.id')
                ->join('import', 'schedules.imported_id', '=', 'import.id')
                ->join('product_category', 'import.testing_code', '=', 'product_category.testing_code')
                ->join('instrument', 'history.ins_id', '=', 'instrument.id')
                ->get();

                session()->put(['title'=> 'Lịch Sử Kiểm Nghiệm']);

                return view('pages.History.list',['datas' => $datas])->with('success', 'Thành công!');

        }
    
}
