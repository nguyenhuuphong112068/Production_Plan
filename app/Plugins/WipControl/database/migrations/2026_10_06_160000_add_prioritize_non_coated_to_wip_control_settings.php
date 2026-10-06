<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plugin Kiểm soát tồn BTP: bật/tắt bước "Ưu tiên lô không bao phim" theo phân xưởng. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wip_control_settings', function (Blueprint $table) {
            $table->boolean('prioritize_non_coated')->default(1)->after('lock_validation')
                ->comment('1 = khi chờ BP vượt Max: ngưng nguồn lô bao phim, kéo lô không bao phim lên');
        });
    }

    public function down(): void
    {
        Schema::table('wip_control_settings', function (Blueprint $table) {
            $table->dropColumn('prioritize_non_coated');
        });
    }
};
