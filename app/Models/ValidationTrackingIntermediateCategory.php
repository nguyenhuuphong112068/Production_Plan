<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidationTrackingIntermediateCategory extends Model
{
    use HasFactory;

    protected $table = 'validation_tracking_intermediate_category';

    protected $fillable = [
        'validation_tracking_id',
        'intermediate_category_id',
        'num_of_tracking_batch',
        'num_of_finished_batch',
        'note',
        'updated_by',
    ];

    public function validationTracking()
    {
        return $this->belongsTo(ValidationTracking::class, 'validation_tracking_id');
    }

    public function intermediateCategory()
    {
        return $this->belongsTo(IntermediateCategory::class, 'intermediate_category_id');
    }
}
