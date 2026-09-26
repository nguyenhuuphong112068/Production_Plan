<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nhật ký trạng thái phòng của trang "Thực Thi Sản Xuất".
 * Mỗi dòng là 1 khoảng trạng thái của phòng; dòng có ended_at NULL (và chưa hủy) là trạng thái hiện tại.
 *
 * Không dùng room_status.status vì trang "Trạng Thái Phòng" đã dùng mã 0-4 với nghĩa khác,
 * và không ghi actual_start vào stage_plan lúc mới bắt đầu vì lịch Gantt ẩn lô có actual_start mà finished = 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_execution_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->string('deparment_code', 5)->nullable();
            $table->unsignedTinyInteger('state')
                ->comment('1 Phòng sạch, 2 Đang SX, 3 Cần VS, 4 Đang VS, 6 Tạm dừng SX (5 Cần VS lại = phòng sạch quá hạn, chỉ tính khi hiển thị)');
            $table->unsignedBigInteger('stage_plan_id')->nullable();
            $table->unsignedBigInteger('yield_id')->nullable()->comment('Dòng yields sinh ra khi đóng khoảng Đang SX');
            $table->unsignedBigInteger('room_status_id')->nullable()->comment('Dòng room_status (Báo cáo ngày) sinh ra khi đóng khoảng này');
            $table->string('cleaning_level', 10)->nullable()->comment('VS-I, VS-II, VS-LAI');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->dateTime('expired_at')->nullable()->comment('Hạn phòng sạch');
            $table->string('note', 255)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->string('ended_by', 100)->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancelled_by', 100)->nullable();
            $table->timestamps();

            $table->index(['room_id', 'ended_at'], 'idx_rel_room_open');
            $table->index('stage_plan_id', 'idx_rel_stage_plan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_execution_log');
    }
};
