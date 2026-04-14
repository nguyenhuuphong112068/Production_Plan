@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.StageGroup.dataTable')
@endsection

@section('model')
    @include('pages.materData.StageGroup.create')
    @include('pages.materData.StageGroup.update')
@endsection
