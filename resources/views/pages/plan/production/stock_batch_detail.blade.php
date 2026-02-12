
<style>
     #data_table_batch_detail {
        font-size: 14px; /* từ 16 → 14 là vừa đẹp */
    }

    #data_table_batch_detail th,
    #data_table_batch_detail td {
        padding: 6px 6px;
    }

     .batch-detail-modal-size {
        max-width: 100% !important;
        width: 100% !important;
        max-height: 100% !important;
        height: 100% !important;
        margin-left: 10px;
        margin-top: 0px;
    }
 
  
    #batch-detail-modal-size .modal-content {
        height: 95vh;
        display: flex;
        flex-direction: column;
    }

    #batch-detail-modal-size .modal-body {
        flex: 1;
        overflow-y: auto;
    }
</style>

<div class="modal fade" id="batchDetialModal" tabindex="-1"
     data-backdrop="static" data-keyboard="false">

    <div class="modal-dialog batch-detail-modal-size">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Các Lô Liên Quan</h5>

                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
            <div class="card" >

  

            @php
                $auth_update = user_has_permission(session('user')['userId'], 'plan_production_update', 'disabled');
                $auth_deActive = user_has_permission(session('user')['userId'], 'plan_production_deActive', 'disabled');
                $auth_view_material = user_has_permission(session('user')['userId'], 'plan_production_view_material', 'disabled');
            @endphp
            
        <!-- /.card-Body -->
        <div class="card-body">
            {{-- @if (!$send)
                <div class="row">
                    <div class="col-md-2">
                        @if (user_has_permission(session('user')['userId'], 'plan_production_create', 'boolean'))
                            <button class="btn btn-success btn-add mb-2" data-toggle="modal"
                                data-target="#selectProductModal" style="width: 155px;">
                                <i class="fas fa-plus"></i> Thêm
                            </button>
                        @endif
                       
                    </div>

                    <div class="col-md-8 text-center">
                        @if (user_has_permission(session('user')['userId'], 'plan_production_create', 'boolean'))
                            <form action="{{ route('pages.plan.production.open_stock') }}" 
                                method="get"
                                class="d-inline-block">
                                @csrf
                                <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                                <input type="hidden" name="material_packaging_type" value="0">
                                <input type="hidden" name="title" value="BẢNG TÍNH NGUYÊN LIỆU">
                                <input type="hidden" name="selected" value="1">
                                <input type="hidden" name="current_url" value="{{ url()->full() }}">
                                <button type="submit" class="btn btn-success" {{ $auth_view_material }} style="width: 300px">
                                    <i class="fas fa-table"></i> Bảng Dự Trù Nguyên Liệu
                                </button>
                            </form>

                            <form action="{{ route('pages.plan.production.open_stock') }}" 
                                method="get"
                                class="d-inline-block ms-2">
                                @csrf
                                <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                                <input type="hidden" name="material_packaging_type" value="1">
                                <input type="hidden" name="title" value="BẢNG TÍNH BAO BÌ">
                                <input type="hidden" name="selected" value="1">
                                <input type="hidden" name="current_url" value="{{ url()->full() }}">
                                <button type="submit" class="btn btn-success"  style="width: 300px" {{ $auth_view_material }}>
                                    <i class="fas fa-table"></i> Bảng Dự Trù Bao Bì
                                </button>
                            </form>
                        @endif

                    </div>


                    <div class="col-md-2" style="text-align: right;">

                        <form id = "send_form" action="{{ route('pages.plan.production.send') }}" method="post">

                            @csrf
                            <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="production" value="{{ $production }}">
                            @if (user_has_permission(session('user')['userId'], 'plan_production_send', 'boolean'))
                            <button class="btn btn-success btn-send mb-2 " style="width: 177px;">
                                <i id = "send_btn" class="fas fa-paper-plane"></i> Gửi
                            </button>
                            @endif
                        </form>

                    </div>
                </div>
            @endif --}}

            <table id="data_table_batch_detail" class="table table-bordered table-striped" style="font-size: 16px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th>STT</th>
                        <th>Tình Trạng</th>
                        <th >Mã Sản Phẩm</th>
                        <th style="width:10%" >Sản Phẩm</th>
                        <th>Số Lô/Số lượng ĐG</th>
                        <th>Thị Trường/ Qui Cách</th>
                        <th>Ngày dự kiến KCS</th>
                        <th>Ưu Tiên</th>
                        <th>Lô Thẩm định</th>
                       
                        <th>
                            <div> {{ "(1) Ngày có đủ NL" }}  </div>
                            <div> {{ "(2) Ngày có đủ BB" }}  </div>
                            <div> {{ "(3) Ngày được phép cân" }}  </div>
                            <div> {{ "(4) Ngày HH NL chính" }}  </div>
                            <div> {{ "(5) Ngày HH BB" }}  </div>
                        </th>

                        <th>
                            <div> {{ "(1) PC trước" }}  </div>
                            <div> {{ "(2) THT trước" }}  </div>
                            <div> {{ "(3) BP trước" }}  </div>
                            <div> {{ "(4) ĐG trước" }}  </div>
                        </th>
                       
                        <th>Ghi Chú</th>
                </thead>
                <tbody id = "data_table_batch_detail_body">

                    {{-- @foreach ($datas as $data)
                        <tr>
                            <td>
                                <div> {{ $loop->iteration }} </div>
                                @if(session('user')['userGroup'] == "Admin") <div> {{ $data->id}} </div> @endif
                            </td>
                            <td>
                                @php
                                    $stutus_colors = [
                                        "Chưa làm" => 'background-color: green; color: white;', 
                                        "Đã Cân"        => 'background-color: #e3f2fd; color: #0d47a1;', // xanh rất nhạt
                                        "Đã Pha chế"    => 'background-color: #bbdefb; color: #0d47a1;',
                                        "Đã THT"        => 'background-color: #90caf9; color: #0d47a1;',
                                        "Đã định hình"  => 'background-color: #64b5f6; color: white;',
                                        "Đã Bao phim"   => 'background-color: #1e88e5; color: white;',
                                        "Hoàn Tất ĐG"   => 'background-color: #0d47a1; color: white;', // xanh đậm nhất
                                        'Hủy' => 'background-color: red; color: white;'
                                      
                                    ];
                                @endphp
                                <div class ="text-center" style="display: inline-block; padding: 6px 10py; width: 100px; border-radius: 10px; {{ $stutus_colors[$data->status] ?? '' }}"
                                    > {{ $data->status }} </div>
                            </td>


                            @if (!$data->cancel)
                                <td class="text-success">
                                    <div> {{ $data->intermediate_code }} </div>
                                    <div> {{ $data->finished_product_code }} </div>
                                </td>
                            @else
                                <td class="text-danger">
                                    <div> {{ $data->intermediate_code }} </div>
                                    <div> {{ $data->finished_product_code }} </div>
                                </td>
                            @endif

                            <td>
                                <div> {{ $data->intermediate_product_name }} </div>
                                <div> {{ trim($data->finished_product_name) == trim($data->intermediate_product_name) ? '':trim($data->finished_product_name)}} </div>
                                <div>  {{'(' . $data->batch_qty . ' ' . $data->unit_batch_qty . ')'}} </div>
                            </td>
                            <td style="text-align: center;" >
                                <input type= "text" class="updateInput" name="batch" value = "{{$data->batch }}" data-id = {{ $data->id }} {{ $auth_update }} style="font-weight: bold;" >                              
                                {{ $splittingModal = "" }}

                                @if ($data->number_parkaging > 0)
                                    @if ($auth_update != 'disabled')
                                    <div  class="btn {{$data->only_parkaging == 0? 'btn-success':'btn-secondary' }} btn-splitting" data-toggle="modal" data-target= "{{$data->only_parkaging == 0 ? '#selectProductModal':'#splittingUpdateModal'}}"
                                        {{ $data->active ? '' : 'disabled' }} data-id="{{ $data->id }}"
                                        data-name="{{ $data->finished_product_name }}"
                                        data-intermediate_code="{{ $data->intermediate_code }}"
                                        data-finished_product_code="{{ $data->finished_product_code }}"
                                        data-batch="{{ $data->batch }}" data-market="{{ $data->market }}"
                                        data-specification="{{ $data->specification }}" data-level="{{ $data->level }}"
                                        data-expected_date="{{ $data->expected_date }}" data-is_val="{{ $data->is_val }}"
                                        data-source_material_name="{{ $data->source_material_name }}"
                                        data-after_weigth_date="{{ $data->after_weigth_date}}"
                                        data-after_parkaging_date="{{ $data->after_parkaging_date }}"
                                        data-note="{{ $data->note }}" data-batch_qty="{{ $data->batch_qty }}"
                                        data-unit_batch_qty="{{ $data->unit_batch_qty}}"
                                        data-material_source_id="{{ $data->material_source_id}}"
                                        data-number_parkaging="{{ $data->number_parkaging}}"

                                       
                                    >
                                        {{ $data->number_parkaging  . ' ' . $data->unit_batch_qty }} </div> 
                                    @else
                                        {{ $data->number_parkaging  . ' ' . $data->unit_batch_qty }}
                                    @endif
                                @endif
                                </td>

                            <td>
                                <div> {{ $data->market }} </div>
                                <div> {{ $data->specification }} </div>
                            </td>

                            <td>
                                <input type= "date" class="updateInput" name="expected_date"  value="{{ $data->expected_date ? \Carbon\Carbon::parse($data->expected_date)->format('Y-m-d') : '' }}" data-id = {{ $data->id }} {{ $auth_update }}>
                            </td>
                            
                            @php
                                $colors = [
                                    1 => 'background-color: #f44336; color: white;', // đỏ
                                    2 => 'background-color: #ff9800; color: white;', // cam
                                    3 => 'background-color: blue; color: white;', // vàng
                                    4 => 'background-color: #4caf50; color: white;', // xanh lá
                                ];
                            @endphp
                           
                            <td class="text-center "> 
                                <span
                                    style="display: inline-block; padding: 6px 10py; width: 50px; border-radius: 40px; {{ $colors[$data->level] ?? '' }}">
                                    <input type= "text" class="updateInput" name="level" value = "{{$data->level}}" data-id = {{$data->id}} {{ $auth_update }}>
                                </span>
                            </td>

                            <td class="text-center ">
                                  <input class="form-check-input step-checkbox2"
                                      type="checkbox" role="switch"
                                      data-id="{{ $data->id }}"
                                      id="{{ $data->id }}"
                                      {{ $auth_update != ''?'readOnly':''}}
                                      {{ $data->is_val ? 'checked' : '' }}
                                      readonly
                                      >
                                      <br>
                                    @if ($data->is_val)
                                        Lô thứ  {{$data->code_val ? explode('_', $data->code_val)[1] ?? '' : '' }}
                                    @endif
                            </td>

                            

                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(1):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput" name="after_weigth_date"
                                        value="{{ $data->after_weigth_date ? \Carbon\Carbon::parse($data->after_weigth_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(2):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput" name="after_parkaging_date"
                                        value="{{ $data->after_parkaging_date ? \Carbon\Carbon::parse($data->after_parkaging_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(3):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput" name="allow_weight_before_date"
                                        value="{{ $data->allow_weight_before_date ? \Carbon\Carbon::parse($data->allow_weight_before_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(4):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput" name="expired_material_date"
                                        value="{{ $data->expired_material_date ? \Carbon\Carbon::parse($data->expired_material_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(5):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput" name="expired_packing_date"
                                        value="{{ $data->expired_packing_date ? \Carbon\Carbon::parse($data->expired_packing_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>
                         
                            </td>

                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(1):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput" name="preperation_before_date"
                                        value="{{ $data->preperation_before_date ? \Carbon\Carbon::parse($data->preperation_before_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(2):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput" name="blending_before_date"
                                        value="{{ $data->blending_before_date ? \Carbon\Carbon::parse($data->blending_before_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(3):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput" name="coating_before_date"
                                        value="{{ $data->coating_before_date ? \Carbon\Carbon::parse($data->coating_before_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                 <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(4):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput" name="parkaging_before_date"
                                        value="{{ $data->parkaging_before_date ? \Carbon\Carbon::parse($data->parkaging_before_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>
                         
                            </td>


                            <td> 
                                {{ $data->note }}
                            </td>

                            <td>
                                <div> {{ $data->prepared_by }} </div>
                                <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                            </td>

                        </tr>
                    @endforeach --}}
                </tbody>
            </table>

            </div>
        </div>
    </div>
</div>


    <script>

        $(document).ready(function() {
            document.body.style.overflowY = "auto";
            preventDoubleSubmit("#send_form", "#send_btn");

  
            $('#data_table_batch_detail').DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Tất cả"]
                ],
                language: {
                    search: "Tìm kiếm:",
                    lengthMenu: "Hiển thị _MENU_ dòng",
                    info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                    paginate: {
                        previous: "Trước",
                        next: "Sau"
                    }
                },
                infoCallback: function(settings, start, end, max, total, pre) {
                    let activeCount = 0;
                    let inactiveCount = 0;

                    settings.aoData.forEach(function(row) {
                        // row.anCells là danh sách <td> của từng hàng
                        const lastTd = row.anCells[row.anCells.length -
                            1]; // cột cuối (Vô Hiệu)
                        const btn = $(lastTd).find('button[type="submit"]');
                        const status = btn.data('type'); // lấy 1 hoặc 0

                        if (status == 1) activeCount++;
                        else inactiveCount++;
                    });

                    return pre + ` (Đang hiệu lực: ${activeCount}, Vô hiệu: ${inactiveCount})`;
                }
            });



        });


    </script>
