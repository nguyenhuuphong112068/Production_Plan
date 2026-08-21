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
        // Cờ "Hồ sơ sẵn sàng" - dược sĩ phụ trách tự tick khi hồ sơ lô của mã đã ổn,
        // không phụ thuộc nội dung theo dõi / quyết định có hay không. Mặc định false:
        // mã nào chưa được ai xác nhận thì tính là hồ sơ chưa sẵn sàng (xem
        // ProductionPlanController::publicationTrackingSettled).
        Schema::table('publication_tracking_detail', function (Blueprint $table) {
            $table->boolean('ready')->default(false)->after('opinion_at');
            $table->string('ready_by', 100)->nullable()->after('ready');
            $table->timestamp('ready_at')->nullable()->after('ready_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_tracking_detail', function (Blueprint $table) {
            $table->dropColumn(['ready', 'ready_by', 'ready_at']);
        });
    }
};
