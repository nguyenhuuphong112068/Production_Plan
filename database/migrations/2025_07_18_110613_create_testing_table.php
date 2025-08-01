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
        Schema::create('testing', function (Blueprint $table) {
           $table->id();
            $table->string('name', 100)->unique();
            $table->string('shortName', 50);
            $table->boolean('active')->default(true);
            $table->string('prepareBy');
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testing', function (Blueprint $table) {
            Schema::dropIfExists('testing');
        });
    }
};
