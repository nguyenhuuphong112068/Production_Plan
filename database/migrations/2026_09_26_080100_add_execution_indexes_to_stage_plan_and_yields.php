<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index cho trang "Thực Thi Sản Xuất" (tùy chọn, chỉ để nhanh hơn):
 * - stage_plan(resourceId, actual_start): tìm lô thực tế mới nhất / lô kế tiếp của từng phòng (hiện phải quét cả bảng).
 * - yields(stage_plan_id): tổng sản lượng đã xác nhận của lô (bảng yields chưa có index nào ngoài khóa chính).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasIndex('stage_plan', 'idx_sp_resource_actual')) {
            Schema::table('stage_plan', function (Blueprint $table) {
                $table->index(['resourceId', 'actual_start'], 'idx_sp_resource_actual');
            });
        }

        if (!Schema::hasIndex('yields', 'idx_yields_stage_plan')) {
            Schema::table('yields', function (Blueprint $table) {
                $table->index('stage_plan_id', 'idx_yields_stage_plan');
            });
        }
    }

    public function down(): void
    {
        Schema::table('stage_plan', function (Blueprint $table) {
            $table->dropIndex('idx_sp_resource_actual');
        });

        Schema::table('yields', function (Blueprint $table) {
            $table->dropIndex('idx_yields_stage_plan');
        });
    }
};
