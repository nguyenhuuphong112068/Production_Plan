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
        Schema::create('quota_maintenance_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quota_maintenance_id');
            $table->unsignedInteger('room_id');
            $table->timestamps();
            
            $table->index('quota_maintenance_id');
            $table->index('room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quota_maintenance_rooms');
    }
};
