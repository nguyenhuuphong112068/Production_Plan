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
        Schema::create('stage_plan_temp_list', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); 
            $table->boolean('select')->default(false);
            $table->string('deparment_code', 5);
            $table->boolean('send');
            $table->string ('prepared_by',100);
            $table->timestamps();
        });

        Schema::create('stage_plan_temp', function (Blueprint $table) {

            $table->id();
            $table->unsignedInteger('stage_plan_id');
            $table->unsignedInteger('stage_plan_temp_list_id');


            $table->unsignedInteger('plan_list_id');
            $table->unsignedInteger('plan_master_id');
            $table->unsignedInteger('product_caterogy_id');

            $table->string('predecessor_code',20)->nullable();
            $table->string('campaign_code',20)->nullable();
            $table->string('code',512)->nullable();
            $table->unsignedInteger('order_by')->nullable();

            $table->boolean('schedualed')->default(false);
            $table->boolean('finished')->default(false);
            $table->boolean('active')->default(true);
            $table->tinyInteger('stage_code');

            $table->string('title',512)->nullable();
            $table->dateTime('start')->nullable();
            $table->dateTime('end')->nullable();
            $table->unsignedInteger('resourceId')->nullable();
            
            $table->string('title_clearning',512)->nullable();
            $table->dateTime('start_clearning')->nullable();
            $table->dateTime('end_clearning')->nullable();
            
            $table->float('quarantine_time')->nullable();
            	
            $table->string('schedualed_by',512)->nullable();
            $table->dateTime('schedualed_at')->nullable();
            $table->string('note',255)->nullable();   
            $table->float('yields')->nullable(); 
            $table->string('deparment_code', 5); 

            $table->dateTime('created_date')->nullable();
            $table->string('created_by',100)->nullable();
            $table->dateTime('finished_date')->nullable();
            $table->string('finished_by',100)->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_plan_temp_list');
        Schema::dropIfExists('stage_plan_temp');
    }
};
