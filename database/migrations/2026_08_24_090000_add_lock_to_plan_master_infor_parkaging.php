<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Khoá/mở khoá Mẫu Đóng Gói Sơ Cấp, Thứ Cấp và Lý Do của một lô.
     *
     * ever_locked là cờ RIÊNG, không suy ra được từ is_locked: is_locked quay lại 0 mỗi
     * lần mở khoá, còn ever_locked phải giữ nguyên 1 vĩnh viễn sau lần khoá đầu tiên -
     * dùng để quyết định thời điểm bắt đầu ghi lịch sử nhập liệu (xem
     * PackagingNotificationController::recordHistory).
     */
    public function up(): void
    {
        Schema::table('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->boolean('is_locked')->default(0)->after('sampled_confirmed');
            $table->boolean('ever_locked')->default(0)->after('is_locked');
            $table->string('locked_by', 100)->nullable()->after('ever_locked');
            $table->timestamp('locked_at')->nullable()->after('locked_by');
        });
    }

    public function down(): void
    {
        Schema::table('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->dropColumn(['is_locked', 'ever_locked', 'locked_by', 'locked_at']);
        });
    }
};
