<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<style>
    .step-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #007bff;
        /* màu xanh bootstrap */
    }

    .step-checkbox2 {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #007bff;
        /* màu xanh bootstrap */
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

    .step-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #007bff;
        /* màu xanh bootstrap */
    }

    .step-checkbox:checked {
        box-shadow: 0 0 5px #007bff;
    }

    #bulkEditModal .modal-dialog {
        max-width: 100% !important;
        width: 100% !important;
        max-height: 100% !important;
        height: 100% !important;
        margin-top: 0px;
        margin-right: 0px;
        margin-left: 10px;
    }

    #bulkEditModal .modal-content {
        height: 100vh;
    }

    #bulkEditModal .modal-body {
        overflow-y: auto;
        max-height: calc(100vh - 120px);
    }

    .highlight-row {
        background-color: #fff3cd !important;
        /* vàng nhạt */
    }
</style>


@php
    $auth_update = user_has_permission(session('user')['userId'], 'plan_production_update', 'disabled');
    $auth_deActive = user_has_permission(session('user')['userId'], 'plan_production_deActive', 'disabled');
    $auth_view_material = user_has_permission(session('user')['userId'], 'plan_production_view_material', 'disabled');
@endphp


<div class="content-wrapper">
    <div class="card" style="min-height: 100vh">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>

        <!-- /.card-Body -->
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">

                <!-- Nhóm 1: Thao tác -->
                <div class="d-flex mb-2" style="gap: 10px;">
                    @if (user_has_permission(session('user')['userId'], 'plan_production_create', 'boolean') && !$send)
                        <button class="btn btn-success btn-add" data-toggle="modal" data-target="#selectProductModal">
                            <i class="fas fa-plus"></i> Thêm
                        </button>
                    @endif
                    @if (user_has_permission(session('user')['userId'], 'plan_production_create', 'boolean') && $plan_list_id > 0)
                        <button type="button" class="btn btn-warning btn-bulk-edit font-weight-bold text-white"
                            disabled>
                            Sửa Nhiều Lô (<span id="selected-count">0</span>)
                        </button>
                        <button type="button" class="btn btn-danger btn-bulk-deactive font-weight-bold text-white"
                            disabled>
                            Hủy Nhiều Lô (<span id="selected-count-deactive">0</span>)
                        </button>
                    @endif
                </div>

                <!-- Nhóm 2: Dự trù -->
                <div class="d-flex mb-2" style="gap: 10px;">
                    @if (user_has_permission(session('user')['userId'], 'plan_production_create', 'boolean') || true)
                        <form action="{{ route('pages.plan.production.open_stock') }}" method="get" class="m-0">
                            @csrf
                            <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                            <input type="hidden" name="material_packaging_type" value="0">
                            <input type="hidden" name="title" value="BẢNG TÍNH NGUYÊN LIỆU">
                            <input type="hidden" name="selected" value="1">
                            <input type="hidden" name="current_url" value="{{ url()->full() }}">
                            <button type="submit" class="btn btn-success" {{ $auth_view_material }}>
                                <i class="fas fa-table"></i> Bảng Dự Trù Nguyên Liệu
                            </button>
                        </form>

                        <form action="{{ route('pages.plan.production.open_stock') }}" method="get" class="m-0">
                            @csrf
                            <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                            <input type="hidden" name="material_packaging_type" value="1">
                            <input type="hidden" name="title" value="BẢNG TÍNH BAO BÌ">
                            <input type="hidden" name="selected" value="1">
                            <input type="hidden" name="current_url" value="{{ url()->full() }}">
                            <button type="submit" class="btn btn-success" {{ $auth_view_material }}>
                                <i class="fas fa-table"></i> Bảng Dự Trù Bao Bì
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Nhóm 3: Gửi và Xuất -->
                <div class="d-flex mb-2" style="gap: 10px;">
                    <form id="send_form" action="{{ route('pages.plan.production.send') }}" method="post"
                        class="m-0">
                        @csrf
                        <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="production" value="{{ $production }}">
                        @if (user_has_permission(session('user')['userId'], 'plan_production_send', 'boolean') && !$send)
                            <button class="btn btn-success btn-send">
                                <i id="send_btn" class="fas fa-paper-plane"></i> Gửi
                            </button>
                        @endif
                    </form>
                    <button class="btn btn-success" onclick="exportPlanToExcel()">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </button>
                </div>

            </div>

            <table id="data_table_plan_master" class="table table-bordered table-striped" style="font-size: 16px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th>STT</th>
                        <th>Tình Trạng
                            <br>
                            <select id="statusFilter" class="form-control form-control-sm mt-1">
                                <option value="">-- Tất cả --</option>
                                <option value="Hủy">Hủy</option>
                                <option value="Chưa làm">Chưa làm</option>
                                <option value="Đã Cân">Đã Cân</option>
                                <option value="Đã Pha chế">Đã Pha chế</option>
                                <option value="Đã THT">Đã THT</option>
                                <option value="Đã định hình">Đã định hình</option>
                                <option value="Đã Bao phim">Đã Bao phim</option>
                                <option value="Hoàn Tất ĐG">Hoàn Tất ĐG</option>
                            </select>

                        </th>
                        @if ($plan_list_id < 0)
                            <th style="width:4%">Tháng</th>
                        @endif

                        <th>Mã Sản Phẩm</th>
                        <th style="width:7%">Sản Phẩm</th>
                        <th style="width:5%">
                            {{ 'Số Lô Dự Kiến' }} <br>
                            {{ 'Số Lô Thực Tế' }} <br>
                            {{ 'Số lượng ĐG' }}
                        </th>
                        <th>Thị Trường/ Qui Cách</th>

                        <th style="width:4%">Ngày dự kiến KCS</th>
                        <th>Ưu Tiên</th>
                        <th>Lô Thẩm định</th>

                        <th>
                            <div> {{ '(1) Ngày có đủ NL' }} </div>
                            <div> {{ '(2) Ngày có đủ BB' }} </div>
                            <div> {{ '(3) Ngày được phép cân' }} </div>
                            <div> {{ '(4) Ngày HH NL chính' }} </div>
                            <div> {{ '(5) Ngày HH BB' }} </div>
                        </th>

                        <th>
                            <div> {{ '(1) PC trước' }} </div>
                            <div> {{ '(2) THT trước' }} </div>
                            <div> {{ '(3) BP trước' }} </div>
                            <div> {{ '(4) ĐG trước' }} </div>
                        </th>

                        <th style="width:15%">Ghi Chú</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                        <th style="width:1%">Cập Nhật/ Vô Hiệu</th>
                        {{-- <th style="width:1%">Vô Hiệu</th> --}}
                        <th style="width:1%">Lịch Sử</th>
                        <th class = "text-center" style="display: none;">
                            Chọn
                            <br>
                            <button type="button" class="btn btn-primary btn-selected-all mt-3" {{ $auth_update }}
                                data-plan_list_id="{{ $plan_list_id }}" data-active="0">
                                <i class="fas fa-check"></i>
                            </button>
                        </th>
                    </tr>

                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr class="{{ $data->IsHypothesis ? 'highlight-row' : '' }}"
                            data-intermediate-code="{{ trim($data->intermediate_code) }}"
                            data-fp-code="{{ trim($data->finished_product_code) }}"
                            data-name="{{ trim($data->finished_product_name) }}"
                            data-market="{{ trim($data->market) }}"
                            data-specification="{{ trim($data->specification) }}"
                            data-batch-qty="{{ trim($data->batch_qty) }}"
                            data-unit-batch-qty="{{ trim($data->unit_batch_qty) }}"
                            data-plan-list-id="{{ $data->plan_list_id }}" data-level="{{ $data->level }}"
                            data-batch="{{ $data->batch }}"
                            data-expected-date="{{ $data->expected_date ? \Carbon\Carbon::parse($data->expected_date)->format('Y-m-d') : '' }}"
                            data-is-val="{{ $data->is_val }}" data-code-val="{{ $data->code_val }}"
                            data-after-weigth-date="{{ $data->after_weigth_date ? \Carbon\Carbon::parse($data->after_weigth_date)->format('Y-m-d') : '' }}"
                            data-after-parkaging-date="{{ $data->after_parkaging_date ? \Carbon\Carbon::parse($data->after_parkaging_date)->format('Y-m-d') : '' }}"
                            data-allow-weight-before-date="{{ $data->allow_weight_before_date ? \Carbon\Carbon::parse($data->allow_weight_before_date)->format('Y-m-d') : '' }}"
                            data-expired-material-date="{{ $data->expired_material_date ? \Carbon\Carbon::parse($data->expired_material_date)->format('Y-m-d') : '' }}"
                            data-expired-packing-date="{{ $data->expired_packing_date ? \Carbon\Carbon::parse($data->expired_packing_date)->format('Y-m-d') : '' }}"
                            data-note="{{ $data->note }}" style="cursor: pointer;">

                            <td>
                                <div> {{ $loop->iteration }} </div>
                                @if (session('user')['userGroup'] == 'Admin')
                                    <div> {{ $data->id }} </div>
                                @endif
                            </td>

                            <td>
                                @if ($data->IsHypothesis)
                                    <div class ="text-center"
                                        style="display: inline-block; padding: 6px 10py; width: 100px; border-radius: 10px; background-color: #d40bf7; color: #ffffff;">
                                        {{ 'Lô Giả Định' }} </div>
                                @else
                                    @php
                                        $stutus_colors = [
                                            'Chưa làm' => 'background-color: green; color: white;',
                                            'Đã Cân' => 'background-color: #e3f2fd; color: #0d47a1;', // xanh rất nhạt
                                            'Đã Pha chế' => 'background-color: #bbdefb; color: #0d47a1;',
                                            'Đã THT' => 'background-color: #90caf9; color: #0d47a1;',
                                            'Đã định hình' => 'background-color: #64b5f6; color: white;',
                                            'Đã Bao phim' => 'background-color: #1e88e5; color: white;',
                                            'Hoàn Tất ĐG' => 'background-color: #0d47a1; color: white;',
                                            'Hoàn Tất' => 'background-color: #0d47a1; color: white;', // xanh đậm nhất
                                            'Hủy' => 'background-color: red; color: white;',
                                        ];
                                    @endphp

                                    {{-- <div class ="text-center" 
                                        style="display: inline-block; padding: 6px 10py; width: 100px; border-radius: 10px; {{ $stutus_colors[$data->status] ?? '' }}"
                                        > {{ $data->status }} 
                                    </div> --}}

                                    <div class ="text-center"
                                        style="display: inline-block; padding: 6px 10py; width: 100px; border-radius: 10px; {{ $stutus_colors[$data->status] ?? '' }}">
                                        {{ $data->status }}
                                    </div>
                                @endif

                            </td>

                            @if ($plan_list_id < 0)
                                <td>{{ $plan_list_id_title[$data->plan_list_id] ?? 'NA' }}</td>
                            @endif


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
                                <div>
                                    {{ trim($data->finished_product_name) == trim($data->intermediate_product_name) ? '' : trim($data->finished_product_name) }}
                                </div>
                                <div> {{ '(' . $data->batch_qty . ' ' . $data->unit_batch_qty . ')' }} </div>
                            </td>

                            <td style="text-align: center;">
                                <input type= "text" class="updateInput" name="batch"
                                    value = "{{ $data->batch }}" data-id={{ $data->id }} {{ $auth_update }}
                                    style="font-weight: bold;">
                                <b class="text-blue"> {{ $data->actual_batch }} </b>
                                @if ($data->number_parkaging > 0)
                                    @if ($auth_update != 'disabled')
                                        <div class="btn {{ $data->only_parkaging == 0 ? 'btn-success' : 'btn-secondary' }} btn-splitting"
                                            data-toggle="modal"
                                            data-target= "{{ $data->only_parkaging == 0 ? '#selectProductModal' : '#splittingUpdateModal' }}"
                                            {{ $data->active ? '' : 'disabled' }} data-id="{{ $data->id }}"
                                            data-name="{{ $data->finished_product_name }}"
                                            data-intermediate_code="{{ $data->intermediate_code }}"
                                            data-finished_product_code="{{ $data->finished_product_code }}"
                                            data-batch="{{ $data->batch }}" data-market="{{ $data->market }}"
                                            data-specification="{{ $data->specification }}"
                                            data-level="{{ $data->level }}"
                                            data-expected_date="{{ $data->expected_date }}"
                                            data-is_val="{{ $data->is_val }}"
                                            data-source_material_name="{{ $data->source_material_name }}"
                                            data-after_weigth_date="{{ $data->after_weigth_date }}"
                                            data-after_parkaging_date="{{ $data->after_parkaging_date }}"
                                            data-note="{{ $data->note }}" data-batch_qty="{{ $data->batch_qty }}"
                                            data-unit_batch_qty="{{ $data->unit_batch_qty }}"
                                            data-material_source_id="{{ $data->material_source_id }}"
                                            data-number_parkaging="{{ $data->number_parkaging }}"
                                            data-product_caterogy_id="{{ $data->product_caterogy_id }}"
                                            data-plan_list_id="{{ $data->plan_list_id }}"
                                            data-IsHypothesis="{{ $data->IsHypothesis }}">
                                            {{ $data->number_parkaging . ' ' . $data->unit_batch_qty }} </div>
                                    @else
                                        {{ $data->number_parkaging . ' ' . $data->unit_batch_qty }}
                                    @endif
                                @endif
                            </td>

                            <td>
                                <div> {{ $data->market }} </div>
                                <div> {{ $data->specification }} </div>
                            </td>

                            <td>
                                <input type= "date" class="updateInput" name="expected_date"
                                    value="{{ $data->expected_date ? \Carbon\Carbon::parse($data->expected_date)->format('Y-m-d') : '' }}"
                                    data-id={{ $data->id }} {{ $auth_update }}>
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
                                    <input type= "text" class="updateInput" name="level"
                                        value = "{{ $data->level }}" data-id={{ $data->id }}
                                        {{ $auth_update }}>
                                </span>
                            </td>

                            <td class="text-center ">
                                <input class="form-check-input step-checkbox2" type="checkbox" role="switch"
                                    data-id="{{ $data->id }}" id="{{ $data->id }}"
                                    {{ $auth_update != '' ? 'readOnly' : '' }} {{ $data->is_val ? 'checked' : '' }}
                                    readonly>
                                <br>
                                @if ($data->is_val)
                                    Lô thứ {{ $data->code_val ? explode('_', $data->code_val)[1] ?? '' : '' }}
                                @endif

                                @if (isset($data->is_validation_tracking) && $data->is_validation_tracking)
                                    @php
                                        $vts = \App\Models\ValidationTrackingPlanMaster::where(
                                            'plan_master_id',
                                            $data->id,
                                        )
                                            ->join(
                                                'validation_tracking',
                                                'validation_tracking_plan_master.validation_tracking_id',
                                                '=',
                                                'validation_tracking.id',
                                            )
                                            ->get();
                                    @endphp
                                    @foreach ($vts as $vt)
                                        <div class="mt-1 text-left">
                                            <span class="badge badge-warning"
                                                style="white-space: normal; text-align: left; line-height: 1.4; border: 1px solid #ffc107;">
                                                <i class="fas fa-exclamation-triangle"></i> TĐNL:
                                                {{ $vt->MaterialName }}
                                                @if ($vt->purpose)
                                                    <br><small>{{ $vt->purpose }}</small>
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </td>

                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(1):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput"
                                        name="after_weigth_date"
                                        value="{{ $data->after_weigth_date ? \Carbon\Carbon::parse($data->after_weigth_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(2):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput"
                                        name="after_parkaging_date"
                                        value="{{ $data->after_parkaging_date ? \Carbon\Carbon::parse($data->after_parkaging_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(3):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput"
                                        name="allow_weight_before_date"
                                        value="{{ $data->allow_weight_before_date ? \Carbon\Carbon::parse($data->allow_weight_before_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(4):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput"
                                        name="expired_material_date"
                                        value="{{ $data->expired_material_date ? \Carbon\Carbon::parse($data->expired_material_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(5):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput"
                                        name="expired_packing_date"
                                        value="{{ $data->expired_packing_date ? \Carbon\Carbon::parse($data->expired_packing_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                            </td>

                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(1):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput"
                                        name="preperation_before_date"
                                        value="{{ $data->preperation_before_date ? \Carbon\Carbon::parse($data->preperation_before_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(2):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput"
                                        name="blending_before_date"
                                        value="{{ $data->blending_before_date ? \Carbon\Carbon::parse($data->blending_before_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(3):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput"
                                        name="coating_before_date"
                                        value="{{ $data->coating_before_date ? \Carbon\Carbon::parse($data->coating_before_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>(4):</span>
                                    <input {{ $auth_update }} type="date" class="updateInput"
                                        name="parkaging_before_date"
                                        value="{{ $data->parkaging_before_date ? \Carbon\Carbon::parse($data->parkaging_before_date)->format('Y-m-d') : '' }}"
                                        data-id="{{ $data->id }}">
                                </div>

                            </td>

                            <td>
                                <textarea {{ $auth_update }} class="updateInput text-left " name="note" rows="5"
                                    style="width:100%; resize:vertical;" data-id="{{ $data->id }}"> {{ $data->note ?? '' }}</textarea>
                            </td>

                            <td>
                                <div> {{ $data->prepared_by }} </div>
                                <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                            </td>

                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-warning btn-edit mb-2" {{ $auth_update }}
                                    {{ $data->active ? '' : 'disabled' }} data-id="{{ $data->id }}"
                                    data-name="{{ $data->finished_product_name }}"
                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                    data-finished_product_code="{{ $data->finished_product_code }}"
                                    data-batch="{{ $data->batch }}" data-market="{{ $data->market }}"
                                    data-specification="{{ $data->specification }}"
                                    data-level="{{ $data->level }}"
                                    data-expected_date="{{ $data->expected_date }}"
                                    data-is_val="{{ $data->is_val }}" data-code_val="{{ $data->code_val }}"
                                    data-source_material_name="{{ $data->source_material_name }}"
                                    data-after_weigth_date="{{ $data->after_weigth_date }}"
                                    data-after_parkaging_date="{{ $data->after_parkaging_date }}"
                                    data-note="{{ $data->note }}" data-batch_qty="{{ $data->batch_qty }}"
                                    data-unit_batch_qty="{{ $data->unit_batch_qty }}"
                                    data-material_source_id="{{ $data->material_source_id }}"
                                    data-number_parkaging="{{ $data->number_parkaging }}" data-toggle="modal"
                                    data-target="#updateModal">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <form class="form-deActive" action="{{ route('pages.plan.production.deActive') }}"
                                    method="post">
                                    @csrf
                                    <input type="hidden" name="id" value = "{{ $data->id }}">
                                    <input type="hidden" name="active" value="{{ $data->active }}">
                                    <input type="hidden" name="only_parkaging"
                                        value="{{ $data->only_parkaging }}">

                                    @if ($data->active == true && $send == false)
                                        <button type="submit" class="btn btn-danger" data-type="delete"
                                            {{ $auth_deActive }}
                                            data-name="{{ $data->finished_product_name . ' - ' . $data->batch }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @elseif ($data->cancel == false && $send == true)
                                        <button type="submit" class="btn btn-danger" data-type="cancel"
                                            {{ $auth_deActive }}
                                            data-name="{{ $data->finished_product_name . ' - ' . $data->batch }}">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @elseif ($data->cancel == true && $send == true)
                                        <button type="submit" class="btn btn-success" data-type="restore"
                                            {{ $auth_deActive }}
                                            data-name="{{ $data->finished_product_name . ' - ' . $data->batch }}">
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
                                </button>
                            </td>

                            <td class="text-center align-middle" style="display: none;">
                                <input type="checkbox" class="step-checkbox" name="selected"
                                    data-id={{ $data->id }} value="1">
                            </td>


                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Sửa Hàng Loạt -->
    <div class="modal fade" id="bulkEditModal" tabindex="-1" role="dialog" aria-labelledby="bulkEditModalLabel"
        aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="bulkEditModalLabel"><i
                            class="fas fa-edit text-warning"></i> CHỈNH SỬA HÀNG LOẠT (<span
                            id="bulk-selected-count">0</span> dòng được chọn)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                    <!-- Product Info Block -->
                    <div class="row mb-3 pb-2 border-bottom">
                        <div class="col-md-3">
                            <label class="font-weight-bold mb-1" style="font-size: 13px;">Mã BTP</label>
                            <input type="text" class="form-control form-control-sm bg-light"
                                id="bulk_info_intermediate_code" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="font-weight-bold mb-1" style="font-size: 13px;">Mã TP</label>
                            <input type="text" class="form-control form-control-sm bg-light"
                                id="bulk_info_fp_code" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="font-weight-bold mb-1" style="font-size: 13px;">Qui Cách - Thị
                                Trường</label>
                            <input type="text" class="form-control form-control-sm bg-light"
                                id="bulk_info_spec_market" readonly>
                        </div>

                        <div class="col-md-9 mt-2">
                            <label class="font-weight-bold mb-1" style="font-size: 13px;">Tên Sản Phẩm</label>
                            <input type="text" class="form-control form-control-sm bg-light"
                                id="bulk_info_product_name" readonly>
                        </div>
                        <div class="col-md-3 mt-2">
                            <label class="font-weight-bold mb-1" style="font-size: 13px;">Cỡ Lô</label>
                            <input type="text" class="form-control form-control-sm bg-light"
                                id="bulk_info_batch_qty" readonly>
                        </div>
                    </div>

                    <form id="bulkEditForm">
                        <div class="row">
                            <!-- Cột 1: Thông tin cơ bản -->
                            <div class="col-md-3 border-right" style="font-size: 13px;">

                                <!-- Batch -->
                                <div class="form-group mb-2">
                                    <label class="font-weight-bold mb-1">Số Lô</label>
                                    <div class="input-group input-group-sm mb-1">
                                        <input type="text" class="form-control" id="bulk_batch">
                                        <span class="input-group-text p-0" style="width: 105px;">
                                            <input type="checkbox" id="bulk_format_batch_no" checked
                                                data-bootstrap-switch data-on-text="AAMMYY" data-off-text="YWWAA"
                                                data-on-color="success" data-off-color="danger" data-size="small">
                                        </span>
                                    </div>
                                </div>

                                <!-- Expected Date -->
                                <div class="form-group mb-2">
                                    <label class="font-weight-bold mb-1">Ngày dự kiến KCS</label>
                                    <input type="date" class="form-control form-control-sm"
                                        id="bulk_expected_date">
                                </div>

                                <!-- Level -->
                                <div class="card card-success mb-2 shadow-sm">
                                    <div class="card-header py-1">
                                        <label class="card-title m-0 font-weight-bold" style="font-size: 14px;">Mức Độ
                                            Ưu Tiên</label>
                                    </div>
                                    <div class="card-body p-2" id="bulk_level_group">
                                        <div class="icheck-danger d-block mb-1">
                                            <input type="radio" name="bulk_level" id="bulk_level_1"
                                                value="1">
                                            <label for="bulk_level_1" class="font-weight-bold text-danger">1: Hàng
                                                Gấp, Hàng Thầu</label>
                                        </div>
                                        <div class="icheck-warning d-block mb-1">
                                            <input type="radio" name="bulk_level" id="bulk_level_2"
                                                value="2">
                                            <label for="bulk_level_2" class="font-weight-bold text-warning">2: Hàng
                                                Gấp, Hàng sắp hết số đăng ký</label>
                                        </div>
                                        <div class="icheck-primary d-block mb-1">
                                            <input type="radio" name="bulk_level" id="bulk_level_3"
                                                value="3">
                                            <label for="bulk_level_3" class="font-weight-bold text-primary">3: Hàng SX
                                                dự trù theo KH bán hàng</label>
                                        </div>
                                        <div class="icheck-success d-block m-0">
                                            <input type="radio" name="bulk_level" id="bulk_level_4"
                                                value="4">
                                            <label for="bulk_level_4" class="font-weight-bold text-success">4: Hàng
                                                không cần gấp</label>
                                        </div>
                                    </div>
                                </div>


                                <!-- Các mốc ngày -->
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="font-weight-bold mb-1" style="font-size: 11px;">Ngày có đủ NL
                                            PC</label>
                                        <input type="date" class="form-control form-control-sm"
                                            id="bulk_after_weigth_date">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="font-weight-bold mb-1" style="font-size: 11px;">Ngày có đủ BB
                                            ĐG</label>
                                        <input type="date" class="form-control form-control-sm"
                                            id="bulk_after_parkaging_date">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="font-weight-bold mb-1" style="font-size: 11px;">Ngày được phép
                                            cân</label>
                                        <input type="date" class="form-control form-control-sm"
                                            id="bulk_allow_weight_before_date">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="font-weight-bold mb-1" style="font-size: 11px;">Hạn dùng
                                            NL</label>
                                        <input type="date" class="form-control form-control-sm"
                                            id="bulk_expired_material_date">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="font-weight-bold mb-1" style="font-size: 11px;">Hạn dùng
                                            BB</label>
                                        <input type="date" class="form-control form-control-sm"
                                            id="bulk_expired_packing_date">
                                    </div>
                                </div>

                                <!-- Note -->
                                <div class="form-group mb-2">
                                    <label class="font-weight-bold mb-1">Ghi chú (nếu có)</label>
                                    <textarea class="form-control form-control-sm" id="bulk_note" rows="2" placeholder="Nhập ghi chú chung..."></textarea>
                                </div>
                            </div>

                            <!-- Cột 2: CÔng Thức PC -->
                            <div class="col-md-4">
                                <div id="valWarningContainer" class="alert alert-warning py-1 px-2 mb-2"
                                    style="font-size: 13px; display: none;">
                                    <i class="fas fa-exclamation-triangle"></i> Có chứa nguyên liệu cần báo QA lấy mẫu
                                    thẩm định.
                                </div>
                                <table class="table table-bordered table-striped table-sm" style="font-size: 13px">
                                    <thead>
                                        <tr class="bg-light">
                                            <th>STT</th>
                                            <th>Mã Nguyên Liệu</th>
                                            <th>Tên Nguyên Liệu</th>
                                            <th>Khối Lượng</th>
                                            <th class="text-center">Chọn</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulk_material_recipe_body">
                                        <!-- Render bằng JS -->
                                        <tr>
                                            <td colspan="5" class="text-center">Đang tải...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Cột 3: CÔng Thức ĐG -->
                            <div class="col-md-5">
                                <table class="table table-bordered table-striped table-sm" style="font-size: 13px">
                                    <thead>
                                        <tr class="bg-light">
                                            <th>STT</th>
                                            <th>Mã Bao Bì</th>
                                            <th>Tên Bao Bì</th>
                                            <th>Lượng Bao Bì</th>
                                            <th class="text-center">Chọn</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulk_packaging_recipe_body">
                                        <!-- Render bằng JS -->
                                        <tr>
                                            <td colspan="5" class="text-center">Đang tải...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <hr class="mt-2 mb-3">

                        <!-- Reason -->
                        <div class="form-group row" @if (!$send) style="display: none;" @endif>
                            <div class="col-md-2">
                                <label class="font-weight-bold text-danger"><i
                                        class="fas fa-exclamation-triangle"></i> Lý do cập nhật</label>
                            </div>
                            <div class="col-md-10">
                                <textarea class="form-control form-control-sm" id="bulk_reason" rows="2"
                                    placeholder="Vui lòng nhập lý do thay đổi để lưu lịch sử {{ $send ? '(Bắt buộc)' : '' }}..."
                                    {{ $send ? 'required' : '' }}></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="btn-save-bulk"><i class="fas fa-save"></i>
                        Lưu cập nhật</button>
                </div>
            </div>
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

    <table id="export_hidden_table" style="display:none;">
        <thead>
            <tr>
                <th>STT</th>
                <th>Tình Trạng</th>
                @if ($plan_list_id < 0)
                    <th>Tháng</th>
                @endif
                <th>Mã BTP</th>
                <th>Mã TP</th>
                <th>Tên BTP</th>
                <th>Tên TP</th>
                <th>Cỡ lô</th>
                <th>Số Lô Dự Kiến</th>
                <th>Số Lô Thực Tế</th>
                <th>Số lượng ĐG</th>
                <th>Thị Trường</th>
                <th>Qui Cách</th>
                <th>Ngày dự kiến KCS</th>
                <th>Ưu Tiên</th>
                <th>Lô Thẩm định</th>
                <th>Ngày có đủ NL</th>
                <th>Ngày có đủ BB</th>
                <th>Ngày được phép cân</th>
                <th>Ngày HH NL chính</th>
                <th>Ngày HH BB</th>
                <th>PC trước</th>
                <th>THT trước</th>
                <th>BP trước</th>
                <th>ĐG trước</th>
                <th>Ghi Chú</th>
                <th>Người Tạo</th>
                <th>Ngày Tạo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($datas as $data)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $data->IsHypothesis ? 'Lô Giả Định' : $data->status }}</td>
                    @if ($plan_list_id < 0)
                        <td>{{ $plan_list_id_title[$data->plan_list_id] ?? 'NA' }}</td>
                    @endif
                    <td>{{ $data->intermediate_code }}</td>
                    <td>{{ $data->finished_product_code }}</td>
                    <td>{{ $data->intermediate_product_name }}</td>
                    <td>{{ trim($data->finished_product_name) == trim($data->intermediate_product_name) ? '' : trim($data->finished_product_name) }}
                    </td>
                    <td>{{ $data->batch_qty . ' ' . $data->unit_batch_qty }}</td>
                    <td>{{ $data->batch }}</td>
                    <td>{{ $data->actual_batch }}</td>
                    <td>{{ $data->number_parkaging > 0 ? $data->number_parkaging . ' ' . $data->unit_batch_qty : '' }}
                    </td>
                    <td>{{ $data->market }}</td>
                    <td>{{ $data->specification }}</td>
                    <td>{{ $data->expected_date ? \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') : '' }}
                    </td>
                    <td>{{ $data->level }}</td>
                    <td>{{ $data->is_val ? '1' : '0' }}</td>
                    <td>{{ $data->after_weigth_date ? \Carbon\Carbon::parse($data->after_weigth_date)->format('d/m/Y') : '' }}
                    </td>
                    <td>{{ $data->after_parkaging_date ? \Carbon\Carbon::parse($data->after_parkaging_date)->format('d/m/Y') : '' }}
                    </td>
                    <td>{{ $data->allow_weight_before_date ? \Carbon\Carbon::parse($data->allow_weight_before_date)->format('d/m/Y') : '' }}
                    </td>
                    <td>{{ $data->expired_material_date ? \Carbon\Carbon::parse($data->expired_material_date)->format('d/m/Y') : '' }}
                    </td>
                    <td>{{ $data->expired_packing_date ? \Carbon\Carbon::parse($data->expired_packing_date)->format('d/m/Y') : '' }}
                    </td>
                    <td>{{ $data->preperation_before_date ? \Carbon\Carbon::parse($data->preperation_before_date)->format('d/m/Y') : '' }}
                    </td>
                    <td>{{ $data->blending_before_date ? \Carbon\Carbon::parse($data->blending_before_date)->format('d/m/Y') : '' }}
                    </td>
                    <td>{{ $data->coating_before_date ? \Carbon\Carbon::parse($data->coating_before_date)->format('d/m/Y') : '' }}
                    </td>
                    <td>{{ $data->parkaging_before_date ? \Carbon\Carbon::parse($data->parkaging_before_date)->format('d/m/Y') : '' }}
                    </td>
                    <td>{{ $data->note ?? '' }}</td>
                    <td>{{ $data->prepared_by }}</td>
                    <td>{{ $data->created_at ? \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script>
        function exportPlanToExcel() {
            var loadScript = function(url, callback) {
                var script = document.createElement("script");
                script.type = "text/javascript";
                script.onload = function() {
                    callback();
                };
                script.src = url;
                document.getElementsByTagName("head")[0].appendChild(script);
            };

            var doExport = function() {
                var planName = {!! json_encode(request()->get('name') ?? 'Ke_Hoach') !!};
                var production = {!! json_encode($production ?? '') !!};
                var date = new Date();
                var dateStr = date.getDate().toString().padStart(2, '0') + '-' +
                    (date.getMonth() + 1).toString().padStart(2, '0') + '-' +
                    date.getFullYear();
                var fileName = planName + '_' + production + '_' + dateStr;

                var exportTable = $('#export_hidden_table');

                // Configure export button if not already present
                if (!$.fn.DataTable.isDataTable('#export_hidden_table')) {
                    exportTable.DataTable({
                        paging: false,
                        searching: false,
                        ordering: false,
                        info: false,
                        buttons: [{
                            extend: 'excelHtml5',
                            name: 'excel-export',
                            title: planName,
                            filename: fileName
                        }]
                    });
                }

                exportTable.DataTable().button('excel-export:name').trigger();
            };

            // Dynamically load plugins if they aren't loaded yet
            if (typeof JSZip === 'undefined' || typeof $.fn.dataTable.Buttons === 'undefined') {
                loadScript("{{ asset('dataTable/plugins/jszip/jszip.min.js') }}", function() {
                    loadScript("{{ asset('dataTable/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}",
                        function() {
                            loadScript(
                                "{{ asset('dataTable/plugins/datatables-buttons/js/buttons.html5.min.js') }}",
                                doExport);
                        });
                });
            } else {
                doExport();
            }
        }

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

                modal.find('textarea[name="source_material_name"]').val(button.data(
                    'source_material_name'));
                modal.find('input[name="after_weigth_date"]').val(button.data('after_weigth_date'));
                modal.find('input[name="after_parkaging_date"]').val(button.data('after_parkaging_date'));
                modal.find('textarea[name="note"]').val(button.data('note'));

                modal.find('input[name="batch_qty"]').val(button.data('batch_qty') + " - " + button.data(
                    'unit_batch_qty'));
                modal.find('input[name="specification"]').val(button.data('market') + " - " + button.data(
                    'specification'));
                modal.find('input[name="number_of_unit"]').attr('max', button.data('batch_qty'));
                modal.find('input[name="max_number_of_unit"]').val(button.data('batch_qty'));
                modal.find('input[name="number_of_unit"]').val(button.data('number_parkaging'));
                modal.find('input[name="expected_date"]').val(button.data('expected_date'));
                modal.find('input[name="level"][value="' + button.data('level') + '"]').prop('checked',
                    true);


                if (button.data('is_val') == 1 && button.data('code_val').split('_')[1] == "1") {
                    modal.find('input[name="batchNo1"]').val(button.data('batch'));
                    modal.find('#update_checkbox1').prop('checked', true).val(true);
                    modal.find('input[name="code_val_first"]').val(button.data('code_val').split('_')[0] +
                        "_1");
                } else if (button.data('is_val') == 1 && button.data('code_val').split('_')[1] == "2") {
                    modal.find('input[name="batchNo1"]').val(button.data('batch'));
                    modal.find('#update_checkbox2').prop('checked', true).val(true);
                    modal.find('input[name="code_val_first"]').val(button.data('code_val').split('_')[0] +
                        "_1");
                } else if (button.data('is_val') == 1 && button.data('code_val').split('_')[1] == "3") {
                    modal.find('input[name="batchNo1"]').val(button.data('batch'));
                    modal.find('#update_checkbox3').prop('checked', true).val(true);
                    modal.find('input[name="code_val_first"]').val(button.data('code_val').split('_')[0] +
                        "_1");
                }

                const create_soure_modal = $('#create_soure_modal');
                create_soure_modal.find('input[name="intermediate_code"]').val(button.data(
                    'intermediate_code'));
                create_soure_modal.find('input[name="product_name"]').val(button.data('name'));
                create_soure_modal.find('input[name="mode"]').val("update");
            });

            $('.btn-splitting').click(function() {
                const button = $(this);
                const targetModal = button.data('target');

                if (targetModal == "#splittingUpdateModal") {
                    const modal = $(targetModal);
                    // Gán dữ liệu vào input
                    modal.find('input[name="id"]').val(button.data('id'));
                    modal.find('input[name="name"]').val(button.data('name'));
                    modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
                    modal.find('input[name="finished_product_code"]').val(button.data(
                        'finished_product_code'));
                    modal.find('input[name="batch"]').val(button.data('batch'));
                    modal.find('input[name="material_source_id"]').val(button.data('material_source_id'));

                    modal.find('textarea[name="source_material_name"]').val(button.data(
                        'source_material_name'));
                    modal.find('input[name="after_weigth_date"]').val(button.data('after_weigth_date'));
                    //modal.find('input[name="before_weigth_date"]').val(button.data('before_weigth_date'));
                    modal.find('input[name="after_parkaging_date"]').val(button.data(
                        'after_parkaging_date'));
                    //modal.find('input[name="before_parkaging_date"]').val(button.data('before_parkaging_date'));
                    modal.find('textarea[name="note"]').val(button.data('note'));

                    modal.find('input[name="batch_qty"]').val(button.data('batch_qty') + " - " + button
                        .data('unit_batch_qty'));
                    modal.find('input[name="specification"]').val(button.data('market') + " - " + button
                        .data('specification'));
                    modal.find('input[name="number_of_unit"]').attr('max', button.data('batch_qty'));
                    modal.find('input[name="max_number_of_unit"]').val(button.data('batch_qty'));
                    modal.find('input[name="number_of_unit"]').val(button.data('number_parkaging'));
                    modal.find('input[name="expected_date"]').val(button.data('expected_date'));
                    modal.find('input[name="is_val"]').prop('checked', button.data('is_val')).val(button
                        .data('is_val'));


                    modal.find('input[name="level"][value="' + button.data('level') + '"]').prop('checked',
                        true);

                    // modal.find('input[name="product_caterogy_id"]').val(button.data('product_caterogy_id'));
                    // modal.find('input[name="plan_list_id"]').val(button.data('plan_list_id'));
                    // modal.find('input[name="IsHypothesis"]').val(button.data('IsHypothesis'));   

                } else {
                    const modal_splitting = $('#splittingModal');
                    modal_splitting.find('input[name="id"]').val(button.data('id'));
                    modal_splitting.find('input[name="batch"]').val(button.data('batch'));
                    modal_splitting.find('textarea[name="source_material_name"]').val(button.data(
                        'source_material_name'));
                    modal_splitting.find('input[name="number_of_unit"]').val(button.data(
                        'number_parkaging'));
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
                                  <td class="${index === 0 ? 'text-success' : 'text-danger'}"> 
                                      <div>${item.intermediate_code ?? ''}</div>
                                      <div>${item.finished_product_code ?? ''}</div>
                                  </td>

                                  <td>${item.name ?? ''} (${item.batch_qty ?? ''} ${item.unit_batch_qty ?? ''})</td>
                                  
                                  <td>
                                      <div>${item.batch ?? ''}</div>
                                      <div>${item.actual_batch ?? ''}</div>
                                      <div>${item.number_parkaging ?? ''} ${item.percent_parkaging ? '('+item.percent_parkaging+'%)' : ''}</div>
                                  </td>

                                  <td>
                                      <div>${item.market ?? ''}</div>
                                      <div>${item.specification ?? ''}</div>
                                  </td>

                                  <td>
                                      <div>${item.expected_date ? moment(item.expected_date).format('DD/MM/YYYY') : ''}</div>
                                  </td>

                                  <td style="text-align: center; vertical-align: middle;">
                                      <span style="display: inline-block; padding: 6px 10px; width: 50px; border-radius: 40px; ${colors[item.level] ?? ''}">
                                          <b>${item.level ?? ''}</b>
                                      </span>
                                  </td>

                                  <td class="text-center align-middle">
                                      ${item.is_val ? '<i class="fas fa-check-circle text-primary fs-4"></i>' : ''}
                                      ${item.vts && item.vts.length > 0 ? item.vts.map(vt => `
                                                                                  <div class="mt-1 text-left">
                                                                                      <span class="badge badge-warning" style="white-space: normal; text-align: left; line-height: 1.4; border: 1px solid #ffc107;">
                                                                                          <i class="fas fa-exclamation-triangle"></i> TĐNL: ${vt.MaterialName}
                                                                                          ${vt.purpose ? `<br><small>${vt.purpose}</small>` : ''}
                                                                                      </span>
                                                                                  </div>
                                                                              `).join('') : ''}
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

            $(document).on('focus', '.updateInput', function() {
                $(this).data('old-value', $(this).val());
            });

            $(document).on('blur', '#data_table_plan_master .updateInput', function() {
                let currentInput = $(this);
                let id = currentInput.data('id');
                let name = currentInput.attr('name');
                let updateValue = currentInput.val();
                let oldValue = currentInput.data('old-value');

                if (updateValue === oldValue) return;

                if (!id || id == '') {
                    Swal.fire({
                        title: 'Cảnh Báo!',
                        text: 'id Không xác định',
                        icon: 'warning',
                        timer: 1000,
                        showConfirmButton: false
                    });
                    currentInput.val('');
                    return;
                }

                if (name == "level") {
                    const pattern = /^[1-9]\d*$/;
                    if (updateValue && !pattern.test(updateValue)) {
                        Swal.fire({
                            title: 'Lỗi định dạng!',
                            text: 'Bậc ưu tiên phải là số nguyên dương',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        currentInput.focus();
                        currentInput.css('border', '1px solid red');
                        return;
                    } else {
                        currentInput.css('border', '');
                    }
                }

                sendUpdateAjax(id, name, updateValue, oldValue, currentInput);
            });

            function sendUpdateAjax(id, name, updateValue, oldValue, inputEl) {
                $.ajax({
                    url: "{{ route('pages.plan.production.updateInput') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        name: name,
                        updateValue: updateValue,
                        oldValue: oldValue
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000
                                })
                                .fire({
                                    icon: 'success',
                                    title: 'Cập nhật thành công'
                                });
                        }
                    },
                    error: function(xhr) {
                        Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            })
                            .fire({
                                icon: 'error',
                                title: 'Cập nhật thất bại'
                            });
                    }
                });
            }

            $(document).on('change', '#data_table_plan_master .step-checkbox', function() {


                let id = $(this).data('id');
                let name = $(this).attr('name');
                let updateValue = $(this).val();
                let oldValue = $(this).data('old-value');

                if (updateValue === oldValue) return;

                if (!id || id == '') {

                    Swal.fire({
                        title: 'Cảnh Báo!',
                        text: 'id Không xác định',
                        icon: 'warning',
                        timer: 1000, // tự đóng sau 2 giây
                        showConfirmButton: false
                    });
                    $(this).val('');
                    return
                }


                $.ajax({
                    url: "{{ route('pages.plan.production.updateInput') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        name: name,
                        updateValue: updateValue,
                        oldValue: oldValue
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000
                                })
                                .fire({
                                    icon: 'success',
                                    title: 'Cập nhật thành công'
                                });
                        }
                    },
                    error: function(xhr) {
                        Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            })
                            .fire({
                                icon: 'error',
                                title: 'Cập nhật thất bại'
                            });
                    }
                });
            });

            $(document).on('click', '.btn-selected-all', function() {
                let btn = $(this);
                let id = btn.data('plan_list_id');
                let isActive = btn.data('active') == 1;

                // Toggle value
                let updateValue = isActive ? 0 : 1;

                // Update lại trạng thái trong button


                // AJAX update
                $.ajax({
                    url: "{{ route('pages.plan.production.updateInput') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        name: 'selected_all', // gửi cố định field name
                        updateValue: updateValue
                    },
                    success: function(res) {

                        const isActive = updateValue == 1;
                        btn.data('active', updateValue);

                        const icon = btn.find('i');

                        // Đổi màu nút
                        btn.toggleClass('btn-danger', isActive);
                        btn.toggleClass('btn-primary', !isActive);

                        // Đổi icon
                        icon.toggleClass('fa-check', !isActive);
                        icon.toggleClass('fa-times-circle', isActive);

                        // Cập nhật checkbox
                        $('.step-checkbox').prop('checked', isActive);
                        updateBulkEditButtonState();
                    }

                });

            });

            // --- Javascript cho Sửa Hàng Loạt (Bulk Edit) ---
            function updateBulkEditButtonState() {
                let checkedCount = $('.step-checkbox:checked').length;
                $('#selected-count').text(checkedCount);
                $('#selected-count-deactive').text(checkedCount);
                $('#bulk-selected-count').text(checkedCount);
                if (checkedCount > 0) {
                    $('.btn-bulk-edit').prop('disabled', false);
                    $('.btn-bulk-deactive').prop('disabled', false);
                } else {
                    $('.btn-bulk-edit').prop('disabled', true);
                    $('.btn-bulk-deactive').prop('disabled', true);
                }
            }

            // Gọi lúc trang tải xong để thiết lập trạng thái ban đầu
            updateBulkEditButtonState();

            // Cập nhật đếm khi click từng checkbox (dành cho logic cũ nếu cần)
            $(document).on('change', '.step-checkbox', function() {
                updateBulkEditButtonState();
            });

            // Click vào dòng để chọn (Chỉ cho phép chọn cùng Mã Thành Phẩm)
            $(document).on('click', '#data_table_plan_master tbody tr', function(e) {
                // Bỏ qua nếu click vào các thẻ input, button, a, select, textarea
                if ($(e.target).is('input, button, a, select, textarea') || $(e.target).closest(
                        'button, a, select').length > 0) {
                    return;
                }

                let currentFpCode = $(this).data('fp-code');
                let isAlreadySelected = $(this).hasClass('row-selected');

                // Lấy các dòng đang được chọn
                let selectedRows = $('#data_table_plan_master tbody tr.row-selected');

                if (selectedRows.length > 0) {
                    let firstSelectedFpCode = selectedRows.first().data('fp-code');

                    if (currentFpCode !== firstSelectedFpCode) {
                        // Khác mã thành phẩm -> Bỏ chọn tất cả dòng cũ
                        selectedRows.removeClass('row-selected table-success');
                        selectedRows.find('.step-checkbox').prop('checked', false);
                    }
                }

                // Toggle dòng hiện tại
                if (isAlreadySelected) {
                    $(this).removeClass('row-selected table-success');
                    $(this).find('.step-checkbox').prop('checked', false);
                } else {
                    $(this).addClass('row-selected table-success');
                    $(this).find('.step-checkbox').prop('checked', true);
                }

                updateBulkEditButtonState();
            });

            // Xoá logic checkbox cũ vì đã bỏ checkbox

            // Chỉ cho phép tích 1 trong 3 checkbox lô thẩm định
            $(document).on('change', '.bulk-val-checkbox', function() {
                if ($(this).is(':checked')) {
                    $('.bulk-val-checkbox').not(this).prop('checked', false);
                }
            });

            $(document).on('click', '.btn-bulk-deactive', function() {
                var selectedIds = [];
                $('.step-checkbox:checked').each(function() {
                    selectedIds.push($(this).data('id'));
                });
                if (selectedIds.length === 0) {
                    Swal.fire('Vui lòng chọn ít nhất 1 lô!', '', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Xác nhận hủy/vô hiệu?',
                    text: "Bạn có chắc chắn muốn hủy/vô hiệu " + selectedIds.length +
                        " lô đã chọn?",
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
                        $.ajax({
                            url: "{{ route('pages.plan.production.bulk_deactive') }}",
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                ids: selectedIds,
                                deactive_reason: result.value
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Thành công', res.message, 'success')
                                        .then(() => {
                                            location.reload();
                                        });
                                } else {
                                    Swal.fire('Lỗi', res.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Lỗi', 'Có lỗi xảy ra khi gọi server',
                                    'error');
                            }
                        });
                    }
                });
            });

            // Khi click nút Sửa Hàng Loạt
            $(document).on('click', '.btn-bulk-edit', function() {
                let checkedCheckboxes = $('.step-checkbox:checked');
                let checkedCount = checkedCheckboxes.length;
                if (checkedCount === 0) return;

                $('#bulk-selected-count').text(checkedCount);
                $('#bulk_reason').val('');

                // Lấy dòng đầu tiên
                let firstRow = checkedCheckboxes.first().closest('tr');

                // Điền thông tin sản phẩm
                $('#bulk_info_intermediate_code').val(firstRow.data('intermediate-code') || '');
                $('#bulk_info_fp_code').val(firstRow.data('fp-code') || '');
                const market = firstRow.data('market') || '';
                const spec = firstRow.data('specification') || '';
                $('#bulk_info_spec_market').val((market ? market + ' - ' : '') + spec);
                $('#bulk_info_product_name').val(firstRow.data('name') || '');
                const qty = firstRow.data('batch-qty') || '';
                const unit = firstRow.data('unit-batch-qty') || '';
                $('#bulk_info_batch_qty').val((qty ? qty + ' - ' : '') + unit);

                // Điền sẵn dữ liệu từ firstRow
                $('#bulk_batch').val(firstRow.data('batch') || '');
                $('#bulk_expected_date').val(firstRow.data('expected-date') || '');

                const level = firstRow.data('level');
                $('input[name="bulk_level"]').prop('checked', false);
                if (level) {
                    $('#bulk_level_' + level).prop('checked', true);
                }


                $('#bulk_after_weigth_date').val(firstRow.data('after-weigth-date') || '');
                $('#bulk_after_parkaging_date').val(firstRow.data('after-parkaging-date') || '');
                $('#bulk_allow_weight_before_date').val(firstRow.data('allow-weight-before-date') || '');
                $('#bulk_expired_material_date').val(firstRow.data('expired-material-date') || '');
                $('#bulk_expired_packing_date').val(firstRow.data('expired-packing-date') || '');
                $('#bulk_note').val(firstRow.data('note') || '');

                // --- Lấy dữ liệu công thức của dòng đầu tiên ---
                let plan_master_id = checkedCheckboxes.first().data('id');
                let ic_code = firstRow.data('intermediate-code');

                $('#bulk_material_recipe_body').html(
                    '<tr><td colspan="4" class="text-center">Đang tải...</td></tr>');
                $('#bulk_packaging_recipe_body').html(
                    '<tr><td colspan="4" class="text-center">Đang tải...</td></tr>');
                $('#valWarningContainer').hide();

                // Lấy công thức nguyên liệu
                $.ajax({
                    url: "{{ route('pages.plan.production.recipe_show_update') }}",
                    type: 'post',
                    data: {
                        plan_master_id: plan_master_id,
                        material_packaging_type: 0,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        const tbody = $('#bulk_material_recipe_body');
                        tbody.empty();
                        if (res.length === 0) {
                            tbody.append(
                                `<tr><td colspan="4" class="text-center">Không có công thức</td></tr>`
                            );
                        } else {
                            res.forEach((item, index) => {
                                tbody.append(`
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.material_packaging_code ?? ''}</td>
                                        <td>${item.MaterialName ?? ''}</td>
                                        <td>
                                            ${
                                                item.qty != null
                                                ? Number(item.qty).toLocaleString(undefined, {
                                                    minimumFractionDigits: 0,
                                                    maximumFractionDigits: 3
                                                })
                                                : ''
                                            } ${item.unit_bom ?? ''}
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="icheck-primary d-inline">
                                                <input type="checkbox" id="bulk_mat_${index}" class="bulk-material-checkbox" data-code="${item.material_packaging_code}" data-type="material" ${item.active == 1 ? 'checked' : ''}>
                                                <label for="bulk_mat_${index}"></label>
                                            </div>
                                        </td>
                                    </tr>
                                `);
                            });
                        }
                    }
                });

                // Lấy công thức bao bì
                $.ajax({
                    url: "{{ route('pages.plan.production.recipe_show_update') }}",
                    type: 'post',
                    data: {
                        plan_master_id: plan_master_id,
                        material_packaging_type: 1,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        const tbody = $('#bulk_packaging_recipe_body');
                        tbody.empty();
                        if (res.length === 0) {
                            tbody.append(
                                `<tr><td colspan="4" class="text-center">Không có công thức</td></tr>`
                            );
                        } else {
                            res.forEach((item, index) => {
                                tbody.append(`
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.material_packaging_code ?? ''}</td>
                                        <td>${item.MaterialName ?? ''}</td>
                                        <td>
                                            ${
                                                item.qty != null
                                                ? Number(item.qty).toLocaleString(undefined, {
                                                    minimumFractionDigits: 0,
                                                    maximumFractionDigits: 3
                                                })
                                                : ''
                                            } ${item.unit_bom ?? ''}
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="icheck-primary d-inline">
                                                <input type="checkbox" id="bulk_pkg_${index}" class="bulk-packaging-checkbox" data-code="${item.material_packaging_code}" data-type="packaging" ${item.active == 1 ? 'checked' : ''}>
                                                <label for="bulk_pkg_${index}"></label>
                                            </div>
                                        </td>
                                    </tr>
                                `);
                            });
                        }
                    }
                });

                // Kiểm tra thẩm định
                $.ajax({
                    url: "{{ route('pages.plan.validation_tracking.check_validation') }}",
                    type: "GET",
                    data: {
                        intermediate_code: ic_code,
                        plan_master_id: plan_master_id
                    },
                    success: function(res) {
                        if (res && res.length > 0) {
                            $('#valWarningContainer').show();
                        } else {
                            $('#valWarningContainer').hide();
                        }
                    }
                });

                $('#bulkEditModal').modal('show');
            });

            // Khi nhấn Lưu trong modal Sửa Hàng Loạt
            $(document).on('click', '#btn-save-bulk', function() {
                const checkedIds = [];
                $('.step-checkbox:checked').each(function() {
                    const id = $(this).data('id');
                    if (id) checkedIds.push(id);
                });

                if (checkedIds.length === 0) {
                    Swal.fire('Cảnh báo', 'Không có dòng nào được chọn!', 'warning');
                    return;
                }

                const isSent = {{ $send ? 'true' : 'false' }};
                const reason = $('#bulk_reason').val().trim();
                if (isSent && !reason) {
                    Swal.fire('Thiếu thông tin', 'Vui lòng nhập lý do thay đổi để lưu lịch sử!', 'warning');
                    return;
                }

                let fields = {};
                fields['batch'] = $('#bulk_batch').val();
                fields['format_batch_no'] = $('#bulk_format_batch_no').is(':checked') ? 'on' : 'off';
                fields['expected_date'] = $('#bulk_expected_date').val();
                fields['level'] = $('input[name="bulk_level"]:checked').val() || null;


                fields['after_weigth_date'] = $('#bulk_after_weigth_date').val();
                fields['after_parkaging_date'] = $('#bulk_after_parkaging_date').val();
                fields['allow_weight_before_date'] = $('#bulk_allow_weight_before_date').val();
                fields['expired_material_date'] = $('#bulk_expired_material_date').val();
                fields['expired_packing_date'] = $('#bulk_expired_packing_date').val();
                fields['note'] = $('#bulk_note').val();

                let recipe_status = [];
                $('.bulk-material-checkbox').each(function() {
                    recipe_status.push({
                        code: $(this).data('code'),
                        active: $(this).is(':checked') ? 1 : 0
                    });
                });
                $('.bulk-packaging-checkbox').each(function() {
                    recipe_status.push({
                        code: $(this).data('code'),
                        active: $(this).is(':checked') ? 1 : 0
                    });
                });
                fields['recipe_status'] = recipe_status;

                Swal.fire({
                    title: 'Xác nhận lưu?',
                    text: `Bạn chuẩn bị cập nhật ${checkedIds.length} dòng đã chọn. Hành động này không thể hoàn tác!`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('pages.plan.production.bulk_update') }}",
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                _token: '{{ csrf_token() }}',
                                ids: checkedIds,
                                fields: fields,
                                reason: reason
                            },
                            success: function(res) {
                                if (res.success) {
                                    $('#bulkEditModal').modal('hide');
                                    Swal.fire({
                                        title: 'Thành công!',
                                        text: res.message,
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire('Lỗi', res.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                const msg = xhr.responseJSON ? xhr.responseJSON
                                    .message : 'Lỗi kết nối server';
                                Swal.fire('Lỗi', msg, 'error');
                            }
                        });
                    }
                });
            });

            if ($("input#bulk_format_batch_no").length) {
                $("input#bulk_format_batch_no").bootstrapSwitch({
                    onText: 'AAMMYY',
                    offText: 'YWWAA',
                    onColor: 'success',
                    offColor: 'danger'
                });
            }

            var table = $('#data_table_plan_master').DataTable();

            $('#statusFilter').on('change', function() {
                var value = $(this).val();

                table.column(1).search(value ? value : '', true, false).draw();
            });
        });
    </script>
