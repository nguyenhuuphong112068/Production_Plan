<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinishedProductCategory extends Model
{
    use HasFactory;
    protected $table = 'finished_product_category';
    protected $guarded = [];

    // Tên sản phẩm nằm trong bảng product_name, liên kết qua product_name_id
    public function productName()
    {
        return $this->belongsTo(\App\Models\masterData\ProductName\ProductName::class, 'product_name_id');
    }
}
