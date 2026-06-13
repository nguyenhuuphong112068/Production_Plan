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
        Schema::table('blister_mold', function (Blueprint $table) {
            $table->string('blister_type_code', 255)->nullable()->change();
        });

        Schema::table('blister_mold_history', function (Blueprint $table) {
            $table->string('blister_type_code', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blister_mold', function (Blueprint $table) {
            $table->tinyInteger('blister_type_code')->nullable()->change();
        });

        Schema::table('blister_mold_history', function (Blueprint $table) {
            $table->tinyInteger('blister_type_code')->nullable()->change();
        });
    }
};
