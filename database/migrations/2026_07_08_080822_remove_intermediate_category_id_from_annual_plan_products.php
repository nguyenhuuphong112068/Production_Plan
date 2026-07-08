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
        Schema::table('annual_plan_products', function (Blueprint $table) {
            $table->dropColumn('intermediate_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annual_plan_products', function (Blueprint $table) {
            $table->unsignedBigInteger('intermediate_category_id')->nullable();
        });
    }
};
