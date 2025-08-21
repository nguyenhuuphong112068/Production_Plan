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
        Schema::create('schedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->string('plan_stage_code');
            $table->text('title');
            $table->dateTime('start');
            $table->dateTime('end');

            $table->string('note', 255)->nullable();

            $table->string('employee', 255);
            $table->double('duration');

            $table->boolean ('finished')->default(0);
            $table->boolean ('active')->default(true);
            $table->timestamps(); // created_at & updated_at
            $table->string('prepare_by',100); 
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule');
    }
};
