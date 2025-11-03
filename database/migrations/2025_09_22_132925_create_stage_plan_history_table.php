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

        Schema::create('stage_plan_history', function (Blueprint $table) {

            $table->id();
            $table->unsignedInteger('plan_list_id');
            $table->unsignedInteger('plan_master_id');
            $table->unsignedInteger('product_caterogy_id');
            $table->string('predecessor_code',20)->nullable();
            $table->string('nextcessor_code',20)->nullable();
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
            $table->string('required_room_code',20)->nullable();
            
            $table->string('title_clearning',512)->nullable();
            $table->dateTime('start_clearning')->nullable();
            $table->dateTime('end_clearning')->nullable();
            $table->boolean('scheduling_direction')->nullable();
            $table->boolean('tank')->default(false);
            $table->boolean('keep_dry')->default(false);
            $table->unsignedTinyInteger('AHU_group')->default(0);
            	
            $table->string('schedualed_by',100)->nullable();
            $table->dateTime('schedualed_at')->nullable();
            $table->unsignedSmallInteger('version');
            $table->string('type_of_change',255)->nullable();
            $table->string('note',255)->nullable();   
    
            
            $table->string('deparment_code', 5); 

            $table->dateTime('created_date')->nullable();
            $table->string('created_by',100)->nullable();

            $table->index('finished', 'idx_finished');
            $table->index('deparment_code', 'idx_department');
            $table->index('plan_master_id', 'idx_plan_master');
            $table->index('stage_code', 'idx_stage_code');
            $table->index('start', 'idx_start');
            $table->index(['start', 'end'], 'idx_start_end');
            $table->index(['start_clearning', 'end_clearning'], 'idx_start_end_cleaning');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_plan_history');
    }
};
