<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dược sĩ phụ trách của từng mã BTP / TP, trỏ tới user_management.id.
        // Bảng history phải có cùng cột vì logHistory() copy nguyên dòng sang.
        foreach (['intermediate_category', 'finished_product_category'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('pharmacist_id')->nullable()->after('deparment_code');
            });
        }

        foreach (['intermediate_category_history', 'finished_product_category_history'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('pharmacist_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([
            'intermediate_category',
            'finished_product_category',
            'intermediate_category_history',
            'finished_product_category_history',
        ] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('pharmacist_id');
            });
        }
    }
};
