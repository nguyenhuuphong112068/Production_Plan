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
        // Theo dõi hồ sơ KCS của từng lô sản xuất (1 lô = 1 dòng), gắn với plan_master.
        //
        // Các cột nhóm "người dùng nhập" tương ứng với cột N trở đi của file theo dõi KCS.
        // Các cột nhóm "hệ thống tính" được tính lại mỗi lần lưu (xem PlanMasterKcs::derive)
        // nên luôn khớp với dữ liệu nhập, và cho phép tổng kết tỉ lệ bằng GROUP BY.
        Schema::create('plan_master_KCS', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_master_id');

            // --- Người dùng nhập ---
            $table->string('edition', 20)->nullable();                  // Ấn bản
            $table->date('record_received_date')->nullable();           // Ngày nhận hồ sơ
            $table->string('reader', 100)->nullable();                  // Người đọc
            $table->date('record_done_date')->nullable();               // Ngày đọc xong hồ sơ
            $table->unsignedBigInteger('stock_in_qty')->nullable();     // Số lượng nhập kho (viên)
            $table->date('kcs_date')->nullable();                       // Ngày KCS
            $table->string('coatp_number', 50)->nullable();             // Số phiếu COATP
            $table->date('coatp_received_date')->nullable();            // Ngày nhận COATP
            $table->string('dr_ir', 100)->nullable();                   // DR/IR
            $table->date('dr_ir_approval_date')->nullable();            // Ngày Approval DR/IR
            $table->string('oos', 100)->nullable();                     // OOS
            $table->date('oos_approval_date')->nullable();              // Ngày Approval OOS
            $table->date('dr_ir_kcq_approval_date')->nullable();        // Ngày Approval DR/IR KCQ
            $table->date('opv_pvr_approval_date')->nullable();          // Ngày Approval OPV/PVR
            $table->text('note')->nullable();

            // --- Hệ thống tính ---
            $table->date('eligible_date')->nullable();                  // Ngày đủ điều kiện = MAX các ngày hoàn tất
            $table->integer('completion_days')->nullable();             // Số ngày hoàn thành = Ngày KCS - Ngày đủ điều kiện
            $table->string('bottleneck', 50)->nullable();               // Mốc quyết định ngày đủ điều kiện
            $table->integer('kcs_pending')->nullable();                 // Số ngày làm việc chờ KCS (nghỉ Chủ nhật)
            $table->unsignedSmallInteger('kcs_year')->nullable();       // Năm KCS
            $table->unsignedTinyInteger('kcs_month')->nullable();       // Tháng KCS
            $table->string('result', 20)->nullable();                   // Đáp Ứng | Không Đáp Ứng

            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('plan_master_id', 'fk_pm_kcs_plan_master_id')
                ->references('id')->on('plan_master')->onDelete('cascade');

            $table->unique('plan_master_id', 'pm_kcs_plan_master');
            $table->index(['kcs_year', 'kcs_month'], 'pm_kcs_year_month');
            $table->index('result', 'pm_kcs_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_master_KCS');
    }
};
