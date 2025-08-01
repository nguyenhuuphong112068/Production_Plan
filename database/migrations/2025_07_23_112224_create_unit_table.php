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
        Schema::create('unit', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name',20)->unique(); 
            $table->string('prepareBy', 100);
            $table->boolean ('active')->default(true);
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('unit', function (Blueprint $table) {
            Schema::dropIfExists('unit');
        });
    }
};
