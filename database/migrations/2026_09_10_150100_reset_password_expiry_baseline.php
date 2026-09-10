<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Đặt lại mốc hết hạn mật khẩu khi bắt đầu áp dụng chính sách 90 ngày.
     *
     * Cột `changePWdate` đã có sẵn từ trước nhưng chưa bao giờ được kiểm tra lúc
     * đăng nhập, nên phần lớn user đang mang ngày rất cũ (nhiều user vẫn ở mốc
     * seed 2026-01-01). Nếu bật thẳng tính năng, gần như toàn bộ user sẽ bị bắt
     * đổi mật khẩu cùng một lúc ngay ngày deploy.
     *
     * Vì vậy: cấp lại chu kỳ 90 ngày kể từ ngày deploy cho những user ĐÃ quá hạn.
     * User còn hạn được giữ nguyên - reset họ sẽ vô tình kéo dài tuổi thọ mật khẩu.
     */
    public function up(): void
    {
        DB::table('user_management')
            ->where('isActive', 1)
            ->whereDate('changePWdate', '<=', today())
            ->update([
                'changePWdate' => today()->addDays((int) config('security.password_expiry_days', 90)),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không thể khôi phục: ngày hết hạn cũ đã bị ghi đè và vốn là dữ liệu rác.
    }
};
