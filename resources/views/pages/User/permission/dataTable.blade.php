<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="card">

              <div class="card-header mt-4">
                {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}

              </div>

              <!-- /.card-Body -->
              <div class="card-body">
                <table id="data_table_permission" class="table table-bordered table-striped" style="font-size: 16px">
                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                
                    <tr>
                    <th>STT</th>
                    <th>Phân Quyền</th>
                    
                  </tr>
                  </thead>
                  <tbody>
                 
                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td>{{ $data->display_name}}</td>
                     
                    </tr>
                  @endforeach

                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
    </section>
    <!-- /.content -->
  </div>

