<?php

namespace App\Console\Commands;

use App\Services\UserRoleSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Kiểm tra user_management.userGroup với user_role (xem App\Services\UserRoleSync). Chạy sau mỗi lần import
 * user lên server.
 *   --fix        : chỉ THÊM user_role còn thiếu theo userGroup (user chưa có role nào) - an toàn
 *   --fix-group  : đặt userGroup = role duy nhất trong user_role cho user lệch. Đổi userGroup làm đổi giao diện
 *                  / quyền theo các chỗ so sánh cứng (vd. 'Admin') nên tách riêng, xem danh sách trước khi chạy.
 */
class CheckUserRoles extends Command
{
    protected $signature = 'users:check-roles
                            {--fix : Thêm user_role còn thiếu theo userGroup (user chưa có role nào)}
                            {--fix-group : Đặt userGroup = role duy nhất trong user_role cho user đang lệch}';

    protected $description = 'Kiểm tra user_management.userGroup có khớp bảng user_role không';

    private const PROBLEMS = [
        'no_role'              => 'Chưa có user_role',
        'no_role_bad'          => 'Chưa có user_role, userGroup không phải role nào',
        'group_mismatch'       => 'userGroup không nằm trong user_role',
        'group_mismatch_multi' => 'userGroup không nằm trong user_role (nhiều role)',
    ];

    public function handle(): int
    {
        $rows = UserRoleSync::mismatches();
        if ($rows->isEmpty()) {
            $this->info('✅ userGroup và user_role khớp nhau cho mọi user.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'User', 'Họ tên', 'userGroup', 'user_role', 'Vấn đề', 'Cách sửa'],
            $rows->map(fn($u) => [
                $u->id, $u->userName, $u->fullName, $u->userGroup, $u->roles->implode(', ') ?: '—',
                self::PROBLEMS[$u->problem], $u->fix ?? 'cần người quyết (sửa ở trang /User)',
            ])->all()
        );
        $this->line('Tổng: ' . $rows->count() . ' user · ' . $rows->countBy('problem')->map(fn($n, $k) => "$k=$n")->implode(', '));

        if ($this->option('fix')) {
            $ids = $rows->where('problem', 'no_role')->pluck('id')->all();
            $n = $ids ? UserRoleSync::fillMissingFromUserGroup($ids) : 0;
            $this->info("Đã thêm user_role cho {$n} user.");
        }

        if ($this->option('fix-group')) {
            $n = 0;
            DB::transaction(function () use ($rows, &$n) {
                foreach ($rows->where('problem', 'group_mismatch') as $u) {
                    $n += DB::table('user_management')->where('id', $u->id)->update(['userGroup' => $u->roles[0]]);
                }
            });
            $this->info("Đã đổi userGroup cho {$n} user (user đang đăng nhập cần đăng nhập lại).");
        }

        if (!$this->option('fix') && !$this->option('fix-group')) {
            $this->line('Chạy lại với --fix (thêm user_role thiếu) và/hoặc --fix-group (đổi userGroup theo user_role).');
        }

        return self::SUCCESS;
    }
}
