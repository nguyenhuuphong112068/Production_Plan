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
        Schema::create('analyst', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('fullName',100)->unique(); 
            $table->string('groupName',100); 
            $table->foreign('groupName')->references('name')->on('groups');
            $table->string('prepareBy', 100)->nullable();
            $table->boolean ('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyst', function (Blueprint $table) {
            Schema::dropIfExists('analyst');
        });
    }
};
