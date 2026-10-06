<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plugin Kiểm soát tồn BTP khi sắp lịch tự động.
 *
 * 1. wip_control_settings: giới hạn Max tồn (đơn vị liều) mỗi phân xưởng nhập ở
 *    modal Sắp lịch tự động. Độc lập với wip_stock_limits của trang WipCoverage:
 *    cùng mục đích nhưng hai dữ liệu không liên quan nhau.
 * 2. wip_control_runs: nhật ký mỗi lần plugin chạy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wip_control_settings', function (Blueprint $table) {
            $table->id();
            $table->string('production_code', 10)->unique()->comment('Mã phân xưởng: PXV1, PXV2...');
            $table->decimal('max_dh_dvl', 18, 2)->nullable()->comment('Max tồn chờ Định hình (đơn vị liều), null = không giới hạn');
            $table->decimal('max_bp_dvl', 18, 2)->nullable()->comment('Max tồn chờ Bao phim (đơn vị liều), null = không giới hạn');
            $table->decimal('max_dg_dvl', 18, 2)->nullable()->comment('Max tồn chờ Đóng gói (đơn vị liều), null = không giới hạn');
            $table->unsignedTinyInteger('max_iterations')->default(5);
            $table->boolean('lock_validation')->default(0)->comment('1 = không lùi lô thẩm định');
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('wip_control_runs', function (Blueprint $table) {
            $table->id();
            $table->string('production_code', 10)->index();
            $table->string('status', 20)->comment('ok, infeasible, timeout, max_iterations, skipped, error');
            $table->unsignedTinyInteger('iterations')->default(0);
            $table->unsignedInteger('delayed_lots')->default(0);
            $table->string('undo_code', 60)->nullable()->comment('bkc_code điểm hoàn tác trong stage_plan_bkc');
            $table->longText('limits')->nullable()->comment('JSON: Max đã dùng');
            $table->longText('report')->nullable()->comment('JSON: báo cáo đầy đủ');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_control_runs');
        Schema::dropIfExists('wip_control_settings');
    }
};
