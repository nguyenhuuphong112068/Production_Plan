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
        Schema::create('room_status_notification', function (Blueprint $table) {
            $table->id(); 
            $table->string('notification', 255)->nullable();
            $table->tinyInteger('group_code')->nullable();
            $table->string('deparment_code', 55);
            $table->dateTime('durability');
            
            $table->string('created_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_status_notification');
    }
};
