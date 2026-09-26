<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Công tắc "Xác nhận và điều chỉnh lịch theo thời gian thực" theo phân xưởng.
 * Bật thì mỗi lần xác nhận vệ sinh của lô (✓✓ trang Xác nhận hoàn thành, Kết thúc vệ sinh trang Thực Thi Sản Xuất)
 * sẽ tịnh tuyến lịch lý thuyết (ScheduleRerouteService::reroute).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_reroute_settings', function (Blueprint $table) {
            $table->id();
            $table->string('deparment_code', 5)->unique();
            $table->boolean('realtime_reroute')->default(false);
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_reroute_settings');
    }
};
