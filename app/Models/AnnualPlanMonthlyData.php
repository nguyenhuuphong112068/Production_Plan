<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualPlanMonthlyData extends Model
{
    use HasFactory;
    protected $table = 'annual_plan_monthly_data';
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(AnnualPlanProduct::class, 'annual_plan_product_id');
    }
}
