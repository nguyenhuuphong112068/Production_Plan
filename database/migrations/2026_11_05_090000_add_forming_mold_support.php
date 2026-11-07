<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sắp khuôn Định Hình (công đoạn 5) để chống trùng khuôn.
 *
 * - blister_mold(.history).stage_code: danh mục khuôn dùng chung, 7 = khuôn ép vỉ (ĐG), 5 = khuôn Định Hình.
 * - intermediate_mold: định mức khuôn ĐH theo BTP (intermediate_code), khác khuôn ép vỉ khai báo theo TP.
 * - room.require_mold: phòng ĐH phải kiểm soát khuôn (bật cho S23, S24 – máy đóng nang IMA Zibo).
 *
 * stage_plan.blister_mold_id dùng chung cho lô CĐ 5 và CĐ 7 (mỗi lô chỉ thuộc 1 công đoạn).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['blister_mold', 'blister_mold_history'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'stage_code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->tinyInteger('stage_code')->default(7)->after('code');
                });
            }
        }

        if (! Schema::hasTable('intermediate_mold')) {
            Schema::create('intermediate_mold', function (Blueprint $table) {
                $table->id();
                $table->string('intermediate_code', 50)->index();
                $table->foreignId('blister_mold_id')->constrained('blister_mold')->cascadeOnDelete();
                $table->string('created_by', 255)->nullable();
                $table->timestamps();
                $table->unique(['intermediate_code', 'blister_mold_id']);
            });
        }

        if (! Schema::hasColumn('room', 'require_mold')) {
            Schema::table('room', function (Blueprint $table) {
                $table->boolean('require_mold')->default(false)->after('blister_type_code');
            });
        }

        DB::table('room')
            ->where('stage_code', 5)
            ->where('deparment_code', 'PXV1')
            ->whereIn('code', ['S23', 'S24'])
            ->update(['require_mold' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('intermediate_mold');

        if (Schema::hasColumn('room', 'require_mold')) {
            Schema::table('room', function (Blueprint $table) {
                $table->dropColumn('require_mold');
            });
        }

        foreach (['blister_mold', 'blister_mold_history'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'stage_code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('stage_code');
                });
            }
        }
    }
};
