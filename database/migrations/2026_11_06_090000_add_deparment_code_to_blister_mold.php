<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Khuôn mẫu (ép vỉ + Định Hình) tách theo phân xưởng.
 * Dữ liệu cũ: mọi khuôn đều đang dùng ở PXV1 -> gán PXV1.
 * Mã khuôn chỉ cần không trùng trong cùng phân xưởng.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['blister_mold', 'blister_mold_history'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deparment_code')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('deparment_code', 5)->nullable()->after('code');
                });
                DB::table($table)->whereNull('deparment_code')->update(['deparment_code' => 'PXV1']);
            }
        }

        $indexes = collect(DB::select('SHOW INDEX FROM blister_mold'))->pluck('Key_name')->unique();
        Schema::table('blister_mold', function (Blueprint $t) use ($indexes) {
            if ($indexes->contains('blister_mold_code_unique')) {
                $t->dropUnique('blister_mold_code_unique');
            }
            if (! $indexes->contains('blister_mold_dept_code_unique')) {
                $t->unique(['deparment_code', 'code'], 'blister_mold_dept_code_unique');
            }
        });
    }

    public function down(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM blister_mold'))->pluck('Key_name')->unique();
        Schema::table('blister_mold', function (Blueprint $t) use ($indexes) {
            if ($indexes->contains('blister_mold_dept_code_unique')) {
                $t->dropUnique('blister_mold_dept_code_unique');
            }
            if (! $indexes->contains('blister_mold_code_unique')) {
                $t->unique('code', 'blister_mold_code_unique');
            }
        });
        foreach (['blister_mold', 'blister_mold_history'] as $table) {
            if (Schema::hasColumn($table, 'deparment_code')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('deparment_code'));
            }
        }
    }
};
