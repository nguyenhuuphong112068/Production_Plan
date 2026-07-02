<style>
    .selectProductModal-modal-size {
        max-width: 90% !important;
        width: 90% !important;
    }
</style>

<div class="modal fade" id="select_intermediate_category_modal" tabindex="-1" role="dialog" aria-hidden="true"
    style="z-index: 1060;">
    <div class="modal-dialog selectProductModal-modal-size" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title w-100 text-center" style="color: #CDC717; font-size: 30px">
                    DANH MỤC BÁN THÀNH PHẨM
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" style="max-height: calc(100vh - 120px); overflow-y: auto;">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="ic_selection_dt" class="table table-bordered table-striped w-100">
                                <thead style="position: sticky; top: -1px; background-color: white; z-index: 1020">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">
                                            <input type="checkbox" id="selectAllIC">
                                        </th>
                                        <th>Mã BTP</th>
                                        <th>Tên Sản Phẩm</th>
                                        <th>Cỡ lô</th>
                                        <th>Phân xưởng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $allIC = \App\Models\IntermediateCategory::with('productName')
                                            ->where('active', 1)
                                            ->where('IsHypothesis', 0)
                                            ->get();
                                    @endphp
                                    @foreach ($allIC as $ic)
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="ic-checkbox" value="{{ $ic->id }}"
                                                    data-code="{{ $ic->intermediate_code }}"
                                                    data-name="{{ $ic->productName->name ?? '' }}">
                                            </td>
                                            <td>{{ $ic->intermediate_code }}</td>
                                            <td>{{ $ic->productName->name ?? '' }}</td>
                                            <td>
                                                <div>{{ $ic->batch_size }} {{ $ic->unit_batch_size }} #</div>
                                                <div>{{ $ic->batch_qty }} {{ $ic->unit_batch_qty }}</div>
                                            </td>
                                            <td>{{ $ic->deparment_code }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btnConfirmICSelection">Xác nhận chọn</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let icTable = $('#ic_selection_dt').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 50,
            "columnDefs": [{
                "orderable": false,
                "targets": 0
            }]
        });

        // Handle select all logic
        $('#selectAllIC').on('click', function() {
            let isChecked = $(this).prop('checked');
            // Check all checkboxes on the current page, or across all pages?
            // To check all across all pages, we must use DT API
            $('input.ic-checkbox', icTable.rows({
                search: 'applied'
            }).nodes()).prop('checked', isChecked);
        });

        // Optional: uncheck 'select all' if one is unchecked
        $('#ic_selection_dt tbody').on('change', 'input.ic-checkbox', function() {
            if (!$(this).prop('checked')) {
                $('#selectAllIC').prop('checked', false);
            }
        });
    });
</script>
