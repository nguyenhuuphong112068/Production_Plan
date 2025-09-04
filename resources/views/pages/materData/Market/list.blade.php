
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.materData.Market.dataTable')
@endsection

@section('model')
  @include('pages.materData.Market.create')
  @include('pages.materData.Market.update') 
@endsection
