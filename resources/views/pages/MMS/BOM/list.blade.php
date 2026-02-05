
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.MMS.BOM.dataTable')
@endsection

@section('model')

  {{-- @include('pages.materData.source_material.update') 
  @include('pages.materData.source_material.create') --}}
  
@endsection
