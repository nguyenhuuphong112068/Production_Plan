<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xác nhận đã thật sự lấy mẫu ngoài hiện trường - khác với việc chỉ mô tả kế hoạch
     * lấy mẫu ở primary_sample/secondary_sample. Người lấy mẫu tick xác nhận, ghi tên
     * và ngày lấy, đứng cuối bảng vì đây là bước hoàn tất sau khi đã có đủ thông tin
     * ở các cột trước.
     */
    public function up(): void
    {
        Schema::table('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->boolean('sampled_confirmed')->default(0)->after('Reason');
            $table->string('sampled_by', 100)->nullable()->after('sampled_confirmed');
            $table->date('sampled_date')->nullable()->after('sampled_by');
        });
    }

    public function down(): void
    {
        Schema::table('plan_master_infor_parkaging', function (Blueprint $table) {
            $table->dropColumn(['sampled_confirmed', 'sampled_by', 'sampled_date']);
        });
    }
};
