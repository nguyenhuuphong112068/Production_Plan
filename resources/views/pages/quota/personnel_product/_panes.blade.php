{{-- Tab "Sản phẩm được phép" của trang Danh sách nhân viên (Nhân sự – Sản phẩm).
     Cần: $productTab (employees, canEdit, types), $activeTab. Modal + script nằm ở _modal.blade.php. --}}
<style>
    #tbl-pp-employees td {
        vertical-align: middle !important;
        padding: 4px 8px !important;
        font-size: 0.875rem;
    }

    .pp-count {
        display: inline-block;
        min-width: 34px;
        padding: 3px 8px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.8rem;
        text-align: center;
    }

    .pp-count.zero {
        background: #f1f3f5;
        color: #adb5bd;
    }

    .pp-count.btp {
        background: #e3f2fd;
        color: #0d47a1;
    }

    .pp-count.tp {
        background: #e8f5e9;
        color: #1b5e20;
    }

    /* Danh sách "mã - tên" sản phẩm đã khai báo trong ô của bảng NV; dài thì cuộn trong ô */
    .pp-list {
        max-height: 120px;
        overflow-y: auto;
        font-size: 0.78rem;
        line-height: 1.35;
        text-align: left;
    }

    .pp-list .pp-code {
        font-weight: 600;
        color: #0d47a1;
    }

    .pp-list.tp .pp-code {
        color: #1b5e20;
    }

    .pp-list .pp-item {
        padding: 1px 0 2px;
        border-bottom: 1px dotted #e3e6ea;
    }

    .pp-list .pp-item:last-child {
        border-bottom: none;
    }

    .pp-list .pp-meta {
        display: block;
        font-size: 0.68rem;
        color: #8a939b;
    }

    .pp-list-empty {
        color: #adb5bd;
        font-size: 0.78rem;
    }

    .pp-room {
        display: inline-block;
        font-size: 0.72rem;
        padding: 1px 6px;
        margin: 1px 2px 1px 0;
        border-radius: 8px;
        background: #f0f4f8;
        color: #37474f;
        border: 1px solid #cfd8dc;
        white-space: nowrap;
    }
</style>

<div class="tab-pane fade {{ $activeTab === 'products' ? 'show active' : '' }}" data-pn-pane="products">
    {{-- <div class="callout callout-info py-2 mb-3" style="font-size: 0.85rem;">
        <ul class="mb-0 pl-3">
            <li><b>BTP</b> (mã bán thành phẩm) áp cho công đoạn 1-6; <b>TP</b> (mã thành phẩm) áp cho công đoạn 7 – Đóng
                gói.</li>
            <li>Phòng được làm <b>không khai báo riêng</b>: = phòng NV đã được cấp ở tab <i>Tổ &amp; Phòng công tác</i> ∩
                phòng định mức sản xuất của sản phẩm.</li>
            <li>NV được xếp vào lô mà chưa khai báo sản phẩm của lô thì Lịch công tác sản xuất <b>cảnh báo</b> ngay dưới ô Hoạt động, không chặn xếp lịch; chỉ các mục chép
                từ Lịch lý thuyết (nút <b>&gt;&gt;</b>) mới được đối chiếu, chữ gõ tay thì không.</li>
        </ul>
    </div> --}}

    <table id="tbl-pp-employees" class="table table-bordered table-striped w-100">
        <thead>
            <tr>
                <th style="width: 40px;">STT</th>
                <th style="width: 90px;">Mã NV</th>
                <th>Tên Nhân Viên</th>
                <th>Tổ</th>
                <th style="width: 150px;">Phòng SX</th>
                <th style="width: 22%;">BTP được phép</th>
                <th style="width: 22%;">TP được phép</th>
                <th style="width: 80px;" class="text-center"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($productTab['employees'] as $e)
                <tr data-employee-id="{{ $e->id }}" data-room-key="{{ $e->room_key }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $e->code }}</td>
                    <td>{{ $e->name }}</td>
                    <td>{{ implode(', ', $e->group_names) }}</td>
                    <td data-order="{{ $e->room_count }}">
                        @forelse ($e->room_codes as $rc)
                            <span class="pp-room">{{ $rc }}</span>
                        @empty
                            <span class="pp-list-empty">—</span>
                        @endforelse
                    </td>
                    @foreach (['BTP' => $e->btp_items, 'TP' => $e->tp_items] as $type => $items)
                        <td class="pp-cell-{{ $type }}" data-order="{{ count($items) }}">
                            @if ($items)
                                <div class="pp-list {{ strtolower($type) }}">
                                    @foreach ($items as $it)
                                        <div class="pp-item"><span class="pp-code">{{ $it['code'] }}</span> - {{ $it['name'] }}
                                            <span class="pp-meta"><i class="fas fa-user-edit"></i> {{ $it['by'] }} · {{ $it['at'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="pp-list-empty">—</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="text-center">
                        <button
                            class="btn btn-xs {{ $productTab['canEdit'] ? 'btn-primary' : 'btn-outline-secondary' }} btn-pp-open"
                            data-id="{{ $e->id }}"
                            title="{{ $e->room_count ? ($productTab['canEdit'] ? 'Khai báo sản phẩm' : 'Xem sản phẩm') : 'Chưa được cấp phòng sản xuất nào' }}"
                            @if (!$e->room_count) disabled @endif>
                            <i class="fas {{ $productTab['canEdit'] ? 'fa-edit' : 'fa-eye' }}"></i>
                        </button>
                        @if ($productTab['canEdit'])
                            <button class="btn btn-xs btn-info btn-pp-copy" data-id="{{ $e->id }}"
                                title="Nhân bản khai báo sang nhân viên khác cùng phòng SX"
                                @if (!$e->room_count || !($e->btp_count + $e->tp_count)) disabled @endif>
                                <i class="fas fa-copy"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
