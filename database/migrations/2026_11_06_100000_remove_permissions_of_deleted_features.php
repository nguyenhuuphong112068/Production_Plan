<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Gỡ 12 quyền không còn dùng (06/11/2026). Bản SQL tương đương để chạy tay trên server:
 * database/sql/2026_11_05-06_forming_mold_and_cleanup_permissions.sql (phần 3-4).
 *
 * a) Quyền của chức năng đã xoá code:
 *  - layout_MMS        : menu MMS (Tồn kho NL / BB / TP)
 *  - layout_statistics : menu Thống Kê
 *  - layout_status     : menu Trạng Thái (bảng tin trạng thái phòng, HPLC)
 *  - create_intermediate_Hypothesis_category : nút Thêm / Cập Nhật Danh Mục Giả Định
 * b) Quyền còn trong bảng nhưng mã không kiểm tra ở đâu:
 *  - category_maintenance_create, category_maintenance_deActive, plan_maintenance_create,
 *    create_general_notification, layout_feedback, Record_KCS_Date, personnel_assignment,
 *    layout_monthly_report
 * Giữ view_Hypothesis_category: vẫn dùng để xem các mã giả định đã có.
 *
 * Chạy riêng file này (bảng migrations lệch với CSDL, không chạy migrate trần):
 *   php artisan migrate --path=database/migrations/2026_11_06_100000_remove_permissions_of_deleted_features.php
 * up() lưu bản sao (quyền + nhóm quyền đã gán) ra storage/app/migration_backups/; down() khôi phục từ đó.
 */
return new class extends Migration
{
    private const NAMES = [
        'layout_MMS',
        'layout_statistics',
        'layout_status',
        'create_intermediate_Hypothesis_category',
        'category_maintenance_create',
        'category_maintenance_deActive',
        'plan_maintenance_create',
        'create_general_notification',
        'layout_feedback',
        'Record_KCS_Date',
        'personnel_assignment',
        'layout_monthly_report',
    ];

    /** Bản sao trong storage/app để down() khôi phục */
    private const BACKUP = 'migration_backups/permissions_removed_20261106.json';

    public function up(): void
    {
        $permissions = DB::table('permissions')->whereIn('name', self::NAMES)->get();
        if ($permissions->isEmpty()) {
            return;
        }
        $ids = $permissions->pluck('id');

        // Lưu bản sao để down() khôi phục được
        Storage::put(self::BACKUP, json_encode([
            'permissions' => $permissions->map(fn ($p) => (array) $p)->all(),
            'role_permission' => DB::table('role_permission')->whereIn('permission_id', $ids)->get()->map(fn ($r) => (array) $r)->all(),
            'user_permission' => DB::table('user_permission')->whereIn('permission_id', $ids)->get()->map(fn ($r) => (array) $r)->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        DB::transaction(function () use ($ids) {
            DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
            DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        });
    }

    public function down(): void
    {
        $backup = Storage::exists(self::BACKUP) ? json_decode(Storage::get(self::BACKUP), true) : null;
        if (! $backup) {
            return;
        }
        DB::transaction(function () use ($backup) {
            foreach ($backup['permissions'] as $row) {
                DB::table('permissions')->updateOrInsert(['id' => $row['id']], $row);
            }
            foreach ($backup['role_permission'] as $row) {
                DB::table('role_permission')->insertOrIgnore($row);
            }
            foreach ($backup['user_permission'] as $row) {
                DB::table('user_permission')->insertOrIgnore($row);
            }
        });
    }
};
