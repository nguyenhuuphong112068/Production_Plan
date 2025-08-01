
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.Schedual.dataTable')
@endsection

@section('model')
  @include('pages.Schedual.create')
  @include('pages.Schedual.inventory')
  @include('pages.Schedual.update')
  @include('pages.Schedual.createHistory')  
@endsection
