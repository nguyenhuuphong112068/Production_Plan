<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Khóa "đang sắp lịch tự động" theo phân xưởng (bảng scheduling_locks).
 *
 * Sắp lịch tự động chạy 1-3 phút; trong lúc đó xác nhận hoàn thành ở máy khác
 * cùng phân xưởng sẽ làm xáo trộn lịch, nên phải chờ sắp lịch xong.
 */
class SchedulingLock
{
    /**
     * Khóa tự hết hạn sau khoảng này, phòng khi request sắp lịch chết giữa chừng
     * (timeout PHP là fatal error, khối finally không chạy). Sắp lịch bình thường chỉ 1-3 phút;
     * lượt nào chạy quá 10 phút thì khóa hết hạn trước khi chạy xong.
     */
    const EXPIRE_MINUTES = 10;

    /**
     * Giành khóa cho phân xưởng. Trả về false nếu phân xưởng đang có người sắp lịch.
     */
    public static function acquire(string $deparmentCode, ?string $startedBy): bool
    {
        $now = now();

        DB::table('scheduling_locks')->insertOrIgnore([
            'deparment_code' => $deparmentCode,
            'is_scheduling'  => 0,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        // UPDATE có điều kiện là thao tác nguyên tử: 2 máy bấm cùng lúc thì chỉ 1 máy được.
        return DB::table('scheduling_locks')
            ->where('deparment_code', $deparmentCode)
            ->where(function ($q) use ($now) {
                $q->where('is_scheduling', 0)
                    ->orWhereNull('started_at')
                    ->orWhere('started_at', '<', $now->copy()->subMinutes(self::EXPIRE_MINUTES));
            })
            ->update([
                'is_scheduling' => 1,
                'started_by'    => $startedBy,
                'started_at'    => $now,
                'updated_at'    => $now,
            ]) === 1;
    }

    public static function release(string $deparmentCode): void
    {
        DB::table('scheduling_locks')
            ->where('deparment_code', $deparmentCode)
            ->update([
                'is_scheduling' => 0,
                'updated_at'    => now(),
            ]);
    }

    /**
     * Dòng khóa nếu phân xưởng đang sắp lịch (còn hạn), ngược lại null.
     */
    public static function active(?string $deparmentCode): ?object
    {
        if (!$deparmentCode) {
            return null;
        }

        return DB::table('scheduling_locks')
            ->where('deparment_code', $deparmentCode)
            ->where('is_scheduling', 1)
            ->where('started_at', '>=', now()->subMinutes(self::EXPIRE_MINUTES))
            ->first();
    }

    /**
     * Khóa thủ công (người sắp lịch bật ở sidebar lịch chờ sắp trên Gantt khi sắp lịch thủ công): chỉ chặn Nhận / Trả phòng
     * ở trang Thực Thi / Ghi Nhận Sản Xuất, không chặn sắp lịch tự động hay xác nhận hoàn thành. Phòng khi quên tắt, khóa
     * tự mở khi không còn thao tác sắp lịch thủ công quá MANUAL_IDLE_MINUTES (mỗi thao tác sửa lịch trên Gantt / thay đổi
     * kéo thả chưa lưu gọi touchManual), và tối đa MANUAL_EXPIRE_HOURS kể từ lúc khóa.
     */
    const MANUAL_EXPIRE_HOURS = 8;

    const MANUAL_IDLE_MINUTES = 10;

    public static function setManual(string $deparmentCode, bool $locked, ?string $by): void
    {
        $now = now();

        DB::table('scheduling_locks')->insertOrIgnore([
            'deparment_code' => $deparmentCode,
            'is_scheduling'  => 0,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        DB::table('scheduling_locks')
            ->where('deparment_code', $deparmentCode)
            ->update([
                'manual_locked' => $locked ? 1 : 0,
                'manual_by'     => $by,
                'manual_at'     => $now,
                'manual_active_at' => $now,
                'updated_at'    => $now,
            ]);
    }

    /**
     * Ghi nhận người sắp lịch vẫn đang sắp lịch thủ công (gia hạn thời gian chờ không hoạt động của khóa còn hiệu lực).
     */
    public static function touchManual(string $deparmentCode): void
    {
        if (self::manual($deparmentCode)) {
            DB::table('scheduling_locks')
                ->where('deparment_code', $deparmentCode)
                ->update(['manual_active_at' => now()]);
        }
    }

    /**
     * Lúc khóa thủ công tự mở: hết thời gian chờ không hoạt động hoặc chạm mức tối đa, mốc nào đến trước.
     */
    public static function manualExpiresAt(object $lock): Carbon
    {
        $idle = Carbon::parse($lock->manual_active_at ?? $lock->manual_at)->addMinutes(self::MANUAL_IDLE_MINUTES);
        $max = Carbon::parse($lock->manual_at)->addHours(self::MANUAL_EXPIRE_HOURS);

        return $idle->min($max);
    }

    /**
     * Dòng khóa nếu phân xưởng đang khóa thủ công (còn hạn), ngược lại null.
     */
    public static function manual(?string $deparmentCode): ?object
    {
        if (!$deparmentCode) {
            return null;
        }

        return DB::table('scheduling_locks')
            ->where('deparment_code', $deparmentCode)
            ->where('manual_locked', 1)
            ->where('manual_at', '>=', now()->subHours(self::MANUAL_EXPIRE_HOURS))
            ->whereRaw('COALESCE(manual_active_at, manual_at) >= ?', [now()->subMinutes(self::MANUAL_IDLE_MINUTES)])
            ->first();
    }

    /**
     * Thông báo chặn thao tác Nhận / Trả phòng nếu phân xưởng đang sắp lịch tự động hoặc đang khóa thủ công, ngược lại null.
     */
    public static function executionBlock(?string $deparmentCode): ?string
    {
        if ($lock = self::active($deparmentCode)) {
            return self::message($lock);
        }
        if ($lock = self::manual($deparmentCode)) {
            return '🔒 Người sắp lịch' . ($lock->manual_by ? ' ' . $lock->manual_by : '')
                . ' đang sắp lịch thủ công (khóa từ ' . Carbon::parse($lock->manual_at)->format('H:i d/m')
                . '), tạm dừng Nhận / Trả phòng để tránh xung đột dời lịch. Vui lòng thử lại sau.';
        }

        return null;
    }

    /**
     * Thông báo cho người dùng khi phân xưởng đang bị khóa.
     */
    public static function message(object $lock): string
    {
        return '⏳ Phân xưởng đang chạy sắp lịch tự động'
            . ($lock->started_by ? ' (' . $lock->started_by . ')' : '')
            . ', bắt đầu lúc ' . Carbon::parse($lock->started_at)->format('H:i')
            . '. Vui lòng thử lại sau ít phút.';
    }
}
