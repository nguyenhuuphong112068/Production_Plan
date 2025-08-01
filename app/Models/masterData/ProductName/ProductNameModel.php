<?php

namespace App\Models\masterData\ProductName;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductNameModel extends Model
{
    use HasFactory;

    protected $table = 'product_name';

    protected static function newFactory()
    {
        return \Database\Factories\ProductNameModelFactory::new();
    }
}
