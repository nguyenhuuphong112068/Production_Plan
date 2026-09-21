<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nhật ký tịnh tuyến lịch lý thuyết theo xác nhận hoàn thành.
 *
 * Mỗi lần một lô được xác nhận hoàn thành lệch giờ so với lý thuyết, các lô liền sau
 * (cùng phòng / công đoạn kế tiếp) bị dịch chuyển tự động. Mỗi lô bị dịch là một dòng,
 * các dòng cùng một lần chạy chung run_code. Cột old_* dùng để hoàn tác.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_plan_reroute_log', function (Blueprint $table) {
            $table->id();
            $table->string('run_code', 36);

            $table->unsignedInteger('source_stage_plan_id');
            $table->string('source_title', 512)->nullable();
            $table->integer('source_delta_minutes');

            $table->unsignedInteger('stage_plan_id');
            $table->dateTime('old_start')->nullable();
            $table->dateTime('old_end')->nullable();
            $table->dateTime('old_start_clearning')->nullable();
            $table->dateTime('old_end_clearning')->nullable();
            $table->dateTime('new_start')->nullable();
            $table->dateTime('new_end')->nullable();
            $table->dateTime('new_start_clearning')->nullable();
            $table->dateTime('new_end_clearning')->nullable();
            $table->integer('shift_minutes');
            $table->string('reason', 512)->nullable();

            $table->string('deparment_code', 5)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('undone_at')->nullable();
            $table->string('undone_by', 100)->nullable();

            $table->index('run_code', 'idx_reroute_run_code');
            $table->index('stage_plan_id', 'idx_reroute_stage_plan');
            $table->index('source_stage_plan_id', 'idx_reroute_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_plan_reroute_log');
    }
};
