<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (!Schema::hasTable('quota_maintenance_history')) {
    Schema::create('quota_maintenance_history', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('category_id')->nullable();
        $table->string('inst_id')->nullable();
        $table->string('parent_eqp_id')->nullable();
        $table->string('inst_name')->nullable();
        $table->string('Eqp_name')->nullable();
        $table->string('exe_time')->nullable();
        $table->string('Inst_sch_type')->nullable();
        $table->string('block')->nullable();
        $table->boolean('is_HVAC')->nullable();
        $table->string('deparment_code')->nullable();
        $table->boolean('active')->nullable();
        $table->string('created_by')->nullable();
        $table->timestamp('created_time')->nullable();
        $table->timestamps();
    });
    echo "Table quota_maintenance_history created successfully.\n";
} else {
    echo "Table quota_maintenance_history already exists.\n";
}

if (!Schema::hasTable('quota_maintenance_rooms_history')) {
    Schema::create('quota_maintenance_rooms_history', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('history_id');
        $table->unsignedBigInteger('quota_maintenance_id');
        $table->unsignedBigInteger('room_id');
        $table->timestamps();
    });
    echo "Table quota_maintenance_rooms_history created successfully.\n";
} else {
    echo "Table quota_maintenance_rooms_history already exists.\n";
}

