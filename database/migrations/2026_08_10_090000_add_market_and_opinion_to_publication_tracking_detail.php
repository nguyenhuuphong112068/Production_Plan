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
        Schema::table('publication_tracking_detail', function (Blueprint $table) {
            // Thị trường của mã TP (tab BPR), snapshot từ danh mục thành phẩm.
            // Mã BTP không có thị trường nên cột này để trống ở tab BMR.
            $table->string('market', 100)->nullable()->after('dosage_name');

            // Ý kiến của dược sĩ phụ trách về các nội dung theo dõi của mã,
            // 1 ô cho cả mã giống cột Quyết Định / Kết Quả Thực Hiện.
            $table->text('pharmacist_opinion')->nullable()->after('comment');
            $table->string('opinion_by', 100)->nullable()->after('pharmacist_opinion');
            $table->timestamp('opinion_at')->nullable()->after('opinion_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_tracking_detail', function (Blueprint $table) {
            $table->dropColumn(['market', 'pharmacist_opinion', 'opinion_by', 'opinion_at']);
        });
    }
};
