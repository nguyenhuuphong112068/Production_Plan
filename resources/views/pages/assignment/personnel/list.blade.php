@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.assignment.personnel.dataTable')
@endsection

@section('model')
    @include('pages.assignment.personnel.create')
    @include('pages.assignment.personnel.update')
@endsection
