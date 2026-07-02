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
        Schema::create('validation_tracking_plan_master', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('validation_tracking_id');
            $table->unsignedBigInteger('plan_master_id');
            $table->timestamps();

            $table->foreign('validation_tracking_id', 'fk_vt_pm_vt_id')->references('id')->on('validation_tracking')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_tracking_plan_master');
    }
};
