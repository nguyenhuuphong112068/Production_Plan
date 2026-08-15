<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ngưỡng cảnh báo, cấu hình trên trang Chính sách sản lượng
        Schema::create('wip_coverage_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('production_code', 10)->comment('Mã phân xưởng: PXV1, PXV2...');
            $table->string('stage_group_code', 4)->comment('Nhóm công đoạn nguồn: PC, DH, BP');

            $table->decimal('critical_days', 6, 2)->nullable()->comment('Dưới ngưỡng này là nguy cấp (ngày)');
            $table->decimal('warn_days', 6, 2)->nullable()->comment('Dưới ngưỡng này là cảnh báo (ngày)');
            $table->unsignedSmallInteger('horizon_days')->default(30)->comment('Số ngày dự báo về phía trước');
            $table->boolean('is_active')->default(1);

            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->unique(['production_code', 'stage_group_code'], 'unique_wip_threshold');
        });

        // Bản chốt tồn lúc 6 giờ sáng, mỗi nhóm công đoạn một dòng
        Schema::create('wip_coverage_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->comment('Ngày chốt');
            $table->dateTime('snapshot_at')->comment('Mốc thời gian tính tồn, thường là 06:00');
            $table->string('production_code', 10);
            $table->string('stage_group_code', 4)->comment('Nhóm công đoạn giữ tồn: PC, DH, BP');
            $table->string('next_stage_group_code', 4)->nullable()->comment('Nhóm tiêu thụ chính, chỉ để hiển thị nhãn');

            $table->decimal('stock_dvl', 18, 2)->default(0)->comment('Lượng tồn quy về đơn vị liều');
            $table->unsignedInteger('stock_lots')->default(0)->comment('Số lô đang nằm tồn');
            $table->unsignedInteger('orphan_lots')->default(0)->comment('Số lô không xác định được công đoạn kế tiếp');

            $table->decimal('days_of_cover', 8, 2)->nullable()->comment('Số ngày đáp ứng, rỗng nghĩa là không có nhu cầu');
            $table->date('first_shortage_date')->nullable()->comment('Ngày tồn cạn');
            $table->decimal('min_product_days_of_cover', 8, 2)->nullable()->comment('Mã BTP cạn sớm nhất');
            $table->string('min_product_code', 50)->nullable();

            $table->string('status', 12)->default('ok')->comment('ok | warn | critical | no_demand');
            $table->unsignedSmallInteger('horizon_days')->default(30);
            $table->longText('daily_series')->nullable()->comment('JSON: [{date, demand_dvl, supply_dvl, remaining_dvl}]');
            $table->dateTime('computed_at')->nullable();

            $table->unique(['snapshot_date', 'production_code', 'stage_group_code'], 'unique_wip_snapshot');
            $table->index(['production_code', 'snapshot_date'], 'idx_wip_snapshot_lookup');
        });

        // Chi tiết theo mã bán thành phẩm
        Schema::create('wip_coverage_snapshot_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('snapshot_id');
            $table->string('intermediate_code', 50);
            $table->string('product_name', 255)->nullable();
            $table->string('unit', 20)->nullable()->comment('Đơn vị thật của mã này, ví dụ viên hoặc Kg');

            $table->decimal('stock_dvl', 18, 2)->default(0);
            $table->unsignedInteger('stock_lots')->default(0);
            $table->decimal('days_of_cover', 8, 2)->nullable();
            $table->date('first_shortage_date')->nullable();
            $table->string('status', 12)->default('ok');
            $table->longText('batches')->nullable()->comment('JSON: [{plan_master_id, batch, stage_code, start, qty_dvl}]');

            $table->index('snapshot_id', 'idx_wip_detail_snapshot');
            $table->foreign('snapshot_id', 'fk_wip_detail_snapshot')
                ->references('id')->on('wip_coverage_snapshots')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_coverage_snapshot_details');
        Schema::dropIfExists('wip_coverage_snapshots');
        Schema::dropIfExists('wip_coverage_thresholds');
    }
};
