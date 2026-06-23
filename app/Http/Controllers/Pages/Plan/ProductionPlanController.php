<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductionPlanController extends Controller
{
        public function index()
        {

                $production_code = session('user')['production_code'];

                /*
                |--------------------------------------------------------------------------
                | 1. LẤY DANH SÁCH PLAN LIST
                |--------------------------------------------------------------------------
                */

                $datas = DB::table('plan_list')
                        ->when(
                                !user_has_permission(session('user')['userId'], 'plan_view_pending_production_plan', 'boolean'),
                                function ($q) {
                                        return $q->where('plan_list.send', 1);
                                }
                        )
                        ->where('active', 1)
                        ->where('deparment_code', $production_code)
                        ->where('type', 1)
                        ->orderBy('id', 'desc')
                        ->get();


                /*
                |--------------------------------------------------------------------------
                | 2. TỔNG BATCH THEO PLAN_LIST
                |--------------------------------------------------------------------------
                */

                $total_batch_qtys = DB::table('plan_master as pm')
                        ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                        ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
                        ->where('pm.active', 1)
                        ->where('pm.cancel', 0)
                        ->where('pm.only_parkaging', 0)
                        ->where('fpc.active', 1)
                        ->where('pm.deparment_code', $production_code)
                        ->where('pl.type', 1)
                        ->groupBy('pm.plan_list_id')
                        ->select(
                                'pm.plan_list_id',
                                DB::raw('SUM(fpc.batch_qty) as total_batch_qty')
                        )
                        ->get()
                        ->keyBy('plan_list_id');

                $products_in_plans = DB::table('plan_master as pm')
                        ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                        ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
                        ->join('product_name as pn', 'fpc.product_name_id', '=', 'pn.id')
                        ->where('pm.active', 1)
                        ->where('pm.cancel', 0)
                        ->where('pm.deparment_code', $production_code)
                        ->where('pl.type', 1)
                        ->groupBy('pm.plan_list_id')
                        ->select(
                                'pm.plan_list_id',
                                DB::raw('GROUP_CONCAT(DISTINCT pn.name SEPARATOR ", ") as product_names'),
                                DB::raw('GROUP_CONCAT(DISTINCT fpc.finished_product_code SEPARATOR ", ") as product_codes'),
                                DB::raw('GROUP_CONCAT(DISTINCT fpc.intermediate_code SEPARATOR ", ") as intermediate_codes')
                        )
                        ->get()
                        ->keyBy('plan_list_id');


                /*
                |--------------------------------------------------------------------------
                | 3. LẤY MAX STAGE FINISHED
                |--------------------------------------------------------------------------
                */

                $maxStageFinished = DB::table('stage_plan')
                        ->where('finished', 1)
                        ->where('active', 1)
                        ->where('stage_code', '!=', 8)
                        ->where('deparment_code', $production_code)
                        ->select(
                                'plan_master_id',
                                DB::raw('MAX(stage_code) as max_stage_code')
                        )
                        ->groupBy('plan_master_id');

                $maxPossibleStage = DB::table('stage_plan')
                        ->where('active', 1)
                        ->where('stage_code', '!=', 8)
                        ->where('deparment_code', $production_code)
                        ->select(
                                'plan_master_id',
                                DB::raw('MAX(stage_code) as max_possible_stage_code')
                        )
                        ->groupBy('plan_master_id');


                /*
                |--------------------------------------------------------------------------
                | 4. XÁC ĐỊNH STATUS TỪNG LÔ
                |--------------------------------------------------------------------------
                */

                $batch_status = DB::table('plan_master as pm')
                        ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
                        ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                                $join->on('pm.id', '=', 'sp_max.plan_master_id');
                        })
                        ->leftJoinSub($maxPossibleStage, 'sp_possible', function ($join) {
                                $join->on('pm.id', '=', 'sp_possible.plan_master_id');
                        })
                        ->leftJoin('stage_plan as sp', function ($join) {
                                $join->on('pm.id', '=', 'sp.plan_master_id')
                                        ->on('sp.stage_code', '=', 'sp_max.max_stage_code');
                        })
                        ->leftJoin('finished_product_category as fc', 'pm.product_caterogy_id', '=', 'fc.id')
                        ->where('pm.active', 1)
                        ->where('pl.type', 1)
                        ->where('pm.only_parkaging', 0)
                        ->where('pm.plan_list_id', '!=', 0)
                        ->where('pm.plan_list_id', '>', 23)
                        ->where('pm.deparment_code', $production_code)
                        ->select(
                                'pm.plan_list_id',
                                'fc.batch_qty',
                                DB::raw("DATE_FORMAT(pm.expected_date, '%m-%Y') as expected_month"),
                                DB::raw("DATE_FORMAT(sp.actual_start, '%m-%Y') as actual_month"),
                                DB::raw("
                                CASE
                                WHEN pm.cancel = 1 THEN 'Hủy'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code < 7 AND sp_max.max_stage_code = sp_possible.max_possible_stage_code THEN 'Hoàn Tất'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 1 THEN 'Đã Cân'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 3 THEN 'Đã Pha chế'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 4 THEN 'Đã THT'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 5 THEN 'Đã định hình'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 6 THEN 'Đã Bao phim'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 7 THEN 'Hoàn Tất ĐG'
                                ELSE 'Chưa làm'
                                END AS status
                        ")
                        )
                        ->get();


                /*
                |--------------------------------------------------------------------------
                | 5. GOM THEO PLAN_LIST
                |--------------------------------------------------------------------------
                */

                $batch_summary = $batch_status
                        ->groupBy('plan_list_id')
                        ->map(function ($rows) {

                                $statusCount = $rows->groupBy('status')->map->count();

                                return (object)[
                                        'tong_lo' => $rows->count(),
                                        'status_counts' => $statusCount,
                                        'batch_qty_pending' => $rows
                                                ->where('status', 'Chưa làm')
                                                ->sum('batch_qty'),
                                        'batch_qty_not_finished' => $rows
                                                ->whereNotIn('status', ['Hoàn Tất ĐG', 'Hủy'])
                                                ->sum('batch_qty'),
                                ];
                        });

                /*
                |--------------------------------------------------------------------------
                | 5.1 GOM THEO THÁNG EXPECTED DATE
                |--------------------------------------------------------------------------
                */

                $summary_by_month = $batch_status
                        ->filter(function ($row) {
                                return !empty($row->expected_month);
                        })
                        ->groupBy('expected_month')
                        ->map(function ($rows, $month) {

                                $statusCount = $rows->groupBy('status')->map->count();

                                return (object)[
                                        'month' => $month,
                                        'tong_lo' => $rows->count(),
                                        'total_batch_qty' => $rows->sum('batch_qty'),
                                        'status_counts' => $statusCount,
                                        'batch_qty_pending' => $rows
                                                ->where('status', 'Chưa làm')
                                                ->sum('batch_qty'),
                                        'batch_qty_not_finished' => $rows
                                                ->whereNotIn('status', ['Hoàn Tất ĐG', 'Hủy'])
                                                ->sum('batch_qty'),
                                ];
                        })
                        ->sortByDesc(function ($item) {
                                return \Carbon\Carbon::createFromFormat('m-Y', $item->month);
                        });

                /*
                |--------------------------------------------------------------------------
                | 5.2 GOM THEO THÁNG ACTUAL START (THỰC TẾ)
                |--------------------------------------------------------------------------
                */

                $yieldSub = DB::table('yields')
                        ->select('stage_plan_id', DB::raw('SUM(yield) as total_yield'))
                        ->groupBy('stage_plan_id');

                $actual_stages = DB::table('stage_plan as sp')
                        ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                        ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
                        ->join('finished_product_category as fc', 'pm.product_caterogy_id', '=', 'fc.id')
                        ->leftJoinSub($yieldSub, 'ys', function ($join) {
                                $join->on('sp.id', '=', 'ys.stage_plan_id');
                        })
                        ->where('pm.active', 1)
                        ->where('pl.type', 1)
                        ->where('pm.only_parkaging', 0)
                        ->where('pm.plan_list_id', '!=', 0)
                        ->where('pm.plan_list_id', '>', 23)
                        ->where('pm.deparment_code', $production_code)
                        ->whereNotNull('sp.actual_start')
                        ->where('sp.actual_start', '>=', '2026-01-01')
                        ->where('sp.finished', 1)
                        ->where('sp.active', 1)
                        ->select(
                                'sp.plan_master_id',
                                'sp.stage_code',
                                'fc.batch_qty',
                                DB::raw("COALESCE(ys.total_yield, 0) as total_yield"),
                                DB::raw("DATE_FORMAT(sp.actual_start, '%m-%Y') as actual_month"),
                                DB::raw("TIMESTAMPDIFF(MINUTE, sp.actual_start, sp.actual_end) as production_minutes"),
                                DB::raw("TIMESTAMPDIFF(MINUTE, sp.actual_start_clearning, sp.actual_end_clearning) as cleaning_minutes")
                        )
                        ->get();

                $off_days_query = DB::table('off_days')
                        ->where('off_date', '>=', '2026-01-01')
                        ->select(DB::raw("DATE_FORMAT(off_date, '%m-%Y') as month"), DB::raw('count(*) as count'))
                        ->groupBy('month')
                        ->pluck('count', 'month');

                $summary_by_actual_month = $actual_stages
                        ->groupBy('actual_month')
                        ->map(function ($rows, $month) use ($off_days_query) {
                                $status_counts = [];
                                $status_yields = [];
                                $status_production_minutes = [];
                                $status_cleaning_minutes = [];
                                $total_production_minutes = 0;
                                $total_cleaning_minutes = 0;

                                foreach ($rows as $row) {
                                        $status = '';
                                        if ($row->stage_code == 1) $status = 'Đã Cân';
                                        elseif ($row->stage_code == 3) $status = 'Đã Pha chế';
                                        elseif ($row->stage_code == 4) $status = 'Đã THT';
                                        elseif ($row->stage_code == 5) $status = 'Đã định hình';
                                        elseif ($row->stage_code == 6) $status = 'Đã Bao phim';
                                        elseif ($row->stage_code == 7) $status = 'Hoàn Tất ĐG';

                                        if ($status) {
                                                $status_counts[$status] = ($status_counts[$status] ?? 0) + 1;
                                                $status_yields[$status] = ($status_yields[$status] ?? 0) + $row->total_yield;

                                                $p_mins = $row->production_minutes > 0 ? $row->production_minutes : 0;
                                                $c_mins = $row->cleaning_minutes > 0 ? $row->cleaning_minutes : 0;

                                                $status_production_minutes[$status] = ($status_production_minutes[$status] ?? 0) + $p_mins;
                                                $status_cleaning_minutes[$status] = ($status_cleaning_minutes[$status] ?? 0) + $c_mins;

                                                $total_production_minutes += $p_mins;
                                                $total_cleaning_minutes += $c_mins;
                                        }
                                }

                                $unique_batches = $rows->unique('plan_master_id');

                                return (object)[
                                        'month' => $month,
                                        'tong_lo' => $unique_batches->count(),
                                        'total_batch_qty' => $unique_batches->sum('batch_qty'),
                                        'status_counts' => $status_counts,
                                        'status_yields' => $status_yields,
                                        'status_production_minutes' => $status_production_minutes,
                                        'status_cleaning_minutes' => $status_cleaning_minutes,
                                        'total_production_minutes' => $total_production_minutes,
                                        'total_cleaning_minutes' => $total_cleaning_minutes,
                                        'off_days' => $off_days_query[$month] ?? 0,
                                ];
                        })
                        ->sortByDesc(function ($item) {
                                return \Carbon\Carbon::createFromFormat('m-Y', $item->month);
                        });

                /*
                |--------------------------------------------------------------------------
                | 6. MERGE VÀO PLAN LIST
                |--------------------------------------------------------------------------
                */

                $datas = $datas->map(function ($item) use ($total_batch_qtys, $batch_summary, $products_in_plans) {

                        $item->total_batch_qty =

                                $total_batch_qtys[$item->id]->total_batch_qty ?? 0;

                        $summary = $batch_summary[$item->id] ?? null;

                        $item->tong_lo = $summary->tong_lo ?? 0;

                        $item->status_counts = $summary->status_counts ?? collect();

                        $item->batch_qty_pending = $summary->batch_qty_pending ?? 0;

                        $item->batch_qty_not_finished = $summary->batch_qty_not_finished ?? 0;

                        $products = $products_in_plans[$item->id] ?? null;
                        $item->product_names = $products ? $products->product_names : '';
                        $item->product_codes = $products ? $products->product_codes : '';
                        $item->intermediate_codes = $products ? $products->intermediate_codes : '';

                        return $item;
                });


                /*
                |--------------------------------------------------------------------------
                | 7. TẠO PLAN "CHƯA THỰC HIỆN"
                |--------------------------------------------------------------------------
                */


                $pending_plan = (object)[
                        'id' => -1,
                        'deparment_code' => $production_code,
                        'prepared_by' => 'NA',
                        'created_at' => now(),
                        'send' => 1,
                        'send_by' => 'NA',
                        'send_date' => null,
                        'month' => 'NA',

                        'name' => 'KẾ HOẠCH CHƯA HOÀN TẤT ĐÓNG GÓI',
                        'total_batch_qty' => 0,
                        'tong_lo' => 0,
                        'status_counts' => [
                                'Chưa làm' => 0,
                                'Đã Cân' => 0,
                                'Đã Pha chế' => 0,
                                'Đã THT' => 0,
                                'Đã định hình' => 0,
                                'Đã Bao phim' => 0,
                        ],
                        'batch_qty_pending' => 0,
                        'product_names' => '',
                        'product_codes' => '',
                        'intermediate_codes' => '',
                ];


                foreach ($datas as $item) {
                        //dd ($item);
                        $notFinished = collect($item->status_counts)
                                ->except(['Hoàn Tất ĐG', 'Hoàn Tất', 'Hủy'])
                                ->sum();

                        if ($notFinished > 0) {

                                $pending_plan->tong_lo += $notFinished;

                                // cộng từng trạng thái
                                foreach ($item->status_counts as $status => $count) {

                                        if (!in_array($status, ['Hoàn Tất ĐG', 'Hoàn Tất', 'Hủy'])) {

                                                if (!isset($pending_plan->status_counts[$status])) {
                                                        $pending_plan->status_counts[$status] = 0;
                                                }

                                                $pending_plan->status_counts[$status] += $count;
                                        }
                                }

                                $pending_plan->total_batch_qty += $item->batch_qty_not_finished ?? 0;

                                if (!empty($item->product_names)) {
                                        $pending_plan->product_names .= ($pending_plan->product_names ? ', ' : '') . $item->product_names;
                                }
                                if (!empty($item->product_codes)) {
                                        $pending_plan->product_codes .= ($pending_plan->product_codes ? ', ' : '') . $item->product_codes;
                                }
                                if (!empty($item->intermediate_codes)) {
                                        $pending_plan->intermediate_codes .= ($pending_plan->intermediate_codes ? ', ' : '') . $item->intermediate_codes;
                                }
                        }
                }


                if ($pending_plan->tong_lo > 0) {
                        $datas->prepend($pending_plan);
                }


                /*
                |--------------------------------------------------------------------------
                | 8. RETURN VIEW
                |--------------------------------------------------------------------------
                */

                session()->put(['title' => 'KẾ HOẠCH SẢN XUẤT THÁNG']);
                //dd ($datas);
                return view('pages.plan.production.plan_list', [
                        'datas' => $datas,
                        'summary_by_month' => $summary_by_month,
                        'summary_by_actual_month' => $summary_by_actual_month,
                        'production_code' => $production_code,
                ]);
        }

        public function create_plan_list(Request $request)
        {
                $request->validateWithBag('createErrors', [
                        'name'  => 'required',
                        'month' => 'required|integer|between:1,12',
                        'year'  => 'required|integer|min:2020',
                ], [
                        'name.required'  => 'Vui lòng nhập tên kế hoạch.',
                        'month.required' => 'Vui lòng chọn tháng.',
                        'year.required'  => 'Vui lòng chọn năm.',
                ]);

                DB::table('plan_list')->insert([
                        'name'            => $request->name,
                        'month'           => $request->month,
                        'year'            => $request->year,
                        'type'            => 1,
                        'send'            => false,
                        'deparment_code'  => session('user')['production_code'],
                        'prepared_by'     => session('user')['fullName'],
                        'created_at'      => now(),
                ]);
                return redirect()->back()->with('success', "Tạo Mới $request->name Thành Công!");
        }

        public function open(Request  $request)
        {

                $maxStageFinished = DB::table('stage_plan')
                        ->when($request->plan_list_id >= 0, function ($q) use ($request) {
                                $q->where('stage_plan.plan_list_id', $request->plan_list_id);
                        })
                        ->where('finished', 1)
                        ->where('stage_code', '!=', 8)
                        ->select(
                                'plan_master_id',
                                DB::raw('MAX(stage_code) as max_stage_code')
                        )
                        ->groupBy('plan_master_id');

                $maxPossibleStage = DB::table('stage_plan')
                        ->when($request->plan_list_id >= 0, function ($q) use ($request) {
                                $q->where('stage_plan.plan_list_id', $request->plan_list_id);
                        })
                        ->where('active', 1)
                        ->where('stage_code', '!=', 8)
                        ->select(
                                'plan_master_id',
                                DB::raw('MAX(stage_code) as max_possible_stage_code')
                        )
                        ->groupBy('plan_master_id');

                $datas = DB::table('plan_master')
                        ->join('plan_list as pl', 'plan_master.plan_list_id', '=', 'pl.id')
                        ->select(
                                'plan_master.*',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                'finished_product_category.IsHypothesis',
                                DB::raw('fp_name.name AS finished_product_name'),
                                DB::raw('im_name.name AS intermediate_product_name'),
                                'market.name as market',
                                'specification.name as specification',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'finished_product_category.deparment_code',
                                'source_material.name as source_material_name',

                                DB::raw("
                                CASE
                                        WHEN plan_master.cancel = 1 THEN 'Hủy'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = sp_possible.max_possible_stage_code THEN 'Hoàn Tất'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 1 THEN 'Đã Cân'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 3 THEN 'Đã Pha chế'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 4 THEN 'Đã THT'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 5 THEN 'Đã định hình'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 6 THEN 'Đã Bao phim'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 7 THEN 'Hoàn Tất ĐG'
                                        ELSE 'Chưa làm'
                                        END AS status
                                ")
                        )
                        ->whereIn('plan_master.plan_list_id', DB::table('plan_list')->where('deparment_code', session('user')['production_code'])->pluck('id'))
                        ->where('plan_master.plan_list_id', ">", 23)
                        ->where('plan_master.active', 1)
                        ->where('pl.type', 1)
                        ->when(
                                $request->plan_list_id < 0,
                                function ($q) use ($request) {
                                        if ($request->plan_list_id == -1) {
                                                return $q->where('plan_master.cancel', 0)
                                                        //->where('plan_master.only_parkaging', 0)
                                                        ->where(function ($sub) {
                                                                $sub->whereNull('sp_max.max_stage_code')
                                                                        ->orWhereRaw('sp_max.max_stage_code < sp_possible.max_possible_stage_code');
                                                        });
                                        } else {
                                                return $q->whereRaw("DATE_FORMAT(plan_master.expected_date, '%m-%Y') = ?", [$request->expected_month]);
                                        }
                                },
                                function ($q) use ($request) {
                                        return $q->where('plan_master.plan_list_id', $request->plan_list_id);
                                }
                        )
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
                        ->leftJoin('source_material', 'plan_master.material_source_id', '=', 'source_material.id')
                        ->leftJoin('product_name as fp_name', 'finished_product_category.product_name_id', '=', 'fp_name.id')
                        ->leftJoin('product_name as im_name', 'intermediate_category.product_name_id', '=', 'im_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                        ->leftJoin('specification', 'finished_product_category.specification_id', '=', 'specification.id')
                        ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                                $join->on('plan_master.main_parkaging_id', '=', 'sp_max.plan_master_id');
                        })
                        ->leftJoinSub($maxPossibleStage, 'sp_possible', function ($join) {
                                $join->on('plan_master.main_parkaging_id', '=', 'sp_possible.plan_master_id');
                        })
                        ->leftJoin('stage_plan', function ($join) {
                                $join->on('plan_master.main_parkaging_id', '=', 'stage_plan.plan_master_id')
                                        ->on('stage_plan.stage_code', '=', 'sp_max.max_stage_code');
                        })

                        ->orderBy('expected_date', 'asc')
                        ->orderBy('level', 'asc')
                        ->orderBy('batch', 'asc')
                        ->get();


                $planMasterIds = $datas->pluck('id')->toArray();

                $historyCounts = DB::table('plan_master_history')
                        ->select('plan_master_id', DB::raw('COUNT(*) as total'))
                        ->whereIn('plan_master_id', $planMasterIds)
                        ->groupBy('plan_master_id')
                        ->pluck('total', 'plan_master_id')
                        ->toArray();
                $datas = $datas->map(function ($item) use ($historyCounts) {
                        $item->history_count = $historyCounts[$item->id] ?? 0; // nếu không có history thì = 0
                        return $item;
                });

                $finished_product_category = DB::table('finished_product_category')
                        ->select(
                                'finished_product_category.*',
                                'product_name.name',
                                'market.name as market',
                                'specification.name as specification',
                                'intermediate_category.id as intermediate_caterogy_id'
                        )
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
                        ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                        ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id')
                        ->where('finished_product_category.active', 1)
                        ->where('finished_product_category.deparment_code', session('user')['production_code'])
                        ->orderBy('name', 'asc')
                        ->get();

                $source_material_list = []; // DB::table('source_material')
                // ->select('source_material.*', 'product_name.name as product_name')
                // ->leftJoin('intermediate_category', 'source_material.intermediate_code', 'intermediate_category.intermediate_code')
                // ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                // ->where('source_material.active', 1)->orderBy('source_material.name', 'asc')->get();



                $production  =  session('user')['production_name'];
                $plan_list_id_title =  DB::table('plan_list')->where('deparment_code', session('user')['production_code'])->pluck('name', 'id');

                session()->put(['title' => " $request->name - $production"]);


                return view('pages.plan.production.list', [
                        'datas' => $datas,
                        'plan_list_id' => $request->plan_list_id,
                        'month' => $request->month,
                        'production' => $request->production,
                        'finished_product_category' => $finished_product_category,
                        'source_material_list' => $source_material_list,
                        'send' => $request->send ?? 1,
                        'plan_list_id_title' => $plan_list_id_title

                ]);
        }

        public function history(Request $request)
        {
                //dd ($request->all());
                $histories = DB::table('plan_master_history')
                        ->select(
                                'plan_master_history.*',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                'product_name.name',
                                'market.name as market',
                                'specification.name as specification',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'finished_product_category.deparment_code',
                                'source_material.name as source_material_name'
                        )
                        ->where('plan_master_history.plan_master_id', $request->id)
                        ->leftJoin('finished_product_category', 'plan_master_history.product_caterogy_id', 'finished_product_category.id')
                        ->leftJoin('source_material', 'plan_master_history.material_source_id', 'source_material.id')
                        ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                        ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id')
                        ->orderBy('version', 'desc')->orderBy('expected_date', 'asc')->get();
                return response()->json($histories);
        }

        public function source_material(Request $request)
        {
                //>where ('intermediate_code', $request->intermediate_code)
                $source_material_list = DB::table('source_material')
                        ->select('source_material.*', 'product_name.name as product_name')
                        ->leftJoin('intermediate_category', 'source_material.intermediate_code', 'intermediate_category.intermediate_code')
                        ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                        ->where('source_material.active', 1)
                        ->where('source_material.intermediate_code', $request->intermediate_code)
                        ->orderBy('source_material.name', 'asc')->get();

                return response()->json($source_material_list);
        }

        public function store(Request $request)
        {
                //dd ($request->all());
                try {
                        $validator = Validator::make($request->all(), [
                                'product_caterogy_id' => 'required',
                                'plan_list_id'   => 'required',
                                'batch' => 'required',
                                'expected_date' => 'required',
                                'level' => 'required',
                                //'material_source_id' => 'required',

                        ], [
                                'product_caterogy_id' => 'Vui lòng chọn lại sản phẩm.',
                                'plan_list_id'   => 'Vui lòng chọn lại sản phẩm',
                                'batch' => 'Vui lòng nhập số lô',
                                'expected_date' => 'Vui lòng chọn ngày dự kiến KCS',
                                'level' => 'vui lòng chọn mức độ ưu tiên',
                                //'material_source_id' => 'vui lòng chọn nguồn nguyên liệu',
                        ]);


                        if ($validator->fails()) {
                                return redirect()->back()
                                        ->withErrors($validator, 'createErrors')
                                        ->withInput();
                        }

                        $first_val_batch = $request->first_val_batch == "on" ? 1 : 0;
                        $second_val_batch = $request->second_val_batch == "on" ? 1 : 0;
                        $third_val_batch = $request->third_val_batch == "on" ? 1 : 0;
                        $total =  $first_val_batch +  $second_val_batch + $third_val_batch;

                        $current_val_batch = 0;
                        if ($first_val_batch == 1) {
                                $current_val_batch = 1;
                        } else if ($second_val_batch == 1) {
                                $current_val_batch = 2;
                        } else if ($third_val_batch == 1) {
                                $current_val_batch = 3;
                        }

                        $code_val_part_0 = explode("_", $request->code_val_first)[0];

                        // // Tạo số lô
                        $batches = [];

                        if ($request->format_batch_no == "on") {
                                $prefix = Str::substr($request->batch, -4);
                                $aa     = intval(Str::substr($request->batch, 0, Str::length($request->batch) - 4));
                                for ($i = 1; $i <= $request->number_of_batch; $i++) {
                                        $charater_val = ""; //($i <= $total) ? "V" : "";
                                        $batches[] = sprintf("%02d", $aa) . $prefix . $charater_val;
                                        $aa++;
                                }
                        } else {
                                $prefix = Str::substr($request->batch, 0, 3);
                                $aa     = intval(Str::substr($request->batch, 3, 3));
                                for ($i = 1; $i <= $request->number_of_batch; $i++) {
                                        $charater_val = ($i <= $total) ? "V" : "";
                                        $batches[] = $prefix . sprintf("%02d", $aa) . $charater_val;
                                        $aa++;
                                }
                        }

                        $first_val_batch = $request->first_val_batch == "on" ? 1 : 0;
                        $second_val_batch = $request->second_val_batch == "on" ? 1 : 0;
                        $third_val_batch = $request->third_val_batch == "on" ? 1 : 0;

                        $deparment_code = DB::table('finished_product_category')
                                ->where('id', $request->product_caterogy_id)
                                ->value('deparment_code') ?? session('user')['production_code'];

                        $i = 1;


                        foreach ($batches as  $batch) {
                                if ($i <= $total) {
                                        $code_val_part_1 = $current_val_batch - 1 + $i;
                                }

                                //dd ($total, $current_val_batch, $code_val_part_1);
                                // Insert vào plan_master
                                $planMasterId = DB::table('plan_master')->insertGetId([
                                        "product_caterogy_id" => $request->product_caterogy_id,
                                        "plan_list_id" => $request->plan_list_id,
                                        "batch" => $batch,
                                        "expected_date" => $request->expected_date,
                                        "responsed_date" => $request->expected_date,
                                        "level" => $request->level,
                                        "is_val" => ($i <= $total) ? 1 : 0,
                                        "code_val" => ($i <= $total) ? $code_val_part_0 . "_" . $code_val_part_1 : null,

                                        "after_weigth_date" => $request->after_weigth_date,
                                        "after_parkaging_date" => $request->after_parkaging_date,

                                        "allow_weight_before_date" => $request->allow_weight_before_date,
                                        "expired_material_date" => $request->expired_material_date,
                                        "preperation_before_date" => $request->preperation_before_date,
                                        "blending_before_date" => $request->blending_before_date,
                                        "coating_before_date" => $request->coating_before_date,

                                        "parkaging_before_date" => $request->parkaging_before_date,
                                        "expired_packing_date" => $request->expired_packing_date,

                                        //"material_source_id" => $request->material_source_id,
                                        "percent_parkaging" => 1,
                                        "number_parkaging" => $request->max_number_of_unit,
                                        "only_parkaging" => 0,
                                        "note" => $request->note ?? "NA",
                                        'deparment_code' => $deparment_code,
                                        'prepared_by' => session('user')['fullName'],
                                        'created_at' => now(),
                                ]);

                                // Cập nhật lại chính bản ghi đó
                                DB::table('plan_master')
                                        ->where('id', $planMasterId)
                                        ->update(['main_parkaging_id' => $planMasterId]);

                                $insertData = [];

                                $materials = $request->input('materials', []);

                                foreach ($materials as $code => $item) {

                                        $insertData[] = [

                                                'plan_master_id'          => $planMasterId,
                                                'material_packaging_code' => (string) $code,
                                                'material_packaging_type' => 0,
                                                'Revno'                   => $item['Revno'],
                                                'qty'                     => (float) $item['qty'],
                                                'unit_bom'                => $item['uom'],
                                                'MaterialName'            => $item['MaterialName'],
                                                'created_at'              => now(),
                                                'created_by'              => session('user')['fullName'],
                                                'active'                  => $item['active'],
                                        ];
                                }

                                $packagings = $request->input('packagings', []);

                                foreach ($packagings as $code => $item) {

                                        $insertData[] = [

                                                'plan_master_id'          => $planMasterId,
                                                'material_packaging_code' => (string) $code,
                                                'material_packaging_type' => 1,
                                                'Revno'                   => $item['Revno'],
                                                'qty'                     => (float) $item['qty'],
                                                'unit_bom'                => $item['uom'],
                                                'MaterialName'            => $item['MaterialName'],
                                                'created_at'              => now(),
                                                'created_by'              => session('user')['fullName'],
                                                'active'                  => $item['active'],

                                        ];
                                }


                                if (!empty($insertData)) {
                                        DB::table('plan_master_materials')->upsert(
                                                $insertData,
                                                ['plan_master_id', 'material_packaging_code', 'material_packaging_type'],
                                                ['qty', 'unit_bom', 'active', 'Revno']
                                        );
                                }

                                // Insert vào plan_master_history
                                DB::table('plan_master_history')->insert([
                                        "plan_master_id" => $planMasterId,
                                        "plan_list_id" => $request->plan_list_id,
                                        "product_caterogy_id" => $request->product_caterogy_id,
                                        "batch" => $batch,
                                        "expected_date" => $request->expected_date,
                                        "level" => $request->level,
                                        "is_val" => ($i <= $total) ? 1 : 0,
                                        "after_weigth_date" => $request->after_weigth_date,
                                        "after_parkaging_date" => $request->after_parkaging_date,

                                        "allow_weight_before_date" => $request->allow_weight_before_date,
                                        "expired_material_date" => $request->expired_material_date,
                                        "preperation_before_date" => $request->preperation_before_date,
                                        "blending_before_date" => $request->blending_before_date,
                                        "coating_before_date" => $request->coating_before_date,

                                        "material_source_id" => $request->material_source_id,
                                        "percent_parkaging" => 1,
                                        "number_parkaging" => $request->max_number_of_unit,
                                        "only_parkaging" => 0,
                                        "note" => $request->note ?? "NA",
                                        'deparment_code' => session('user')['production_code'],
                                        'prepared_by' => session('user')['fullName'],
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                        "version" => 1,
                                        "reason" => "Tạo Mới", // lần đầu tạo thì version = 1
                                ]);

                                $i++;
                        }
                } catch (\Throwable $e) {
                        Log::error('Lỗi store plan_master', [
                                'message' => $e->getMessage(),
                                'file'    => $e->getFile(),
                                'line'    => $e->getLine(),
                                'request' => $request->all(),
                                'user'    => session('user') ?? null,
                        ]);
                        return redirect()->back()
                                ->with('error', 'Có lỗi xảy ra, vui lòng kiểm tra log!');
                }
                return redirect()->back()->with('success', 'Đã thêm thành công!');
        }

        public function store_source(Request $request)
        {

                $validator = Validator::make($request->all(), [
                        'name' => 'required',
                ], [
                        'name.required' => 'Vui lòng nhập nguồn nguyên liệu',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'create_source_Errors')->withInput();
                }

                // Update dữ liệu chính
                $id = DB::table('source_material')->insertGetId([
                        "intermediate_code" => $request->intermediate_code,
                        "name" => $request->name,
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                return response()->json([
                        'id'   => $id,
                        'name' => $request->name
                ]);
        }

        public function update(Request $request)
        {
                // dd ($request->all());
                $validator = Validator::make($request->all(), [

                        'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',


                        // 'material_source_id' => 'required',

                ], [

                        'batch' => 'Vui lòng nhập số lô',
                        'expected_date' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level' => 'vui lòng chọn mức độ ưu tiên',


                        //'material_source_id' => 'vui lòng chọn nguồn nguyên liệu',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'update_Errors')->withInput();
                }

                $first_val_batch = $request->first_val_batch == "on" ? 1 : 0;
                $second_val_batch = $request->second_val_batch == "on" ? 1 : 0;
                $third_val_batch = $request->third_val_batch == "on" ? 1 : 0;

                $is_val = 0;
                $code_val = null;
                if ($first_val_batch == 1) {
                        $code_val_part_0 = explode("_", $request->code_val_first)[0];
                        $is_val = 1;
                        $code_val =  $code_val_part_0 . "_1";
                } else if ($second_val_batch == 1) {
                        $code_val_part_0 = explode("_", $request->code_val_first)[0];
                        $is_val = 1;
                        $code_val =  $code_val_part_0 . "_2";
                } else if ($third_val_batch == 1) {
                        $code_val_part_0 = explode("_", $request->code_val_first)[0];
                        $is_val = 1;
                        $code_val =  $code_val_part_0 . "_3";
                }

                // Update dữ liệu chính
                DB::table('plan_master')->where('main_parkaging_id', $request->id)->update([
                        "batch" => $request->batch,
                        "expected_date" => $request->expected_date,
                        "level" => $request->level,
                        "is_val" => $is_val,
                        "code_val" => $code_val,
                        "after_weigth_date" => $request->after_weigth_date,

                        "after_parkaging_date" => $request->after_parkaging_date,

                        "material_source_id" => $request->material_source_id,
                        "note" => $request->note ?? "NA",
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);

                // Lấy dữ liệu gốc từ plan_master
                $plan = DB::table('plan_master')->where('id', $request->id)->first();


                //  update recipe
                $allItems = array_merge(
                        $request->input('materials', []),
                        $request->input('packagings', [])
                );
                //dd ($allItems);
                foreach ($allItems as $item) {
                        DB::table('plan_master_materials')
                                ->where('id', $item['id'])
                                ->update([
                                        'active' => $item['active'],
                                        'updated_at' => now(),
                                        'created_by' => session('user')['fullName']
                                ]);
                }


                // Tìm version cao nhất hiện tại trong history
                $lastVersion = DB::table('plan_master_history')
                        ->where('plan_master_id', $request->id)
                        ->max('version');

                $newVersion = $lastVersion ? $lastVersion + 1 : 1;
                DB::table('plan_master_history')->insert([
                        'plan_master_id' => $plan->id,
                        'plan_list_id' => $plan->plan_list_id,
                        'product_caterogy_id' => $plan->product_caterogy_id,
                        'version' => $newVersion,

                        'level' => $request->level,
                        'batch' => $request->batch,
                        'expected_date' => $request->expected_date,
                        'is_val' => $request->is_val == null ? 0 : 1,
                        'after_weigth_date' => $request->after_weigth_date,

                        'after_parkaging_date' => $request->after_parkaging_date,

                        'material_source_id' => $request->material_source_id,
                        'percent_parkaging' => $plan->percent_parkaging,
                        'only_parkaging' => $plan->only_parkaging,
                        "number_parkaging" => $plan->number_parkaging,
                        'note' => $request->note ?? "NA",
                        'reason' => $request->reason ?? "NA",
                        'deparment_code' => $plan->deparment_code,

                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                        'updated_at' => now(),
                ]);

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');
        }

        public function splitting(Request $request)
        {

                //dd ($request->all());
                try {
                        $validator = Validator::make($request->all(), [
                                //'batch' => 'required',
                                'expected_date' => 'required',
                                'level' => 'required',
                                'percent_packaging' => 'required',
                                'number_of_unit' => 'required',
                        ], [
                                //'batch.required' => 'Vui lòng nhập số lô',
                                'expected_date.required' => 'Vui lòng chọn ngày dự kiến KCS',
                                'level.required' => 'Vui lòng chọn mức độ ưu tiên',
                                'percent_packaging.required' => 'Vui lòng nhập số lượng đơn vị liều đóng gói',
                                'number_of_unit.required' => 'Vui lòng chọn số lượng đóng gói',
                        ]);


                        if ($validator->fails()) {
                                return redirect()->back()->withErrors($validator, 'update_Errors')->withInput();
                        }



                        $mainPlanMaster = DB::table('plan_master')->where('id', $request->id)->first();
                        $main_intermediate_code = DB::table('finished_product_category')->where('id', $mainPlanMaster->product_caterogy_id)->value('intermediate_code');


                        if ($request->intermediate_code != $main_intermediate_code) {
                                $error = ['intermediate_code' => 'Mã bán thành phẩm không khớp với sản phẩm chính.'];
                                return redirect()->back()->withErrors($error, 'update_Errors')->withInput();
                        }

                        $planMasterId = DB::table('plan_master')->insertGetId([
                                "product_caterogy_id" => $request->product_caterogy_id,
                                "plan_list_id" => $mainPlanMaster->plan_list_id,
                                "batch" => $mainPlanMaster->batch,
                                "expected_date" => $request->expected_date,
                                "level" => $request->level,
                                "is_val" => $mainPlanMaster->is_val,
                                "code_val" => $mainPlanMaster->code_val,
                                "after_weigth_date" => $mainPlanMaster->after_weigth_date,
                                "after_parkaging_date" => $mainPlanMaster->after_parkaging_date,
                                "material_source_id" => $mainPlanMaster->material_source_id,
                                "percent_parkaging" => round($request->number_of_unit / $request->max_number_of_unit, 4),
                                "number_parkaging" => $request->number_of_unit,
                                "only_parkaging" => 1,
                                "note" => $request->note ?? "NA",
                                'deparment_code' => session('user')['production_code'],
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                        ]);

                        DB::table('plan_master')
                                ->where('id', $planMasterId)
                                ->update(['main_parkaging_id' => $request->id]);

                        DB::table('plan_master')
                                ->where('id', $request->id)
                                ->update([
                                        'number_parkaging' => $mainPlanMaster->number_parkaging - $request->number_of_unit,
                                        "percent_parkaging" => round(($mainPlanMaster->number_parkaging - $request->number_of_unit) / $request->max_number_of_unit, 4),
                                ]);


                        $packagings = $request->input('packagings', []);

                        foreach ($packagings as $code => $item) {
                                $insertData[] = [
                                        'plan_master_id'          => $planMasterId,
                                        'material_packaging_code' => (string) $code,
                                        'material_packaging_type' => 1,
                                        'Revno'                   => $item['Revno'],
                                        'qty'                     => (float) $item['qty'],
                                        'unit_bom'                => $item['uom'],
                                        'MaterialName'            => $item['MaterialName'],
                                        'created_at'              => now(),
                                        'created_by'              => session('user')['fullName'],
                                        'active'                  => $item['active'],

                                ];
                        }

                        if (!empty($insertData)) {
                                DB::table('plan_master_materials')->upsert(
                                        $insertData,
                                        ['plan_master_id', 'material_packaging_code', 'material_packaging_type'],
                                        ['qty', 'unit_bom', 'active', 'Revno']
                                );
                        }


                        // Insert vào plan_master_history
                        DB::table('plan_master_history')->insert([
                                "plan_master_id" => $planMasterId,
                                "plan_list_id" => $mainPlanMaster->plan_list_id,
                                "product_caterogy_id" => $mainPlanMaster->product_caterogy_id,
                                "batch" => $mainPlanMaster->batch,
                                "expected_date" => $request->expected_date,
                                "level" => $request->level,
                                "is_val" => $mainPlanMaster->is_val,
                                "after_weigth_date" => $mainPlanMaster->after_weigth_date,

                                "after_parkaging_date" => $mainPlanMaster->after_parkaging_date,

                                "material_source_id" => $mainPlanMaster->material_source_id,
                                "percent_parkaging" => round($request->number_of_unit / $request->max_number_of_unit, 2),
                                "number_parkaging" =>  $request->number_of_unit,
                                "only_parkaging" => 1,
                                "note" => $request->note ?? "NA",
                                'deparment_code' => session('user')['production_code'],
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                                'updated_at' => now(),
                                "version" => 1,
                                "reason" => "Chia Lô Đóng Gói", // lần đầu tạo thì version = 1
                        ]);
                } catch (\Throwable $e) {
                        Log::error('Lỗi store plan_master', [
                                'message' => $e->getMessage(),
                                'file'    => $e->getFile(),
                                'line'    => $e->getLine(),
                                'request' => $request->all(),
                                'user'    => session('user') ?? null,
                        ]);
                        return redirect()->back()
                                ->with('error', 'Có lỗi xảy ra, vui lòng kiểm tra log!');
                }

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');
        }

        public function splittingUpdate(Request $request)
        {

                $validator = Validator::make($request->all(), [
                        //'batch' => 'required',
                        'expected_date' => 'required',
                        'level' => 'required',
                        'percent_packaging' => 'required',
                        'number_of_unit' => 'required',
                ], [
                        //'batch.required' => 'Vui lòng nhập số lô',
                        'expected_date.required' => 'Vui lòng chọn ngày dự kiến KCS',
                        'level.required' => 'Vui lòng chọn mức độ ưu tiên',
                        'percent_packaging.required' => 'Vui lòng nhập số lượng đơn vị liều đóng gói',
                        'number_of_unit.required' => 'Vui lòng chọn số lượng đóng gói',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'update_Errors')->withInput();
                }

                $mainPlanMaster = DB::table('plan_master')->where('id', $request->id)->first();

                DB::table('plan_master')->where('id', $request->id)->update([
                        "batch" => $mainPlanMaster->batch,
                        "expected_date" => $request->expected_date,
                        "level" => $request->level,
                        "percent_parkaging" => round($request->number_of_unit / $request->max_number_of_unit, 4),
                        "number_parkaging" => $request->number_of_unit,
                        "note" => $request->note ?? "NA",
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                $sum_number_parkaging =  DB::table('plan_master')->where('active', 1)->where('main_parkaging_id', $mainPlanMaster->main_parkaging_id)->where('only_parkaging', 1)->sum('number_parkaging');
                //dd ($request->all());
                DB::table('plan_master')
                        ->where('id', $mainPlanMaster->main_parkaging_id)
                        ->update([
                                'number_parkaging' => $request->max_number_of_unit - $sum_number_parkaging,
                                "percent_parkaging" => round(($request->max_number_of_unit - $sum_number_parkaging) / $request->max_number_of_unit, 4),
                        ]);

                $allItems = array_merge(
                        $request->input('packagings', [])
                );

                //dd ($allItems);
                foreach ($allItems as $item) {
                        DB::table('plan_master_materials')
                                ->where('id', $item['id'])
                                ->update([
                                        'active' => $item['active'],
                                        'updated_at' => now(),
                                        'created_by' => session('user')['fullName']
                                ]);
                }

                // Insert vào plan_master_history
                DB::table('plan_master_history')->insert([
                        "plan_master_id" => $mainPlanMaster->id,
                        "plan_list_id" => $mainPlanMaster->plan_list_id,
                        "product_caterogy_id" => $mainPlanMaster->product_caterogy_id,
                        "batch" => $mainPlanMaster->batch,
                        "expected_date" => $request->expected_date,
                        "level" => $request->level,
                        "is_val" => $mainPlanMaster->is_val,
                        "after_weigth_date" => $mainPlanMaster->after_weigth_date,

                        "after_parkaging_date" => $mainPlanMaster->after_parkaging_date,

                        "material_source_id" => $mainPlanMaster->material_source_id,
                        "percent_parkaging" => round($request->number_of_unit / $request->max_number_of_unit, 2),
                        "number_parkaging" =>  $request->number_of_unit,
                        "only_parkaging" => 1,
                        "note" => $request->note ?? "NA",
                        'deparment_code' => session('user')['production_code'],
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        "version" => 1,
                        "reason" => "Cập Nhật Chia Lô Đóng Gói", // lần đầu tạo thì version = 1
                ]);

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');
        }

        public function deActive(Request $request)
        {

                $reason = $request->deactive_reason;
                $updatesql = [
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ];

                $active_stage_plan = 1;
                if ($request->type === 'delete') {
                        $updatesql['active'] = 0;
                        $active_stage_plan = 0;
                } elseif ($request->type === 'cancel') {
                        $updatesql['cancel'] = 1;
                        $active_stage_plan = 0;
                } elseif ($request->type === 'restore') {
                        $updatesql['cancel'] = 0;
                        $active_stage_plan = 1;
                }
                if ($request->only_parkaging == 1) {

                        $main_parkaging_id =  DB::table('plan_master')->where('id', $request->id)->value('main_parkaging_id');

                        $max_number_parkaging =  DB::table('plan_master')->where('active', 1)->where('main_parkaging_id', $main_parkaging_id)->sum('number_parkaging');

                        DB::table('plan_master')->where('id', $request->id)->update($updatesql);


                        $sum_number_parkaging =  DB::table('plan_master')->where('active', 1)->where('main_parkaging_id', $main_parkaging_id)->where('only_parkaging', 1)->sum('number_parkaging');


                        DB::table('plan_master')
                                ->where('id', $main_parkaging_id)
                                ->update([
                                        'number_parkaging' => $max_number_parkaging - $sum_number_parkaging,
                                        "percent_parkaging" => round(($max_number_parkaging - $sum_number_parkaging) / $max_number_parkaging, 4),
                                ]);
                } else {
                        DB::table('plan_master')->where('main_parkaging_id', $request->id)->update($updatesql);
                }

                $latest = DB::table('plan_master_history')
                        ->where('plan_master_id', $request->id)
                        ->orderByDesc('version')
                        ->first();

                if ($latest) {
                        DB::table('plan_master_history')
                                ->where('id', $latest->id)
                                ->update(['reason' => $reason]);
                }

                DB::table('stage_plan')->where('plan_master_id', $request->id)->update([
                        'active' => $active_stage_plan
                ]);

                return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
        }

        public function send(Request $request)
        {

                $exists = DB::table('stage_plan')->where('plan_list_id', $request->plan_list_id)->exists();
                if ($exists) {
                        return redirect()->route('pages.plan.production.list');
                }

                // Phần 1: Các plan không chỉ đóng gói (only_parkaging = 0)
                $plans_main = DB::table('plan_master')
                        ->join('plan_list as pl', 'plan_master.plan_list_id', '=', 'pl.id')
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('intermediate_category', 'intermediate_category.intermediate_code', '=', 'finished_product_category.intermediate_code')
                        ->leftJoin('dosage', 'intermediate_category.dosage_id', '=', 'dosage.id')
                        ->where('plan_master.plan_list_id', $request->plan_list_id)
                        ->where('plan_master.active', 1)
                        ->where('plan_master.cancel', 0)
                        ->where('plan_master.only_parkaging', 0)
                        ->where('finished_product_category.IsHypothesis', 0)
                        ->where('pl.type', 1)
                        ->select(
                                'plan_master.id',
                                'plan_master.plan_list_id',
                                'plan_master.product_caterogy_id',
                                'plan_master.expected_date',
                                'plan_master.level',
                                'plan_master.batch',
                                'plan_master.only_parkaging',
                                'plan_master.percent_parkaging',
                                'plan_master.main_parkaging_id',
                                'intermediate_category.weight_1',
                                'intermediate_category.weight_2',
                                'intermediate_category.prepering',
                                'intermediate_category.blending',
                                'intermediate_category.forming',
                                'intermediate_category.coating',
                                'intermediate_category.batch_size',
                                'finished_product_category.primary_parkaging',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                'finished_product_category.batch_qty',
                                DB::raw("
                                CASE
                                        WHEN dosage.name LIKE '%phim%' THEN 1
                                        WHEN dosage.name LIKE '%nang%' THEN 0
                                        ELSE NULL
                                END AS w2
                                ")
                        )
                        ->orderBy('expected_date', 'asc')
                        ->orderBy('level', 'asc')
                        ->orderByRaw('batch + 0 ASC')
                        ->get();



                // Phần 2: Các plan chỉ đóng gói (only_parkaging = 1)
                $plans_packaging = DB::table('plan_master')
                        ->join('plan_list as pl', 'plan_master.plan_list_id', '=', 'pl.id')
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('intermediate_category', 'intermediate_category.intermediate_code', '=', 'finished_product_category.intermediate_code')
                        ->where('plan_master.plan_list_id', $request->plan_list_id)
                        ->where('plan_master.active', 1)
                        ->where('plan_master.cancel', 0)
                        ->where('plan_master.only_parkaging', 1)
                        ->where('finished_product_category.IsHypothesis', 0)
                        ->where('pl.type', 1)
                        ->select(
                                'plan_master.id',
                                'plan_master.plan_list_id',
                                'plan_master.product_caterogy_id',
                                'plan_master.expected_date',
                                'plan_master.level',
                                'plan_master.batch',
                                'plan_master.only_parkaging',
                                'plan_master.percent_parkaging',
                                'plan_master.main_parkaging_id',
                                'intermediate_category.weight_1',
                                'intermediate_category.weight_2',
                                'intermediate_category.prepering',
                                'intermediate_category.blending',
                                'intermediate_category.forming',
                                'intermediate_category.coating',
                                'intermediate_category.batch_size',
                                'finished_product_category.primary_parkaging',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                'finished_product_category.batch_qty'
                        )
                        ->orderBy('expected_date', 'asc')
                        ->orderBy('level', 'asc')
                        ->orderByRaw('batch + 0 ASC')
                        ->get();


                $stages = ['weight_1', 'weight_2', 'prepering', 'blending', 'forming', 'coating', 'primary_parkaging'];
                $stage_code = [
                        'weight_1'              => 1,
                        'weight_2'              => 2,
                        'prepering'             => 3,
                        'blending'              => 4,
                        'forming'               => 5,
                        'coating'               => 6,
                        'primary_parkaging'     => 7,
                ];

                $dataToInsert = [];

                foreach ($plans_main as $plan) {
                        $stageList = [];

                        // Vòng 1: gom các stage có tồn tại cho plan này
                        foreach ($stages as $index => $stage) {
                                if ($plan->$stage) {
                                        $stageList[] = [
                                                'w2'            => $plan->w2,
                                                'code'          => $plan->id . "_" . $stage_code[$stage],
                                                'stage_code'    => $stage_code[$stage],
                                                'order_by'      => $index,
                                        ];
                                }
                        }


                        // Vòng 2: set predecessor và nextcessor
                        foreach ($stageList as $i => $stageItem) {
                                $prevCode = null;
                                $nextCode = null;

                                // ✅ set prevCode
                                if ($i > 0) {
                                        $prevItem = $stageList[$i - 1];

                                        if ($stageItem['stage_code'] >= 3 && $prevItem['stage_code'] == 2) {
                                                $prevCode = collect($stageList)->firstWhere('stage_code', 1)['code'] ?? null;
                                        } elseif ($stageItem['stage_code'] == 2) {
                                                $prevCode = null;
                                        } else {
                                                $prevCode = $prevItem['code'];
                                        }
                                }



                                // ✅ set nextCode
                                if ($i < count($stageList) - 1) {
                                        $nextItem = $stageList[$i + 1];
                                        // nếu stage hiện tại = 1 và next là 2 thì bỏ qua, tìm stage_code >= 3
                                        if ($stageItem['stage_code'] == 1 && ($nextItem['stage_code'] == 2)) {
                                                $nextCode = collect($stageList)->first(fn($s) => $s['stage_code'] >= 3)['code'] ?? null;
                                        } elseif ($stageItem['stage_code'] == 2) {
                                                if (session('user')['production_code'] == 'PXTN' && $plan->weight_2 == 1) {
                                                        $nextCode = explode("_", $nextItem['code'])[0] . "_7";
                                                } else {
                                                        if ($stageItem['w2'] == 1) {
                                                                $nextCode = explode("_", $nextItem['code'])[0] . "_6";
                                                        } else {
                                                                $nextCode = explode("_", $nextItem['code'])[0] . "_5";
                                                        }
                                                }
                                        } else {
                                                $nextCode = $nextItem['code'];
                                        }
                                }



                                $tank = DB::table('quota')
                                        ->select('tank', 'keep_dry')
                                        ->when($stageItem['stage_code'] != 7, function ($q) use ($plan, $stageItem) {
                                                return $q->where('intermediate_code', $plan->intermediate_code)
                                                        ->where('stage_code', $stageItem['stage_code']);
                                        })
                                        ->when($stageItem['stage_code'] == 7, function ($q) use ($plan, $stageItem) {
                                                return $q->where('finished_product_code', $plan->finished_product_code)
                                                        ->where('stage_code', $stageItem['stage_code']);
                                        })
                                        ->first();


                                $dataToInsert[] = [
                                        'plan_list_id'        => $plan->plan_list_id,
                                        'plan_master_id'      => $plan->id,
                                        'product_caterogy_id' => $plan->product_caterogy_id,
                                        'stage_code'          => $stageItem['stage_code'],
                                        'order_by'            => $stageItem['order_by'],
                                        'code'                => $stageItem['code'],
                                        'predecessor_code'    => $prevCode,
                                        'nextcessor_code'     => $nextCode,
                                        'tank'                => $tank->tank ?? 0,
                                        'keep_dry'            => $tank->keep_dry ?? 0,
                                        'deparment_code'      => session('user')['production_code'],
                                        'created_date'        => now(),
                                        'Theoretical_yields' => $stageItem['stage_code'] <= 4 ? $plan->batch_size : $plan->batch_qty,
                                        'Theoretical_yields_qty'        => $plan->batch_qty
                                ];

                                if ($plan->percent_parkaging  < 1 && $stageItem['stage_code'] == 7) {
                                        $plan_packagings = $plans_packaging->where('main_parkaging_id', $plan->id);
                                        foreach ($plan_packagings as $plan_packaging) {
                                                $dataToInsert[] = [
                                                        'plan_list_id'        => $plan_packaging->plan_list_id,
                                                        'plan_master_id'      => $plan_packaging->id,
                                                        'product_caterogy_id' => $plan_packaging->product_caterogy_id,
                                                        'stage_code'          => $stageItem['stage_code'],
                                                        'order_by'            => $stageItem['order_by'],
                                                        'code'                => $stageItem['code'],
                                                        'predecessor_code'    => $prevCode,
                                                        'nextcessor_code'     => $nextCode,
                                                        'tank'                => $tank->tank ?? 0,
                                                        'keep_dry'            => $tank->keep_dry ?? 0,
                                                        'deparment_code'      => session('user')['production_code'],
                                                        'created_date'        => now(),
                                                        'Theoretical_yields' => $stageItem['stage_code'] <= 4 ? $plan_packaging->batch_size : $plan_packaging->batch_qty,
                                                        'Theoretical_yields_qty'        => $plan->batch_qty
                                                ];
                                        }
                                }
                        }
                }

                DB::table('stage_plan')->insert($dataToInsert);

                DB::table('plan_list')->where('id', $request->plan_list_id)->update([
                        'send' => 1,
                        'send_by' => session('user')['fullName'],
                        'send_date' => now(),
                ]);

                // --- GỬI THÔNG BÁO TỰ ĐỘNG ---
                $plan = DB::table('plan_list')->where('id', $request->plan_list_id)->first();
                $senderName = session('user')['fullName'];
                $productionName = session('user')['production_name'];
                $sendDate = date('d/m/Y H:i');

                $message = "{$senderName} đã Gửi {$plan->name} ngày {$sendDate} PX {$productionName}";
                $targetUrl = route('pages.plan.production.open', [
                        '_token' => csrf_token(),
                        'plan_list_id' => $request->plan_list_id,
                        'month' => $plan->month,
                        'send' => $plan->send,
                        'name' => $plan->name
                ]);

                \App\Http\Controllers\General\NotificationController::sendNotification(
                        $message,
                        'Gửi Kế Hoạch',
                        $request->plan_list_id,
                        'all',
                        [],
                        $targetUrl
                );
                // -----------------------------

                return redirect()->route('pages.plan.production.list');
        }

        public function updateInput(Request $request)
        {
                $now = now();
                $user = session('user')['fullName'];
                $idOrPlanListId = 'id';

                if ($request->name == "selected") {
                        $updateData = ['selected' => !$request->updateValue];
                } else if ($request->name == "selected_all" && $request->id > 0) {
                        $idOrPlanListId = 'plan_list_id';
                        $updateData = ['selected' => $request->updateValue == 1 ? 1 : 0];
                } else {
                        $updateData = [$request->name => $request->updateValue];
                }


                switch ($request->name) {
                        case 'pro_feedback':
                                $updateData['pro_feedback_by']   = $user;
                                $updateData['pro_feedback_date'] = $now;
                                break;

                        case 'qc_feedback':
                                $updateData['qc_feedback_by']   = $user;
                                $updateData['qc_feedback_date'] = $now;
                                break;
                        case 'actual_CoA_date':
                                $updateData['qc_feedback_by']   = $user;
                                $updateData['qc_feedback_date'] = $now;
                                break;

                        case 'en_feedback':
                                $updateData['en_feedback_by']   = $user;
                                $updateData['en_feedback_date'] = $now;
                                break;

                        case 'has_punch_die_mold':
                                $updateData['en_feedback_by']   = $user;
                                $updateData['en_feedback_date'] = $now;
                                break;

                        case 'qa_feedback':
                                $updateData['qa_feedback_by']   = $user;
                                $updateData['qa_feedback_date'] = $now;
                                break;
                        case 'has_BMR':
                                $updateData['qa_feedback_by']   = $user;
                                $updateData['qa_feedback_date'] = $now;
                                break;
                        case 'actual_record':
                                $updateData['qa_feedback_by']   = $user;
                                $updateData['qa_feedback_date'] = $now;
                                break;

                        case 'actual_KCS':
                                $updateData['kcs_record_by']   = $user;
                                $updateData['kcs_record_date'] = $now;
                                break;

                        default:
                                // các field khác như has_BMR, actual_record… thì không cần _by và _date
                                break;
                }


                if ($request->name  == "selected_all" && $request->id < 0) {
                        DB::table('plan_master')
                                ->where('weighed', 0)
                                ->update(['selected' => 1]);
                } else {
                        DB::table('plan_master')
                                ->where($idOrPlanListId, $request->id)
                                ->update($updateData);
                }


                return response()->json(['success' => true, 'updateValue' => $request->updateValue]);
        }

        public function first_batch(Request $request)
        {
                ob_clean();
                $datas = DB::table('plan_master')
                        ->select(
                                'plan_master.*',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                'product_name.name',
                                'market.name as market',
                                'specification.name as specification',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'finished_product_category.deparment_code',
                                'source_material.name as source_material_name'
                        )
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
                        ->leftJoin('source_material', 'plan_master.material_source_id', 'source_material.id')
                        ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                        ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id')
                        ->where('plan_master.active', 1)
                        ->where('is_val', 1)
                        ->where('plan_master.active', 1)
                        //->whereRaw("SUBSTRING_INDEX(plan_master.code_val, '_', -1) = '1'") 
                        ->where('finished_product_category.intermediate_code', $request->intermediate_code)
                        ->orderBy('id', 'desc')
                        ->get();


                return response()->json($datas);
        }

        public function get_last_id(Request $request)
        {
                ob_clean();
                $last = DB::table($request->table)->latest('id')->value('id');
                return response()->json([
                        'last_id' => $last ?? 0
                ]);
        }

        public function feedback_list(Request $request)
        {

                $datas = DB::table('plan_list')
                        ->where('active', 1)
                        ->where('send', 1)
                        ->where('deparment_code', session('user')['production_code'])
                        ->where('type', 1)
                        ->orderBy('id', 'desc')
                        ->get();

                session()->put(['title' => 'PHẢN HỒI KẾ HOẠCH SẢN XUẤT THÁNG']);

                return view('pages.plan.production.feedback_plan_list', [
                        'datas' => $datas
                ]);
        }

        public function open_feedback(Request $request)
        {

                $department = DB::table('user_management')->where('userName', session('user')['userName'])->value('deparment');

                $datas = DB::table('plan_master')
                        ->join('plan_list as pl', 'plan_master.plan_list_id', '=', 'pl.id')
                        ->select(
                                'plan_master.*',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                'product_name.name',
                                'market.code as market',
                                'specification.name as specification',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'finished_product_category.deparment_code',
                                'source_material.name as source_material_name',
                                'stage_plan.end as end'
                        )
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
                        ->leftJoin('source_material', 'plan_master.material_source_id', 'source_material.id')
                        ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                        ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id')
                        ->leftJoin('stage_plan', function ($join) use ($request) {
                                $join->on('plan_master.id', '=', 'stage_plan.plan_master_id')
                                        ->where('stage_plan.stage_code', 7)
                                        ->where('stage_plan.active', 1)
                                ;
                        })
                        ->where('plan_master.plan_list_id', $request->plan_list_id)
                        ->where('plan_master.active', 1)
                        ->where('pl.type', 1)
                        //->where('only_parkaging', 0)
                        ->orderBy('expected_date', 'asc')
                        ->orderBy('level', 'asc')
                        ->orderBy('batch', 'asc')
                        ->get();



                // dd ($datas);


                $production_name  =  session('user')['production_name'];
                $production =  session('user')['production_code'];

                $send_date = DB::table('plan_list')->where('id',  $request->plan_list_id)->value('send_date');

                session()->put(['title' => "Phản Hồi $request->name - $production_name"]);

                return view('pages.plan.production.feedback_list', [
                        'datas' => $datas,
                        'plan_list_id' => $request->plan_list_id,
                        'send' => $request->send ?? 1,
                        'department' => $department,
                        'production' => $production,
                        'send_date' => $send_date
                ]);
        }

        public function accept_expected_date(Request $request)
        {

                DB::table('plan_master')->where('id', $request->id)->update([
                        "expected_date" => $request->new_expected_date,
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);

                // Lấy dữ liệu gốc từ plan_master
                $plan = DB::table('plan_master')->where('id', $request->id)->first();

                // Tìm version cao nhất hiện tại trong history
                $lastVersion = DB::table('plan_master_history')
                        ->where('plan_master_id', $request->id)
                        ->max('version');

                $newVersion = $lastVersion ? $lastVersion + 1 : 1;

                DB::table('plan_master_history')->insert([
                        'plan_master_id' => $plan->id,
                        'plan_list_id' => $plan->plan_list_id,
                        'product_caterogy_id' => $plan->product_caterogy_id,
                        'version' => $newVersion,

                        'level' => $plan->level,
                        'batch' => $plan->batch,
                        'expected_date' => $request->new_expected_date,
                        'is_val' => $plan->is_val,
                        'after_weigth_date' => $plan->after_weigth_date,
                        'after_parkaging_date' => $plan->after_parkaging_date,
                        'material_source_id' => $plan->material_source_id,
                        'percent_parkaging' => $plan->percent_parkaging,
                        'only_parkaging' => $plan->only_parkaging,
                        "number_parkaging" => $plan->number_parkaging,
                        'note' => $plan->note,
                        'reason' => "Chấp nhận ngày dự kiến KCS mới: $request->new_expected_date",
                        'deparment_code' => $plan->deparment_code,

                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                return response()->json(['success' => true, 'message' => 'Đã cập nhật thành công!']);
        }

        public function all_feedback(Request $request)
        {

                $dataToUpdate = [];
                if (isset($request->en_feedback)) {
                        $dataToUpdate = [
                                'has_punch_die_mold' => $request->has_punch_die_mold == "on" ? 1 : 0,
                                'en_feedback' => $request->en_feedback,
                        ];
                } else if (isset($request->qa_feedback)) {
                        $dataToUpdate = [
                                'actual_record' => $request->actual_record == "on" ? 1 : 0,
                                'has_BMR' => $request->has_BMR == "on" ? 1 : 0,
                                'en_feedback' => $request->qa_feedback
                        ];
                } else if (isset($request->qc_feedback)) {
                        $dataToUpdate = [
                                'qc_feedback' => $request->qc_feedback
                        ];
                } else if (isset($request->pro_feedback)) {
                        $dataToUpdate = [
                                'pro_feedback' => $request->pro_feedback
                        ];
                }

                DB::table('plan_master')
                        ->where('plan_list_id', $request->plan_list_id)
                        ->update($dataToUpdate);

                return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
        }

        public function order(Request $request)
        {

                DB::table('plan_master')->where('id', $request->id)->update([
                        "batch" => $request->batch,
                        'order_number' =>  $request->order_number,
                        'order_by' => session('user')['fullName'],
                        'order_date' => now(),
                ]);

                // Lấy dữ liệu gốc từ plan_master
                $plan = DB::table('plan_master')->where('id', $request->id)->first();

                // Tìm version cao nhất hiện tại trong history
                $lastVersion = DB::table('plan_master_history')
                        ->where('plan_master_id', $request->id)
                        ->max('version');

                $newVersion = $lastVersion ? $lastVersion + 1 : 1;

                DB::table('plan_master_history')->insert([
                        'plan_master_id' => $plan->id,
                        'plan_list_id' => $plan->plan_list_id,
                        'product_caterogy_id' => $plan->product_caterogy_id,
                        'version' => $newVersion,

                        'level' => $plan->level,
                        'batch' => $plan->batch,
                        'expected_date' => $plan->expected_date,
                        'is_val' => $plan->is_val,
                        'after_weigth_date' => $plan->after_weigth_date,
                        'after_parkaging_date' => $plan->after_parkaging_date,
                        'material_source_id' => $plan->material_source_id,
                        'percent_parkaging' => $plan->percent_parkaging,
                        'only_parkaging' => $plan->only_parkaging,
                        "number_parkaging" => $plan->number_parkaging,
                        'note' => $plan->note,
                        'reason' => "Cập nhật Số lệnh: $request->order_number",
                        'deparment_code' => $plan->deparment_code,

                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                return response()->json(['success' => true, 'message' => 'Đã cập nhật thành công!']);
        }

        public function open_stock(Request  $request)
        {

                try {

                        $maxStageFinished = DB::table('stage_plan')
                                ->select(
                                        'plan_master_id',
                                        DB::raw('MAX(stage_code) as max_stage_code')
                                )
                                ->where('finished', 1)
                                ->where("stage_code", "!=", 8)
                                ->when($request->plan_list_id >= 0, function ($q) use ($request) {
                                        $q->where('plan_list_id', $request->plan_list_id);
                                })
                                ->groupBy('plan_master_id');

                        $sub = DB::table('plan_master_materials as pmm')
                                ->join('plan_master as pm', 'pmm.plan_master_id', '=', 'pm.id')
                                ->leftJoin('finished_product_category as fc', 'pm.product_caterogy_id', '=', 'fc.id')

                                ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                                        $join->on('pm.main_parkaging_id', '=', 'sp_max.plan_master_id');
                                })

                                ->when(
                                        $request->plan_list_id < 0,
                                        fn($q) => $q->where(function ($sub) {
                                                $sub->whereNull('sp_max.max_stage_code')
                                                        ->orWhere('sp_max.max_stage_code', '<', 7);
                                        }),
                                        fn($q) => $q->where('pm.plan_list_id', $request->plan_list_id)
                                )

                                ->where([
                                        ['pm.deparment_code', session('user')['production_code']],
                                        ['pm.cancel', 0],
                                        ['pm.active', 1],
                                        ['pmm.active', 1]
                                ])

                                ->when(
                                        $request->plan_list_id < 0,
                                        fn($q) => $q->where(function ($q) {
                                                $q->where('pmm.material_packaging_type', '!=', 0)
                                                        ->orWhere(function ($sub) {
                                                                $sub->where('pmm.material_packaging_type', 0)
                                                                        ->whereNull('sp_max.max_stage_code');
                                                        });
                                        })
                                )

                                ->when(
                                        $request->has('selected'),
                                        fn($q) => $q->where('pm.selected', 1)
                                )

                                ->when(
                                        $request->has('material_packaging_type') && $request->material_packaging_type == 0,
                                        fn($q) => $q->where('pmm.material_packaging_type', $request->material_packaging_type)
                                                ->where('pm.only_parkaging', 0),
                                )

                                ->when(
                                        $request->has('material_packaging_type') && $request->material_packaging_type == 1,
                                        fn($q) => $q->where('pmm.material_packaging_type', $request->material_packaging_type),
                                )


                                ->selectRaw("
                                        pmm.material_packaging_code,
                                        pmm.material_packaging_type,

                                        CASE
                                        WHEN pmm.material_packaging_type = 0
                                        THEN fc.intermediate_code
                                        ELSE fc.finished_product_code
                                        END AS product_code,

                                        SUM(
                                        CASE
                                                WHEN pmm.material_packaging_type = 1
                                                THEN pmm.qty * pm.percent_parkaging
                                                ELSE pmm.qty
                                        END
                                        ) as total_qty,

                                        COUNT(DISTINCT pmm.plan_master_id) as batch_count
                                ")

                                ->groupByRaw("
                                        pmm.material_packaging_code,
                                        pmm.material_packaging_type,
                                        product_code
                        ");


                        // Tổng hợp lại theo từng material_packaging_code/type để tránh bị nhân bản khi join với pmm/pm
                        $qtyTotal = DB::query()
                                ->fromSub($sub, 'qty_sum')
                                ->selectRaw("
                                        material_packaging_code,
                                        material_packaging_type,
                                        SUM(total_qty) as total_qty,
                                        SUM(batch_count) as NumberOfBatch,
                                        GROUP_CONCAT(
                                                DISTINCT CONCAT(
                                                        product_code,
                                                        ' : ',
                                                        batch_count,' lô x ',
                                                        ROUND(total_qty / NULLIF(batch_count, 0), 3),
                                                        ' = ',
                                                        ROUND(total_qty, 3)
                                                )
                                                SEPARATOR '<br>'
                                        ) as qty_list
                                ")
                                ->groupBy('material_packaging_code', 'material_packaging_type');

                        $plan_master_materials = DB::table('plan_master_materials as pmm')
                                ->join('plan_master as pm', 'pmm.plan_master_id', '=', 'pm.id')
                                ->leftJoinSub($qtyTotal, 'qty_total', function ($join) {
                                        $join->on('pmm.material_packaging_code', '=', 'qty_total.material_packaging_code')
                                                ->on('pmm.material_packaging_type', '=', 'qty_total.material_packaging_type');
                                })
                                ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                                        $join->on('pm.main_parkaging_id', '=', 'sp_max.plan_master_id');
                                })

                                ->when(
                                        $request->plan_list_id < 0,
                                        fn($q) => $q->where(function ($sub) {
                                                $sub->whereNull('sp_max.max_stage_code')
                                                        ->orWhere('sp_max.max_stage_code', '<', 7)
                                                ;
                                        }),
                                        fn($q) => $q->where('pm.plan_list_id', $request->plan_list_id)
                                )
                                ->when(
                                        $request->plan_list_id < 0,
                                        fn($q) => $q->where(function ($q) {
                                                $q->where('pmm.material_packaging_type', '!=', 0)
                                                        ->orWhere(function ($sub) {
                                                                $sub->where('pmm.material_packaging_type', 0)
                                                                        ->whereNull('sp_max.max_stage_code');
                                                        });
                                        })
                                )

                                ->where([
                                        ['pm.deparment_code', session('user')['production_code']],
                                        ['pm.cancel', 0],
                                        ['pm.active', 1],
                                        ['pmm.active', 1]
                                ])

                                ->when(
                                        $request->has('selected'),
                                        fn($q) => $q->where('pm.selected', 1)
                                )

                                ->when(
                                        $request->has('material_packaging_type') && $request->material_packaging_type == 0,
                                        fn($q) => $q->where('pmm.material_packaging_type', $request->material_packaging_type)
                                                ->where('pm.only_parkaging', 0),
                                )

                                ->when(
                                        $request->has('material_packaging_type') && $request->material_packaging_type == 1,
                                        fn($q) => $q->where('pmm.material_packaging_type', $request->material_packaging_type),
                                )

                                ->selectRaw("
                                        pmm.MaterialName,
                                        pmm.material_packaging_code,
                                        pmm.material_packaging_type,
                                        pmm.unit_bom,

                                        MAX(qty_total.total_qty) as total_qty,

                                     
                                        MAX(qty_total.NumberOfBatch) as NumberOfBatch,
                                        SUM(pmm.qty) as TotalMatQty,

                                        GROUP_CONCAT(DISTINCT pm.id SEPARATOR '_') as plan_master_ids,

                                        MAX(qty_total.qty_list) as qty_list
                                ")

                                ->groupBy(
                                        'pmm.MaterialName',
                                        'pmm.material_packaging_code',
                                        'pmm.material_packaging_type',
                                        'pmm.unit_bom'
                                )
                                ->orderBy('pmm.material_packaging_code')
                                ->get();



                        $material_packaging_code =  $plan_master_materials->pluck('material_packaging_code');

                        $source = $request->input('stock_source', 'live');
                        $db_plan_list_id = $request->plan_list_id < 0 ? 0 : $request->plan_list_id;

                        // Lấy danh sách các bản sao lưu có sẵn
                        $backupList = DB::table('inventory_backups')
                                //->where('plan_list_id', $db_plan_list_id)
                                ->select('backup_name', DB::raw('MIN(created_at) as created_at'))
                                ->groupBy('backup_name')
                                ->orderBy('created_at', 'desc')
                                ->get();

                        $selectedBackupName = $request->input('backup_name');
                        if (!$selectedBackupName && $backupList->isNotEmpty()) {
                                $selectedBackupName = $backupList->first()->backup_name;
                        }

                        $lastBackup = $backupList->first(); // Để tương thích với UI cũ nếu cần

                        if ($source == 'backup' && $selectedBackupName) {
                                $StockOverview = DB::table('inventory_backups as s')
                                        //->where('s.plan_list_id', $db_plan_list_id)
                                        ->where('s.backup_name', $selectedBackupName)
                                        ->whereIn('s.mat_id', $material_packaging_code)
                                        ->select(
                                                's.grn_no as GRNNO',
                                                's.mfg_batch_no as Mfgbatchno',
                                                's.ar_no as ARNO',
                                                's.expiry_date as Expirydate',
                                                's.retest_date as Retestdate',
                                                's.mat_uom as MatUOM',
                                                's.mat_id as MatID',
                                                's.grn_sts as GRNSts',
                                                's.mfg as Mfg',
                                                's.qc_sts as QCSTS',
                                                's.receipt_quantity as ReceiptQuantity',
                                                's.total_qty as Total_Qty',
                                                's.warehouse_list',
                                                's.coa_list'
                                        )
                                        ->get();
                        } else {
                                $StockOverview = DB::connection('mms')
                                        ->table('yf_RMPMStockOverview_pms as s')
                                        ->whereIn('s.MatID', $material_packaging_code)
                                        ->select(
                                                's.GRNNO',
                                                's.Mfgbatchno',
                                                's.ARNO',
                                                's.Expirydate',
                                                's.Retestdate',
                                                's.MatUOM',
                                                's.MatID',
                                                's.GRNSts',
                                                's.Mfg',
                                                's.QCSTS',

                                                DB::raw('SUM(s.ReceiptQuantity) as ReceiptQuantity'),
                                                DB::raw('SUM([Total Qty]) as Total_Qty'),

                                                // Gộp warehouse_id
                                                DB::raw("
                                        STUFF((
                                                SELECT DISTINCT ', ' + 
                                                LEFT(s2.warehouse_id, CHARINDEX('.', s2.warehouse_id + '.') - 1)
                                                FROM yf_RMPMStockOverview_pms s2
                                                WHERE s2.GRNNO = s.GRNNO
                                                FOR XML PATH('')
                                        ), 1, 2, '') as warehouse_list
                                        "),

                                                // Gộp IntBatchNo
                                                DB::raw("
                                        STUFF((
                                                SELECT DISTINCT ', ' + s3.IntBatchNo
                                                FROM yf_RMPMStockOverview_pms s3
                                                WHERE s3.GRNNO = s.GRNNO
                                                FOR XML PATH('')
                                        ), 1, 2, '') as coa_list
                                        "),
                                        )
                                        ->groupBy(
                                                's.GRNNO',
                                                's.Mfgbatchno',
                                                's.ARNO',
                                                's.Expirydate',
                                                's.Retestdate',
                                                's.MatUOM',
                                                's.MatID',
                                                's.GRNSts',
                                                's.Mfg',
                                                's.QCSTS',
                                        )
                                        ->get();
                        }


                        // dd ($StockOverview);




                        $stockByMat = collect($StockOverview)->groupBy('MatID');

                        $plan_master_materials = collect($plan_master_materials)
                                ->map(function ($item) use ($stockByMat) {

                                        $stocks = $stockByMat[$item->material_packaging_code] ?? collect([]);

                                        // 👉 Chỉ tính tổng, không thêm dòng
                                        $item->totalReceipt = $stocks->sum('ReceiptQuantity');
                                        $item->totalQty     = $stocks->sum('Total_Qty');

                                        $item->stock = $stocks;

                                        return $item;
                                })
                                ->sortBy(fn($i) => mb_strtolower($i->MaterialName))
                                ->values();



                        $production  =  session('user')['production_name'];

                        // dd ( $plan_master_materials);

                        session()->put(['title' => "BẢNG TÍNH DỰ TRÙ NGUYÊN LIỆU CHO $request->name - $production"]);

                        if ($request->title) {
                                session()->put(['title' => "$request->title - $production"]);
                        }

                        //dd ($plan_master_materials);
                        return view('pages.plan.production.stock_list', [
                                'datas' => $plan_master_materials,
                                'plan_list_id' => $request->plan_list_id,
                                'month' => $request->month,
                                'production' => $request->production,
                                'send' => $request->send ?? 1,
                                'current_url' => $request->current_url ?? null,
                                'lastBackup' => $lastBackup,
                                'stock_source' => $source,
                                'backupList' => $backupList,
                                'selectedBackupName' => $selectedBackupName,
                        ]);
                } catch (\Throwable $e) {

                        Log::error('OPEN_STOCK_ERROR', [
                                'message' => $e->getMessage(),
                                'line' => $e->getLine(),
                                'file' => $e->getFile(),
                                'trace' => $e->getTraceAsString()
                        ]);

                        return view('pages.plan.production.stock_list', [
                                'datas' => collect([]),
                                'js_error' => [
                                        'message' => $e->getMessage(),
                                        'line' => $e->getLine(),
                                        'file' => $e->getFile()
                                ]
                        ]);
                }
        }


        public function backup_stock(Request $request)
        {
                $plan_list_id = $request->plan_list_id;

                if (!$plan_list_id) {
                        return response()->json(['success' => false, 'message' => 'Thiếu ID kế hoạch.']);
                }

                try {
                        // 2. Lấy TOÀN BỘ dữ liệu từ MMS (không phụ thuộc plan_master như yêu cầu)
                        $stockOverview = DB::connection('mms')
                                ->table('yf_RMPMStockOverview_pms as s')
                                ->select(
                                        's.GRNNO',
                                        's.Mfgbatchno',
                                        's.ARNO',
                                        's.Expirydate',
                                        's.Retestdate',
                                        's.MatUOM',
                                        's.MatID',
                                        's.GRNSts',
                                        's.Mfg',
                                        's.QCSTS',
                                        DB::raw('SUM(s.ReceiptQuantity) as ReceiptQuantity'),
                                        DB::raw('SUM([Total Qty]) as Total_Qty'),
                                        DB::raw("
                                        STUFF((
                                                SELECT DISTINCT ', ' + LEFT(s2.warehouse_id, CHARINDEX('.', s2.warehouse_id + '.') - 1)
                                                FROM yf_RMPMStockOverview_pms s2
                                                WHERE s2.GRNNO = s.GRNNO
                                                FOR XML PATH('')
                                        ), 1, 2, '') as warehouse_list
                                        "),
                                        DB::raw("
                                        STUFF((
                                                SELECT DISTINCT ', ' + s3.IntBatchNo
                                                FROM yf_RMPMStockOverview_pms s3
                                                WHERE s3.GRNNO = s.GRNNO
                                                FOR XML PATH('')
                                        ), 1, 2, '') as coa_list
                                        "),
                                )
                                ->groupBy(
                                        's.GRNNO',
                                        's.Mfgbatchno',
                                        's.ARNO',
                                        's.Expirydate',
                                        's.Retestdate',
                                        's.MatUOM',
                                        's.MatID',
                                        's.GRNSts',
                                        's.Mfg',
                                        's.QCSTS'
                                )
                                ->get();

                        $db_plan_list_id = $plan_list_id < 0 ? 0 : $plan_list_id;
                        $backup_name = (session('user')['fullName'] ?? 'User') . '_' . now()->format('d/m/Y H:i:s');

                        // 3. Lưu vào database
                        DB::beginTransaction();
                        try {
                                $insertData = [];
                                foreach ($stockOverview as $stock) {
                                        $insertData[] = [
                                                'plan_list_id'    => $db_plan_list_id,
                                                'backup_name'     => $backup_name,
                                                'mat_id'          => $stock->MatID,
                                                'grn_no'          => $stock->GRNNO,
                                                'mfg_batch_no'    => $stock->Mfgbatchno,
                                                'ar_no'           => $stock->ARNO,
                                                'expiry_date'     => $stock->Expirydate,
                                                'retest_date'     => $stock->Retestdate,
                                                'mat_uom'         => $stock->MatUOM,
                                                'grn_sts'         => $stock->GRNSts,
                                                'mfg'             => $stock->Mfg,
                                                'qc_sts'          => $stock->QCSTS,
                                                'receipt_quantity' => $stock->ReceiptQuantity,
                                                'total_qty'       => $stock->Total_Qty,
                                                'warehouse_list'  => $stock->warehouse_list,
                                                'coa_list'        => $stock->coa_list,
                                                'created_at'      => now(),
                                                'updated_at'      => now(),
                                        ];
                                }

                                if (!empty($insertData)) {
                                        // Chia nhỏ batch nếu quá lớn (vd: 500 records mỗi lần)
                                        foreach (array_chunk($insertData, 500) as $chunk) {
                                                DB::table('inventory_backups')->insert($chunk);
                                        }
                                }

                                // 4. Giới hạn 30 bản sao lưu cho mỗi plan_list_id
                                $allBackups = DB::table('inventory_backups')
                                        ->where('plan_list_id', $db_plan_list_id)
                                        ->select('backup_name', DB::raw('MIN(created_at) as created_at'))
                                        ->groupBy('backup_name')
                                        ->orderBy('created_at', 'desc')
                                        ->get();

                                if ($allBackups->count() > 30) {
                                        $toDelete = $allBackups->slice(30);
                                        foreach ($toDelete as $old) {
                                                DB::table('inventory_backups')
                                                        ->where('plan_list_id', $db_plan_list_id)
                                                        ->where('backup_name', $old->backup_name)
                                                        ->delete();
                                        }
                                }

                                DB::commit();

                                return response()->json(['success' => true, 'message' => 'Sao lưu dữ liệu tồn kho thành công! (' . $backup_name . ')']);
                        } catch (\Exception $ex) {
                                DB::rollBack();
                                return response()->json(['success' => false, 'message' => 'Lỗi lưu database: ' . $ex->getMessage()]);
                        }
                } catch (\Exception $e) {
                        return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
                }
        }

        public function open_bacth_detail(Request  $request)
        {


                $maxStageFinished = DB::table('stage_plan')
                        ->whereIn('stage_plan.plan_master_id', $request->plan_master_ids)
                        ->where('finished', 1)
                        ->where('stage_code', "!=", 8)
                        ->select(
                                'plan_master_id',
                                DB::raw('MAX(stage_code) as max_stage_code')
                        )
                        ->groupBy('plan_master_id');


                $datas = DB::table('plan_master')
                        ->join('plan_list as pl', 'plan_master.plan_list_id', '=', 'pl.id')
                        ->select(
                                'plan_master.*',


                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                DB::raw('fp_name.name AS finished_product_name'),
                                DB::raw('im_name.name AS intermediate_product_name'),
                                'market.name as market',
                                'specification.name as specification',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'finished_product_category.deparment_code',
                                'source_material.name as source_material_name',

                                DB::raw("
                                CASE
                                        WHEN plan_master.cancel = 1 THEN 'Hủy'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 1 THEN 'Đã Cân'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 3 THEN 'Đã Pha chế'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 4 THEN 'Đã THT'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 5 THEN 'Đã định hình'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 6 THEN 'Đã Bao phim'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 7 THEN 'Hoàn Tất ĐG'
                                        ELSE 'Chưa làm'
                                        END AS status
                                ")
                        )
                        ->whereIn('plan_master.id', $request->plan_master_ids)
                        ->where('plan_master.active', 1)
                        ->where('pl.type', 1)

                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
                        ->leftJoin('source_material', 'plan_master.material_source_id', '=', 'source_material.id')
                        ->leftJoin('product_name as fp_name', 'finished_product_category.product_name_id', '=', 'fp_name.id')
                        ->leftJoin('product_name as im_name', 'intermediate_category.product_name_id', '=', 'im_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                        ->leftJoin('specification', 'finished_product_category.specification_id', '=', 'specification.id')
                        ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                                $join->on('plan_master.id', '=', 'sp_max.plan_master_id');
                        })
                        ->leftJoin('stage_plan', function ($join) {
                                $join->on('plan_master.id', '=', 'stage_plan.plan_master_id')
                                        ->on('stage_plan.stage_code', '=', 'sp_max.max_stage_code');
                        })
                        ->orderBy('expected_date', 'asc')
                        ->orderBy('level', 'asc')
                        ->orderBy('batch', 'asc')
                        ->get();


                return response()->json([
                        'datas' => $datas
                ]);
        }

        public function open_feedback_API(Request $request)
        {

                $deparment_code = $request->deparment_code ?? 'PXV1';
                $month = $request->month ?? now()->month;
                $year = $request->year ?? now()->year;

                if ($year > 2035) {
                        $plan_list_id = DB::table('plan_list')->where('deparment_code', $deparment_code)->pluck('id');
                } else {
                        $plan_list_id = DB::table('plan_list')->where('deparment_code', $deparment_code)->where('year', $year)->where('month', $month)->pluck('id');
                }

                $maxStageFinished = DB::table('stage_plan')
                        ->whereIn('stage_plan.plan_list_id', $plan_list_id)
                        ->where('finished', 1)
                        ->where('stage_code', '!=', 8)
                        ->select(
                                'plan_master_id',
                                DB::raw('MAX(stage_code) as max_stage_code')
                        )
                        ->groupBy('plan_master_id');

                $maxPossibleStage = DB::table('stage_plan')
                        ->whereIn('stage_plan.plan_list_id', $plan_list_id)
                        ->where('active', 1)
                        ->where('stage_code', '!=', 8)
                        ->select(
                                'plan_master_id',
                                DB::raw('MAX(stage_code) as max_possible_stage_code')
                        )
                        ->groupBy('plan_master_id');

                $query = DB::table('plan_master')
                        ->join('plan_list as pl', 'plan_master.plan_list_id', '=', 'pl.id')
                        ->select(

                                "plan_master.id",
                                "plan_master.plan_list_id",
                                "plan_master.product_caterogy_id",
                                "plan_master.level",
                                "plan_master.batch",
                                "plan_master.actual_batch",
                                "plan_master.order_number",
                                "plan_master.expected_date",
                                "plan_master.responsed_date",
                                "plan_master.actual_KCS",
                                "plan_master.is_val",
                                "plan_master.code_val",
                                "plan_master.after_weigth_date",
                                "plan_master.parkaging_before_date",
                                "plan_master.after_parkaging_date",
                                "plan_master.expired_packing_date",
                                "plan_master.preperation_before_date",
                                "plan_master.blending_before_date",
                                "plan_master.coating_before_date",
                                "plan_master.allow_weight_before_date",
                                "plan_master.expired_material_date",
                                "plan_master.material_source_id",
                                "plan_master.only_parkaging",
                                "plan_master.percent_parkaging",
                                "plan_master.main_parkaging_id",
                                "plan_master.number_parkaging",
                                "plan_master.note",
                                "plan_master.pro_feedback",
                                "plan_master.qc_feedback",


                                DB::raw("IF(plan_master.qa_feedback IS NOT NULL, plan_master.qa_feedback, 'NA') AS qa_feedback_text"),
                                DB::raw("IF(plan_master.has_BMR = 0, 'Chưa sẵn sàng', 'Đã sẵn sàng') AS has_BMR_text"),

                                DB::raw("IF(plan_master.en_feedback IS NOT NULL, plan_master.en_feedback, 'NA') AS en_feedback"),
                                DB::raw("IF(plan_master.has_punch_die_mold = 0, 'Chưa sẵn sàng', 'Đã sẵn sàng') AS has_punch_die_mold"),


                                "plan_master.actual_CoA_date",
                                "plan_master.actual_record_date",

                                "plan_master.qa_feedback_by",
                                "plan_master.qa_feedback_date",
                                "plan_master.qc_feedback_by",
                                "plan_master.qc_feedback_date",
                                "plan_master.pro_feedback_by",
                                "plan_master.pro_feedback_date",
                                "plan_master.en_feedback_by",
                                "plan_master.en_feedback_date",
                                "plan_master.kcs_record_by",
                                "plan_master.kcs_record_date",
                                "plan_master.accept_expectedDate_by",
                                "plan_master.accept_expectedDate_date",
                                "plan_master.deparment_code",
                                "plan_master.active",
                                "plan_master.cancel",

                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                'product_name.name',
                                'market.code as market',
                                'specification.name as specification',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'finished_product_category.deparment_code',
                                'source_material.name as source_material_name',
                                'stage_plan.end as end',

                                DB::raw("
                                CASE
                                        WHEN plan_master.cancel = 1 THEN 'Hủy'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 1 THEN 'Đã Cân'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 3 THEN 'Đã Pha chế'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 4 THEN 'Đã THT'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 5 THEN 'Đã định hình'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 6 THEN 'Đã Bao phim'
                                        WHEN stage_plan.finished = 1 AND sp_max.max_stage_code = 7 THEN 'Hoàn Tất ĐG'
                                        ELSE 'Chưa làm'
                                        END AS status
                        ")

                        )
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
                        ->leftJoin('source_material', 'plan_master.material_source_id', 'source_material.id')
                        ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                        ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id')
                        ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                                $join->on('plan_master.id', '=', 'sp_max.plan_master_id');
                        })
                        ->leftJoinSub($maxPossibleStage, 'sp_possible', function ($join) {
                                $join->on('plan_master.id', '=', 'sp_possible.plan_master_id');
                        })
                        ->leftJoin('stage_plan', function ($join) {
                                $join->on('plan_master.id', '=', 'stage_plan.plan_master_id')
                                        ->on('stage_plan.stage_code', '=', 'sp_max.max_stage_code');
                        })
                        ->whereIn('plan_master.plan_list_id', $plan_list_id)
                        ->where('plan_master.active', 1)
                        ->where('pl.type', 1);

                if ($year > 2035) {
                        $query->where('plan_master.cancel', 0)
                                ->where(function ($q) {
                                        $q->whereNull('sp_max.max_stage_code')
                                                ->orWhereRaw('sp_max.max_stage_code < sp_possible.max_possible_stage_code');
                                });
                }

                $datas = $query->orderBy('id', 'asc')->get();

                return response()->json([
                        'datas' => $datas
                ]);
        }

        public function recipe_show_update(Request $request)
        {

                $datas = DB::table('plan_master_materials as pmm')
                        ->where('pmm.plan_master_id', $request->plan_master_id)
                        ->where('pmm.material_packaging_type', $request->material_packaging_type)
                        ->get();
                return response()->json($datas);
        }

        public function update_plan_master_material(Request $request)
        {

                $type_update =   'intermediate_code'; //'finished_product_code';
                $material_packaging_type = 0;
                $insertData = [];

                // 1️⃣ Lấy plan
                $plans = DB::table('plan_master as pm')
                        ->select(
                                'pm.id as plan_master_id',
                                "fpc.$type_update"
                        )
                        ->leftJoin(
                                'finished_product_category as fpc',
                                'pm.product_caterogy_id',
                                '=',
                                'fpc.id'
                        )
                        ->where('pm.active', 1)
                        ->where('pm.plan_list_id', '>', 23)
                        //->where('pm.weighed', 0)
                        ->where('pm.cancel', 0)
                        ->get();
                //dd ($plans);
                // 2️⃣ Lấy danh sách PrdID
                $prdIds = $plans->pluck($type_update)
                        ->filter()
                        ->unique()
                        ->values();

                if ($prdIds->isEmpty()) {
                        return response()->json([]);
                }

                // 3️⃣ Lấy BOM từ SQL Server
                $boms = DB::connection('mms')
                        ->table('yfBOM_BOMItemHP')
                        ->whereIn('PrdID', $prdIds)
                        ->get();

                if ($boms->isEmpty()) {
                        return response()->json([]);
                }

                // 4️⃣ Tính Revno max theo từng PrdID (CHỈ TÍNH 1 LẦN)
                $maxRevByPrd = $boms
                        ->groupBy('PrdID')
                        ->map(fn($items) => $items->max('Revno'));

                // 5️⃣ Lọc BOM chỉ giữ Revno max
                $boms = $boms->filter(function ($item) use ($maxRevByPrd) {
                        return $item->Revno == $maxRevByPrd[$item->PrdID];
                });

                // 6️⃣ Group lại theo PrdID cho nhanh
                $bomsGrouped = $boms->groupBy('PrdID');

                // 7️⃣ Map vào từng plan
                foreach ($plans as $plan) {

                        $prdId = $plan->$type_update;

                        if (!isset($bomsGrouped[$prdId])) {
                                continue;
                        }

                        foreach ($bomsGrouped[$prdId] as $item) {

                                $insertData[] = [
                                        'plan_master_id'          => $plan->plan_master_id,
                                        'material_packaging_code' => (string) $item->MatID,
                                        'material_packaging_type' => $material_packaging_type,
                                        'Revno'                   => $item->Revno,
                                        'qty'                     => (float) $item->MatQty,
                                        'unit_bom'                => $item->uom,
                                        'MaterialName'            => $item->MaterialName,
                                        'created_at'              => now(),
                                        'created_by'              => "Auto_generate",
                                        'active'                  => 1,
                                ];
                        }
                }

                // 8️⃣ Upsert
                if (!empty($insertData)) {

                        foreach (array_chunk($insertData, 1000) as $chunk) {

                                DB::table('plan_master_materials')->upsert(
                                        $chunk,
                                        ['plan_master_id', 'material_packaging_code', 'material_packaging_type'],
                                        ['qty', 'unit_bom', 'active', 'Revno']
                                );
                        }
                }

                return response()->json([]);
        }

        public function getWaitingPlans(Request $request)
        {
                $production = session('user')['production_code'];
                $month = $request->month;

                $plan_waiting = DB::table('stage_plan as sp')
                        ->whereNull('sp.start')
                        ->where('sp.active', 1)
                        ->where('sp.finished', 0)
                        ->where('sp.stage_code', '!=', 8)
                        ->where('sp.deparment_code', $production)
                        ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                        ->leftJoin('plan_list', 'sp.plan_list_id', '=', 'plan_list.id')
                        ->leftJoin('source_material', 'plan_master.material_source_id', '=', 'source_material.id')
                        ->leftJoin('finished_product_category', function ($join) {
                                $join->on('sp.product_caterogy_id', '=', 'finished_product_category.id')
                                        ->where('sp.stage_code', '<=', 7);
                        })
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
                        ->leftJoin('product_name', function ($join) {
                                $join->on('intermediate_category.product_name_id', '=', 'product_name.id')
                                        ->where('sp.stage_code', '<=', 7);
                        })
                        ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                        ->whereRaw("DATE_FORMAT(plan_master.expected_date, '%m-%Y') = ?", [$month])
                        ->select(
                                'sp.id',
                                'sp.code',
                                'sp.stage_code',
                                DB::raw("
                        CASE
                                WHEN sp.stage_code >= 8 THEN sp.title
                                ELSE CONCAT(
                                product_name.name,
                                '-',
                                COALESCE(plan_master.actual_batch, plan_master.batch)
                                )
                        END AS title
                "),
                                'product_name.name as name',
                                DB::raw('COALESCE(plan_master.actual_batch, plan_master.batch) as batch'),
                                'plan_master.expected_date',
                                'plan_master.responsed_date',
                                'plan_master.is_val',
                                'plan_master.level',
                                DB::raw("CONCAT(LPAD(plan_list.month, 2, '0'), '-', plan_list.year) as month")
                        )
                        ->get();

                return response()->json($plan_waiting);
        }

        public function getBatchesByStatus(Request $request)
        {
                $production_code = session('user')['production_code'];
                $month = $request->month;
                $status_filter = $request->status;
                $filter_type = $request->filter_type ?? 'expected';

                $maxStageFinished = DB::table('stage_plan')
                        ->where('finished', 1)
                        ->where('active', 1)
                        ->where('stage_code', '!=', 8)
                        ->where('deparment_code', $production_code)
                        ->select(
                                'plan_master_id',
                                DB::raw('MAX(stage_code) as max_stage_code')
                        )
                        ->groupBy('plan_master_id');

                $maxPossibleStage = DB::table('stage_plan')
                        ->where('active', 1)
                        ->where('stage_code', '!=', 8)
                        ->where('deparment_code', $production_code)
                        ->select(
                                'plan_master_id',
                                DB::raw('MAX(stage_code) as max_possible_stage_code')
                        )
                        ->groupBy('plan_master_id');

                $yieldSub = DB::table('yields')
                        ->select('stage_plan_id', DB::raw('SUM(yield) as total_yield'))
                        ->groupBy('stage_plan_id');

                $target_stage_code = null;
                if ($filter_type === 'actual') {
                        $stage_code_map = [
                                'Đã Cân' => 1,
                                'Đã Pha chế' => 3,
                                'Đã THT' => 4,
                                'Đã định hình' => 5,
                                'Đã Bao phim' => 6,
                                'Hoàn Tất ĐG' => 7,
                        ];
                        $target_stage_code = $stage_code_map[$status_filter] ?? null;
                }

                $batches = DB::table('plan_master as pm')
                        ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
                        ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                                $join->on('pm.id', '=', 'sp_max.plan_master_id');
                        })
                        ->leftJoinSub($maxPossibleStage, 'sp_possible', function ($join) {
                                $join->on('pm.id', '=', 'sp_possible.plan_master_id');
                        })
                        ->leftJoin('stage_plan as sp', function ($join) {
                                $join->on('pm.id', '=', 'sp.plan_master_id')
                                        ->on('sp.stage_code', '=', 'sp_max.max_stage_code');
                        })
                        ->when($filter_type === 'actual' && $target_stage_code, function ($q) use ($target_stage_code) {
                                $q->join('stage_plan as sp_target', function ($join) use ($target_stage_code) {
                                        $join->on('pm.id', '=', 'sp_target.plan_master_id')
                                                ->where('sp_target.stage_code', '=', $target_stage_code)
                                                ->where('sp_target.finished', '=', 1)
                                                ->where('sp_target.active', '=', 1);
                                });
                        })
                        ->leftJoinSub($yieldSub, 'ys', function ($join) {
                                $join->on('sp.id', '=', 'ys.stage_plan_id');
                        })
                        ->leftJoin('finished_product_category as fc', 'pm.product_caterogy_id', '=', 'fc.id')
                        ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
                        ->where('pm.active', 1)
                        ->where('pl.type', 1)
                        ->where('pm.only_parkaging', 0)
                        ->where('pm.plan_list_id', '!=', 0)
                        ->where('pm.plan_list_id', '>', 23)
                        ->where('pm.deparment_code', $production_code)
                        ->when($request->plan_list_id, function ($q) use ($request) {
                                if ($request->plan_list_id == -1) {
                                        return $q->where('pm.cancel', 0)->where(function ($sub) {
                                                $sub->whereNull('sp_max.max_stage_code')
                                                        ->orWhereRaw('sp_max.max_stage_code < sp_possible.max_possible_stage_code');
                                        });
                                }
                                return $q->where('pm.plan_list_id', $request->plan_list_id);
                        })
                        ->when($month, function ($q) use ($month, $filter_type, $target_stage_code) {
                                if ($filter_type === 'actual') {
                                        if ($target_stage_code) {
                                                return $q->whereRaw("DATE_FORMAT(sp_target.actual_start, '%m-%Y') = ?", [$month]);
                                        } else {
                                                return $q->whereRaw("1 = 0");
                                        }
                                } else {
                                        return $q->whereRaw("DATE_FORMAT(pm.expected_date, '%m-%Y') = ?", [$month]);
                                }
                        })
                        ->select(
                                'pm.id',
                                'fc.finished_product_code as ma_san_pham',
                                'pn.name as ten_san_pham',
                                DB::raw("COALESCE(pm.actual_batch, pm.batch) AS so_lo"),
                                DB::raw("COALESCE(ys.total_yield, 0) as san_luong"),
                                'sp_max.max_stage_code',
                                DB::raw("CASE
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 1 THEN 'Đã Cân'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 3 THEN 'Đã Pha chế'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 4 THEN 'Đã THT'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 5 THEN 'Đã định hình'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 6 THEN 'Đã Bao phim'
                                WHEN sp.finished = 1 AND sp_max.max_stage_code = 7 THEN 'Hoàn Tất ĐG'
                                ELSE 'Chưa làm'
                                END AS current_status
                        ")
                        )
                        ->when($filter_type === 'actual' && $target_stage_code, function ($q) {
                                $q->addSelect(
                                        'sp_target.deparment_code as phong_san_xuat',
                                        DB::raw("TIMESTAMPDIFF(MINUTE, sp_target.actual_start, sp_target.actual_end) as production_minutes"),
                                        DB::raw("TIMESTAMPDIFF(MINUTE, sp_target.actual_start_clearning, sp_target.actual_end_clearning) as cleaning_minutes")
                                );
                        })
                        ->get();

                if ($filter_type !== 'actual') {
                        $batches = $batches->filter(function ($b) use ($status_filter) {
                                return $b->current_status == $status_filter;
                        })->values();
                }

                $plan_master_ids = $batches->pluck('id')->toArray();
                $all_stages = DB::table('stage_plan')
                        ->whereIn('plan_master_id', $plan_master_ids)
                        ->where('active', 1)
                        ->get()
                        ->groupBy('plan_master_id');

                $formatMins = function ($mins) {
                        if (!$mins) {
                                return '0 giờ';
                        }
                        $total_hours = round($mins / 60);
                        if ($total_hours == 0) {
                                return '0 giờ';
                        }
                        $d = floor($total_hours / 24);
                        $h = $total_hours % 24;
                        if ($d > 0 && $h > 0) {
                                return "{$d} ngày {$h} giờ";
                        }
                        if ($d > 0) {
                                return "{$d} ngày";
                        }
                        return "{$h} giờ";
                };

                foreach ($batches as $batch) {
                        $batch->cong_doan_tiep_theo = 'N/A';
                        $batch->thoi_gian_bat_dau = 'N/A';
                        if ($filter_type === 'actual') {
                                $batch->thoi_gian_san_xuat_thuc_te = $formatMins($batch->production_minutes ?? 0);
                                $batch->thoi_gian_ve_sinh_thuc_te = $formatMins($batch->cleaning_minutes ?? 0);
                        }

                        if (in_array($batch->current_status, ['Hoàn Tất ĐG', 'Hoàn Tất', 'Hủy'])) {
                                continue;
                        }

                        if (isset($all_stages[$batch->id])) {
                                $stages = $all_stages[$batch->id];
                                $next_stage_code = null;

                                if ($batch->max_stage_code) {
                                        $current_stage = $stages->where('stage_code', $batch->max_stage_code)->where('finished', 1)->first();
                                        if ($current_stage && $current_stage->nextcessor_code) {
                                                $parts = explode('_', $current_stage->nextcessor_code);
                                                $next_stage_code = isset($parts[1]) ? (int)$parts[1] : 0;
                                        }
                                } else {
                                        $first_stage = $stages->where('finished', 0)->sortBy('stage_code')->first();
                                        if ($first_stage) {
                                                $next_stage_code = $first_stage->stage_code;
                                        }
                                }

                                if ($next_stage_code) {
                                        $next_stage = $stages->where('stage_code', $next_stage_code)->first();
                                        if ($next_stage) {
                                                $stageNames = [
                                                        1 => "Cân Nguyên Liệu",
                                                        3 => "Pha Chế",
                                                        4 => "Trộn Hoàn Tất",
                                                        5 => "Định Hình",
                                                        6 => "Bao Phim",
                                                        7 => "ĐGSC - ĐGTC",
                                                        8 => "N/A"
                                                ];
                                                $batch->cong_doan_tiep_theo = $stageNames[$next_stage->stage_code] ?? $next_stage->stage_code;
                                                $batch->thoi_gian_bat_dau = $next_stage->start ? \Carbon\Carbon::parse($next_stage->start)->format('d/m/Y H:i') : 'Chưa bắt đầu';
                                        }
                                }
                        }
                }

                return response()->json($batches);
        }

        public function getEquipmentAllocation($id)
        {
                $stageCodeReq = request()->query('stage_code');
                $effectiveStageCode = ($stageCodeReq && $stageCodeReq !== 'all') ? (int)$stageCodeReq : 7;

                $departmentCode = request()->query('department_code', 'PXV1');

                $planMasterQuery = null;

                if ($id == -1) {

                        $maxStageFinished = DB::table('stage_plan')
                                ->where('finished', 1)
                                ->where('active', 1)
                                ->where('stage_code', '!=', 8)
                                ->where('deparment_code', $departmentCode)
                                ->select('plan_master_id', DB::raw('MAX(stage_code) as max_stage_code'))
                                ->groupBy('plan_master_id');

                        $maxPossibleStage = DB::table('stage_plan')
                                ->where('active', 1)
                                ->where('stage_code', '!=', 8)
                                ->where('deparment_code', $departmentCode)
                                ->select('plan_master_id', DB::raw('MAX(stage_code) as max_possible_stage_code'))
                                ->groupBy('plan_master_id');

                        $yieldSub = DB::table('yields')
                                ->select('stage_plan_id', DB::raw('SUM(yield) as total_yield'))
                                ->groupBy('stage_plan_id');

                        $planMasterQuery = DB::table('plan_master as pm')
                                ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
                                ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
                                        $join->on('pm.id', '=', 'sp_max.plan_master_id');
                                })
                                ->leftJoinSub($maxPossibleStage, 'sp_possible', function ($join) {
                                        $join->on('pm.id', '=', 'sp_possible.plan_master_id');
                                })
                                ->leftJoin('stage_plan as sp', function ($join) {
                                        $join->on('pm.id', '=', 'sp.plan_master_id')
                                                ->on('sp.stage_code', '=', 'sp_max.max_stage_code');
                                })
                                ->leftJoinSub($yieldSub, 'ys', function ($join) {
                                        $join->on('sp.id', '=', 'ys.stage_plan_id');
                                })
                                ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                                ->where('pm.active', 1)
                                ->where('pl.type', 1)
                                ->where('pm.only_parkaging', 0)
                                ->where('pm.plan_list_id', '!=', 0)
                                ->where('pm.plan_list_id', '>', 23)
                                ->where('pm.cancel', 0)
                                ->where('pm.deparment_code', $departmentCode)
                                ->whereRaw("NOT (
                    (IFNULL(sp.finished, 0) = 1 AND IFNULL(sp_max.max_stage_code, 0) < 7 AND IFNULL(sp_max.max_stage_code, 0) = IFNULL(sp_possible.max_possible_stage_code, -1)) 
                    OR (IFNULL(sp.finished, 0) = 1 AND IFNULL(sp_max.max_stage_code, 0) = 7)
                )");

                        $planMasterIds = (clone $planMasterQuery)->pluck('pm.id')->toArray();

                        $planMasterData = (clone $planMasterQuery)
                                ->select(
                                        'fpc.finished_product_code as product_code',
                                        'fpc.intermediate_code',
                                        DB::raw('COUNT(pm.id) as batch_count'),
                                        DB::raw('MAX(fpc.batch_qty) as batch_qty'),
                                        DB::raw('SUM(CASE WHEN IFNULL(sp_max.max_stage_code, 0) < ' . $effectiveStageCode . ' THEN 1 ELSE 0 END) as inventory_count'),
                                        DB::raw('SUM(CASE WHEN IFNULL(sp_max.max_stage_code, 0) < ' . $effectiveStageCode . ' THEN IFNULL(ys.total_yield, 0) ELSE 0 END) as inventory_qty')
                                )
                                ->groupBy('fpc.finished_product_code', 'fpc.intermediate_code')
                                ->get();
                } else {
                        $planMasterQuery = DB::table('plan_master as pm')
                                ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                                ->where('pm.plan_list_id', $id)
                                ->where('pm.active', 1)
                                ->where('pm.cancel', 0)
                                ->where('pm.only_parkaging', 0);

                        $planMasterData = (clone $planMasterQuery)
                                ->select(
                                        'fpc.finished_product_code as product_code',
                                        'fpc.intermediate_code',
                                        DB::raw('COUNT(pm.id) as batch_count'),
                                        DB::raw('MAX(fpc.batch_qty) as batch_qty')
                                )
                                ->groupBy('fpc.finished_product_code', 'fpc.intermediate_code')
                                ->get();
                }

                if ($planMasterData->isEmpty()) {
                        return response()->json(['success' => true, 'data' => []]);
                }

                $productCodes = $planMasterData->pluck('product_code')->filter(function ($val) {
                        return $val && $val !== 'NA';
                })->unique()->toArray();
                $intermediateCodes = $planMasterData->pluck('intermediate_code')->filter(function ($val) {
                        return $val && $val !== 'NA';
                })->unique()->toArray();

                $groupByLine = request()->query('group_by') === 'line';

                $scheduledCounts = DB::table('stage_plan')
                        ->where('stage_code', $effectiveStageCode)
                        ->where('finished', 0)
                        ->where(function ($query) {
                                $query->whereNotNull('actual_start')
                                        ->orWhereNotNull('schedualed_at');
                        })
                        ->select('resourceId', DB::raw('COUNT(*) as scheduled_count'))
                        ->groupBy('resourceId')
                        ->pluck('scheduled_count', 'resourceId')
                        ->toArray();

                $quotasQuery = DB::table('quota as q')
                        ->join('room as r', 'q.room_id', '=', 'r.id')
                        ->leftJoin('blister_type as bt', 'r.blister_type_code', '=', 'bt.code')
                        ->where('r.deparment_code', $departmentCode)
                        ->where('q.active', 1);

                if ($stageCodeReq && $stageCodeReq !== 'all') {
                        $quotasQuery->where('q.stage_code', $stageCodeReq);
                } else {
                        $quotasQuery->whereIn('q.stage_code', [3, 4, 5, 6, 7]);
                }

                $quotas = $quotasQuery->select('q.finished_product_code', 'q.intermediate_code', 'q.room_id', 'q.m_time', 'r.name as equipment_name', 'r.code as equipment_code', 'r.main_equiment_name', 'r.blister_type_code', 'bt.name as blister_type_name', 'r.order_by as room_order_by')->get();

                $equipmentStats = [];

                foreach ($planMasterData as $plan) {
                        $productQuotas = $quotas->filter(function ($q) use ($plan) {
                                return ($q->finished_product_code === $plan->product_code && $q->finished_product_code !== 'NA') ||
                                        ($q->intermediate_code === $plan->intermediate_code && $q->intermediate_code !== 'NA');
                        });
                        $processedGroups = [];
                        foreach ($productQuotas as $q) {
                                $mTimeVal = $q->m_time;
                                $mTime = 0;
                                if (strpos($mTimeVal, ':') !== false) {
                                        $parts = explode(':', $mTimeVal);
                                        $mTime = (float)$parts[0] + ((float)$parts[1] / 60);
                                } else {
                                        $mTime = (float)$mTimeVal;
                                }

                                $roomId = $q->room_id;

                                $groupId = $roomId;
                                $groupCode = $q->equipment_code;
                                $groupName = $q->equipment_name;
                                $groupMainName = $q->main_equiment_name;

                                if ($groupByLine && !empty($q->blister_type_code)) {
                                        $groupId = 'line_' . $q->blister_type_code;
                                        $groupCode = 'Dòng ' . ($q->blister_type_name ?? $q->blister_type_code);
                                        $groupName = 'Tập hợp các máy dòng ' . ($q->blister_type_name ?? $q->blister_type_code);
                                        $groupMainName = 'Multiple';
                                }

                                if (in_array($groupId, $processedGroups)) {
                                        continue;
                                }
                                $processedGroups[] = $groupId;

                                if (!isset($equipmentStats[$groupId])) {
                                        $sched = 0;
                                        if (!$groupByLine && isset($scheduledCounts[$roomId])) {
                                                $sched = $scheduledCounts[$roomId];
                                        } elseif ($groupByLine && !empty($q->blister_type_code)) {
                                                $lineEquipments = $quotas->where('blister_type_code', $q->blister_type_code)->pluck('room_id')->unique();
                                                foreach ($lineEquipments as $rId) {
                                                        if (isset($scheduledCounts[$rId])) {
                                                                $sched += $scheduledCounts[$rId];
                                                        }
                                                }
                                        }

                                        $equipmentStats[$groupId] = [
                                                'room_id' => $groupId,
                                                'equipment_code' => $groupCode,
                                                'equipment_name' => $groupName,
                                                'main_equipment_name' => $groupMainName,
                                                'blister_type_code' => $q->blister_type_code,
                                                'room_order_by' => $q->room_order_by,
                                                'total_batches' => 0,
                                                'total_time' => 0,
                                                'total_quantity' => 0,
                                                'scheduled_batches' => $sched,
                                                'inventory_qty' => 0,
                                        ];
                                }

                                $batchCount = (float)$plan->batch_count;
                                $batchQty = (float)$plan->batch_qty;
                                $equipmentStats[$groupId]['total_batches'] += $batchCount;
                                $equipmentStats[$groupId]['total_time'] += ($mTime * $batchCount);
                                $equipmentStats[$groupId]['total_quantity'] += ($batchQty * $batchCount);

                                if (isset($plan->inventory_qty)) {
                                        $equipmentStats[$groupId]['inventory_qty'] += $plan->inventory_qty;
                                }
                        }
                }

                return response()->json([
                        'success' => true,
                        'data' => array_values($equipmentStats)
                ]);
        }
}
