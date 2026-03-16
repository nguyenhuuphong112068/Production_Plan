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

        /* --- CHAT CSS --- */
        .chat-sidebar {
            position: fixed;
            right: -320px;
            top: 0;
            width: 320px;
            height: 100%;
            background: #fff;
            box-shadow: -2px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1051;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .chat-sidebar.active {
            right: 0;
        }

        .chat-header {
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-list {
            flex: 1;
            overflow-y: auto;
        }

        .chat-tab-item {
            padding: 10px 15px;
            cursor: pointer;
            color: #666;
            border-bottom: 2px solid transparent;
        }

        .chat-tab-item.active {
            color: #28a745;
            border-bottom: 2px solid #28a745;
            font-weight: bold;
        }

        .chat-group-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f1f1;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background 0.2s;
        }

        .chat-group-item:hover {
            background: #f8f9fa;
        }

        .user-initials {
            width: 40px;
            height: 40px;
            background: #28a745; /* Màu xanh lá chủ đạo */
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
            font-size: 16px;
        }

        .chat-group-info {
            flex: 1;
            min-width: 0;
        }

        .chat-group-name {
            font-weight: 600;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #28a745; /* Màu tên người dùng */
        }

        .chat-group-last-msg {
            font-size: 12px;
            color: #777;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            display: inline-block;
            margin-left: 5px;
        }

        .chat-trigger .unread-badge-total {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .online-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
            vertical-align: middle;
            box-shadow: 0 0 2px rgba(0, 0, 0, 0.2);
        }

        /* Floating Windows */
        .chat-window-container {
            position: fixed;
            bottom: 0;
            right: 60px;
            /* Offset from sidebar trigger if any */
            display: flex;
            flex-direction: row-reverse;
            align-items: flex-end;
            z-index: 1050;
            pointer-events: none;
        }

        .chat-window {
            width: 300px;
            height: 400px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            margin-left: 10px;
            display: flex;
            flex-direction: column;
            pointer-events: auto;
            position: relative;
        }

        .chat-resizer {
            width: 15px;
            height: 15px;
            position: absolute;
            top: -2px;
            left: -2px;
            cursor: nw-resize;
            z-index: 10;
            background: transparent;
        }

        .chat-window.maximized {
            position: fixed;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            border-radius: 0;
            z-index: 2000;
        }

        .chat-window.maximized .chat-window-content,
        .chat-window.maximized .chat-window-footer {
            padding: 10px 5px;
        }

        .chat-window.maximized .msg-text {
            font-size: 16px;
        }

        .chat-window.maximized .chat-input {
            font-size: 16px;
            padding: 10px;
        }

        body.chat-maximized {
            overflow: hidden;
        }

        .chat-window-header {
            padding: 8px 12px;
            background: #28a745; /* Xanh lá */
            color: white;
            border-radius: 7px 7px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .chat-window-content {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            background: #ffffff; /* Trắng tinh khiết cho thoáng */
            display: flex;
            flex-direction: column;
        }

        .chat-window-footer {
            padding: 8px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            border: none;
            outline: none;
            padding: 5px;
            font-size: 13px;
        }

        .msg-item {
            margin-bottom: 8px;
            max-width: 90%;
            padding: 8px 12px;
            position: relative;
            align-self: flex-start; /* Tất cả căn trái */
        }

        .msg-item.me {
            background: #e8f5e9; /* Xanh lá cực nhạt */
            color: #1b5e20; /* Chữ xanh lá đậm */
            border-radius: 0 12px 12px 12px;
            border-left: 3px solid #81c784; /* Viền xanh lá sáng */
            margin-left: 0;
        }

        .msg-item.me .msg-text {
            color: #1b5e20;
        }

        .msg-item.me .msg-status, .msg-item.me .msg-sender {
            color: #666;
        }

        .msg-item.other {
            align-self: flex-start;
            background: #f5f5f5; /* Xám cực nhạt */
            color: #333;
            border-radius: 0 12px 12px 12px;
            border-left: 3px solid #e0e0e0; /* Viền xám nhạt */
        }

        .msg-sender {
            font-size: 11px;
            color: #28a745; /* Màu tên người gửi trong chat */
            font-weight: bold;
            margin-bottom: 4px;
        }

        .msg-status {
            font-size: 10px;
            color: #999;
            margin-top: 4px;
            display: flex;
            justify-content: flex-start;
            gap: 10px;
        }

        .msg-item.me .msg-status {
            justify-content: flex-start;
        }

        .chat-trigger {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: #28a745; /* Xanh lá */
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: transform 0.2s;
        }

        .chat-trigger:hover {
            transform: scale(1.1);
        }

        .emoji-picker {
            position: absolute;
            bottom: 50px;
            right: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1060;
        }

        .emoji-item {
            cursor: pointer;
            font-size: 18px;
            padding: 2px;
            text-align: center;
        }

        .emoji-item:hover {
            background: #f0f0f0;
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

        <!-- CHAT CENTER -->
        <div id="chat-overlay"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); z-index:1050;"
            onclick="toggleChat(false)"></div>
        <div id="chat-sidebar" class="chat-sidebar">
            <div class="chat-header">
                <h5 class="mb-0">TIN NHẮN</h5>
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-outline-primary me-2" onclick="showCreateGroupModal()">
                        <i class="fas fa-plus"></i> Nhóm
                    </button>
                    <button class="btn btn-sm btn-light" onclick="toggleChat(false)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-2 border-bottom">
                <input type="text" class="form-control form-control-sm" placeholder="Tìm kiếm đồng nghiệp..."
                    id="chatSearch" onkeyup="searchChat()">
            </div>
            <div class="notif-tabs" style="border-top:none;">
                <div class="notif-tab active" id="tab-conversations" onclick="switchChatTab('conv')">Hội thoại</div>
                <div class="notif-tab" id="tab-contacts" onclick="switchChatTab('contacts')">Danh bạ</div>
            </div>
            <div class="chat-list" id="chatList">
                <!-- Data will be loaded here -->
            </div>
            <div class="chat-list d-none" id="contactList">
                <!-- Users will be loaded here -->
            </div>
        </div>

        <div id="chat-window-container" class="chat-window-container"></div>

        <div class="chat-trigger" onclick="toggleChat(true)">
            <i class="fas fa-comments"></i>
            <span id="unread-total-badge" class="unread-badge-total d-none">0</span>
        </div>

        <!-- Modal Tạo Nhóm Chat -->
        <div class="modal fade" id="createGroupModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tạo nhóm chat mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên nhóm</label>
                            <input type="text" id="newGroupName" class="form-control" placeholder="Nhập tên nhóm...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Chọn thành viên</label>
                            <div id="userListForGroup"
                                style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px; border-radius: 5px;">
                                <!-- User list will be loaded here -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="submitCreateGroup()">Tạo nhóm</button>
                    </div>
                </div>
            </div>
        </div>

        @yield('model')

        @yield('script')

        <!-- Audio for notifications -->
        <audio id="chat-notif-sound" src="https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3"
            preload="auto"></audio>


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

            // --- HỆ THỐNG CHAT MỚI ---
            let openChatGroups = []; // Danh sách các nhóm đang mở cửa sổ chat
            let chatGroupLastTimes = {}; // Lưu trữ thời gian tin nhắn cuối cùng của từng nhóm
            // task.md
            // - [x] Tối ưu giao diện toàn màn hình, căn lề sát trái <!-- id: 51 -->
            // - [x] Sửa lỗi 404 khi mở file đính kèm <!-- id: 52 -->
            // - [x] Tinh chỉnh màu sắc chat nhẹ nhàng, dễ nhìn <!-- id: 54 -->
            // - [x] Sửa lỗi tự động cuộn khi đang xem tin nhắn cũ <!-- id: 55 -->
            // - [x] Kiểm tra và tối ưu hóa hiệu năng <!-- id: 32 -->
            let currentUserId = {{ session('user')['userId'] }};

            function playChatSound() {
                try {
                    document.getElementById('chat-notif-sound').play();
                } catch (e) {}
            }

            let blinkInterval = null;
            let originalTitle = document.title;

            function blinkTitle(msg) {
                if (blinkInterval) return;
                blinkInterval = setInterval(() => {
                    document.title = document.title === originalTitle ? msg : originalTitle;
                }, 1000);
            }
            $(window).on('focus click keydown', function() {
                if (blinkInterval) {
                    clearInterval(blinkInterval);
                    blinkInterval = null;
                    document.title = originalTitle;
                }
            });

            window.toggleChat = function(show) {
                if (show) {
                    $('#chat-sidebar').addClass('active');
                    $('#chat-overlay').fadeIn();
                    loadChatGroups();
                    loadContacts();
                } else {
                    $('#chat-sidebar').removeClass('active');
                    $('#chat-overlay').fadeOut();
                }
            };

            window.switchChatTab = function(tab) {
                $('.chat-sidebar .notif-tab').removeClass('active');
                if (tab === 'conv') {
                    $('#tab-conversations').addClass('active');
                    $('#chatList').removeClass('d-none');
                    $('#contactList').addClass('d-none');
                } else {
                    $('#tab-contacts').addClass('active');
                    $('#chatList').addClass('d-none');
                    $('#contactList').removeClass('d-none');
                }
            };

            function loadChatGroups() {
                $.get("{{ route('chat.groups') }}", function(data) {
                    let html = '';
                    let totalUnread = 0;

                    data.forEach(g => {
                        let unreadHtml = g.unread_count > 0 ?
                            `<span class="unread-badge">${g.unread_count}</span>` : '';
                        let onlineHtml = g.is_online ?
                            `<span class="online-dot" title="Online"></span>` : '';
                        totalUnread += g.unread_count;

                        html += `
                            <div class="chat-group-item" onclick="openChatWindow(${g.id}, '${g.display_name}', ${g.is_online || false})">
                                <div class="chat-group-info">
                                    <div class="chat-group-name">
                                        ${onlineHtml}
                                        <b>${g.display_name}</b>
                                        ${unreadHtml}
                                    </div>
                                    <div class="chat-group-last-msg">${g.last_message || 'Chưa có tin nhắn'}</div>
                                </div>
                            </div>
                        `;

                        // Tự động mở cửa sổ chat nếu có tin nhắn mới và không phải mình gửi
                        if (g.last_time) {
                            if (chatGroupLastTimes[g.id] && g.last_time > chatGroupLastTimes[g.id]) {
                                if (g.last_sender_id != currentUserId) {
                                    playChatSound();
                                    blinkTitle("Có tin nhắn mới...");
                                    if (!openChatGroups.includes(g.id)) {
                                        openChatWindow(g.id, g.display_name, g.is_online || false);
                                    }
                                }
                            }
                            chatGroupLastTimes[g.id] = g.last_time;
                        }
                    });

                    // The original code had a block here to update lastChatCheckTime, which is now handled by chatGroupLastTimes
                    // if (data.length > 0) {
                    //     let maxTime = data.reduce((max, obj) => obj.last_time > max ? obj.last_time : max, lastChatCheckTime);
                    //     lastChatCheckTime = maxTime;
                    // }

                    if (totalUnread > 0) {
                        $('#unread-total-badge').text(totalUnread).removeClass('d-none');
                    } else {
                        $('#unread-total-badge').addClass('d-none');
                    }

                    $('#chatList').html(html ||
                        '<div class="text-center p-3 text-muted">Chưa có hội thoại nào</div>');
                });
            }

            function loadContacts() {
                $.get("{{ route('chat.users') }}", function(data) {
                    let html = '';
                    data.forEach(u => {
                        let onlineHtml = u.is_online ?
                            `<span class="online-dot" title="Online"></span>` : '';
                        html += `
                            <div class="chat-group-item contact-item" onclick="startDirectChat(${u.id}, '${u.fullName}', ${u.is_online || false})">
                                <div class="chat-group-info">
                                    <div class="chat-group-name">
                                        ${onlineHtml}
                                        <b>${u.fullName}</b>
                                    </div>
                                    <div class="chat-group-last-msg">@${u.userName}</div>
                                </div>
                            </div>
                        `;
                    });
                    $('#contactList').html(html);
                });
            }

            window.startDirectChat = function(userId, fullName, isOnline) {
                $.post("{{ route('chat.getDirectChat') }}", {
                    _token: "{{ csrf_token() }}",
                    target_user_id: userId
                }, function(group) {
                    openChatWindow(group.id, fullName, isOnline);
                });
            };

            window.searchChat = function() {
                let val = $('#chatSearch').val().toLowerCase();
                $('.chat-group-item').each(function() {
                    let text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(val) > -1);
                });
            };

            window.openChatWindow = function(groupId, groupName, isOnline) {
                if (openChatGroups.includes(groupId)) return;
                if (openChatGroups.length >= 3) {
                    let oldest = openChatGroups.shift();
                    $(`#chat-window-${oldest}`).remove();
                }

                let onlineHtml = isOnline ?
                    `<span class="online-dot me-1" title="Online" style="border: 1px solid white;"></span>` : '';

                openChatGroups.push(groupId);
                let html = `
                    <div class="chat-window" id="chat-window-${groupId}">
                        <div class="chat-resizer" onmousedown="initChatResize(event, ${groupId})"></div>
                        <div class="chat-window-header" onclick="handleChatHeaderClick(${groupId})" ondblclick="toggleChatWindowMax(${groupId})">
                            <span class="chat-window-title">
                                ${onlineHtml}
                                <b>${groupName}</b>
                            </span>
                            <div class="chat-window-actions">
                                <i class="fas fa-minus me-2"></i>
                                <i class="fas fa-times" onclick="closeChatWindow(event, ${groupId})"></i>
                            </div>
                        </div>
                        <div class="chat-window-content" id="chat-content-${groupId}">
                            <!-- Messages -->
                        </div>
                        <div class="chat-window-footer">
                            <label class="mb-0 me-2" style="cursor:pointer">
                                <i class="fas fa-paperclip text-muted"></i>
                                <input type="file" style="display:none" onchange="uploadFile(this, ${groupId})">
                            </label>
                            <input type="text" class="chat-input" placeholder="Nhập tin nhắn..." 
                                onkeypress="if(event.key === 'Enter') sendChatMessage(${groupId}, this)"
                                onpaste="handleChatPaste(event, ${groupId})">
                            <i class="far fa-smile ms-2 text-muted" style="cursor:pointer" onclick="toggleEmojiPicker(${groupId})"></i>
                        </div>
                    </div>
                `;
                $('#chat-window-container').append(html);
                loadChatMessages(groupId, true);
                markChatAsRead(groupId);
                toggleChat(false);
            };

            window.markChatAsRead = function(groupId) {
                $.post("{{ route('chat.markAsRead') }}", {
                    _token: "{{ csrf_token() }}",
                    group_id: groupId
                }, function() {
                    loadChatGroups();
                });
            };

            window.closeChatWindow = function(event, groupId) {
                event.stopPropagation();
                openChatGroups = openChatGroups.filter(id => id !== groupId);
                $(`#chat-window-${groupId}`).remove();
            };

            let chatClickTimer = null;
            window.handleChatHeaderClick = function(groupId) {
                if (chatClickTimer) {
                    clearTimeout(chatClickTimer);
                    chatClickTimer = null;
                    return;
                }
                chatClickTimer = setTimeout(() => {
                    toggleChatWindowMin(groupId);
                    chatClickTimer = null;
                }, 250); // Đợi 250ms để xem có click thứ 2 không
            };

            window.toggleChatWindowMin = function(groupId) {
                let win = $(`#chat-window-${groupId}`);
                if (win.height() > 50) {
                    win.data('old-height', win.height());
                    win.css('height', '40px');
                } else {
                    let oldH = win.data('old-height') || '400px';
                    win.css('height', oldH);
                }
            };

            window.toggleChatWindowMax = function(groupId) {
                if (chatClickTimer) {
                    clearTimeout(chatClickTimer);
                    chatClickTimer = null;
                }
                let win = $(`#chat-window-${groupId}`);
                let isMax = win.hasClass('maximized');
                
                // Đóng các cửa sổ khác nếu đang phóng to (tùy chọn, để đỡ rối)
                if (!isMax) {
                    $('.chat-window').not(win).removeClass('maximized');
                    $('body').addClass('chat-maximized');
                } else {
                    $('body').removeClass('chat-maximized');
                }

                win.toggleClass('maximized');
                
                // Cuộn xuống cuối sau khi phóng to
                setTimeout(() => {
                    let contentDiv = document.getElementById(`chat-content-${groupId}`);
                    if (contentDiv) contentDiv.scrollTop = contentDiv.scrollHeight;
                }, 350);
            };

            function loadChatMessages(groupId, forceScroll = false) {
                let url = "{{ route('chat.messages', ':groupId') }}".replace(':groupId', groupId);
                let contentDiv = document.getElementById(`chat-content-${groupId}`);
                
                // Kiểm tra xem người dùng có đang ở gần đáy không (sai số 50px)
                let isAtBottom = contentDiv ? (contentDiv.scrollHeight - contentDiv.scrollTop <= contentDiv.clientHeight + 50) : true;

                $.get(url, function(res) {
                    let html = '';
                    let currentUserId = {{ session('user')['userId'] }};
                    let messages = res.messages;
                    let othersLastRead = res.others_last_read;

                    messages.forEach(m => {
                        let side = m.sender_id == currentUserId ? 'me' : 'other';
                        let content = m.message || '';
                        if (m.file_path) {
                            let fPath = m.file_path.startsWith('http') ? m.file_path : (m.file_path.startsWith('/') ? m.file_path : '/storage/' + m.file_path);
                            if (m.file_type && m.file_type.startsWith('image/')) {
                                content +=
                                    `<div class="mt-1"><img src="${fPath}" style="max-width:100%; border-radius:5px; cursor:pointer;" onclick="window.open('${fPath}', '_blank')"></div>`;
                            } else {
                                content +=
                                    `<div class="mt-1"><a href="${fPath}" target="_blank" class="text-primary font-weight-bold"><i class="fas fa-file-download"></i> ${m.file_name || 'Tải xuống File'}</a></div>`;
                            }
                        }

                        let statusHtml = '';
                        let timeHtml = `<span class="msg-time">${moment(m.created_at).format('HH:mm')}</span>`;

                        if (side === 'me') {
                            let isSeen = false;
                            if (othersLastRead && othersLastRead.length > 0) {
                                isSeen = othersLastRead.some(time => time && time >= m.created_at);
                            }
                            statusHtml = `<div class="msg-status">${timeHtml} <span>${isSeen ? 'Đã xem' : 'Đã gửi'}</span></div>`;
                        } else {
                            statusHtml = `<div class="msg-status">${timeHtml}</div>`;
                        }

                        html += `
                            <div class="msg-item ${side}">
                                ${side === 'other' ? `<div class="msg-sender">${m.sender_name}</div>` : ''}
                                <div class="msg-text">${content}</div>
                                ${statusHtml}
                            </div>
                        `;
                    });
                    $(`#chat-content-${groupId}`).html(html);
                    
                    if (forceScroll || isAtBottom) {
                        let div = document.getElementById(`chat-content-${groupId}`);
                        if (div) div.scrollTop = div.scrollHeight;
                    }
                });
            }

            window.sendChatMessage = function(groupId, input) {
                let msg = input.value;
                if (!msg.trim()) return;

                $.post("{{ route('chat.send') }}", {
                    _token: "{{ csrf_token() }}",
                    group_id: groupId,
                    message: msg
                }, function() {
                    input.value = '';
                    loadChatMessages(groupId, true); // Force scroll khi mình gửi tin
                });
            };

            window.uploadFile = function(input, groupId) {
                if (!input.files || !input.files[0]) return;
                performFileUpload(input.files[0], groupId, function() {
                    input.value = ''; // Reset input
                });
            };

            window.handleChatPaste = function(event, groupId) {
                let items = (event.clipboardData || event.originalEvent.clipboardData).items;
                for (let index in items) {
                    let item = items[index];
                    if (item.kind === 'file' && item.type.startsWith('image/')) {
                        let blob = item.getAsFile();
                        let fileName = "pasted_image_" + moment().format('YYYYMMDD_HHmmss') + ".png";
                        let file = new File([blob], fileName, { type: item.type });
                        performFileUpload(file, groupId);
                    }
                }
            };

            function performFileUpload(file, groupId, callback = null) {
                let formData = new FormData();
                formData.append('file', file);
                formData.append('group_id', groupId);
                formData.append('_token', "{{ csrf_token() }}");
                formData.append('message', '[Hình ảnh dán: ' + file.name + ']');

                $.ajax({
                    url: "{{ route('chat.send') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (callback) callback();
                        loadChatMessages(groupId, true);
                        loadChatGroups();
                    },
                    error: function(xhr) {
                        alert('Lỗi tải lên hình ảnh: ' + (xhr.responseJSON?.message || 'Vui lòng thử lại'));
                    }
                });
            }

            window.initChatResize = function(e, groupId) {
                let win = document.getElementById(`chat-window-${groupId}`);
                if (!win || win.classList.contains('maximized')) return;

                let startX = e.clientX;
                let startY = e.clientY;
                let startWidth = win.offsetWidth;
                let startHeight = win.offsetHeight;

                function doResize(e) {
                    let newWidth = startWidth + (startX - e.clientX);
                    let newHeight = startHeight + (startY - e.clientY);

                    // Limits
                    if (newWidth >= 250 && newWidth <= 800) win.style.width = newWidth + 'px';
                    
                    // Giới hạn chiều cao: Tối thiểu 100px, tối đa là chiều cao màn hình trừ đi 100px để không vượt quá header phần mềm
                    let maxHeight = window.innerHeight - 100;
                    if (newHeight >= 100 && newHeight <= maxHeight) win.style.height = newHeight + 'px';
                }

                function stopResize() {
                    window.removeEventListener('mousemove', doResize);
                    window.removeEventListener('mouseup', stopResize);
                }

                window.addEventListener('mousemove', doResize);
                window.addEventListener('mouseup', stopResize);
            };

            // Polling cập nhật tin nhắn
            setInterval(function() {
                openChatGroups.forEach(groupId => {
                    loadChatMessages(groupId);
                });
                if ($('#chat-sidebar').hasClass('active')) {
                    loadChatGroups();
                }
            }, 3000);

            // --- CÁC HÀM XỬ LÝ NHÓM & EMOJI ---
            window.showCreateGroupModal = function() {
                $.get("{{ route('chat.users') }}", function(users) {
                    let html = '';
                    users.forEach(u => {
                        html += `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="${u.id}" id="user-${u.id}" name="group_members">
                                <label class="form-check-label" for="user-${u.id}">
                                    ${u.fullName} (@${u.userName})
                                </label>
                            </div>
                        `;
                    });
                    $('#userListForGroup').html(html);
                    $('#createGroupModal').modal('show');
                });
            };

            window.submitCreateGroup = function() {
                let name = $('#newGroupName').val();
                let members = [];
                $('input[name="group_members"]:checked').each(function() {
                    members.push($(this).val());
                });

                if (!name || members.length === 0) {
                    alert('Vui lòng nhập tên nhóm và chọn ít nhất 1 thành viên');
                    return;
                }

                $.post("{{ route('chat.createGroup') }}", {
                    _token: "{{ csrf_token() }}",
                    name: name,
                    member_ids: members
                }, function(res) {
                    $('#createGroupModal').modal('hide');
                    loadChatGroups();
                    openChatWindow(res.id, name);
                });
            };

            const commonEmojis = ['😀', '😂', '😍', '👍', '🙏', '❤️', '🔥', '👏', '🙄', '😮', '😢', '😡', '✅', '❌',
                '🚀'
            ];

            window.toggleEmojiPicker = function(groupId) {
                let existing = $(`#emoji-picker-${groupId}`);
                if (existing.length) {
                    existing.remove();
                    return;
                }

                let html = `<div class="emoji-picker" id="emoji-picker-${groupId}">`;
                commonEmojis.forEach(e => {
                    html += `<span class="emoji-item" onclick="addEmoji(${groupId}, '${e}')">${e}</span>`;
                });
                html += `</div>`;
                $(`#chat-window-${groupId}`).append(html);
            };

            window.addEmoji = function(groupId, emoji) {
                let input = $(`#chat-window-${groupId} .chat-input`);
                input.val(input.val() + emoji);
                input.focus();
                $(`#emoji-picker-${groupId}`).remove();
            };
        })();
    </script>
</body>

</html>
