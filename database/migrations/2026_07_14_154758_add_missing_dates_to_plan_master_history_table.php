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
        Schema::table('plan_master_history', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_master_history', 'expired_packing_date')) {
                $table->date('expired_packing_date')->nullable();
            }
            if (!Schema::hasColumn('plan_master_history', 'parkaging_before_date')) {
                $table->date('parkaging_before_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_master_history', function (Blueprint $table) {
            $table->dropColumn('expired_packing_date');
            $table->dropColumn('parkaging_before_date');
        });
    }
};
