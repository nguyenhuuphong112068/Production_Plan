
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.plan.production.dataTable')
@endsection
@section('model')
  {{-- @include('pages.category.intermediate.create')
  @include('pages.category.intermediate.update')  --}}
@endsection
