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
        Schema::create('stage_plan', function (Blueprint $table) {
            $table->id();
            $table->string('plan_stage_code');
            $table->text('title');
            $table->string('duration');
            $table->string('resource_Id_Group');
            $table->date('expertedDate');
            $table->boolean('is_scheduled');
            $table->tinyInteger('stage_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_plan');
    }
};
