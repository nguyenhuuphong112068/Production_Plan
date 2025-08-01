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
        Schema::create('import', function (Blueprint $table) {
            $table->id();
            $table->string('testing_code', 50);
            $table->foreign('testing_code')->references('testing_code')->on('product_category');

            $table->float('imoported_amount');
            $table->string('batch_no',20);
            $table->string('stage', 30);

            $table->date('experted_date');

            $table->boolean ('scheduled')->default(0);
            $table->boolean ('finished')->default(0);
            $table->boolean ('active')->default(true);

            $table->string('prepareBy', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import', function (Blueprint $table) {
            Schema::dropIfExists('import');
        });
    }
};
