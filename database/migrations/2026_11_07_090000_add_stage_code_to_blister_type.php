<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Loại máy (nhóm máy) dùng cho cả Định Hình (CĐ 5) và Đóng Gói (CĐ 7).
 *
 * - blister_type(.history).stage_code: 7 = máy ép vỉ (dữ liệu cũ), 5 = máy Định Hình.
 *   Mã nhóm máy (code) không dùng lại giữa 2 công đoạn (kiểm ở BlisterTypeController).
 * - Phòng CĐ 5 có room.blister_type_code => phòng kiểm soát trùng khuôn ĐH, thay cho
 *   cờ room.require_mold (cột giữ lại nhưng không còn đọc).
 * - Chuyển dữ liệu: tạo loại máy "IMA Zibo" (CĐ 5), gán cho phòng đang bật
 *   require_mold (S23, S24) và cho khuôn ĐH chưa có loại máy.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['blister_type', 'blister_type_history'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'stage_code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->tinyInteger('stage_code')->default(7)->after('code');
                });
            }
        }

        $roomIds = Schema::hasColumn('room', 'require_mold')
            ? DB::table('room')->where('stage_code', 5)->where('require_mold', 1)->whereNull('blister_type_code')->pluck('id')
            : collect();
        $formingMoldIds = DB::table('blister_mold')->where('stage_code', 5)
            ->where(fn ($q) => $q->whereNull('blister_type_code')->orWhere('blister_type_code', ''))
            ->pluck('id');

        if ($roomIds->isEmpty() && $formingMoldIds->isEmpty()) {
            return;
        }

        $type = DB::table('blister_type')->where('name', 'IMA Zibo')->first();
        if (! $type) {
            $code = ((int) DB::table('blister_type')->max('code')) + 1;
            DB::table('blister_type')->insert([
                'name' => 'IMA Zibo',
                'code' => $code,
                'stage_code' => 5,
                'active' => 1,
                'created_by' => 'Hệ thống (chuyển từ room.require_mold)',
                'created_at' => now(),
            ]);
        } else {
            // Tạo tay trên giao diện cũ (mặc định stage 7): chuyển sang Định Hình nếu mã không dùng chung với loại máy khác
            $code = (int) $type->code;
            $shared = DB::table('blister_type')->where('code', $code)->where('id', '!=', $type->id)->exists();
            if ($shared) {
                return;
            }
            DB::table('blister_type')->where('id', $type->id)->update(['stage_code' => 5]);
        }

        DB::table('room')->whereIn('id', $roomIds)->update(['blister_type_code' => $code]);
        DB::table('blister_mold')->whereIn('id', $formingMoldIds)->update(['blister_type_code' => json_encode([(string) $code])]);
    }

    public function down(): void
    {
        foreach (['blister_type', 'blister_type_history'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'stage_code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('stage_code');
                });
            }
        }
    }
};
