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

        <li class="nav-item d-flex align-items-center" style="margin-right: 20px;">
            <div id="notif-bell-btn"
                style="border: 2px solid #CDC717; border-radius: 50%; width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; cursor: pointer;">
                <i class="far fa-bell" style="font-size: 18px; color: #CDC717;"></i>
                <span class="badge badge-warning" id="notif-badge-navbar"
                    style="display:none; position: absolute; top: 0; right: 0;">0</span>
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
