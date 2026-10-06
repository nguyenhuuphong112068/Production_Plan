<?php

namespace App\Services;

use RuntimeException;

/**
 * Chờ quá RerouteLock::WAIT_SECONDS mà lần tịnh tuyến khác của cùng phân xưởng chưa xong: thao tác bị hủy, người dùng làm lại.
 */
class RerouteBusyException extends RuntimeException
{
}
