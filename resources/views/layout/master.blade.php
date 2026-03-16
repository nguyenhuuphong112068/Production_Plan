<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>PSM Stellapharm</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">

    @include('layout.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tributejs@5.1.3/dist/tribute.css">
    <style>
        /* NOTIFICATION DRAWER CSS */
        #notification-drawer {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100%;
            background: #fff;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        #notification-drawer.open {
            right: 0;
        }

        #notification-drawer-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #notification-drawer-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .notif-tabs {
            display: flex;
            padding: 0 20px;
            border-bottom: 1px solid #eee;
        }

        .notif-tab {
            padding: 10px 15px;
            cursor: pointer;
            color: #666;
            border-bottom: 2px solid transparent;
        }

        .notif-tab.active {
            color: #28a745;
            border-bottom-color: #28a745;
            font-weight: bold;
        }

        #notification-drawer-items {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }

        .notif-date-group {
            padding: 10px 20px;
            background: #f8f9fa;
            font-size: 12px;
            font-weight: bold;
            color: #888;
            text-transform: uppercase;
        }

        .notif-item {
            padding: 15px 20px;
            display: flex;
            gap: 15px;
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }

        .notif-item:hover {
            background: #f0f7f2;
        }

        .notif-item.unread {
            background: #f3fcf5;
        }

        .notif-content {
            flex: 1;
        }

        .notif-title {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .notif-title b {
            color: #333;
        }

        .notif-message {
            font-size: 13px;
            color: #666;
            border-left: 3px solid #ddd;
            padding-left: 10px;
            margin: 5px 0;
        }

        .notif-time {
            font-size: 11px;
            color: #999;
        }

        .unread-indicator {
            width: 10px;
            height: 10px;
            background: #007bff;
            border-radius: 50%;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        #notification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: 9998;
            display: none;
        }

        /* FLOATING BELL BUTTON - Now integrated into Header */
        #notif-bell-btn {
            width: 40px;
            height: 40px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
            position: relative;
            margin: 0 10px;
        }

        #notif-bell-btn:hover {
            transform: scale(1.1);
            background: #f8f9fa;
        }

        #notif-bell-btn .badge {
            position: absolute;
            top: -2px;
            right: -2px;
            padding: 3px 5px;
            font-size: 10px;
        }
    </style>

</head>

<body class="hold-transition sidebar-mini">

    <!-- General wrapper -->
    <div class="wrapper">

        @yield('topNAV')

        @yield('leftNAV')

        @yield('mainContent')

        <!-- NOTIFICATION CENTER -->
        <div id="notification-overlay"></div>
        <div id="notification-drawer">
            <div id="notification-drawer-header">
                <h3>Thông báo</h3>
                <button type="button" class="btn btn-sm btn-light" id="close-notif-drawer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="notif-tabs">
                <div class="notif-tab active" data-tab="all">Tất cả</div>
                <div class="notif-tab" data-tab="unread">Chưa đọc</div>
            </div>
            <div id="notification-drawer-items">
                <!-- Items will be loaded here -->
            </div>
            <div class="p-3 text-center border-top">
                <a href="#" style="color: #666; font-size: 13px;">Xem thêm thông báo cũ hơn</a>
            </div>
        </div>

        @yield('model')

        @yield('script')


    </div>
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    @include('layout.js')
    <!-- page script -->
    <!-- page script -->
    <script>
        $(function() {
            $("#example1").DataTable({
                "responsive": true,
                "autoWidth": false,
            });
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
            });
        });
    </script>

    {{-- Tự động Logout khi không sử dụng sau 1 tiếng --}}
    <script>
        (function() {
            // Lấy thời gian sống của session từ server (phút) chuyển sang mili giây
            // Mặc định là 60 phút nếu không lấy được
            const sessionLifetime = {{ config('session.lifetime') }} * 60 * 1000;
            let timeout;

            function resetTimer() {
                clearTimeout(timeout);
                timeout = setTimeout(logout, sessionLifetime);
            }

            function logout() {
                // Chuyển hướng người dùng về trang login kèm tham số báo hết hạn
                window.location.replace("{{ route('login') }}?timeout=true");
            }

            // Các sự kiện được coi là người dùng đang hoạt động
            const events = ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'];

            events.forEach(function(event) {
                window.addEventListener(event, resetTimer);
            });

            // Khởi tạo lần đầu
            resetTimer();

            // Xử lý lỗi AJAX toàn cục (419 - Page Expired)
            $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
                if (jqXHR.status === 419 || jqXHR.status === 401) {
                    window.location.href = "{{ route('login') }}";
                }
            });

            // --- HỆ THỐNG THÔNG BÁO MỚI ---
            function toggleDrawer(show) {
                if (show) {
                    $('#notification-drawer').addClass('open');
                    $('#notification-overlay').fadeIn();
                } else {
                    $('#notification-drawer').removeClass('open');
                    $('#notification-overlay').fadeOut();
                }
            }

            $('#notif-bell-btn, #close-notif-drawer, #notification-overlay').on('click', function() {
                toggleDrawer($('#notification-drawer').hasClass('open') ? false : true);
            });

            function loadNotifications() {
                $.get("{{ route('notifications.list') }}", function(data) {
                    let unreadCount = data.filter(n => n.is_read == 0).length;
                    if (unreadCount > 0) {
                        $('#notif-badge-navbar').text(unreadCount).show();
                    } else {
                        $('#notif-badge-navbar').hide();
                    }

                    let html = '';
                    if (data.length === 0) {
                        html = '<div class="text-center p-5 text-muted">Không có thông báo mới</div>';
                    } else {
                        // Nhóm theo ngày
                        let groups = {};
                        data.forEach(n => {
                            let dateLabel = moment(n.created_at).calendar(null, {
                                sameDay: '[Hôm nay]',
                                lastDay: '[Hôm qua]',
                                lastWeek: 'DD/MM/YYYY',
                                sameElse: 'DD/MM/YYYY'
                            });
                            if (!groups[dateLabel]) groups[dateLabel] = [];
                            groups[dateLabel].push(n);
                        });

                        for (let date in groups) {
                            html += `<div class="notif-date-group">${date}</div>`;
                            groups[date].forEach(n => {
                                let isUnread = n.is_read == 0 ? 'unread' : '';
                                html += `
                                    <div class="notif-item ${isUnread}" onclick="markNotificationRead(${n.id}, '${n.url}')">
                                        <div class="notif-content">
                                            <div class="notif-title"><b>${n.sender_name}</b> đã ${n.activity_type}</div>
                                            <div class="notif-message">${n.message}</div>
                                            <div class="notif-time">${moment(n.created_at).format('HH:mm DD/MM/YYYY')}</div>
                                        </div>
                                        ${n.is_read == 0 ? '<div class="unread-indicator"></div>' : ''}
                                    </div>
                                `;
                            });
                        }
                    }
                    $('#notification-drawer-items').html(html);
                    $('#notification-items').html(html); // Dự phòng cho các mẫu cũ
                });
            }

            window.markNotificationRead = function(id, targetUrl) {
                $.post("{{ route('notifications.markAsRead') }}", {
                    _token: "{{ csrf_token() }}",
                    notification_id: id
                }, function() {
                    loadNotifications();
                    
                    // ĐIỀU HƯỚNG ĐỘNG TỪ DATABASE
                    if (targetUrl && targetUrl !== 'null' && targetUrl !== 'undefined') {
                        window.location.href = targetUrl;
                    }
                });
            };

            loadNotifications();
            setInterval(loadNotifications, 60000);
        })();
    </script>
</body>

</html>
