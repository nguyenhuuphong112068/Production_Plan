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
        Schema::table('plan_master', function (Blueprint $table) {
            $table->boolean('is_validation_tracking')->default(0)->after('IsHypothesis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_master', function (Blueprint $table) {
            $table->dropColumn('is_validation_tracking');
        });
    }
};
