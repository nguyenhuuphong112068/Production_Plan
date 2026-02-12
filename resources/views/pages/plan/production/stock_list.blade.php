
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.plan.production.dataTable_stock')
@endsection

@section('model')
    @include('pages.plan.production.stock_batch_detail')
@endsection
