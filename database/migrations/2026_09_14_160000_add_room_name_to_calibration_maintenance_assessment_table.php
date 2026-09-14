<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calibration_maintenance_assessment', function (Blueprint $table) {
            // Khu vực tự nhập khi công việc ngoài kế hoạch không thuộc phòng nào trong danh mục
            $table->string('room_name')->nullable()->after('room_id');
        });
    }

    public function down(): void
    {
        Schema::table('calibration_maintenance_assessment', function (Blueprint $table) {
            $table->dropColumn('room_name');
        });
    }
};
