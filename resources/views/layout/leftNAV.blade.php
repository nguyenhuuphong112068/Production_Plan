  {{-- css cho nút về đầu trang --}}
  <style>
    #backToTopBtn {
      display: none; /* Ẩn ban đầu */
      position: fixed;
      bottom: 20px;
      left: 20px; /* Góc dưới bên trái */
      width: 50px;
      height: 50px;
      background-color: rgba(77, 240, 13, 0.4); /* Màu mờ */
      color: white;
      border: none;
      border-radius: 8px; /* Bo góc nhẹ */
      font-size: 24px;
      cursor: pointer;
      z-index: 999;
      transition: background-color 0.1s;
    }





  </style>
  
  <aside class="main-sidebar sidebar-light-primary elevation-4" style="height: 100vh;";>

    <!-- Brand Logo -->
    <a href="{{ route ('pages.general.home')}}" class="brand-link container d-flex justify-content-center align-items-center">
      <img src="{{ asset('img/iconstella.svg') }}"
           alt="AdminLTE Logo"
           style="opacity: .8 ; max-width:43px; hight: auto">
    </a>

   <!-- Sidebar user (optional) -->
    {{-- <div class="user-panel mt-3 pb-3 mb-3 container d-flex justify-content-center align-items-center">
      
    </div> --}}

    <!-- Sidebar -->
    <div class="sidebar" >

      <!-- Sidebar Menu -->
      <nav class="mt-2" >

        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

          <!-- Droplist Menu Chuyển Phân Xưởng  -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-capsules"></i>
              <p>
                {{ session('user')['production_name'] }}
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            
            <ul class="nav nav-treeview">
              <li class="nav-item">
                 <a href="{{ route('switch', ['production_code' => 'PXV1', 'redirect' => url()->current()]) }}" class="nav-link">
                  <i class="far fa-circle nav-icon {{ session('user')['production_code'] == "PXV1" ? "text-danger":""}}"></i>
                  <p>PXV1</p>
                </a>
              </li>

              <li class="nav-item">
                  <a href="{{ route('switch', ['production_code' => 'PXV2', 'redirect' => url()->current()]) }}" class="nav-link">
                  <i class="far fa-circle nav-icon {{ session('user')['production_code'] == "PXV2" ? "text-danger":""}}"></i>
                  <p>PXV2</p>
                </a>
              </li>

              <li class="nav-item">
                  <a href="{{ route('switch', ['production_code' => 'PXVH', 'redirect' => url()->current()]) }}" class="nav-link">
                  <i class="far fa-circle nav-icon {{ session('user')['production_code'] == "PXVH" ? "text-danger":""}}"></i>
                  <p>PXVH</p>
                </a>
              </li>

               <li class="nav-item">
                  <a href="{{ route('switch', ['production_code' => 'PXTN', 'redirect' => url()->current()]) }}" class="nav-link">
                  <i class="far fa-circle nav-icon {{ session('user')['production_code'] == "PXTN" ? "text-danger":""}}" ></i>
                  <p>PXTN</p>
                </a>
              </li> 
              
              <li class="nav-item">
                  <a href="{{ route('switch', ['production_code' => 'PXDN', 'redirect' => url()->current()])}}" class="nav-link">
                  <i class="far fa-circle nav-icon {{ session('user')['production_code'] == "PXDN" ? "text-danger":""}}"></i>
                  <p>PXDN</p>
                </a>
              </li>
            </ul>
          </li>
          
      
          <!-- Droplist Menu Dữ Liệu Gốc  -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-database"></i>
              <p>
                Dữ Liệu Gốc
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route ('pages.materData.productName.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Tên Sản Phẩm</p>
                </a>
              </li>

              <li class="nav-item">
                <a href="{{ route ('pages.materData.room.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Phòng Sản Xuất</p>
                </a>
              </li>

               <li class="nav-item">
                <a href="{{ route ('pages.materData.Dosage.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Dạng Bào Chế</p>
                </a>
              </li> 
              
              <li class="nav-item">
                <a href="{{ route ('pages.materData.Unit.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Đơn Vị Tính</p>
                </a>
              </li>
              
              <li class="nav-item">
                <a href="{{ route ('pages.materData.Market.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Thị Trường</p>
                </a>
              </li>
              
              <li class="nav-item">
                <a href="{{ route ('pages.materData.Specification.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Qui Cách Sản Phẩm</p>
                </a>
              </li>
              
              {{-- <li class="nav-item">
                <a href="{{ route ('pages.materData.Instrument.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Thiết Bị</p>
                </a>
              </li> --}}


            </ul>
          </li>


            <!-- Droplist Menu Danh Muc  -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-newspaper"></i>
              <p>
                Danh Mục 
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            
            <ul class="nav nav-treeview">
              
              <li class="nav-item">
                <a href="{{ route ('pages.category.intermediate.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Bán Thành Phẩm</p>
                </a>
              </li>

              <li class="nav-item">
                <a href="{{ route ('pages.category.product.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Thành Phẩm</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route ('pages.category.maintenance.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Bảo Trì - Hiệu Chuẩn</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Định Mức  -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>
                Định Mức 
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            
            <ul class="nav nav-treeview">
              
              <li class="nav-item">
                <a href="{{ route ('pages.quota.production.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Sản Xuất</p>
                </a>
              </li>

              {{-- <li class="nav-item">
                <a href="{{ route ('pages.category.product.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Bảo Trì</p>
                </a>
              </li> --}}

            </ul>
          </li>

            <!-- Droplist Kế Hoạch-->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              
              <i class="nav-icon fas fa-file-import"></i>
              <p>
                Kế Hoạch
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            
            <ul class="nav nav-treeview">
              
              <li class="nav-item">
                <a href="{{ route ('pages.plan.production.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Kế Hoạch Sản Xuất</p>
                </a>
              </li>

                            <li class="nav-item">
                <a href="{{ route ('pages.plan.maintenance.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Kế Hoạch Bảo Trì</p>
                </a>
              </li>

            </ul>
          </li>

          <!-- Droplist Menu Lập Lịch -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>
                Lập Lịch
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            
            <ul class="nav nav-treeview">

              <li class="nav-item">
                <a href="/Schedual" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p> Lập Lịch </p>
                </a>
              </li>

              <li class="nav-item">
                <a href="{{ route ('pages.Schedual.list.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p> Danh Sách Lịch</p>
                </a>
              </li>

              <li class="nav-item">
                <a href="{{ route ('pages.Schedual.step.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Tiến Trình Sản Xuất</p>
                </a>
              </li>
            
            </ul>
          </li>

          <!-- Droplist Thống Kê -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>
                Thống Kê
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            
            <ul class="nav nav-treeview">

              <li class="nav-item">
                <a href="{{ route ('pages.statistics.product.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p> Sản Phẩm </p>
                </a>
              </li>

              <li class="nav-item">
                <a href="{{ route ('pages.statistics.room.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p> Phòng Sản Xuất </p>
                </a>
              </li>

              <li class="nav-item">
                <a href="{{ route ('pages.statistics.stage.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p> Công Đoạn Sản Xuất  </p>
                </a>
              </li>
            
            </ul>
          </li>

          <!-- History-->
          <li class="nav-item">
            <a href="{{ route ('pages.History.list') }}" class="nav-link">
              <i class="nav-icon fas fa-history"></i>
              <p>
                Lịch Sử Sản Xuất
              </p>
            </a>
          </li>

            <!-- User-->
            <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user"></i>
              <p>
                User Policy
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route ('pages.User.user.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p> Danh Sách User </p>
                </a>
              </li>

              <li class="nav-item">
                <a href="{{ route ('pages.User.role.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p> Nhóm Phân Quyền </p>
                </a>
              </li>

              <li class="nav-item">
                <a href="{{ route ('pages.User.permission.list') }}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p> Danh Sách Quyền </p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Audit Trial-->
          <li class="nav-item">
            <a href="{{ route ('pages.AuditTrail.list') }}" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Audit Trail
              </p>
            </a>
          </li>


          <li class="nav-item">
            <a href="/Schedual/test" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Test Route
              </p>
            </a>
          </li>

        </ul>

  
      </nav>

      <button onclick="scrollToTop()" id="backToTopBtn" title="Trở về đầu trang" class = "btn btn-success">
          <i class="fas fa-chevron-up"></i>
      </button>


      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <script>
    // Hiện nút khi scroll xuống 300px
    window.onscroll = function () {
      const btn = document.getElementById("backToTopBtn");
      if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
        btn.style.display = "block";
      } else {
        btn.style.display = "none";
      }
    };

    // Cuộn mượt về đầu trang
    function scrollToTop() {
      window.scrollTo({
        top: 0,
        behavior: "smooth"
      });
    }
</script>
