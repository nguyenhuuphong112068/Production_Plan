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

        $hotData = [];
        foreach($plan->products as $product) {
            $row = [
                'id' => $product->id,
                'intermediate_code' => $product->finishedProductCategory?->intermediate_code ?? '',
                'product_name' => $product->finishedProductCategory?->productName?->name ?? 'N/A',
                'classification' => $product->classification,
                'customer_type' => $product->customer_type,
                'batch_size' => $product->finishedProductCategory?->batch_qty ?? 0,
                'avg_sales' => $product->avg_sales_box,
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
        // For updating cells from handsontable via ajax
    }
}
