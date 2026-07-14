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
            $table->dateTime('create_at_order_number')->nullable();
            $table->string('create_by_order_number', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_master', function (Blueprint $table) {
            $table->dropColumn(['create_at_order_number', 'create_by_order_number']);
        });
    }
};
