<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lý do thay đổi của danh mục BTP / TP.
 * change_reason trên một dòng là lý do đã tạo ra phiên bản đó; khi sửa, logHistory() chép nguyên
 * dòng cũ (kèm lý do cũ) sang bảng lịch sử rồi dòng chính nhận lý do mới.
 */
return new class extends Migration
{
    private array $tables = [
        'intermediate_category',
        'intermediate_category_history',
        'finished_product_category',
        'finished_product_category_history',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasColumn($table, 'change_reason')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('change_reason', 500)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'change_reason')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('change_reason');
                });
            }
        }
    }
};
