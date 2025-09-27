<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /** Run the migrations.*/
    public function up(): void
    {    
        Schema::create('quota', function (Blueprint $table) {

            $table->id();
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

        Schema::create('plan_list', function (Blueprint $table) {
            $table->id();
            //$table->string('plan_code', 20);
            $table->string('name', 50);
            $table->unsignedSmallInteger('month');
            $table->string('deparment_code', 5);
            $table->boolean('type');
            $table->string ('prepared_by',100);
            $table->boolean ('send');
            $table->string ('send_by',100)->nullable();
            $table->date ('send_date')->nullable();
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
            $table->unsignedInteger ('material_source_id')->nullable();
            $table->boolean ('only_parkaging');
            $table->float ('percent_parkaging');
            $table->text ('note');
            $table->string('deparment_code',5);
            $table->boolean('active')->default(true);
            $table->boolean('cancel')->default(false);
            $table->string ('prepared_by',100);
            $table->timestamps();

        });

        Schema::create('stage_plan', function (Blueprint $table) {

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
            
            $table->string('title_clearning',512)->nullable();
            $table->dateTime('start_clearning')->nullable();
            $table->dateTime('end_clearning')->nullable();
            $table->boolean('scheduling_direction')->nullable();
            
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
        Schema::dropIfExists('stage_plan');
        Schema::dropIfExists('plan_master');
        Schema::dropIfExists('plan_list');
        Schema::dropIfExists('quota');
    }
};
