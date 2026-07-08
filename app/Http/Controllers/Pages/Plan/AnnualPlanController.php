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
        
        $allFinishedProducts = \App\Models\FinishedProductCategory::with('productName')->get();
        $existingProductIds = $plan->products->pluck('finished_product_category_id')->toArray();

        $markets = \Illuminate\Support\Facades\DB::table('market')->pluck('name', 'id');
        $dosages = \Illuminate\Support\Facades\DB::table('dosage')->pluck('name', 'id');
        $intermediateDosages = \Illuminate\Support\Facades\DB::table('intermediate_category')->pluck('dosage_id', 'intermediate_code');

        $hotData = [];
        foreach($plan->products as $product) {
            $fpc = $product->finishedProductCategory;
            $market_name = $fpc && $fpc->market_id ? ($markets[$fpc->market_id] ?? '') : '';
            $int_code = $fpc ? $fpc->intermediate_code : null;
            $dosage_id = $int_code ? ($intermediateDosages[$int_code] ?? null) : null;
            $dosage_name = $dosage_id ? ($dosages[$dosage_id] ?? '') : '';

            $row = [
                'id' => $product->id,
                'registration_expiry' => $product->registration_expiry,
                'classification' => $product->classification,
                'customer_type' => $product->customer_type,
                'market' => $market_name,
                'dosage' => $dosage_name,
                'intermediate_code' => $int_code ?? '',
                'finished_product_code' => $fpc->finished_product_code ?? '',
                'product_name' => $fpc->productName?->name ?? 'N/A',
                'shelf_life' => $product->shelf_life,
                'packaging_spec' => $product->packaging_spec,
                'batch_size' => $fpc->batch_qty ?? 0,
                'avg_sales_box' => $product->avg_sales_box,
                'avg_sales_pill' => $product->avg_sales_pill,
            ];
            
            // Initialize 12 months with null
            for ($m = 1; $m <= 12; $m++) {
                $row['m'.$m.'_batches'] = null;
                $row['m'.$m.'_expected_inventory'] = null;
            }
            
            foreach($product->monthlyData as $md) {
                $row['m'.$md->month.'_batches'] = $md->planned_batches;
                $row['m'.$md->month.'_expected_inventory'] = $md->inventory_fg; // Assuming inventory_fg is expected inventory
            }
            
            $hotData[] = $row;
        }

        return view('pages.plan.annual.show', compact('plan', 'hotData', 'allFinishedProducts', 'existingProductIds'));
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

        foreach ($data as $row) {
            if (!isset($row['id'])) continue;

            $product = AnnualPlanProduct::find($row['id']);
            if ($product) {
                // Update fields
                if (array_key_exists('classification', $row)) $product->classification = $row['classification'];
                if (array_key_exists('customer_type', $row)) $product->customer_type = $row['customer_type'];
                if (array_key_exists('shelf_life', $row)) {
                    $product->shelf_life = $row['shelf_life'] !== null && $row['shelf_life'] !== '' ? (int) $row['shelf_life'] : null;
                }
                if (array_key_exists('registration_expiry', $row)) {
                    $product->registration_expiry = $row['registration_expiry'] ?: null;
                }
                if (array_key_exists('packaging_spec', $row)) {
                    $product->packaging_spec = $row['packaging_spec'] !== null && $row['packaging_spec'] !== '' ? (int) $row['packaging_spec'] : null;
                }
                if (array_key_exists('avg_sales_box', $row)) {
                    $product->avg_sales_box = $row['avg_sales_box'] !== null && $row['avg_sales_box'] !== '' ? (int) str_replace(',', '', $row['avg_sales_box']) : null;
                }
                if (array_key_exists('avg_sales_pill', $row)) {
                    $product->avg_sales_pill = $row['avg_sales_pill'] !== null && $row['avg_sales_pill'] !== '' ? (int) str_replace(',', '', $row['avg_sales_pill']) : null;
                }
                $product->save();

                // Update monthly data
                for ($m = 1; $m <= 12; $m++) {
                    $batches_key = 'm' . $m . '_batches';
                    if (array_key_exists($batches_key, $row)) {
                        $planned_batches = $row[$batches_key] !== null && $row[$batches_key] !== '' ? (int) str_replace(',', '', $row[$batches_key]) : null;

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

        return response()->json(['success' => true, 'message' => 'Lưu dữ liệu thành công!']);
    }
}
