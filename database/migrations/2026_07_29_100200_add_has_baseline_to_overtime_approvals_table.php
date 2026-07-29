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
        Schema::table('overtime_approvals', function (Blueprint $table) {
            // Các bản ghi duyệt cũ (trước khi có tính năng so mốc giờ) không lưu approved_hours.
            // Cờ này = 0 nghĩa là "không có mốc để so" → giữ nguyên hành vi cũ, không bắt duyệt lại.
            // Mọi lần duyệt từ nay về sau đều set = 1.
            $table->boolean('has_baseline')->default(0)->after('groups_detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_approvals', function (Blueprint $table) {
            $table->dropColumn('has_baseline');
        });
    }
};
