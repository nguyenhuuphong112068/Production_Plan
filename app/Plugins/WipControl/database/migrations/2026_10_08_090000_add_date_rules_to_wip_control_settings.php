<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plugin Kiểm soát tồn BTP: các ngày NL/BB không được vi phạm khi kiểm soát (chọn trên modal). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wip_control_settings', function (Blueprint $table) {
            $table->string('date_rules', 255)->nullable()->after('bp_strategy')
                ->comment('JSON các ngày NL/BB không được vi phạm, null = tất cả');
        });
    }

    public function down(): void
    {
        Schema::table('wip_control_settings', function (Blueprint $table) {
            $table->dropColumn('date_rules');
        });
    }
};
