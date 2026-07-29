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
        // Lịch sử duyệt giờ tăng ca: mỗi lần bấm "Chấp Nhận Tăng ca" (kể cả duyệt lại)
        // đều ghi thêm 1 dòng, không ghi đè, để truy vết được ai duyệt mức nào, lúc nào.
        Schema::create('overtime_approval_logs', function (Blueprint $table) {
            $table->id();
            $table->date('reported_date');
            $table->string('production_code');
            $table->string('group_code')->nullable();
            $table->decimal('approved_hours', 8, 2)->default(0);
            $table->integer('approved_persons')->default(0);
            $table->decimal('previous_hours', 8, 2)->default(0);
            $table->integer('previous_persons')->default(0);
            $table->text('groups_detail')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamps();

            $table->index(['reported_date', 'production_code', 'group_code'], 'overtime_approval_logs_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_approval_logs');
    }
};
