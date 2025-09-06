
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.category.maintenance.dataTable')
@endsection

@section('model')
  @include('pages.category.maintenance.create')
  @include('pages.category.maintenance.update') 
@endsection
