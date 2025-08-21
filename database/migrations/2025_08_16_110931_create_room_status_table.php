<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     **/
    public function up(): void
    {
        Schema::create('room_status', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('room_id'); 
            $table->unsignedBigInteger('stage_plan_id');
            $table->dateTime('start'); 
            $table->dateTime('end'); 
            $table->timestamps(); // created_at + updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_status');
    }
};
