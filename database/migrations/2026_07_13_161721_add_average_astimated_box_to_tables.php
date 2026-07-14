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
            $table->integer('average_astimated_box')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finished_product_category', function (Blueprint $table) {
            $table->dropColumn('average_astimated_box');
        });
    }
};
