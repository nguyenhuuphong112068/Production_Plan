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
        Schema::create('explanation', function (Blueprint $table) {
            
            $table->id();
            $table->date('reported_date');
            $table->unsignedTinyInteger('stage_code');
            $table->text('content');
            $table->string('created_by');
            $table->timestamps();

            // Tạo unique key kết hợp reported_date và stage_code
            $table->unique(['reported_date', 'stage_code'], 'unique_report_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('explanation');
    }
};
