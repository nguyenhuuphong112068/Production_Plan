@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.BlisterMold.dataTable')
@endsection

@section('model')
    @include('pages.materData.BlisterMold.create')
    @include('pages.materData.BlisterMold.update')
    @include('pages.materData.BlisterMold.history')
@endsection
