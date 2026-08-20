<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Danh sách thị trường được đánh dấu Châu Âu sẵn khi chạy migration.
     *
     * Gồm 27 nước EU cộng các mã gộp đang dùng trong finished_product_category
     * (ALDE, DK-FI, DK-FI-SE, DK-SE-FI, EU) - những mã này không phải mã ISO nên
     * không thể suy ra tự động từ danh sách nước.
     *
     * Cố ý KHÔNG đánh dấu các nước châu Âu ngoài EU đang có sản phẩm: CH
     * (Switzerland), BA (Bosnia), RS (Serbia), UA (Ukraine). Nếu nghiệp vụ muốn
     * xếp chúng vào "Sản phẩm Châu Âu" thì tick trực tiếp ở Dữ Liệu Gốc >
     * Thị Trường, không cần sửa code.
     */
    private const EU_CODES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR',
        'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK',
        'SI', 'ES', 'SE',
        'ALDE', 'DK-FI', 'DK-FI-SE', 'DK-SE-FI', 'EU',
    ];

    public function up(): void
    {
        // Kiểm tra hasColumn để chạy lại migration không lỗi "Duplicate column"
        if (!Schema::hasColumn('market', 'is_eu')) {
            Schema::table('market', function (Blueprint $table) {
                // Phân loại Châu Âu / Ngoài Châu Âu của trang Thông Báo Đóng Gói.
                // Mặc định 0: thị trường mới thêm phải được tick thủ công, an toàn hơn
                // là đoán theo tên nước.
                $table->boolean('is_eu')->default(false)->after('name');
            });
        }

        // market_history phải có đúng bộ cột của market: MarketController::logHistory()
        // chép nguyên dòng hiện tại sang bảng lịch sử, thiếu cột là câu insert lỗi
        // "Unknown column" và chức năng cập nhật thị trường sẽ hỏng.
        if (!Schema::hasColumn('market_history', 'is_eu')) {
            Schema::table('market_history', function (Blueprint $table) {
                $table->boolean('is_eu')->default(false)->after('name');
            });
        }

        DB::table('market')->whereIn('code', self::EU_CODES)->update(['is_eu' => true]);
    }

    public function down(): void
    {
        Schema::table('market', function (Blueprint $table) {
            $table->dropColumn('is_eu');
        });

        Schema::table('market_history', function (Blueprint $table) {
            $table->dropColumn('is_eu');
        });
    }
};
