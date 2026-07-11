<table class="table table-bordered table-striped" id="productsTable" style="width: 100%">
    <thead style="position: sticky; top: 0; background: white; z-index: 10;">
        <tr>
            <th class="text-center" style="width: 50px;">
                <input type="checkbox" id="selectAllProducts">
            </th>
            <th>Mã BTP</th>
            <th>Mã TP</th>
            <th>Tên Sản Phẩm</th>
            <th>Cỡ Lô</th>
            <th>Thị Trường</th>
            <th>Qui Cách</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($products as $fpc)
            <tr>
                <td class="text-center">
                    <input type="checkbox" name="selected_products[]"
                        value="{{ $fpc->id }}" class="product-checkbox">
                </td>
                <td>{{ $fpc->intermediate_code }}</td>
                <td>{{ $fpc->finished_product_code }}</td>
                <td>{{ $fpc->productName?->name ?? $fpc->name }}</td>
                <td>{{ $fpc->batch_qty }} {{ $fpc->unit_batch_qty }}</td>
                <td>{{ $fpc->market }}</td>
                <td>{{ $fpc->specification }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<script>
    $(document).ready(function() {
        if (!$.fn.DataTable.isDataTable('#productsTable')) {
            $('#productsTable').DataTable({
                "pageLength": 50,
                "lengthMenu": [[50, 100, 500, -1], [50, 100, 500, "Tất cả"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json"
                },
                "order": [[ 3, "asc" ]] // Sắp xếp theo tên sản phẩm
            });
        }

        // Handle 'Select All' after DataTables is drawn (for paginated items)
        $('#selectAllProducts').on('change', function() {
            var isChecked = $(this).is(':checked');
            // If DataTables is active, find all checkboxes across all pages
            var table = $('#productsTable').DataTable();
            $('input.product-checkbox', table.cells().nodes()).prop('checked', isChecked);
        });

        $('#productsTable tbody').on('change', '.product-checkbox', function() {
            var table = $('#productsTable').DataTable();
            var totalCheckboxes = $('input.product-checkbox', table.cells().nodes()).length;
            var checkedCheckboxes = $('input.product-checkbox:checked', table.cells().nodes()).length;
            $('#selectAllProducts').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
    });
</script>
