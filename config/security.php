<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chính sách đăng nhập / mật khẩu
    |--------------------------------------------------------------------------
    |
    | Các tham số bảo mật cho luồng đăng nhập tự viết (LoginController).
    | Có thể ghi đè bằng biến môi trường trong file .env.
    |
    */

    // Số lần nhập sai mật khẩu liên tiếp trước khi khoá tài khoản.
    'max_login_attempts' => (int) env('AUTH_MAX_LOGIN_ATTEMPTS', 5),

    // Thời gian khoá (phút). Sau khoảng này tài khoản tự động mở khoá.
    'lockout_minutes' => (int) env('AUTH_LOCKOUT_MINUTES', 15),

    // Số ngày hiệu lực của mật khẩu. Quá hạn sẽ bị bắt buộc đổi khi đăng nhập.
    'password_expiry_days' => (int) env('AUTH_PASSWORD_EXPIRY_DAYS', 90),

    // Số mật khẩu gần nhất không được phép trùng lại (không tính mật khẩu hiện tại).
    'password_history_count' => 3,

];
