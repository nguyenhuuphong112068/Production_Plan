@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.category.material_source_warning.dataTable')
@endsection

@section('model')
    @include('pages.category.material_source_warning.create')
    @include('pages.category.material_source_warning.update')
@endsection
