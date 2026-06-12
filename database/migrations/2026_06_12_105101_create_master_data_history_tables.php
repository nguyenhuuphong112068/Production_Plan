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
        Schema::create('unit_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('unit_id');
            $table->string('code')->nullable();
            $table->string('name')->nullable(); 
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('stage_groups_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('stage_group_id');
            $table->smallInteger('code')->nullable();
            $table->string('name')->nullable();
            $table->tinyInteger('type')->nullable();
            $table->string('create_by')->nullable();
            $table->timestamps();
        });

        Schema::create('specification_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('specification_id');
            $table->string('name')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('source_material_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('source_material_id');
            $table->string('intermediate_code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('active')->default(true);
            $table->string('prepared_by')->nullable();
            $table->timestamps();
        });

        Schema::create('room_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('room_id');
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('main_equiment_name')->nullable();
            $table->float('capacity')->nullable();
            $table->string('stage')->nullable();
            $table->smallInteger('stage_code')->nullable();
            $table->tinyInteger('blister_type_code')->nullable();
            $table->tinyInteger('sheet_1')->nullable();
            $table->tinyInteger('sheet_2')->nullable();
            $table->tinyInteger('sheet_3')->nullable();
            $table->tinyInteger('sheet_regular')->nullable();
            $table->string('production_group')->nullable();
            $table->tinyInteger('group_code')->nullable();
            $table->tinyInteger('number_of_employes_on_sheet1')->nullable();
            $table->tinyInteger('number_of_employes_on_sheet2')->nullable();
            $table->tinyInteger('number_of_employes_on_sheet3')->nullable();
            $table->tinyInteger('number_of_employes_on_sheet4')->nullable();
            $table->tinyInteger('number_of_employes_on_sheet_regular')->nullable();
            $table->boolean('active')->default(true);
            $table->tinyInteger('order_by')->nullable();
            $table->tinyInteger('AHU_group')->nullable();
            $table->tinyInteger('only_maintenance')->nullable();
            $table->string('deparment_code')->nullable();
            $table->string('prepareBy')->nullable();
            $table->timestamps();
        });

        Schema::create('market_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('market_id');
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('product_name_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_name_id');
            $table->string('name')->nullable();
            $table->string('shortName')->nullable();
            $table->string('productType')->nullable();
            $table->string('deparment_code')->nullable();
            $table->string('prepareBy')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('dosage_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dosage_id');
            $table->string('name')->nullable();
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('blister_type_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('blister_type_id');
            $table->string('name')->nullable();
            $table->integer('code')->nullable();
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('deparments_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('deparment_id');
            $table->string('shortName')->nullable();
            $table->string('name')->nullable();
            $table->string('prepareBy')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('blister_mold_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('blister_mold_id');
            $table->string('code')->nullable();
            $table->tinyInteger('amount')->nullable();
            $table->tinyInteger('blister_type_code')->nullable();
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_history');
        Schema::dropIfExists('stage_groups_history');
        Schema::dropIfExists('specification_history');
        Schema::dropIfExists('source_material_history');
        Schema::dropIfExists('room_history');
        Schema::dropIfExists('market_history');
        Schema::dropIfExists('product_name_history');
        Schema::dropIfExists('dosage_history');
        Schema::dropIfExists('blister_type_history');
        Schema::dropIfExists('deparments_history');
        Schema::dropIfExists('blister_mold_history');
    }
};
