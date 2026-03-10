

<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<style>
    .step-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #007bff;
    }
    .updateInput {
        width: 100%;
        border: none;
        outline: none;
        background: transparent;
        text-align: center;
    }
    .updateInput:focus {
        border: 1px solid #007bff;
        background: #fff;
    }

    /* CUỘN TABLE */
    .table-scroll-wrapper {
        max-height: 86vh;
        overflow-y: auto;
        overflow-x: auto;
    }
    #data_table_raw_material {
        table-layout: fixed;
        width: 100%;
    }

    table th, table td {
        white-space: normal;     /* cho phép xuống dòng */
        word-break: break-word;  /* tự bẻ chữ dài */
        vertical-align: middle;
    }

    #data_table_raw_material {
        font-size: 14px; /* từ 16 → 14 là vừa đẹp */
    }

    #data_table_raw_material th,
    #data_table_raw_material td {
        padding: 6px 6px;
    }

    /* GRN hình tròn */
    .status-circle{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:2px solid #007bff;
        border-radius:50%;
        width:40px;
        height:40px;
        font-size:11px;
        font-weight:600;
        color:#007bff;
    }

    /* QC bo góc */
    .status-pill{
        display:inline-block;
        padding:3px 10px;
        border-radius:8px;
        border:1px solid #999;
        font-size:11px;
    }

    /* Approved */
    .status-approved{
        background:#28a745;
        color:white;
        border-color:#28a745;
    }


</style>

