<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thay 4 ô rời (Quy Cách Lấy Mẫu / Số Lần Lấy Mẫu / Số Lượng Lấy Mẫu / Đơn Vị) bằng
     * đúng 2 ô tự do "Mẫu Đóng Gói Sơ Cấp" và "Mẫu Đóng Gói Thứ Cấp".
     *
     * Phiếu lấy mẫu QA thật cần 2 bộ dữ liệu song song - sơ cấp (vỉ) và thứ cấp (hộp) -
     * mỗi bộ có thể có nhiều điểm lấy mẫu trong lô (đầu lô/giữa lô/cuối lô) với giá trị
     * gộp kiểu "5 hộp + 7 hộp SC". Một ô số + một ô đơn vị không gõ được dạng này, nên
     * đổi sang 2 ô văn bản tự do, gõ nguyên văn như phiếu giấy gốc
     * (xem PlanMasterInforParkaging::INPUT_LABELS).
     *
     * Chỉ có 1 dòng dữ liệu thật tính đến thời điểm này (dữ liệu thử nghiệm) nên không
     * cần chuyển đổi dữ liệu cũ - vết lịch sử cũ vẫn còn nguyên trong
     * plan_master_infor_parkaging_history, hiển thị bằng PlanMasterInforParkaging::LEGACY_LABELS.
     */
    public function up(): void
    {
        Schema::table('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->dropColumn([
                'Sampling_specifications',
                'Sampling_times',
                'Sampling_amount',
                'sampling_uint',
            ]);

            $table->text('primary_sample')->nullable()->after('PO_no');
            $table->text('secondary_sample')->nullable()->after('primary_sample');
        });
    }

    public function down(): void
    {
        Schema::table('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->dropColumn(['primary_sample', 'secondary_sample']);

            $table->string('Sampling_specifications', 100)->nullable()->after('PO_no');
            $table->string('Sampling_times', 100)->nullable()->after('Sampling_specifications');
            $table->decimal('Sampling_amount', 15, 3)->nullable()->after('Sampling_times');
            $table->string('sampling_uint', 50)->nullable()->after('Sampling_amount');
        });
    }
};
