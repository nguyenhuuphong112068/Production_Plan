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
        Schema::table('room', function (Blueprint $table) {
            $table->tinyInteger('blister_type_code')->nullable()->after('stage_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room', function (Blueprint $table) {
            $table->dropColumn('blister_type_code');
        });
    }
};