<div class="content-wrapper">
    <div class="card" style="min-height:100vh">
        <div class="card-body mt-5">

            {{-- SEARCH --}}
            <div class="row mb-2">
                <div class="col-md-9">
                    <button type="button" class="btn btn-success"
                        onclick="window.location.href='{{ url()->previous() }}'">
                        <i class="fas fa-arrow-left"></i> Trở Về
                    </button>
                </div>
                <div class="col-md-3">
                    <input id="globalSearch"
                           class="form-control"
                           placeholder="🔍 Tìm kiếm nguyên liệu / sản phẩm / lô">
                </div>
            </div>

            <div class="table-scroll-wrapper mt-1">
                <table id="data_table_raw_material"
                       class="table table-bordered table-striped"
                       style="font-size:16px;width:100%">

                    <thead style="position:sticky;top:0;background:#fff;z-index:10">
                        <tr>
                            <th rowspan="2" style="width: 40px">STT</th>
                            <th rowspan="2" >Mã Nguyên Liệu/Bao Bì</th>
                            <th rowspan="2" style="width: 10%">Tên Nguyên Liệu/Bao Bì</th>                      
                            <th rowspan="2" style="width: 4%">Số Lô Dùng Cho</th>
                            <th rowspan="2" style="width: 15%">Lượng Dùng Chi Tiết</th>
                            <th rowspan="2" style="width: 4%">Lượng Tổng Cần Dùng</th>
                            <th rowspan="2">Tổng Tồn MMS</th>
                            <th rowspan="2">Lượng Thiếu Hụt (Nếu có)</th>
                            <th colspan="9" class="text-center" >Chi Tiết Tồn Kho Hiện Hành</th>
                        </tr>
                        <tr>
                            <th>Tồn</th>
                            <th>Nhập</th>
                            <th>Số GRN</th>
                            <th>Số Lô NB/ Số Lô NSX</th>
                            <th>HSD / Retest</th>
                            <th>Nhà SX</th>
                            <th style="width: 5%">CoA</th>
                            <th style="width: 1%">
                                {{ 'GRN Status' }}
                                <br>
                                {{ 'Approve Status' }}
                            </th>
                            {{-- <th style="width: 1%"></th> --}}
                            <th style="width: 1%">Kho</th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach ($datas as $data)

                        @php
                            $stocks = $data->stock ?? collect();
                            $rowspan = max($stocks->count(), 1);
                            $groupId = 'grp_'.$loop->iteration;
                        @endphp

                        {{-- ROW CHÍNH --}}
                        <tr data-group="{{ $groupId }}">
                            <td rowspan="{{ $rowspan }}">{{ $loop->iteration }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->material_packaging_code }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->MaterialName }}</td>
                            <td rowspan="{{ $rowspan }}">
                                <button type="button" class="btn btn-primary btn-batch-datial"
                                        data-plan_master_ids="{{ $data->plan_master_ids }}"
                                        data-toggle="modal" 
                                        data-target="#batchDetialModal"
                                        >
                                        {{ $data->NumberOfBatch}} Lô
                                </button>
                            </td>
                            <td rowspan="{{ $rowspan }}"> {!!  $data->qty_list  !!} </td>
                            <td rowspan="{{ $rowspan }}">{{ round($data->total_qty,5) }} {{ $data->unit_bom }}</td>

                            <td rowspan="{{ $rowspan }}" class = "{{ $data->totalQty < $data->total_qty?'text-red':'a' }}">{{ round($data->totalQty,5) }} {{ $data->unit_bom }}</td>
                            
                            @php
                                $shortageQuantity =  $data->total_qty -  $data->totalQty;
                            @endphp
                            <td rowspan="{{ $rowspan }} ">{{ $shortageQuantity > 0 ? round($shortageQuantity,5) . " " . $data->unit_bom : '-' }} </td>
                            
                            @if ($stocks->count())
                               @php 
                                    $s = $stocks->first(); 
                               @endphp
                   
                                <td>{{ round($s->Total_Qty,4) }} {{ $s->MatUOM }}</td>
                                <td>{{ round($s->ReceiptQuantity,4) }} {{ $s->MatUOM }}</td>
                                <td>{{ $s->GRNNO}}</td>
                                <td>{{ $s->ARNO }}
                                    {{ $s->Mfgbatchno}}
                                </td>
                                <td>
                                    {{ $s->Expirydate ? \Carbon\Carbon::parse($s->Expirydate)->format('d/m/Y') : '' }}<br>
                                    {{ $s->Retestdate ? \Carbon\Carbon::parse($s->Retestdate)->format('d/m/Y') : '' }}
                                </td>
                                <td>{{ $s->Mfg }}</td>

                                <td>{{ $s->coa_list }} </td>

                                <td class="text-center">
                                    <span class="status-circle">
                                        {{ $s->GRNSts }}
                                    </span>
                                    <br>
                                    <span class="status-pill 
                                        {{ strtolower(trim($s->QCSTS)) == 'approved' ? 'status-approved' : '' }}">
                                        {{ $s->QCSTS }}
                                    </span>
                                </td>
                            
                                <td>{{ $s->warehouse_list }} </td>
                            @else
                                <td colspan="9" class="text-center text-danger fw-bold">
                                    Không có tồn kho
                                </td>
                            @endif
                        </tr>

                        {{-- ROW STOCK --}}
                        @foreach ($stocks->skip(1) as $s)

                            <tr data-group="{{ $groupId }}">
                                <td>{{ round($s->Total_Qty,4) }} {{ $s->MatUOM }}</td>
                                <td>{{ round($s->ReceiptQuantity,4) }} {{ $s->MatUOM }}</td>
                                
                                <td>{{ $s->Mfgbatchno }}</td>
                                <td>{{ $s->ARNO }}</td>
                                <td>
                                    {{ $s->Expirydate ? \Carbon\Carbon::parse($s->Expirydate)->format('d/m/Y') : '' }}<br>
                                    {{ $s->Retestdate ? \Carbon\Carbon::parse($s->Retestdate)->format('d/m/Y') : '' }}
                                </td>
                                <td>{{ $s->Mfg }}</td>

                                <td>{{ $s->coa_list }} </td>
                        
                                <td class="text-center">
                                    <span class="status-circle">
                                        {{ $s->GRNSts }}
                                    </span>
                                    <br>
                                    <span class="status-pill 
                                        {{ strtolower(trim($s->QCSTS)) == 'approved' ? 'status-approved' : '' }}">
                                        {{ $s->QCSTS }}
                                    </span>
                                </td>

                                <td>{{ $s->warehouse_list }} </td>
                            </tr>
                        @endforeach


                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class=" fw-bold text-muted">
                Hiển thị: <span id="visibleCount"></span> / <span id="totalCount"></span>
            </div>

        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

<script>


