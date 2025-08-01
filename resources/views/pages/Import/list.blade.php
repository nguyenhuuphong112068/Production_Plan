
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.Import.dataTable')
@endsection

@section('model')
  @include('pages.Import.category') 
  @include('pages.Import.create')
  @include('pages.Import.update') 
@endsection
