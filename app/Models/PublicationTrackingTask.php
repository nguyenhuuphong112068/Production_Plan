<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationTrackingTask extends Model
{
    use HasFactory;

    protected $table = 'publication_tracking_task';

    protected $fillable = [
        'period_id',
        'content',
        'created_by',
        'updated_by',
    ];

    public function period()
    {
        return $this->belongsTo(PublicationTrackingPeriod::class, 'period_id');
    }

    public function items()
    {
        return $this->hasMany(PublicationTrackingTaskItem::class, 'task_id');
    }
}
