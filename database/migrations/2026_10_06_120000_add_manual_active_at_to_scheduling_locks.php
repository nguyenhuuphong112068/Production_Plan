<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lần cuối người sắp lịch còn thao tác sắp lịch thủ công khi đang Khóa TTSX: không còn thao tác quá
 * SchedulingLock::MANUAL_IDLE_MINUTES thì khóa tự mở (phòng quên tắt).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduling_locks', function (Blueprint $table) {
            $table->dateTime('manual_active_at')->nullable()->after('manual_at');
        });
    }

    public function down(): void
    {
        Schema::table('scheduling_locks', function (Blueprint $table) {
            $table->dropColumn('manual_active_at');
        });
    }
};
