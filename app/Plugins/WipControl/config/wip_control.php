<?php

/**
 * Cấu hình plugin Kiểm soát tồn BTP khi sắp lịch tự động.
 * Tắt hẳn plugin: WIP_CONTROL_ENABLED=false trong .env, hoặc bỏ
 * WipControlServiceProvider khỏi bootstrap/providers.php.
 */
return [
    'enabled' => env('WIP_CONTROL_ENABLED', true),

    // Số vòng lặp mặc định và trần người dùng được phép nhập
    'default_iterations' => 5,
    'max_iterations'     => 10,

    // Dừng sớm khi tổng lượng vượt không giảm sau bấy nhiêu vòng liên tiếp
    'stagnant_rounds' => 2,

    // Số ngày tính tồn kể từ ngày bắt đầu sắp lịch
    'horizon_days' => 30,

    // Thời gian tối đa cho cả phần lặp (giây)
    'time_budget_seconds' => 900,

    // Một lô kéo theo cả campaign / lô con đóng gói; nhóm lớn hơn mức này thì bỏ qua lô đó
    'max_group_lots' => 40,

    // Lùi ít hơn mức này (phút) thì coi như không đáng lùi
    'min_shift_minutes' => 60,

    // Lô vào kho sớm hơn lúc công đoạn sau cần ít nhất bấy nhiêu giờ, chừa chỗ cho
    // phòng không trống đúng giờ khi sắp lại
    'safety_buffer_hours' => 24,
];
