<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>Đăng nhập</title>

    <!-- Bootstrap offline -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">

    <style>
        body {
            background: url('{{ asset('img/Stella_Icon_Main.jpg') }}') no-repeat center center fixed;
            background-size: cover;
            background-size: 100% 100%;
        }

        .login-card {
            background-size: cover;
            backdrop-filter: blur(3px);
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.4);
            border: 1px solid #003A4F;

            
        }

        .overlay {
            background-color: rgba(255, 255, 255, 0.85);
            padding: 1rem;
            border-radius: 15px;
        }

        .toggle-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #003A4F;
            cursor: pointer;
            text-decoration: underline;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .toggle-password {
           
            position: absolute;
            right: 12px;
            top: 73%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #003A4F;
            opacity: 0.6;
            transition: 0.2s;
            font-size: 1.1rem; /* hơi to hơn tí cho cân đối */
        }

        .password-wrapper .toggle-password:hover {
            opacity: 1;
            color: #000;
            transform: translateY(-50%) scale(1.15);
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="mt-5 login-card p-4 shadow rounded" style="width: 100%; max-width: 400px; max-height: 800px;">
        <div class="overlay">
            <div class="text-center mb-5 mt-1">
                <img src="{{ asset('img/iconstella.svg') }}" alt="Logo" style="max-width: 80px; height: auto;">
            </div>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
        
            <!-- ✅ Form đăng nhập -->
            <form id="loginForm" action="{{ route('login') }}" method="POST">
                @csrf
                <div class="mb-3 mt-3">
                    <label for="username" class="form-label">User Name</label>
                    <input type="text" name="username" class="form-control" required autofocus value="{{ old('username') }}">
                </div>

                <div class="mb-3 mt-3 password-wrapper">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="loginPassword" name="passWord" class="form-control" required>
                    <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('loginPassword', this)"></i>
                </div>

                <button type="submit" class="btn w-100 mt-4" style="background-color: rgb(213, 213, 9)" name="login">
                    Đăng nhập
                </button>

                <span class="toggle-link" onclick="toggleForms(true)">Đổi mật khẩu?</span>
            </form>
            <a  href="/status" class="toggle-link"> Xem Trang Thái Phòng Sản Xuất </a>
            <a  href="/status_HPLC" class="toggle-link"> Xem Trang Thái Kiểm Nghiệm - HPLC</a>

            <!-- ✅ Form đổi mật khẩu -->
            <form id="changePassForm" action="{{ route('changePassword') }}" method="POST" style="display: none;">
                @csrf
                <div class="mb-3 mt-3">
                    <label for="usernameChange" class="form-label">User Name</label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="mb-3 mt-3 password-wrapper">
                    <label for="oldPassword" class="form-label">Mật khẩu cũ</label>
                    <input type="password" id="oldPassword" name="oldPassword" class="form-control" required>
                  
                    <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('oldPassword', this)"></i>
                    @error('oldPassword', 'changePasswordErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 mt-3 password-wrapper">
                    <label for="newPassword" class="form-label">Mật khẩu mới</label>
                    <input type="password" id="newPassword" name="newPassword" class="form-control" required>
                    <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('newPassword', this)"></i>
                    @error('newPassword', 'changePasswordErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 mt-3 password-wrapper">
                    <label for="confirmPassword" class="form-label">Xác nhận mật khẩu mới</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                    
                    <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('confirmPassword', this)"></i>
                    @error('confirmPassword', 'changePasswordErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn w-100 mt-4" style="background-color: rgb(213, 213, 9)" name="changePass">
                    Cập nhật mật khẩu
                </button>

                <span class="toggle-link" onclick="toggleForms()">Quay lại đăng nhập</span>
            </form>

        </div>
    </div>
</div>

<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

{{-- <script>
    document.addEventListener("DOMContentLoaded", function () {

        const currentHost = window.location.host;
        const oldDomain = "http://khsx.stellapharm.int/";
        const newUrl = "http://s-pms.stellapharm.int/";

        if (true) {
            Swal.fire({
                icon: 'warning',
                title: 'Thông báo',
                html: `
                    Hệ thống <b>KHSX</b> đã được chuyển sang địa chỉ mới. <br>(Từ 18:05 25/12/2025)<br>
                    Vui lòng truy cập hệ thống qua đường dẫn:<br>
                    <b style="color:#003A4F">${newUrl}</b>
                `,
                allowOutsideClick: false,   // ❌ không click ngoài
                allowEscapeKey: false,      // ❌ không bấm ESC
                allowEnterKey: false,       // ❌ không Enter
                showConfirmButton: true,
                confirmButtonText: 'Chuyển sang hệ thống mới',
                confirmButtonColor:  '#003A4F',
                backdrop: true
            }).then(() => {
                window.location.href = newUrl;
            });
        }
    });
</script> --}}

<script>
    // 🔁 Chuyển form login <-> đổi mật khẩu
    function toggleForms(showChangePass = false) {
        const loginForm = document.getElementById('loginForm');
        const changePassForm = document.getElementById('changePassForm');
        loginForm.style.display = showChangePass ? 'none' : 'block';
        changePassForm.style.display = showChangePass ? 'block' : 'none';
    }

    // 👁‍🗨 Toggle hiển thị mật khẩu
    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        }
    }

    // 🪄 Giữ lại form đang mở sau khi reload
    document.addEventListener("DOMContentLoaded", function() {
        const activeForm = "{{ session('activeForm', 'login') }}";
        toggleForms(activeForm === 'changePass');

        // Kiểm tra thông báo timeout từ URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('timeout')) {
            Swal.fire({
                icon: 'info',
                title: 'Thông báo',
                text: 'Bạn Đã Không Sử Dụng Phần Mềm Hơn 15 Phút, Tính Năng Autologout Được Kích Hoạt. Vui Lòng Đăng Nhập Lại',
                confirmButtonColor: '#003A4F',
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

