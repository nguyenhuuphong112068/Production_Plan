<?php

namespace App\Http\Controllers\Pages\Status;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class StatusHistoryController extends Controller{
    
    protected array $stage = [
                        'Cân Nguyên Liệu' => 'Cân',
                        'Pha Chế' => 'PC',
                        'Trộn Hoàn Tất'=> 'THT',
                        'Định Hình' => "ĐH",
                        'Bao Phim' => 'BP',
                        'ĐGSC-ĐGTC' =>'ĐGSC-ĐGTC'
    ];    
        
    public function index(Request $request){

        $production =  session('user')['production_code']??"PXV1";
        $startDate = $request->startDate?? Carbon::now();

        $room = DB::table('room')
            ->where('deparment_code', $production)
            ->select(
                'room.id',
                'room.production_group',
                DB::raw("CONCAT(room.code,'-', room.name) as room_name")
            )
            ->orderby('group_code')
            ->orderby('order_by')
            ->get();

        $process = DB::table('stage_plan')
            ->where('submit', 1)
            ->where('deparment_code', $production)
            ->where(function ($q) use ($startDate) {
                $q->whereDate('start', $startDate)
                ->orWhereDate('end', $startDate);
            })
            ->where('active', 1)
            ->select(
                'resourceId',
                'title',
                'start',
                'end'
            )
            ->get();

        $cleaning = DB::table('stage_plan')
            ->where('submit', 1)
            ->where('deparment_code', $production)
            ->where(function ($q) use ($startDate) {
                $q->whereDate('start_clearning', $startDate)
                ->orWhereDate('end_clearning', $startDate);
            })
            ->where('active', 1)
            ->select(
                'resourceId',
                DB::raw("CONCAT(title_clearning,'-', title) as title"),
                DB::raw("start_clearning as start"),
                DB::raw("end_clearning as end")
            )
            ->get();

        $actual = DB::table('room_status')
            ->where ('room_status.is_daily_report',0)
            ->where(function ($q) use ($startDate) {
                $q->whereDate('start', $startDate)
                ->orWhereDate('end', $startDate);
            })
            //->where('active', 1)
            ->select(
                'id',
                'room_id',
                'in_production',
                'start',
                'end',
                'notification',
                'created_by',
                'created_at',
                'status',
                'active'
            )
        ->get();

        $rooms = [];

        // Khởi tạo danh sách phòng
        foreach ($room as $r) {
            $rooms[$r->room_name] = [
                'production_group' => $r->production_group,
                'thero'  => [],
                'actual' => []
            ];
        }

        // Gộp dữ liệu process + cleaning vào thero
        foreach ($process as $p) {
            foreach ($room as $r) {
                if ($p->resourceId == $r->id) {
                    $rooms[$r->room_name]['thero'][] = [
                        'title' => $p->title,
                        'start' => $p->start,
                        'end'   => $p->end
                    ];
                }
            }
        }

        foreach ($cleaning as $c) {
            foreach ($room as $r) {
                if ($c->resourceId == $r->id) {
                    $rooms[$r->room_name]['thero'][] = [
                        'title' => $c->title,
                        'start' => $c->start,
                        'end'   => $c->end
                    ];
                }
            }
        }

        // SORT theo start
        foreach ($rooms as $roomName => $data) {
            usort($rooms[$roomName]['thero'], function ($a, $b) {
                return strtotime($a['start']) <=> strtotime($b['start']);
            });
        }

        foreach ($actual as $a) {
            foreach ($room as $r) {
                if ($a->room_id == $r->id) {
                    $rooms[$r->room_name]['actual'][] = [
                        'id'            => $a->id,
                        'in_production' => $a->in_production,
                        'status'        => $a->status,
                        'start'         => $a->start,
                        'end'           => $a->end,
                        'active'        => $a->active,
                        'notification'  => $a->notification,
                        'created_by'    => $a->created_by,
                        'created_at'    => $a->created_at
                    ];
                }
            }
        }

        //dd ($rooms);
        session()->put(['title'=> "LỊCH SỬ TRANG THÁI PHÒNG SẢN XUẤT $production"]);
        
        
        return view('pages.status.history.list',[
            'datas' =>  $rooms,
            'production' =>  $production,
            'stage' => $this->stage
        ]);
    }

