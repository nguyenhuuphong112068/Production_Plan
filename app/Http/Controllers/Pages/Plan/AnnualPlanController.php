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
            SELECT MatID AS ProductID, Mfgbatchno AS lot_number, recttlqty AS Qty 
            FROM FGGRN 
            WHERE GRNAPSTS = 3 AND CRON < ?
            
            UNION ALL
            
            SELECT i.prdid AS ProductID, g.Mfgbatchno AS lot_number, (i.ttlqty * -1) AS Qty 
            FROM fgisuregitem i 
            JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno 
            JOIN FGGRN g ON i.FGGRNNO = g.GRNNO
            WHERE r.apsts = 1 AND r.issuedate < ?
        ),
        LotBalances AS (
            SELECT ProductID, lot_number, SUM(Qty) as LotQty
            FROM InventoryTransactions
            GROUP BY ProductID, lot_number
            HAVING SUM(Qty) >= 1000
        )
        SELECT ProductID, SUM(LotQty) as OpeningQty
        FROM LotBalances
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

        // 3. Thực xuất KD từng tháng trong năm kế hoạch
        $kdMonthlyNets = [];
        $kdSql = "
        SELECT 
            i.prdid AS ProductID, 
            MONTH(r.issuedate) as Mth, 
            SUM(i.ttlqty) as Qty
        FROM fgisuregitem i
        JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno
        WHERE r.apsts = 1 
          AND r.rem = 'KHUONG DUY'
          AND r.issuedate >= ? AND r.issuedate <= ?
        GROUP BY i.prdid, MONTH(r.issuedate)
        ";
        try {
            $kdResults = \Illuminate\Support\Facades\DB::connection('mms')->select($kdSql, [$startDate, $endDate]);
            foreach($kdResults as $r) {
                $trimmedPid = trim($r->ProductID);
                if (in_array($trimmedPid, $matIds)) {
                    $kdMonthlyNets[$trimmedPid][$r->Mth] = $r->Qty;
                }
            }
        } catch (\Exception $e) { }
        // --- Kết thúc tính toán Tồn Kho Lũy Kế từ MMS ---

        // --- Tính toán BTP dở dang (WIP) từ plan_master ---
        $wipQuery = "
            SELECT 
                pm.product_caterogy_id AS fpc_id,
                COALESCE(
                    CASE WHEN pm.order_number_R1 IS NOT NULL AND pm.order_number_R1 <> '' THEN pm.create_at_order_number ELSE NULL END,
                    weighing_sp.start_date
                ) as start_date,
                packaging_sp.end_date,
                CASE WHEN packaging_sp.end_date IS NOT NULL THEN 1 ELSE 0 END as is_finished,
                fpc.batch_qty
            FROM plan_master pm
            JOIN finished_product_category fpc ON pm.product_caterogy_id = fpc.id
            LEFT JOIN (
                SELECT plan_master_id, MIN(COALESCE(actual_end, actual_start, end)) as start_date
                FROM stage_plan
                WHERE stage_code IN (1, 2) AND finished = 1
                GROUP BY plan_master_id
            ) weighing_sp ON weighing_sp.plan_master_id = pm.id
            LEFT JOIN (
                SELECT plan_master_id, MAX(COALESCE(actual_end, actual_start, end)) as end_date
                FROM stage_plan
                WHERE stage_code >= 7 AND finished = 1
                GROUP BY plan_master_id
            ) packaging_sp ON packaging_sp.plan_master_id = pm.id
            WHERE pm.active = 1 AND pm.cancel = 0
              AND (
                  (pm.order_number_R1 IS NOT NULL AND pm.order_number_R1 <> '' AND pm.create_at_order_number IS NOT NULL)
                  OR weighing_sp.start_date IS NOT NULL
              )
        ";
        
        $wipBatches = \Illuminate\Support\Facades\DB::select($wipQuery);
        $wipData = [];
        $planYearInt = (int) $plan->year;

        foreach ($wipBatches as $batch) {
            $startDate = $batch->start_date ? \Carbon\Carbon::parse($batch->start_date) : null;
            $endDate = $batch->end_date ? \Carbon\Carbon::parse($batch->end_date) : null;
            $isFinished = (bool) $batch->is_finished;
            
            if (!$startDate) {
                continue;
            }
            
            for ($m = 1; $m <= 12; $m++) {
                $endOfMonth = \Carbon\Carbon::create($planYearInt, $m, 1)->endOfMonth();
                
                $hasStarted = $startDate->lte($endOfMonth);
                
                $hasNotEnded = true;
                if ($isFinished && $endDate) {
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
                'average_astimated_box' => $fpc->average_astimated_box,
                'average_astimated_pill' => ($fpc->average_astimated_box ?? 0) * ($fpc->packaging_spec ?? 0),
            ];
            
            $matId = $fpc->finished_product_code ? trim($fpc->finished_product_code) : null;
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

            // Calculate planned_quantity and months_sales, and KD actual export, ratio, safety stock
            $avg_forecast_pill = ($fpc->average_astimated_box ?? 0) * ($fpc->packaging_spec ?? 0);
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

                // Thực xuất KD
                $kd_qty = ($matId && isset($kdMonthlyNets[$matId][$m])) ? $kdMonthlyNets[$matId][$m] : 0;
                $row['m'.$m.'_kd_export'] = $kd_qty;

                // Tỉ lệ thực xuất / dự trù và dự trữ an toàn
                if ($avg_forecast_pill > 0) {
                    $row['m'.$m.'_kd_ratio'] = round($kd_qty / $avg_forecast_pill, 4);
                    $row['m'.$m.'_kd_safety_stock'] = round($fg / $avg_forecast_pill, 2);
                } else {
                    $row['m'.$m.'_kd_ratio'] = 0;
                    $row['m'.$m.'_kd_safety_stock'] = 0;
                }
            }
            
            $hotData[] = $row;
        }

        $pendingPlans = \Illuminate\Support\Facades\DB::table('plan_list')
            ->where('send', 0)
            ->where('active', 1)
            ->where('type', 1)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return view('pages.plan.annual.show', compact('plan', 'hotData', 'existingProductIds', 'pendingPlans'));
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
                        if (array_key_exists('average_astimated_box', $row)) {
                            $fpc->average_astimated_box = $row['average_astimated_box'] !== null && $row['average_astimated_box'] !== '' ? (int) str_replace(',', '', $row['average_astimated_box']) : null;
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

    public function getWipDetails($productId, $month)
    {
        $appProduct = AnnualPlanProduct::findOrFail($productId);
        $plan = AnnualPlan::findOrFail($appProduct->annual_plan_id);
        $fpc_id = $appProduct->finished_product_category_id;
        $planYearInt = (int) $plan->year;
        $month = (int) $month;

        $wipQuery = "
            SELECT 
                pm.id as plan_id,
                pm.batch as planned_batch,
                pm.actual_batch,
                pm.order_number_R1,
                COALESCE(
                    CASE WHEN pm.order_number_R1 IS NOT NULL AND pm.order_number_R1 <> '' THEN pm.create_at_order_number ELSE NULL END,
                    weighing_sp.start_date
                ) as start_date,
                packaging_sp.end_date,
                CASE WHEN packaging_sp.end_date IS NOT NULL THEN 1 ELSE 0 END as is_finished,
                fpc.batch_qty,
                CASE
                    WHEN pm.cancel = 1 THEN 'Hủy'
                    WHEN sp_max.max_stage_code IS NULL THEN 'Chưa làm'
                    WHEN sp_max.max_stage_code < 7 AND sp_max.max_stage_code = sp_possible.max_possible_stage_code THEN 'Hoàn Tất'
                    WHEN sp_max.max_stage_code = 1 THEN 'Đã Cân'
                    WHEN sp_max.max_stage_code = 2 THEN 'Đã Cân'
                    WHEN sp_max.max_stage_code = 3 THEN 'Đã Pha chế'
                    WHEN sp_max.max_stage_code = 4 THEN 'Đã THT'
                    WHEN sp_max.max_stage_code = 5 THEN 'Đã định hình'
                    WHEN sp_max.max_stage_code = 6 THEN 'Đã Bao phim'
                    WHEN sp_max.max_stage_code >= 7 THEN 'Hoàn Tất ĐG'
                    ELSE 'Đang dở dang'
                END as current_stage_status
            FROM plan_master pm
            JOIN finished_product_category fpc ON pm.product_caterogy_id = fpc.id
            LEFT JOIN (
                SELECT plan_master_id, MIN(COALESCE(actual_end, actual_start, end)) as start_date
                FROM stage_plan
                WHERE stage_code IN (1, 2) AND finished = 1
                GROUP BY plan_master_id
            ) weighing_sp ON weighing_sp.plan_master_id = pm.id
            LEFT JOIN (
                SELECT plan_master_id, MAX(COALESCE(actual_end, actual_start, end)) as end_date
                FROM stage_plan
                WHERE stage_code >= 7 AND finished = 1
                GROUP BY plan_master_id
            ) packaging_sp ON packaging_sp.plan_master_id = pm.id
            LEFT JOIN (
                SELECT plan_master_id, MAX(stage_code) as max_stage_code
                FROM stage_plan
                WHERE finished = 1
                GROUP BY plan_master_id
            ) sp_max ON sp_max.plan_master_id = pm.id
            LEFT JOIN (
                SELECT plan_master_id, MAX(stage_code) as max_possible_stage_code
                FROM stage_plan
                WHERE active = 1 AND stage_code <> 8
                GROUP BY plan_master_id
            ) sp_possible ON sp_possible.plan_master_id = pm.id
            WHERE pm.active = 1 AND pm.cancel = 0
              AND pm.product_caterogy_id = ?
              AND (
                  (pm.order_number_R1 IS NOT NULL AND pm.order_number_R1 <> '' AND pm.create_at_order_number IS NOT NULL)
                  OR weighing_sp.start_date IS NOT NULL
              )
        ";

        $batches = \Illuminate\Support\Facades\DB::select($wipQuery, [$fpc_id]);
        $details = [];

        $endOfMonth = \Carbon\Carbon::create($planYearInt, $month, 1)->endOfMonth();

        foreach ($batches as $batch) {
            $startDate = $batch->start_date ? \Carbon\Carbon::parse($batch->start_date) : null;
            $endDate = $batch->end_date ? \Carbon\Carbon::parse($batch->end_date) : null;
            $isFinished = (bool) $batch->is_finished;

            if (!$startDate) {
                continue;
            }

            $hasStarted = $startDate->lte($endOfMonth);
            $hasNotEnded = true;
            if ($isFinished && $endDate) {
                $hasNotEnded = $endDate->gt($endOfMonth);
            }

            if ($hasStarted && $hasNotEnded) {
                $status = $batch->current_stage_status;
                if ($isFinished && $endDate) {
                    $status = 'Hoàn Tất ĐG (' . $endDate->format('d/m/Y') . ')';
                }

                $details[] = [
                    'plan_id' => $batch->plan_id,
                    'batch_code' => $batch->actual_batch ?: $batch->planned_batch,
                    'order_number' => $batch->order_number_R1 ?: 'Chưa có',
                    'start_date' => $startDate->format('d/m/Y H:i'),
                    'end_date' => $endDate ? $endDate->format('d/m/Y H:i') : 'Chưa hoàn thành',
                    'status' => $status,
                    'batch_qty' => number_format($batch->batch_qty)
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $details
        ]);
    }

    public function getInventoryDetails($productId, $month)
    {
        $appProduct = AnnualPlanProduct::findOrFail($productId);
        $plan = AnnualPlan::findOrFail($appProduct->annual_plan_id);
        $fpc = $appProduct->finishedProductCategory;
        $finishedProductCode = $fpc ? $fpc->finished_product_code : null;

        if (!$finishedProductCode) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $endOfMonth = \Carbon\Carbon::create((int) $plan->year, (int) $month, 1)->endOfMonth()->format('Y-m-d 23:59:59');

        $sql = "
            SELECT 
                t.lot_number,
                SUM(t.Qty) AS quantity
            FROM (
                SELECT Mfgbatchno AS lot_number, recttlqty AS Qty 
                FROM FGGRN 
                WHERE GRNAPSTS = 3 AND MatID = ? AND CRON <= ?
                
                UNION ALL
                
                SELECT g.Mfgbatchno AS lot_number, (i.ttlqty * -1) AS Qty 
                FROM fgisuregitem i 
                JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno 
                JOIN FGGRN g ON i.FGGRNNO = g.GRNNO
                WHERE r.apsts = 1 AND i.prdid = ? AND r.issuedate <= ?
            ) t
            GROUP BY t.lot_number
            HAVING SUM(t.Qty) >= 1000
            ORDER BY t.lot_number ASC
        ";

        $results = \Illuminate\Support\Facades\DB::connection('mms')->select($sql, [
            $finishedProductCode, $endOfMonth,
            $finishedProductCode, $endOfMonth
        ]);

        $data = [];
        foreach ($results as $r) {
            $data[] = [
                'lot_number' => trim($r->lot_number),
                'quantity' => number_format($r->quantity)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function pushToMonthly(Request $request, $id)
    {
        $plan = AnnualPlan::with('products.monthlyData')->findOrFail($id);
        $month = (int) $request->input('month');
        $targetPlanListId = (int) $request->input('target_plan_list_id');

        $targetPlanList = \Illuminate\Support\Facades\DB::table('plan_list')
            ->where('id', $targetPlanListId)
            ->first();

        if (!$targetPlanList || $targetPlanList->type != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Kế hoạch tháng nhận không tồn tại hoặc không phải là kế hoạch sản xuất!'
            ], 404);
        }

        if ($targetPlanList->send == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Kế hoạch tháng nhận đã được gửi đi, không thể đẩy thêm!'
            ], 400);
        }

        $targetYear = $targetPlanList->year;
        $targetMonth = $targetPlanList->month;
        $baseDate = \Carbon\Carbon::create($targetYear, $targetMonth, 1)->startOfDay();
        $expectedDate = $baseDate->copy()->addDays(40)->format('Y-m-d');

        $targetMonthStr = str_pad($targetMonth, 2, '0', STR_PAD_LEFT);
        $targetYearStr = substr($targetYear, -2);

        $insertedCount = 0;

        // Fetch BOM codes for all products in this plan
        $intermediateCodes = [];
        $finishedProductCodes = [];
        foreach ($plan->products as $product) {
            $fpc = $product->finishedProductCategory;
            if ($fpc) {
                if ($fpc->intermediate_code) {
                    $intermediateCodes[] = $fpc->intermediate_code;
                }
                if ($fpc->finished_product_code) {
                    $finishedProductCodes[] = $fpc->finished_product_code;
                }
            }
        }
        $intermediateCodes = array_values(array_unique(array_filter($intermediateCodes)));
        $finishedProductCodes = array_values(array_unique(array_filter($finishedProductCodes)));

        $filteredMaterials = [];
        $filteredPackagings = [];

        try {
            $materialsBoms = [];
            if (!empty($intermediateCodes)) {
                $materialsBoms = \Illuminate\Support\Facades\DB::connection('mms')
                    ->table('yfBOM_BOMItemHP')
                    ->whereIn('PrdID', $intermediateCodes)
                    ->get();
            }

            $packagingsBoms = [];
            if (!empty($finishedProductCodes)) {
                $packagingsBoms = \Illuminate\Support\Facades\DB::connection('mms')
                    ->table('yfBOM_BOMItemHP')
                    ->whereIn('PrdID', $finishedProductCodes)
                    ->get();
            }

            $materialsGrouped = collect($materialsBoms)->groupBy('PrdID');
            foreach ($materialsGrouped as $prdId => $items) {
                $maxRev = $items->max('Revno');
                $filteredMaterials[$prdId] = $items->filter(fn($item) => $item->Revno == $maxRev);
            }

            $packagingsGrouped = collect($packagingsBoms)->groupBy('PrdID');
            foreach ($packagingsGrouped as $prdId => $items) {
                $maxRev = $items->max('Revno');
                $filteredPackagings[$prdId] = $items->filter(fn($item) => $item->Revno == $maxRev);
            }
        } catch (\Exception $e) {
            // Silence or handle connection errors
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($plan->products as $product) {
                $fpc = $product->finishedProductCategory;
                if (!$fpc) {
                    continue;
                }

                $fpc_id = $fpc->id;
                $deparment_code = $fpc->deparment_code ?? session('user')['production_code'] ?? 'PX1';

                $md = $product->monthlyData->where('month', $month)->first();
                $plannedBatches = $md ? (int) $md->planned_batches : 0;

                if ($plannedBatches <= 0) {
                    continue;
                }

                $existingCount = \Illuminate\Support\Facades\DB::table('plan_master')
                    ->where('plan_list_id', $targetPlanListId)
                    ->where('product_caterogy_id', $fpc_id)
                    ->where('active', 1)
                    ->where('cancel', 0)
                    ->count();

                $batchesToPush = $plannedBatches - $existingCount;

                if ($batchesToPush <= 0) {
                    continue;
                }

                $latestPlan = \Illuminate\Support\Facades\DB::table('plan_master')
                    ->where('product_caterogy_id', $fpc_id)
                    ->whereNotNull('batch')
                    ->where('batch', '<>', '')
                    ->where('batch', '<>', 'NA')
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $currentLastBatch = $latestPlan ? ($latestPlan->actual_batch ?: $latestPlan->batch) : null;

                for ($i = 0; $i < $batchesToPush; $i++) {
                    $newBatchStr = $this->incrementBatchNumber($currentLastBatch, $targetMonthStr, $targetYearStr);
                    $currentLastBatch = $newBatchStr;

                    $planMasterId = \Illuminate\Support\Facades\DB::table('plan_master')->insertGetId([
                        "product_caterogy_id" => $fpc_id,
                        "plan_list_id" => $targetPlanListId,
                        "batch" => $newBatchStr,
                        "expected_date" => $expectedDate,
                        "responsed_date" => $expectedDate,
                        "level" => 1,
                        "is_val" => 0,
                        "is_validation_tracking" => 0,
                        "percent_parkaging" => 1,
                        "number_parkaging" => 0,
                        "only_parkaging" => 0,
                        "note" => "Đẩy từ kế hoạch năm",
                        'deparment_code' => $deparment_code,
                        'prepared_by' => session('user')['fullName'] ?? 'Auto-generate',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    \Illuminate\Support\Facades\DB::table('plan_master')
                        ->where('id', $planMasterId)
                        ->update(['main_parkaging_id' => $planMasterId]);

                    // Insert BOM items into plan_master_materials
                    $bomMaterialsToInsert = [];
                    $intermediateCode = $fpc->intermediate_code;
                    $user_fullname = session('user')['fullName'] ?? 'Auto-generate';
                    
                    if ($intermediateCode && isset($filteredMaterials[$intermediateCode])) {
                        foreach ($filteredMaterials[$intermediateCode] as $bomItem) {
                            $bomMaterialsToInsert[] = [
                                'plan_master_id' => $planMasterId,
                                'material_packaging_code' => (string) $bomItem->MatID,
                                'material_packaging_type' => 0,
                                'Revno' => $bomItem->Revno,
                                'qty' => (float) $bomItem->MatQty,
                                'unit_bom' => $bomItem->uom,
                                'MaterialName' => $bomItem->MaterialName,
                                'created_at' => now(),
                                'created_by' => $user_fullname,
                                'active' => 1
                            ];
                        }
                    }

                    $finishedProductCode = $fpc->finished_product_code;
                    if ($finishedProductCode && isset($filteredPackagings[$finishedProductCode])) {
                        foreach ($filteredPackagings[$finishedProductCode] as $bomItem) {
                            $bomMaterialsToInsert[] = [
                                'plan_master_id' => $planMasterId,
                                'material_packaging_code' => (string) $bomItem->MatID,
                                'material_packaging_type' => 1,
                                'Revno' => $bomItem->Revno,
                                'qty' => (float) $bomItem->MatQty,
                                'unit_bom' => $bomItem->uom,
                                'MaterialName' => $bomItem->MaterialName,
                                'created_at' => now(),
                                'created_by' => $user_fullname,
                                'active' => 1
                            ];
                        }
                    }

                    if (!empty($bomMaterialsToInsert)) {
                        \Illuminate\Support\Facades\DB::table('plan_master_materials')->insert($bomMaterialsToInsert);
                    }

                    \Illuminate\Support\Facades\DB::table('plan_master_history')->insert([
                        "plan_master_id" => $planMasterId,
                        "plan_list_id" => $targetPlanListId,
                        "product_caterogy_id" => $fpc_id,
                        "batch" => $newBatchStr,
                        "expected_date" => $expectedDate,
                        "level" => 1,
                        "is_val" => 0,
                        "percent_parkaging" => 1,
                        "number_parkaging" => 0,
                        "only_parkaging" => 0,
                        "note" => "Đẩy từ kế hoạch năm",
                        'deparment_code' => $deparment_code,
                        'prepared_by' => session('user')['fullName'] ?? 'Auto-generate',
                        'created_at' => now(),
                        'updated_at' => now(),
                        "version" => 1,
                        "reason" => "Tạo Mới",
                    ]);

                    $insertedCount++;
                }
            }

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }

        if ($insertedCount === 0) {
            return response()->json([
                'success' => true,
                'message' => "Tất cả các lô của tháng này đã được đẩy trước đó hoặc đã tồn tại đầy đủ trong kế hoạch tháng '{$targetPlanList->name}'. Không có lô nào bị trùng lặp!",
                'redirect_url' => route('pages.plan.production.open', ['plan_list_id' => $targetPlanListId])
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Đã đẩy bổ sung thành công {$insertedCount} lô hàng mới vào kế hoạch tháng '{$targetPlanList->name}'!",
            'redirect_url' => route('pages.plan.production.open', ['plan_list_id' => $targetPlanListId])
        ]);
    }

    private function incrementBatchNumber($lastBatchStr, $targetMonthStr, $targetYearStr)
    {
        $lastBatchStr = trim($lastBatchStr);
        if (strlen($lastBatchStr) >= 5 && strlen($lastBatchStr) <= 8 && !str_contains($lastBatchStr, ',')) {
            if (preg_match('/^(\d+)(\d{4})(.*)$/', $lastBatchStr, $matches)) {
                $prefix = $matches[1];
                $valSuffix = $matches[3];

                $prefixVal = (int)$prefix;
                $newPrefixVal = $prefixVal + 1;

                $newPrefix = str_pad($newPrefixVal, strlen($prefix), '0', STR_PAD_LEFT);
                return $newPrefix . $targetMonthStr . $targetYearStr . $valSuffix;
            }
        }
        return '01' . $targetMonthStr . $targetYearStr;
    }
}
