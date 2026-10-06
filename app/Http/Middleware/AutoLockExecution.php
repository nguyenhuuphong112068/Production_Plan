<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Services\SchedulingLock;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Người sắp lịch quên bấm "Khóa TTSX" khi sắp lịch thủ công trên Gantt (thêm lịch, kéo thả, đổi phòng, gỡ lịch...):
 * thao tác sửa lịch thành công đầu tiên sẽ tự bật khóa thủ công (SchedulingLock::setManual) để Nhận / Trả phòng ở trang
 * Thực Thi không tịnh tuyến chồng lên lịch đang sắp. Gantt còn gọi PUT /Schedual/executionLock/auto ngay khi có thay đổi
 * kéo thả chưa lưu (lockIfNeeded). Trả header X-Exec-Auto-Locked để Gantt nhắc người dùng và cập nhật nút.
 * Chỉ áp cho phân xưởng trong DEPARTMENTS, chỉ với Admin / Schedualer của chính phân xưởng (như quyền bật nút khóa).
 */
class AutoLockExecution
{
    const DEPARTMENTS = ['PXV1'];

    const HEADER = 'X-Exec-Auto-Locked';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->isSuccessful() && self::lockIfNeeded($request->path())) {
            $response->headers->set(self::HEADER, '1');
        }

        return $response;
    }

    /**
     * Bật khóa nếu người đăng nhập là người sắp lịch của phân xưởng áp dụng và phân xưởng chưa khóa; nếu đã khóa thì
     * gia hạn thời gian chờ không hoạt động (người sắp lịch vẫn đang sắp). Trả về true nếu vừa khóa. $source ghi vào audit (route / "kéo thả chưa lưu").
     */
    public static function lockIfNeeded(string $source): bool
    {
        $user = session('user') ?? [];
        $production = $user['production_code'] ?? null;

        if (!in_array($production, self::DEPARTMENTS, true)
            || !in_array($user['userGroup'] ?? null, ['Admin', 'Schedualer'], true)
            || DB::table('user_management')->where('userName', $user['userName'] ?? null)->value('deparment') !== $production) {
            return false;
        }

        if (SchedulingLock::manual($production)) {
            SchedulingLock::touchManual($production);
            return false;
        }

        SchedulingLock::setManual($production, true, $user['fullName'] ?? null);
        AuditTrialController::log('Tự khóa thực thi sản xuất', 'scheduling_locks', 0, 'NA',
            'Phân xưởng ' . $production . ' · sắp lịch thủ công (' . $source . ')');

        return true;
    }
}
