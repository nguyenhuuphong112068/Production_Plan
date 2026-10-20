<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Khai báo sản phẩm mà nhân viên vận hành được phép thực hiện.
 *
 * - product_type = 'BTP': product_code là intermediate_category.intermediate_code, áp cho công đoạn 1-6.
 * - product_type = 'TP' : product_code là finished_product_category.finished_product_code, áp cho công đoạn 7.
 *
 * Phòng được làm không lưu ở đây mà suy ra = phòng của NV (employee_assignments.room_id)
 * ∩ phòng định mức của sản phẩm (quota.room_id).
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('employee_products')) {
            return;
        }

        Schema::create('employee_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employees_id');
            $table->string('product_type', 3);
            $table->string('product_code', 20);
            $table->boolean('active')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['employees_id', 'product_type', 'product_code'], 'employee_products_unique');
            $table->index(['product_type', 'product_code'], 'employee_products_product_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_products');
    }
};
