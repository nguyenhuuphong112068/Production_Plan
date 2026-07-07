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
        Schema::create('annual_plan_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_plan_id')->constrained('annual_plans')->onDelete('cascade');
            $table->date('registration_expiry')->nullable();
            $table->string('classification')->nullable();
            $table->string('customer_type')->nullable();
            $table->unsignedBigInteger('intermediate_category_id')->nullable();
            $table->unsignedBigInteger('finished_product_category_id')->nullable();
            $table->integer('shelf_life')->nullable();
            $table->integer('packaging_spec')->nullable();
            $table->integer('avg_sales_box')->nullable();
            $table->integer('avg_sales_pill')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_plan_products');
    }
};
