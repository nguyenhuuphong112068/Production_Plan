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
        Schema::create('overtime_approvals', function (Blueprint $table) {
            $table->id();
            $table->date('reported_date');
            $table->string('production_code');
            $table->string('group_code')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamps();

            $table->unique(['reported_date', 'production_code', 'group_code'], 'overtime_approvals_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_approvals');
    }
};
