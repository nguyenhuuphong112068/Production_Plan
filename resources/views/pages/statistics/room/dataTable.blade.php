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

          <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"> Hiệu Suất Sử Dụng Phòng</h3>
                    </div>
                <!-- /.card-header -->
                    <div class="card-body" style="height: 100vh;">
                        <table class="table table-bordered">
                        <thead>                  
                            <tr>
                            <th style="width: 10px">#</th>
                            <th>Task</th>
                            <th>Progress</th>
                            <th style="width: 40px">Label</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            <td>1.</td>
                            <td>Update software</td>
                            <td>
                                <div class="progress progress-xs">
                                <div class="progress-bar progress-bar-danger" style="width: 55%"></div>
                                </div>
                            </td>
                            <td><span class="badge bg-danger">55%</span></td>
                            </tr>
                            <tr>
                            <td>2.</td>
                            <td>Clean database</td>
                            <td>
                                <div class="progress progress-xs">
                                <div class="progress-bar bg-warning" style="width: 70%"></div>
                                </div>
                            </td>
                            <td><span class="badge bg-warning">70%</span></td>
                            </tr>
                            <tr>
                            <td>3.</td>
                            <td>Cron job running</td>
                            <td>
                                <div class="progress progress-xs progress-striped active">
                                <div class="progress-bar bg-primary" style="width: 30%"></div>
                                </div>
                            </td>
                            <td><span class="badge bg-primary">30%</span></td>
                            </tr>
                            <tr>
                            <td>4.</td>
                            <td>Fix and squish bugs</td>
                            <td>
                                <div class="progress progress-xs progress-striped active">
                                <div class="progress-bar bg-success" style="width: 90%"></div>
                                </div>
                            </td>
                            <td><span class="badge bg-success">90%</span></td>
                            </tr>
                        </tbody>
                        </table>
                    </div>          
                </div>
            </div>
            <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Sản Lượng </h3>

                            <div class="card-tools">
                            <div class="input-group input-group-sm" style="width: 50%;">
                                <input type="text" name="table_search" class="form-control float-right" placeholder="Search">

                                <div class="input-group-append">
                                <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            </div>
                        </div>
                        
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-0" style="height: 100vh;">
                            <table class="table table-head-fixed text-nowrap">
                            <thead>
                                <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                <td>183</td>
                                <td>John Doe</td>
                                <td>11-7-2014</td>
                                <td><span class="tag tag-success">Approved</span></td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                </tr>
                                <tr>
                                <td>219</td>
                                <td>Alexander Pierce</td>
                                <td>11-7-2014</td>
                                <td><span class="tag tag-warning">Pending</span></td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                </tr>
                                <tr>
                                <td>657</td>
                                <td>Bob Doe</td>
                                <td>11-7-2014</td>
                                <td><span class="tag tag-primary">Approved</span></td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                </tr>
                                <tr>
                                <td>175</td>
                                <td>Mike Doe</td>
                                <td>11-7-2014</td>
                                <td><span class="tag tag-danger">Denied</span></td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                </tr>
                                <tr>
                                <td>134</td>
                                <td>Jim Doe</td>
                                <td>11-7-2014</td>
                                <td><span class="tag tag-success">Approved</span></td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                </tr>
                                <tr>
                                <td>494</td>
                                <td>Victoria Doe</td>
                                <td>11-7-2014</td>
                                <td><span class="tag tag-warning">Pending</span></td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                </tr>
                                <tr>
                                <td>832</td>
                                <td>Michael Doe</td>
                                <td>11-7-2014</td>
                                <td><span class="tag tag-primary">Approved</span></td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                </tr>
                                <tr>
                                <td>982</td>
                                <td>Rocky Doe</td>
                                <td>11-7-2014</td>
                                <td><span class="tag tag-danger">Denied</span></td>
                                <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                    
                    </div>
                </div>

            </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>

   