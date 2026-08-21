<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Đánh dấu dòng thông báo đóng gói do người dùng tự tạo thêm.
     *
     * Dòng sinh tự động lúc gửi kế hoạch (theo quy tắc ở PlanMasterInforParkaging::eligibleQuery)
     * có is_manual = 0. Dòng người dùng bấm nút "Tạo Thông Báo Khác" có is_manual = 1, nhờ đó
     * lưới vẫn hiển thị được lô nằm ngoài quy tắc và chỉ những dòng này mới cho phép gỡ ra.
     */
    public function up(): void
    {
        Schema::table('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->boolean('is_manual')->default(0)->after('plan_list_id');
            $table->string('created_by', 100)->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->dropColumn(['is_manual', 'created_by']);
        });
    }
};
