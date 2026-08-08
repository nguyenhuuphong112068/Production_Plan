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
            // Dược sĩ phụ trách nay lấy từ danh mục BTP / TP nên chỉ còn là snapshot
            $table->dropColumn(['pharmacist', 'tracking_content', 'decision', 'result']);

            $table->unsignedBigInteger('pharmacist_id')->nullable()->after('dosage_name');
            $table->string('pharmacist_name', 255)->nullable()->after('pharmacist_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_tracking_detail', function (Blueprint $table) {
            $table->dropColumn(['pharmacist_id', 'pharmacist_name']);

            $table->string('pharmacist', 255)->nullable();
            $table->text('tracking_content')->nullable();
            $table->text('decision')->nullable();
            $table->text('result')->nullable();
        });
    }
};
