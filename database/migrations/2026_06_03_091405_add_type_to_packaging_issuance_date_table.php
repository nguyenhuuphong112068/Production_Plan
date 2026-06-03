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
        Schema::table('packaging_issuance_date', function (Blueprint $table) {
            $table->string('type', 100)->nullable()->comment('Source of update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packaging_issuance_date', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
