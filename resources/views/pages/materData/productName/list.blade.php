
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.materData.productName.dataTable')
@endsection
@section('model')
  @include('pages.materData.productName.create')
  @include('pages.materData.productName.update') 
@endsection
