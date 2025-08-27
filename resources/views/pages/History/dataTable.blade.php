<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <!-- /.card-header -->
            <div class="card">

              <div class="card-header mt-4">
                {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
              </div>
              <!-- /.card-Body -->
              <div class="card-body">
                <form id="filterForm" method="GET" action="{{ route('pages.History.list') }}" class="d-flex flex-wrap gap-2">
                    @csrf
                    <div class="row w-100 align-items-center">

                        <!-- Filter From/To -->
                        <div class="col-md-4 d-flex gap-2">
                            @php
                                use Carbon\Carbon;
                                $defaultFrom = Carbon::now()->subMonth(1)->toDateString();
                                $defaultTo   = Carbon::now()->toDateString();
                            @endphp
                            <div class="form-group d-flex align-items-center">
                                <label for="from_date" class="mr-2 mb-0">From:</label>
                                <input type="date" id="from_date" name="from_date" value="{{ request('from_date') ?? $defaultFrom }}" class="form-control" />
                            </div>
                            <div class="form-group d-flex align-items-center">
                                <label for="to_date" class="mr-2 mb-0">To:</label>
                                <input type="date" id="to_date" name="to_date" value="{{ request('to_date') ?? $defaultTo }}" class="form-control" />
                            </div>
                        </div>

                        <!-- Stage Selector -->
                        <div class="col-md-4 d-flex justify-content-center align-items-center" style="gap: 10px; height: 40px;">
                            <input type="hidden" name="stage_code" id="stage_code" value="{{ $stageCode }}">
                            <button type="button" id="prevStage" class="btn btn-link stage-btn" style="font-size: 25px;">&laquo;</button>
                            <span id="stageName" class="fw-bold text-center" style="font-size: 25px;">
                                {{ optional($stages->firstWhere('stage_code', $stageCode))->stage ?? 'Không có công đoạn' }}
                            </span>
                            <button type="button" id="nextStage" class="btn btn-link stage-btn" style="font-size: 25px;">&raquo;</button>
                        </div>

                        <!-- Optional Right Side -->
                        <div class="col-md-4 d-flex justify-content-end">
                            <!-- Bạn có thể thêm nút submit hoặc button khác ở đây -->
                        </div>

                    </div>
                </form>
                
                <table id="example1" class="table table-bordered table-striped" style="font-size: 20px">
                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                    <tr>
                        <th>STT</th>
                        <th>Mã Sản Phẩm</th>
                        <th>Sản Phẩm</th>
                        <th>Cở lô</th>
                        <th>Số Lô</th>
                        <th>Ngày Dự Kiến KCS</th>
                        <th>Lô Thẩm Định</th>
                        <th>Phòng Sản Xuất</th>
                        <th>Thới Gian Sản Xuất</th>
                        <th>Thời Gian Vệ Sinh</th>
                        <th>Ghi Chú</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                    </tr>
                  </thead>
                  <tbody>

                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td> 
                          <div> {{ $data->intermediate_code}} </div>
                          <div> {{ $data->finished_product_code}} </div>
                      </td>
                      <td>{{$data->title}}</td>
                      <td>{{$data->batch_qty . " " .  $data->unit_batch_qty}}</td>
                      <td>{{$data->batch}}  </td>
                     
                      <td>
                          <div>{{ \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') }} </div>
                      </td>
                      <td class="text-center align-middle">
                          @if ($data->is_val)
                            <i class="fas fa-check-circle text-primary fs-4"></i>
                          @endif
                      </td>
                      <td> {{ $data->room_name ." - ". $data->room_code}} </td>
                      <td> {{ \Carbon\Carbon::parse($data->start)->format('d/m/Y H:i')  ." - ". \Carbon\Carbon::parse($data->end)->format('d/m/Y H:i') }} </td>
                      <td> {{ \Carbon\Carbon::parse($data->start_clearning)->format('d/m/Y H:i')  ." - ". \Carbon\Carbon::parse($data->end_clearning)->format('d/m/Y H:i') }} </td>

                      <td> {{ $data->note}} </td>

                      <td>
                          <div> {{ $data->schedualed_by}} </div>
                          <div>{{ \Carbon\Carbon::parse($data->schedualed_at)->format('d/m/Y') }} </div>
                      </td>                     

  
                      {{-- <td class="text-center align-middle">  
                        <form class="form-deActive" action="{{ route('pages.category.product.deActive', ['id' => $data->id]) }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-danger" data-name="{{ $data->name }}">
                                <i class="fas fa-lock"></i>
                            </button>
                        </form>
                      </td> --}}
                 

                    </tr>
                  @endforeach

                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>



<script>
  $(document).ready(function() {
    $('#schedual_list').DataTable({
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

    document.getElementById("prevStage").addEventListener("click", function () {
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : stages.length - 1;
        updateStage();
        filterForm.submit();
    });

    document.getElementById("nextStage").addEventListener("click", function () {
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
        input.addEventListener('input', function () { 
            form.submit();
        });
    });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
      // Init tất cả stepper
      document.querySelectorAll('.bs-stepper').forEach(stepperEl => {
          new Stepper(stepperEl, { linear: false, animation: true });
      });
  });

</script>

