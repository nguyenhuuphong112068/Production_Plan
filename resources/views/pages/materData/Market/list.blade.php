
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.materData.Unit.dataTable')
@endsection

@section('model')
  @include('pages.materData.Unit.create')
  @include('pages.materData.Unit.update') 
@endsection
