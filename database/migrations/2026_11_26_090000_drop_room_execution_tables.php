<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 26/11/2026: bỏ 2 bảng của bản Thực Thi Sản Xuất cũ (26/09–05/10/2026). Nhận/Trả phòng ghi thẳng stage_plan
 * (RoomOccupancyService), không còn code nào đọc/ghi 2 bảng này. Giống database/sql/2026_11_26_drop_room_execution_tables.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('room_execution_batch');
        Schema::dropIfExists('room_execution_log');
    }

    public function down(): void
    {
        // Không tạo lại: bảng cũ đã bỏ hẳn, xem migration 2026_09_26_080000 / 2026_09_28_100000 / 2026_09_28_120000 trong git.
    }
};
