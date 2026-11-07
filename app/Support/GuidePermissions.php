<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * PHÂN QUYỀN TRONG HƯỚNG DẪN SỬ DỤNG
 *
 * Bảng tra "màn hình / thao tác -> điều kiện" của từng tài liệu hướng dẫn (khoá = khoá trong UserGuideController::GUIDES).
 * Dùng cho:
 *   - mục "Phân Quyền" ở cuối mỗi tài liệu chức năng (shared/permissionTable.blade.php)
 *   - ma trận nhóm quyền × quyền của tài liệu Bảo Mật & Phân Quyền (shared/permissionMatrix.blade.php)
 *   - lệnh pms:user-guide-check (quyền khai ở đây phải có trong bảng permissions; quyền trong bảng permissions phải
 *     được khai ở đây hoặc nằm trong UNUSED)
 *
 * Điều kiện mỗi dòng là MỘT trong hai kiểu:
 *   'perm'  => tên quyền trong bảng permissions (nhóm quyền đang có quyền được đọc thẳng CSDL lúc mở trang)
 *   'rule'  => mô tả điều kiện kiểm tra cứng trong mã (tên nhóm quyền chính, phòng ban, phân xưởng...),
 *              kèm 'roles' (tên nhóm quyền liên quan, nếu có) để hiện nhãn
 *
 * Thêm / đổi quyền hoặc điều kiện ở một chức năng thì sửa bảng này cùng lúc (quy tắc trong .agents/skills/common-rules).
 */
class GuidePermissions
{
    public const ALL = 'Mọi tài khoản đăng nhập';

    /**
     * Quyền mã nguồn không còn kiểm tra ở đâu. Cả 12 quyền được gỡ khỏi CSDL bằng
     * database/sql/2026_11_05-06_forming_mold_and_cleanup_permissions.sql (hoặc migration
     * 2026_11_06_100000_remove_permissions_of_deleted_features). Giữ danh sách để mục "Quyền Không Còn Dùng"
     * của hướng dẫn và pms:user-guide-check nhận ra khi CSDL nào chưa chạy file đó.
     */
    public const UNUSED = [
        'layout_statistics' => 'Màn hình Thống Kê đã xoá (06/11/2026)',
        'layout_status' => 'Màn hình Trạng Thái đã xoá (06/11/2026)',
        'layout_MMS' => 'Màn hình tồn kho MMS đã xoá (06/11/2026)',
        'create_intermediate_Hypothesis_category' => 'Chức năng thêm danh mục giả định đã xoá (06/11/2026)',
        'category_maintenance_create' => 'Danh mục BT-HC-TI chỉ có nút cập nhật',
        'category_maintenance_deActive' => 'Danh mục BT-HC-TI chỉ có nút cập nhật',
        'plan_maintenance_create' => 'Thêm kế hoạch HC-BT dùng quyền Tạo Mới Kế Hoạch',
        'create_general_notification' => 'Thông báo chung không kiểm tra quyền',
        'layout_feedback' => 'Menu Phản hồi KHSX không kiểm tra quyền',
        'Record_KCS_Date' => 'Không còn nơi kiểm tra',
        'personnel_assignment' => 'Mục menu đã ẩn',
        'layout_monthly_report' => 'Báo Cáo Tháng đi theo quyền Xem Báo Cáo Tuần',
    ];

