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
        Schema::create('finished_product_mold', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('finished_product_category_id');
            $table->unsignedBigInteger('blister_mold_id');
            $table->string('created_by', 255)->nullable();
            $table->timestamps();

            $table->foreign('finished_product_category_id')->references('id')->on('finished_product_category')->onDelete('cascade');
            $table->foreign('blister_mold_id')->references('id')->on('blister_mold')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finished_product_mold');
    }
};
