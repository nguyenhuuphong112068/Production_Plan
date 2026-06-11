@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.materData.BlisterType.dataTable')
@endsection

@section('model')
  @include('pages.materData.BlisterType.create')
  @include('pages.materData.BlisterType.update') 
@endsection
