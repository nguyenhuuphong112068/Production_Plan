<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualPlan extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'year',
        'description',
        'created_by',
        'deparment_code'
    ];

    public function products()
    {
        return $this->hasMany(AnnualPlanProduct::class);
    }
}
