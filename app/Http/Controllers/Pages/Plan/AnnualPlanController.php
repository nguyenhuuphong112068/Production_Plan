<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AnnualPlan;
use App\Models\AnnualPlanProduct;
use App\Models\AnnualPlanMonthlyData;

class AnnualPlanController extends Controller
{
    public function index()
    {
        $plans = AnnualPlan::orderBy('year', 'desc')->get();
        return view('pages.plan.annual.index', compact('plans'));
    }

    public function show($id)
    {
        $plan = AnnualPlan::with(['products.monthlyData', 'products.finishedProductCategory.productName'])->findOrFail($id);
        
        $existingProductIds = $plan->products->pluck('finished_product_category_id')->toArray();

        $markets = \Illuminate\Support\Facades\DB::table('market')->pluck('name', 'id');
        $dosages = \Illuminate\Support\Facades\DB::table('dosage')->pluck('name', 'id');
        $intermediateDosages = \Illuminate\Support\Facades\DB::table('intermediate_category')->pluck('dosage_id', 'intermediate_code');

        $hotData = [];

        // --- Bắt đầu tính toán Tồn Kho Lũy Kế từ MMS ---
        $matIds = [];
        foreach($plan->products as $product) {
            $fpc = $product->finishedProductCategory;
            if ($fpc && $fpc->finished_product_code) {
                $matIds[] = $fpc->finished_product_code;
            }
        }
        $matIds = array_unique($matIds);

        $openingBalances = [];
        $monthlyNets = [];

        $startDate = $plan->year . '-01-01 00:00:00';
        $endDate = $plan->year . '-12-31 23:59:59';
        
        // 1. Số dư đầu kỳ (Tính đến trước 01/01 của năm kế hoạch)
        $openingSql = "
        WITH InventoryTransactions AS (
            SELECT MatID AS ProductID, recttlqty AS Qty 
            FROM FGGRN 
            WHERE GRNAPSTS = 3 AND CRON < ?
            
            UNION ALL
            
            SELECT i.prdid AS ProductID, (i.ttlqty * -1) AS Qty 
            FROM fgisuregitem i 
            JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno 
            WHERE r.apsts = 1 AND r.issuedate < ?
        )
        SELECT ProductID, SUM(Qty) as OpeningQty
        FROM InventoryTransactions
        GROUP BY ProductID
        ";
        try {
            $openingResults = \Illuminate\Support\Facades\DB::connection('mms')->select($openingSql, [$startDate, $startDate]);
            foreach($openingResults as $r) {
                // Chỉ lấy những mã có trong kế hoạch
                if (in_array($r->ProductID, $matIds)) {
                    $openingBalances[$r->ProductID] = $r->OpeningQty;
                }
            }
        } catch (\Exception $e) { }

        // 2. Biến động Tồn kho từng tháng trong năm kế hoạch
        $monthlySql = "
        WITH InventoryTransactions AS (
            SELECT MatID AS ProductID, recttlqty AS Qty, CRON AS TransactionDate 
            FROM FGGRN 
            WHERE GRNAPSTS = 3 AND CRON >= ? AND CRON <= ?
            
            UNION ALL
            
            SELECT i.prdid AS ProductID, (i.ttlqty * -1) AS Qty, r.issuedate AS TransactionDate 
            FROM fgisuregitem i 
            JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno 
            WHERE r.apsts = 1 AND r.issuedate >= ? AND r.issuedate <= ?
        )
        SELECT ProductID, MONTH(TransactionDate) as Mth, SUM(Qty) as NetQty
        FROM InventoryTransactions
        GROUP BY ProductID, MONTH(TransactionDate)
        ";
        try {
            $monthlyResults = \Illuminate\Support\Facades\DB::connection('mms')->select($monthlySql, [$startDate, $endDate, $startDate, $endDate]);
            foreach($monthlyResults as $r) {
                if (in_array($r->ProductID, $matIds)) {
                    $monthlyNets[$r->ProductID][$r->Mth] = $r->NetQty;
                }
            }
        } catch (\Exception $e) { }
        // --- Kết thúc tính toán Tồn Kho Lũy Kế từ MMS ---

        // --- Tính toán BTP dở dang (WIP) từ plan_master ---
        $wipQuery = "
            SELECT 
                pm.product_caterogy_id AS fpc_id,
                sp_start.actual_start AS start_date,
                sp_end.actual_end AS end_date,
                sp_end.finished as is_finished,
                fpc.batch_qty
            FROM plan_master pm
            JOIN finished_product_category fpc ON pm.product_caterogy_id = fpc.id
            JOIN stage_plan sp_start ON sp_start.plan_master_id = pm.id AND sp_start.stage_code = 1 AND sp_start.finished = 1
            JOIN (
                SELECT plan_master_id, MAX(stage_code) as max_stage_code
                FROM stage_plan
                GROUP BY plan_master_id
            ) max_sp ON max_sp.plan_master_id = pm.id
            JOIN stage_plan sp_end ON sp_end.plan_master_id = pm.id AND sp_end.stage_code = max_sp.max_stage_code
            WHERE pm.active = 1 AND pm.cancel = 0
        ";
        
        $wipBatches = \Illuminate\Support\Facades\DB::select($wipQuery);
        $wipData = [];
        $planYearInt = (int) $plan->year;

        foreach ($wipBatches as $batch) {
            if (!$batch->start_date && !$batch->is_finished) {
                continue;
            }
            
            $startDate = $batch->start_date ? \Carbon\Carbon::parse($batch->start_date) : null;
            $endDate = ($batch->is_finished && $batch->end_date) ? \Carbon\Carbon::parse($batch->end_date) : null;
            
            for ($m = 1; $m <= 12; $m++) {
                $endOfMonth = \Carbon\Carbon::create($planYearInt, $m, 1)->endOfMonth();
                
                $hasStarted = false;
                if ($startDate) {
                    $hasStarted = $startDate->lte($endOfMonth);
                } else {
                    if ($endDate && $endDate->gt($endOfMonth)) {
                        $hasStarted = true;
                    }
                }
                
                $hasNotEnded = true;
                if ($endDate) {
                    $hasNotEnded = $endDate->gt($endOfMonth);
                }
                
                if ($hasStarted && $hasNotEnded) {
                    if (!isset($wipData[$batch->fpc_id])) {
                        $wipData[$batch->fpc_id] = array_fill(1, 12, 0);
                    }
                    $wipData[$batch->fpc_id][$m] += $batch->batch_qty;
                }
            }
        }
        // --- Kết thúc tính WIP ---

        foreach($plan->products as $product) {
            $fpc = $product->finishedProductCategory;
            $market_name = $fpc && $fpc->market_id ? ($markets[$fpc->market_id] ?? '') : '';
            $int_code = $fpc ? $fpc->intermediate_code : null;
            $dosage_id = $int_code ? ($intermediateDosages[$int_code] ?? null) : null;
            $dosage_name = $dosage_id ? ($dosages[$dosage_id] ?? '') : '';

            $row = [
                'id' => $product->id,
                'registration_expiry' => $fpc->registration_expiry,
                'classification' => $fpc->classification,
                'customer_type' => $fpc->customer_type,
                'market' => $market_name,
                'dosage' => $dosage_name,
                'intermediate_code' => $int_code ?? '',
                'finished_product_code' => $fpc->finished_product_code ?? '',
                'product_name' => $fpc->productName?->name ?? 'N/A',
                'shelf_life' => $fpc->shelf_life,
                'packaging_spec' => $fpc->packaging_spec,
                'batch_size' => $fpc->batch_qty ?? 0,
                'avg_sales_box' => $fpc->avg_sales_box,
                'avg_sales_pill' => $fpc->avg_sales_pill,
            ];
            
            $matId = $fpc->finished_product_code ?? null;
            $runningBalance = $matId && isset($openingBalances[$matId]) ? $openingBalances[$matId] : 0;

            $currentYear = (int) date('Y');
            $currentMonth = (int) date('n');

            // Initialize 12 months with null
            for ($m = 1; $m <= 12; $m++) {
                $row['m'.$m.'_batches'] = null;
                $row['m'.$m.'_wip_inventory'] = null;
                $row['m'.$m.'_planned_quantity'] = null;
                $row['m'.$m.'_months_sales'] = null;
                
                // Tính tồn kho thực tế từ MMS
                if ($matId) {
                    $shouldCalculate = true;
                    if ($plan->year > $currentYear) {
                        $shouldCalculate = false;
                    } elseif ($plan->year == $currentYear && $m >= $currentMonth) {
                        $shouldCalculate = false;
                    }
                    
                    if ($shouldCalculate) {
                        $net = isset($monthlyNets[$matId][$m]) ? $monthlyNets[$matId][$m] : 0;
                        $runningBalance += $net;
                    }
                    
                    $row['m'.$m.'_expected_inventory'] = max(0, round($runningBalance, 0));
                } else {
                    $row['m'.$m.'_expected_inventory'] = null;
                }
            }
            
            foreach($product->monthlyData as $md) {
                $row['m'.$md->month.'_batches'] = $md->planned_batches;
                // $row['m'.$md->month.'_wip_inventory'] = $md->inventory_wip; // Vô hiệu hóa, giờ tính tự động
            }

            // Gán dữ liệu WIP tự động
            $fpcId = $fpc->id ?? null;
            for ($m = 1; $m <= 12; $m++) {
                $row['m'.$m.'_wip_inventory'] = ($fpcId && isset($wipData[$fpcId][$m])) ? $wipData[$fpcId][$m] : 0;
            }

            // Calculate planned_quantity and months_sales based on loaded data
            for ($m = 1; $m <= 12; $m++) {
                $batches = $row['m'.$m.'_batches'] ?: 0;
                $batch_size = $row['batch_size'] ?: 0;
                $row['m'.$m.'_planned_quantity'] = $batches * $batch_size;

                $wip = $row['m'.$m.'_wip_inventory'] ?: 0;
                $fg = $row['m'.$m.'_expected_inventory'] ?: 0;
                
                // Recalculate avg_sales_pill
                $packaging_spec = $row['packaging_spec'] ?: 0;
                $avg_sales_box = $row['avg_sales_box'] ?: 0;
                $avg_sales = $avg_sales_box * $packaging_spec;
                $row['avg_sales_pill'] = $avg_sales;

                if ($avg_sales > 0) {
                    $row['m'.$m.'_months_sales'] = round(($wip + $fg) / $avg_sales, 2);
                } else {
                    $row['m'.$m.'_months_sales'] = 0;
                }
            }
            
            $hotData[] = $row;
        }

        return view('pages.plan.annual.show', compact('plan', 'hotData', 'existingProductIds'));
    }