    /**
     * Khoá tài liệu => [ [màn hình, thao tác, điều kiện], ... ] theo thứ tự màn hình trên leftNAV.
     * Điều kiện: ['perm' => '...'] hoặc ['rule' => '...', 'roles' => [...]].
     */
    public const MAP = [
        'batDau' => [
            ['Trang chủ, chọn phân xưởng, thông báo', 'Dùng', ['rule' => self::ALL]],
            ['Hướng Dẫn Sử Dụng', 'Thấy menu Hướng Dẫn Sử Dụng, nút ? trên thanh trên cùng, mở các trang hướng dẫn', ['perm' => 'user_guide_view']],
        ],
        'duLieuGoc' => [
            ['Mọi màn hình Dữ Liệu Gốc', 'Xem danh sách', ['rule' => self::ALL]],
            ['Tên Sản Phẩm', 'Thêm mới', ['perm' => 'materData_productName_store']],
            ['Tên Sản Phẩm', 'Cập nhật', ['perm' => 'materData_productName_update']],
            ['Tên Sản Phẩm', 'Vô hiệu / kích hoạt', ['perm' => 'materData_productName_deActive']],
            ['Phòng Sản Xuất', 'Thêm mới', ['perm' => 'materData_room_store']],
            ['Phòng Sản Xuất', 'Cập nhật', ['perm' => 'materData_room_update']],
            ['Phòng Sản Xuất', 'Vô hiệu / kích hoạt', ['perm' => 'materData_room_deActive']],
            ['Liên Kết Phòng', 'Tạo / điều chỉnh liên kết', ['perm' => 'materData_room_links']],
            ['Dạng Bào Chế', 'Thêm mới', ['perm' => 'materData_Dosage_store']],
            ['Dạng Bào Chế', 'Cập nhật', ['perm' => 'materData_Dosage_update']],
            ['Đơn Vị Tính', 'Thêm mới', ['perm' => 'materData_unit_store']],
            ['Đơn Vị Tính', 'Cập nhật', ['perm' => 'materData_unit_update']],
            ['Thị Trường', 'Thêm mới', ['perm' => 'materData_Market_store']],
            ['Thị Trường', 'Cập nhật', ['perm' => 'materData_Market_update']],
            ['Qui Cách Sản Phẩm', 'Thêm mới', ['perm' => 'materData_Specification_store']],
            ['Qui Cách Sản Phẩm', 'Cập nhật', ['perm' => 'materData_Specification_update']],
            ['Nguồn Nguyên Liệu (không có mục trên menu)', 'Thêm mới', ['perm' => 'materData_source_material_create']],
            ['Nguồn Nguyên Liệu (không có mục trên menu)', 'Cập nhật', ['perm' => 'materData_source_material_update']],
            ['Nguồn Nguyên Liệu (không có mục trên menu)', 'Vô hiệu / kích hoạt', ['perm' => 'materData_source_material_deActive']],
            ['Tổ Quản Lý, Phòng Ban, Khuôn Mẫu', 'Thêm, cập nhật, vô hiệu', ['rule' => self::ALL]],
            ['Loại Máy Ép Vỉ', 'Thấy mục trên menu', ['perm' => 'layout_test']],
        ],
        'danhMuc' => [
            ['Bán Thành Phẩm', 'Thêm mới', ['perm' => 'category_intermediate_create']],
            ['Bán Thành Phẩm', 'Cập nhật', ['perm' => 'category_intermediate_update']],
            ['Bán Thành Phẩm', 'Vô hiệu / kích hoạt', ['perm' => 'category_intermediate_deActive']],
            ['Bán Thành Phẩm, Thành Phẩm', 'Thấy các mã giả định cũ', ['perm' => 'view_Hypothesis_category']],
            ['Thành Phẩm', 'Thêm mới', ['perm' => 'category_product_create']],
            ['Thành Phẩm', 'Cập nhật', ['perm' => 'category_product_update']],
            ['Thành Phẩm', 'Vô hiệu / kích hoạt', ['perm' => 'category_product_deActive']],
            ['Theo Dõi Lên Ấn Bản', 'Tạo nội dung theo dõi', ['perm' => 'publication_tracking_task_create']],
            ['Theo Dõi Lên Ấn Bản', 'Thêm nội dung cho từng mã', ['perm' => 'publication_tracking_task_add']],
            ['Theo Dõi Lên Ấn Bản', 'Quyết định lên ấn bản', ['perm' => 'publication_tracking_decision_update']],
            ['Kiểm Tra NL/BB', 'Xem', ['rule' => self::ALL]],
            ['Cảnh Báo Nguồn NL', 'Thêm mới', ['perm' => 'material_source_warning_create']],
            ['Cảnh Báo Nguồn NL', 'Cập nhật', ['perm' => 'material_source_warning_update']],
            ['Cảnh Báo Nguồn NL', 'Vô hiệu', ['perm' => 'material_source_warning_deActive']],
            ['BT - HC B1 / B2', 'Cập nhật thiết bị', ['perm' => 'category_maintenance_update']],
            ['BT - HC B1 / B2', 'Phạm vi được sửa', ['rule' => 'Hiệu chuẩn: phòng ban QA. Bảo trì, Tiện ích: phòng ban EN. Nhóm quyền chính Admin (và phòng ban BOD khi lưu) sửa được tất cả.', 'roles' => ['Admin']]],
        ],
        'dinhMuc' => [
            ['Định Mức Sản Xuất', 'Thêm định mức', ['perm' => 'quota_production_create']],
            ['Định Mức Sản Xuất', 'Cập nhật định mức', ['perm' => 'quota_production_update']],
            ['Định Mức Sản Xuất', 'Vô hiệu định mức', ['perm' => 'quota_production_deActive']],
            ['Khuôn EV, Khuôn Định Hình', 'Cập nhật định mức khuôn', ['perm' => 'quota_production_update']],
            ['Danh Sách Nhân Viên, Sản Phẩm Được Phép', 'Thêm, sửa nhân viên và sản phẩm được phép', ['rule' => 'Tài khoản cùng phòng ban với danh sách đang xem, hoặc nhóm quyền chính Admin.', 'roles' => ['Admin']]],
        ],
        'keHoach' => [
            ['Kế Hoạch Năm Dự Kiến', 'Thấy mục trên menu', ['perm' => 'layout_annual_plan']],
            ['Kế Hoạch Sản Xuất', 'Tạo kế hoạch tháng mới', ['perm' => 'plan_production_create_plan_list']],
            ['Kế Hoạch Sản Xuất', 'Thấy kế hoạch tháng chưa gửi', ['perm' => 'plan_view_pending_production_plan']],
            ['Kế Hoạch Sản Xuất', 'Thêm lô', ['perm' => 'plan_production_create']],
            ['Kế Hoạch Sản Xuất', 'Cập nhật lô (ngày NL/BB, ưu tiên, chia lô...)', ['perm' => 'plan_production_update']],
            ['Kế Hoạch Sản Xuất', 'Hủy lô', ['perm' => 'plan_production_deActive']],
            ['Kế Hoạch Sản Xuất', 'Gửi kế hoạch', ['perm' => 'plan_production_send']],
            ['Kế Hoạch Sản Xuất', 'Xem dự trù nguyên liệu', ['perm' => 'plan_production_view_material']],
            ['Kế Hoạch Sản Xuất', 'Tạo bản sao lưu tồn kho MMS', ['perm' => 'plan_production_backup']],
            ['Phản hồi KHSX', 'Phản hồi kế hoạch tháng', ['perm' => 'plan_feedback']],
            ['Phản hồi KHSX', 'Phản hồi sau khi kế hoạch đã khoá (Tổ trưởng / Trưởng phòng)', ['perm' => 'plan_feedback_leader']],
            ['Theo Dõi Thẩm Định', 'Cập nhật danh sách theo dõi', ['perm' => 'validation_tracking_update']],
            ['Theo Dõi Thẩm Định', 'Phê duyệt', ['perm' => 'validation_tracking_approve']],
            ['Theo Dõi Hồ Sơ KCS', 'Cập nhật', ['perm' => 'kcs_tracking_update']],
            ['Thông Báo Đóng Gói', 'Cập nhật thông báo', ['perm' => 'packaging_notification_update_po']],
            ['Thông Báo Đóng Gói', 'Nhập thông tin lấy mẫu', ['perm' => 'packaging_notification_update_sampling']],
            ['Thông Báo Đóng Gói', 'Khoá / mở khoá', ['perm' => 'packaging_notification_lock']],
            ['Kế Hoạch HC - BT', 'Tạo kế hoạch mới', ['perm' => 'plan_maintenance_create_plan_list']],
            ['Kế Hoạch HC - BT', 'Gửi kế hoạch', ['perm' => 'plan_maintenance_send']],
            ['Kế Hoạch HC - BT', 'Phạm vi tạo / gửi', ['rule' => 'Ngoài quyền trên: Hiệu chuẩn chỉ phòng ban QA, Bảo trì và Tiện ích chỉ phòng ban EN. Nhóm quyền chính Admin làm được tất cả.', 'roles' => ['Admin']]],
            ['Kế Hoạch HC - BT', 'Cập nhật', ['perm' => 'plan_maintenance_update']],
            ['Kế Hoạch HC - BT', 'Hủy', ['perm' => 'plan_maintenance_deActive']],
        ],
        'schedual' => [
            ['Lịch Sản Xuất Chart', 'Xem biểu đồ', ['rule' => self::ALL]],
            ['Lịch Sản Xuất Chart', 'Tải danh sách KHSX (lô chờ sắp lịch)', ['perm' => 'loading_plan_waiting']],
            ['Lịch Sản Xuất Chart', 'Sửa lịch, sắp lịch tự động, Submit, Khóa TTSX', ['rule' => 'Nhóm quyền chính Admin hoặc Schedualer, và phân xưởng đang chọn trùng phòng ban của tài khoản. Phòng ban BOD sửa được mọi phân xưởng.', 'roles' => ['Admin', 'Schedualer']]],
            ['Lịch Sản Xuất Chart', 'Dòng tồn BTP chờ vào phòng', ['perm' => 'schedual_wip_rows']],
            ['Lịch Sản Xuất Chart', 'Lịch sử tịnh tuyến (chuột phải)', ['rule' => 'Chỉ các tài khoản được chỉ định trong mã (ScheduleRerouteService).']],
            ['Lịch tuần, Danh sách lịch, Tiến trình, Nhận bao bì', 'Thấy lịch chưa Submit', ['rule' => 'Nhóm quyền chính Admin, Schedualer hoặc Leader. Người khác chỉ thấy lịch đã Submit.', 'roles' => ['Admin', 'Schedualer', 'Leader']]],
            ['Lịch Sản Xuất Tuần', 'Lead xác nhận lịch (PXV1)', ['rule' => 'Nhóm quyền chính Leader hoặc Admin, phân xưởng PXV1.', 'roles' => ['Leader', 'Admin']]],
            ['Thực Thi Sản Xuất', 'Thấy mục trên menu', ['perm' => 'layout_production_execution']],
            ['Thực Thi Sản Xuất', 'Nhận phòng', ['perm' => 'execution_receive']],
            ['Thực Thi Sản Xuất', 'Nhận phòng vệ sinh sau bảo trì', ['perm' => 'execution_receive_cleaning']],
            ['Thực Thi Sản Xuất', 'Trả phòng', ['perm' => 'execution_release']],
            ['Ghi Nhận Sản Xuất', 'Vào trang', ['perm' => 'layout_production_record']],
            ['Ghi Nhận Sản Xuất', 'Chỉ vào được trang này', ['rule' => 'Tài khoản có nhóm quyền chính Executor: mọi màn hình khác tự chuyển về Ghi Nhận Sản Xuất.', 'roles' => ['Executor']]],
            ['Đề Nghị Thay Đổi KH', 'Thấy mục trên menu', ['perm' => 'layout_warning']],
            ['Đề Nghị Thay Đổi KH', 'Đề nghị ngày đáp ứng, thay đổi NL/BB', ['perm' => 'schedual_warning_propose']],
            ['Đề Nghị Thay Đổi KH', 'Chấp nhận / từ chối đề nghị', ['perm' => 'schedual_warning_approve']],
            ['Lịch Nhận Bao Bì', 'Phản hồi', ['perm' => 'receive_packaging_feedback']],
            ['Lịch Nhận Bao Bì', 'Đổi ngày nhận', ['perm' => 'Change_receive_packaging_date']],
            ['Lịch Nhận Bao Bì', 'Xác nhận đã nhận', ['perm' => 'confirm_receive_packaging']],
            ['Chính Sách Sản Lượng', 'Cài đặt', ['perm' => 'set_yield_policy']],
            ['Lịch Sử Thay Đổi Lịch', 'Thấy mục trên menu', ['perm' => 'schedual_audit']],
            ['Lịch Sử Thay Đổi Lịch', 'Xem cột Lý do', ['rule' => 'Nhóm quyền chính Admin hoặc Schedualer, hoặc phòng ban COMP.', 'roles' => ['Admin', 'Schedualer']]],
            ['Xác Nhận Hoàn Thành, Xác Định Phòng BT', 'Thấy mục trên menu', ['perm' => 'layout_finised']],
        ],
        'lichHcBt' => [
            ['Lịch HC-BT Chart', 'Xem biểu đồ', ['rule' => self::ALL]],
            ['Lịch HC-BT Chart', 'Tải danh sách chờ, sắp lịch (tay và tự động)', ['perm' => 'plan_maintenance_scheduler']],
            ['Lịch HC-BT Chart', 'Duyệt lịch', ['perm' => 'plan_maintenance_accept']],
            ['Lịch HC-BT Chart', 'Đánh giá công tác HC-BT', ['perm' => 'maintenance_assessment_create']],
            ['Lịch HC-BT Chart', 'Xem đánh giá', ['perm' => 'maintenance_assessment_view']],
            ['Lịch HC-BT Chart', 'Cập nhật nhân sự đánh giá', ['perm' => 'maintenance_assessment_update_employees']],
            ['Lịch HC-BT Chart', 'Nút Xong trên danh sách chờ', ['rule' => 'Nhóm quyền chính Admin.', 'roles' => ['Admin']]],
            ['Lịch HC-BT Chart', 'Phạm vi danh sách chờ (PXV1)', ['rule' => 'Nhóm quyền chính Leader ở PXV1 chỉ thấy lịch của các phòng thuộc tổ mình; Admin, Schedualer thấy tất cả.', 'roles' => ['Leader']]],
        ],
        'lichCongTac' => [
            ['Lịch Công Tác', 'Thấy nhóm menu', ['perm' => 'layout_assignment']],
            ['Sản Xuất', 'Phân công nhân sự sản xuất', ['perm' => 'production_assignment']],
            ['Bảo trì / Hiệu Chuẩn', 'Phân công nhân sự bảo trì - hiệu chuẩn', ['perm' => 'maintenance_assignment']],
            ['Bảo trì / Hiệu Chuẩn', 'Phạm vi tổ được sửa', ['rule' => 'Ngoài quyền trên, theo nhóm quyền chính: Admin và Engineer Manager sửa mọi tổ; Calibration and Maintenance Planning Manager phòng QA chỉ sửa tổ HC Thiết Bị, phòng EN sửa các tổ còn lại; Calibration and Maintenance Scheduler chỉ sửa tổ của mình. Bảng EN_ALL chỉ Engineer Manager sửa. Không sửa ngày đã qua.', 'roles' => ['Admin', 'Engineer Manager', 'Calibration and Maintenance Planning Manager', 'Calibration and Maintenance Scheduler']]],
            ['DashBoard Nhân Sự, Sản Xuất', 'Duyệt tăng ca vượt trần', ['perm' => 'Authorize_overtime']],
            ['DashBoard Nhân Sự, Sản Xuất, Bảo trì', 'Đồng bộ dữ liệu eO chủ động', ['perm' => 'e-o_synchronization']],
        ],
        'baoCao' => [
            ['Báo Cáo', 'Thấy nhóm menu và Báo Cáo Ngày SX', ['perm' => 'layout_daily_report']],
            ['Báo Cáo Ngày SX', 'Ghi chú sản lượng không đạt 90%', ['perm' => 'report_explanation']],
            ['Báo Cáo Ngày SX, Tuần, Tháng', 'Cập nhật hoạt động khác', ['perm' => 'update_daily_report']],
            ['Báo Cáo Tuần, Tháng, OEE', 'Thấy mục trên menu', ['perm' => 'layout_weekly_report']],
            ['Báo Cáo Tuần', 'Cập nhật ca, số ngày làm việc', ['perm' => 'report_update_sheets']],
        ],
        'tonBtp' => [
            ['Tồn BTP', 'Thấy nhóm menu (Tồn BTP lý thuyết, thực tế)', ['perm' => 'layout_quarantine']],
            ['Tồn Kho Lý Thuyết', 'Thấy mục và mở màn hình', ['perm' => 'layout_wip_coverage']],
        ],
        'lichSu' => [
            ['Lịch Sử', 'Thấy nhóm menu', ['perm' => 'layout_history']],
            ['Lịch Sử Sản Xuất', 'Trả lô về chưa hoàn thành', ['perm' => 'return_event']],
        ],
        'userPolicy' => [
            ['User Policy', 'Thấy nhóm menu, quản lý user, nhóm quyền, quyền riêng', ['perm' => 'layout_User']],
        ],
        'dangNhap' => [
            ['Toàn Hệ Thống', 'Xem lịch sử đăng nhập toàn hệ thống', ['perm' => 'login_history_view']],
            ['Lịch Sử Đăng Nhập (Audit Trail)', 'Xem nhật ký thao tác', ['rule' => self::ALL]],
        ],
    ];

