<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title inertia>Laravel Inertia</title>

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
