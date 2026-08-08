<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
    .status-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 8px;
        border: 1px solid #999;
        font-size: 11px;
        white-space: nowrap;
    }

    .status-active {
        background: #28a745;
        color: #fff;
        border-color: #28a745;
    }

    .status-inactive {
        background: #f1f1f1;
        color: #666;
    }

    .code-list {
        margin: 0;
        padding: 0;
        list-style: none;
        white-space: nowrap;
    }

    .code-list li+li {
        margin-top: 2px;
    }

    /* Badge version hiện hành của chính mã BTP/TP đứng trước nó */
    .rev-badge {
        display: inline-block;
        margin-left: 6px;
        padding: 1px 7px;
        border-radius: 8px;
        background: #e7f1ff;
        border: 1px solid #007bff;
        color: #0056b3;
        font-size: 11px;
        font-weight: 600;
    }

    /* Thị trường của mã TP đứng trước nó */
    .market-text {
        color: #6c757d;
        font-size: 12px;
    }

    .rev-badge-none {
        background: #f1f1f1;
        border-color: #ccc;
        color: #888;
        font-weight: 400;
    }

</style>

@php
    /** Bỏ phần .0 thừa của kiểu real trên MMS */
    $mms_number = function ($value) {
        if ($value === null || $value === '') {
            return '';
        }

        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    };
@endphp

<div class="content-wrapper">
    <div class="card mt-5">

        <div class="card-header mt-4">
            <form action="{{ route('pages.category.material_check.list') }}" method="GET" class="form-inline">
                <label for="mat_id" class="mr-2 font-weight-bold">Mã NL/BB</label>
                <input type="text" class="form-control mr-2" id="mat_id" name="mat_id" style="width: 260px"
                    value="{{ $mat_id }}" placeholder="Nhập mã nguyên liệu / bao bì" autofocus autocomplete="off">
                <button type="submit" class="btn btn-success mr-2">
                    <i class="fas fa-search"></i> Tìm
                </button>
                @if ($searched)
                    <a href="{{ route('pages.category.material_check.list') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Xóa
                    </a>
                @endif
            </form>
        </div>

        <!-- /.card-Body -->
        <div class="card-body">

            @if ($error)
                <div class="alert alert-danger">{{ $error }}</div>
            @endif

            @if ($searched && !$error && !$material)
                <div class="alert alert-warning">
                    Không tìm thấy mã <b>{{ $mat_id }}</b> trên MMS.
                </div>
            @endif

            @if (!$searched)
                <div class="alert alert-secondary">
                    Nhập một mã NL/BB rồi bấm <b>Tìm</b> để xem các công thức hiện hành trên MMS có sử dụng mã này.
                </div>
            @endif

            <table id="data_table_material_check" class="table table-bordered table-striped">

                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th style="width: 15px">STT</th>
                        <th>Mã Nguyên Liệu</th>
                        <th>Tên Nguyên Liệu/ Bao Bì</th>
                        <th>Mã BTP</th>
                        <th>Mã TP</th>
                        <th>Tên Sản Phẩm</th>
                        <th>Cỡ Lô</th>
                        <th>Trạng Thái</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $data->MatID }}</td>
                            <td>{{ trim($data->MatNM) }}</td>
                            <td>
                                <ul class="code-list">
                                    @foreach ($data->btp_items as $item)
                                        <li>
                                            {{ $item['code'] }}
                                            @if ($item['revision'] !== null)
                                                <span class="rev-badge"
                                                    title="Version hiện hành">v{{ $mms_number($item['revision']) }}</span>
                                            @else
                                                <span class="rev-badge rev-badge-none"
                                                    title="Không có BOM hiện hành trên MMS">--</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                <ul class="code-list">
                                    @foreach ($data->tp_items as $item)
                                        <li>
                                            {{ $item['code'] }}@if (!empty($item['market']))
                                                <span class="market-text" title="Thị trường">- {{ $item['market'] }}</span>
                                            @endif
                                            @if ($item['revision'] !== null)
                                                <span class="rev-badge"
                                                    title="Version hiện hành">v{{ $mms_number($item['revision']) }}</span>
                                            @else
                                                <span class="rev-badge rev-badge-none"
                                                    title="Không có BOM hiện hành trên MMS">--</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>{{ trim((string) $data->PrdName) }}</td>
                            <td>{{ trim($mms_number($data->BatchQty) . ' ' . $data->BatchqtyUOM) }}</td>
                            <td>
                                <span
                                    class="status-pill {{ $data->BOMSTS == 'BOM Active' ? 'status-active' : 'status-inactive' }}">
                                    {{ $data->BOMSTS }}
                                </span>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>

        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('#data_table_material_check').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            language: {
                search: "Lọc trong kết quả:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                zeroRecords: "Không có công thức nào sử dụng mã NL/BB này",
                emptyTable: "Không có công thức nào sử dụng mã NL/BB này",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            }
        });
    });
</script>
