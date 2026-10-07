<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plugin Kiểm soát tồn BTP: cách giảm tồn chờ BP chọn trên modal (thay hai công tắc cũ). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wip_control_settings', function (Blueprint $table) {
            $table->string('bp_strategy', 20)->default('mix')->after('pull_buffer_hours')
                ->comment('none | mix | pull | gate_shift | gate_keep');
        });
    }

    public function down(): void
    {
        Schema::table('wip_control_settings', function (Blueprint $table) {
            $table->dropColumn('bp_strategy');
        });
    }
};
