<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yield_policies', function (Blueprint $table) {
            $table->id();
            $table->string('production_code', 10)->comment('Mã phân xưởng: PXV1, PXV2...');
            $table->unsignedSmallInteger('year')->comment('Năm áp dụng');
            $table->unsignedTinyInteger('month')->comment('Tháng áp dụng (1-12)');

            // Target tổng tháng
            $table->float('target_month_kg')->nullable()->comment('Target cả tháng (Kg)');
            $table->float('target_month_dvl')->nullable()->comment('Target cả tháng (ĐVL)');

            // Target mặc định mỗi ngày
            $table->float('target_daily_kg')->nullable()->comment('Target mỗi ngày (Kg)');
            $table->float('target_daily_dvl')->nullable()->comment('Target mỗi ngày (ĐVL)');

            $table->text('note')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->unique(['production_code', 'year', 'month'], 'unique_policy_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yield_policies');
    }
};
