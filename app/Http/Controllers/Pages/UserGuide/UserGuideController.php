<?php

namespace App\Http\Controllers\Pages\UserGuide;

use App\Http\Controllers\Controller;
use App\Support\UserGuideLinks;

/**
 * HƯỚNG DẪN SỬ DỤNG
 *
 * Mỗi tài liệu hướng dẫn ứng với một nhóm chức năng trên leftNAV. Nội dung viết sẵn trong
 * resources/views/pages/userGuide/<khoá>/ (toc.php = mục lục, sections/ = mỗi mục một file; khung trang
 * dùng chung shared/guide.blade.php),
 * không lưu CSDL nên đi theo git cùng mã nguồn.
 *
 * Thêm tài liệu mới: khai một dòng trong GUIDES + tạo thư mục view cùng tên khoá (toc.php, sections/).
 * leftNAV tự thêm mục con. Thứ tự GUIDES = thứ tự các mục lớn trên leftNAV. Xem skill .agents/skills/user-guide.
 *
 * Mọi thay đổi chức năng người dùng thấy đều phải cập nhật hướng dẫn rồi chạy
 *   php artisan pms:user-guide-check
 * (quy tắc trong .agents/skills/common-rules).
 */
class UserGuideController extends Controller
{
    /** Khoá => thông tin hiển thị. Khoá chính là tham số {guide} trên route và tên thư mục view. */
    public const GUIDES = [
        'batDau' => [
            'title' => 'Bắt Đầu',
            'icon' => 'fas fa-flag-checkered',
            'summary' => 'Đăng nhập, mật khẩu và khoá tài khoản, màn hình xem nhanh không cần đăng nhập, Trang chủ, thông báo, chọn phân xưởng, các nhóm menu và thao tác chung trên màn hình danh sách.',
            'updated_at' => '2026-11-06',
        ],
        'duLieuGoc' => [
            'title' => 'Dữ Liệu Gốc',
            'icon' => 'fas fa-database',
            'summary' => 'Tên sản phẩm, dạng bào chế, đơn vị, thị trường, qui cách, phòng sản xuất, liên kết phòng, tổ quản lý, phòng ban, ngày nghỉ, khuôn mẫu và loại máy ép vỉ.',
            'updated_at' => '2026-11-06',
        ],
        'danhMuc' => [
            'title' => 'Danh Mục',
            'icon' => 'fas fa-newspaper',
            'summary' => 'Danh mục bán thành phẩm và thành phẩm (công đoạn, thời gian biệt trữ, công thức MMS), theo dõi lên ấn bản, kiểm tra NL/BB, ma trận cảnh báo nguồn NL, danh mục thiết bị BT-HC-TI khối B1/B2.',
            'updated_at' => '2026-11-06',
        ],
        'dinhMuc' => [
            'title' => 'Định Mức',
            'icon' => 'fas fa-chart-line',
            'summary' => 'Định mức phòng và thời gian sản xuất, định mức khuôn ép vỉ và khuôn Định hình, danh sách nhân viên (tổ, phòng công tác, bậc kỹ năng) và sản phẩm nhân viên được phép làm.',
            'updated_at' => '2026-11-06',
        ],
        'keHoach' => [
            'title' => 'Kế Hoạch',
            'icon' => 'fas fa-file-import',
            'summary' => 'Kế hoạch năm dự kiến, kế hoạch sản xuất tháng (thêm lô, ngày NL/BB, ưu tiên, gửi, chia lô đóng gói, dự trù), phản hồi KHSX, theo dõi thẩm định, hồ sơ KCS, thông báo đóng gói và kế hoạch HC-BT.',
            'updated_at' => '2026-11-06',
        ],
        'schedual' => [
            'title' => 'Lịch Sản Xuất',
            'icon' => 'fas fa-industry',
            'summary' => 'Lịch Sản Xuất Chart (Gantt, màu cảnh báo), Sắp lịch thủ công (danh sách KHSX, thả lô vào phòng, chỉnh lịch kéo thả), Sắp lịch tự động và nguyên lý hoạt động, Kiểm soát tồn BTP; Thực thi sản xuất (Nhận / Trả phòng, tịnh tuyến lịch), xác nhận hoàn thành, phòng biệt trữ, lịch tuần, danh sách lịch, tiến trình, đề nghị thay đổi KH, lịch sử thay đổi, thẩm định vệ sinh, nhận bao bì, sản lượng.',
            'updated_at' => '2026-11-06',
        ],
        'lichHcBt' => [
            'title' => 'Lịch HC-BT',
            'icon' => 'fas fa-tools',
            'summary' => 'Lịch Hiệu chuẩn - Bảo trì - Tiện ích dạng biểu đồ: sắp lịch tự động BT-HC-TI và nguyên lý, kéo thả, xác nhận hoàn thành, duyệt, đánh giá; lịch HC-BT theo tuần.',
            'updated_at' => '2026-11-06',
        ],
        'lichCongTac' => [
            'title' => 'Lịch Công Tác',
            'icon' => 'fas fa-user-check',
            'summary' => 'Phân công nhân sự sản xuất và bảo trì - hiệu chuẩn theo ca: dashboard nhân sự, chính sách tăng ca, tự động phân công và các ràng buộc, lịch công tác theo tuần.',
            'updated_at' => '2026-11-06',
        ],
        'baoCao' => [
            'title' => 'Báo Cáo',
            'icon' => 'fas fa-crosshairs',
            'summary' => 'Báo cáo ngày sản xuất (sản lượng lý thuyết - thực tế, giải trình, tồn công đoạn trước), báo cáo tuần, tháng, OEE và cách tính OEE, Loading, TEEP.',
            'updated_at' => '2026-11-06',
        ],
        'tonBtp' => [
            'title' => 'Tồn BTP',
            'icon' => 'fas fa-pallet',
            'summary' => 'Tồn kho lý thuyết theo công đoạn đích (dự báo theo ngày từ lịch đã sắp) và tồn bán thành phẩm thực tế đang biệt trữ.',
            'updated_at' => '2026-11-06',
        ],
        'lichSu' => [
            'title' => 'Lịch Sử',
            'icon' => 'fas fa-history',
            'summary' => 'Tra cứu lịch sử sản xuất và lịch sử hiệu chuẩn - bảo trì đã hoàn thành, trả lô về trạng thái chưa hoàn thành.',
            'updated_at' => '2026-11-06',
        ],
        'userPolicy' => [
            'title' => 'User Policy',
            'icon' => 'fas fa-user',
            'summary' => 'Tài khoản người dùng (thêm, quyền riêng, vô hiệu), nhóm phân quyền và ma trận quyền, các nhóm quyền đặc biệt không được đổi tên.',
            'updated_at' => '2026-11-06',
        ],
        'phanQuyen' => [
            'title' => 'Bảo Mật & Phân Quyền',
            'icon' => 'fas fa-shield-alt',
            'summary' => 'Cách phần mềm tính quyền, nhóm quyền chính và các nhóm quyền / phòng ban đặc biệt, cấp quyền cho người dùng, ma trận nhóm quyền × quyền theo từng nhóm menu, tuân thủ 21 CFR Part 11, quyền không còn dùng.',
            'updated_at' => '2026-11-06',
        ],
        'dangNhap' => [
            'title' => 'Lịch Sử Đăng Nhập',
            'icon' => 'fas fa-key',
            'summary' => 'Lịch sử đăng nhập toàn hệ thống (kết quả, IP, phiên đang mở, xuất Excel) và nhật ký thao tác Audit Trail.',
            'updated_at' => '2026-11-06',
        ],
    ];

