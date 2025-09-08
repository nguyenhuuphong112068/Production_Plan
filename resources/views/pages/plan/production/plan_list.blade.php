
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.plan.production.dataTable_plan_list')
@endsection
@section('model')
  @include('pages.plan.production.create_plan_list')

@endsection
