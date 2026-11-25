<?php

namespace App\Http\Middleware;

use App\Support\WeighingScheduler;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gắn vào các nhóm route sắp lịch trên Lịch Sản Xuất Chart (/Schedual, /Schedual/weighing, sắp lịch tự động,
 * plugin Kiểm soát tồn BTP). Nhóm quyền chính Weighing Scheduler chỉ được gọi route đọc (GET) và các route ghi
 * trong ALLOWED; mỗi route ghi trong ALLOWED tự kiểm tra lại chỉ đụng tới lịch công đoạn cân (WeighingScheduler).
 * Các nhóm quyền khác đi qua bình thường.
 */
class RestrictWeighingScheduler
{
    const ALLOWED = [
        // Đọc (POST / PUT nhưng không ghi)
        'pages.Schedual.view',
        'pages.Schedual.getSumaryData',
        'pages.Schedual.getInforSoure',
        'pages.Schedual.history',
        'pages.Schedual.checkQuotaRoom',
        'pages.Schedual.checkMissingMoldQuotas',
        'pages.Schedual.validate_submit',
        'pages.Schedual.weighing.status',
        'pages.Schedual.weighing.suggest',
        'pages.Schedual.executionLockAuto', // Gantt gọi khi có kéo thả chưa lưu; Weighing Scheduler chỉ khóa phòng cân
        'pages.Schedual.executionLockToggle', // nút Khóa TTSX: Weighing Scheduler chỉ khóa phòng cân (SchedulingLock::weighKey)
        // Ghi - hàm tự chặn id ngoài công đoạn cân
        'pages.Schedual.store',
        'pages.Schedual.update',
        'pages.Schedual.updateClearning',
        'pages.Schedual.deActive',
        'pages.Schedual.submit',
        'pages.Schedual.weighing.apply',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! WeighingScheduler::active() || $request->isMethod('GET') || $request->routeIs(...self::ALLOWED)) {
            return $next($request);
        }

        return WeighingScheduler::deny();
    }
}
