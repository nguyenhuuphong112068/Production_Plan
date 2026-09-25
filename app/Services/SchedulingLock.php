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
