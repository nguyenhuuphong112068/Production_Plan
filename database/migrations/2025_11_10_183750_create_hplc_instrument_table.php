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

        // Sample 1
        $table->string('sample_1')->nullable();
        $table->datetime('sample_start_1')->nullable();
        $table->datetime('sample_end_1')->nullable();

        $table->string('actual_1')->nullable();
        $table->datetime('actual_start_1')->nullable();
        $table->datetime('actual_end_1')->nullable();
        $table->string('analyst_1', 100)->nullable();
        $table->timestamp('update_time_1')->nullable();

        // Sample 2
        $table->string('sample_2')->nullable();
        $table->datetime('sample_start_2')->nullable();
        $table->datetime('sample_end_2')->nullable();

        $table->string('actual_2')->nullable();
        $table->datetime('actual_start_2')->nullable();
        $table->datetime('actual_end_2')->nullable();
        $table->string('analyst_2', 100)->nullable();
        $table->timestamp('update_time_2')->nullable();

        $table->string('created_by', 100)->nullable();
        $table->timestamp('created_at')->nullable();
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
