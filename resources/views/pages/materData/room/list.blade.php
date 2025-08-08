
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.materData.room.dataTable')
@endsection

@section('model')
  {{-- @include('pages.materData.room.create')
  @include('pages.materData.room.update')  --}}
@endsection
