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
        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name', 50);
            $table->string('stage', 50);
            $table->unsignedSmallInteger('stage_code');
            $table->string('production_group', 50);
            $table->boolean('active')->default(true);
            $table->tinyInteger('order_by')->nullable();
            $table->unsignedTinyInteger('AHU_group')->nullable();
            $table->string('deparment_code',5);
            $table->string('prepareBy');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room');
    }
};
