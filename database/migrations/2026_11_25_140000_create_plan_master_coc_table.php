<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Theo dõi xuất kho EU (25/11/2026): Ngày lập phiếu COC do người dùng nhập cho từng lô
 * (1 lô = 1 dòng), kèm vết sửa và quyền cập nhật coc_tracking_update.
 * Server chạy database/sql/2026_11_25_coc_tracking.sql (cùng nội dung).
 */
return new class extends Migration
{
    /** Nhóm quyền của các chức năng theo dõi thuộc tab Kế Hoạch (cùng kcs_tracking_update) */
    private const PERMISSION_GROUP = 15;

    public function up(): void
    {
        if (!Schema::hasTable('plan_master_coc')) {
            Schema::create('plan_master_coc', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('plan_master_id')->unique();
                $table->date('coc_date')->nullable();               // Ngày lập phiếu COC
                $table->string('updated_by', 100)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('plan_master_coc_history')) {
            Schema::create('plan_master_coc_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('plan_master_id')->index();
                $table->string('field', 50);
                $table->string('old_value')->nullable();
                $table->string('new_value')->nullable();
                $table->string('changed_by', 100)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        $now = Carbon::now();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'coc_tracking_update'],
            [
                'permission_group' => self::PERMISSION_GROUP,
                'display_name' => 'Nhập Ngày Lập Phiếu COC',
                'description' => 'Nhập / sửa Ngày Lập Phiếu COC trên trang Theo Dõi Xuất Kho EU (chỉ lô đã xuất kho)',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $permissionId = DB::table('permissions')->where('name', 'coc_tracking_update')->value('id');

        foreach (DB::table('roles')->where('name', 'Admin')->pluck('id') as $roleId) {
            DB::table('role_permission')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                []
            );
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('name', 'coc_tracking_update')->pluck('id');

        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('plan_master_coc_history');
        Schema::dropIfExists('plan_master_coc');
    }
};
