<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 28/11/2026: plan_master.excess_pro_return_lot = "Lô Thu Hồi".
 * Chỉ tích ở bảng chi tiết Kế hoạch tháng sau khi đã tạo lô (không có trong modal Tạo mới / Cập nhật).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plan_master', 'excess_pro_return_lot')) {
            Schema::table('plan_master', function (Blueprint $table) {
                $table->boolean('excess_pro_return_lot')->default(0)->after('additional');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('plan_master', 'excess_pro_return_lot')) {
            Schema::table('plan_master', function (Blueprint $table) {
                $table->dropColumn('excess_pro_return_lot');
            });
        }
    }
};
