<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGroup extends Model
{
    use HasFactory;

    protected $table = 'chat_groups';

    protected $fillable = [
        'name',
        'type',
        'created_by',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, 'chat_group_members', 'group_id', 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'group_id');
    }
}
