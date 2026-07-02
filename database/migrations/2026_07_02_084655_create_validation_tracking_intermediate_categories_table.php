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
        Schema::create('validation_tracking_intermediate_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('validation_tracking_id');
            $table->unsignedSmallInteger('intermediate_category_id');
            $table->tinyInteger('num_of_tracking_batch')->default(1);
            $table->tinyInteger('num_of_finished_batch')->default(0);
            $table->text('note')->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('validation_tracking_id', 'fk_vt_ic_vt_id')->references('id')->on('validation_tracking')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_tracking_intermediate_category');
    }
};
