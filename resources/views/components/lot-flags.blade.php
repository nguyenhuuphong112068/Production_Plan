{{-- Badge phân loại lô dùng chung cho các bảng lịch / lịch sử:
     <x-lot-flags :val="$data->is_val" :ret="$data->excess_pro_return_lot" />
     val = plan_master.is_val (lô thẩm định), ret = plan_master.excess_pro_return_lot (lô thu hồi dư phẩm). --}}
@props(['val' => false, 'ret' => false])
@if ($val)
    <span class="badge badge-primary d-inline-block m-0 mb-1" title="Lô thẩm định"
        style="white-space: normal; line-height: 1.3;">Thẩm định</span>
@endif
@if ($ret)
    <span class="badge badge-danger d-inline-block m-0 mb-1" title="Lô thu hồi dư phẩm"
        style="white-space: normal; line-height: 1.3;">Thu hồi dư phẩm</span>
@endif
