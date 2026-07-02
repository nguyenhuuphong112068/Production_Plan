<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanMaster extends Model
{
    use HasFactory;

    protected $table = 'plan_master';
    public $timestamps = false;

    public function getFinishedProductCodeAttribute()
    {
        return \Illuminate\Support\Facades\DB::table('finished_product_category')
            ->where('id', $this->product_caterogy_id)
            ->value('finished_product_code');
    }
}
