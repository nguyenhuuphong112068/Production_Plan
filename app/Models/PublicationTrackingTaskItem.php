<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationTrackingTaskItem extends Model
{
    use HasFactory;

    protected $table = 'publication_tracking_task_item';

    protected $fillable = [
        'task_id',
        'detail_id',
        'moved_to_period_id',
        'moved_to_item_id',
        'moved_at',
        'moved_by',
        'updated_by',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(PublicationTrackingTask::class, 'task_id');
    }

    /** Kỳ mà nội dung này đã được chuyển sang, null nếu chưa chuyển */
    public function movedToPeriod()
    {
        return $this->belongsTo(PublicationTrackingPeriod::class, 'moved_to_period_id');
    }

    public function detail()
    {
        return $this->belongsTo(PublicationTrackingDetail::class, 'detail_id');
    }
}
