
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.category.intermediate.dataTable')
@endsection
@section('model')
  
  @include('pages.category.intermediate.create')
  @include('pages.category.intermediate.create_hypothesis')
  @include('pages.category.intermediate.update_hypothesis')
  @include('pages.category.intermediate.update') 
  @include('pages.category.intermediate.recipe')
  @include('pages.category.intermediate.create_bom')
@endsection
