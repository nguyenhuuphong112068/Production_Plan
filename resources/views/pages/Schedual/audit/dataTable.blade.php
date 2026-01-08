
<div class="content-wrapper">

    <div class="card" >

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>
        <!-- /.card-Body -->
        <div class="card-body">


            <form id="filterForm" method="GET" action="{{ route('pages.Schedual.audit.index') }}"
                class="d-flex flex-wrap gap-2">
                @csrf
                <div class="row w-100 align-items-center">

                    <!-- Filter From/To -->
                    <div class="col-md-4 d-flex gap-2">
                        @php
                            use Carbon\Carbon;
                            $defaultFrom = Carbon::now()->toDateString();
                            $defaultTo = Carbon::now()->addMonth(2)->toDateString();
                        @endphp
                        <div class="form-group d-flex align-items-center">
                            <label for="from_date" class="mr-2 mb-0">From:</label>
                            <input type="date" id="from_date" name="from_date"
                                value="{{ request('from_date') ?? $defaultFrom }}" class="form-control" />
                        </div>
                        <div class="form-group d-flex align-items-center">
                            <label for="to_date" class="mr-2 mb-0">To:</label>
                            <input type="date" id="to_date" name="to_date"
                                value="{{ request('to_date') ?? $defaultTo }}" class="form-control" />
                        </div>
                    </div>

                    <!-- Stage Selector -->
                    <div class="col-md-4 d-flex justify-content-center align-items-center"
                        style="gap: 10px; height: 40px;">
                        <input type="hidden" name="stage_code" id="stage_code" value="{{ $stageCode }}">
                        <button type="button" id="prevStage" class="btn btn-link stage-btn"
                            style="font-size: 25px;">&laquo;</button>
                        <span id="stageName" class="fw-bold text-center" style="font-size: 25px;">
                            {{ optional($stages->firstWhere('stage_code', $stageCode))->stage ?? 'Không có công đoạn' }}
                        </span>
                        <button type="button" id="nextStage" class="btn btn-link stage-btn"
                            style="font-size: 25px;">&raquo;</button>
                        
                    </div>
                

                </div>
            </form>

            <table id="data_table_Schedual_list" class="table table-bordered table-striped" style="font-size: 20px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                    <tr>
                        <th>STT</th>
                        <th>Mã Sản Phẩm</th>
                        <th>Sản Phẩm</th>
                        <th>Cỡ lô</th>
                        <th>Số Lô</th>
                        <th>Ngày Dự Kiến KCS</th>
                        <th>Lô Thẩm Định</th>
                        <th>Phòng Sản Xuất</th>
                        <th>Thới Gian Sản Xuất</th>
                        <th>Thời Gian Vệ Sinh</th>
                        <th>Lý Do Tạo Lịch</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                        <th>Xem version cũ</th>

                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} 
                                @if(session('user')['userGroup'] == "Admin") <div> {{ $data->id}} </div> @endif
                            </td>
                            <td>
                                <div> {{ $data->intermediate_code }} </div>
                                <div> {{ $data->finished_product_code }} </div>
                            </td>
                            <td>{{ $data->product_name . '-' . $data->batch }}</td>
                            <td>{{ $data->batch_qty . ' ' . $data->unit_batch_qty }}</td>
                            <td>{{ $data->batch }} </td>

                            <td>
                                <div>{{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') }} </div>
                            </td>
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

                            <td> {{ $data->type_of_change }} </td>


                            <td>
                                <div> {{ $data->schedualed_by }} </div>
                                <div>{{ \Carbon\Carbon::parse($data->schedualed_at)->format('d/m/Y') }} </div>
                            </td>
                            <td>
                                {{$data->version}}
                            </td>

                            {{-- <td class="text-center align-middle">
                                <button type="button" class="btn btn-primary btn-history position-relative"
                                    data-id="{{ $data->stage_plan_id }}" data-toggle="modal" data-target="#historyModal">
                                    <i class="fas fa-history"></i>
                                    <span class="badge badge-danger"
                                        style="position: absolute; top: -5px;  right: -5px; border-radius: 50%;">
                                        {{ $data->version}} 
                                    </span>
                                </button>
                            </td> --}}

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
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>



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

        // $('.btn-history').on('click', function() {
        //         //const id = $(this).data('id');
        //         const id = $(this).data('id');
        //         const history_modal = $('#data_table_history_body')
               
        //         // Xóa dữ liệu cũ
        //         history_modal.empty();
              
        //         // Gọi Ajax lấy dữ liệu history
        //         $.ajax({
        //             url: "{{ route('pages.Schedual.audit.history') }}",
        //             type: 'post',
        //             data: {
        //                 id: id,
        //                 _token: "{{ csrf_token() }}"
        //             },
        //             success: function(res) {
        //                 console.log (res)
        //                 if (res.length === 0) {
        //                     history_modal.append(
        //                         `<tr><td colspan="13" class="text-center">Không có lịch sử</td></tr>`
        //                     );
        //                 } else {
        //                     res.forEach((item, index) => {
                              
        //                     history_modal.append(`
        //                         <tr>
        //                             <td>${index + 1}</td>

        //                             <td>
        //                                 <div>${item.intermediate_code ?? ''}</div>
        //                                 <div>${item.finished_product_code ?? ''}</div>
        //                             </td>

        //                             <td>${item.title ?? ''}</td>

        //                             <td>${item.batch_qty ? item.batch_qty + ' ' + (item.unit_batch_qty ?? '') : ''}</td>
        //                             <td>${item.batch ?? ''}</td>



        //                             <td>${(item.room_name ?? '') + ' - ' + (item.room_code ?? '')}</td>

        //                             <td>
        //                                 ${
        //                                     item.start && item.end
        //                                         ?`<div> ${moment(item.start).format('DD/MM/YYYY HH:mm')} </div> 
        //                                          <div> ${moment(item.end).format('DD/MM/YYYY HH:mm') }</div> `
        //                                         : ''
        //                                 }
        //                             </td>

        //                             <td>
        //                                 ${
        //                                     item.start_clearning && item.end_clearning
        //                                         ? `<div> ${moment(item.start_clearning).format('DD/MM/YYYY HH:mm')} </div>
        //                                          <div> ${ moment(item.end_clearning).format('DD/MM/YYYY HH:mm')} </div>`
        //                                         : ''
        //                                 }
        //                             </td>

        //                             <td>${item.type_of_change ?? ''}</td>

        //                             <td>
        //                                 <div>${item.schedualed_by ?? ''}</div>
        //                                 <div>${item.schedualed_at ? moment(item.schedualed_at).format('DD/MM/YYYY') : ''}</div>
        //                             </td>
        //                             <td>
        //                             <div>${item.version ?? ''}</div>
        //                             </td>
        //                         </tr>
        //                     `);



        //                     });
        //                 }
        //             },
        //             error: function() {
        //                 history_modal.append(
        //                     `<tr><td colspan="13" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
        //                 );
        //             }
        //         });
        // });

    
    });
