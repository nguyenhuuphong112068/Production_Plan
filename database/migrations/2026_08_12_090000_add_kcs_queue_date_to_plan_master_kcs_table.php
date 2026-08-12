<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm mốc "Ngày chờ KCS theo đúng thứ tự lô": ngày lô được đưa vào hàng chờ KCS
     * đúng thứ tự lô. Đây là một mốc quyết định "ngày đủ điều kiện" như các mốc còn lại
     * (xem PlanMasterKcs::MILESTONES), nên phải là cột riêng chứ không suy ra từ cột khác.
     */
    public function up(): void
    {
        Schema::table('plan_master_KCS', function (Blueprint $table) {
            $table->date('kcs_queue_date')->nullable()->after('opv_pvr_approval_date');
        });
    }

    public function down(): void
    {
        Schema::table('plan_master_KCS', function (Blueprint $table) {
            $table->dropColumn('kcs_queue_date');
        });
    }
};
