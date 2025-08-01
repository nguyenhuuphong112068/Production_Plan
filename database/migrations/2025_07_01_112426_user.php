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
        Schema::create('userGroup', function (Blueprint $table) {
            $table->id();
            $table->string('name',50)->unique();
            $table->boolean ('active')->default(true);
            $table->string('prepareBy');
            $table->timestamps();
        });

        Schema::create('deparments', function (Blueprint $table) {
            $table->id();
            $table->string('name',50)->unique();
            $table->string('shortName',20)->unique();
            $table->boolean ('active');
            $table->string('prepareBy');
            $table->timestamps();
        });

        Schema::create('user_Management', function (Blueprint $table) {
            $table->id();
            $table->string('userName',10)->unique();
            $table->string('userGroup', 30);
            $table->string('passWord', 255);
            $table->string('fullName', 255);
            $table->string('deparment', 50);
            $table->string('groupName', 50);
            $table->string('mail', 255)->nullable();
            $table->boolean('isLocked')->default(false); 
            $table->boolean('isActive')->default(true);
            $table->date('changePWdate');
            $table->string('hisPW_1', 20)->nullable();
            $table->string('hisPW_2', 20)->nullable();
            $table->string('hisPW_3', 20)->nullable();
            $table->string('prepareBy');
            $table->timestamps();

            $table->foreign('userGroup')->references('name')->on('userGroup'); 
            $table->foreign('deparment')->references('name')->on('deparments'); 

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_Management');
        Schema::dropIfExists('deparments');
        Schema::dropIfExists('userGroup');
    }
};
