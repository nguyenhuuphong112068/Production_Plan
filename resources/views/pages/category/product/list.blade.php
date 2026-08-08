
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.category.product.dataTable')
@endsection

@section('model')

  @include('pages.category.product.intermediate_category')
  @include('pages.category.product.create')
  {{-- TẠM ẨN: modal "Thêm Danh Mục Giả Định"
  @include('pages.category.product.create_hypothesis')
  --}}
  @include('pages.category.product.update')
  {{-- TẠM ẨN: modal "Cập Nhật DMGĐ"
  @include('pages.category.product.update_hypothesis')
  --}}
  @include('pages.category.product.recipe')
  @include('pages.category.product.create_bom')
  @include('pages.category.product.history')
  
@endsection
