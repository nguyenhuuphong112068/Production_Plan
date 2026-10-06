<?php

namespace App\Console\Commands;

use App\Services\UserRoleSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Tạo tài khoản role Executor (Ghi Nhận Sản Xuất) cho nhân viên 5 phân xưởng.
 *
 * - Nguồn: bảng employees còn làm việc (active=1, resign=0); phân xưởng lấy từ
 *   employee_assignments.production_code của dòng is_main=1.
 * - userName = employees.code; đã có trong user_management thì bỏ qua.
 * - Mật khẩu ban đầu = MSNV, bắt đổi mật khẩu ở lần đăng nhập đầu tiên.
 * - Mỗi lần chạy đều bù dòng user_role còn thiếu cho mọi user userGroup = 'Executor' (user tạo bằng cách khác,
 *   vd. import user_management, không có user_role thì PermissionHelper không cấp quyền của role).
 */
class CreateExecutorUsers extends Command
{
    protected $signature = 'users:create-executors
                            {--department= : Chỉ tạo cho một phân xưởng (VD: PXV1). Bỏ trống = cả 5 PX}
                            {--dry-run : Chỉ liệt kê, không ghi DB}
                            {--fix-groups : Chỉ tính lại groupName cho các user do lệnh này tạo trước đó}';

    protected $description = 'Tạo user Executor từ bảng employees cho PXV1, PXV2, PXDN, PXVH, PXTN (bỏ qua MSNV đã có tài khoản)';

    private const DEPARTMENTS = ['PXV1', 'PXV2', 'PXDN', 'PXVH', 'PXTN'];

    // employee_assignments.group_id của phân xưởng sản xuất là MÃ TỔ theo danh sách cứng của trang Nhân Sự /
    // Lịch Công Tác (ProductionAssignmentController), KHÔNG phải stage_groups.id
    private const GROUPS = [
        1 => 'Trung Tâm Cân',
        3 => 'Pha Chế',
        4 => 'Văn Phòng',
        5 => 'Định Hình',
        6 => 'Bao Phim',
        7 => 'ĐGSC',
        8 => 'ĐGTC',
        9 => 'VSCN + Kho BTP',
        10 => 'Mã Hoá BB',
    ];

