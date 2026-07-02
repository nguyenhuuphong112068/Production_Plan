@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.plan.validation_tracking.dataTable')
@endsection

@section('model')
  @include('pages.plan.validation_tracking.create')
  @include('pages.plan.validation_tracking.update')
@endsection
