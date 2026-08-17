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
            if (!Schema::hasColumn('employees', 'on_long_leave')) {
                $table->boolean('on_long_leave')->default(0)->after('on_maternity_leave')->comment('Tình trạng nghỉ phép dài hạn');
            }
            if (!Schema::hasColumn('employees', 'long_leave_updated_by')) {
                $table->string('long_leave_updated_by')->nullable();
            }
            if (!Schema::hasColumn('employees', 'long_leave_updated_at')) {
                $table->timestamp('long_leave_updated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['on_long_leave', 'long_leave_updated_by', 'long_leave_updated_at']);
        });
    }
};
