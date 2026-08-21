<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bỏ "Người Lấy" / "Ngày Lấy": chỉ giữ lại cờ xác nhận (sampled_confirmed), không
     * cần ghi ai lấy / lấy lúc nào. Việc xác nhận chỉ cho phép khi lô đã có Số PO
     * (xem PackagingNotificationController::save).
     */
    public function up(): void
    {
        Schema::table('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->dropColumn(['sampled_by', 'sampled_date']);
        });
    }

    public function down(): void
    {
        Schema::table('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->string('sampled_by', 100)->nullable()->after('sampled_confirmed');
            $table->date('sampled_date')->nullable()->after('sampled_by');
        });
    }
};
