<?php

namespace App\Support;

/**
 * Tên + icon nhóm chức năng theo permissions.permission_group.
 * Dùng chung cho màn Phân Quyền › Nhóm Quyền, Danh Sách Quyền và modal Quyền Riêng của user.
 * Thứ tự khai báo cũng là thứ tự hiển thị.
 */
class PermissionGroups
{
    public const GROUPS = [
        0  => ['label' => 'Chức Năng Chung (Menu)', 'icon' => 'fa-layer-group'],
        1  => ['label' => 'Kế Hoạch', 'icon' => 'fa-clipboard-list'],
        2  => ['label' => 'Dữ Liệu Gốc', 'icon' => 'fa-database'],
        3  => ['label' => 'Danh Mục', 'icon' => 'fa-list-alt'],
        4  => ['label' => 'Định Mức', 'icon' => 'fa-chart-line'],
        5  => ['label' => 'Thông Báo', 'icon' => 'fa-bell'],
        6  => ['label' => 'Lịch Sản Xuất - Thực Thi', 'icon' => 'fa-industry'],
        7  => ['label' => 'Báo Cáo', 'icon' => 'fa-chart-bar'],
        8  => ['label' => 'Báo Cáo Ngày', 'icon' => 'fa-calendar-day'],
        9  => ['label' => 'Bao Bì - Cảnh Báo', 'icon' => 'fa-exclamation-triangle'],
        10 => ['label' => 'Phân Công Công Việc', 'icon' => 'fa-user-check'],
        11 => ['label' => 'Chính Sách Sản Lượng', 'icon' => 'fa-balance-scale'],
        12 => ['label' => 'Cảnh Báo Lịch NL/BB', 'icon' => 'fa-calendar-times'],
        13 => ['label' => 'Tồn Kho MMS', 'icon' => 'fa-warehouse'],
        14 => ['label' => 'Kế Hoạch Năm', 'icon' => 'fa-calendar-alt'],
        15 => ['label' => 'Theo Dõi', 'icon' => 'fa-tasks'],
    ];

    private const OTHER = ['label' => 'Khác', 'icon' => 'fa-ellipsis-h'];

    public static function meta($group): array
    {
        return self::GROUPS[$group] ?? self::OTHER;
    }

    public static function label($group): string
    {
        return self::meta($group)['label'];
    }
}