    public function update (Request $request) {
        

        $validator = Validator::make($request->all(), [
            'room_name' => 'required',
            'in_production' => 'required',
            'status' => 'required',
            
        ],[
            'room_name.required' => 'Chọn phòng sản xuất', 
            'in_production.required' => 'Chọn sản phẩm đang sản xuất', 
            'status.required' => 'Chọn trạng thái phòng sản xuất hiện tại.',  
            
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

                
        DB::table('room_status')->where ('id', $request->id)->update([
                        'status' => $request->status,
                        'start' => $request->start,
                        'end' => $request->end,
                        'in_production' => $request->in_production,
                        'notification' => $request->notification??"NA",
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
        return redirect()->back()->with('success', 'Đã thêm thành công!');    
    }

    public function deActive (Request $request){
        
         DB::table('room_status')->where ('id', $request->id)->update([
                        'active' => 0,
                        'notification' => $request->deactive_reason,
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã xóa thành công!');   
    }

    public function show (Request $request){
        //dd ($request->all());
        $production =  session('user')['production_code']??"PXV1";
        $startDate = $request->startDate?? Carbon::now();

        $room = DB::table('room')
            ->where('deparment_code', $production)
            ->select(
                'room.id',
                'room.production_group',
                DB::raw("CONCAT(room.code,'-', room.name) as room_name")
            )
            ->orderby('group_code')
            ->orderby('order_by')
            ->get();

        $process = DB::table('stage_plan')
            ->where('submit', 1)
            ->where(function ($q) use ($startDate) {
                $q->whereDate('start', $startDate)
                ->orWhereDate('end', $startDate);
            })
            ->where('active', 1)
            ->select(
                'resourceId',
                'title',
                'start',
                'end'
            )
            ->get();

        $cleaning = DB::table('stage_plan')
            ->where('submit', 1)
            ->where(function ($q) use ($startDate) {
                $q->whereDate('start_clearning', $startDate)
                ->orWhereDate('end_clearning', $startDate);
            })
            ->where('active', 1)
            ->select(
                'resourceId',
                DB::raw("CONCAT(title_clearning,'-', title) as title"),
                DB::raw("start_clearning as start"),
                DB::raw("end_clearning as end")
            )
            ->get();

        $actual = DB::table('room_status')
            ->where ('room_status.is_daily_report',0)
            ->where(function ($q) use ($startDate) {
                $q->whereDate('start', $startDate)
                ->orWhereDate('end', $startDate);
            })
            //->where('active', 1)
            ->select(
                'id',
                'room_id',
                'in_production',
                'start',
                'end',
                'notification',
                'created_by',
                'created_at',
                'status',
                'active'
            )
        ->get();

        $rooms = [];

        // Khởi tạo danh sách phòng
        foreach ($room as $r) {
            $rooms[$r->room_name] = [
                'production_group' => $r->production_group,
                'thero'  => [],
                'actual' => []
            ];
        }

        // Gộp dữ liệu process + cleaning vào thero
        foreach ($process as $p) {
            foreach ($room as $r) {
                if ($p->resourceId == $r->id) {
                    $rooms[$r->room_name]['thero'][] = [
                        'title' => $p->title,
                        'start' => $p->start,
                        'end'   => $p->end
                    ];
                }
            }
        }

        foreach ($cleaning as $c) {
            foreach ($room as $r) {
                if ($c->resourceId == $r->id) {
                    $rooms[$r->room_name]['thero'][] = [
                        'title' => $c->title,
                        'start' => $c->start,
                        'end'   => $c->end
                    ];
                }
            }
        }

        // SORT theo start
        foreach ($rooms as $roomName => $data) {
            usort($rooms[$roomName]['thero'], function ($a, $b) {
                return strtotime($a['start']) <=> strtotime($b['start']);
            });
        }

        foreach ($actual as $a) {
            foreach ($room as $r) {
                if ($a->room_id == $r->id) {
                    $rooms[$r->room_name]['actual'][] = [
                        'id'            => $a->id,
                        'in_production' => $a->in_production,
                        'status'        => $a->status,
                        'start'         => $a->start,
                        'end'           => $a->end,
                        'active'        => $a->active,
                        'notification'  => $a->notification,
                        'created_by'    => $a->created_by,
                        'created_at'    => $a->created_at
                    ];
                }
            }
        }

        //dd ($rooms);
        session()->put(['title'=> "LỊCH SỬ TRANG THÁI PHÒNG SẢN XUẤT $production"]);
              
        return view('pages.status.history.dataTableShow',[
            'datas' =>  $rooms,
            'production' =>  $production,
            'stage' => $this->stage
        ]);
    }
    
    public function next(Request $request){
              
                if ($request->production == "PXV1"){
                     $production_code = "PXV2";
                }elseif ($request->production == "PXV2"){
                     $production_code = "PXVH";
                }elseif ($request->production == "PXVH"){
                     $production_code = "PXTN";
                }elseif ($request->production == "PXTN"){
                     $production_code = "PXDN";
                }else {
                        $production_code = "PXV1";
                }

                $request->session()->put('user', [
                        'production_code' => $production_code
                ]);
                                
                session()->put(['title'=> "LỊCH SỬ TRANG THÁI PHÒNG SẢN XUẤT $production_code"]);
                // Nếu có redirect URL thì quay lại đó
                return redirect()->back();
    }

}
