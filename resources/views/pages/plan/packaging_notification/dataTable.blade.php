@php
    // Gợi ý đơn vị lấy mẫu từ những giá trị đã nhập trước đó, đỡ gõ lại và đỡ lệch chính tả
    $unitOptions = $records->pluck('sampling_uint')->filter()->unique()->sort()->values();
    $disabled = $canUpdate ? '' : 'disabled';
@endphp

<datalist id="pkg_unit_options">
    @foreach ($unitOptions as $unit)
        <option value="{{ $unit }}"></option>
    @endforeach
</datalist>

@if ($datas->isEmpty())
    <div class="alert alert-secondary py-3 mb-0 text-center">
        <i class="fas fa-inbox"></i> {{ $emptyText }}
    </div>
@else
    <div class="table-responsive" id="{{ $tableId }}_scroll">
        <table class="table table-bordered table-striped table-hover mb-0 pkg-table" id="{{ $tableId }}">
            <thead class="text-center align-middle">
                <tr>
                    <th class="pkg-sticky pkg-sticky-1 bg-light" style="min-width: 50px;">STT</th>
                    <th class="pkg-sticky pkg-sticky-2 bg-light" style="min-width: 90px;">Số Lô</th>
                    <th class="pkg-sticky pkg-sticky-3 bg-light" style="min-width: 200px;">Tên Sản Phẩm</th>
                    <th class="bg-light" style="min-width: 130px;">Mã TP</th>
                    <th class="bg-light" style="min-width: 130px;">Mã BTP</th>
                    <th class="bg-light" style="min-width: 110px;">Thị Trường</th>
                    <th class="bg-light" style="min-width: 150px;">Quy Cách</th>
                    <th class="bg-light" style="min-width: 100px;">Cỡ Lô</th>
                    <th class="bg-light" style="min-width: 90px;">Tỉ Lệ ĐG</th>
                    <th class="bg-light" style="min-width: 100px;">Ngày DK</th>

                    <th class="bg-warning" style="min-width: 130px;">Số PO</th>
                    <th class="bg-warning" style="min-width: 170px;">Quy Cách Lấy Mẫu</th>
                    <th class="bg-warning" style="min-width: 140px;">Số Lần Lấy Mẫu</th>
                    <th class="bg-warning" style="min-width: 130px;">Số Lượng Lấy Mẫu</th>
                    <th class="bg-warning" style="min-width: 110px;">Đơn Vị</th>
                    <th class="bg-warning" style="min-width: 220px;">Lý Do</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($datas as $index => $data)
                    @php
                        $record = $records->get($data->id);
                    @endphp
                    <tr data-plan-master-id="{{ $data->id }}" data-batch="{{ $data->batch }}"
                        class="{{ $record ? '' : 'table-light' }}">
                        <td class="pkg-sticky pkg-sticky-1 text-center">{{ $index + 1 }}</td>
                        <td class="pkg-sticky pkg-sticky-2 text-center font-weight-bold">
                            {{ $data->batch }}
                            @unless ($record)
                                <i class="fas fa-circle text-muted pkg-unsaved" style="font-size: 6px;"
                                    title="Lô chưa có dòng thông báo đóng gói - sẽ được tạo khi bạn nhập và lưu."></i>
                            @endunless
                        </td>
                        <td class="pkg-sticky pkg-sticky-3">{{ $data->finished_product_name }}</td>
                        <td class="text-center">{{ $data->finished_product_code }}</td>
                        <td class="text-center">{{ $data->intermediate_code }}</td>
                        <td class="text-center">{{ $data->market }}</td>
                        <td>{{ $data->specification }}</td>
                        <td class="text-center">
                            {{ $data->batch_qty ? number_format($data->batch_qty) : '' }}
                            {{ $data->unit_batch_qty }}
                        </td>
                        <td class="text-center">
                            {{ $data->percent_parkaging !== null ? round($data->percent_parkaging * 100, 2) . '%' : '' }}
                        </td>
                        <td class="text-center">
                            {{ $data->expected_date ? \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') : '' }}
                        </td>

                        <td>
                            <input type="text" class="form-control form-control-sm pkg-input" name="PO_no"
                                maxlength="50" value="{{ $record->PO_no ?? '' }}" {{ $disabled }}>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm pkg-input"
                                name="Sampling_specifications" maxlength="100"
                                value="{{ $record->Sampling_specifications ?? '' }}" {{ $disabled }}>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm pkg-input" name="Sampling_times"
                                maxlength="100" value="{{ $record->Sampling_times ?? '' }}" {{ $disabled }}>
                        </td>
                        <td>
                            <input type="number" step="any" class="form-control form-control-sm pkg-input text-right"
                                name="Sampling_amount"
                                value="{{ $record && $record->Sampling_amount !== null ? 0 + $record->Sampling_amount : '' }}"
                                {{ $disabled }}>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm pkg-input" name="sampling_uint"
                                list="pkg_unit_options" maxlength="50" value="{{ $record->sampling_uint ?? '' }}"
                                {{ $disabled }}>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm pkg-input" name="Reason"
                                maxlength="255" value="{{ $record->Reason ?? '' }}" {{ $disabled }}>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
