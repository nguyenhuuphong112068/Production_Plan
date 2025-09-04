
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.materData.Specification.dataTable')
@endsection

@section('model')
  @include('pages.materData.Specification.create')
  @include('pages.materData.Specification.update') 
@endsection