    /**
     * Quyền xem Hướng Dẫn Sử Dụng: menu "Hướng Dẫn Sử Dụng", nút "?" trên topNAV và mọi trang /userGuide.
     * Thêm vào CSDL bởi database/sql/2026_11_05-06_forming_mold_and_cleanup_permissions.sql (phần 5)
     * hoặc migration 2026_11_06_110000_add_user_guide_view_permission (cấp sẵn cho mọi nhóm quyền đang hoạt động).
     */
    public const PERMISSION = 'user_guide_view';

    /** Tài khoản đang đăng nhập có được xem hướng dẫn không (dùng ở controller, leftNAV, topNAV). */
    public static function canView(): bool
    {
        $userId = session('user')['userId'] ?? null;

        return $userId !== null && user_has_permission($userId, self::PERMISSION, 'boolean');
    }

    public function index()
    {
        abort_unless(self::canView(), 403, 'Bạn không có quyền xem Hướng Dẫn Sử Dụng');

        session()->put(['title' => 'HƯỚNG DẪN SỬ DỤNG']);

        return view('pages.userGuide.list', [
            'guides' => self::GUIDES,
        ]);
    }

    public function show(string $guide)
    {
        abort_unless(self::canView(), 403, 'Bạn không có quyền xem Hướng Dẫn Sử Dụng');
        abort_unless(isset(self::GUIDES[$guide]), 404);

        session()->put(['title' => 'HƯỚNG DẪN SỬ DỤNG - ' . mb_strtoupper(self::GUIDES[$guide]['title'])]);

        // Tài liệu cần khung riêng thì tạo <khoá>/index.blade.php; mặc định dùng khung chung shared/guide
        $view = view()->exists('pages.userGuide.' . $guide . '.index')
            ? 'pages.userGuide.' . $guide . '.index'
            : 'pages.userGuide.shared.guide';

        return view($view, [
            'guideKey' => $guide,
            'guide' => self::GUIDES[$guide],
            // Mục lục ở <khoá>/toc.php, dùng chung với nút "?" trên topNAV
            'ugGroups' => UserGuideLinks::groups($guide),
        ]);
    }
}
