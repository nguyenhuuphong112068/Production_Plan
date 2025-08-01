<?php

namespace Database\Seeders;

use App\Models\masterData\ProductName\ProductNameModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductNameModel::factory()->count(200)->create();
    }
}
