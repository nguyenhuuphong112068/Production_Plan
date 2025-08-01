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

                <table id="example1" class="table table-bordered table-striped">

                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                
                    <tr>
                    <th>STT</th>
                    <th>Sản Phẩm</th>
                    <th>Số lô - Công Đoạn</th>
                    <th>Chỉ tiêu</th>
                    <th>Ngày Phân Tích</th>
                    <th>Thiết Bị KN</th>
                    <th>Kiểm Nghiệm Viên</th>
                    <th>Kết Quả</th>
                    <th>{{"BBSL/OOS (Nếu có)"}}</th>
                    <th>Ghi chú</th>                   
                    <th>Người Tạo</th>
                    <th>Ngày Tạo</th>

                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td>{{ $data->code ." - ". $data->name}}</td>
                      <td>{{ $data->batch_no ." - ". $data->stage}}</td>
                      <td>{{ $data->testing}}</td>
                      <td><div> {{ \Carbon\Carbon::parse($data->startDate)->format('d/m/Y H:i') }}   </div> 
                           <div>  {{ \Carbon\Carbon::parse($data->endDate)->format('d/m/Y H:i')}} </div>  </td>
                      <td>{{ $data->instrument_name}}</td>    
                      <td>{{ $data->analyst}}</td>
                      <td>{{ $data->result}}</td>
                      <td>{{ $data->relativeReport}}</td>
                      <td>{{ $data->note}}</td>
                      <td>{{ $data->prepareBy}}</td>
                      <td>{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                      
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

@if (session('success'))
<script>
    Swal.fire({
        title: 'Thành công!',
        text: '{{ session('success') }}',
        icon: 'success',
        timer: 2000, // tự đóng sau 2 giây
        showConfirmButton: false
    });
</script>
@endif

