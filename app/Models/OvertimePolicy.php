<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimePolicy extends Model
{
    use HasFactory;

    protected $table = 'overtime_policies';

    protected $fillable = [
        'production_code',
        'group_id',
        'max_personnel_per_day',
        'max_hours_per_day',
        'active',
        'created_by',
    ];
}
