<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>QC Plan Management</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}" sizes="32x32">

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
  $(function () {
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
</body>
</html>
