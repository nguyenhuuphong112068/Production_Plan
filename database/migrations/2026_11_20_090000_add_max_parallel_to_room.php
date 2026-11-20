<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phòng đôi gói đầu (20/11/2026): room.max_parallel = số lô sản xuất giữ phòng cùng lúc ở trang Thực Thi Sản Xuất.
 * 1 = bình thường; 2 = được Nhận lô gói đầu (lô 2 cùng mã BTP) khi phòng đang Bận với 1 lô.
 * Đặt sẵn 2 cho S3, S7+S8, S10+S11 (Pha Chế PXV1).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['room', 'room_history'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'max_parallel')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->tinyInteger('max_parallel')->default(1)->after('only_maintenance');
                });
            }
        }

        DB::table('room')->whereIn('code', ['S3', 'S7+S8', 'S10+S11'])->update(['max_parallel' => 2]);
    }

    public function down(): void
    {
        foreach (['room', 'room_history'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'max_parallel')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('max_parallel');
                });
            }
        }
    }
};
