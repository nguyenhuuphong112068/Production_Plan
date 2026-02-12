

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
        font-size: 14px; /* từ 16 → 14 là vừa đẹp */
    }

    #data_table_raw_material th,
    #data_table_raw_material td {
        padding: 6px 6px;
    }

     .stockModal-modal-size {
        max-width: 100% !important;
        width: 100% !important;
        max-height: 100% !important;
        height: 100% !important;
    }
 
    #stockModal .modal-dialog {
        max-width: 100%;
        margin: 0;
    }

    #stockModal .modal-content {
        height: 95vh;
        display: flex;
        flex-direction: column;
    }

    #stockModal .modal-body {
        flex: 1;
        overflow-y: auto;
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

{{-- <div class="content-wrapper"> --}}
<div class="modal fade" id="stockModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog updateModal-modal-size" role="document">    
        <div class="card" style="min-height:100vh">
            <div class="card-body mt-5">
                {{-- SEARCH --}}
                <div class="row mb-2">
                    <div class="col-md-9"></div>
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
                                <th rowspan="2">Số Lượng Lô</th>
                                <th rowspan="2">Lượng Theo CT</th>
                                <th rowspan="2">Lượng Cần Dùng</th>
                                <th colspan="7" class="text-center">Tồn Kho</th>
                            </tr>
                            <tr>
                                <th>Lô NSX</th>
                                <th>Lô NB</th>
                                <th>HSD / Retest</th>
                                <th>Nhà SX</th>
                                <th>Nhập</th>
                                <th>Tồn</th>
                                <th>Trạng Thái</th>
                            </tr>
                        </thead>

                        <tbody id ="stock_modal_table_body">

                        {{-- @foreach ($datas as $data)
                            @php
                                $stocks = $data->stock ?? collect();
                                $rowspan = max($stocks->count(), 1);
                                $groupId = 'grp_'.$loop->iteration;
                            @endphp

                        
                            <tr data-group="{{ $groupId }}">
                                <td rowspan="{{ $rowspan }}">{{ $loop->iteration }}</td>
                                <td rowspan="{{ $rowspan }}">{{ $data->material_packaging_code }}</td>
                                <td rowspan="{{ $rowspan }}">{{ $data->MaterialName }}</td>
                                <td rowspan="{{ $rowspan }}">{{ $data->NumberOfBatch }}</td>
                                <td rowspan="{{ $rowspan }}">{{ round($data->total_qty,5) }} {{ $data->unit_bom }}</td>
                                <td rowspan="{{ $rowspan }}">{{ round($data->TotalMatQty,5) }} {{ $data->unit_bom }}</td>

                                
                                @if ($stocks->count())
                                    @php $s = $stocks->first(); $lb = lable_status($s->GRNSts,$s->IntBatchNo); @endphp
                                    <td>{{ $s->GRNNO}}</td>
                                    <td>{{ $s->ARNO }}
                                        {{ $s->Mfgbatchno}}
                                    </td>
                                    <td>
                                        {{ $s->Expirydate ? \Carbon\Carbon::parse($s->Expirydate)->format('d/m/Y') : '' }}<br>
                                        {{ $s->Retestdate ? \Carbon\Carbon::parse($s->Retestdate)->format('d/m/Y') : '' }}
                                    </td>
                                    <td>{{ $s->Mfg }}</td>
                                    <td>{{ round($s->ReceiptQuantity,4) }} {{ $s->MatUOM }}</td>
                                    <td>{{ round($s->Total_Qty,4) }} {{ $s->MatUOM }}</td>
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

            
                            @foreach ($stocks->skip(1) as $s)
                                @php $lb = lable_status($s->GRNSts,$s->IntBatchNo); @endphp
                                <tr data-group="{{ $groupId }}">
                                    <td>{{ $s->Mfgbatchno }}</td>
                                    <td>{{ $s->ARNO }}</td>
                                    <td>
                                        {{ $s->Expirydate ? \Carbon\Carbon::parse($s->Expirydate)->format('d/m/Y') : '' }}<br>
                                        {{ $s->Retestdate ? \Carbon\Carbon::parse($s->Retestdate)->format('d/m/Y') : '' }}
                                    </td>
                                    <td>{{ $s->Mfg }}</td>
                                    <td>{{ round($s->ReceiptQuantity,4) }} {{ $s->MatUOM }}</td>
                                    <td>{{ round($s->Total_Qty,4) }} {{ $s->MatUOM }}</td>
                                    <td class="text-center">
                                        <span style="background:{{ $lb['color'] }};color:#fff;padding:4px 12px;border-radius:14px">
                                            {{ $lb['text'] }}
                                        </span>
                                        {{ $s->IntBatchNo }}
                                    </td>
                                </tr>
                            @endforeach


                        @endforeach --}}

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

    $('#stockModal').on('shown.bs.modal', function() {
       
        const stock_modal_table_body = $('#stock_modal_table_body')
        const plan_list_id = {{ $plan_list_id }};

        stock_modal_table_body.empty();

        $.ajax({
                url: "{{ route('pages.plan.production.open_stock_modal') }}",
                type: 'post',
                data: {
                    plan_list_id: plan_list_id,
                    material_packaging_type : 0,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {

                        console.log (res)

                        if (res.length === 0) {
                            stock_modal_table_body.append(
                                `<tr><td colspan="13" class="text-center">Không có công thức</td></tr>`
                            );
                        } else {
                            res.forEach((data, index) => {

                                let stocks = data.stock ?? [];
                                let rowspan = Math.max(stocks.length, 1);
                                let groupId = 'grp_' + (index + 1);

                                if (stocks.length > 0) {

                                    let s = stocks[0];

                                    stock_modal_table_body.append(`
                                        <tr data-group="${groupId}">
                                            <td rowspan="${rowspan}">${index + 1}</td>
                                            <td rowspan="${rowspan}">${data.material_packaging_code ?? ''}</td>
                                            <td rowspan="${rowspan}">${data.MaterialName ?? ''}</td>
                                            <td rowspan="${rowspan}">${data.NumberOfBatch ?? ''}</td>
                                            <td rowspan="${rowspan}">
                                                ${Number(data.total_qty ?? 0).toFixed(5)} ${data.unit_bom ?? ''}
                                            </td>
                                            <td rowspan="${rowspan}">
                                                ${Number(data.TotalMatQty ?? 0).toFixed(5)} ${data.unit_bom ?? ''}
                                            </td>

                                            <td>${s.GRNNO ?? ''}</td>
                                            <td>${s.ARNO ?? ''} ${s.Mfgbatchno ?? ''}</td>
                                            <td>
                                                ${formatDate(s.Expirydate)}<br>
                                                ${formatDate(s.Retestdate)}
                                            </td>
                                            <td>${s.Mfg ?? ''}</td>
                                            <td>${Number(s.ReceiptQuantity ?? 0).toFixed(4)} ${s.MatUOM ?? ''}</td>
                                            <td>${Number(s.Total_Qty ?? 0).toFixed(4)} ${s.MatUOM ?? ''}</td>
                                            <td class="text-center">
                                                ${renderLabel(s)}
                                                ${s.IntBatchNo ?? ''}
                                            </td>
                                        </tr>
                                    `);

                                    // render các stock còn lại
                                    stocks.slice(1).forEach(s => {
                                        stock_modal_table_body.append(`
                                            <tr data-group="${groupId}">
                                                <td>${s.Mfgbatchno ?? ''}</td>
                                                <td>${s.ARNO ?? ''}</td>
                                                <td>
                                                    ${formatDate(s.Expirydate)}<br>
                                                    ${formatDate(s.Retestdate)}
                                                </td>
                                                <td>${s.Mfg ?? ''}</td>
                                                <td>${Number(s.ReceiptQuantity ?? 0).toFixed(4)} ${s.MatUOM ?? ''}</td>
                                                <td>${Number(s.Total_Qty ?? 0).toFixed(4)} ${s.MatUOM ?? ''}</td>
                                                <td class="text-center">
                                                    ${renderLabel(s)}
                                                    ${s.IntBatchNo ?? ''}
                                                </td>
                                            </tr>
                                        `);
                                    });

                                } else {

                                    stock_modal_table_body.append(`
                                        <tr data-group="${groupId}">
                                            <td>${index + 1}</td>
                                            <td>${data.material_packaging_code ?? ''}</td>
                                            <td>${data.MaterialName ?? ''}</td>
                                            <td>${data.NumberOfBatch ?? ''}</td>
                                            <td>${Number(data.total_qty ?? 0).toFixed(5)} ${data.unit_bom ?? ''}</td>
                                            <td>${Number(data.TotalMatQty ?? 0).toFixed(5)} ${data.unit_bom ?? ''}</td>
                                            <td colspan="7" class="text-center text-danger fw-bold">
                                                Không có tồn kho
                                            </td>
                                        </tr>
                                    `);
                                }

                            });

                        }
                        buildGroups();
                    },
                    error: function() {
                        stock_modal_table_body.append(
                            `<tr><td colspan="13" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
                        );
                    }
        });  
          
    });



});

function formatDate(date) {
    if (!date) return '';
    let d = new Date(date);
    return d.toLocaleDateString('vi-VN');
}
function renderLabel(s) {
    if (!s.label) return '';

    return `
        <span style="background:${s.label.color};
                     color:#fff;
                     padding:4px 12px;
                     border-radius:14px">
            ${s.label.text}
        </span>
    `;
}

</script>
