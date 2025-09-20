
<nav class="main-header navbar navbar-expand navbar-white navbar-light fixed-top">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
      <li class="nav-item">
          <a class="nav-link ms-5" data-widget="pushmenu" href="#" role="button">
              <i class="fas fa-bars"></i>
          </a>
      </li>
  </ul>

  <!-- Title Center -->
  <div class="mx-auto text-center" style="color: #CDC717">
      <h4>{{ session('title') }}</h4>
  </div>

  <!-- Right User Info + Logout -->
  <ul class="navbar-nav ms-auto">
      <li class="nav-item d-flex flex-column justify-content-center align-items-end me-3 mr-5">
          <span>👤 {{ session('user')['fullName'] }}</span>
          <span>🛡️ {{ session('user')['userGroup'] }}</span>
      </li>
      <li class="nav-item">
          <a href="{{ route('logout') }}" class="nav-link text-primary" style="font-size: 20px">
              <i class="fas fa-sign-out-alt"></i>
          </a>
      </li>
  </ul>
</nav>