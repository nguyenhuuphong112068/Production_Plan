<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột trạng thái cho nhóm quyền, phục vụ nút "Quản Lý Nhóm Quyền"
 * trên màn DANH SÁCH NHÓM QUYỀN (tạo / sửa / vô hiệu hoá role ngay trên giao diện).
 * Role active = 0 không còn cấp quyền cho user và không hiện ở ô chọn phân quyền.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles') && !Schema::hasColumn('roles', 'active')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'active')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }
    }
};
