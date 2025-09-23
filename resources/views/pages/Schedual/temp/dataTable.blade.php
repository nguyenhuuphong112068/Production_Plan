<div class="content-wrapper">
    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>
        <!-- /.card-Body -->
        <div class="card-body">
            <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#create_stage_plan_list_modal"
              style="width: 155px">
                <i class="fas fa-plus"></i> Thêm
            </button>

            <table id="data_table_sp_temp_list" class="table table-bordered table-striped" style="font-size: 20px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Tên</th>
                        <th>Người Tạo</th>
                        <th>Ngày Tạo</th>
                        <th class="text-center align-middle">Lập Lịch</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->name }}</td>
                            <td>
                                <div> {{ $data->prepared_by }} </div>
                            </td>
                            <td>
                                <div>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }} </div>
                            </td>
                            <td class="text-center align-middle">
                                <form action="{{ route('pages.Schedual.temp.open') }}" method="get">
                                    @csrf
                                    <input type="hidden"  name="stage_plan_temp_list_id" value = "{{ $data->id }}">
                                    <input type="hidden"  name="name" value = "{{ $data->name }}">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-calendar-check"></i>
                                    </button>
                                </form>
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
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>



<script>
    $(document).ready(function() {
        $('#data_table_sp_temp_list').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
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
