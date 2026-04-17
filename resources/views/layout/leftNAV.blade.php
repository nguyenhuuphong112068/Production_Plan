<style>
    #backToTopBtn {
        display: none;
        /* Ẩn ban đầu */
        position: fixed;
        bottom: 20px;
        left: 20px;
        /* Góc dưới bên trái */
        width: 50px;
        height: 50px;
        background-color: rgba(77, 240, 13, 0.4);
        /* Màu mờ */
        color: white;
        border: none;
        border-radius: 8px;
        /* Bo góc nhẹ */
        font-size: 24px;
        cursor: pointer;
        z-index: 999;
        transition: background-color 0.1s;
    }

    /* Giữ sidebar cố định bên trái */
    .sidebar {

        position: fixed;
        top: 0px;
        left: -3px;

        height: 100vh;
        /* chiếm toàn bộ chiều cao trình duyệt */
        overflow-y: auto;
        /* nếu menu dài vẫn có thể cuộn riêng */
        z-index: 1000;
        /* nằm trên các phần khác */
    }

    /* Để phần nội dung không bị che bởi sidebar */
    .main-content {

        margin-left: 100px;
        /* đúng bằng chiều rộng sidebar */
        overflow-y: auto;
    }

    /* Gentle Stella Gold (Semi-transparent rgba) Active State */
    .nav-pills .nav-link.active,
    .nav-item.menu-open>.nav-link {
        background-color: rgba(205, 199, 23, 0.4) !important;
        /* Vàng Stella mờ nhẹ */
        color: #003a4f !important;
        /* Chữ Navy cho rõ nét */
        border-radius: 4px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .nav-pills .nav-link.active i,
    .nav-item.menu-open>.nav-link i {
        color: #003a4f !important;
    }
</style>

<aside class="main-sidebar sidebar-light-primary elevation-4" style="height: 100vh;";>
    <div class="sidebar">
        <!-- Brand Logo -->
        <a href="{{ route('pages.general.home') }}"
            class="brand-link container d-flex justify-content-center align-items-center">
            <img src="{{ asset('img/iconstella.svg') }}" alt="AdminLTE Logo"
                style="opacity: .8 ; max-width:43px; hight: auto">
        </a>

        <!-- Sidebar Menu -->
        <nav class="mt-2">

            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">

                <!-- Droplist Menu Chuyển Phân Xưởng  -->
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-capsules"></i>
                        <p>
                            {{ session('user')['production_name'] }}
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('switch', ['production_code' => 'PXV1', 'redirect' => url()->current()]) }}"
                                class="nav-link">
                                <i
                                    class="far fa-circle nav-icon {{ session('user')['production_code'] == 'PXV1' ? 'text-danger' : '' }}"></i>
                                <p>PXV1</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('switch', ['production_code' => 'PXV2', 'redirect' => url()->current()]) }}"
                                class="nav-link">
                                <i
                                    class="far fa-circle nav-icon {{ session('user')['production_code'] == 'PXV2' ? 'text-danger' : '' }}"></i>
                                <p>PXV2</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('switch', ['production_code' => 'PXVH', 'redirect' => url()->current()]) }}"
                                class="nav-link">
                                <i
                                    class="far fa-circle nav-icon {{ session('user')['production_code'] == 'PXVH' ? 'text-danger' : '' }}"></i>
                                <p>PXVH</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('switch', ['production_code' => 'PXTN', 'redirect' => url()->current()]) }}"
                                class="nav-link">
                                <i
                                    class="far fa-circle nav-icon {{ session('user')['production_code'] == 'PXTN' ? 'text-danger' : '' }}"></i>
                                <p>PXTN</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('switch', ['production_code' => 'PXDN', 'redirect' => url()->current()]) }}"
                                class="nav-link">
                                <i
                                    class="far fa-circle nav-icon {{ session('user')['production_code'] == 'PXDN' ? 'text-danger' : '' }}"></i>
                                <p>PXDN</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Droplist Menu Dữ Liệu Gốc  -->
                <li class="nav-item has-treeview {{ str_contains(url()->current(), 'materData') ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ str_contains(url()->current(), 'materData') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-database"></i>
                        <p>
                            Dữ Liệu Gốc
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('pages.materData.productName.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Tên Sản Phẩm</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.materData.room.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Phòng Sản Xuất</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.materData.Dosage.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dạng Bào Chế</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.materData.Unit.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Đơn Vị Tính</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.materData.Market.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Thị Trường</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.materData.Specification.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Qui Cách Sản Phẩm</p>
                            </a>
                        </li>

                        @if (user_has_permission(session('user')['userId'], 'layout_test', 'boolean'))
                            <li class="nav-item">
                                <a href="{{ route('pages.materData.offdays.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon text-warning"></i>
                                    <p>Cập nhật ngày nghỉ</p>
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a href="{{ route('pages.materData.stageGroup.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon text-info"></i>
                                <p>Tổ Quản Lý</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.materData.department.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon text-info"></i>
                                <p>Phòng Ban</p>
                            </a>
                        </li>


                    </ul>
                </li>

                <!-- Droplist Menu Danh Muc  -->
                <li class="nav-item has-treeview {{ str_contains(url()->current(), 'category') ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ str_contains(url()->current(), 'category') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-newspaper"></i>
                        <p>
                            Danh Mục
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">

                        <li class="nav-item">
                            <a href="{{ route('pages.category.intermediate.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Bán Thành Phẩm</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.category.product.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Thành Phẩm</p>
                            </a>
                        </li>
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p> BT - HC B1 <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('pages.category.maintenance.list', ['block' => 'B1', 'type' => 1]) }}"
                                        class="nav-link mx-4">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Hiệu Chuẩn</p>
                                    </a>
                                </li>
                                <li class="nav-item mx-4">
                                    <a href="{{ route('pages.category.maintenance.list', ['block' => 'B1', 'type' => 2]) }}"
                                        class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Bảo Trì</p>
                                    </a>
                                </li>
                                <li class="nav-item mx-4">
                                    <a href="{{ route('pages.category.maintenance.list', ['block' => 'B1', 'type' => 3]) }}"
                                        class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Tiện Ích</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p> BT - HC B2 <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item mx-4">
                                    <a href="{{ route('pages.category.maintenance.list', ['block' => 'B2', 'type' => 1]) }}"
                                        class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Hiệu Chuẩn</p>
                                    </a>
                                </li>
                                <li class="nav-item mx-4">
                                    <a href="{{ route('pages.category.maintenance.list', ['block' => 'B2', 'type' => 2]) }}"
                                        class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Bảo Trì</p>
                                    </a>
                                </li>
                                <li class="nav-item mx-4">
                                    <a href="{{ route('pages.category.maintenance.list', ['block' => 'B2', 'type' => 3]) }}"
                                        class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Tiện Ích</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <!-- Định Mức  -->
                <li class="nav-item has-treeview {{ str_contains(url()->current(), 'quota') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ str_contains(url()->current(), 'quota') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>
                            Định Mức
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">

                        <li class="nav-item">
                            <a href="{{ route('pages.quota.production.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Sản Xuất</p>
                            </a>
                        </li>

                        {{-- <li class="nav-item">
                            <a href="{{ route('pages.category.product.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Bảo Trì</p>
                            </a>
                        </li> --}}

                    </ul>
                </li>

                <!-- Droplist MMS-->
                @if (user_has_permission(session('user')['userId'], 'layout_assignment', 'boolean'))
                    <li class="nav-item has-treeview {{ str_contains(url()->current(), 'MMS') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ str_contains(url()->current(), 'MMS') ? 'active' : '' }}">

                            <i class="nav-icon fas fa-warehouse"></i>
                            <p>
                                MMS
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">

                            <li class="nav-item">
                                <a href="{{ route('pages.MMS.material.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Tồn Kho Nguyên Liệu</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('pages.MMS.packaging.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Tồn Kho Bao Bì</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('pages.MMS.finished_product.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Tồn Kho Thành Phẩm</p>
                                </a>
                            </li>



                        </ul>
                    </li>
                @endif
                <!-- Droplist Kế Hoạch-->
                <li class="nav-item has-treeview {{ str_contains(url()->current(), 'plan') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ str_contains(url()->current(), 'plan') ? 'active' : '' }}">

                        <i class="nav-icon fas fa-file-import"></i>
                        <p>
                            Kế Hoạch
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">

                        <li class="nav-item">
                            <a href="{{ route('pages.plan.production.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Kế Hoạch Sản Xuất</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.plan.production.feedback_list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Phản hồi KHSX</p>
                            </a>
                        </li>

                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Kế Hoạch HC-BT
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('pages.plan.maintenance.list', ['type' => 1]) }}"
                                        class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Hiệu Chuẩn</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('pages.plan.maintenance.list', ['type' => 2]) }}"
                                        class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Bảo Trì</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('pages.plan.maintenance.list', ['type' => 3]) }}"
                                        class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Tiện Ích</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                    </ul>
                </li>

                <!-- Droplist Menu Lịch SX -->
                <li
                    class="nav-item {{ str_contains(url()->current(), 'weekly-production-schedule') || str_contains(url()->current(), 'Schedual') ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ str_contains(url()->current(), 'weekly-production-schedule') || str_contains(url()->current(), 'Schedual') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-industry"></i>
                        <p>
                            Lịch Sản Xuất
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">

                        <li class="nav-item">
                            <a href="/Schedual" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p> Lịch Sản Xuất Chart</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.report.weekly_production_schedule.index') }}"
                                class="nav-link {{ str_contains(url()->current(), 'weekly-production-schedule') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p> Lịch Sản Xuất Tuần </p>
                            </a>
                        </li>

                        @if (user_has_permission(session('user')['userId'], 'layout_report', 'boolean'))
                            <li class="nav-item">
                                <a href="{{ route('pages.Schedual.report.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Báo Cáo </p>
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a href="{{ route('pages.Schedual.list.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p> Danh Sách Lịch</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.Schedual.step.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Tiến Trình Sản Xuất</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.Schedual.clearning_validation.index') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p> Lịch Thẩm Định Vệ Sinh</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.Schedual.receive_packaging.list') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Lịch Nhận Bao Bì</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.Schedual.yield.index') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Sản Lượng</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.Schedual.audit.index') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Lịch Sử Thay Đổi Lịch</p>
                            </a>
                        </li>

                        @if (user_has_permission(session('user')['userId'], 'layout_finised', 'boolean'))
                            <li class="nav-item">
                                <a href="{{ route('pages.Schedual.finised.index') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Xác Nhận Hoàn Thành</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('pages.Schedual.quarantine_room.index') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Xác Định Phòng BT</p>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>


                <li
                    class="nav-item {{ str_contains(url()->current(), 'maintenance-weekly-report') || str_contains(url()->current(), 'maintenance-calendar') ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ str_contains(url()->current(), 'maintenance-weekly-report') || str_contains(url()->current(), 'maintenance-calendar') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tools"></i>
                        <p>
                            Lịch HC-BT
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/maintenance-calendar" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p> Lịch HC-BT Chart </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('pages.report.maintenance_weekly_report.index') }}"
                                class="nav-link {{ str_contains(url()->current(), 'maintenance-weekly-report') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p> Lịch HC-BT Tuần </p>
                            </a>
                        </li>
                    </ul>
                </li>


                <!-- Droplist Phân Công -->
                @if (user_has_permission(session('user')['userId'], 'layout_assignment', 'boolean'))
                    <li
                        class="nav-item has-treeview {{ str_contains(url()->current(), 'assignment') || str_contains(url()->current(), 'personnel') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ str_contains(url()->current(), 'assignment') || str_contains(url()->current(), 'personnel') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-check"></i>
                            <p>
                                Phân Công
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            {{-- @if (user_has_permission(session('user')['userId'], 'production_assignment', 'boolean')) --}}
                            <li class="nav-item">
                                <a href="{{ route('pages.assignment.production.index') }}"
                                    class="nav-link {{ str_contains(url()->current(), 'assignment/production') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Phân Công Sản Xuất</p>
                                </a>
                            </li>
                            {{-- @endif
                            @if (user_has_permission(session('user')['userId'], 'maintenance_assignment', 'boolean')) --}}
                            <li class="nav-item">
                                <a href="{{ route('pages.assignment.maintenance.portal') }}"
                                    class="nav-link {{ str_contains(url()->current(), 'assignment/maintenance') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon text-warning"></i>
                                    <p>Phân Công Bảo Trì</p>
                                </a>
                            </li>
                            {{-- @endif --}}

                            @if (user_has_permission(session('user')['userId'], 'personnel_assignment', 'boolean'))
                                <li
                                    class="nav-item has-treeview {{ str_contains(url()->current(), 'personnel') ? 'menu-open' : '' }}">
                                    <a href="#"
                                        class="nav-link {{ str_contains(url()->current(), 'personnel') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon text-info"></i>
                                        <p>
                                            Danh Sách Nhân Viên
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview ml-2">
                                        @foreach (['PXV1', 'PXV2', 'PXVH', 'PXTN', 'PXDN', 'EN', 'QA'] as $dept)
                                            <li class="nav-item">
                                                <a href="{{ route('pages.assignment.personnel.list', ['department' => $dept]) }}"
                                                    class="nav-link {{ request()->department == $dept ? 'active' : '' }}">
                                                    <i class="far fa-dot-circle nav-icon"></i>
                                                    <p>{{ $dept }}</p>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                <!-- Droplist Menu Báo Cáo  -->
                @if (user_has_permission(session('user')['userId'], 'layout_daily_report', 'boolean'))
                    <li
                        class="nav-item has-treeview {{ str_contains(url()->current(), 'daily_report') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ str_contains(url()->current(), 'daily_report') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-crosshairs"></i>
                            <p>
                                Báo Cáo
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('pages.report.daily_report.index') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Báo Cáo Ngày SX </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('pages.report.maintenance_daily_report.index') }}"
                                    class="nav-link">
                                    <i class="far fa-circle nav-icon text-warning"></i>
                                    <p> Báo Cáo Ngày BT </p>
                                </a>
                            </li>
                            @if (user_has_permission(session('user')['userId'], 'layout_weekly_report', 'boolean'))
                                <li class="nav-item">
                                    <a href="{{ route('pages.report.weekly_report.index') }}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p> Báo Cáo Tuần </p>
                                    </a>
                                </li>
                                @if (user_has_permission(session('user')['userId'], 'layout_monthly_report', 'boolean'))
                                @endif
                                <li class="nav-item">
                                    <a href="{{ route('pages.report.monthly_report.index') }}" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p> Báo Cáo Tháng </p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('pages.report.oee_report.index') }}" class="nav-link">
                                        <i class="far fa-circle nav-icon text-info"></i>
                                        <p> Báo Cáo OEE </p>
                                    </a>
                                </li>
                            @endif

                        </ul>
                    </li>
                @endif

                <!-- Droplist Menu Biệt Trữ -->
                @if (user_has_permission(session('user')['userId'], 'layout_quarantine', 'boolean'))
                    <!-- Droplist Menu Biệt Trữ -->
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-pallet"></i>
                            <p>
                                Tồn BTP
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">
                            {{-- 
                            <li class="nav-item">
                                <a href="{{ route('pages.quarantine.theory.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Tồn BTP Lý Thyết </p>
                                </a>
                            </li> --}}

                            <li class="nav-item">
                                <a href="{{ route('pages.quarantine.actual.index_actual') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Tồn BTP Thực Tế </p>
                                </a>
                            </li>

                        </ul>
                    </li>
                @endif

                <!-- roplist Trang Thái Sản Xuất-->
                @if (user_has_permission(session('user')['userId'], 'layout_status', 'boolean'))
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-recycle"></i>
                            <p>
                                Trang Thái SX
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">

                            <li class="nav-item">
                                <a href="{{ route('pages.status.index') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Tạo Mới Trang Thái </p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('pages.status.history.index') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Cập Nhật Trang Thái </p>
                                </a>
                            </li>

                        </ul>
                    </li>
                @endif

                <!-- Droplist Thống Kê -->
                @if (user_has_permission(session('user')['userId'], 'layout_statistics', 'boolean'))
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>
                                Thống Kê
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">

                            <li class="nav-item">
                                <a href="{{ route('pages.statistics.product.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Sản Phẩm </p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('pages.statistics.room.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Phòng Sản Xuất </p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('pages.statistics.stage.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Công Đoạn Sản Xuất </p>
                                </a>
                            </li>

                        </ul>
                    </li>
                @endif

                <!-- History-->
                @if (user_has_permission(session('user')['userId'], 'layout_history', 'boolean'))
                    <li
                        class="nav-item has-treeview {{ str_contains(url()->current(), 'History') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ str_contains(url()->current(), 'History') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-history"></i>
                            <p>
                                Lịch Sử
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('pages.History.production.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Lịch Sử Sản Xuất</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('pages.History.maintenance.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Lịch Sử HC - BT</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                <!-- User-->
                @if (user_has_permission(session('user')['userId'], 'layout_User', 'boolean'))
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-user"></i>
                            <p>
                                User Policy
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('pages.User.user.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Danh Sách User </p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('pages.User.role.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Nhóm Phân Quyền </p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('pages.User.permission.list') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p> Danh Sách Quyền </p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                <!-- Audit Trial-->
                <li class="nav-item">
                    <a href="{{ route('pages.AuditTrail.list') }}" class="nav-link">
                        <i class="nav-icon fas fa-th"></i>
                        <p>
                            Audit Trail
                        </p>
                    </a>
                </li>

                @if (user_has_permission(session('user')['userId'], 'layout_test', 'boolean'))
                    <li class="nav-item">
                        <a href="{{ route('pages.Schedual.test') }}" class="nav-link">
                            <i class="nav-icon fas fa-th"></i>
                            <p>
                                Test Route
                            </p>
                        </a>
                    </li>
                @endif
            </ul>

        </nav>

        <button onclick="scrollToTop()" id="backToTopBtn" title="Trở về đầu trang" class = "btn btn-success">
            <i class="fas fa-chevron-up"></i>
        </button>


        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

<script>
    // Hiện nút khi scroll xuống 300px
    window.onscroll = function() {
        const btn = document.getElementById("backToTopBtn");
        if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
            btn.style.display = "block";
        } else {
            btn.style.display = "none";
        }
    };

    // Cuộn mượt về đầu trang
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    }
</script>
