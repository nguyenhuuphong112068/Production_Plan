<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kiểm tra hasColumn để chạy lại migration không lỗi "Duplicate column"
        if (!Schema::hasColumn('plan_master', 'additional')) {
            Schema::table('plan_master', function (Blueprint $table) {
                // Lô phát sinh so với kế hoạch năm dự kiến: mặc định 0 vì
                // lô được lập theo kế hoạch dự kiến mới là trường hợp thông thường.
                $table->boolean('additional')->default(0)->after('is_validation_tracking');
            });
        }
    }

    public function down(): void
    {
        Schema::table('plan_master', function (Blueprint $table) {
            $table->dropColumn('additional');
        });
    }
};
