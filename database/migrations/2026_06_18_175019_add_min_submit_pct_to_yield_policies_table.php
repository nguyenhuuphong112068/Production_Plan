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
        Schema::table('yield_policies', function (Blueprint $table) {
            $table->decimal('min_submit_pct', 5, 2)->nullable()->default(100)->after('target_daily_dvl')
                  ->comment('Ngưỡng % sản lượng lý thuyết tối thiểu để cho phép submit lịch (mặc định 100%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yield_policies', function (Blueprint $table) {
            $table->dropColumn('min_submit_pct');
        });
    }
};
