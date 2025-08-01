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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imported_id');
            $table->unsignedBigInteger('ins_Id');
            $table->dateTime('startDate');
            $table->dateTime('endDate');
            $table->string('note', 255)->nullable();
            $table->string('analyst', 100);
            $table->boolean ('finished')->default(0);
            $table->boolean ('active')->default(true);
            $table->timestamps(); // created_at & updated_at
            
            $table->foreign('analyst')->references('fullName')->on('analyst'); 
            $table->foreign('ins_id')->references('id')->on('instrument');
             $table->foreign('imported_id')->references('id')->on('import');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
