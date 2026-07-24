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
                'intermediate_category.intermediate_code',
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

            ->where('stage_plan.active', 1)
            ->where('stage_plan.deparment_code', $production)
            ->where('plan_master.only_parkaging',  0)
            ->where('stage_plan.stage_code', '<', 8)
            ->orderBy('stage_plan.plan_master_id')
            ->orderBy('stage_plan.stage_code')
            ->get()
            ->groupBy('plan_master_id');

        $allDatas = $datas;

        $filteredDatas = collect();
        if ($request->has('filter_overdue') && $request->filter_overdue == '1') {
            foreach ($datas as $plan_master_id => $stages) {
                $isOverdue = false;
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
                    if (!$next || $next->finished == 1) continue;

                    $stdValue = null;
                    if (in_array($stage->stage_code, [1, 2])) $stdValue = $stage->quarantine_weight;
                    elseif ($stage->stage_code == 3) $stdValue = $stage->quarantine_preparing;
                    elseif ($stage->stage_code == 4) $stdValue = $stage->quarantine_blending;
                    elseif ($stage->stage_code == 5) $stdValue = $stage->quarantine_forming;
                    elseif ($stage->stage_code == 6) $stdValue = $stage->quarantine_coating;
                    
                    if ($stage->end && $stdValue !== null && $stdValue > 0) {
                        $stdInMinutes = ($stage->quarantine_time_unit == 1) ? $stdValue * 24 * 60 : $stdValue * 60;
                        
                        if (!$next->start) {
                            $endTs = strtotime($stage->end);
                            $nowTs = time();
                            if ((($nowTs - $endTs) / 60) > $stdInMinutes) {
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
        } else {
            foreach ($datas as $plan_master_id => $stages) {
                $main = $stages->first();
                $expected = $main->expected_date ?? null;
                if ($expected && $expected >= $fromDate && $expected <= $toDate) {
                    $filteredDatas->put($plan_master_id, $stages);
                }
            }
        }
        $datas = $filteredDatas;

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
                'intermediate_category.intermediate_code',
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
        $mapFunction = function ($plans) use ($stage_name, $datas_only_parkaging) {
            $plans = $plans->map(function ($item) use ($stage_name) {
                $item->stage_name = $stage_name[$item->stage_code]->stage ?? null;
                return $item;
            });
            $main = $plans->first();
            if ($main && $main->percent_parkaging < 1) {
                $extraStages = $datas_only_parkaging->where('main_parkaging_id', $main->plan_master_id)->values();
                if ($extraStages->isNotEmpty()) {
                    $extraStages = $extraStages->map(function ($item) use ($stage_name) {
                        $item->stage_name = $stage_name[$item->stage_code]->stage ?? null;
                        return $item;
                    });
                    $plans = $plans->merge($extraStages);
                }
            }
            return $plans->values();
        };

        $datas = $datas->map($mapFunction);
        
        $wipDatasUnmapped = collect();
        foreach ($allDatas as $plan_master_id => $stages) {
            $sortedStages = $stages->sortBy('stage_code')->values();
            $firstStage = $sortedStages->first();
            $lastStage = $sortedStages->last();

            if ($firstStage && $lastStage) {
                $hasStarted = $firstStage->finished == 1;
                $notFinished = $lastStage->finished != 1;
                
                if ($hasStarted && $notFinished) {
                    $lastFinishedStage = $sortedStages->where('finished', 1)->last();
                    $nextStage = $sortedStages->where('stage_code', '>', $lastFinishedStage->stage_code)
                                              ->where('stage_code', '!=', 2)
                                              ->first();
                    $nextStageName = $nextStage ? ($stage_name[$nextStage->stage_code]->stage ?? 'Khác') : 'Khác';
                    
                    if (!isset($wipDatasUnmapped[$nextStageName])) {
                        $wipDatasUnmapped[$nextStageName] = collect();
                    }
                    $wipDatasUnmapped[$nextStageName]->put($plan_master_id, $stages);
                }
            }
        }
        
        $wipDatas = collect();
        foreach ($wipDatasUnmapped as $stageName => $groupStages) {
            $wipDatas->put($stageName, $groupStages->map($mapFunction));
        }

        $stageOrder = [
            'Cân Nguyên Liệu' => 1,
            'Pha Chế' => 2,
            'Trộn Hoàn Tất' => 3,
            'Định Hình' => 4,
            'Bao Phim' => 5,
            'ĐGSC-ĐGTC' => 6,
        ];
        
        $wipDatas = $wipDatas->sortBy(function ($groupStages, $stageName) use ($stageOrder) {
            return $stageOrder[$stageName] ?? 99;
        });

        // Theo dõi biệt trữ TỔNG: từ khi bắt đầu Pha Chế (3) / Trộn Hoàn Tất (4)
        // đến khi kết thúc Đóng Gói (7) không được vượt quá intermediate_category.quarantine_total (ngày).
        // Liệt kê MỌI lô BTP dở dang có quarantine_total > 0 để theo dõi xuyên suốt, không chỉ lô đã quá hạn.
        $wipQuarantineWarnings = collect();
        foreach ($wipDatas as $stageName => $groupStages) {
            foreach ($groupStages as $plan_master_id => $stages) {
                $plan = $stages->first();
                $totalDays = is_numeric($plan->quarantine_total ?? null) ? (float) $plan->quarantine_total : 0;
                if ($totalDays <= 0) continue;

                $startStage = $stages->whereIn('stage_code', [3, 4])
                    ->filter(fn($s) => !empty($s->start))
                    ->sortBy('stage_code')
                    ->first();

                $packStages = $stages->where('stage_code', 7);
                $packEnded  = $packStages->filter(fn($s) => !empty($s->end));
                $packEndTs  = $packEnded->isNotEmpty() ? $packEnded->map(fn($s) => strtotime($s->end))->max() : null;
                $packFinished = $packStages->isNotEmpty() && $packStages->filter(fn($s) => $s->finished != 1)->isEmpty();

                $deadlineTs = null;
                $remainDays = null;
                $isOverdue  = false;
                $statusKey  = 'not_started'; // chưa bắt đầu PC/THT

                if ($startStage) {
                    $deadlineTs = strtotime($startStage->start) + (int) round($totalDays * 86400);
                    $remainDays = ($deadlineTs - time()) / 86400;

                    if ($packFinished && $packEndTs) {
                        $isOverdue = $packEndTs > $deadlineTs;
                        $statusKey = $isOverdue ? 'overdue' : 'done';
                    } else {
                        $isOverdue = time() > $deadlineTs || ($packEndTs && $packEndTs > $deadlineTs);
                        if ($isOverdue) {
                            $statusKey = 'overdue';
                        } elseif ($remainDays < 3) {
                            $statusKey = 'urgent';   // sắp đến hạn
                        } elseif ($remainDays < 5) {
                            $statusKey = 'near';     // cần chú ý
                        } else {
                            $statusKey = 'ok';       // còn nhiều thời gian
                        }
                    }
                }

                $wipQuarantineWarnings->push((object) [
                    'plan_master_id'   => $plan_master_id,
                    'stage_group'      => $stageName,
                    'product_name'     => $plan->product_name,
                    'intermediate_code' => $plan->intermediate_code,
                    'batch'            => $plan->batch,
                    'quarantine_total' => $totalDays,
                    'start_at'         => $startStage->start ?? null,
                    'start_stage_name' => $startStage->stage_name ?? null,
                    'deadline'         => $deadlineTs ? date('Y-m-d H:i:s', $deadlineTs) : null,
                    'pack_end'         => $packEndTs ? date('Y-m-d H:i:s', $packEndTs) : null,
                    'remain_days'      => $remainDays,
                    'is_overdue'       => $isOverdue,
                    'status'           => $statusKey,
                    'stages'           => $stages,
                ]);
            }
        }

        // Gấp nhất (quá hạn nhiều nhất) lên đầu; lô chưa bắt đầu PC/THT xuống cuối
        $wipQuarantineWarnings = $wipQuarantineWarnings
            ->sortBy(fn($w) => $w->remain_days === null ? PHP_INT_MAX : $w->remain_days)
            ->values();

        $wipQuarantineSummary = [
            'total'       => $wipQuarantineWarnings->count(),
            'overdue'     => $wipQuarantineWarnings->where('status', 'overdue')->count(),
            'urgent'      => $wipQuarantineWarnings->where('status', 'urgent')->count(),
            'near'        => $wipQuarantineWarnings->where('status', 'near')->count(),
            'ok'          => $wipQuarantineWarnings->where('status', 'ok')->count(),
            'done'        => $wipQuarantineWarnings->where('status', 'done')->count(),
            'not_started' => $wipQuarantineWarnings->where('status', 'not_started')->count(),
        ];

        // Dữ liệu để render đầy đủ tiến trình (stepper) của các lô đang theo dõi
        $wipQuarantineWarningDatas = collect();
        foreach ($wipQuarantineWarnings as $warning) {
            $wipQuarantineWarningDatas->put($warning->plan_master_id, $warning->stages);
        }

        //dd ($datas);

        session()->put(['title' => 'Tiến Trình Sản Xuất']);
        //dd ($datas);
        return view('pages.Schedual.step.list', [
            'datas' => $datas,
            'wipDatas' => $wipDatas,
            'wipQuarantineWarnings' => $wipQuarantineWarnings,
            'wipQuarantineWarningDatas' => $wipQuarantineWarningDatas,
            'wipQuarantineSummary' => $wipQuarantineSummary
        ]);
    }
}
