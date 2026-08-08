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
        // Ma trận cảnh báo nguồn NL: mỗi dòng khai báo 1 mã BTP khi sử dụng 1 mã NL
        // (lấy từ BOM ấn bản mới nhất trên MMS) thì chỉ được sản xuất cho 1 thị trường,
        // trên các thiết bị đã khai báo ở bảng material_source_warning_room.
        Schema::create('material_source_warning', function (Blueprint $table) {
            $table->id();
            $table->string('intermediate_code', 20);
            $table->string('material_code', 50);
            // Tên NL tại thời điểm khai báo - chỉ để hiển thị khi mất kết nối MMS.
            $table->string('material_name', 255)->nullable();
            $table->unsignedBigInteger('market_id');
            // Ấn bản BOM trên MMS tại thời điểm khai báo, để biết ma trận dựa trên công thức nào.
            $table->unsignedSmallInteger('bom_revision')->nullable();
            $table->text('note')->nullable();
            $table->string('deparment_code', 5);
            $table->boolean('active')->default(1);
            $table->string('prepared_by', 100)->nullable();
            $table->timestamps();

            $table->unique(
                ['deparment_code', 'intermediate_code', 'material_code', 'market_id'],
                'msw_unique_matrix'
            );
            $table->index('intermediate_code', 'msw_intermediate_code');
            $table->index('material_code', 'msw_material_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_source_warning');
    }
};
