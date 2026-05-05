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
        Schema::create('employee_groups', function (Blueprint $table) {
            $table->id();
            $table->integer('employees_id');
            $table->integer('group_id');
            $table->timestamp('created_at')->useCurrent();
            $table->string('created_by')->nullable();
        });

        Schema::create('employee_rooms', function (Blueprint $table) {
            $table->id();
            $table->integer('employees_id');
            $table->integer('room_id');
            $table->timestamp('created_at')->useCurrent();
            $table->string('created_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_groups');
        Schema::dropIfExists('employee_rooms');
    }
};
