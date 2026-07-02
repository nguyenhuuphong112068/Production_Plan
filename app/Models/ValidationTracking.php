<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidationTracking extends Model
{
    use HasFactory;

    protected $table = 'validation_tracking';

    protected $fillable = [
        'MatID',
        'MaterialName',
        'purpose',
        'CC_num',
        'status',
        'note',
        'created_by',
        'approved',
        'approved_at',
        'approved_by',
    ];

    public function intermediateCategories()
    {
        return $this->hasMany(ValidationTrackingIntermediateCategory::class, 'validation_tracking_id');
    }

    public function planMasters()
    {
        return $this->hasMany(ValidationTrackingPlanMaster::class, 'validation_tracking_id');
    }
}
