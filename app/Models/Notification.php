<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    
    // Tắt timestamps tự động vì bảng chỉ có created_at
    public $timestamps = false;

    protected $fillable = [
        'sender_id',
        'activity_type',
        'message',
        'url',
        'reference_id',
        'created_at',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id');
    }
}
