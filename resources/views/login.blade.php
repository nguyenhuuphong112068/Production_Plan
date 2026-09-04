<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>PMS | Production Management System</title>

    <!-- Local Arimo Font -->
    <link rel="stylesheet" href="{{ asset('fonts/Arimo/arimo.css') }}">

    <!-- Bootstrap offline -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">

    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --accent: #22d3ee;
            --bg-dark: #0f172a;
            --text-main: #1e293b;
            --glass: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
        }

        body {
            background: url('{{ asset('img/login_bg.png') }}') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Arimo', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 24px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(15, 23, 42, 0.4) 100%);
            z-index: 1;
        }

        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .login-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 48px;
            border: 1px solid var(--glass-border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .login-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--primary), var(--accent), transparent);
            animation: scanning 3s infinite linear;
        }

        @keyframes scanning {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        .brand-section {
            margin-bottom: 32px;
            text-align: center;
        }

        .brand-logo {
            width: 70px;
            height: auto;
            margin-bottom: 16px;
            filter: drop-shadow(0 0 10px rgba(14, 165, 233, 0.3));
        }

        .brand-name {
            font-weight: 800;
            font-size: 2.2rem;
            letter-spacing: -0.025em;
            color: var(--bg-dark);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .brand-name span {
            color: var(--primary);
        }

        .brand-tagline {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .form-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-control {
            border-radius: 12px;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            color: var(--bg-dark);
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
            background: #fff;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            font-size: 1.2rem;
            transition: color 0.2s;
        }

        .password-wrapper .toggle-password:hover {
            color: var(--primary);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 24px;
            width: 100%;
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.4);
            transition: all 0.3s;
        }

        .btn-login:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(14, 165, 233, 0.5);
            filter: brightness(1.1);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer-links {
            margin-top: 24px;
            display: flex;
            justify-content: center;
        }

        .toggle-link {
            color: var(--primary-dark);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .toggle-link:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        .alert-custom {
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Menu truy cập nhanh */
        .quick-menu {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 24px;
        }

        .quick-btn {
            width: 100px;
            height: 90px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            box-shadow: 0 10px 20px -8px rgba(0, 0, 0, 0.5);
            color: var(--bg-dark);
            text-decoration: none;
            transition: all 0.3s;
        }

        .quick-btn i {
            font-size: 2.2rem;
            line-height: 1;
            margin-bottom: 4px;
        }

        .quick-btn span {
            font-size: 0.75rem;
            font-weight: 700;
            text-align: center;
            line-height: 1.15;
        }

        .quick-btn:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            transform: translateY(-5px);
            box-shadow: 0 18px 25px -10px rgba(14, 165, 233, 0.6);
        }

        /* App Version Watermark (Góc phải dưới màn hình) */
        .app-version {
            position: fixed;
            bottom: 14px;
            right: 18px;
            z-index: 10;
            font-size: 0.75rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.45);
            letter-spacing: 0.04em;
            padding: 4px 10px;
            border-radius: 6px;
            background: rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            user-select: none;
            cursor: default;
            transition: all 0.25s ease;
        }

        .app-version:hover {
            color: rgba(255, 255, 255, 0.85);
            background: rgba(15, 23, 42, 0.55);
            border-color: rgba(255, 255, 255, 0.18);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }

            .quick-menu {
                gap: 12px;
            }

            .app-version {
                bottom: 8px;
                right: 12px;
                font-size: 0.7rem;
                padding: 3px 8px;
            }
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="login-card">
            <div class="brand-section">
                <img src="{{ asset('img/iconstella.svg') }}" alt="Stella Logo" class="brand-logo">
                <h1 class="brand-name">P<span>MS</span></h1>
                <p class="brand-tagline">Production Management System</p>
            </div>

            @if (session('error'))
                <div class="alert-custom">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <!-- ✅ Form đăng nhập -->
            <form id="loginForm" action="{{ route('login') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="username" class="form-label">Tài khoản</label>
                    <input type="text" name="username" class="form-control" placeholder="Nhập tên đăng nhập" required
                        autofocus value="{{ old('username') }}">
                </div>

                <div class="mb-3">
                    <label for="loginPassword" class="form-label">Mật khẩu</label>
                    <div class="password-wrapper">
                        <input type="password" id="loginPassword" name="passWord" class="form-control"
                            placeholder="••••••••" required>
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('loginPassword', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-login" name="login">
                    Đăng nhập
                </button>

                <div class="footer-links">
                    <a class="toggle-link" onclick="toggleForms(true)">
                        <i class="bi bi-key-fill me-1"></i> Đổi mật khẩu?
                    </a>
                </div>
            </form>

            <!-- ✅ Form đổi mật khẩu (Ẩn mặc định) -->
            <form id="changePassForm" action="{{ route('changePassword') }}" method="POST" style="display: none;">
                @csrf
                <div class="mb-4 text-center">
                    <h5 class="fw-bold" style="color: var(--bg-dark);">Thiết lập mật khẩu mới</h5>
                    <p class="text-muted small mb-0">Cập nhật thông tin bảo mật tài khoản</p>
                </div>

                <div class="mb-3">
                    <label for="usernameChange" class="form-label">Tài khoản</label>
                    <input type="text" id="usernameChange" name="username" class="form-control"
                        placeholder="Tên đăng nhập" required>
                </div>

                <div class="mb-3">
                    <label for="oldPassword" class="form-label">Mật khẩu cũ</label>
                    <div class="password-wrapper">
                        <input type="password" id="oldPassword" name="oldPassword" class="form-control"
                            placeholder="Mật khẩu hiện tại" required>
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('oldPassword', this)"></i>
                    </div>
                    @error('oldPassword', 'changePasswordErrors')
                        <div class="alert-custom mt-2 mb-0">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div>{{ $message }}</div>
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="newPassword" class="form-label">Mật khẩu mới</label>
                    <div class="password-wrapper">
                        <input type="password" id="newPassword" name="newPassword" class="form-control"
                            placeholder="Nhập mật khẩu mới" required>
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('newPassword', this)"></i>
                    </div>
                    @error('newPassword', 'changePasswordErrors')
                        <div class="alert-custom mt-2 mb-0">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div>{{ $message }}</div>
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Xác nhận mật khẩu mới</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirmPassword" name="confirmPassword" class="form-control"
                            placeholder="Nhập lại mật khẩu mới" required>
                        <i class="bi bi-eye-slash toggle-password"
                            onclick="togglePassword('confirmPassword', this)"></i>
                    </div>
                    @error('confirmPassword', 'changePasswordErrors')
                        <div class="alert-custom mt-2 mb-0">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div>{{ $message }}</div>
                        </div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-login" name="changePass">
                    Cập nhật mật khẩu
                </button>

                <div class="footer-links">
                    <a class="toggle-link" onclick="toggleForms(false)">
                        <i class="bi bi-arrow-left me-1"></i> Quay lại đăng nhập
                    </a>
                </div>
            </form>
        </div>

        <!-- Menu truy cập nhanh bên dưới form -->
        <div class="quick-menu">
            {{-- <a href="/status" class="quick-btn">
                <i class="bi bi-activity"></i>
                <span>Trạng Thái<br>Thời Gian Thực</span>
            </a> --}}
            <a href="{{ route('pages.assignment.production.public') }}" class="quick-btn">
                <i class="bi bi-calendar4-week"></i>
                <span>Phân Công<br>Sản Xuất</span>
            </a>
            <a href="{{ route('pages.assignment.public') }}" class="quick-btn">
                <i class="bi bi-tools"></i>
                <span>Phân Công<br>Bảo Trì</span>
            </a>
        </div>
    </div>

    <!-- Phiên bản hệ thống ở góc phải màn hình -->
    <div class="app-version" title="Phiên bản hệ thống">
        Version 1.0
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

    <script>
        // 🔁 Chuyển form login <-> đổi mật khẩu
        function toggleForms(showChangePass = false) {
            const loginForm = document.getElementById('loginForm');
            const changePassForm = document.getElementById('changePassForm');

            if (showChangePass) {
                loginForm.style.opacity = '0';
                setTimeout(() => {
                    loginForm.style.display = 'none';
                    changePassForm.style.display = 'block';
                    changePassForm.style.opacity = '0';
                    setTimeout(() => {
                        changePassForm.style.opacity = '1';
                    }, 50);
                }, 200);
            } else {
                changePassForm.style.opacity = '0';
                setTimeout(() => {
                    changePassForm.style.display = 'none';
                    loginForm.style.display = 'block';
                    loginForm.style.opacity = '0';
                    setTimeout(() => {
                        loginForm.style.opacity = '1';
                    }, 50);
                }, 200);
            }
        }

        // 👁‍🗨 Toggle hiển thị mật khẩu
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("bi-eye-slash", "bi-eye");
            } else {
                input.type = "password";
                icon.classList.replace("bi-eye", "bi-eye-slash");
            }
        }

        // 🪄 Giữ lại form đang mở sau khi reload
        document.addEventListener("DOMContentLoaded", function() {
            const activeForm = "{{ session('activeForm', 'login') }}";
            if (activeForm === 'changePass') {
                document.getElementById('loginForm').style.display = 'none';
                document.getElementById('changePassForm').style.display = 'block';
            }

            // Kiểm tra thông báo timeout từ URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('timeout')) {
                Swal.fire({
                    icon: 'info',
                    title: 'Thông báo',
                    text: 'Bạn Đã Không Sử Dụng Phần Mềm Hơn 15 Phút, Tính Năng Autologout Được Kích Hoạt. Vui Lòng Đăng Nhập Lại',
                    confirmButtonColor: '#0284c7',
                    confirmButtonText: 'Đồng ý'
                }).then(() => {
                    // Xóa tham số ?timeout=true trên URL mà không load lại trang
                    window.history.replaceState({}, document.title, window.location.pathname);
                });
            }
        });
    </script>

</body>

</html>
