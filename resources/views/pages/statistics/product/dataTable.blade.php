  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Simple Tables</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Simple Tables</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">

          <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        {{-- <h3 class="card-title"> Sản Lượng Theo Sản Phẩm</h3> --}}
                    <div class="row " >
                        <div class="col-dm-2">
                        <!-- select -->
                            <div class="form-group mr-1">
                                <label>Tháng</label>
                                <select class="custom-select">
                                <option>Tháng 8</option>
                                <option>option 2</option>
                                <option>option 3</option>
                                <option>option 4</option>
                                <option>option 5</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-dm-2 " >
                            <div class="form-group">
                                <label>Năm</label>
                                <select class="custom-select">
                                <option>2025</option>
                                <option>option 2</option>
                                <option>option 3</option>
                                <option>option 4</option>
                                <option>option 5</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    </div>
                <!-- /.card-header -->
                    <div class="card-body" style="height: 100vh;">
                        <table class="table table-bordered">
                        <thead>                  
                            <tr>
                            <th style="width: 10px">STT</th>
                            <th>Mã Sản Phẩm</th>
                            <th>Tên Sản Phẩm</th>
                            <th>Số Lượng Lô</th>
                            <th>Sản Lượng TB Lý Thuyết</th>
                            <th>Sản Lượng TB Thực Tế</th>
                            <th>Hiệu Suất</th>
                            <th>% Hiệu Xuất</th>
                            </tr>
                        </thead>
                        <tbody>

                            <tr>
                            <td>1.</td>
                            <td>010102025</td>
                            <td>Paracetamol EG 1000 mg</td>
                            <td>20</td>
                            <td>100000</td>
                            <td>550000</td>
                            <td>
                                <div class="progress progress-xs">
                                <div class="progress-bar progress-bar-danger" style="width: 80%"></div>
                                </div>
                            </td>
                            <td><span class="badge bg-danger">55%</span></td>
                            </tr>
                            <tr>
                                                        <tr>
                            <td>2.</td>
                            <td>010102025</td>
                            <td>Paracetamol EG 1000 mg</td>
                            <td>20</td>
                            <td>100000</td>
                            <td>550000</td>
                            <td>
                                <div class="progress progress-xs">
                                <div class="progress-bar progress-bar-danger" style="width: 90%"></div>
                                </div>
                            </td>
                            <td><span class="badge bg-danger">55%</span></td>
                            </tr>
                            <tr>
                             <tr>
                            <td>3.</td>
                            <td>010102025</td>
                            <td>Paracetamol EG 1000 mg</td>
                            <td>20</td>
                            <td>100000</td>
                            <td>550000</td>
                            <td>
                                <div class="progress progress-xs">
                                <div class="progress-bar progress-bar-danger" style="width: 95%"></div>
                                </div>
                            </td>
                            <td><span class="badge bg-danger">55%</span></td>
                            </tr>
                            <tr>
                            <td>4.</td>
                            <td>010102025</td>
                            <td>Paracetamol EG 1000 mg</td>
                            <td>20</td>
                            <td>100000</td>
                            <td>550000</td>
                            <td>
                                <div class="progress progress-xs">
                                <div class="progress-bar progress-bar-danger" style="width: 55%"></div>
                                </div>
                            </td>
                            <td><span class="badge bg-danger">55%</span></td>
                            </tr>
                            <tr>                            <tr>
                                
                                



                        </tbody>
                        </table>
                    </div>          
                </div>
            </div>
     
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>

   