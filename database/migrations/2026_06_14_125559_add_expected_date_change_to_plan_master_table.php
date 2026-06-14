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
            $table->boolean('expected_date_change')->default(0)->after('expected_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_master', function (Blueprint $table) {
            $table->dropColumn('expected_date_change');
        });
    }
};
