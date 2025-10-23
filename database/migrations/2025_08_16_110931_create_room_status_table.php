<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     **/
    public function up(): void
    {
        Schema::create('room_status', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('room_id'); 
            $table->unsignedTinyInteger('status')->nullable();
            $table->string('in_production',255)->nullable(); 
            $table->string('notification',255)->nullable();
            $table->string('created_by',100)->nullable();
            $table->timestamps(); // created_at + updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_status');
    }
};
