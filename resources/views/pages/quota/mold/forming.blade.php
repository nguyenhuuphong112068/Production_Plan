@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
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

        #data_table_forming_mold td {
            vertical-align: middle;
            font-size: 16px;
        }
    </style>

    <div class="content-wrapper">
        <div class="card">
            <div class="card-header mt-4">
                <h3 class="card-title">Định mức Khuôn Định Hình cho Bán Thành Phẩm</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">
                    Chỉ liệt kê BTP có định mức Định Hình ở phòng kiểm soát khuôn. Mỗi khuôn chỉ chạy được
                    trên tối đa <b>Số Lượng</b> phòng cùng lúc; khai báo khuôn ở Dữ liệu gốc &rsaquo; Khuôn Mẫu (Công Đoạn: Định Hình).
                </p>
                @php
                    $auth_update = user_has_permission(session('user')['userId'], 'quota_production_update', 'disabled');
                @endphp

                <table id="data_table_forming_mold" class="table table-bordered table-striped">
                    <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                        <tr>
                            <th>STT</th>
                            <th>Mã BTP</th>
                            <th>Tên Sản Phẩm</th>
                            <th>Cỡ Lô</th>
                            <th style="width: 30%;">Khuôn Định Hình</th>
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

            function escapeHtml(s) {
                return String(s ?? '').replace(/[&<>"']/g, function(c) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
                });
            }

            $('#data_table_forming_mold').DataTable({
                data: tableData,
                paging: true,
                deferRender: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
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
                        data: 'intermediate_code'
                    },
                    {
                        data: 'product_name'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return (row.batch_size || '') + ' ' + (row.unit_batch_size || '');
                        }
                    },
                    {
                        data: 'mold_ids',
                        render: function(data, type, row) {
                            var selectedIds = data ? data.split(',').map(function(id) { return parseInt(id); }) : [];

                            if (type === 'filter' || type === 'search' || type === 'sort') {
                                return moldsData.filter(function(m) {
                                    return selectedIds.indexOf(parseInt(m.id)) !== -1;
                                }).map(function(m) {
                                    return m.code;
                                }).join(' ');
                            }

                            var options = moldsData.map(function(mold) {
                                var isSelected = selectedIds.indexOf(parseInt(mold.id)) !== -1 ? 'selected' : '';
                                return '<option value="' + mold.id + '" data-amount="' + (mold.amount ?? 0) + '" ' +
                                    isSelected + '>' + escapeHtml(mold.code) + '</option>';
                            }).join('');

                            var isDisabled = authUpdate === 'disabled' ? 'disabled' : '';

                            return '<div class="select-mold-wrapper"><select ' + isDisabled +
                                ' class="form-control select-forming-mold" multiple="multiple" data-code="' +
                                escapeHtml(row.intermediate_code) + '">' + options + '</select></div>';
                        }
                    },
                    {
                        data: 'quota_rooms'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            var date = row.mold_created_at ? new Date(row.mold_created_at).toLocaleDateString('vi-VN') : '-';
                            var creator = row.mold_created_by || '-';
                            return '<div>' + escapeHtml(creator) + '</div><small class="text-muted">' + date + '</small>';
                        }
                    }
                ],
                drawCallback: function() {
                    function formatMold(mold) {
                        if (!mold.id) {
                            return mold.text;
                        }
                        var amount = $(mold.element).data('amount');
                        var $state = $('<span></span>').text(mold.text);
                        $state.append(' <span class="badge badge-info ml-1" style="font-size: 0.8em; padding: 0.2em 0.5em;">SL: ' +
                            (amount || 0) + '</span>');
                        return $state;
                    }

                    $('.select-forming-mold:not(.select2-hidden-accessible)').select2({
                        placeholder: "-- Chọn khuôn Định Hình --",
                        allowClear: true,
                        theme: 'bootstrap4',
                        templateSelection: formatMold,
                        templateResult: formatMold
                    });
                }
            });

            $(document).on('change', '.select-forming-mold', function() {
                $.ajax({
                    url: "{{ route('pages.quota.mold.forming.update') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        intermediate_code: $(this).data('code'),
                        mold_ids: $(this).val()
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
                        Swal.fire('Lỗi', xhr.responseJSON?.message || 'Không thể cập nhật khuôn Định Hình', 'error');
                    }
                });
            });
        });
    </script>
@endsection
