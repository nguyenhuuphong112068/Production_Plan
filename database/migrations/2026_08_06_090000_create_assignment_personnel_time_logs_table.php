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
        // Lịch sử chỉnh thời gian công tác của từng nhân sự bằng thanh trượt (slider)
        // trên Lịch Công Tác Sản Xuất: mỗi lần kéo slider làm đổi giờ bắt đầu / kết thúc
        // đều ghi thêm 1 dòng để truy vết ai đổi, đổi từ mức nào sang mức nào, lúc nào.
        Schema::create('assignment_personnel_time_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('personnel_id');
            $table->string('personnel_name')->nullable();
            $table->date('reported_date')->nullable();
            $table->string('production_code', 50)->nullable();
            $table->string('group_code', 50)->nullable();
            $table->string('room_name')->nullable();
            $table->dateTime('old_start')->nullable();
            $table->dateTime('old_end')->nullable();
            $table->dateTime('new_start')->nullable();
            $table->dateTime('new_end')->nullable();
            $table->decimal('old_hours', 6, 2)->nullable();
            $table->decimal('new_hours', 6, 2)->nullable();
            $table->string('changed_by', 100)->nullable();
            $table->string('changed_by_name')->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'personnel_id'], 'apt_logs_assignment_personnel');
            $table->index(['reported_date', 'production_code', 'group_code'], 'apt_logs_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_personnel_time_logs');
    }
};
