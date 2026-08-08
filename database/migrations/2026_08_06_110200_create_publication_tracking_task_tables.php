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
        // Một "Nội dung theo dõi" (công việc) của kỳ, có thể gán cùng lúc cho nhiều mã BTP / TP.
        Schema::create('publication_tracking_task', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('period_id');
            $table->string('content', 500);
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('period_id', 'fk_pt_task_period_id')
                ->references('id')->on('publication_tracking_period')->onDelete('cascade');

            $table->index('period_id', 'pt_task_period');
        });

        // Công việc gắn vào 1 mã cụ thể: quyết định (Có/Không) + kết quả thực hiện.
        Schema::create('publication_tracking_task_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('detail_id');
            $table->boolean('decision')->nullable();          // 1 = Có, 0 = Không, null = chưa quyết định
            $table->date('due_date')->nullable();             // Ngày hoàn thành dự kiến, chỉ khi quyết định = Có
            $table->date('completed_date')->nullable();       // Ngày hoàn thành thực tế
            $table->text('comment')->nullable();              // Ghi chú kết quả thực hiện
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('task_id', 'fk_pt_task_item_task_id')
                ->references('id')->on('publication_tracking_task')->onDelete('cascade');
            $table->foreign('detail_id', 'fk_pt_task_item_detail_id')
                ->references('id')->on('publication_tracking_detail')->onDelete('cascade');

            $table->unique(['task_id', 'detail_id'], 'pt_task_item_task_detail');
            $table->index('detail_id', 'pt_task_item_detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_tracking_task_item');
        Schema::dropIfExists('publication_tracking_task');
    }
};
