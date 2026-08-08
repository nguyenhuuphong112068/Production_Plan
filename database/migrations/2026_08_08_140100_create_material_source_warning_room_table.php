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
        // Chi tiết ma trận: thiết bị được phép thực hiện ở từng công đoạn
        // của 1 dòng khai báo trong material_source_warning.
        Schema::create('material_source_warning_room', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warning_id');
            $table->unsignedSmallInteger('stage_code'); // stages.code
            $table->string('room_code', 20);            // room.code
            $table->timestamps();

            $table->unique(['warning_id', 'stage_code', 'room_code'], 'msw_room_unique');

            $table->foreign('warning_id', 'msw_room_warning_fk')
                ->references('id')
                ->on('material_source_warning')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_source_warning_room');
    }
};
