<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thời gian biệt trữ của cốm sửa hạt khô (nhập dưới công đoạn Pha Chế).
 * Bảng lịch sử cũng phải có cột này vì logHistory() sao chép nguyên dòng intermediate_category.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['intermediate_category', 'intermediate_category_history'] as $table) {
            if (!Schema::hasColumn($table, 'quarantine_dry_granule')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->double('quarantine_dry_granule')->nullable()->after('quarantine_preparing');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['intermediate_category', 'intermediate_category_history'] as $table) {
            if (Schema::hasColumn($table, 'quarantine_dry_granule')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('quarantine_dry_granule');
                });
            }
        }
    }
};
