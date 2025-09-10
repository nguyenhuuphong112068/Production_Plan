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
        Schema::create('quota_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('quota_id');
            $table->string('process_code',50)->unique();
            $table->string('intermediate_code',20);
            $table->string('finished_product_code',20);
            $table->unsignedInteger('room_id');

            $table->string('p_time');
            $table->string('m_time');
            $table->string('C1_time');
            $table->string('C2_time');
            $table->unsignedSmallInteger('stage_code');
            $table->unsignedSmallInteger('maxofbatch_campaign'); 
            $table->string('note');
            $table->string('deparment_code',5);

            $table->boolean('active')->default(true);
            $table->string ('prepared_by',100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quota_history');
    }
};
