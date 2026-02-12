
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.plan.production.dataTable_feedback')
  {{-- @include('pages.plan.production.qa_feedback')
  @include('pages.plan.production.en_feedback')
  @include('pages.plan.production.qc_feedback')
  @include('pages.plan.production.pro_feedback') --}}
@endsection
