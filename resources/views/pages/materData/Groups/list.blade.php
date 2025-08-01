
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.materData.Groups.dataTable')
@endsection

@section('model')
  @include('pages.materData.Groups.create')
  @include('pages.materData.Groups.update') 
@endsection
