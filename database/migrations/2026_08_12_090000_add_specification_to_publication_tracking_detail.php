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
            // Qui cách của mã TP (tab BPR), snapshot từ danh mục thành phẩm và hiển thị
            // chung ô với thị trường. Mã BTP không có qui cách nên để trống ở tab BMR.
            $table->string('specification', 255)->nullable()->after('market');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_tracking_detail', function (Blueprint $table) {
            $table->dropColumn('specification');
        });
    }
};
