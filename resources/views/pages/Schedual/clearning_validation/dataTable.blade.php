
<div class="content-wrapper">

    <div class="card" >

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>
        <!-- /.card-Body -->
        <div class="card-body">

            <table id="data_table_Schedual_list" class="table table-bordered table-striped" style="font-size: 20px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã Sản Phẩm</th>
                        <th>Sản Phẩm</th>
                        <th>Cỡ lô</th>
                        <th>Số Lô</th>
                        <th>Lô Thẩm Định</th>
                        <th>Phòng Sản Xuất</th>
                        <th>Thới Gian Sản Xuất</th>
                        <th>Thời Gian Vệ Sinh</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>
                                <div> {{ $data->intermediate_code }} </div>
                                <div> {{ $data->finished_product_code }} </div>
                            </td>
                            <td>{{ $data->title }}</td>
                            <td>{{ $data->batch_qty . ' ' . $data->unit_batch_qty }}</td>
                            <td>{{ $data->batch }} </td>

                            <td class="text-center align-middle">
                                @if ($data->is_val)
                                    <i class="fas fa-check-circle text-primary fs-4"></i>
                                @endif
                            </td>
                            <td> {{ $data->room_name . ' - ' . $data->room_code }} </td>
                            <td> {{ \Carbon\Carbon::parse($data->start)->format('d/m/Y H:i') . ' - ' . \Carbon\Carbon::parse($data->end)->format('d/m/Y H:i') }}
                            </td>
                            <td> {{ \Carbon\Carbon::parse($data->start_clearning)->format('d/m/Y H:i') . ' - ' . \Carbon\Carbon::parse($data->end_clearning)->format('d/m/Y H:i') }}
                            </td>


                            <td>
                                <div> {{ $data->schedualed_by }} </div>
                                <div>{{ \Carbon\Carbon::parse($data->schedualed_at)->format('d/m/Y') }} </div>
                            </td>


                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
</div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>




<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('#data_table_Schedual_list').DataTable({
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
            },
        });

        

    
    });
</script>

