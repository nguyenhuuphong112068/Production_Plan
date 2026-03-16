<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'group_id',
        'sender_id',
        'message',
        'file_path',
        'file_name',
        'file_type',
    ];

    public function group()
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
