<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Màn tồn bán thành phẩm bỏ hẳn phần "số ngày còn đáp ứng được", và đổi cách
 * gom tồn từ "theo công đoạn NGUỒN" sang "theo công đoạn ĐÍCH".
 *
 * Số ngày đáp ứng dựa vào định mức giờ máy (quota.m_time) và nhịp chạy đo được
 * của công đoạn sau — cả hai đều là ước lượng, nên con số ra được đọc như một
 * lời hứa nhưng lại không kiểm chứng được bằng gì trên hiện trường. Màn này
 * quay về đúng việc nó làm tốt: thống kê lượng bán thành phẩm đang chờ.
 *
 * Trước đây mỗi công đoạn nguồn (Pha chế, Định hình, Bao phim) là một dòng
 * riêng. Người dùng thực ra chỉ quan tâm câu hỏi "đang chờ ĐÓNG GÓI bao
 * nhiêu", bất kể lô đó xuất phát từ đâu — hàng đi đủ tuần tự qua Bao phim hay
 * hàng bỏ qua Bao phim/Định hình đều phải cộng chung vào một con số. Nay mỗi
 * dòng là MỘT CÔNG ĐOẠN ĐÍCH (Định hình, Bao phim, Đóng gói), khoá bằng cột
 * next_stage_group_code có sẵn từ migration đầu tiên — trước đây cột này chỉ
 * mang tính hiển thị (nhãn của nguồn nào đông nhất), nay trở thành khoá chính.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bản chốt cũ gom theo công đoạn NGUỒN, không dựng lại được thành nhóm
        // theo công đoạn ĐÍCH, nên xoá sạch để lệnh chốt 6h sáng dựng lại từ đầu
        DB::table('wip_coverage_snapshot_details')->delete();
        DB::table('wip_coverage_snapshots')->delete();

        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->dropUnique('unique_wip_snapshot');
            $table->dropIndex('idx_wip_snapshot_lookup');
            $table->dropColumn([
                'stage_group_code',
                'next_stage_group_code',
                'orphan_lots',
                'load_hours',
                'days_of_cover',
                'capacity_basis',
                'status',
                'top_product_days',
            ]);
        });

        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->string('next_stage_group_code', 4)->default('')->after('production_code')
                ->comment('Công đoạn mà lượng tồn này đang chờ để bước vào: DH, BP, DG, hoặc NA nếu chưa rõ');

            $table->decimal('highest_stock_dvl', 18, 2)->nullable()->after('lowest_stock_date')
                ->comment('Mức tồn cao nhất trong khoảng dự báo');
            $table->date('highest_stock_date')->nullable()->after('highest_stock_dvl')
                ->comment('Ngày tồn lên cao nhất');
            $table->decimal('avg_stock_dvl', 18, 2)->nullable()->after('highest_stock_date')
                ->comment('Mức tồn trung bình trong khoảng dự báo');
            $table->decimal('top_product_dvl', 18, 2)->nullable()->after('top_product_code')
                ->comment('Lượng tồn của mã BTP giữ nhiều hàng nhất');

            $table->unique(['snapshot_date', 'production_code', 'next_stage_group_code'], 'unique_wip_snapshot');
            $table->index(['production_code', 'next_stage_group_code', 'snapshot_date'], 'idx_wip_snapshot_lookup');
        });

        Schema::table('wip_coverage_snapshot_details', function (Blueprint $table) {
            $table->dropColumn(['load_hours', 'days_of_cover', 'status']);
        });

        Schema::table('wip_coverage_snapshot_details', function (Blueprint $table) {
            $table->decimal('share_pct', 6, 1)->nullable()->after('stock_lots')
                ->comment('Phần trăm lượng tồn của nhóm mà riêng mã này chiếm');
        });

        // Ngưỡng cảnh báo chỉ có nghĩa với số ngày đáp ứng, bỏ theo luôn cùng
        // mục cấu hình ở trang Chính sách sản lượng
        Schema::dropIfExists('wip_coverage_thresholds');
    }

    public function down(): void
    {
        Schema::create('wip_coverage_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('production_code', 10);
            $table->string('stage_group_code', 4);
            $table->decimal('critical_days', 6, 2)->nullable();
            $table->decimal('warn_days', 6, 2)->nullable();
            $table->unsignedSmallInteger('horizon_days')->default(30);
            $table->boolean('is_active')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
            $table->unique(['production_code', 'stage_group_code'], 'unique_wip_threshold');
        });

        Schema::table('wip_coverage_snapshot_details', function (Blueprint $table) {
            $table->dropColumn('share_pct');
            $table->decimal('load_hours', 12, 1)->nullable()->after('stock_lots');
            $table->decimal('days_of_cover', 8, 2)->nullable()->after('load_hours');
            $table->string('status', 12)->default('ok')->after('last_out_date');
        });

        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->dropUnique('unique_wip_snapshot');
            $table->dropIndex('idx_wip_snapshot_lookup');
            $table->dropColumn([
                'next_stage_group_code',
                'highest_stock_dvl',
                'highest_stock_date',
                'avg_stock_dvl',
                'top_product_dvl',
            ]);
        });

        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->string('stage_group_code', 4)->after('production_code')
                ->comment('Nhóm công đoạn giữ tồn: PC, DH, BP');
            $table->string('next_stage_group_code', 4)->nullable()->after('stage_group_code')
                ->comment('Nhóm tiêu thụ chính, chỉ để hiển thị nhãn');
            $table->unsignedInteger('orphan_lots')->default(0)->after('stock_lots')
                ->comment('Số lô không xác định được công đoạn kế tiếp');
            $table->decimal('load_hours', 12, 1)->nullable()->after('orphan_lots');
            $table->decimal('days_of_cover', 8, 2)->nullable()->after('load_hours');
            $table->decimal('top_product_days', 8, 2)->nullable()->after('lowest_stock_date');
            $table->string('status', 12)->default('ok')->after('top_product_code');
            $table->longText('capacity_basis')->nullable()->after('horizon_days');

            $table->unique(['snapshot_date', 'production_code', 'stage_group_code'], 'unique_wip_snapshot');
            $table->index(['production_code', 'snapshot_date'], 'idx_wip_snapshot_lookup');
        });
    }
};
