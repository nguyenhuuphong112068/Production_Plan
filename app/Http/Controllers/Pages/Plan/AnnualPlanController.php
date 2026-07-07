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
        $plan = AnnualPlan::with(['products.monthlyData', 'products.finishedProductCategory.productName', 'products.intermediateCategory'])->findOrFail($id);
        
        $hotData = [];
        foreach($plan->products as $product) {
            $row = [
                'id' => $product->id,
                'product_name' => $product->finishedProductCategory?->productName?->name ?? 'N/A',
                'classification' => $product->classification,
                'customer_type' => $product->customer_type,
                'batch_size' => $product->finishedProductCategory?->batch_qty ?? 0,
                'avg_sales' => $product->avg_sales_box,
            ];
            
            foreach($product->monthlyData as $md) {
                $row['m'.$md->month.'_batches'] = $md->planned_batches;
            }
            
            $hotData[] = $row;
        }

        return view('pages.plan.annual.show', compact('plan', 'hotData'));
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
