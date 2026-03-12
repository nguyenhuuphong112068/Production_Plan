@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection


@section('mainContent')
    @include('pages.OffDays.dataTable')
    <script src="{{ asset('js/jquery.js') }}"></script>
@endsection
