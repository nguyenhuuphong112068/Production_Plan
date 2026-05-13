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
        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('notAssignment', 'hasAssignment');
        });
        // Sau khi rename, cập nhật default và giá trị hiện tại thành 1
        DB::table('employees')->update(['hasAssignment' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('hasAssignment', 'notAssignment');
        });
    }
};
