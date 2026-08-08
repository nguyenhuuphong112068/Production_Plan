<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationTrackingTaskHistory extends Model
{
    use HasFactory;

    protected $table = 'publication_tracking_task_history';

    /** Chỉ có created_at, không có updated_at: đây là bảng ghi vết, không sửa lại */
    public const UPDATED_AT = null;

    protected $fillable = [
        'task_id',
        'period_id',
        'action',
        'old_content',
        'new_content',
        'detail_id',
        'detail_code',
        'affected_count',
        'changed_by',
    ];

    /** Nhãn tiếng Việt của hành động, dùng khi hiển thị lịch sử */
    public function getActionLabelAttribute(): string
    {
        return [
            'create' => 'Tạo mới',
            'update' => 'Sửa nội dung',
            'detach' => 'Gỡ khỏi mã',
            'delete' => 'Xoá nội dung',
            'carry' => 'Chuyển kỳ sau',
        ][$this->action] ?? $this->action;
    }
}
