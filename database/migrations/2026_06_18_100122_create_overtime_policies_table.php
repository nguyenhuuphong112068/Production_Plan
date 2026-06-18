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
        Schema::create('overtime_policies', function (Blueprint $table) {
            $table->id();
            $table->string('production_code', 50)->comment('Mã phân xưởng (VD: PXV1)');
            $table->integer('group_id')->nullable()->comment('Mã tổ, null nếu áp dụng cho toàn xưởng');
            $table->integer('max_personnel_per_day')->default(0)->comment('Số người tối đa tăng ca/ngày');
            $table->float('max_hours_per_day')->default(0)->comment('Số giờ tối đa tăng ca/ngày');
            $table->boolean('active')->default(1)->comment('Trạng thái: 1=đang áp dụng, 0=lịch sử');
            $table->string('created_by', 100)->nullable()->comment('Người thiết lập (Username)');
            $table->timestamps();
            
            // Indexes for fast lookup
            $table->index(['production_code', 'active']);
            $table->index(['production_code', 'group_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_policies');
    }
};
