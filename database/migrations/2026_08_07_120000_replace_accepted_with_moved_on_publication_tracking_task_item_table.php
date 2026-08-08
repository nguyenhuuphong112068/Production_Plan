<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bỏ trạng thái "chấp nhận nội dung": không còn dùng tới nữa
        Schema::table('publication_tracking_task_item', function (Blueprint $table) {
            $table->dropColumn(['accepted', 'accepted_at', 'accepted_by']);
        });

        // Thay bằng việc chuyển nội dung theo dõi chưa xong sang chu kỳ tiếp theo.
        // Giữ vết ở dòng gốc để kỳ hiện tại vẫn thấy nội dung đã được chuyển đi đâu
        // và không cho chuyển trùng lần nữa.
        Schema::table('publication_tracking_task_item', function (Blueprint $table) {
            $table->unsignedBigInteger('moved_to_period_id')->nullable()->after('detail_id');
            $table->unsignedBigInteger('moved_to_item_id')->nullable()->after('moved_to_period_id');
            $table->timestamp('moved_at')->nullable()->after('moved_to_item_id');
            $table->string('moved_by', 100)->nullable()->after('moved_at');

            $table->index('moved_to_period_id', 'pt_task_item_moved_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_tracking_task_item', function (Blueprint $table) {
            $table->dropIndex('pt_task_item_moved_period');
            $table->dropColumn(['moved_to_period_id', 'moved_to_item_id', 'moved_at', 'moved_by']);
        });

        Schema::table('publication_tracking_task_item', function (Blueprint $table) {
            $table->boolean('accepted')->default(false)->after('detail_id');
            $table->timestamp('accepted_at')->nullable()->after('accepted');
            $table->string('accepted_by', 100)->nullable()->after('accepted_at');
        });
    }
};
