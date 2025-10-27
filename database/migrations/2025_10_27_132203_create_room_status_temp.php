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
        Schema::create('room_status_temp', function (Blueprint $table) {
            $table->id(); // bigint unsigned, auto increment
            $table->unsignedBigInteger('room_id');
            $table->unsignedTinyInteger('status')->nullable();
            $table->string('in_production', 255)->nullable();
            $table->dateTime('start')->nullable();
            $table->dateTime('end')->nullable();
            $table->tinyInteger('step')->nullable();
            $table->string('notification', 255)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_status_temp');
    }
};
