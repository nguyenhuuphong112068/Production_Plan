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
        Schema::create('quota', function (Blueprint $table) {

            $table->id();
            $table->string('intermediate_code',20);
            $table->string('finished_product_code',20);
            $table->unsignedInteger('room_id');

            $table->string('p_time');
            $table->string('m_time');
            $table->string('C1_time');
            $table->string('C2_time');
            $table->tinyInteger('stage_code');
            $table->tinyInteger('maxofbatch_campaign');
            $table->string('note');

            $table->boolean('active')->default(true);
            $table->string ('prepared_by',100);
            $table->timestamps();

        });

        Schema::create('plan_list', function (Blueprint $table) {
            $table->id();

            $table->string('plan_code', 20);
            $table->string('title', 20);
            $table->string ('prepared_by',100);
            $table->string ('send_by',100);
            $table->date ('send_date');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        
        Schema::create('plan_master', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('plan_list_id');
            $table->unsignedInteger('product_caterogy_id');

            $table->tinyInteger('level');
            $table->string ('batch',10);
            $table->date ('expected_date');
            $table->boolean ('is_val');
            $table->date ('after_weigth_date')->nullable();
            $table->date ('before_weigth_date')->nullable();
            $table->date ('after_parkaging_date')->nullable();
            $table->date ('before_parkaging_date')->nullable();
            $table->text ('material_source')->nullable();
            $table->boolean ('only_parkaging');
            $table->float ('percent_parkaging');
            $table->text ('note');
            	

            
            $table->boolean('active')->default(true);
            $table->string ('prepared_by',100);
            $table->timestamps();

            //$table->foreign('plan_code')->references('plan_code')->on('plan_list'); 

        });

        Schema::create('stage_plan', function (Blueprint $table) {

            $table->id();
            $table->unsignedInteger('plan_list_id');
            $table->unsignedInteger('plan_master_id');
            $table->unsignedInteger('product_caterogy_id');


            $table->boolean('schedualed')->default(false);
            $table->boolean('finished')->default(false);
            $table->boolean('active')->default(true);
            $table->tinyInteger('stage_code');

            $table->string('title',512);
            $table->dateTime('start');
            $table->dateTime('end');
            $table->unsignedInteger('resourceId');
    
            //$table->foreign('plan_code')->references('plan_code')->on('plan_list'); 

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_plan');
        Schema::dropIfExists('plan_master');
        Schema::dropIfExists('plan_list');
        Schema::dropIfExists('quota');
    }
};
