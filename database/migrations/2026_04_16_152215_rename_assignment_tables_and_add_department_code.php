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
        Schema::rename('maintenance_assignments', 'assignments');
        Schema::rename('maintenance_assignment_personnel', 'assignment_personnel');

        Schema::table('assignments', function (Blueprint $table) {
            $table->string('deparment_code', 50)->nullable()->after('room_id');
            $table->index('deparment_code');
        });

        Schema::table('assignment_personnel', function (Blueprint $table) {
            $table->index('assignment_id');
            $table->index('personnel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('deparment_code');
        });

        Schema::rename('assignments', 'maintenance_assignments');
        Schema::rename('assignment_personnel', 'maintenance_assignment_personnel');
    }
};
