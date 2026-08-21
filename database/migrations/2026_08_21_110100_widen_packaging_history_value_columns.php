<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * old_value/new_value đang là varchar(255), đủ cho các cột cũ (PO_no, Reason...) nhưng
     * primary_sample/secondary_sample giờ là text tự do có thể dài hơn 255 ký tự (nhiều
     * điểm lấy mẫu trong 1 ô). DB chạy strict mode nên chèn vết dài hơn 255 sẽ ném lỗi
     * "Data too long" thay vì cắt bớt - phải nới cột trước khi cột mới có dữ liệu dài.
     */
    public function up(): void
    {
        Schema::table('plan_master_infor_parkaging_history', function (Blueprint $table) {
            $table->text('old_value')->nullable()->change();
            $table->text('new_value')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('plan_master_infor_parkaging_history', function (Blueprint $table) {
            $table->string('old_value', 255)->nullable()->change();
            $table->string('new_value', 255)->nullable()->change();
        });
    }
};