    public function unassignedProducts($id)
    {
        $plan = AnnualPlan::with('products')->findOrFail($id);
        $existingProductIds = $plan->products->pluck('finished_product_category_id')->filter()->toArray();
        $products = \App\Models\FinishedProductCategory::with('productName');
        
        if (!empty($existingProductIds)) {
            $products = $products->whereNotIn('id', $existingProductIds);
        }
        
        $products = $products->get();
            
        return view('pages.plan.annual.partials.unassigned_products', compact('products'))->render();
    }

    public function addProducts(Request $request, $id)
    {
        $plan = AnnualPlan::findOrFail($id);
        $selectedIds = $request->input('selected_products', []);

        if (!empty($selectedIds)) {
            $newProducts = [];
            foreach ($selectedIds as $fpc_id) {
                $newProducts[] = [
                    'annual_plan_id' => $plan->id,
                    'finished_product_category_id' => $fpc_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            AnnualPlanProduct::insert($newProducts);
        }

        return redirect()->back()->with('success', 'Đã thêm sản phẩm vào kế hoạch');
    }

    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|unique:annual_plans,year',
            'description' => 'nullable|string'
        ]);

        AnnualPlan::create([
            'year' => $request->year,
            'description' => $request->description
        ]);

        return redirect()->back()->with('success', 'Đã tạo kế hoạch năm thành công');
    }

    public function updateMonthlyData(Request $request)
    {
        $data = $request->input('data');
        $year = $request->input('year');

        if (!$data || !is_array($data)) {
            return response()->json(['success' => false, 'message' => 'No data provided']);
        }

        \Illuminate\Support\Facades\DB::transaction(function() use ($data, $year) {
            foreach ($data as $row) {
                if (!isset($row['id'])) continue;

                $product = AnnualPlanProduct::find($row['id']);
                if ($product) {
                    $fpc = $product->finishedProductCategory;
                    if ($fpc) {
                        // Update fields on finished_product_category
                        if (array_key_exists('classification', $row)) $fpc->classification = $row['classification'];
                        if (array_key_exists('customer_type', $row)) $fpc->customer_type = $row['customer_type'];
                        if (array_key_exists('shelf_life', $row)) {
                            $fpc->shelf_life = $row['shelf_life'] !== null && $row['shelf_life'] !== '' ? (int) $row['shelf_life'] : null;
                        }
                        if (array_key_exists('registration_expiry', $row)) {
                            $fpc->registration_expiry = $row['registration_expiry'] ?: null;
                        }
                        if (array_key_exists('packaging_spec', $row)) {
                            $fpc->packaging_spec = $row['packaging_spec'] !== null && $row['packaging_spec'] !== '' ? (int) $row['packaging_spec'] : null;
                        }
                        if (array_key_exists('avg_sales_box', $row)) {
                            $fpc->avg_sales_box = $row['avg_sales_box'] !== null && $row['avg_sales_box'] !== '' ? (int) str_replace(',', '', $row['avg_sales_box']) : null;
                        }
                        if (array_key_exists('avg_sales_pill', $row)) {
                            $fpc->avg_sales_pill = $row['avg_sales_pill'] !== null && $row['avg_sales_pill'] !== '' ? (int) str_replace(',', '', $row['avg_sales_pill']) : null;
                        }
                        $fpc->save();
                    }

                    // Update monthly data
                    for ($m = 1; $m <= 12; $m++) {
                        $batches_key = 'm' . $m . '_batches';
                        
                        if (array_key_exists($batches_key, $row)) {
                            $planned_batches = isset($row[$batches_key]) && $row[$batches_key] !== null && $row[$batches_key] !== '' ? (int) str_replace(',', '', $row[$batches_key]) : null;

                            AnnualPlanMonthlyData::updateOrCreate(
                                [
                                    'annual_plan_product_id' => $product->id,
                                    'month' => $m,
                                    'year' => $year
                                ],
                                [
                                    'planned_batches' => $planned_batches
                                ]
                            );
                        }
                    }
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Lưu dữ liệu thành công!']);
    }

    public function getEquipmentAllocation($id)
    {
        $month = (int) request()->query('month', 8);
        $stageCodeReq = request()->query('stage_code');
        $effectiveStageCode = ($stageCodeReq && $stageCodeReq !== 'all') ? (int)$stageCodeReq : 7;

        $departmentCode = request()->query('department_code', 'PXV1');

        $planMasterData = \Illuminate\Support\Facades\DB::table('annual_plan_products as app')
            ->join('annual_plan_monthly_data as apmd', function($join) use ($month) {
                $join->on('app.id', '=', 'apmd.annual_plan_product_id')
                     ->where('apmd.month', '=', $month);
            })
            ->join('finished_product_category as fpc', 'app.finished_product_category_id', '=', 'fpc.id')
            ->where('app.annual_plan_id', $id)
            ->where('apmd.planned_batches', '>', 0)
            ->select(
                'fpc.finished_product_code as product_code',
                'fpc.intermediate_code',
                \Illuminate\Support\Facades\DB::raw('SUM(apmd.planned_batches) as batch_count'),
                \Illuminate\Support\Facades\DB::raw('MAX(fpc.batch_qty) as batch_qty')
            )
            ->groupBy('fpc.finished_product_code', 'fpc.intermediate_code')
            ->get();

        if ($planMasterData->isEmpty()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $groupByLine = request()->query('group_by') === 'line';

        $scheduledCounts = \Illuminate\Support\Facades\DB::table('stage_plan')
            ->where('stage_code', $effectiveStageCode)
            ->where('finished', 0)
            ->where(function ($query) {
                $query->whereNotNull('actual_start')
                      ->orWhereNotNull('schedualed_at');
            })
            ->select('resourceId', \Illuminate\Support\Facades\DB::raw('COUNT(*) as scheduled_count'))
            ->groupBy('resourceId')
            ->pluck('scheduled_count', 'resourceId')
            ->toArray();

        $quotasQuery = \Illuminate\Support\Facades\DB::table('quota as q')
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
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_values($equipmentStats)
        ]);
    }
}
