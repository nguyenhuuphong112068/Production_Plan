<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calibration_maintenance_assessment', function (Blueprint $table) {
            $table->id();
            // Mỗi dòng công việc bảo trì chỉ có một đánh giá, mở lại modal là sửa đánh giá cũ
            $table->unsignedBigInteger('stage_plan_id')->unique();
            $table->unsignedBigInteger('plan_master_id')->nullable()->index();
            $table->unsignedTinyInteger('star_rating');
            $table->text('comment')->nullable();
            $table->json('employees_code')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibration_maintenance_assessment');
    }
};
