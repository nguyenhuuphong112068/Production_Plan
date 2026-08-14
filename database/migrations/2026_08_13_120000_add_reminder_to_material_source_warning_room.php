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
        Schema::table('material_source_warning_room', function (Blueprint $table) {
            // Nội dung nhắc nhở của riêng thiết bị đó, vd: "Khử ẩm".
            // Hiện lên lịch sản xuất khi lô được xếp đúng thiết bị này.
            $table->string('reminder', 255)->nullable()->after('room_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_source_warning_room', function (Blueprint $table) {
            $table->dropColumn('reminder');
        });
    }
};
