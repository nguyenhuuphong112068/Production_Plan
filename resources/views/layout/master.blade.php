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

</head>

<body class="hold-transition sidebar-mini">

    <!-- General wrapper -->
    <div class="wrapper">

        @yield('topNAV')

        @yield('leftNAV')

        @yield('mainContent')

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
        })();
    </script>
</body>

</html>
