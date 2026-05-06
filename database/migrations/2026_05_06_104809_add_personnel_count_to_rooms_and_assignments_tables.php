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
            $table->tinyInteger('number_of_employes_on_sheet1')->default(0)->after('group_code');
            $table->tinyInteger('number_of_employes_on_sheet2')->default(0)->after('number_of_employes_on_sheet1');
            $table->tinyInteger('number_of_employes_on_sheet3')->default(0)->after('number_of_employes_on_sheet2');
            $table->tinyInteger('number_of_employes_on_sheet4')->default(0)->after('number_of_employes_on_sheet3');
            $table->tinyInteger('number_of_employes_on_sheet_regular')->default(0)->after('number_of_employes_on_sheet4');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->tinyInteger('number_of_employes')->default(0)->after('Job_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room', function (Blueprint $table) {
            $table->dropColumn([
                'number_of_employes_on_sheet1',
                'number_of_employes_on_sheet2',
                'number_of_employes_on_sheet3',
                'number_of_employes_on_sheet4',
                'number_of_employes_on_sheet_regular'
            ]);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('number_of_employes');
        });
    }
};
