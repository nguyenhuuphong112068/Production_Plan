<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * stage_plan.code và stage_plan.nextcessor_code chưa hề có chỉ mục, trong khi
 * QuarantineRoomController và các màn tính tồn đều tự nối bảng qua cặp cột này.
 * Với ~30k dòng, phép nối đó phải quét 30k x 30k và có thể chạy quá vài phút.
 */
return new class extends Migration
{
    private const INDEXES = [
        'idx_code'            => 'code',
        'idx_nextcessor_code' => 'nextcessor_code',
        'idx_predecessor_code' => 'predecessor_code',
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $indexName => $column) {
            if ($this->indexExists($indexName)) {
                continue;
            }

            DB::statement("ALTER TABLE `stage_plan` ADD INDEX `{$indexName}` (`{$column}`)");
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::INDEXES) as $indexName) {
            if (! $this->indexExists($indexName)) {
                continue;
            }

            DB::statement("ALTER TABLE `stage_plan` DROP INDEX `{$indexName}`");
        }
    }

    /** Bảng stage_plan được sửa nhiều lần ngoài migration nên phải kiểm tra trước khi thêm */
    private function indexExists(string $indexName): bool
    {
        return count(DB::select("SHOW INDEX FROM `stage_plan` WHERE Key_name = ?", [$indexName])) > 0;
    }
};
