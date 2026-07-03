<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidationTrackingPlanMaster extends Model
{
    use HasFactory;

    protected $table = 'validation_tracking_plan_master';

    protected $fillable = [
        'validation_tracking_id',
        'plan_master_id',
        'active',
    ];

    public function validationTracking()
    {
        return $this->belongsTo(ValidationTracking::class, 'validation_tracking_id');
    }

    public function planMaster()
    {
        return $this->belongsTo(PlanMaster::class, 'plan_master_id');
    }
}
