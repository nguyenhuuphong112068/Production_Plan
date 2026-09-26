<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Đánh dấu hoạt động của Báo cáo ngày (room_status, is_daily_report = 1) do trang Thực Thi Sản Xuất tạo ra:
 * hoạt động khác, khoảng tạm dừng, vệ sinh không gắn lô. Trang Báo cáo ngày không được sửa/xóa các dòng này.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_status', function (Blueprint $table) {
            $table->boolean('from_execution')->default(false)->after('is_daily_report')
                ->comment('1 = tạo từ trang Thực Thi Sản Xuất: Báo cáo ngày không được sửa/xóa');
        });

        // Các dòng trang Thực Thi Sản Xuất đã tạo trước khi có cột này: khoảng tạm dừng / vệ sinh gắn với log trạng thái,
        // và hoạt động khai báo trên trang (giờ hệ thống có giây; Báo cáo ngày nhập theo phút nên giây luôn = 0)
        $first = Schema::hasTable('room_execution_log') ? DB::table('room_execution_log')->min('created_at') : null;
        if ($first) {
            DB::table('room_status')
                ->where('is_daily_report', 1)
                ->where(fn($q) => $q
                    ->whereIn('id', DB::table('room_execution_log')->whereNotNull('room_status_id')->select('room_status_id'))
                    ->orWhere(fn($q2) => $q2->where('start', '>=', $first)->whereRaw('SECOND(`start`) <> 0')))
                ->update(['from_execution' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('room_status', function (Blueprint $table) {
            $table->dropColumn('from_execution');
        });
    }
};
