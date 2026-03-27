<?php

namespace App\Http\Controllers\Pages\Quarantine;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuarantineRoomController extends Controller
{

    public function index(Request $request)
    {

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
                $join->on('t2.code', '=', 't.nextcessor_code');
            })
            ->leftJoin('finished_product_category as fc', 't.product_caterogy_id', '=', 'fc.id')
            ->whereNotNull('t.start')
            ->where('t.active', 1)
            ->where('t.finished', 0)
            ->where('t.deparment_code', session('user')['production_code'])
            ->where('t2.deparment_code', session('user')['production_code'])
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
                    'next_stage_code' => explode("_", $r->nextcessor_code ?? "_0")[1],
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

        return view('pages.quarantine.theory.list', [
            'totals' => $totals,
            'timePoints' => $timePoints,
            'stageTimeSeries' => $stageTimeSeries,
        ]);
    }


    public function index_actual(Request $request)
    {
        // 1) Truy vấn 1 lần duy nhất toàn bộ dữ liệu cần thiết cho cả 2 phần
        // Tối ưu: Loại bỏ COALESCE trong WHERE để MySQL dùng được Index
        $datasRaw = DB::table('stage_plan as t')
            ->leftJoin('stage_plan as t2', 't2.code', '=', 't.nextcessor_code')
            ->leftJoin('plan_master', 't.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category as fc', 't.product_caterogy_id', '=', 'fc.id')
            ->leftJoin('product_name', 'fc.product_name_id', '=', 'product_name.id')
            ->leftJoin('quarantine_room', 't.quarantine_room_code', '=', 'quarantine_room.code')
            ->leftJoin('room', 't2.resourceId', '=', 'room.id')
            ->whereNotNull('t.actual_start')
            ->whereNotNull('t.yields')
            ->where('t.active', 1)
            ->where('t.finished', 1)
            ->where('t2.finished', 0)
            ->where('t.deparment_code', session('user')['production_code'])
            ->where('t2.deparment_code', session('user')['production_code'])
            ->where(function ($q) {
                $now = now();
                $q->where(function ($sub) use ($now) {
                        $sub->whereNotNull('t2.actual_start')->where('t2.actual_start', '>', $now);
                    })
                    ->orWhere(function ($sub) use ($now) {
                        $sub->whereNull('t2.actual_start')->where('t2.start', '>', $now);
                    })
                    ->orWhere(function ($sub) {
                        $sub->whereNull('t2.actual_start')->whereNull('t2.start');
                    });
            })
            ->select(
                'fc.finished_product_code',
                'fc.intermediate_code',
                't.plan_master_id',
                'product_name.name as product_name',
                DB::raw("COALESCE(plan_master.actual_batch, plan_master.batch) AS batch"),
                't.quarantine_room_code',
                'quarantine_room.name as quarantine_room_name',
                't.yields',
                't.stage_code',
                't.number_of_boxes',
                't.finished_by',
                't.finished_date',
                't2.stage_code as next_stage',
                't2.start as next_start',
                't2.resourceId as next_room_id',
                DB::raw("CONCAT(room.code, ' - ', room.name, ' - ', room.main_equiment_name) as next_room"),
                'room.production_group',
                'room.stage',
                'room.group_code'
            )
            ->get();

        // 2) Group theo phòng Biệt Trữ (Dùng cho bảng Details)
        // Chỉ lấy những dòng có quarantine_room_code
        $datas = $datasRaw->whereNotNull('quarantine_room_code')
            ->groupBy('quarantine_room_code')
            ->map(function ($items) {
                return [
                    'room_name' => $items->first()->quarantine_room_name,
                    'total_yields' => $items->sum('number_of_boxes'),
                    'details' => $items
                ];
            });

        // 3) Tổng hợp theo Phòng Kế Tiếp (Sử dụng Collection để tính toán trong RAM - cực nhanh)
        $sum_by_next_room = $datasRaw->filter(fn($r) => !empty($r->next_room))
            ->groupBy('next_room')
            ->map(function ($items) {
                return (object)[
                    'sum_yields' => $items->sum('yields'),
                    'next_room' => $items->first()->next_room,
                    'production_group' => $items->first()->production_group,
                    'stage' => $items->first()->stage,
                    'next_stage' => $items->first()->next_stage, // Lấy stage code của phòng kế tiếp
                    'group_code' => $items->first()->group_code,
                    'room_id' => $items->first()->next_room_id,
                ];
            })
            ->sortBy('next_stage')
            ->values();


        session()->put(['title' => 'QUẢN LÝ BIỆT TRỮ']);

        return view('pages.quarantine.actual.list', [
            'datas' => $datas,
            'sum_by_next_room' => $sum_by_next_room,
        ]);
    }

    public function detail(Request $request)
    {


        $detial = DB::table('stage_plan as t')
            ->leftJoin('stage_plan as t2', function ($join) {
                $join->on('t2.code', '=', 't.nextcessor_code');
            })
            ->leftJoin('plan_master', 't.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category as fc', 't.product_caterogy_id', '=', 'fc.id')
            ->leftJoin('product_name', 'fc.product_name_id', 'product_name.id')
            ->leftJoin('quarantine_room', 't.quarantine_room_code', 'quarantine_room.code')
            ->leftJoin('room', 't.resourceId', 'room.id')
            ->whereNotNull('t.start')
            ->whereNotNull('t.yields')
            ->where('t2.resourceId', $request->room_id)
            ->where('t.active', 1)
            ->where('t.finished', 1)
            ->where('t2.finished', 0)
            ->where('t.deparment_code', session('user')['production_code'])
            ->where('t2.deparment_code', session('user')['production_code'])
            ->where(function ($q) {
                $q->whereRaw('COALESCE(t2.actual_start, t2.start) > ?', [now()])
                    ->orWhere(function ($q2) {
                        $q2->whereNull('t2.start')
                            ->whereNull('t2.actual_start');
                    });
            })
            ->select(
                'fc.finished_product_code',
                'fc.intermediate_code',
                'product_name.name as product_name',
                DB::raw("COALESCE(plan_master.actual_batch, plan_master.batch) AS batch"),
                't.quarantine_room_code',
                'quarantine_room.name',
                't.yields',
                't.stage_code',
                't2.stage_code as next_stage',
                't2.start as next_start',
                DB::raw("CONCAT(room.code, ' - ', room.name, ' - ', room.main_equiment_name) as pre_room"),
                'room.production_group as production_group',
                'room.stage as stage',
                'room.group_code',

            )
            ->orderBy('t.plan_master_id')
            ->orderBy('t.stage_code')
            ->get();
        return response()->json($detial);
    }
}
