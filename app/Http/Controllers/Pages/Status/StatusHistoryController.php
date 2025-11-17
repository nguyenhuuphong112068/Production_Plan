<?php

namespace App\Http\Controllers\Pages\Status;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

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
            ->where(function ($q) use ($startDate) {
                $q->whereDate('start', $startDate)
                ->orWhereDate('end', $startDate);
            })
            ->where('active', 1)
            ->select(
                'room_id',
                'in_production',
                'start',
                'end',
                'notification'
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
                        'in_production' => $a->in_production,
                        'start'         => $a->start,
                        'end'           => $a->end,
                        'notification'  => $a->notification
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

    
}
