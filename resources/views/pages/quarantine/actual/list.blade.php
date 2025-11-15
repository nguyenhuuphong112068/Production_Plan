
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.quarantine.actual.dataTable')
@endsection

@section('model')

  @include('pages.quarantine.actual.detail')
  
@endsection