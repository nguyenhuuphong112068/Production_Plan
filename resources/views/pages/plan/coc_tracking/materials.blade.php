{{-- Nội dung modal Nguyên Liệu / Bao Bì Sử Dụng của một lô (CocTrackingController::materials) --}}
@php
    $qty = fn($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    $dmy = fn($date) => $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '';
    $tabs = [
        'materials' => ['Nguyên Liệu', $materials, 'Mã BTP ' . $lot->intermediate_code, 'Mã NL', 'Tên Nguyên Liệu'],
        'packagings' => ['Bao Bì', $packagings, 'Mã TP ' . $lot->finished_product_code, 'Mã BB', 'Tên Bao Bì'],
    ];
@endphp

<ul class="nav nav-tabs px-3 pt-2" role="tablist">
    @foreach ($tabs as $id => [$title, $rows])
        <li class="nav-item">
            <a class="nav-link font-weight-bold" id="coc-mat-tab-{{ $id }}" data-toggle="tab" href="#coc-mat-{{ $id }}"
                role="tab">
                {{ $title }} <span class="badge badge-secondary">{{ count($rows) }}</span>
            </a>
        </li>
    @endforeach
</ul>

<div class="tab-content">
    @foreach ($tabs as $id => [$title, $rows, $source, $codeLabel, $nameLabel])
        <div class="tab-pane fade" id="coc-mat-{{ $id }}" role="tabpanel">
            <div class="small text-muted px-3 py-2">
                Phiếu xuất kho {{ mb_strtolower($title) }} cho sản xuất trên MMS ({{ $source }}, số lô {{ $batch }}).
                Số lượng đã cộng dồn các bao / lần cân của cùng một lô {{ mb_strtolower($title) }}.
            </div>
            <div class="coc-mat-scroll">
                <table class="table table-bordered table-sm table-hover mb-0 coc-mat-table">
                    <thead class="text-center">
                        <tr>
                            <th>STT</th>
                            <th>Công Đoạn</th>
                            <th>{{ $codeLabel }}</th>
                            <th>{{ $nameLabel }}</th>
                            <th title="Số lô kiểm soát nội bộ (lotno)">Số Lô</th>
                            <th title="Số lô của nhà sản xuất (GRN.Mfgbatchno)">Số Lô NSX</th>
                            <th>Số COA</th>
                            <th>Số Lượng</th>
                            <th>ĐVT</th>
                            <th>Ngày Xuất</th>
                            <th>Phiếu Xuất</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $index => $row)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $row['stage'] }}</td>
                                <td class="text-center">{{ $row['code'] }}</td>
                                <td class="coc-mat-name">{{ $row['name'] }}</td>
                                <td class="text-center">{{ $row['lot'] }}</td>
                                <td class="text-center">{{ $row['mfg_batch'] }}</td>
                                <td class="text-center">{{ $row['coa'] }}</td>
                                <td class="text-right">{{ $qty($row['qty']) }}</td>
                                <td class="text-center">{{ $row['uom'] }}</td>
                                <td class="text-center">{{ $dmy($row['first_date']) }}</td>
                                <td class="text-center">{{ implode(', ', $row['issues']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    Chưa có phiếu xuất {{ mb_strtolower($title) }} cho lô này trên MMS
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
