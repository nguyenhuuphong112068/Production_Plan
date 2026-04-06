
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
    @if ($main_type == 'production')
        @include('pages.History.Production.dataTable')
    @else
        @include('pages.History.Maintenance.dataTable')
    @endif
@endsection

