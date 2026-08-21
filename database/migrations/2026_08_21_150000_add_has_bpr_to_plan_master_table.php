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
        // Mỗi lô gắn với 2 mã và 2 hồ sơ khác nhau: mã BTP ra hồ sơ BMR, mã TP ra hồ sơ
        // BPR. Trước đây has_BMR gộp cả hai nên nhìn ô đỏ không biết còn chờ hồ sơ nào;
        // từ giờ has_BMR chỉ còn nói về mã BTP, has_BPR nói về mã TP.
        //
        // Mặc định 1 (sẵn sàng) để các kế hoạch tháng cũ - vốn do QA tự tích tay ô
        // "Hồ sơ lô" và không chạy qua "Theo dõi lên ấn bản" - không bị chuyển hàng loạt
        // thành "Chưa sẵn sàng" trên bản xuất Excel.
        Schema::table('plan_master', function (Blueprint $table) {
            $table->boolean('has_BPR')->default(true)->after('has_BMR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_master', function (Blueprint $table) {
            $table->dropColumn('has_BPR');
        });
    }
};
