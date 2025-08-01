
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.User.dataTable')
@endsection

@section('model')
  @include('pages.User.create')
  @include('pages.User.update') 
@endsection
