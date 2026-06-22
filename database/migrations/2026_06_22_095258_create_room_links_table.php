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
        Schema::create('room_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_room_id');
            $table->unsignedBigInteger('target_room_id');
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('source_room_id')->references('id')->on('room')->onDelete('cascade');
            $table->foreign('target_room_id')->references('id')->on('room')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_links');
    }
};
