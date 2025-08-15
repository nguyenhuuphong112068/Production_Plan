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
        Schema::create('source_material', function (Blueprint $table) {
            $table->id();
            $table->string('intermediate_code');
            $table->string('code', 30);
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->string ('prepared_by',100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_material');

    }
};
