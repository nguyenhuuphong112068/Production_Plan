
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.report.weekly_report.dataTable')
@endsection

@section('model')
  {{-- @include('pages.report.weekly_report.create')
  @include('pages.report.weekly_report.update')
  @include('pages.report.weekly_report.detail')
  @include('pages.report.weekly_report.explanation') --}}
@endsection