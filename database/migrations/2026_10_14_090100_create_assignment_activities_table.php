<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liên kết ô "Hoạt động" (assignments.Job_description) của Lịch công tác sản xuất với stage_plan.
 *
 * Mỗi mục lịch lý thuyết chép sang ô Hoạt động mang data-sp-id / data-kind; khi lưu ca, các mục này
 * được ghi thành dòng ở đây. Phần chữ người dùng tự gõ vẫn chỉ nằm trong Job_description.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('assignment_activities')) {
            return;
        }

        Schema::create('assignment_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('stage_plan_id');
            // SX = sản xuất lô, VS = vệ sinh
            $table->string('activity_type', 2)->default('SX');
            $table->timestamp('created_at')->nullable();

            $table->unique(['assignment_id', 'stage_plan_id', 'activity_type'], 'assignment_activities_unique');
            $table->index('stage_plan_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('assignment_activities');
    }
};
