<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $table = 'room';

    // Disabling timestamps since we only need this model for reading related data in RoomLink
    public $timestamps = false;
    
    protected $guarded = [];
}
