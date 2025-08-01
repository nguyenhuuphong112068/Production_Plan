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
        Schema::create('product_name', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string ('shortName' , 255);
            $table->string ('productType', 100);
            $table->string('prepareBy');
            $table->boolean ('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_name', function (Blueprint $table) {
            Schema::dropIfExists('product_name');
        });
    }
};
