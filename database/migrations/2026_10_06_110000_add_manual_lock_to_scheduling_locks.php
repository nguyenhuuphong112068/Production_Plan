<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Khóa thủ công "đang sắp lịch thủ công" theo phân xưởng: người sắp lịch tự bật ở sidebar lịch chờ sắp (Gantt) để chặn
 * Nhận phòng / Trả phòng ở trang Thực Thi / Ghi Nhận Sản Xuất (tránh tịnh tuyến lịch xung đột). Tách khỏi is_scheduling
 * (khóa sắp lịch tự động, hết hạn 10 phút) để khóa thủ công không chặn chính việc sắp lịch tự động.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduling_locks', function (Blueprint $table) {
            $table->boolean('manual_locked')->default(0)->after('started_at');
            $table->string('manual_by')->nullable()->after('manual_locked');
            $table->dateTime('manual_at')->nullable()->after('manual_by');
        });
    }

    public function down(): void
    {
        Schema::table('scheduling_locks', function (Blueprint $table) {
            $table->dropColumn(['manual_locked', 'manual_by', 'manual_at']);
        });
    }
};