$(document).ready(function () {
    let authUpdate = '';
    /* GROUP ROWS */
    let groups = {};

    $('#data_table_raw_material tbody tr').each(function () {
        let g = $(this).data('group');
        if (!groups[g]) groups[g] = [];
        groups[g].push(this);
    });

    const totalGroups = Object.keys(groups).length;
    $('#totalCount').text(totalGroups);

    /* SEARCH + COUNT */
    $('#globalSearch').on('keyup', function () {
        let keyword = $(this).val().toLowerCase();
        let visibleGroups = 0;

        $.each(groups, function (_, rows) {
            let match = false;

            rows.forEach(r => {
                if ($(r).text().toLowerCase().includes(keyword)) {
                    match = true;
                }
            });

            rows.forEach(r => $(r).toggle(match));

            if (match) visibleGroups++;
        });

        $('#visibleCount').text(visibleGroups);
    });

    /* INIT COUNT */
    $('#visibleCount').text(totalGroups);


    $('.btn-batch-datial').on('click', function () {

        const btn = $(this);
        const plan_master_ids_text = btn.data('plan_master_ids');

        const plan_master_ids = plan_master_ids_text
            ? String(plan_master_ids_text).split('_')
            : [];

        if (!plan_master_ids.length) return;

        $.ajax({
            url: "{{ route('pages.plan.production.open_bacth_detail') }}",
            type: 'post',
            data: {
                plan_master_ids: plan_master_ids,
                _token: "{{ csrf_token() }}"
            },

            success: function (res) {

                batchTable.clear(); // 🔥 Xóa dữ liệu cũ

                if (!res || !res.datas || res.datas.length === 0) {
                    batchTable.row.add([
                        '',
                        '',
                        '',
                        'Không có dữ liệu',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        ''
                    ]).draw();
                    return;
                }

                res.datas.forEach((data, index) => {

                    let statusColors = {
                        "Chưa làm": "background-color: green; color: white;",
                        "Đã Cân": "background-color: #e3f2fd; color: #0d47a1;",
                        "Đã Pha chế": "background-color: #bbdefb; color: #0d47a1;",
                        "Đã THT": "background-color: #90caf9; color: #0d47a1;",
                        "Đã định hình": "background-color: #64b5f6; color: white;",
                        "Đã Bao phim": "background-color: #1e88e5; color: white;",
                        "Hoàn Tất ĐG": "background-color: #0d47a1; color: white;",
                        "Hủy": "background-color: red; color: white;"
                    };

                    let levelColors = {
                        1: "background-color:#f44336;color:white;",
                        2: "background-color:#ff9800;color:white;",
                        3: "background-color:blue;color:white;",
                        4: "background-color:#4caf50;color:white;"
                    };

                    let cancelClass = data.cancel ? 'text-danger' : 'text-success';

                    let finishedName =
                        (data.finished_product_name?.trim() !== data.intermediate_product_name?.trim())
                            ? data.finished_product_name ?? ''
                            : '';

                    let isValText = '';
                    if (data.is_val && data.code_val) {
                        let arr = data.code_val.split('_');
                        isValText = `Lô thứ ${arr[1] ?? ''}`;
                    }

                    batchTable.row.add([
                        `
                        <div>${index + 1}</div>
                        ${data.userGroup === "Admin" ? `<div>${data.id}</div>` : ''}
                        `,
                        `
                        <div class="text-center"
                            style="display:inline-block;padding:6px 10px;width:100px;border-radius:10px;
                            ${statusColors[data.status] ?? ''}">
                            ${data.status ?? ''}
                        </div>
                        `,
                        `
                        <div class="${cancelClass}">
                            <div>${data.intermediate_code ?? ''}</div>
                            <div>${data.finished_product_code ?? ''}</div>
                        </div>
                        `,
                        `
                        <div>${data.intermediate_product_name ?? ''}</div>
                        <div>${finishedName}</div>
                        <div>(${data.batch_qty ?? ''} ${data.unit_batch_qty ?? ''})</div>
                        `,
                        `<div class="text-center">${data.batch ?? ''}</div>`,
                        `
                        <div>${data.market ?? ''}</div>
                        <div>${data.specification ?? ''}</div>
                        `,
                        `
                        <input ${authUpdate}
                                style="width:auto;"
                                type="date"
                                class="updateInput"
                                name="expected_date"
                                value="${formatDateForInput(data.expected_date)}"
                                data-id="${data.id}">
                       `,
                        `
                        <span style="display:inline-block;padding:6px 10px;width:50px;border-radius:40px;
                            ${levelColors[data.level] ?? ''}">
                            <input type="text"
                                class="updateInput"
                                name="level"
                                value="${data.level ?? ''}"
                                data-id="${data.id}">
                        </span>
                        `,
                        `
                        <input type="checkbox"
                            ${data.is_val ? 'checked' : ''}
                            disabled>
                        <br>
                        ${isValText}
                        `,
                        `
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span>(1):</span>
                            <input ${authUpdate}
                                style="width:auto;"
                                type="date"
                                class="updateInput"
                                name="after_weigth_date"
                                value="${formatDateForInput(data.after_weigth_date)}"
                                data-id="${data.id}">
                        </div>

                        <div style="display:flex; align-items:center; gap:6px;">
                            <span>(2):</span>
                            <input ${authUpdate}
                                style="width:auto;"
                                type="date"
                                class="updateInput"
                                name="after_parkaging_date"
                                value="${formatDateForInput(data.after_parkaging_date)}"
                                data-id="${data.id}">
                        </div>

                        <div style="display:flex; align-items:center; gap:6px;">
                            <span>(3):</span>
                            <input ${authUpdate}
                                style="width:auto;"
                                type="date"
                                class="updateInput"
                                name="allow_weight_before_date"
                                value="${formatDateForInput(data.allow_weight_before_date)}"
                                data-id="${data.id}">
                        </div>

                        <div style="display:flex; align-items:center; gap:6px;">
                            <span>(4):</span>
                            <input ${authUpdate}
                                style="width:auto;"
                                type="date"
                                class="updateInput"
                                name="expired_material_date"
                                value="${formatDateForInput(data.expired_material_date)}"
                                data-id="${data.id}">
                        </div>

                        <div style="display:flex; align-items:center; gap:6px;">
                            <span>(5):</span>
                            <input ${authUpdate}
                                style="width:auto;"
                                type="date"
                                class="updateInput"
                                name="expired_packing_date"
                                value="${formatDateForInput(data.expired_packing_date)}"
                                data-id="${data.id}">
                        </div>
                        `
                        ,
                        `
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span>(1):</span>
                            <input ${authUpdate}
                                style="width:auto;"
                                type="date"
                                class="updateInput"
                                name="preperation_before_date"
                                value="${formatDateForInput(data.preperation_before_date)}"
                                data-id="${data.id}">
                        </div>

                        <div style="display:flex; align-items:center; gap:6px;">
                            <span>(2):</span>
                            <input ${authUpdate}
                                style="width:auto;"
                                type="date"
                                class="updateInput"
                                name="blending_before_date"
                                value="${formatDateForInput(data.blending_before_date)}"
                                data-id="${data.id}">
                        </div>

                        <div style="display:flex; align-items:center; gap:6px;">
                            <span>(3):</span>
                            <input ${authUpdate}
                                style="width:auto;"
                                type="date"
                                class="updateInput"
                                name="coating_before_date"
                                value="${formatDateForInput(data.coating_before_date)}"
                                data-id="${data.id}">
                        </div>

                        <div style="display:flex; align-items:center; gap:6px;">
                            <span>(4):</span>
                            <input ${authUpdate}
                                style="width:auto;"
                                type="date"
                                class="updateInput"
                                name="parkaging_before_date"
                                value="${formatDateForInput(data.parkaging_before_date)}"
                                data-id="${data.id}">
                        </div>
                        `                    ,
                        `
                        <div style="display:flex; align-items:left; gap:6px; width:100%;">
                            <textarea ${authUpdate}
                                
                                class="updateInput text-left "
                                name="note"
                                rows="5"
                                style="width:100%; resize:vertical;"
                                data-id="${data.id}">${data.note ?? ''}</textarea>
                        </div>
                        `
                    ]);

                });

                batchTable.draw(); // 🔥 Vẽ lại table

            },

            error: function () {
                batchTable.clear().draw();
            }

        });

    });

});


    function formatDateForInput(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d)) return '';
        return d.toISOString().split('T')[0];
    }

    function formatDate(date) {
        if (!date) return '';
        let d = new Date(date);
        return d.toLocaleDateString('vi-VN');
    }
</script>

@if(isset($js_error))
<script>
    console.error("🔥 PRODUCTION ERROR:");
    console.error(@json($js_error));
</script>
@endif