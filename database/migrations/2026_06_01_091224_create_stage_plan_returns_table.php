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
        Schema::create('stage_plan_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stage_plan_id')->index();
            $table->string('returned_by')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('previous_actual_start')->nullable();
            $table->timestamp('previous_actual_end')->nullable();
            $table->timestamp('previous_actual_start_clearning')->nullable();
            $table->timestamp('previous_actual_end_clearning')->nullable();
            $table->float('previous_yields', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_plan_returns');
    }
};