    /** Nhóm quyền đang hoạt động, xếp Admin đầu, còn lại theo id. */
    public static function roles()
    {
        return DB::table('roles')->where('active', 1)
            ->orderByRaw("CASE WHEN name = 'Admin' THEN 0 ELSE 1 END")->orderBy('id')
            ->get(['id', 'name', 'display_name']);
    }

    /** [tên quyền => thông tin quyền] của các quyền đang có trong bảng permissions. */
    public static function permissions(): array
    {
        return DB::table('permissions')->get(['id', 'name', 'display_name'])->keyBy('name')->all();
    }

    /** [tên quyền => [tên nhóm quyền đang hoạt động có quyền đó]] */
    public static function rolesByPermission(): array
    {
        $rows = DB::table('role_permission')
            ->join('roles', 'roles.id', '=', 'role_permission.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('roles.active', 1)
            ->orderBy('roles.id')
            ->get(['permissions.name as perm', 'roles.name as role']);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->perm][] = $row->role;
        }

        return $map;
    }

    /** [tên quyền => ['allow' => số tài khoản được cấp riêng, 'deny' => số tài khoản bị từ chối riêng]] */
    public static function userOverrides(): array
    {
        $rows = DB::table('user_permission')
            ->join('permissions', 'permissions.id', '=', 'user_permission.permission_id')
            ->get(['permissions.name as perm', 'user_permission.is_denied']);

        $map = [];
        foreach ($rows as $row) {
            $key = $row->is_denied ? 'deny' : 'allow';
            $map[$row->perm][$key] = ($map[$row->perm][$key] ?? 0) + 1;
        }

        return $map;
    }

    /**
     * Các dòng của một tài liệu, đã gắn tên quyền hiển thị và nhóm quyền đang có quyền (đọc CSDL).
     *
     * @return array<int, array{screen:string, action:string, perm:?string, label:?string, missing:bool, rule:?string, roles:array, allow:int, deny:int}>
     */
    public static function rows(string $guide): array
    {
        $permissions = self::permissions();
        $byPerm = self::rolesByPermission();
        $overrides = self::userOverrides();

        $rows = [];
        foreach (self::MAP[$guide] ?? [] as [$screen, $action, $condition]) {
            $perm = $condition['perm'] ?? null;
            $rows[] = [
                'screen' => $screen,
                'action' => $action,
                'perm' => $perm,
                'label' => $perm ? ($permissions[$perm]->display_name ?? $perm) : null,
                'missing' => $perm && !isset($permissions[$perm]),
                'rule' => $condition['rule'] ?? null,
                'roles' => $perm ? ($byPerm[$perm] ?? []) : ($condition['roles'] ?? []),
                'allow' => $perm ? ($overrides[$perm]['allow'] ?? 0) : 0,
                'deny' => $perm ? ($overrides[$perm]['deny'] ?? 0) : 0,
            ];
        }

        return $rows;
    }

    /**
     * Ma trận nhóm quyền × quyền của một tài liệu: chỉ các dòng điều kiện là quyền, chỉ các nhóm quyền có ít nhất
     * một quyền trong tài liệu (để bảng gọn).
     *
     * @return array{rows: array, roles: \Illuminate\Support\Collection}
     */
    public static function matrix(string $guide): array
    {
        $rows = array_values(array_filter(self::rows($guide), fn ($row) => $row['perm'] !== null));
        $used = [];
        foreach ($rows as $row) {
            foreach ($row['roles'] as $role) {
                $used[$role] = true;
            }
        }

        return [
            'rows' => $rows,
            'roles' => self::roles()->filter(fn ($role) => isset($used[$role->name]))->values(),
        ];
    }

    /** Tên mọi quyền được khai trong MAP. */
    public static function mappedPermissions(): array
    {
        $names = [];
        foreach (self::MAP as $rows) {
            foreach ($rows as [, , $condition]) {
                if (!empty($condition['perm'])) {
                    $names[$condition['perm']] = true;
                }
            }
        }

        return array_keys($names);
    }
}
