
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.category.product.dataTable')
@endsection
@section('model')
  @include('pages.category.product.create')
  @include('pages.category.product.update') 
@endsection
