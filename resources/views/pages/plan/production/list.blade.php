
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection


@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.plan.production.dataTable')
@endsection
@section('model')

  {{-- Hàm dùng chung cho cảnh báo nguồn NL ở 2 modal tạo lô / sửa lô --}}
  @include('pages.plan.production.material_source_warning_script')

  @include('pages.plan.production.update')
  @include('pages.plan.production.create')
  @include('pages.plan.production.batch_splitting')
  @include('pages.plan.production.update_batch_splitting')   
  @include('pages.plan.production.finished_category')
  @include('pages.plan.production.history')
  @include('pages.plan.production.confirm_first_val_batch')
  {{-- @include('pages.plan.production.source_material_list') --}}
  {{-- @include('pages.plan.production.create_source')  --}}
  
@endsection
