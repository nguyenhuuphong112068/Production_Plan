

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


</style>

@php
function lable_status(int $GRNSts, ?string $ARNO): array {
    if (!empty($ARNO) && $GRNSts == 7) {
        return ['text'=>'Chờ Tái Kiểm','color'=>'#dc2626'];
    }
    if (!empty($ARNO) && $GRNSts >= 2 && $GRNSts <= 5) {
        return ['text'=>'Chấp Nhận','color'=>'#166534'];
    }
    if (empty($ARNO) && $GRNSts >= 2 && $GRNSts <= 5) {
        return ['text'=>'Đã Lấy Mẫu','color'=>'#ca8a04'];
    }
    return ['text'=>'Biệt Trữ','color'=>'#facc15'];
}
@endphp

<div class="content-wrapper">
    <div class="card" style="min-height:100vh">
        <div class="card-body mt-5">

            {{-- SEARCH --}}
            <div class="row mb-2">
                <div class="col-md-9">
                    <button type="button" class="btn btn-success"
                        onclick="window.location.href='{{ $current_url }}'">
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
                            <th rowspan="2" >Mã NL</th>
                            <th rowspan="2" style="width: 15%">Tên NL</th>                      
                            <th rowspan="2" style="width: 4%">Số Lượng Lô</th>
                            <th rowspan="2">Lượng Theo CT</th>
                            <th rowspan="2">Lượng Cần Dùng</th>
                            <th rowspan="2">Tổng Tồn</th>
                            <th colspan="7" class="text-center">Tồn Kho Hiện Hành</th>
                        </tr>
                        <tr>
                            <th>Tồn</th>
                            <th>Nhập</th>
                            <th>Số GRN</th>
                            <th>Số Lô NB/ Số Lô NSX</th>
                            <th>HSD / Retest</th>
                            <th>Nhà SX</th>
                            <th>Trạng Thái</th>
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
                            <td rowspan="{{ $rowspan }}">{{ $data->NumberOfBatch }}
                                <button type="button" class="btn btn-sm btn-batch-datial"
                                        data-plan_master_ids="{{ $data->plan_master_ids }}"
                            
                                        data-toggle="modal" 
                                        data-target="#batchDetialModal"
                                        >
                                                    📝
                                </button>
                            </td>
                            <td rowspan="{{ $rowspan }}">{{ round($data->total_qty,5) }} {{ $data->unit_bom }}</td>
                            <td rowspan="{{ $rowspan }}">{{ round($data->TotalMatQty,5) }} {{ $data->unit_bom }}</td>
                            <td rowspan="{{ $rowspan }}">{{ round($data->totalQty,5) }} {{ $data->unit_bom }}</td>

                            
                            @if ($stocks->count())
                                @php $s = $stocks->first(); $lb = lable_status($s->GRNSts,$s->IntBatchNo); @endphp
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

                                <td class="text-center">
                                    <span style="background:{{ $lb['color'] }};color:#fff;padding:4px 12px;border-radius:14px">
                                        {{ $lb['text'] }}
                                    </span>
                                    {{ $s->IntBatchNo }}
                                </td>
                            @else
                                <td colspan="7" class="text-center text-danger fw-bold">
                                    Không có tồn kho
                                </td>
                            @endif
                        </tr>

                        {{-- ROW STOCK --}}
                        @foreach ($stocks->skip(1) as $s)
                            @php $lb = lable_status($s->GRNSts,$s->IntBatchNo); @endphp
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

                                
                                <td class="text-center">
                                    <span style="background:{{ $lb['color'] }};color:#fff;padding:4px 12px;border-radius:14px">
                                        {{ $lb['text'] }}
                                    </span>
                                    {{ $s->IntBatchNo }}
                                </td>
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

    $('.btn-batch-datial').on('click', function() {
     
        const btn = $(this);
        const plan_master_ids_text = btn.data('plan_master_ids');

        const plan_master_ids = plan_master_ids_text
            ? String(plan_master_ids_text).split('_')
            : [];
        if (!plan_master_ids.length) return
        
        const modal_table_body = $('#data_table_batch_detail_body')
        
                modal_table_body.empty();

                $.ajax({
                        url: "{{ route('pages.plan.production.open_bacth_detail') }}",
                        type: 'post',
                        data: {
                            plan_master_ids: plan_master_ids,
                            _token: "{{ csrf_token() }}"
                        },

                        success: function (res) {
                            console.log (res)
                            if (!res || res.length === 0) {
                                modal_table_body.append(`
                                    <tr>
                                        <td colspan="15" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                `);
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

                                modal_table_body.append(`
                                    <tr>
                                        <td>
                                            <div>${index + 1}</div>
                                            ${data.userGroup === "Admin" ? `<div>${data.id}</div>` : ''}
                                        </td>

                                        <td>
                                            <div class="text-center"
                                                style="display:inline-block;padding:6px 10px;width:100px;border-radius:10px;
                                                ${statusColors[data.status] ?? ''}">
                                                ${data.status ?? ''}
                                            </div>
                                        </td>

                                        <td class="${cancelClass}">
                                            <div>${data.intermediate_code ?? ''}</div>
                                            <div>${data.finished_product_code ?? ''}</div>
                                        </td>

                                        <td>
                                            <div>${data.intermediate_product_name ?? ''}</div>
                                            <div>${finishedName}</div>
                                            <div>(${data.batch_qty ?? ''} ${data.unit_batch_qty ?? ''})</div>
                                        </td>

                                        <td class="text-center">
                                            <div>${data.batch ?? ''}</div>
                                        </td>

                                        <td>
                                            <div>${data.market ?? ''}</div>
                                            <div>${data.specification ?? ''}</div>
                                        </td>

                                        <td>
                                            <div>${formatDateInput(data.expected_date)??''}</div>
                                        </td>

                                        <td class="text-center">
                                            <span style="display:inline-block;padding:6px 10px;width:50px;border-radius:40px;
                                                ${levelColors[data.level] ?? ''}">
                                                <input type="text"
                                                    class="updateInput"
                                                    name="level"
                                                    value="${data.level ?? ''}"
                                                    data-id="${data.id}">
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <input type="checkbox"
                                                ${data.is_val ? 'checked' : ''}
                                                disabled>
                                            <br>
                                            ${isValText}
                                        </td>

                                        <td>
                                            <div>${formatDateInput(data.expected_date)??''}</div>
                                        </td>
                                        <td>
                                            <div>${formatDateInput(data.expected_date)??''}</div>
                                        </td>

                                        <td>${data.note ?? ''}</td>

                                
                                    </tr>
                                `);
                            });
                        }
                        ,
                        error: function() {
                                modal_table_body.append(
                                    `<tr><td colspan="13" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
                                );
                            }
                });  
                
    });   
});


function formatDateInput(date) {
    if (!date) return '';
    let d = new Date(date);
    return d.toISOString().split('T')[0];
}

function formatDate(date) {
    if (!date) return '';
    let d = new Date(date);
    return d.toLocaleDateString('vi-VN');
}
</script>
