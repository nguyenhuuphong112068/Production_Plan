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
            // Tổng giờ / số người tăng ca tại thời điểm được duyệt.
            // Dùng để so sánh: nếu lịch bị sửa làm tổng giờ tăng ca lớn hơn
            // mốc đã duyệt thì phải duyệt lại mới được in / xuất Excel.
            $table->decimal('approved_hours', 8, 2)->default(0)->after('group_code');
            $table->integer('approved_persons')->default(0)->after('approved_hours');
            $table->text('groups_detail')->nullable()->after('approved_persons');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_approvals', function (Blueprint $table) {
            $table->dropColumn(['approved_hours', 'approved_persons', 'groups_detail']);
        });
    }
};
