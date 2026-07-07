<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualPlanProduct extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function plan()
    {
        return $this->belongsTo(AnnualPlan::class, 'annual_plan_id');
    }

    public function monthlyData()
    {
        return $this->hasMany(AnnualPlanMonthlyData::class);
    }

    public function intermediateCategory()
    {
        return $this->belongsTo(IntermediateCategory::class, 'intermediate_category_id');
    }

    public function finishedProductCategory()
    {
        return $this->belongsTo(FinishedProductCategory::class, 'finished_product_category_id');
    }
}
