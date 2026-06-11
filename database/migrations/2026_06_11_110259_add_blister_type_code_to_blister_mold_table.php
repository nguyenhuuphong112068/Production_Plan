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
            $table->tinyInteger('blister_type_code')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blister_mold', function (Blueprint $table) {
            $table->dropColumn('blister_type_code');
        });
    }
};
