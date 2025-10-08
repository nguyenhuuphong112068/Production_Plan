
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.User.user.dataTable')
@endsection

@section('model')
  @include('pages.User.user.create')
  {{-- @include('pages.User.user.update')  --}}
@endsection
