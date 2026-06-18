<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yield_policy_daily_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')
                  ->constrained('yield_policies')
                  ->onDelete('cascade')
                  ->comment('FK đến yield_policies');
            $table->date('target_date')->comment('Ngày cụ thể cần override');
            $table->float('target_qty_kg')->nullable()->comment('Target ngày (Kg)');
            $table->float('target_qty_dvl')->nullable()->comment('Target ngày (ĐVL)');
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->unique(['policy_id', 'target_date'], 'unique_daily_override');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yield_policy_daily_overrides');
    }
};
