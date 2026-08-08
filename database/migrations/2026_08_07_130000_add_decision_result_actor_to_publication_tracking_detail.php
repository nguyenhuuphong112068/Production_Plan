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
        // "Quyết định" và "Kết quả thực hiện" là 2 thao tác của 2 người khác nhau
        // (người quyết định và người ghi nhận kết quả) nên mỗi cột phải có mốc riêng,
        // cột updated_by chung của dòng không nói được ai đã đụng vào ô nào.
        Schema::table('publication_tracking_detail', function (Blueprint $table) {
            $table->string('decision_by', 100)->nullable()->after('comment');
            $table->timestamp('decision_at')->nullable()->after('decision_by');
            $table->string('result_by', 100)->nullable()->after('decision_at');
            $table->timestamp('result_at')->nullable()->after('result_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_tracking_detail', function (Blueprint $table) {
            $table->dropColumn(['decision_by', 'decision_at', 'result_by', 'result_at']);
        });
    }
};
