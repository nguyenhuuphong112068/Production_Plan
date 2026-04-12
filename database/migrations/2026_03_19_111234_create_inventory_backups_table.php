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
        Schema::create('inventory_backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_list_id');
            $table->string('mat_id', 50);
            $table->string('grn_no', 100)->nullable();
            $table->string('mfg_batch_no', 100)->nullable();
            $table->string('ar_no', 100)->nullable();
            $table->string('expiry_date')->nullable();
            $table->string('retest_date')->nullable();
            $table->string('mat_uom', 20)->nullable();
            $table->string('grn_sts', 50)->nullable();
            $table->string('mfg', 255)->nullable();
            $table->string('qc_sts', 50)->nullable();
            $table->decimal('receipt_quantity', 18, 4)->default(0);
            $table->decimal('total_qty', 18, 4)->default(0);
            $table->text('warehouse_list')->nullable();
            $table->text('coa_list')->nullable();
            $table->timestamps();

            $table->index('plan_list_id');
            $table->index('mat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_backups');
    }
};
