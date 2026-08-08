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
        // Version BOM của mã BTP / TP được chốt tại thời điểm lô có số lệnh.
        //
        // Phải chụp lại thay vì tra MMS mỗi lần xem, vì BOM được nâng revision theo thời
        // gian còn hồ sơ KCS của lô phải phản ánh đúng version dùng để sản xuất lô đó.
        //
        // Tách khỏi plan_master_KCS: đây là dữ liệu của lô kế hoạch, không phải nội dung
        // người dùng nhập khi theo dõi KCS. Để chung sẽ khiến lô chưa nhập gì cũng có
        // dòng theo dõi, làm hỏng dấu hiệu "chưa lưu" và phần điền sẵn của lưới.
        Schema::create('plan_master_bom_version', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_master_id');
            $table->string('order_number', 20)->nullable();   // số lệnh tại thời điểm chốt
            $table->string('btp_code', 40)->nullable();
            $table->string('btp_version', 20)->nullable();
            $table->string('tp_code', 40)->nullable();
            $table->string('tp_version', 20)->nullable();
            $table->timestamp('captured_at')->nullable();

            $table->foreign('plan_master_id', 'fk_pm_bom_version_plan_master_id')
                ->references('id')->on('plan_master')->onDelete('cascade');

            $table->unique('plan_master_id', 'pm_bom_version_plan_master');
        });

        // Ấn bản không còn nhập tay: đã thay bằng version BOM chốt theo mã BTP / TP.
        // An toàn để bỏ vì cột này chưa có dòng dữ liệu nào.
        Schema::table('plan_master_KCS', function (Blueprint $table) {
            $table->dropColumn('edition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_master_KCS', function (Blueprint $table) {
            $table->string('edition', 20)->nullable()->after('plan_master_id');
        });

        Schema::dropIfExists('plan_master_bom_version');
    }
};
