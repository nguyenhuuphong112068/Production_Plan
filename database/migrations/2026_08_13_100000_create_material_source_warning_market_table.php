<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Trước đây mỗi dòng ma trận chỉ khai được 1 thị trường nên cùng 1 mã BTP - mã NL
        // phải khai lặp lại nhiều dòng. Tách thị trường ra bảng riêng để 1 dòng khai được
        // nhiều thị trường (VN, HK, HK (Tender)...) đúng như ma trận giấy đang dùng.
        Schema::create('material_source_warning_market', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warning_id');
            $table->unsignedBigInteger('market_id');
            // Kênh/khách hàng của thị trường, vd: Tender. Bảng market chỉ có mã quốc gia ISO
            // nên biến thể "HK (Tender)" được lưu ở đây thay vì thêm mã ảo vào danh mục.
            $table->string('channel', 50)->default('');
            $table->timestamps();

            $table->unique(['warning_id', 'market_id', 'channel'], 'msw_market_unique');

            $table->foreign('warning_id', 'msw_market_warning_fk')
                ->references('id')
                ->on('material_source_warning')
                ->cascadeOnDelete();
        });

        // Chuyển dữ liệu đã khai theo cấu trúc cũ sang bảng mới
        $rows = DB::table('material_source_warning')
            ->select('id', 'market_id')
            ->whereNotNull('market_id')
            ->get()
            ->map(fn($row) => [
                'warning_id' => $row->id,
                'market_id' => $row->market_id,
                'channel' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if ($rows) {
            DB::table('material_source_warning_market')->insert($rows);
        }

        Schema::table('material_source_warning', function (Blueprint $table) {
            // Unique cũ có market_id nên phải bỏ trước khi xoá cột
            $table->dropUnique('msw_unique_matrix');
            $table->dropColumn('market_id');
            $table->index(['deparment_code', 'intermediate_code', 'material_code'], 'msw_matrix_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_source_warning', function (Blueprint $table) {
            $table->dropIndex('msw_matrix_lookup');
            $table->unsignedBigInteger('market_id')->default(0)->after('material_name');
        });

        // Cấu trúc cũ chỉ chứa được 1 thị trường nên chỉ giữ lại thị trường đầu tiên
        foreach (DB::table('material_source_warning')->select('id')->get() as $warning) {
            $marketId = DB::table('material_source_warning_market')
                ->where('warning_id', $warning->id)
                ->orderBy('market_id')
                ->value('market_id');

            DB::table('material_source_warning')
                ->where('id', $warning->id)
                ->update(['market_id' => $marketId ?: 0]);
        }

        Schema::table('material_source_warning', function (Blueprint $table) {
            $table->unique(
                ['deparment_code', 'intermediate_code', 'material_code', 'market_id'],
                'msw_unique_matrix'
            );
        });

        Schema::dropIfExists('material_source_warning_market');
    }
};
