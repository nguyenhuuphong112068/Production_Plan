
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.assignment.production.dataTable')
@endsection

@section('model')
  {{-- @include('pages.assignment.production.create')
  @include('pages.assignment.production.update')  --}}
@endsection
