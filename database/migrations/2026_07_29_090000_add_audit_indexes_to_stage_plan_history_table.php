<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bổ sung index phục vụ trang "LỊCH SỬ THAY ĐỔI LỊCH SẢN XUẤT" (thống kê theo ngày):
     * - idx_stage_plan_version: self-join lấy phiên bản liền trước + truy vấn lịch sử theo stage_plan.
     * - idx_department_created: gom nhóm / lọc theo ngày thay đổi của từng phân xưởng.
     */
    public function up(): void
    {
        Schema::table('stage_plan_history', function (Blueprint $table) {
            if (! $this->hasIndex('idx_stage_plan_version')) {
                $table->index(['stage_plan_id', 'version'], 'idx_stage_plan_version');
            }

            if (! $this->hasIndex('idx_department_created')) {
                $table->index(['deparment_code', 'created_date'], 'idx_department_created');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stage_plan_history', function (Blueprint $table) {
            if ($this->hasIndex('idx_stage_plan_version')) {
                $table->dropIndex('idx_stage_plan_version');
            }

            if ($this->hasIndex('idx_department_created')) {
                $table->dropIndex('idx_department_created');
            }
        });
    }

    private function hasIndex(string $name): bool
    {
        return count(DB::select("SHOW INDEX FROM stage_plan_history WHERE Key_name = ?", [$name])) > 0;
    }
};
