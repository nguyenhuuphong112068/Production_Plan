
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.status.history.dataTable')
@endsection

@section('model')
  {{-- @include('pages.status.create') 
  @include('pages.status.create_general_notification')  --}}
@endsection
