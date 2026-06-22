<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomLink extends Model
{
    use HasFactory;

    protected $table = 'room_links';

    protected $fillable = [
        'source_room_id',
        'target_room_id',
        'active',
    ];

    public function sourceRoom()
    {
        return $this->belongsTo(Room::class, 'source_room_id');
    }

    public function targetRoom()
    {
        return $this->belongsTo(Room::class, 'target_room_id');
    }
}
