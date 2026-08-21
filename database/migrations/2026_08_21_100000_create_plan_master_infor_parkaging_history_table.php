<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lịch sử nhập liệu Thông Báo Đóng Gói: mỗi ô thay đổi (Số PO, thông tin lấy mẫu,
     * lý do) là một dòng ghi vết, cùng cấu trúc với plan_master_KCS_history.
     *
     * Cố ý KHÔNG đặt khoá ngoại tới plan_master_infor_parkaging: dòng thêm tay bị gỡ
     * (xem PackagingNotificationController::destroy) thì vết lịch sử vẫn phải còn.
     * plan_master_id được lưu kèm để tra cứu theo lô kể cả khi dòng gốc đã bị xoá.
     */
    public function up(): void
    {
        Schema::create('plan_master_infor_parkaging_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_master_id');
            $table->unsignedBigInteger('infor_parkaging_id')->nullable();
            $table->string('action', 20);                    // create | update
            $table->string('field', 50);                     // tên cột nhập bị đổi
            $table->string('old_value', 255)->nullable();
            $table->string('new_value', 255)->nullable();
            $table->string('changed_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['plan_master_id', 'id'], 'pm_infor_parkaging_history_plan_master');
            $table->index('infor_parkaging_id', 'pm_infor_parkaging_history_parkaging');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_master_infor_parkaging_history');
    }
};
