<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
    /* GRN hình tròn */
    .status-circle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #007bff;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        font-size: 11px;
        font-weight: 600;
        color: #007bff;
    }

    /* QC bo góc */
    .status-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 8px;
        border: 1px solid #999;
        font-size: 11px;
    }

    /* Approved */
    .status-approved {
        background: #28a745;
        color: white;
        border-color: #28a745;
    }
</style>
<div class="content-wrapper">
    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>

        <!-- /.card-Body -->
        <div class="card-body ">

            <table id="data_table" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th style = "width: 15px">STT</th>
                        <th>Mã Nguyên Liệu/GRN No.</th>

                        <th>Tên Bao Bì</th>
                        <th>Nhà Sản Xuất</th>
                        <th>Nhà Cung Cấp</th>
                        <th>Số Lô NSX</th>
                        <th>Số Lô NB</th>
                        <th>Hạn Dùng</th>
                        <th>Hạn TK</th>
                        <th>Nhập</th>
                        <th>Tồn</th>
                        <th>Định Khu</th>
                        {{-- <th>Tình Trạng Lấy Mẫu</th> --}}
                        <th style="width: 5%">CoA</th>
                        <th style="width: 1%">
                            {{ 'GRN Status' }}
                            <br>
                            {{ 'Approve Status' }}
                        </th>

                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->MatID }}
                                {{ $data->GRNNO }}
                            </td>
                            <td>{{ $data->MatNM }} </td>
                            <td>{{ $data->Mfg }} </td>
                            <td>{{ $data->Supplier }} </td>
                            <td>{{ $data->Mfgbatchno }} </td>
                            <td>{{ $data->ARNO }} </td>
                            <td>{{ $data->Expirydate ? \Carbon\Carbon::parse($data->Expirydate)->format('d/m/Y') : '' }}
                            </td>
                            <td>{{ $data->Retestdate ? \Carbon\Carbon::parse($data->Retestdate)->format('d/m/Y') : '' }}
                            </td>
                            <td>{{ round($data->{'ReceiptQuantity'}, 4) . ' ' . $data->MatUOM }} </td>
                            <td>{{ round($data->{'Total Qty'}, 4) . ' ' . $data->MatUOM }} </td>
                            <td>{{ $data->warehouse_id }} </td>
                            {{-- <td>{{ $material_status[$data->GRNSts] }} </td> --}}

                            <td>{{ $data->IntBatchNo }} </td>

                            <td class="text-center">
                                <span class="status-circle">
                                    {{ $data->GRNSts }}
                                </span>
                                <br>
                                <span
                                    class="status-pill 
                                {{ strtolower(trim($data->QCSTS)) == 'approved' ? 'status-approved' : '' }}">
                                    {{ $data->QCSTS }}
                                </span>
                            </td>



                        </tr>
                    @endforeach

                </tbody>
        </div>
        </table>

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
        $('#data_table').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            }
        });

    });
</script>
