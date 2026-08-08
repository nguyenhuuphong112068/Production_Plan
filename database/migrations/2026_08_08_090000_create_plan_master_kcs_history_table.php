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
        // Lịch sử chỉnh sửa theo dõi hồ sơ KCS: mỗi ô thay đổi là một dòng ghi vết.
        //
        // Cố ý KHÔNG đặt khoá ngoại tới plan_master_KCS: xoá dòng theo dõi thì vết
        // lịch sử vẫn phải còn. plan_master_id được lưu kèm để tra cứu theo lô kể cả
        // khi dòng theo dõi đã bị xoá.
        Schema::create('plan_master_KCS_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_master_id');
            $table->unsignedBigInteger('kcs_id')->nullable();
            $table->string('action', 20);                    // create | update
            $table->string('field', 40);                     // tên cột nhập bị đổi
            $table->string('old_value', 255)->nullable();
            $table->string('new_value', 255)->nullable();
            $table->string('changed_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['plan_master_id', 'id'], 'pm_kcs_history_plan_master');
            $table->index('kcs_id', 'pm_kcs_history_kcs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_master_KCS_history');
    }
};
