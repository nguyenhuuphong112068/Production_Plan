
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.report.monthly_report.dataTable')
@endsection

@section('model')
  @include('pages.report.monthly_report.create')
  @include('pages.report.monthly_report.update')
  @include('pages.report.monthly_report.detail')
  @include('pages.report.monthly_report.explanation')
@endsection