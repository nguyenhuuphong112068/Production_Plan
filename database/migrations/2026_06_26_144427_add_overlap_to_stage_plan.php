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
        Schema::table('stage_plan', function (Blueprint $table) {
            if (!Schema::hasColumn('stage_plan', 'overlap')) {
                $table->tinyInteger('overlap')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stage_plan', function (Blueprint $table) {
            if (Schema::hasColumn('stage_plan', 'overlap')) {
                $table->dropColumn('overlap');
            }
        });
    }
};
