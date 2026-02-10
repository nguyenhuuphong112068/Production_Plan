

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
                            <th rowspan="2">Mã SP</th>
                            <th rowspan="2">Tên SP</th>
                            <th rowspan="2">KL CT</th>
                            <th rowspan="2">Số Lô</th>
                            <th rowspan="2">KL Cần Dùng</th>
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
                            <td rowspan="{{ $rowspan }}">{{ $data->MatID }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->MaterialName }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->PrdID }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->MatNM }}</td>
                            <td rowspan="{{ $rowspan }}">{{ round($data->MatQty,5) }} {{ $data->uom }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $data->NumberOfBatch }}</td>
                            <td rowspan="{{ $rowspan }}">{{ round($data->TotalMatQty,5) }} {{ $data->uom }}</td>

                            @if ($stocks->count())
                                @php $s = $stocks->first(); $lb = lable_status($s->GRNSts,$s->IntBatchNo); @endphp
                                <td>{{ $s->Mfgbatchno }}</td>
                                <td>{{ $s->ARNO }}</td>
                                <td>
                                    {{ $s->Expirydate ? \Carbon\Carbon::parse($s->Expirydate)->format('d/m/Y') : '' }}<br>
                                    {{ $s->Retestdate ? \Carbon\Carbon::parse($s->Retestdate)->format('d/m/Y') : '' }}
                                </td>
                                <td>{{ $s->Mfg }}</td>
                                <td>{{ round($s->ReceiptQuantity,4) }} {{ $s->MatUOM }}</td>
                                <td>{{ round($s->{'Total Qty'},4) }} {{ $s->MatUOM }}</td>
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
                                <td>{{ $s->Mfgbatchno }}</td>
                                <td>{{ $s->ARNO }}</td>
                                <td>
                                    {{ $s->Expirydate ? \Carbon\Carbon::parse($s->Expirydate)->format('d/m/Y') : '' }}<br>
                                    {{ $s->Retestdate ? \Carbon\Carbon::parse($s->Retestdate)->format('d/m/Y') : '' }}
                                </td>
                                <td>{{ $s->Mfg }}</td>
                                <td>{{ round($s->ReceiptQuantity,4) }} {{ $s->MatUOM }}</td>
                                <td>{{ round($s->{'Total Qty'},4) }} {{ $s->MatUOM }}</td>
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
});
</script>
