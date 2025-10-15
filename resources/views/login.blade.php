<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>Đăng nhập</title>

    <!-- Bootstrap offline -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

    <style>
        body {
            background: url('{{ asset('img/Map.jpg') }}') no-repeat center center fixed;
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
        
            <!-- Form đăng nhập -->
            <form id="loginForm" action="{{ route('login') }}" method="POST">
                @csrf
                <div class="mb-3 mt-3">
                    <label for="username" class="form-label">User Name</label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" required autofocus value="{{ old('username') }}">
                </div>

                <div class="mb-3 mt-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="passWord" class="form-control @error('password') is-invalid @enderror" required>

                </div>

                <button type="submit" class="btn w-100 mt-4" style="background-color: rgb(213, 213, 9)" name="login">
                    Đăng nhập
                </button>

                <span class="toggle-link" onclick="toggleForms()">Đổi mật khẩu?</span>
            </form>

            <!-- Form đổi mật khẩu -->
            <form id="changePassForm" action="{{ route('changePassword') }}" method="POST" style="display: none;">
                @csrf
                <div class="mb-3 mt-3">
                    <label for="usernameChange" class="form-label">User Name</label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="mb-3 mt-3">
                    <label for="oldPassword" class="form-label">Mật khẩu cũ</label>
                    <input type="password" name="oldPassword" class="form-control" required>
                    @error('oldPassword', 'changePasswordErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 mt-3">
                    <label for="newPassword" class="form-label">Mật khẩu mới</label>
                    <input type="password" name="newPassword" class="form-control" required>
                    @error('newPassword', 'changePasswordErrors')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 mt-3">
                    <label for="confirmPassword" class="form-label">Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirmPassword" class="form-control" required>
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

<script>
    function toggleForms(showChangePass = false) {
        const loginForm = document.getElementById('loginForm');
        const changePassForm = document.getElementById('changePassForm');
        if (showChangePass) {
            loginForm.style.display = 'none';
            changePassForm.style.display = 'block';
        } else {
            loginForm.style.display = 'block';
            changePassForm.style.display = 'none';
        }
    }

    // 👇 Nhận giá trị từ Laravel (session)
    document.addEventListener("DOMContentLoaded", function() {
        const activeForm = "{{ session('activeForm', 'login') }}"; 
        toggleForms(activeForm === 'changePass');
    });
</script>

</body>
</html>


{{-- <!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}" >
    <title>Đăng nhập</title>

    <!-- Bootstrap offline -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Custom style -->
    <style>

        body {
            background: url('{{ asset('img/Map.jpg')}}') no-repeat center center fixed;
            background-size: cover;
            background-size: 100% 100%;
        }

        .login-card {
            /* background: url('{{ asset('img/iconstella.svg') }}') no-repeat center center; */
            background-size: cover;
            backdrop-filter: blur(3px);
            border-radius: 15px ;
            box-shadow: 0 0 10px rgba(0,0,0,0.4);
            border: 1px solid #003A4F;
        }

        .overlay {
            background-color: rgba(255, 255, 255, 0.85);
            padding: 1rem;
            border-radius: 15px;
        }

    </style>
    

</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class=" mt-5 login-card p-4 shadow rounded" style=" width: 100%; max-width: 400px; max-height: 600px;">
        <div class="overlay">

                <div class="text-center mb-5 mt-1">
                     <img src="{{ asset('img/iconstella.svg') }}" alt="Logo"  style=" max-width: 80px; height: auto;">
                </div>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ route ('login') }}" method="POST">
                    @csrf

                    <div class="mb-3 mt-3">
                        <label for="username" class="form-label">User Name</label>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" required autofocus value="{{ old('email') }}">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 mt-3">
                        <label for="passWord" class="form-label">PassWord</label>
                        <input type="passWord" name="passWord" class="form-control @error('passWord') is-invalid @enderror" required>
                        @error('passWord')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn w-100 mt-5" style="background-color: rgb(213, 213, 9)" name = "login">
                        Đăng nhập
                    </button>
                </form>

            
            </div>
        </div>
    </div>
   
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
</body>
</html> --}}
