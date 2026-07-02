<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntermediateCategory extends Model
{
    use HasFactory;

    protected $table = 'intermediate_category';

    protected $fillable = [
        'intermediate_code',
        'product_name_id',
        'batch_size',
        'unit_batch_size',
        'batch_qty',
        'unit_batch_qty',
        'dosage_id',
        'weight_1',
        'weight_2',
        'prepering',
        'blending',
        'forming',
        'coating',
        'quarantine_total',
        'quarantine_weight',
        'quarantine_preparing',
        'quarantine_blending',
        'quarantine_forming',
        'quarantine_coating',
        'quarantine_time_unit',
        'deparment_code',
        'cancel',
        'IsHypothesis',
        'active',
        'prepared_by',
    ];

    public function productName()
    {
        return $this->belongsTo(\App\Models\masterData\ProductName\ProductNameModel::class, 'product_name_id');
    }

    public function dosage()
    {
        return $this->belongsTo(Dosage::class, 'dosage_id');
    }

    public function validationTrackings()
    {
        return $this->hasMany(\App\Models\ValidationTrackingIntermediateCategory::class, 'intermediate_category_id', 'id');
    }
}
