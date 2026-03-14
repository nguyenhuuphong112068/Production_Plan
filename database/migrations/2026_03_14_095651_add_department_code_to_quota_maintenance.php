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
        Schema::table('quota_maintenance', function (Blueprint $table) {
            $table->string('deparment_code', 5)->nullable()->after('exe_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quota_maintenance', function (Blueprint $table) {
            $table->dropColumn('deparment_code');
        });
    }
};
