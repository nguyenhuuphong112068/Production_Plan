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
        Schema::create('annual_plan_monthly_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_plan_product_id')->constrained('annual_plan_products')->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->integer('planned_batches')->nullable();
            $table->integer('planned_quantity')->nullable();
            $table->integer('actual_issue')->nullable();
            $table->integer('inventory_wip')->nullable();
            $table->integer('inventory_fg')->nullable();
            $table->integer('safety_stock')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_plan_monthly_data');
    }
};
