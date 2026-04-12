@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.History.Production.dataTable')
@endsection
