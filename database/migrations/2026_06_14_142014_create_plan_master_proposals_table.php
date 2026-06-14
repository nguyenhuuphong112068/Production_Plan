<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanMasterProposalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_master_proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('plan_master_id');
            $table->string('type'); // 'KCS' or 'NL_BB'
            $table->string('action'); // 'PROPOSE', 'ACCEPT', 'REJECT'
            $table->string('field_name')->nullable(); // e.g., 'after_weigth_date'
            $table->date('old_date')->nullable();
            $table->date('new_date')->nullable();
            $table->text('reason')->nullable(); // Reason for rejection or other note
            $table->unsignedInteger('user_id'); // ID of the user performing the action
            $table->timestamps();

            // Foreign keys if necessary
            // $table->foreign('plan_master_id')->references('id')->on('plan_master')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plan_master_proposals');
    }
}
