
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.report.daily_report.dataTable')
@endsection

@section('model')
 @include('pages.report.daily_report.detail')
@endsection