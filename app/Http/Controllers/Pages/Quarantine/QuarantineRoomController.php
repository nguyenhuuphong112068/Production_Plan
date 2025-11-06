<?php

namespace App\Http\Controllers\Pages\Quarantine;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuarantineRoomController extends Controller
{
    public function index(Request $request) {

        $startDate = $request->from_date
            ? Carbon::parse($request->from_date)->setTime(5, 59, 59)
            : Carbon::now()->startOfMonth()->setTime(5, 59, 59);

        $endDate = $request->to_date
            ? Carbon::parse($request->to_date)->setTime(6, 0, 0)
            : Carbon::now()->endOfMonth()->setTime(6, 0, 0);

        $intervalHours = 24;

        $timePoints = [];
        for ($t = strtotime($startDate); $t <= strtotime($endDate); $t += 3600 * $intervalHours) {
            $timePoints[] = date('Y-m-d H:i:s', $t);
        }

        $results = [];
    
        $stages = DB::table('stage_plan as t')
            ->leftJoin('stage_plan as t2', function ($join) {
                $join->on('t2.code','=','t.nextcessor_code');
            })
            ->leftJoin('finished_product_category as fc', 't.product_caterogy_id', '=', 'fc.id')
            ->whereNotNull('t.start')
            ->where('t.active', 1)
            ->where('t.finished', 0)
            ->select(
                'fc.finished_product_code',
                'fc.intermediate_code',
                't.plan_master_id',
                't.stage_code',
                't.theoretical_yields',
                't.nextcessor_code',
                't.start',
                't2.start as next_start'
            )
            ->orderBy('t.plan_master_id')
            ->orderBy('t.stage_code')
            ->get();
        
        $results = [];

        foreach ($timePoints as $T) {
            $filtered = $stages->filter(function ($r) use ($T) {
                return ($r->start <= $T) && (is_null($r->next_start) || $r->next_start > $T) && $r->stage_code < 7;
            });
            foreach ($filtered as $r) {
                     $results[$T][$r->stage_code][] = [
                    'next_stage_code' => explode("_",$r->nextcessor_code??"_0")[1],
                    'plan_master_id' => $r->plan_master_id,
                    'time_point' => $T,
                    'intermediate_code' => $r->intermediate_code,
                    'finished_product_code' => $r->finished_product_code,
                    'stage_code' => $r->stage_code,
                    'theoretical_stock' => (float)$r->theoretical_yields,
                ];
            }
        }

        $totals = [];

        foreach ($results as $timePoint => $stages) {
            foreach ($stages as $stageCode => $entries) {
                // Tính tổng theoretical_stock cho stage_code này
                $sum = collect($entries)->sum('theoretical_stock');

                // Lưu kết quả
                $totals[$stageCode][$timePoint] = $sum;
            }
        }

        $stageTimeSeries = [];

        foreach ($totals as $stageCode => $points) {
            foreach ($points as $timePoint => $value) {
                $stageTimeSeries[$stageCode][] = [
                    'stage_code'   => $stageCode,
                    'time_point'   => $timePoint,
                    'room_id'      => 0, // giả lập vì không có room
                    'room_name'    => 'Tổng tồn', // tên hiển thị trên legend
                    'total_stock'  => $value,
                ];
            }
        }

        
        session()->put(['title' => 'TỒN BÁN THÀNH PHẨM THEO CÔNG ĐOẠN']);

        return view('pages.quarantine.room.list', [
            'totals' => $totals,
            'timePoints' => $timePoints,
            'stageTimeSeries' => $stageTimeSeries,
        ]);

    }
}
