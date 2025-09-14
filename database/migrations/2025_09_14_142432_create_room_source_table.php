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
        Schema::create('room_source', function (Blueprint $table) {
            $table->string('intermediate_code',30);
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('source_id');
            $table->primary(['room_id', 'source_id', 'intermediate_code']);
            $table->string ('prepared_by',100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_source');
    }
};
