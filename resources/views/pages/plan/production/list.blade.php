
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
  {{-- @include('pages.plan.production.update') --}}
  @include('pages.plan.production.create')  
  @include('pages.plan.production.finished_category')
  @include('pages.plan.production.source_material_list')
  @include('pages.plan.production.history')
  @include('pages.plan.production.create_source') 
  @include('pages.plan.production.confirm_first_val_batch')
@endsection
