@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.materData.personnel.dataTable')
@endsection

@section('model')
  @include('pages.materData.personnel.create')
  @include('pages.materData.personnel.update') 
@endsection
