<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MaintenancePlanController extends Controller
{
       public function index(){
           
                $datas = DB::table('plan_list')
                ->where ('active',1)
                ->where ('type',0)
                ->orderBy('created_at','desc')->get();

                session()->put(['title'=> 'KẾ HOẠCH BẢO TRÌ THÁNG']);
        
                return view('pages.plan.maintenance.plan_list',[
                        'datas' => $datas
                ]);
        }
        
        public function create_plan_list (Request $request) {
                       
                 DB::table('plan_list')->insert([
                        'name' => $request->name,
                        'month' => date('m'),
                        'type' => 0,
                        'send' => false,
                        'deparment_code'  => session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', "Tạo Mới $request->name Thành Công!");

        }

        public function open(Request  $request){
                
                $datas = DB::table('plan_master')
                ->select(
                        'plan_master.*',
                        'maintenance_category.code',
                        'maintenance_category.name',
                        'room.name as room_name',
                        'room.code as room_code'
                )
                ->where('plan_list_id', $request->plan_list_id)
                ->where('plan_master.active', 1)
                ->leftJoin('maintenance_category', 'plan_master.product_caterogy_id', '=', 'maintenance_category.id')
                ->leftJoin('room', 'maintenance_category.room_id', '=', 'room.id')
                ->orderBy('level', 'asc')
                ->orderBy('expected_date', 'asc')
                ->get()
                ->groupBy('id')   // group theo id của plan_master
                ->map(function ($items) {
                        $first = $items->first();

                        // Danh sách room_code - room_name
                        $first->rooms = $items->map(function ($item) {
                        return $item->room_code . ' - ' . $item->room_name;
                        })
                        ->filter()
                        ->unique()
                        ->implode(', ');

                        return $first;
                })
                ->values();

                

                $planMasterIds = $datas->pluck('id')->toArray();

                $historyCounts = DB::table('plan_master_history')
                        ->select('plan_master_id', DB::raw('COUNT(*) as total'))
                        ->whereIn('plan_master_id', $planMasterIds)
                        ->groupBy('plan_master_id')
                        ->pluck('total', 'plan_master_id')
                        ->toArray();
                $datas = $datas->map(function($item) use ($historyCounts) {
                        $item->history_count = $historyCounts[$item->id] ?? 0; // nếu không có history thì = 0
                        return $item;
                        });
               
                $maintenance_category = DB::table('maintenance_category')
                ->select(
                        'maintenance_category.*',
                        'room.name as room_name',
                        'room.code as room_code',
                        'room.id as room_id'
                )
                ->leftJoin('room', 'maintenance_category.room_id', '=', 'room.id')
                ->where('maintenance_category.active', 1)
                ->orderBy('maintenance_category.name', 'asc')
                ->get()
                ->groupBy('code')
                ->map(function ($items) {
                        $first = $items->first(); // lấy bản ghi gốc

                        // Danh sách room_code - room_name
                        $first->rooms = $items->map(function ($item) {
                                return $item->room_code . ' - ' . $item->room_name;
                        })
                        ->filter()
                        ->unique()
                        ->implode(', ');

                        // Danh sách maintenance_category_id -> chuỗi ngăn cách bằng dấu phẩy
                        $first->maintenance_category_ids = $items->pluck('id')
                        ->filter()
                        ->unique()
                        ->implode(',');

                        return $first;
                })
                ->values();

              

                $production  =  session('user')['production_name'];
                session()->put(['title'=> " $request->name - $production"]);
        
                return view('pages.plan.maintenance.list',[
                        'datas' => $datas, 
                        'plan_list_id' => $request->plan_list_id,
                        'month' => $request->month, 
                        'production' => $request->production,
                        'category' => $maintenance_category,
                        'send'=> $request->send??1,
                        
                ]);
        }

       public function store(Request $request){
               
                $validator = Validator::make($request->all(), [
                        'expected_date' => 'required',             
                ], [
                        'expected_date' => 'Vui lòng chọn ngày dự kiến KCS',  
                ]);
                dd ('tới đây, sửa lại để tạo nhiều event theo ids');
                if ($validator->fails()) {
                        return redirect()->back()
                        ->withErrors($validator, 'create_Errors')
                        ->withInput();
                }

                        // Insert vào plan_master
                        $planMasterId = DB::table('plan_master')->insertGetId([
                        "product_caterogy_id" => $request->product_caterogy_id,
                        "plan_list_id" => $request->plan_list_id,
                        "batch" => "NA",
                        "expected_date" => $request->expected_date,
                        "level" => 1,
                        "is_val" => 0,
                        "percent_parkaging" => 1,
                        "only_parkaging" => 0,
                        "note" => $request->note ?? "NA",
                        'deparment_code'=> session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                        ]);
                        
                        // Insert vào plan_master_history
                        $checked = DB::table('plan_master_history')->insert([
                        "plan_master_id" => $planMasterId,
                        "plan_list_id" => $request->plan_list_id,
                        "product_caterogy_id" => $request->product_caterogy_id,
                        "batch" => "NA",
                        "expected_date" => $request->expected_date,
                        "level" => 1,
                        "is_val" => 0,
                        "percent_parkaging" => 1,
                        "only_parkaging" => 0,
                        "note" => $request->note ?? "NA",
                        'deparment_code'=> session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        "version" => 1, // lần đầu tạo thì version = 1
                        ]);
                        
                return redirect()->back()->with('success', 'Đã thêm thành công!');
        }

}
