<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('assignment_suggestions', function (Blueprint $table) {
            $table->id();
            $table->date('target_date');
            $table->string('room_id')->nullable();
            $table->string('work_location')->nullable();
            $table->string('deparment_code')->nullable();
            $table->string('stage_groups_code')->nullable();
            $table->string('shift')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('personnel_data')->nullable(); // Chứa list personnel_id và notification
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_suggestions');
    }
};
