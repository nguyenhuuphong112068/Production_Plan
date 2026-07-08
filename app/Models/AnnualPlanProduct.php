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

    // Removed intermediateCategory relation since column was dropped

    public function finishedProductCategory()
    {
        return $this->belongsTo(FinishedProductCategory::class, 'finished_product_category_id');
    }
}
