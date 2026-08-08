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
        // Chi tiết theo dõi lên ấn bản của từng mã BTP / TP còn hiệu lực trong kỳ.
        // Các cột code / product_name / batch_size / dosage_name là ảnh chụp (snapshot)
        // tại thời điểm đồng bộ để kỳ cũ không bị đổi số liệu khi danh mục thay đổi.
        Schema::create('publication_tracking_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('period_id');
            $table->string('category_type', 3);              // BTP | TP
            $table->unsignedBigInteger('category_id');
            $table->string('code', 40);
            $table->string('process_code', 40)->nullable();   // Mã quy trình (chỉ có ở TP)
            $table->string('product_name', 255)->nullable();
            $table->string('batch_size', 100)->nullable();
            $table->string('dosage_name', 100)->nullable();

            $table->string('pharmacist', 255)->nullable();    // Dược sĩ phụ trách
            $table->text('tracking_content')->nullable();     // Nội dung theo dõi
            $table->text('decision')->nullable();             // Quyết định
            $table->text('result')->nullable();               // Kết quả thực hiện

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('period_id', 'fk_pt_detail_period_id')
                ->references('id')->on('publication_tracking_period')->onDelete('cascade');

            $table->unique(['period_id', 'category_type', 'category_id'], 'pt_detail_period_category');
            $table->index(['period_id', 'category_type'], 'pt_detail_period_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_tracking_detail');
    }
};
