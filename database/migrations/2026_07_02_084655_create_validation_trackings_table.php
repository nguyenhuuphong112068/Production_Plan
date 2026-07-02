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
        Schema::create('validation_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('MatID', 50);
            $table->string('MaterialName', 255);
            $table->string('purpose', 255)->nullable();
            $table->string('CC_num', 20)->nullable();
            $table->string('status', 50)->default('Chờ phê duyệt'); // Chờ phê duyệt, Đang theo dõi, Hoàn thành, Ngưng theo dõi
            $table->text('note')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->boolean('approved')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_tracking');
    }
};
