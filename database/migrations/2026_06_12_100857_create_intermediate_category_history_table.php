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
        Schema::create('intermediate_category_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('intermediate_code', 50)->nullable();
            $table->unsignedBigInteger('product_name_id')->nullable();
            $table->integer('batch_size')->nullable();
            $table->string('unit_batch_size', 50)->nullable();
            $table->decimal('batch_qty', 15, 2)->nullable();
            $table->string('unit_batch_qty', 50)->nullable();
            $table->unsignedBigInteger('dosage_id')->nullable();
            
            $table->boolean('weight_1')->default(0);
            $table->boolean('weight_2')->default(0);
            $table->boolean('prepering')->default(0);
            $table->boolean('blending')->default(0);
            $table->boolean('forming')->default(0);
            $table->boolean('coating')->default(0);
            
            $table->string('quarantine_total', 50)->nullable();
            $table->string('quarantine_weight', 50)->nullable();
            $table->string('quarantine_preparing', 50)->nullable();
            $table->string('quarantine_blending', 50)->nullable();
            $table->string('quarantine_forming', 50)->nullable();
            $table->string('quarantine_coating', 50)->nullable();
            $table->string('quarantine_time_unit', 50)->nullable();
            
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
        Schema::dropIfExists('intermediate_category_history');
    }
};
