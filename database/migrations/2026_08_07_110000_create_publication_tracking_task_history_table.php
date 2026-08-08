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
        // Ai sửa nội dung gần nhất, để hiện ngay trên badge
        Schema::table('publication_tracking_task', function (Blueprint $table) {
            $table->string('updated_by', 100)->nullable()->after('created_by');
        });

        // Lịch sử thay đổi của "Nội dung theo dõi".
        // Cố ý KHÔNG đặt khoá ngoại tới task: xoá công việc thì vết lịch sử vẫn phải còn.
        Schema::create('publication_tracking_task_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('period_id');
            $table->string('action', 20);                       // create | update | detach | delete
            $table->text('old_content')->nullable();
            $table->text('new_content')->nullable();
            $table->unsignedBigInteger('detail_id')->nullable(); // mã bị gỡ (action = detach)
            $table->string('detail_code', 40)->nullable();       // snapshot mã để xem lại sau khi mã bị xoá
            $table->unsignedSmallInteger('affected_count')->nullable(); // số mã được gán khi tạo mới
            $table->string('changed_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['task_id', 'id'], 'pt_task_history_task');
            $table->index('period_id', 'pt_task_history_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_tracking_task_history');

        Schema::table('publication_tracking_task', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
    }
};
