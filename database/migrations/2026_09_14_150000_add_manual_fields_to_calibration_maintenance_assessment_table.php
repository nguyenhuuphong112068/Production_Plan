<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calibration_maintenance_assessment', function (Blueprint $table) {
            // Đánh giá ngoài kế hoạch không gắn với dòng lịch nào (MySQL cho phép nhiều NULL trên unique index)
            $table->unsignedBigInteger('stage_plan_id')->nullable()->change();

            $table->dateTime('assessment_date')->nullable()->after('plan_master_id');
            $table->unsignedBigInteger('room_id')->nullable()->after('assessment_date');
            $table->string('equipment_name')->nullable()->after('room_id');
            $table->string('type_name', 50)->nullable()->after('equipment_name');
            $table->string('deparment_code', 50)->nullable()->after('type_name');
        });
    }

    public function down(): void
    {
        Schema::table('calibration_maintenance_assessment', function (Blueprint $table) {
            $table->dropColumn(['assessment_date', 'room_id', 'equipment_name', 'type_name', 'deparment_code']);
            $table->unsignedBigInteger('stage_plan_id')->nullable(false)->change();
        });
    }
};
