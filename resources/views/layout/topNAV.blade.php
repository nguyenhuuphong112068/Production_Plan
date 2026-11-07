<nav class="main-header navbar navbar-expand navbar-white navbar-light fixed-top">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link ms-5" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <!-- Title Center -->
    <div class="mx-auto text-center" style="color: #CDC717">
        <h4>{{ session('title') }}</h4>
    </div>

    <!-- Right User Info + Notification + Logout -->
    <ul class="navbar-nav ms-auto">
        <!-- Thông báo cũ đã được chuyển sang Floating Button -->

        {{-- Nút "?": mở mục Hướng Dẫn Sử Dụng của màn hình hiện tại (App\Support\UserGuideLinks, khai trong toc.php).
             Chỉ hiện với tài khoản có quyền xem hướng dẫn (UserGuideController::PERMISSION). --}}
        @php
            $ugRouteName = request()->route()?->getName() ?? '';
            $ugCanView = \App\Http\Controllers\Pages\UserGuide\UserGuideController::canView();
            $ugHelp = $ugCanView ? \App\Support\UserGuideLinks::forRoute($ugRouteName) : [];
        @endphp
        @unless (!$ugCanView || str_starts_with($ugRouteName, 'pages.userGuide.'))
            <li class="nav-item d-flex align-items-center" style="margin-right: 14px;">
                <a id="ug-help-btn" class="ug-help-btn" target="_blank" rel="noopener"
                    href="{{ $ugHelp[0]['url'] ?? route('pages.userGuide.list') }}"
                    title="{{ $ugHelp ? 'Hướng dẫn: ' . $ugHelp[0]['title'] : 'Hướng dẫn sử dụng' }}"
                    data-links='@json($ugHelp)'>
                    <i class="fas fa-question"></i>
                </a>
            </li>
        @endunless

        <li class="nav-item d-flex align-items-center" style="margin-right: 20px; position: relative;">
            <div id="notif-bell-btn"
                style="border: 2px solid #CDC717; border-radius: 50%; width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; cursor: pointer; position: relative;">
                <i class="far fa-bell" style="font-size: 18px; color: #CDC717;"></i>
                <span class="badge badge-warning" id="notif-badge-navbar"
                    style="display:none; position: absolute; top: 0; right: 0;">0</span>
                <span class="badge bg-danger" id="mention-badge-navbar"
                    style="display:none; position: absolute; top: -12px; right: 40px; font-size: 11px; animation: badge-blink 1.5s infinite; white-space: nowrap; padding: 5px 10px; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">@ Nhắc tên</span>
            </div>
        </li>

        {{-- <li class="nav-item d-flex align-items-center" style="margin-right: 40px;">
            <div class="chat-trigger" onclick="toggleChat(true)">
                <i class="fas fa-comments"></i>
                <span id="unread-total-badge" class="unread-badge-total d-none">0</span>
            </div>
        </li> --}}

        <li class="nav-item d-flex flex-column justify-content-center align-items-end me-3 mr-5">
            <span>👤 {{ session('user')['fullName'] }}</span>
            <span>🛡️ {{ session('user')['userGroup'] }}</span>
        </li>

        <li class="nav-item">
            <a href="{{ route('logout') }}" class="nav-link text-primary" style="font-size: 20px">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </li>
    </ul>
</nav>

<style>
    .ug-help-btn {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #CDC717;
        border-radius: 50%;
        color: #CDC717;
        font-size: 16px;
        transition: .15s ease;
    }

    .ug-help-btn:hover {
        background: #CDC717;
        color: #fff;
        text-decoration: none;
    }
</style>

<script>
    // Màn hình nhiều tab: chọn mục hướng dẫn của tab đang hiện (id vùng nội dung khai 'pane' trong toc.php),
    // sau đó theo ?tab= trên URL, cuối cùng là mục đầu tiên
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('ug-help-btn');
        if (!btn) return;

        var links = [];
        try {
            links = JSON.parse(btn.getAttribute('data-links') || '[]');
        } catch (e) {}
        if (links.length < 2) return;

        function pick() {
            for (var i = 0; i < links.length; i++) {
                for (var j = 0; j < links[i].panes.length; j++) {
                    var pane = document.getElementById(links[i].panes[j]);
                    if (pane && pane.offsetParent !== null) return links[i];
                }
            }
            var tab = new URLSearchParams(window.location.search).get('tab');
            for (var k = 0; tab && k < links.length; k++) {
                if (links[k].tab === tab) return links[k];
            }
            return links[0];
        }

        function sync() {
            var link = pick();
            btn.href = link.url;
            btn.title = 'Hướng dẫn: ' + link.title;
        }

        btn.addEventListener('mouseenter', sync);
        btn.addEventListener('focus', sync);
        btn.addEventListener('click', sync);
    });
</script>
