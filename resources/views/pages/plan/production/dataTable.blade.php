<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<style>
    .step-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #007bff; /* màu xanh bootstrap */
    }

    .step-checkbox2 {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #007bff; /* màu xanh bootstrap */
    }

    .step-checkbox:checked {
        box-shadow: 0 0 5px #007bff;
    }
    .updateInput {
        width: 100%;
        border: none;
        outline: none;
        background: transparent;
        text-align: center;
        height: 100%;
        padding: 2px 4px;
        box-sizing: border-box;
    }

  /* Khi focus thì chỉ có viền nhẹ để người dùng biết đang nhập */
    .updateInput:focus {
        border: 1px solid #007bff;
        border-radius: 2px;
        background-color: #fff;
    }

  /* Tùy chọn: nếu bạn muốn chữ canh giữa theo chiều dọc */
    td input.updateInput {
        display: block;
        margin: auto;
    }
</style>

<div class="content-wrapper">
    <div class="card" style="min-height: 100vh">

        <div class="card-header mt-4" >
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>
        @php
            $auth_update = user_has_permission(session('user')['userId'], 'plan_production_update', 'disabled');
            $auth_deActive = user_has_permission(session('user')['userId'], 'plan_production_deActive', 'disabled');
        @endphp
        <!-- /.card-Body -->
        <div class="card-body">
            @if (!$send)
                <div class="row">
                    <div class="col-md-2">
                        @if (user_has_permission(session('user')['userId'], 'plan_production_create', 'boolean'))
                            <button class="btn btn-success btn-add mb-2" data-toggle="modal"
                                data-target="#selectProductModal" style="width: 155px;">
                                <i class="fas fa-plus"></i> Thêm
                            </button>
                        @endif
                    </div>

                    <div class="col-md-8"></div>
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
            @endif
            <table id="data_table_plan_master" class="table table-bordered table-striped" style="font-size: 16px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th>STT</th>
                        <th >Mã Sản Phẩm</th>
                        <th style="width:10%" >Sản Phẩm</th>
                        <th>Số Lô/Số lượng ĐG</th>
                        <th>Thị Trường/ Qui Cách</th>
                        <th>Ngày dự kiến KCS</th>
                        <th>Ưu Tiên</th>
                        <th>Lô Thẩm định</th>
                        <th>Nguồn</th>
                        <th>Ngày có đủ NL/BB</th>
                        {{-- <th>Bao Bì</th> --}}
                        <th>Ghi Chú</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                        <th style="width:1%">Cập Nhật</th>
                        <th style="width:1%">Vô Hiệu</th>
                        <th style="width:1%">Lịch Sữ</th>
                    </tr>

                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>

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
                                <div> {{ $data->name }} </div>
                                <div>  {{'(' . $data->batch_qty . ' ' . $data->unit_batch_qty . ')'}} </div>
                            </td>
                            <td style="text-align: center;" >
                                <input type= "text" class="updateInput" name="batch" value = "{{$data->batch }}" data-id = {{ $data->id }} {{ $auth_update }} style="font-weight: bold;" >                              
                                {{ $splittingModal = "" }}
                                <div class="btn {{$data->only_parkaging == 0? 'btn-success':'btn-secondary' }} btn-splitting" data-toggle="modal" data-target= "{{$data->only_parkaging == 0 ? '#selectProductModal':'#splittingUpdateModal'}}"
                                    
                                    {{ $data->active ? '' : 'disabled' }} data-id="{{ $data->id }}"
                                    data-name="{{ $data->name }}"
                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                    data-finished_product_code="{{ $data->finished_product_code }}"
                                    data-batch="{{ $data->batch }}" data-market="{{ $data->market }}"
                                    data-specification="{{ $data->specification }}" data-level="{{ $data->level }}"
                                    data-expected_date="{{ $data->expected_date }}" data-is_val="{{ $data->is_val }}"
                                    data-source_material_name="{{ $data->source_material_name }}"
                                    data-after_weigth_date="{{ $data->after_weigth_date}}"
                                    data-before_weigth_date="{{ $data->before_weigth_date}}"
                                    data-after_parkaging_date="{{ $data->after_parkaging_date }}"
                                    data-before_parkaging_date="{{ $data->before_parkaging_date }}"
                                    data-note="{{ $data->note }}" data-batch_qty="{{ $data->batch_qty }}"
                                    data-unit_batch_qty="{{ $data->unit_batch_qty}}"
                                    data-material_source_id="{{ $data->material_source_id}}"
                                    data-number_parkaging="{{ $data->number_parkaging}}"
                                
                                > 
                                    {{ $data->number_parkaging  . ' ' . $data->unit_batch_qty }} </div>
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
                            {{-- style="text-align: center; vertical-align: middle;" --}}
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

                            <td>{{ $data->source_material_name }}</td>

                            <td>
                                <input {{ $auth_update }} type= "date" class="updateInput" name="after_weigth_date" value="{{ $data->after_weigth_date ? \Carbon\Carbon::parse($data->after_weigth_date)->format('Y-m-d') : '' }}" data-id = {{ $data->id }}>
                                {{-- <input {{ $auth_update }} type= "date" class="updateInput" name="before_weigth_date" value="{{ $data->before_weigth_date ? \Carbon\Carbon::parse($data->before_weigth_date)->format('Y-m-d') : '' }}" data-id = {{ $data->id }}> --}}
                            
                                <input {{ $auth_update }} type= "date" class="updateInput" name="after_parkaging_date" value="{{ $data->after_parkaging_date ? \Carbon\Carbon::parse($data->after_parkaging_date)->format('Y-m-d') : '' }}" data-id = {{ $data->id }}>
                                {{-- <input {{ $auth_update }} type= "date" class="updateInput" name="before_parkaging_date" value="{{ $data->before_parkaging_date ? \Carbon\Carbon::parse($data->before_parkaging_date)->format('Y-m-d') : '' }}" data-id = {{ $data->id }}> --}}
                            </td>
                            <td> 
                                <input {{ $auth_update }} type= "text" class="updateInput" name="note" value = "{{$data->note }}" data-id = {{ $data->id }}>
                            </td>

                            <td>
                                <div> {{ $data->prepared_by }} </div>
                                <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                            </td>
 

                            <td class="text-center align-middle">
                                <button type="button"  class="btn btn-warning btn-edit" 
                                    {{ $auth_update }}
                                    {{ $data->active ? '' : 'disabled' }} data-id="{{ $data->id }}"
                                    data-name="{{ $data->name }}"
                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                    data-finished_product_code="{{ $data->finished_product_code }}"
                                    data-batch="{{ $data->batch }}" 
                                    data-market="{{ $data->market }}"
                                    data-specification="{{ $data->specification }}" 
                                    data-level="{{ $data->level }}"
                                    data-expected_date="{{ $data->expected_date }}" 
                                    data-is_val="{{ $data->is_val }}"
                                    data-code_val="{{ $data->code_val}}"
                                    data-source_material_name="{{ $data->source_material_name }}"
                                    data-after_weigth_date="{{ $data->after_weigth_date }}"
                                    data-before_weigth_date="{{ $data->before_weigth_date }}"
                                    data-after_parkaging_date="{{ $data->after_parkaging_date }}"
                                    data-before_parkaging_date="{{ $data->before_parkaging_date }}"
                                    data-note="{{ $data->note }}" 
                                    data-batch_qty="{{ $data->batch_qty }}"
                                    data-unit_batch_qty="{{ $data->unit_batch_qty }}"
                                    data-material_source_id="{{ $data->material_source_id }}"
                                    data-number_parkaging="{{ $data->number_parkaging}}"
                                   
                                    data-toggle="modal"
                                    data-target="#updateModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>

                            <td class="text-center align-middle">

                                <form class="form-deActive" action="{{ route('pages.plan.production.deActive') }}"
                                    method="post">
                                    @csrf
                                    <input type="hidden" name="id" value = "{{ $data->id }}">
                                    <input type="hidden" name="active" value="{{ $data->active }}">

                                    @if ($data->active == true && $send == false)
                                        <button type="submit" class="btn btn-danger" data-type="delete"
                                            {{ $auth_deActive }}
                                            data-name="{{ $data->name . ' - ' . $data->batch }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @elseif ($data->cancel == false && $send == true)
                                        <button type="submit" class="btn btn-danger" data-type="cancel"
                                            {{ $auth_deActive }}
                                            data-name="{{ $data->name . ' - ' . $data->batch }}">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @elseif ($data->cancel == true && $send == true)
                                        <button type="submit" class="btn btn-success" data-type="restore"
                                            {{ $auth_deActive }}
                                            data-name="{{ $data->name . ' - ' . $data->batch }}">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    @endif
                                </form>

                            </td>

                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-primary btn-history position-relative"
                                    data-id="{{ $data->id }}" data-toggle="modal" data-target="#historyModal">
                                    <i class="fas fa-history"></i>
                                    <span class="badge badge-danger"
                                        style="position: absolute; top: -5px;  right: -5px; border-radius: 50%;">
                                        {{ $data->history_count ?? 0 }}
                                    </span>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


    <script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
    <script src="{{ asset('js/popper.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

    @if (session('success'))
        <script>
            Swal.fire({
                title: 'Thành công!',
                text: '{{ session('success') }}',
                icon: 'success',
                timer: 1000, // tự đóng sau 2 giây
                showConfirmButton: false
            });
        </script>
    @endif

    <script>

        $(document).ready(function() {
            document.body.style.overflowY = "auto";
            preventDoubleSubmit("#send_form", "#send_btn");

            $('.btn-edit').click(function() {
                const button = $(this);
                const modal = $('#updateModal');

                //bỏ tick thẩm định
                modal.find('#update_checkbox1').prop('checked', false).val(false);
                modal.find('#update_checkbox2').prop('checked', false).val(false);
                modal.find('#update_checkbox3').prop('checked', false).val(false);


                // Gán dữ liệu vào input
                modal.find('input[name="id"]').val(button.data('id'));
                modal.find('input[name="name"]').val(button.data('name'));
                modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
                modal.find('input[name="finished_product_code"]').val(button.data('finished_product_code'));
                modal.find('input[name="batch"]').val(button.data('batch'));
                modal.find('input[name="material_source_id"]').val(button.data('material_source_id'));

                modal.find('textarea[name="source_material_name"]').val(button.data('source_material_name'));
                modal.find('input[name="after_weigth_date"]').val(button.data('after_weigth_date'));
                modal.find('input[name="before_weigth_date"]').val(button.data('before_weigth_date'));
                modal.find('input[name="after_parkaging_date"]').val(button.data('after_parkaging_date'));
                modal.find('input[name="before_parkaging_date"]').val(button.data('before_parkaging_date'));
                modal.find('textarea[name="note"]').val(button.data('note'));

                modal.find('input[name="batch_qty"]').val(button.data('batch_qty') + " - " + button.data('unit_batch_qty'));
                modal.find('input[name="specification"]').val(button.data('market') + " - " + button.data('specification'));
                modal.find('input[name="number_of_unit"]').attr('max', button.data('batch_qty'));
                modal.find('input[name="max_number_of_unit"]').val(button.data('batch_qty'));
                modal.find('input[name="number_of_unit"]').val(button.data('number_parkaging'));
                modal.find('input[name="expected_date"]').val(button.data('expected_date'));
                modal.find('input[name="level"][value="' + button.data('level') + '"]').prop('checked',true);


                if (button.data('is_val')  == 1 && button.data('code_val').split('_')[1] == "1"){
                    modal.find('input[name="batchNo1"]').val(button.data('batch'));
                    modal.find('#update_checkbox1').prop('checked', true).val(true);
                    modal.find('input[name="code_val_first"]').val(button.data('code_val').split('_')[0] + "_1");
                }else if (button.data('is_val')  == 1 && button.data('code_val').split('_')[1] == "2"){
                    modal.find('input[name="batchNo1"]').val(button.data('batch'));
                    modal.find('#update_checkbox2').prop('checked', true).val(true);
                    modal.find('input[name="code_val_first"]').val(button.data('code_val').split('_')[0] + "_1");
                }else if (button.data('is_val')  == 1 && button.data('code_val').split('_')[1] == "3"){
                    modal.find('input[name="batchNo1"]').val(button.data('batch'));
                    modal.find('#update_checkbox3').prop('checked', true).val(true);
                    modal.find('input[name="code_val_first"]').val(button.data('code_val').split('_')[0] + "_1");
                }
                
                const create_soure_modal = $('#create_soure_modal');
                create_soure_modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
                create_soure_modal.find('input[name="product_name"]').val(button.data('name'));
                create_soure_modal.find('input[name="mode"]').val("update");
            });

            $('.btn-splitting').click(function() {
                const button = $(this);
                const targetModal = button.data('target');
                
                if (targetModal == "#splittingUpdateModal"){
                    const modal = $(targetModal);
                    // Gán dữ liệu vào input
                    modal.find('input[name="id"]').val(button.data('id'));
                    modal.find('input[name="name"]').val(button.data('name'));
                    modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
                    modal.find('input[name="finished_product_code"]').val(button.data('finished_product_code'));
                    modal.find('input[name="batch"]').val(button.data('batch'));
                    modal.find('input[name="material_source_id"]').val(button.data('material_source_id'));

                    modal.find('textarea[name="source_material_name"]').val(button.data('source_material_name'));
                    modal.find('input[name="after_weigth_date"]').val(button.data('after_weigth_date'));
                    modal.find('input[name="before_weigth_date"]').val(button.data('before_weigth_date'));
                    modal.find('input[name="after_parkaging_date"]').val(button.data('after_parkaging_date'));
                    modal.find('input[name="before_parkaging_date"]').val(button.data('before_parkaging_date'));
                    modal.find('textarea[name="note"]').val(button.data('note'));

                    modal.find('input[name="batch_qty"]').val(button.data('batch_qty') + " - " + button.data('unit_batch_qty'));
                    modal.find('input[name="specification"]').val(button.data('market') + " - " + button.data('specification'));
                    modal.find('input[name="number_of_unit"]').attr('max', button.data('batch_qty'));
                    modal.find('input[name="max_number_of_unit"]').val(button.data('batch_qty'));
                    modal.find('input[name="number_of_unit"]').val(button.data('number_parkaging'));
                    modal.find('input[name="expected_date"]').val(button.data('expected_date'));
                    modal.find('input[name="is_val"]').prop('checked', button.data('is_val')).val(button.data('is_val'));

                    modal.find('input[name="level"][value="' + button.data('level') + '"]').prop('checked',true);
                }else {
                    const modal_splitting = $('#splittingModal');
                    modal_splitting.find('input[name="id"]').val(button.data('id'));
                    modal_splitting.find('input[name="batch"]').val(button.data('batch'));
                    modal_splitting.find('textarea[name="source_material_name"]').val(button.data('source_material_name'));
                    modal_splitting.find('input[name="number_of_unit"]').val(button.data('number_parkaging'));
                    $('#selectedModalId').val('#splittingModal');
     
                }

                 
            });

            $('.btn-add').click(function() {
                $('#selectedModalId').val("#createModal");
            });

            $('.form-deActive').on('submit', function(e) {
                e.preventDefault(); // chặn submit mặc định
                const form = this;
                const productName = $(form).find('button[type="submit"]').data('name');
                const type = $(form).find('button[type="submit"]').data('type');

                if (type == "delete") {
                    title = "Bạn chắc chắn muốn xóa kế hoạch?"
                } else if (type == "cancel") {
                    title = "Bạn chắc chắn muốn hủy kế hoạch?"
                } else {
                    title = "Bạn chắc chắn muốn phục hồi kế hoạch?"
                }

                Swal.fire({
                    title: title,
                    text: `Sản phẩm: ${productName}`,
                    icon: 'warning',
                    input: 'textarea', // ô nhập lý do
                    inputPlaceholder: 'Nhập lý do hủy...',
                    inputAttributes: {
                        'aria-label': 'Nhập lý do hủy'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy',
                    preConfirm: (reason) => {
                        if (!reason) {
                            Swal.showValidationMessage('Bạn phải nhập lý do');
                        }
                        return reason;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Tạo 1 input hidden trong form để gửi lý do
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'deactive_reason',
                            value: result.value
                        }).appendTo(form);
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'type',
                            value: type
                        }).appendTo(form);

                        form.submit(); // submit sau khi xác nhận
                    }
                });
            });

            $('.btn-history').on('click', function() {
                const planMasterId = $(this).data('id');
                const history_modal = $('#data_table_history_body')

                // Xóa dữ liệu cũ
                history_modal.empty();

                // Gọi Ajax lấy dữ liệu history
                $.ajax({
                    url: "{{ route('pages.plan.production.history') }}",
                    type: 'post',
                    data: {
                        id: planMasterId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        if (res.length === 0) {
                            history_modal.append(
                                `<tr><td colspan="13" class="text-center">Không có lịch sử</td></tr>`
                            );
                        } else {
                            res.forEach((item, index) => {
                                // map màu level
                                const colors = {
                                    1: 'background-color: #f44336; color: white;', // đỏ
                                    2: 'background-color: #ff9800; color: white;', // cam
                                    3: 'background-color: blue; color: white;', // xanh dương
                                    4: 'background-color: #4caf50; color: white;', // xanh lá
                                };
                                history_modal.append(`
                              <tr>
                                  <td>${index + 1}</td>
                                  <td class="${index === 0 ? 'text-success' : 'text-danger'}""> 
                                      <div>${item.intermediate_code ?? ''}</div>
                                      <div>${item.finished_product_code ?? ''}</div>
                                  </td>

                                  <td>${item.name ?? ''} (${item.batch_qty ?? ''} ${item.unit_batch_qty ?? ''})</td>
                                  <td>${item.batch ?? ''}</td>
                                  <td>
                                      <div>${item.market ?? ''}</div>
                                      <div>${item.specification ?? ''}</div>
                                  </td>

                                  <td style="text-align: center; vertical-align: middle;">
                                      <span style="display: inline-block; padding: 6px 10px; width: 50px; border-radius: 40px; ${colors[item.level] ?? ''}">
                                          <b>${item.level ?? ''}</b>
                                      </span>
                                  </td>

                                  <td>
                                      <div>${item.expected_date ? moment(item.expected_date).format('DD/MM/YYYY') : ''}</div>
                                  </td>

                                  <td class="text-center align-middle">
                                      ${item.is_val ? '<i class="fas fa-check-circle text-primary fs-4"></i>' : ''}
                                  </td>

                                  <td>${item.source_material_name ?? ''}</td>

                                  <td>
                                      <div>${item.after_weigth_date ? moment(item.after_weigth_date).format('DD/MM/YYYY') : ''}</div>
                                      <div>${item.after_parkaging_date ? moment(item.after_parkaging_date).format('DD/MM/YYYY') : ''}</div>
                                  </td>

                                  <td>${item.note ?? ''}</td>
                                  <td>${item.version ?? ''}</td>
                                  <td >${item.reason ?? ''}</td>

                                  <td>
                                      <div>${item.prepared_by ?? ''}</div>
                                      <div>${item.created_at ? moment(item.created_at).format('DD/MM/YYYY') : ''}</div>
                                  </td>
                              </tr>
                          `);
                            });
                        }
                    },
                    error: function() {
                        history_modal.append(
                            `<tr><td colspan="13" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
                        );
                    }
                });
            });

            $('#data_table_plan_master').DataTable({
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

            $(document).on('focus', '.updateInput', function () {
                $(this).data('old-value', $(this).val());
            });

            $(document).on('blur', '.updateInput', function () {
                
                let id = $(this).data('id');
                let name = $(this).attr('name');
                let updateValue = $(this).val();
                let oldValue = $(this).data('old-value');
              
                if (updateValue === oldValue)return;
                
                if (id == ''){
                    Swal.fire({
                    title: 'Cảnh Báo!',
                    text: 'Sản Phẩm Chưa Định Mức',
                    icon: 'warning',
                    timer: 1000, // tự đóng sau 2 giây
                    showConfirmButton: false
                });
                    $(this).val('');
                    return
                }

                if (name == "level"){
                    const pattern = /^[1-9]\d*$/;
                    if (updateValue && !pattern.test(updateValue)) {
                        Swal.fire({
                            title: 'Lỗi định dạng!',
                            text: 'Thời gian phải có dạng hh:mm (phút là 00, 15, 30, 45)',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $(this).focus();
                        $(this).css('border', '1px solid red');
                        return;
                    } else {
                        $(this).css('border', '');
                    }
                }


                $.ajax({
                    url: "{{ route('pages.plan.production.updateInput') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    name: name,
                    updateValue: updateValue
                    }
                });
            });

        });
    </script>
