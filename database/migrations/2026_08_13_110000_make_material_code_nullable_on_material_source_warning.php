<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mã NL để trống nghĩa là ma trận áp dụng cho mọi nguồn NL của mã BTP đó.
        // Cùng với thị trường/thiết bị để trống, dòng khai báo chỉ ràng buộc đúng
        // phần thông tin đã khai.
        Schema::table('material_source_warning', function (Blueprint $table) {
            $table->string('material_code', 50)->nullable()->change();
        });

        // Dữ liệu cũ có thể đã lưu chuỗi rỗng - đưa về NULL cho thống nhất
        DB::table('material_source_warning')->where('material_code', '')->update(['material_code' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('material_source_warning')->whereNull('material_code')->update(['material_code' => '']);

        Schema::table('material_source_warning', function (Blueprint $table) {
            $table->string('material_code', 50)->nullable(false)->change();
        });
    }
};
