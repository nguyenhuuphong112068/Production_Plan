
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.plan.maintenance.dataTable')
@endsection
@section('model')
    @include('pages.plan.maintenance.maintenance_category')
    @include('pages.plan.maintenance.create')
     
  {{-- @include('pages.plan.maintenance.update')
  @include('pages.plan.maintenance.history')  --}}
@endsection
