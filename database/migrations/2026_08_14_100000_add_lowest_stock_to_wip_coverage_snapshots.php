<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tồn được tính lại tại 06:00 từng ngày nên đường tồn có thể lên xuống,
 * không chỉ đi xuống. Mốc đáng lo là lúc tồn xuống thấp nhất chứ không
 * nhất thiết là lúc chạm 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->decimal('lowest_stock_dvl', 18, 2)->nullable()
                ->after('first_shortage_date')
                ->comment('Mức tồn thấp nhất trong khoảng dự báo');
            $table->date('lowest_stock_date')->nullable()
                ->after('lowest_stock_dvl')
                ->comment('Ngày tồn xuống thấp nhất');
        });
    }

    public function down(): void
    {
        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->dropColumn(['lowest_stock_dvl', 'lowest_stock_date']);
        });
    }
};