</script>

<script>
    let stages = @json($stages);
    let currentIndex = stages.findIndex(s => s.stage_code == {{ $stageCode ?? 'null' }});

    const filterForm = document.getElementById("filterForm");
    const stageNameEl = document.getElementById("stageName");
    const stageCodeEl = document.getElementById("stage_code");


    function updateStage() {
        stageNameEl.textContent = stages[currentIndex].stage;
        stageCodeEl.value = stages[currentIndex].stage_code;
    }

    document.getElementById("prevStage").addEventListener("click", function() {
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : stages.length - 1;
        updateStage();
        filterForm.submit();
    });

    document.getElementById("nextStage").addEventListener("click", function() {
        currentIndex = (currentIndex < stages.length - 1) ? currentIndex + 1 : 0;
        updateStage();
        filterForm.submit();
    });
</script>



<script>
    const form = document.getElementById('filterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');

    [fromInput, toInput].forEach(input => {
        input.addEventListener('input', function() {
            form.submit();
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init tất cả stepper
        document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
            new Stepper(stepperEl, {
                linear: false,
                animation: true
            });
        });
    });
</script>
                                    {{-- <td>
                                        <div>${item.expected_date ? moment(item.expected_date).format('DD/MM/YYYY') : ''}</div>
                                    </td>

                                    <td class="text-center align-middle">
                                        ${item.is_val ? '<i class="fas fa-check-circle text-primary fs-4"></i>' : ''}
                                    </td> --}}