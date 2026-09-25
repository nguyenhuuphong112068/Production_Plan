<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mỗi phân xưởng 1 dòng: is_scheduling = 1 khi đang chạy sắp lịch tự động,
     * trong lúc đó các máy khác cùng phân xưởng không được xác nhận hoàn thành.
     */
    public function up()
    {
        Schema::create('scheduling_locks', function (Blueprint $table) {
            $table->id();
            $table->string('deparment_code', 10)->unique();
            $table->boolean('is_scheduling')->default(false);
            $table->string('started_by')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scheduling_locks');
    }
};
