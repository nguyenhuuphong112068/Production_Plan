  <nav class="main-header navbar navbar-expand navbar-white navbar-light fixed-top">
    <!-- Left navbar links -->
    <div class="col-sm-2 md-2">
      <ul class="navbar-nav">

        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i> </a>
        </li>

        <li class="nav-item d-none d-sm-inline-block">
          <a href="{{ route ('logout') }}" class="nav-link">Log Out</a>
        </li>
    
      </ul>
    </div>
    <!-- Title Center -->
    <div class="col-sm-8 md-8 d-flex justify-content-center align-items-center" style = "color: #CDC717">
          <h4> {{ session('title') }} </h4>
    </div>

    <!-- Right User infor  -->
     @if(session('user'))
    <div class="col-sm-2 md-2">
        <div class="info">
          <a href="#" class="d-block"> 🧑‍💼 {{ session('user')['fullName'] }} </a>
          <a href="#" class="d-block"> 🛡️ {{ session('user')['userGroup'] }} </a>
        </div>
    </div>
    @endif

  </nav>