<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trang Thực Thi Sản Xuất đọc nhân sự đang được phân công (Lịch Công Tác → Sản Xuất) theo phân xưởng + thời điểm hiện tại.
 * Bảng assignments chưa có index nào ngoài khóa chính nên mỗi lần phải quét toàn bảng; các trang Lịch Công Tác
 * cũng lọc theo deparment_code + start.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasIndex('assignments', 'idx_assignments_dept_start')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->index(['deparment_code', 'start'], 'idx_assignments_dept_start');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('assignments', 'idx_assignments_dept_start')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->dropIndex('idx_assignments_dept_start');
            });
        }
    }
};
