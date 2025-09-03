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
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('code');
            $table->string('name', 50);
            $table->string('create_by',100);
            $table->timestamps();
        });
        Schema::create('stage_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('code');
            $table->string('name', 50);
            $table->string('create_by',100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_groups');
        Schema::dropIfExists('stage');
    }
};
