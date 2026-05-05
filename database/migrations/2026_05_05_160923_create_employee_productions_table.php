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
        Schema::create('employee_productions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employees_id');
            $table->string('production_code'); // PXV1, PXV2, PXVH, PXTN, PXDN
            $table->boolean('is_main')->default(false); // Trực thuộc chính (từ API)
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('employees_id')->references('id')->on('employees')->onDelete('cascade');
        });

        // Chuyển dữ liệu cũ sang bảng mới
        $employees = DB::table('employees')->get();
        foreach ($employees as $emp) {
            if (!empty($emp->deparment_code)) {
                DB::table('employee_productions')->insert([
                    'employees_id' => $emp->id,
                    'production_code' => $emp->deparment_code,
                    'is_main' => true,
                    'active' => true,
                    'created_at' => now(),
                    'created_by' => 'System Migration'
                ]);
            }
        }

        // Xóa cột cũ ở bảng employees
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('deparment_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Khôi phục cột cũ
        if (!Schema::hasColumn('employees', 'deparment_code')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('deparment_code')->nullable();
            });
        }

        // Khôi phục dữ liệu từ các bản ghi chính (is_main = 1)
        $mainProductions = DB::table('employee_productions')->where('is_main', true)->get();
        foreach ($mainProductions as $mp) {
            DB::table('employees')->where('id', $mp->employees_id)->update([
                'deparment_code' => $mp->production_code
            ]);
        }

        Schema::dropIfExists('employee_productions');
    }
};
