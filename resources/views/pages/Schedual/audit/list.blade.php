
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.Schedual.audit.dataTable')
@endsection

@section('model')
  @include('pages.Schedual.audit.history')
@endsection
