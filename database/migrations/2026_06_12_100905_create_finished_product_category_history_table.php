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
        Schema::create('finished_product_category_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('process_code', 100)->nullable();
            $table->string('finished_product_code', 50)->nullable();
            $table->string('intermediate_code', 50)->nullable();
            $table->unsignedBigInteger('product_name_id')->nullable();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->unsignedBigInteger('specification_id')->nullable();
            $table->decimal('batch_qty', 15, 2)->nullable();
            $table->string('unit_batch_qty', 50)->nullable();
            $table->string('primary_parkaging', 255)->nullable();
            $table->string('secondary_parkaging', 255)->nullable();
            
            $table->string('deparment_code', 50)->nullable();
            $table->boolean('cancel')->default(0);
            $table->boolean('IsHypothesis')->default(0);
            $table->boolean('active')->default(1);
            $table->string('prepared_by', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finished_product_category_history');
    }
};
