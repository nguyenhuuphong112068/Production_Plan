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
            $table->string('created_by', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        });
        Schema::table('employee_rooms', function (Blueprint $table) {
            $table->string('created_by', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        });
        Schema::table('employee_productions', function (Blueprint $table) {
            $table->string('created_by', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to revert collation usually, but can be added if needed
    }
};
