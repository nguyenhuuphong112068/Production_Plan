<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationTrackingDetail extends Model
{
    use HasFactory;

    protected $table = 'publication_tracking_detail';

    protected $fillable = [
        'period_id',
        'category_type',
        'category_id',
        'code',
        'process_code',
        'product_name',
        'batch_size',
        'dosage_name',
        'market',
        'specification',
        'pharmacist_id',
        'pharmacist_name',
        'decision',
        'due_date',
        'completed_date',
        'comment',
        'pharmacist_opinion',
        'decision_by',
        'decision_at',
        'result_by',
        'result_at',
        'opinion_by',
        'opinion_at',
        'sort_order',
        'updated_by',
    ];

    protected $casts = [
        'decision' => 'boolean',
        'due_date' => 'date',
        'completed_date' => 'date',
        'decision_at' => 'datetime',
        'result_at' => 'datetime',
        'opinion_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(PublicationTrackingPeriod::class, 'period_id');
    }

    public function taskItems()
    {
        return $this->hasMany(PublicationTrackingTaskItem::class, 'detail_id');
    }
}
