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
        Schema::create('blister_mold', function (Blueprint $table) {
            $table->id();
            $table->string('code', 15)->unique();
            $table->string('name', 255);
            $table->boolean('active')->default(true);
            $table->string('created_by', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blister_mold');
    }
};
