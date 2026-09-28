<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kiểm tra vệ sinh (trạng thái Chờ Kiểm Tra): ghi trên dòng Chờ kiểm tra
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_execution_log', function (Blueprint $table) {
            $table->string('checked_by', 255)->nullable()->after('cancelled_by');
            $table->dateTime('checked_at')->nullable()->after('checked_by');
            $table->tinyInteger('check_result')->nullable()->after('checked_at'); // 1 Đạt, 0 Không đạt
        });
    }

    public function down(): void
    {
        Schema::table('room_execution_log', function (Blueprint $table) {
            $table->dropColumn(['checked_by', 'checked_at', 'check_result']);
        });
    }
};
