<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ghi role của user ở 1 chỗ duy nhất. Role lưu 2 nơi:
 *   - user_role (nguồn chuẩn, nhiều role / user): PermissionHelper cấp quyền theo bảng này.
 *   - user_management.userGroup (tên role CHÍNH = role đầu tiên): bản sao để các chỗ so sánh cứng
 *     ('Admin', 'Schedualer', 'Executor'...) và session('user.userGroup') dùng.
 * Mọi chỗ tạo / sửa role của user phải đi qua assign() để 2 nơi không lệch nhau.
 * Kiểm tra / sửa dữ liệu đã lệch: php artisan users:check-roles [--fix].
 */
class UserRoleSync
{
    /**
     * Đặt role cho user: user_role = $roleIds (thay toàn bộ), userGroup = tên role đầu tiên.
     * $roleIds giữ thứ tự người dùng chọn; phần tử đầu là role chính.
     */
    public static function assign(int $userId, array $roleIds): void
    {
        $roleIds = array_values(array_unique(array_map('intval', array_filter($roleIds))));
        $primary = $roleIds ? DB::table('roles')->where('id', $roleIds[0])->value('name') : null;
        if (!$primary) {
            throw new \InvalidArgumentException('Phải có ít nhất 1 role hợp lệ');
        }

        DB::transaction(function () use ($userId, $roleIds, $primary) {
            DB::table('user_role')->where('user_id', $userId)->delete();
            DB::table('user_role')->insert(array_map(fn($id) => ['user_id' => $userId, 'role_id' => $id], $roleIds));
            DB::table('user_management')->where('id', $userId)->update(['userGroup' => $primary]);
        });
    }

    /**
     * Thêm dòng user_role theo userGroup cho user CHƯA có role nào (user tạo ngoài trang /User: import Excel,
     * import SQL...). Không đụng user đã có role. $userIds = null: mọi user. Trả về số dòng đã thêm.
     */
    public static function fillMissingFromUserGroup(?array $userIds = null): int
    {
        $rows = self::withoutRoles($userIds)
            ->join('roles as r', 'r.name', '=', 'u.userGroup')
            ->get(['u.id as user_id', 'r.id as role_id'])
            ->map(fn($r) => (array) $r)
            ->all();

        return $rows ? DB::table('user_role')->insertOrIgnore($rows) : 0;
    }

    /**
     * Các user đang lệch, mỗi dòng: id, userName, fullName, userGroup, roles (tên các role trong user_role),
     * problem (mã lỗi), fix (cách --fix sẽ sửa, null = cần người quyết).
     *   no_role        : chưa có user_role, userGroup là role có thật  → thêm user_role theo userGroup
     *   no_role_bad    : chưa có user_role, userGroup không phải role nào → cần người quyết
     *   group_mismatch : userGroup không nằm trong user_role, user có đúng 1 role → userGroup = role đó
     *   group_mismatch_multi : như trên nhưng có nhiều role → cần người quyết role chính
     */
    public static function mismatches(): Collection
    {
        $roleNames = DB::table('roles')->pluck('name', 'id');
        $userRoles = DB::table('user_role')->get()->groupBy('user_id')
            ->map(fn($rows) => $rows->pluck('role_id')->map(fn($id) => $roleNames[$id] ?? "#$id")->values());

        return DB::table('user_management')
            ->orderBy('id')
            ->get(['id', 'userName', 'fullName', 'userGroup', 'deparment', 'isActive'])
            ->map(function ($u) use ($userRoles, $roleNames) {
                $roles = $userRoles->get($u->id, collect());
                if ($roles->contains($u->userGroup)) {
                    return null;
                }

                if ($roles->isEmpty()) {
                    $valid = $roleNames->contains($u->userGroup);
                    $u->problem = $valid ? 'no_role' : 'no_role_bad';
                    $u->fix = $valid ? "thêm user_role = {$u->userGroup}" : null;
                } elseif ($roles->count() === 1) {
                    $u->problem = 'group_mismatch';
                    $u->fix = "userGroup: {$u->userGroup} → {$roles[0]}";
                } else {
                    $u->problem = 'group_mismatch_multi';
                    $u->fix = null;
                }
                $u->roles = $roles;

                return $u;
            })
            ->filter()
            ->values();
    }

    private static function withoutRoles(?array $userIds)
    {
        return DB::table('user_management as u')
            ->when($userIds !== null, fn($q) => $q->whereIn('u.id', $userIds))
            ->whereNotExists(fn($q) => $q->from('user_role as ur')->whereColumn('ur.user_id', 'u.id'));
    }
}
