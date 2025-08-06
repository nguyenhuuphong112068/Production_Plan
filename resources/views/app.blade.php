<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title inertia>Quản Lý Sản Xuất</title>
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}" >

    @include('layout.css')

    @viteReactRefresh
    @vite('resources/js/app.jsx')
    @inertiaHead

</head>
<body class="antialiased">
    @inertia


 @include('layout.js')
 
</body>

</html>
