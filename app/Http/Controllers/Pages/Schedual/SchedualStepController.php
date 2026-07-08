<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedualStepController extends Controller
{


    public function list(Request $request)
    {
        //dd ($request->all());
        $fromDate = $request->from_date ?? Carbon::now()->subMonth()->toDateString();
        $toDate   = $request->to_date   ?? Carbon::now()->addMonth();
        $production = session('user')['production_code'];
        // Lấy danh sách stage_name (danh mục stage)
        $stage_name = DB::table('room')
            ->distinct()
            ->select('stage_code', 'stage')
            ->get()
            ->keyBy('stage_code');

        $yieldsSubquery = DB::table('yields')
            ->select('stage_plan_id', DB::raw('SUM(yield) as yields'))
            ->groupBy('stage_plan_id');

        $datas = DB::table('stage_plan')
            ->when(!in_array(session('user')['userGroup'], ['Schedualer', 'Admin', 'Leader']), fn($query) => $query->where('submit', 1))
            ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
            ->leftJoin('plan_master',  'stage_plan.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id',  'finished_product_category.id')
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
            ->leftJoinSub($yieldsSubquery, 'yields_summary', 'yields_summary.stage_plan_id', '=', 'stage_plan.id')
            ->select(
                'stage_plan.id',
                'stage_plan.code',
                'stage_plan.nextcessor_code',
                'stage_plan.plan_master_id',
                'stage_plan.stage_code',
                DB::raw("IF(stage_plan.actual_start IS NOT NULL, stage_plan.actual_start, stage_plan.start) AS start"),
                DB::raw("IF(stage_plan.actual_end IS NOT NULL, stage_plan.actual_end, stage_plan.end) AS end"),
                DB::raw("IF(stage_plan.actual_start_clearning IS NOT NULL, stage_plan.actual_start_clearning, stage_plan.start_clearning) AS start_clearning"),
                DB::raw("IF(stage_plan.actual_end_clearning IS NOT NULL, stage_plan.actual_end_clearning, stage_plan.end_clearning) AS end_clearning"),
                'stage_plan.finished',
                'yields_summary.yields',
                DB::raw("CONCAT(room.name,'-', room.code) as room_name"),
                //'room.name as room_name',
                //'room.code as room_code',
                'plan_master.batch',
                'plan_master.expected_date',
                'plan_master.after_weigth_date',

                'plan_master.after_parkaging_date',

                'plan_master.only_parkaging',
                'plan_master.main_parkaging_id',
                'plan_master.percent_parkaging',
                'finished_product_category.batch_qty',
                'finished_product_category.unit_batch_qty',
                'market.name as market',
                'product_name.name as product_name',
                'intermediate_category.quarantine_total',
                'intermediate_category.quarantine_weight',
                'intermediate_category.quarantine_preparing',
                'intermediate_category.quarantine_blending',
                'intermediate_category.quarantine_forming',
                'intermediate_category.quarantine_coating',
                'intermediate_category.quarantine_time_unit',
                DB::raw("
                        CASE 
                            WHEN stage_plan.finished = 1 THEN 'finished'
                            WHEN stage_plan.end IS NOT NULL THEN 'scheduled'
                            ELSE 'pending'
                        END as status
                    ")
            )
            ->when(!$request->has('filter_overdue') || $request->filter_overdue != '1', function($query) use ($fromDate, $toDate) {
                return $query->whereBetween('plan_master.expected_date', [$fromDate, $toDate]);
            })
            ->where('stage_plan.active', 1)
            ->where('stage_plan.deparment_code', $production)
            ->where('plan_master.only_parkaging',  0)
            ->where('stage_plan.stage_code', '<', 8)
            ->orderBy('stage_plan.plan_master_id')
            ->orderBy('stage_plan.stage_code')
            ->get()
            ->groupBy('plan_master_id');

        // Logic lọc cảnh báo đỏ (quá hạn biệt trữ) và công đoạn sau chưa hoàn thành
        if ($request->has('filter_overdue') && $request->filter_overdue == '1') {
            $filteredDatas = collect();
            foreach ($datas as $plan_master_id => $stages) {
                $isOverdue = false;
                
                // Nếu lô đã hoàn thành (công đoạn 7 hoặc 8 đã finished) thì bỏ qua, không tính là cảnh báo quá hạn
                $isPlanFinished = false;
                foreach ($stages as $s) {
                    if ($s->stage_code >= 7 && $s->finished == 1) {
                        $isPlanFinished = true;
                        break;
                    }
                }
                if ($isPlanFinished) continue;

                foreach ($stages as $stage) {
                    $next = $stages->firstWhere('code', $stage->nextcessor_code);
                    // Yêu cầu: công đoạn sau chưa hoàn thành (chưa xếp lịch hoặc chưa finished)
                    if (!$next || $next->finished == 1) continue;

                    // Lấy thời gian biệt trữ chuẩn
                    $stdValue = null;
                    if (in_array($stage->stage_code, [1, 2])) $stdValue = $stage->quarantine_weight;
                    elseif ($stage->stage_code == 3) $stdValue = $stage->quarantine_preparing;
                    elseif ($stage->stage_code == 4) $stdValue = $stage->quarantine_blending;
                    elseif ($stage->stage_code == 5) $stdValue = $stage->quarantine_forming;
                    elseif ($stage->stage_code == 6) $stdValue = $stage->quarantine_coating;
                    
                    if ($stage->end && $stdValue !== null && $stdValue > 0) {
                        $stdInMinutes = ($stage->quarantine_time_unit == 1) ? $stdValue * 24 * 60 : $stdValue * 60;
                        
                        if ($next->start) {
                            continue; // Đã xếp lịch thì bỏ qua, không lọc cảnh báo
                        } else {
                            $endTs = strtotime($stage->end);
                            $nowTs = time();
                            $diffInMinutes = ($nowTs - $endTs) / 60;
                            if ($diffInMinutes > $stdInMinutes) {
                                $isOverdue = true;
                                break;
                            }
                        }
                    }
                }
                if ($isOverdue) {
                    $filteredDatas->put($plan_master_id, $stages);
                }
            }
            $datas = $filteredDatas;
        }

        $datas_only_parkaging = DB::table('stage_plan')
            ->when(!in_array(session('user')['userGroup'], ['Schedualer', 'Admin', 'Leader']), fn($query) => $query->where('submit', 1))
            ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
            ->leftJoin('plan_master',  'stage_plan.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id',  'finished_product_category.id')
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
            ->leftJoinSub($yieldsSubquery, 'yields_summary', 'yields_summary.stage_plan_id', '=', 'stage_plan.id')
            ->select(
                'stage_plan.id',
                'stage_plan.code',
                'stage_plan.nextcessor_code',
                'stage_plan.plan_master_id',
                'stage_plan.stage_code',
                DB::raw("IF(stage_plan.actual_start IS NOT NULL, stage_plan.actual_start, stage_plan.start) AS start"),
                DB::raw("IF(stage_plan.actual_end IS NOT NULL, stage_plan.actual_end, stage_plan.end) AS end"),
                DB::raw("IF(stage_plan.actual_start_clearning IS NOT NULL, stage_plan.actual_start_clearning, stage_plan.start_clearning) AS start_clearning"),
                DB::raw("IF(stage_plan.actual_end_clearning IS NOT NULL, stage_plan.actual_end_clearning, stage_plan.end_clearning) AS end_clearning"),
                'stage_plan.finished',
                'yields_summary.yields',
                DB::raw("CONCAT(room.name,'-', room.code) as room_name"),
                // 'room.name as room_name',
                // 'room.code as room_code',
                'plan_master.batch',
                'plan_master.expected_date',
                'plan_master.after_weigth_date',

                'plan_master.after_parkaging_date',

                'plan_master.only_parkaging',
                'plan_master.main_parkaging_id',
                'finished_product_category.batch_qty',
                'finished_product_category.unit_batch_qty',
                'market.name as market',
                'product_name.name as product_name',
                'intermediate_category.quarantine_total',
                'intermediate_category.quarantine_weight',
                'intermediate_category.quarantine_preparing',
                'intermediate_category.quarantine_blending',
                'intermediate_category.quarantine_forming',
                'intermediate_category.quarantine_coating',
                'intermediate_category.quarantine_time_unit',
                DB::raw("
                        CASE 
                            WHEN stage_plan.finished = 1 THEN 'finished'
                            WHEN stage_plan.end IS NOT NULL THEN 'scheduled'
                            ELSE 'pending'
                        END as status
                    ")
            )
            ->whereBetween('plan_master.expected_date', [$fromDate, $toDate])
            ->where('stage_plan.active', 1)
            ->where('stage_plan.deparment_code', $production)
            ->where('plan_master.only_parkaging',  1)
            ->where('stage_plan.stage_code', '<', 8)
            ->orderBy('stage_plan.plan_master_id')
            ->orderBy('stage_plan.stage_code')
            ->get();


        // --- 3. Map thêm stage_name + ghép dữ liệu phụ ---
        $datas = $datas->map(function ($plans) use ($stage_name, $datas_only_parkaging) {

            $plans = $plans->map(function ($item) use ($stage_name) {
                $item->stage_name = $stage_name[$item->stage_code]->stage ?? null;
                return $item;
            });

            // Lấy plan_master đầu tiên để kiểm tra percent_parkaging
            $main = $plans->first();

            // Nếu percent_parkaging < 1 → lấy các stage đóng gói phụ tương ứng (main_parkaging_id)
            if ($main && $main->percent_parkaging < 1) {

                $extraStages = $datas_only_parkaging
                    ->where('main_parkaging_id', $main->plan_master_id)
                    ->values();


                if ($extraStages->isNotEmpty()) {
                    // map thêm stage_name
                    $extraStages = $extraStages->map(function ($item) use ($stage_name) {
                        $item->stage_name = $stage_name[$item->stage_code]->stage ?? null;
                        return $item;
                    });

                    // Gộp thêm vào cuối
                    $plans = $plans->merge($extraStages);
                }
            }

            return $plans->values();
        });



        //dd ($datas);

        session()->put(['title' => 'Tiến Trình Sản Xuất']);
        //dd ($datas);
        return view('pages.Schedual.step.list', [
            'datas' => $datas,
        ]);
    }
}
