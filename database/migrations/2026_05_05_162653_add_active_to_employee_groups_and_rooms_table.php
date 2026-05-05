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
        Schema::table('employee_groups', function (Blueprint $table) {
            $table->boolean('active')->default(1)->after('group_id');
        });
        Schema::table('employee_rooms', function (Blueprint $table) {
            $table->boolean('active')->default(1)->after('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_groups', function (Blueprint $table) {
            $table->dropColumn('active');
        });
        Schema::table('employee_rooms', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
