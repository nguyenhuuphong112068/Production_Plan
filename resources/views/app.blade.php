
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @viteReactRefresh
        @vite('resources/js/app.jsx')
        <div class="content-wrapper">
                <div class="card">
                    <div id="root" class ="mt-5"></div>
                </div>
        </div>
        
@endsection


{{-- <!DOCTYPE html>
<html>
<head>
    <title>Laravel + React</title>
    @vite('resources/js/app.jsx') 
</head>
<body>

    <div id="root"></div>
    
</body>
</html> --}}

{{-- <!DOCTYPE html>
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

</html> --}}
