<div class="content-wrapper">
    <section class="content mt-5">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="filter_department" class="mb-1">
                                <i class="fas fa-industry text-primary"></i> Phân Xưởng
                            </label>
                            <select class="form-control" id="filter_department">
                                <option value="">Tất cả phân xưởng</option>
                                @foreach ($departments as $code => $name)
                                    <option value="{{ $code }}" {{ $code === $userDepartment ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <table id="data_table_period" class="table table-bordered table-striped w-100">
                        <thead style="position: sticky; top: 60px; background-color: white; z-index: 1020">
                            <tr>
                                <th class="text-center" style="width: 60px;">STT</th>
                                <th class="text-center" style="width: 170px;">Phân Xưởng</th>
                                <th class="text-center">Kỳ Theo Dõi</th>
                                <th class="text-center">Số Mã BTP</th>
                                <th class="text-center">Số Mã TP</th>
                                <th class="text-center">Trạng Thái</th>
                                <th class="text-center" style="width: 120px;">Xem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($periods as $index => $period)
                                @php
                                    $periodCounts = $counts->get($period->id, collect());
                                    $btpCount = optional($periodCounts->firstWhere('category_type', 'BTP'))->total ?? 0;
                                    $tpCount = optional($periodCounts->firstWhere('category_type', 'TP'))->total ?? 0;
                                    $isCurrent = $period->start_date->toDateString() === $currentStart;
                                @endphp
                                <tr class="{{ $isCurrent ? 'table-warning' : '' }}"
                                    data-department="{{ $period->deparment_code }}">
                                    <td class="text-center align-middle pt-stt">{{ $index + 1 }}</td>
                                    <td class="align-middle">
                                        {{ $departments[$period->deparment_code] ?? $period->deparment_code }}
                                    </td>
                                    <td class="align-middle font-weight-bold">
                                        {{ $period->label }}
                                        @if ($isCurrent)
                                            <span class="badge badge-warning ml-1">Kỳ hiện tại</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">{{ $btpCount }}</td>
                                    <td class="text-center align-middle">{{ $tpCount }}</td>
                                    <td class="text-center align-middle">
                                        <span
                                            class="badge {{ $period->status === 'Đã chốt' ? 'badge-secondary' : 'badge-success' }}">
                                            {{ $period->status }}
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <a href="{{ route('pages.category.publication_tracking.show', $period->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Xem
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    // layout.js (jQuery, DataTables) được nạp sau mainContent nên phải đợi DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        document.body.style.overflowY = "auto";

        const table = $('#data_table_period').DataTable({
            "paging": false,
            "lengthChange": false,
            "searching": true,
            "ordering": false,
            "info": false,
            "autoWidth": false,
            "responsive": false,
            "language": {
                "search": "Tìm kiếm:",
                "zeroRecords": "Không tìm thấy kỳ theo dõi nào"
            }
        });

        // Lọc theo data-department của dòng, không phụ thuộc chữ đang hiển thị trong ô
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'data_table_period') {
                return true;
            }

            const wanted = $('#filter_department').val();
            if (!wanted) {
                return true;
            }

            return $(table.row(dataIndex).node()).data('department') === wanted;
        });

        // Đánh lại STT theo các dòng còn lại sau khi lọc
        table.on('draw', function() {
            table.rows({
                search: 'applied'
            }).nodes().to$().each(function(index) {
                $(this).find('.pt-stt').text(index + 1);
            });
        });

        $('#filter_department').on('change', function() {
            table.draw();
        });

        table.draw();
    });
</script>
