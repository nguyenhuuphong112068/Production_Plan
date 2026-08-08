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
        // Kỳ theo dõi lên ấn bản: mỗi dòng là 1 chu kỳ 1 tháng tính từ ngày 20
        // tháng này đến ngày 20 tháng sau (start_date = 20/M, end_date = 19/M+1).
        Schema::create('publication_tracking_period', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('deparment_code', 5);
            $table->string('status', 30)->default('Đang mở'); // Đang mở, Đã chốt
            $table->text('note')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->unique(['deparment_code', 'year', 'month'], 'pt_period_dept_year_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_tracking_period');
    }
};
