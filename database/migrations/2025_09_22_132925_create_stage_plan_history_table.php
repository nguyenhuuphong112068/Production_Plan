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

        Schema::create('stage_plan_history', function (Blueprint $table) {

            $table->id();
            $table->unsignedInteger('stage_plan_id');
            $table->unsignedSmallInteger('version');

            $table->unsignedInteger('resourceId')->nullable();
            $table->dateTime('start')->nullable();
            $table->dateTime('end')->nullable();
            $table->string('deparment_code', 5);
            $table->string('schedualed_by',100)->nullable();
            $table->dateTime('schedualed_at')->nullable();
            $table->string('type_of_change',50)->nullable();
            

            
 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_plan_history');
    }
};
