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
                    <form id="filterForm" method="GET" action="{{ route('pages.Schedual.list.list') }}" class="d-flex flex-wrap gap-2">
                        @csrf
                        <div class="row w-100 align-items-center mt-3">
                            <!-- Filter From/To -->
                            <div class="col-md-4 d-flex gap-2">
                                @php
                                    use Carbon\Carbon;
                                    $defaultFrom = Carbon::now()->toDateString();
                                    $defaultTo   = Carbon::now() ->addMonth(2)->toDateString();
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
      
                            <div class="col-md-4 d-flex justify-content-end">
                                <!-- Bạn có thể thêm nút submit hoặc button khác ở đây -->
                            </div>
                        </div>
                    </form>

                    <section class="content">
                      <div class="container-fluid">

                        <h5 class="mb-2">Cân Nguyên Liệu</h5>
                        <div class="row">
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-info"><i class="far fa-envelope"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Số Lượng Lô Thực Hiện</span>
                                <span class="info-box-number">1,410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-success"><i class="far fa-flag"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Thời Gian Sản Xuất - Vệ Sinh</span>
                                <span class="info-box-number">410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-warning"><i class="far fa-copy"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Sản Lượng Lý Thuyết</span>
                                <span class="info-box-number">13,648</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-danger"><i class="far fa-star"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Sản Lượng Thực Tế</span>
                                <span class="info-box-number">93,139</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->


                 
                        </div>


                        <h5 class="mb-2">Pha Chế</h5>
                        <div class="row">
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-info"><i class="far fa-envelope"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Messages</span>
                                <span class="info-box-number">1,410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-success"><i class="far fa-flag"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Bookmarks</span>
                                <span class="info-box-number">410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-warning"><i class="far fa-copy"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Uploads</span>
                                <span class="info-box-number">13,648</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-danger"><i class="far fa-star"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Likes</span>
                                <span class="info-box-number">93,139</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                        </div>   

                        <h5 class="mb-2">Trộn Hoàn Tất</h5>
                        <div class="row">
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-info"><i class="far fa-envelope"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Messages</span>
                                <span class="info-box-number">1,410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-success"><i class="far fa-flag"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Bookmarks</span>
                                <span class="info-box-number">410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-warning"><i class="far fa-copy"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Uploads</span>
                                <span class="info-box-number">13,648</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-danger"><i class="far fa-star"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Likes</span>
                                <span class="info-box-number">93,139</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                        </div> 

                        <h5 class="mb-2">Định Hình</h5>
                        <div class="row">
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-info"><i class="far fa-envelope"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Messages</span>
                                <span class="info-box-number">1,410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-success"><i class="far fa-flag"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Bookmarks</span>
                                <span class="info-box-number">410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-warning"><i class="far fa-copy"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Uploads</span>
                                <span class="info-box-number">13,648</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-danger"><i class="far fa-star"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Likes</span>
                                <span class="info-box-number">93,139</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                        </div> 

                        <h5 class="mb-2">Bao Phim</h5>
                        <div class="row">
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-info"><i class="far fa-envelope"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Messages</span>
                                <span class="info-box-number">1,410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-success"><i class="far fa-flag"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Bookmarks</span>
                                <span class="info-box-number">410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-warning"><i class="far fa-copy"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Uploads</span>
                                <span class="info-box-number">13,648</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-danger"><i class="far fa-star"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Likes</span>
                                <span class="info-box-number">93,139</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                        </div> 

                        <h5 class="mb-2">Đóng Gói</h5>
                        <div class="row">
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-info"><i class="far fa-envelope"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Messages</span>
                                <span class="info-box-number">1,410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-success"><i class="far fa-flag"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Bookmarks</span>
                                <span class="info-box-number">410</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-warning"><i class="far fa-copy"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Uploads</span>
                                <span class="info-box-number">13,648</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                          <div class="col-md-3 col-sm-6 col-12">
                            <div class="info-box">
                              <span class="info-box-icon bg-danger"><i class="far fa-star"></i></span>

                              <div class="info-box-content">
                                <span class="info-box-text">Likes</span>
                                <span class="info-box-number">93,139</span>
                              </div>
                              <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                          </div>
                          <!-- /.col -->
                        </div>


                      </div><!-- /.container-fluid -->
                    </section>

                
      
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


{{-- 
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
</script>  --}}



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



