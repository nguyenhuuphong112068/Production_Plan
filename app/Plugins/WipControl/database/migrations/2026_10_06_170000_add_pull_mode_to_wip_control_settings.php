<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plugin Kiểm soát tồn BTP: chế độ "ĐH bao phim theo nhịp BP" theo phân xưởng. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wip_control_settings', function (Blueprint $table) {
            $table->boolean('pull_mode')->default(0)->after('prioritize_non_coated')
                ->comment('1 = lô bao phim PC→ĐH vừa kịp giờ BP, năng lực dư cho lô không bao phim');
            $table->unsignedSmallInteger('pull_buffer_hours')->default(24)->after('pull_mode')
                ->comment('Đệm an toàn: ĐH xong trước giờ BP (đã trừ thời gian chờ) bấy nhiêu giờ');
        });
    }

    public function down(): void
    {
        Schema::table('wip_control_settings', function (Blueprint $table) {
            $table->dropColumn(['pull_mode', 'pull_buffer_hours']);
        });
    }
};