    public function handle(): int
    {
        $departments = self::DEPARTMENTS;
        if ($only = $this->option('department')) {
            if (!in_array($only, self::DEPARTMENTS, true)) {
                $this->error("Phân xưởng '{$only}' không hợp lệ. Hợp lệ: " . implode(', ', self::DEPARTMENTS));
                return self::FAILURE;
            }
            $departments = [$only];
        }

        $roleId = DB::table('roles')->where('name', 'Executor')->value('id');
        if (!$roleId) {
            $this->error("Chưa có role 'Executor' trong bảng roles.");
            return self::FAILURE;
        }

        // Phân xưởng chính + tổ chính (tổ xuất hiện nhiều nhất trong các dòng is_main=1 của PX đó).
        // Dòng active=1 được ưu tiên; nhân viên chỉ còn dòng đã tắt thì mới lấy theo dòng đã tắt.
        $mains = DB::table('employee_assignments')
            ->where('is_main', 1)
            ->whereIn('production_code', $departments)
            ->orderByDesc('active')
            ->orderByDesc('id')
            ->get(['employees_id', 'production_code', 'group_id', 'active']);

        $px = [];
        $groupCount = [];
        foreach ($mains as $m) {
            $px[$m->employees_id] ??= $m->production_code;
            if ($m->production_code === $px[$m->employees_id] && $m->group_id > 0) {
                $groupCount[$m->employees_id][$m->group_id] = ($groupCount[$m->employees_id][$m->group_id] ?? 0) + ($m->active ? 1000 : 1);
            }
        }

        // Ngoài danh sách cứng: một số dòng cũ ghi stage_groups.id (vd. 14 = ĐGTC)
        $groupNames = DB::table('stage_groups')->pluck('name', 'id')->all();
        $groupName = fn ($gid) => self::GROUPS[$gid] ?? $groupNames[$gid] ?? 'NA';
        $existing = DB::table('user_management')->pluck('userName')->map(fn ($u) => (string) $u)->flip();

        $employees = DB::table('employees')
            ->whereIn('id', array_keys($px))
            ->where('active', 1)
            ->where('resign', 0)
            ->whereNotNull('code')
            ->where('code', '<>', '')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $toCreate = [];
        $skipped = 0;
        foreach ($employees as $e) {
            $code = trim($e->code);
            if ($this->option('fix-groups')) {
                if (isset($existing[$code])) {
                    $toCreate[] = ['code' => $code, 'group' => $this->pickGroup($groupCount[$e->id] ?? [], $groupName)];
                }
                continue;
            }
            if (isset($existing[$code]) || strlen($code) > 10) {
                $skipped++;
                continue;
            }
            $toCreate[] = [
                'code' => $code,
                'name' => trim($e->name ?? '') ?: $code,
                'px' => $px[$e->id],
                'group' => $this->pickGroup($groupCount[$e->id] ?? [], $groupName),
            ];
            $existing[$code] = true; // tránh trùng MSNV trong chính bảng employees
        }

        if ($this->option('fix-groups')) {
            return $this->fixGroups($toCreate);
        }

        $this->backfillUserRoles($roleId);

        $byPx = collect($toCreate)->countBy('px');
        $this->table(['PX', 'Sẽ tạo'], $byPx->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info('Bỏ qua (đã có tài khoản): ' . $skipped);

        if ($this->option('dry-run') || empty($toCreate)) {
            $this->line($this->option('dry-run') ? 'Dry-run: không ghi DB.' : 'Không có tài khoản nào cần tạo.');
            return self::SUCCESS;
        }

        $changePWdate = today()->addDays((int) config('security.password_expiry_days', 90));
        $bar = $this->output->createProgressBar(count($toCreate));

        DB::transaction(function () use ($toCreate, $roleId, $changePWdate, $bar) {
            foreach ($toCreate as $u) {
                $userId = DB::table('user_management')->insertGetId([
                    'userName' => $u['code'],
                    'passWord' => Hash::make($u['code']),
                    'fullName' => $u['name'],
                    'userGroup' => 'Executor',
                    'deparment' => $u['px'],
                    'groupName' => $u['group'],
                    'mail' => 'NA',
                    'changePWdate' => $changePWdate,
                    'must_change_password' => 1,
                    'failed_attempts' => 0,
                    'isLocked' => 0,
                    'prepareBy' => 'Tạo tự động (users:create-executors)',
                    'created_at' => now(),
                ]);
                UserRoleSync::assign($userId, [$roleId]);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Đã tạo ' . count($toCreate) . ' tài khoản Executor.');

        return self::SUCCESS;
    }

    /** user_management.userGroup = 'Executor' mà chưa có dòng user_role của role Executor → thêm */
    private function backfillUserRoles(int $roleId): void
    {
        $missing = DB::table('user_management as u')
            ->where('u.userGroup', 'Executor')
            ->whereNotExists(fn ($q) => $q->from('user_role as ur')->whereColumn('ur.user_id', 'u.id')->where('ur.role_id', $roleId))
            ->pluck('u.id');

        if ($missing->isEmpty()) {
            return;
        }
        if ($this->option('dry-run')) {
            $this->warn("Thiếu user_role: {$missing->count()} user Executor (dry-run: chưa bù).");
            return;
        }

        DB::table('user_role')->insertOrIgnore($missing->map(fn ($id) => ['user_id' => $id, 'role_id' => $roleId])->all());
        $this->info("Đã bù user_role cho {$missing->count()} user Executor.");
    }

    // Tổ chính: tổ có nhiều dòng phân công nhất (mỗi phòng 1 dòng)
    private function pickGroup(array $counts, \Closure $groupName): string
    {
        if (!$counts) {
            return 'NA';
        }
        arsort($counts);
        return $groupName(array_key_first($counts));
    }

    private function fixGroups(array $rows): int
    {
        $changed = 0;
        DB::transaction(function () use ($rows, &$changed) {
            foreach ($rows as $r) {
                $changed += DB::table('user_management')
                    ->where('userName', $r['code'])
                    ->where('prepareBy', 'like', '%create-executors%')
                    ->where('groupName', '<>', $r['group'])
                    ->when(!$this->option('dry-run'), fn ($q) => $q->update(['groupName' => $r['group']]), fn ($q) => $q->count());
            }
        });
        $this->info(($this->option('dry-run') ? 'Sẽ sửa' : 'Đã sửa') . " groupName: {$changed} tài khoản.");
        return self::SUCCESS;
    }
}
