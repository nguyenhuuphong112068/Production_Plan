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
        Schema::create('auditTrialLog', function (Blueprint $table) {
                $table->id();
                $table->string('userName',10);
                $table->string('action'); 
                $table->string('table_Audit');
                $table->unsignedBigInteger('record_Id_AuditTrial');
                $table->text('old_values')->nullable();
                $table->text('new_values')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->timestamps();

                $table->foreign('userName')->references('userName')->on('user_Management');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditTrialLog', function (Blueprint $table) {
           Schema::dropIfExists('auditTrialLog');
        });
    }
};
