
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
            Schema::create('product_category', function (Blueprint $table) {

            $table->id();
            $table->string('code', 20);
            $table->string('name',255); 

            $table->string('testing_code',50)->unique(); 
            $table->string('testing',50); 
            

            $table->float('sample_Amout'); 
            $table->string('unit',20);
            

            $table->float('excution_time');   

            $table->string('instrument_type')->references('instrument_type')->on('instrument');             

            $table->boolean ('active')->default(true);
            $table->string('prepareBy', 100);
            $table->timestamps();

            $table->foreign('testing')->references('name')->on('testing');
            $table->foreign('unit')->references('name')->on('unit');
            $table->foreign('name')->references('name')->on('product_name');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_category', function (Blueprint $table) {
            Schema::dropIfExists('product_category');
        });
 
    }
};
