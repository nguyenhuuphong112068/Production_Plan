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
        Schema::create('maintenance_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stage_plan_id');
            $table->unsignedBigInteger('personnel_id');
            $table->string('assigned_by')->nullable();
            $table->timestamps();

            $table->foreign('stage_plan_id')->references('id')->on('stage_plan')->onDelete('cascade');
            $table->foreign('personnel_id')->references('id')->on('personnel')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_assignments');
    }
};
