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
        Schema::create('plan_master_history', function (Blueprint $table) {

            $table->id();
            $table->unsignedInteger('plan_list_id');
            $table->unsignedInteger('product_caterogy_id');
            $table->unsignedInteger('plan_master_id');
            $table->tinyInteger('version');

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
            $table->string ('reason',255);
            $table->string('deparment_code',5);
            
            $table->string ('prepared_by',100);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_master_history');
    }
};
