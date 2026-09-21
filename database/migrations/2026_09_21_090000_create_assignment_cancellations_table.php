<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('assignment_cancellations', function (Blueprint $table) {
            $table->id();
            $table->string('stage_plan_id');
            $table->string('deparment_code', 10);
            $table->string('stage_groups_code', 20);
            $table->date('reported_date')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->timestamps();

            $table->unique(['stage_plan_id', 'deparment_code', 'stage_groups_code'], 'assignment_cancellations_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_cancellations');
    }
};
