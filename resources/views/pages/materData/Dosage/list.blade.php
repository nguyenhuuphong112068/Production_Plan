
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.materData.Dosage.dataTable')
@endsection

@section('model')
  @include('pages.materData.Dosage.create')
  @include('pages.materData.Dosage.update') 
@endsection
