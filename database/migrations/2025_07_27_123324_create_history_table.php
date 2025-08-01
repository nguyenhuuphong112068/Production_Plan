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
        Schema::create('history', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('schedual_id');
            $table->unsignedBigInteger('ins_id');
            $table->dateTime('startDate');
            $table->dateTime('endDate');
            $table->string('note', 255)->nullable();
            $table->string('analyst', 100);            
            $table->string('result',20);
            $table->string('relativeReport',255)->nullable();
            $table->string('prepareBy',100);
            $table->timestamps();

            $table->foreign('schedual_id')->references('id')->on('schedules'); 
            $table->foreign('ins_id')->references('id')->on('instrument'); 
            $table->foreign('analyst')->references('fullName')->on('analyst'); 
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history');
    }
};
