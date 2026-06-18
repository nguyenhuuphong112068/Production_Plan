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
            $table->dropColumn(['target_month_kg', 'target_daily_kg']);
        });

        Schema::table('yield_policy_daily_overrides', function (Blueprint $table) {
            $table->dropColumn('target_qty_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yield_policies', function (Blueprint $table) {
            $table->float('target_month_kg')->nullable()->comment('Target cả tháng (Kg)');
            $table->float('target_daily_kg')->nullable()->comment('Target mỗi ngày (Kg)');
        });

        Schema::table('yield_policy_daily_overrides', function (Blueprint $table) {
            $table->float('target_qty_kg')->nullable()->comment('Target ngày (Kg)');
        });
    }
};
