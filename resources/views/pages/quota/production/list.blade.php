
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.quota.production.dataTable')
@endsection

@section('model')
  @include('pages.quota.production.create')
  @include('pages.quota.production.update') 
  @include('pages.quota.production.history')
@endsection
