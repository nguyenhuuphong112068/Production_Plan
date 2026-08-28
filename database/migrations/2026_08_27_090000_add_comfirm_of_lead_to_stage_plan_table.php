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
        Schema::table('stage_plan', function (Blueprint $table) {
            if (!Schema::hasColumn('stage_plan', 'comfirm_of_lead')) {
                $table->tinyInteger('comfirm_of_lead')->default(0)->after('overlap');
            }
            if (!Schema::hasColumn('stage_plan', 'comfirm_of_lead_by')) {
                $table->string('comfirm_of_lead_by', 100)->nullable()->after('comfirm_of_lead');
            }
            if (!Schema::hasColumn('stage_plan', 'comfirm_of_lead_at')) {
                $table->dateTime('comfirm_of_lead_at')->nullable()->after('comfirm_of_lead_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stage_plan', function (Blueprint $table) {
            foreach (['comfirm_of_lead_at', 'comfirm_of_lead_by', 'comfirm_of_lead'] as $column) {
                if (Schema::hasColumn('stage_plan', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
