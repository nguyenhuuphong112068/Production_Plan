<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Số ngày đáp ứng chuyển từ cách tính theo lịch đã sắp sang cách tính theo định
 * mức giờ máy và công suất của công đoạn tiêu thụ.
 *
 * Lịch chỉ được sắp trước khoảng hai tuần nên phần sau của khoảng dự báo không có
 * nhu cầu nào để trừ, khiến nhóm nào cũng ra "còn dư", không cảnh báo được gì.
 * Cách mới lấy quota.m_time làm tải và nhịp chạy đo được của công đoạn làm mẫu số,
 * nên luôn ra số hữu hạn và biến thiên theo tồn.
 *
 * Các cột cũ mang nghĩa "mã cạn sớm nhất" nay đổi thành "mã chiếm nhiều giờ máy
 * nhất", nên đổi tên luôn để không ai đọc nhầm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->decimal('load_hours', 12, 1)->nullable()->after('orphan_lots')
                ->comment('Giờ máy mà lượng tồn này đặt lên công đoạn tiêu thụ');
            $table->longText('capacity_basis')->nullable()->after('horizon_days')
                ->comment('JSON: [{stage_code, rooms, hours_per_day, share_per_day, lots_measured, window_days}]');
        });

        // Số liệu cũ tính theo cách khác hẳn, giữ lại sẽ làm biểu đồ xu hướng gãy khúc
        DB::table('wip_coverage_snapshot_details')->delete();
        DB::table('wip_coverage_snapshots')->delete();

        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->renameColumn('min_product_days_of_cover', 'top_product_days');
            $table->renameColumn('min_product_code', 'top_product_code');
        });

        Schema::table('wip_coverage_snapshot_details', function (Blueprint $table) {
            $table->decimal('load_hours', 12, 1)->nullable()->after('stock_lots')
                ->comment('Giờ máy mà riêng mã này đặt lên công đoạn tiêu thụ');
            $table->renameColumn('first_shortage_date', 'last_out_date');
        });
    }

    public function down(): void
    {
        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->dropColumn(['load_hours', 'capacity_basis']);
            $table->renameColumn('top_product_days', 'min_product_days_of_cover');
            $table->renameColumn('top_product_code', 'min_product_code');
        });

        Schema::table('wip_coverage_snapshot_details', function (Blueprint $table) {
            $table->dropColumn('load_hours');
            $table->renameColumn('last_out_date', 'first_shortage_date');
        });
    }
};
