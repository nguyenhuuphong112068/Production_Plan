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
        Schema::create('intermediate_category', function (Blueprint $table) {
            $table->id();
            $table->string('intermediate_code',20)->unique();
            $table->unsignedMediumInteger('product_name_id');
            $table->float('batch_size');
            $table->string('unit_batch_size',10);
            $table->float('batch_qty');
            $table->string('unit_batch_qty',10);
            $table->unsignedSmallInteger('dosage_id');
            $table->boolean('weight_1');
            $table->boolean('weight_2');
            $table->boolean('prepering');
            $table->boolean('blending');
            $table->boolean('forming');
            $table->boolean('coating');

            $table->float('quarantine_total')->nullable();
            $table->float('quarantine_weight')->nullable();
            $table->float('quarantine_preparing')->nullable();
            $table->float('quarantine_blending')->nullable();
            $table->float('quarantine_forming')->nullable();
            $table->float('quarantine_coating')->nullable();
            $table->boolean('quarantine_time_unit');

            $table->string('deparment_code',5);

            $table->boolean('active')->default(true);
            $table->string ('prepared_by',100);
            $table->timestamps();
        });

        Schema::create('finished_product_category', function (Blueprint $table) {
            $table->id();	
            $table->string('process_code',40)->unique();
            $table->string('finished_product_code',20);
            $table->string('intermediate_code',20);
            $table->unsignedMediumInteger('product_name_id');

            $table->unsignedMediumInteger('market_id');
            $table->unsignedMediumInteger('specification_id');

            $table->float('batch_qty');
            $table->string('unit_batch_qty',10);
     
            $table->boolean('primary_parkaging');
            $table->boolean('secondary_parkaging');
    
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
        Schema::dropIfExists('finished_product_category');
        Schema::dropIfExists('intermediate_category');
    }
};
