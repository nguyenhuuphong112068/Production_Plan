<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thông báo đóng gói của từng lô sản xuất (1 lô = 1 dòng), gắn với plan_master.
     *
     * Dòng được sinh tự động khi người dùng bấm "Gửi Kế Hoạch Tháng" (xem
     * ProductionPlanController::createPackagingNotifications), lúc đó chỉ có
     * plan_master_id và plan_list_id; các cột còn lại để người dùng nhập sau
     * ở trang Thông Báo Đóng Gói.
     */
    public function up(): void
    {
        Schema::create('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->id();

            // Cùng kiểu với plan_master.id (bigint unsigned) để đặt được khoá ngoại
            $table->unsignedBigInteger('plan_master_id');

            // Cố ý để int unsigned giống hệt plan_master.plan_list_id: plan_list.id là
            // bigint nên KHÔNG đặt được khoá ngoại ở đây, nhưng dùng sai kiểu thì join
            // với plan_master lại phải ép kiểu. Toàn hệ thống đang theo int unsigned.
            $table->unsignedInteger('plan_list_id');

            // --- Người dùng nhập ---
            $table->string('PO_no', 50)->nullable();                    // Số PO
            $table->string('Sampling_specifications', 100)->nullable(); // Quy cách lấy mẫu
            $table->string('Sampling_times', 100)->nullable();          // Số lần lấy mẫu
            $table->decimal('Sampling_amount', 15, 3)->nullable();       // Số lượng lấy mẫu
            $table->string('sampling_uint', 50)->nullable();            // Đơn vị lấy mẫu
            $table->string('Reason', 255)->nullable();                  // Lý do

            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('plan_master_id', 'fk_pm_infor_parkaging_plan_master_id')
                ->references('id')->on('plan_master')->onDelete('cascade');

            // Mỗi lô chỉ có một thông báo đóng gói: chặn luôn việc gửi lại kế hoạch
            // sinh ra dòng trùng (phần insert dùng insertOrIgnore dựa trên ràng buộc này).
            $table->unique('plan_master_id', 'pm_infor_parkaging_plan_master');
            $table->index('plan_list_id', 'pm_infor_parkaging_plan_list');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_master_infor_parkaging');
    }
};
