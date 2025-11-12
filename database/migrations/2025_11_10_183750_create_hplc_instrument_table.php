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
        Schema::create('hplc_instrument', function (Blueprint $table) {
            $table->id();

            $table->string('code',20);
            $table->string('name',30);
            $table->string('created_by',100);

            $table->timestamps();
        });

    Schema::create('hplc_status', function (Blueprint $table) {

        $table->id();
        $table->unsignedTinyInteger('ins_id')->nullable();
        $table->string('column', 20)->nullable(); 
        $table->string('analyst', 100)->nullable(); 
        $table->string('sample_name', 255)->nullable();
        $table->string('batch_no', 100)->nullable(); 
        $table->string('stage', 100)->nullable(); 
        $table->string('test', 100)->nullable(); 
        $table->string('notes', 255)->nullable(); 
        $table->string('remark', 255)->nullable(); 
        $table->dateTime('start_time')->nullable();
        $table->dateTime('end_time')->nullable();
        $table->timestamps();

    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hplc_instrument');
        Schema::dropIfExists('hplc_status');
    }
};
