<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SchedualController extends Controller
{
    public function view(){
       
        session()->put(['title' => 'Lịch Kiểm Nghiệm']);

        $events = DB::table('schedule')
                        ->select('id',
                                'title',
                                'start',
                                'end',
                                'room_id as resourceId')
                        ->where('finished', 0)
                        ->where('active', 1)
                        ->get();

        $resources = DB::table('room')
                        ->select('id', DB::raw("CONCAT(name, '-', code) as title"))
                        ->where('active', 1)
                        ->orderBy('order_by', 'asc')
                        ->get();
  
        return Inertia::render('FullCalender', [
      
            'title' => 'Lịch Kiểm Nghiệm',
            'user' => session('user'),
            'events' => $events,
            'resources' => $resources
        ]);
    }

    public function index(){

                $analysts = DB::table('analyst')->where ('active',1)->orderBy('created_at','desc')->get();
                $instruments = DB::table('instrument')->where ('active',1)->orderBy('created_at','desc')->get();
   
                $imports = DB::table('import')
                ->select('import.*', 'product_category.name', 'product_category.code', 'product_category.testing','product_category.testing_code', 
                        'product_category.sample_Amout', 'product_category.unit', 'product_category.excution_time','product_category.instrument_type',)
                ->where ('import.Active',1)->where('import.finished',0)->where('import.scheduled',0)
                ->leftJoin('product_category', 'import.testing_code', 'product_category.testing_code')
                ->orderBy('experted_date','asc')->get();
                
                $datas = DB::table('schedules')
                    ->select(
                        'schedules.*',
                        'product_category.name',
                        'product_category.code',
                        'product_category.testing',
                        'import.batch_no',
                        'import.experted_date',
                        'import.stage',
                        'import.imoported_amount',
                        'product_category.unit',
                        'import.id as imported_id',
                        'instrument.name as ins_name',
                        'product_category.excution_time',
                        
                    )
                    ->where('schedules.finished', 0)->where('schedules.active', 1)
                    ->leftJoin('import', 'schedules.imported_id', '=', 'import.id')
                    ->leftJoin('instrument', 'schedules.ins_Id', '=', 'instrument.id')
                    ->leftJoin('product_category', 'import.testing_code', '=', 'product_category.testing_code')
                    ->get();
        
                session()->put(['title'=> 'Danh Sách Mẫu Chờ Kiểm']);
       
                return view('pages.Schedual.list',['datas' => $datas,'imports' => $imports, 'instruments' => $instruments,  'analysts' => $analysts])
                ->with('instrument_type', request()->get('instrument_type'));;
        }
    

        public function store (Request $request) {
                
                $validator = Validator::make($request->all(), [
                        'analyst'    => 'required',
                        'startDate'  => 'required|date',
                        'endDate'    => 'required|date|after_or_equal:startDate',
                        'ins_Id' => 'required',
                        'imported_id'=> 'required'
                ], [
                        'analyst.required' => 'Vui lòng chọn kiểm nghiệm viên',
                        'startDate.required' => 'Vui lòng chọn ngày kiểm',
                        'endDate.required' => 'Vui lòng chọn ngày kết thúc',
                        'ins_Id.required' => 'Vui lòng chọn thiết bị kiểm',
                        'imported_id.required' => 'Không có sản phẩm được chọn',
                ]);
               

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                
              
                DB::table('schedules')->insert([
                        
                        'imported_id' => $request->imported_id,
                        'analyst' => $request->analyst,
                        'startDate' => $request->startDate,
                        'endDate'  => $request->endDate,
                        'ins_Id'  => $request->ins_Id,
                        'note'  => $request->note,
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);

                DB::table('import')->where('id', $request->imported_id)->update(['scheduled' => 1]);

                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){

                
                try {
                        DB::table('schedule')
                        ->where('id', $request->id)
                        ->update([
                                'start' => $request->start,
                                'end' => $request->end,
                                'room_id' => $request->resourceId,
                                'updated_at' => now(),
                        ]);  
                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);       
                }
        }

        public function deActive(string|int $id, Request $request){
               
               DB::table('schedules')->where('id', $id)->update([
                        'active' => 0,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                DB::table('import')->where('id', $request->imported_id)->update(['scheduled' => 0]);

                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }

        public function finished(Request $request){
               
                $validator = Validator::make($request->all(), [
                        'analyst'    => 'required',
                        'startDate'  => 'required|date',
                        'endDate'    => 'required|date|after_or_equal:startDate',
                        'ins_Id' => 'required',
                        'schedual_id'=> 'required',
                        'result'=> 'required',
                        'relativeReport' => 'required'
                ], [
                        'analyst.required' => 'Vui lòng chọn kiểm nghiệm viên',
                        'startDate.required' => 'Vui lòng chọn ngày kiểm',
                        'endDate.required' => 'Vui lòng chọn ngày kết thúc',
                        'ins_Id.required' => 'Vui lòng chọn thiết bị kiểm',
                        'schedual_id.required' => 'Không có sản phẩm được chọn',
                        'result.required' => 'Vui lòng chọn kết quả',
                        'relativeReport.required' => 'Vui lòng nhập số báo cáo liên quan, nếu không nhập NA'
                ]);
      

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createHistoryErrors')->withInput();
                }
                
              
                $check= DB::table('history')->insert([
                        
                        'schedual_id' => $request->schedual_id,
                        'analyst' => $request->analyst,
                        'startDate' => $request->startDate,
                        'endDate'  => $request->endDate,
                        'ins_Id'  => $request->ins_Id,
                        'note'  => $request->note,
                        'result'  => $request->result,
                        'relativeReport'  => $request->relativeReport,
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
                        'created_at' => now(),
                ]);
                
                if ($check){
                        DB::table('import')->where('id', $request->imported_id)->update(['finished' => 1]);
                        DB::table('schedules')->where('id', $request->schedual_id)->update(['finished' => 1]);

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
                else {
                        return redirect()->back()->withErrors($validator, 'createHistoryErrors');
                }


            
        }





}
