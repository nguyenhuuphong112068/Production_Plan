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
        Schema::table('room_status', function (Blueprint $table) {
            $table->index(['room_id', 'id'], 'idx_room_latest');
            $table->index('active', 'idx_status_active');
            $table->index('is_daily_report', 'idx_status_report');
            $table->index('deparment_code', 'idx_dept');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_status', function (Blueprint $table) {
            $table->dropIndex('idx_room_latest');
            $table->dropIndex('idx_status_active');
            $table->dropIndex('idx_status_report');
            $table->dropIndex('idx_dept');
        });
    }
};
