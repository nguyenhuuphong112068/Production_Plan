
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.Schedual.temp.dataTable')
  
@endsection

@section('model')
  @include('pages.Schedual.temp.create_plan_list')

@endsection



