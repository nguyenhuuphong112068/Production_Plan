<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nội dung theo dõi thực tế có thể dài hơn 500 ký tự (mô tả nhiều bước, nhiều lưu ý),
        // nên bỏ giới hạn độ dài ở cột content.
        Schema::table('publication_tracking_task', function (Blueprint $table) {
            $table->text('content')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cắt bớt nội dung đã lưu vượt 500 ký tự, nếu không MySQL sẽ báo lỗi khi thu nhỏ cột
        DB::table('publication_tracking_task')
            ->whereRaw('CHAR_LENGTH(content) > 500')
            ->update(['content' => DB::raw('LEFT(content, 500)')]);

        Schema::table('publication_tracking_task', function (Blueprint $table) {
            $table->string('content', 500)->change();
        });
    }
};
