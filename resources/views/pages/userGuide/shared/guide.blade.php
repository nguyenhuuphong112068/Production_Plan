@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

{{-- Mục lục nằm ở toc.php cùng thư mục: dùng chung cho trang này và nút "?" trên thanh trên cùng --}}
@section('mainContent')
    @include('pages.userGuide.shared.page')
@endsection
