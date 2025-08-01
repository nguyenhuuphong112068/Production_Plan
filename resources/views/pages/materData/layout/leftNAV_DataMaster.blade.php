  <aside class="main-sidebar sidebar-light-primary elevation-4" style="height: 100vh;";>

    <!-- Brand Logo -->
    <a href="{{ route ('pages.general.home') }}" class="brand-link container d-flex justify-content-center align-items-center">
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
          
          
          <!-- Tên Sản Phẩm -->
          <li class="nav-item">
            <a href="{{route ('pages.materData.productName.list')}}" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Tên Sản Phẩm
                {{-- <span class="right badge badge-danger">New</span>  --}}
              </p>
            </a>
          </li>

           <!-- Chỉ Tiêu Kiểm -->
          <li class="nav-item">
            <a href="{{route ('pages.materData.Testing.list')}}" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Chỉ Tiêu Kiểm
                {{-- <span class="right badge badge-danger">New</span>  --}}
              </p>
            </a>
          </li>         
          
            <!-- Thiêt Bị-->
          <li class="nav-item">
            <a href="{{route ('pages.materData.Instrument.list')}}" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Thiết Bị Kiểm Nghiệm
                {{-- <span class="right badge badge-danger">New</span>  --}}
              </p>
            </a>
          </li>          
  
            <!-- Nhóm -->
          <li class="nav-item">
            <a href="{{route ('pages.materData.Groups.list')}}" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Tổ Kiểm Nghiệm
                {{-- <span class="right badge badge-danger">New</span>  --}}
              </p>
            </a>
          </li>  
          
               <!-- KNV -->
          <li class="nav-item">
            <a href="{{route ('pages.materData.Analyst.list')}}" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Kiểm Nghiệm Viên
                {{-- <span class="right badge badge-danger">New</span>  --}}
              </p>
            </a>
          </li>        




          <!-- Droplist Menu Chỉ Tiêu Kiểm-->
          {{-- <li class="nav-item has-treeview">
             <a href="" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Chỉ Tiêu Kiểm
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{route ('pages.materData.Testing.list')}}" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Danh Sách Chỉ Tiêu Kiểm</p>
                </a>
              </li>

            </ul>
          </li> --}}
    
        </ul>
      </nav>

      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>