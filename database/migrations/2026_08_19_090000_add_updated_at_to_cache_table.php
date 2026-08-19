<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột `updated_at` vào bảng `cache` để biết mỗi ô cache được ghi lúc nào.
 *
 * Bảng cache mặc định của Laravel chỉ có `key`, `value`, `expiration` nên không
 * thể biết dữ liệu được cập nhật khi nào — trước đây phải suy ngược bằng
 * (expiration - TTL), mà cách đó sai ngay khi TTL trong config bị đổi.
 *
 * Cột này do MySQL/MariaDB tự điền nhờ `DEFAULT CURRENT_TIMESTAMP ON UPDATE
 * CURRENT_TIMESTAMP`, KHÔNG cần sửa gì trong code Laravel: `Cache::put()` chạy
 * `INSERT ... ON DUPLICATE KEY UPDATE` nên cả lúc thêm mới lẫn lúc ghi đè đều
 * kích hoạt cột này.
 *
 * Lưu ý: cột chỉ nhằm mục đích quan sát/chẩn đoán. Không được dùng nó vào logic
 * nghiệp vụ, vì `php artisan cache:clear` sẽ xoá sạch bảng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cache') || Schema::hasColumn('cache', 'updated_at')) {
            return;
        }

        // Dùng SQL thô: Blueprint của Laravel không diễn đạt được
        // "ON UPDATE CURRENT_TIMESTAMP".
        DB::statement('
            ALTER TABLE `cache`
            ADD COLUMN `updated_at` TIMESTAMP NOT NULL
                DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP
        ');

        // Các dòng đang có sẽ nhận thời điểm chạy migration. Suy ngược lại cho
        // đúng hơn bằng (expiration - TTL) với những khoá đã biết TTL cố định.
        DB::statement('
            UPDATE `cache`
            SET `updated_at` = FROM_UNIXTIME(`expiration` - CASE
                    WHEN `key` LIKE "%shiftapi:month_backup:%"  THEN ?
                    WHEN `key` LIKE "%shiftapi:month:%"         THEN ?
                    WHEN `key` LIKE "%shiftapi:roster_backup:%" THEN ?
                    WHEN `key` LIKE "%shiftapi:roster:%"        THEN ?
                    ELSE 0 END)
            WHERE (`key` LIKE "%shiftapi:month%" OR `key` LIKE "%shiftapi:roster%")
              AND `expiration` > ?
        ', [
            (int) config('shiftapi.backup_ttl', 86400),
            (int) config('shiftapi.cache_ttl', 43200),
            (int) config('shiftapi.backup_ttl', 86400),
            (int) config('shiftapi.roster_cache_ttl', 21600),
            // Tránh tạo ra mốc thời gian âm/vô lý với các dòng rác cũ.
            (int) config('shiftapi.backup_ttl', 86400),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('cache') && Schema::hasColumn('cache', 'updated_at')) {
            Schema::table('cache', function ($table) {
                $table->dropColumn('updated_at');
            });
        }
    }
};
