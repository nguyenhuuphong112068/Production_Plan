<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hai bổ sung cho màn tồn kho lý thuyết theo công đoạn.
 *
 * 1. supply_series: sản lượng Pha chế nhập vào dây chuyền mỗi ngày. Pha chế là
 *    nguồn duy nhất cấp bán thành phẩm cho toàn bộ công đoạn sau, nên đặt cạnh
 *    đường tồn mới đọc được quan hệ nhân quả: đổ vào nhiều mà tồn vẫn tụt nghĩa
 *    là phía sau chạy vượt nguồn, còn tồn dâng lên là dấu hiệu nghẽn ở phía sau.
 *    Chuỗi này tính cho cả phân xưởng chứ không riêng nhóm nào, ghi lặp trên mọi
 *    dòng của cùng ngày chốt để đọc bản chốt không phải nối thêm bảng.
 *
 * 2. wip_stock_limits: giới hạn trên/dưới của mức tồn từng công đoạn, cấu hình ở
 *    trang Chính sách sản lượng. Khác hẳn wip_coverage_thresholds đã bỏ: ngưỡng
 *    cũ tính theo SỐ NGÀY đáp ứng - một con số suy ra từ định mức giờ máy nên
 *    không đối chiếu được với hiện trường. Giới hạn mới đặt thẳng trên LƯỢNG TỒN
 *    (đơn vị liều), là thứ đếm được ngay trong kho.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->longText('supply_series')->nullable()->after('daily_series')
                ->comment('JSON: [{date, output_kg, output_dvl, lots}] - sản lượng Pha chế từng ngày');
        });

        Schema::create('wip_stock_limits', function (Blueprint $table) {
            $table->id();
            $table->string('production_code', 10)->comment('Mã phân xưởng: PXV1, PXV2...');
            $table->string('stage_group_code', 4)
                ->comment('Công đoạn đang chờ: DH, BP, DG');
            $table->decimal('min_stock_dvl', 18, 2)->nullable()
                ->comment('Dưới mức này là thiếu hàng cho công đoạn sau (đơn vị liều)');
            $table->decimal('max_stock_dvl', 18, 2)->nullable()
                ->comment('Trên mức này là ứ hàng, công đoạn sau không tiêu thụ kịp (đơn vị liều)');
            $table->boolean('is_active')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->unique(['production_code', 'stage_group_code'], 'unique_wip_stock_limit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_stock_limits');

        Schema::table('wip_coverage_snapshots', function (Blueprint $table) {
            $table->dropColumn('supply_series');
        });
    }
};
