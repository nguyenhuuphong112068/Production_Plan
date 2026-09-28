<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nhóm nhiều lô chạy chung 1 lần mở phòng (Cân NL): mỗi lô kết thúc riêng, vệ sinh chung cho cả nhóm
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_execution_batch', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('stage_plan_id');
            $table->unsignedBigInteger('open_log_id');   // dòng room_execution_log lúc mở phòng
            $table->dateTime('started_at');              // BĐSX
            $table->dateTime('ended_at')->nullable();    // lúc bấm kết thúc lô
            $table->string('ended_by', 255)->nullable();
            $table->dateTime('cleaned_at')->nullable();  // nhóm đã vệ sinh xong (kiểm tra đạt) / đóng
            $table->dateTime('cancelled_at')->nullable(); // hủy thao tác mở phòng
            $table->string('created_by', 255);
            $table->timestamps();

            $table->index(['room_id', 'cleaned_at']);
            $table->index('stage_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_execution_batch');
    }
};
