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
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->tinyInteger('priority_level')->nullable()->default(1)->after('active')->comment('Priority level of the room assignment (1 is highest)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->dropColumn('priority_level');
        });
    }
};
