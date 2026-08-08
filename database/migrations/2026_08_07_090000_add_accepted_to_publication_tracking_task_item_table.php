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
        // Trạng thái "chấp nhận nội dung" của từng công việc trên từng mã.
        // Khác với cột decision (Có/Không của cột Quyết Định): đây là xác nhận
        // rằng nội dung theo dõi được duyệt để áp dụng cho mã này.
        Schema::table('publication_tracking_task_item', function (Blueprint $table) {
            $table->boolean('accepted')->default(false)->after('detail_id');
            $table->timestamp('accepted_at')->nullable()->after('accepted');
            $table->string('accepted_by', 100)->nullable()->after('accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_tracking_task_item', function (Blueprint $table) {
            $table->dropColumn(['accepted', 'accepted_at', 'accepted_by']);
        });
    }
};
