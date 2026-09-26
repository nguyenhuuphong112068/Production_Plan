<?php

namespace App\Services;

use RuntimeException;

/**
 * Lỗi nghiệp vụ của trang Thực Thi Sản Xuất; mã lỗi là HTTP status trả về cho trình duyệt
 * (422 dữ liệu không hợp lệ, 409 trạng thái phòng đã bị người khác thay đổi).
 */
class ProductionExecutionException extends RuntimeException
{
    public function __construct(string $message, int $status = 422)
    {
        parent::__construct($message, $status);
    }
}
