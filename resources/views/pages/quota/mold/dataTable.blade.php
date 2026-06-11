<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/select2/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('assets/vendor/select2/select2-bootstrap4.min.css') }}">

<style>
    .select-mold-wrapper .select2-container {
        width: 100% !important;
        max-width: 100% !important;
    }

    .select2-selection--multiple {
        max-height: 150px;
        overflow-y: auto !important;
    }

    #data_table_quota td {
        vertical-align: middle;
        font-size: 16px;
    }
</style>

<div class="content-wrapper">
    <div class="card">
        <div class="card-header mt-4">
            <h3 class="card-title">Định mức Khuôn mẫu cho Sản phẩm</h3>
        </div>
        <div class="card-body">
            @php
                $auth_update = user_has_permission(session('user')['userId'], 'quota_production_update', 'disabled');
            @endphp

            <table id="data_table_quota" class="table table-bordered table-striped">
                <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã Sản Phẩm</th>
                        <th>Tên Sản Phẩm</th>
                        <th>Cỡ Lô</th>
                        <th>Thị Trường</th>
                        <th>Qui Cách</th>
                        <th style="width: 30%;">Khuôn mẫu</th>
                        <th>Phòng SX Đã định mức</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        var moldsData = @json($molds);
        var tableData = @json($datas);
        var authUpdate = '{{ $auth_update }}';
        var blisterTypesData = @json($blister_types);

        const table = $('#data_table_quota').DataTable({
            data: tableData,
            paging: true,
            deferRender: true,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            searching: true,
            ordering: true,
            autoWidth: false,
            columns: [{
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'finished_product_code'
                },
                {
                    data: 'product_name'
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return (row.batch_qty || '') + ' ' + (row.unit_batch_qty || '');
                    }
                },
                {
                    data: 'market_name'
                },
                {
                    data: 'specification_name'
                },
                {
                    data: 'mold_ids',
                    render: function(data, type, row) {
                        var rawIds = data;
                        var selectedIds = [];
                        if (rawIds) {
                            selectedIds = rawIds.split(',').map(id => parseInt(id));
                        }

                        if (type === 'filter' || type === 'search' || type === 'sort') {
                            return moldsData.filter(function(m) {
                                return selectedIds.indexOf(parseInt(m.id)) !== -1;
                            }).map(function(m) {
                                return m.code;
                            }).join(' ');
                        }

                        var allowedTypeCodes = [];
                        if (row.quota_rooms) {
                            var rooms = row.quota_rooms.split('::');
                            rooms.forEach(function(r) {
                                var parts = r.split('|');
                                if (parts[1]) {
                                    allowedTypeCodes.push(parseInt(parts[1]));
                                }
                            });
                        }

                        var filteredMolds = moldsData.filter(function(mold) {
                            if (allowedTypeCodes.length === 0) return true;
                            return allowedTypeCodes.indexOf(parseInt(mold
                                .blister_type_code)) !== -1 || selectedIds.indexOf(
                                parseInt(mold.id)) !== -1;
                        });

                        var options = filteredMolds.map(function(mold) {
                            var isSelected = selectedIds.indexOf(parseInt(mold.id)) !==
                                -1 ? 'selected' : '';
                            var typeName = mold.type_name ? mold.type_name : '';
                            return '<option value="' + mold.id + '" data-type="' +
                                typeName + '" ' + isSelected + '>' + mold.code +
                                '</option>';
                        }).join('');

                        var isDisabled = authUpdate === 'disabled' ? 'disabled' : '';

                        return '<div class="select-mold-wrapper"><select ' + isDisabled +
                            ' class="form-control select-mold" multiple="multiple" data-id="' +
                            row.id + '">' + options + '</select></div>';
                    }
                },
                {
                    data: 'quota_rooms',
                    render: function(data, type, row) {
                        if (!data) return '<span class="text-muted">Chưa định mức</span>';

                        var rooms = data.split('::');
                        var html = rooms.map(function(roomStr) {
                            var parts = roomStr.split('|');
                            var roomName = parts[0];
                            var typeCode = parts[1];

                            var badgeHtml = '';
                            if (typeCode && typeof blisterTypesData !== 'undefined') {
                                var typeNames = blisterTypesData.filter(function(bt) {
                                    return bt.code == typeCode;
                                }).map(function(bt) {
                                    return bt.name;
                                }).join(', ');

                                if (typeNames) {
                                    badgeHtml =
                                        ' <span class="badge badge-success ml-1" style="font-size: 0.8em; padding: 0.2em 0.5em;">' +
                                        typeNames + '</span>';
                                }
                            }

                            return '<div>' + roomName + badgeHtml + '</div>';
                        }).join('');

                        return html;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        var date = row.mold_created_at ? new Date(row.mold_created_at)
                            .toLocaleDateString('vi-VN') : '-';
                        var creator = row.mold_created_by || '-';
                        return '<div>' + creator + '</div>' +
                            '<small class="text-muted">' + date + '</small>';
                    }
                }
            ],
            drawCallback: function() {
                function formatMold(mold) {
                    if (!mold.id) {
                        return mold.text;
                    }
                    var typeName = $(mold.element).data('type');
                    var $state = $('<span>' + mold.text + '</span>');
                    if (typeName) {
                        $state.append(
                            ' <span class="badge badge-primary ml-1" style="font-size: 0.8em; padding: 0.2em 0.5em;">' +
                            typeName + '</span>');
                    }
                    return $state;
                }

                $('.select-mold:not(.select2-hidden-accessible)').select2({
                    placeholder: "-- Chọn khuôn mẫu --",
                    allowClear: true,
                    theme: 'bootstrap4',
                    templateSelection: formatMold,
                    templateResult: formatMold
                });
            }
        });

        // Handle multi-select change
        $(document).on('change', '.select-mold', function() {
            var id = $(this).data('id');
            var moldIds = $(this).val();

            $.ajax({
                url: "{{ route('pages.quota.mold.update') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    mold_ids: moldIds
                },
                success: function(res) {
                    if (res.success) {
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        }).fire({
                            icon: 'success',
                            title: res.message
                        });
                    } else {
                        Swal.fire('Lỗi', res.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Lỗi server', 'Không thể cập nhật khuôn mẫu', 'error');
                }
            });
        });
    });
</script>
