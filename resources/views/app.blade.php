
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


