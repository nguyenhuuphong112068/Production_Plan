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
        Schema::table('finished_product_category', function (Blueprint $table) {
            $table->string('general_market', 20)->nullable()->after('market_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finished_product_category', function (Blueprint $table) {
            $table->dropColumn('general_market');
        });
    }
};
