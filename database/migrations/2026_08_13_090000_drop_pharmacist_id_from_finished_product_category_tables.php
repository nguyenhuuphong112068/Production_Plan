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
        // Mã TP không còn dược sĩ phụ trách riêng: mỗi mã TP luôn liên kết với đúng một mã BTP
        // qua intermediate_code, nên dược sĩ phụ trách lấy từ intermediate_category.pharmacist_id.
        // Cột bên finished_product_category đã không còn được ghi / đọc ở bất kỳ màn hình nào.
        // Bảng history phải bỏ cùng lúc vì logHistory() copy nguyên dòng sang.
        foreach (['finished_product_category', 'finished_product_category_history'] as $table) {
            if (Schema::hasColumn($table, 'pharmacist_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('pharmacist_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Chỉ dựng lại cấu trúc cột, dữ liệu dược sĩ cũ của mã TP không khôi phục được.
     */
    public function down(): void
    {
        foreach (['finished_product_category', 'finished_product_category_history'] as $table) {
            if (! Schema::hasColumn($table, 'pharmacist_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('pharmacist_id')->nullable();
                });
            }
        }
    }
};
