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
        Schema::table('employee_rooms', function (Blueprint $table) {
            $table->integer('level')->default(1)->after('room_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'level')) {
                $table->dropColumn('level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('level')->nullable();
        });

        Schema::table('employee_rooms', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
