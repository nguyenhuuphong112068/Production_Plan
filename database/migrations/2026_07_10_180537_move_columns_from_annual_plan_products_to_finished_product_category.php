<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Thêm cột vào bảng finished_product_category
        Schema::table('finished_product_category', function (Blueprint $table) {
            $table->date('registration_expiry')->nullable();
            $table->string('classification')->nullable();
            $table->string('customer_type')->nullable();
            $table->integer('shelf_life')->nullable();
            $table->integer('packaging_spec')->nullable();
            $table->integer('avg_sales_box')->nullable();
            $table->integer('avg_sales_pill')->nullable();
        });

        // 2. Chuyển dữ liệu từ annual_plan_products sang finished_product_category
        // Do một FPC có thể có nhiều APP (từ nhiều năm), ta ưu tiên lấy dữ liệu mới nhất (dựa theo id)
        $latestProducts = DB::table('annual_plan_products')
            ->whereNotNull('finished_product_category_id')
            ->orderBy('id', 'desc')
            ->get()
            ->unique('finished_product_category_id');

        foreach ($latestProducts as $product) {
            DB::table('finished_product_category')
                ->where('id', $product->finished_product_category_id)
                ->update([
                    'registration_expiry' => $product->registration_expiry,
                    'classification'      => $product->classification,
                    'customer_type'       => $product->customer_type,
                    'shelf_life'          => $product->shelf_life,
                    'packaging_spec'      => $product->packaging_spec,
                    'avg_sales_box'       => $product->avg_sales_box,
                    'avg_sales_pill'      => $product->avg_sales_pill,
                ]);
        }

        // 3. Xóa các cột khỏi annual_plan_products
        Schema::table('annual_plan_products', function (Blueprint $table) {
            $table->dropColumn([
                'registration_expiry',
                'classification',
                'customer_type',
                'shelf_life',
                'packaging_spec',
                'avg_sales_box',
                'avg_sales_pill'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finished_product_category', function (Blueprint $table) {
            //
        });
    }
};
