{{-- <div class="content-wrapper"> --}}
<div class="card">

  <!-- ====== HEADER ====== -->
  <div >


    <div class="row align-items-center">

      <a href="{{ route ('pages.general.home') }}" class=" mx-5">
        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:35px;">
      </a>

      <div class="mx-auto text-center" style="color: #CDC717">
        <h4>{{ session('title') }}</h4>
      </div>
      <a href="{{ route('logout') }}" class="nav-link text-primary" style="font-size: 20px">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
      <div class="text-white w-100 " style="background-color: #CDC717">
      <div class="animate-scroll inline-block text-xl">
        Thông Báo Chung: Lorem ipsum dolor sit amet consectetur adipisicing elit. Ex, autem? Veniam quasi modi soluta expedita a maxime commodi eius error fugit. Dicta laborum ea quae vero fugit, excepturi exercitationem id.
      </div>
  </div>

  </div>

  <!-- ====== BODY ====== -->
  {{-- <div class="card-body"> --}}
        <div class="table-responsive scroll-container">
            <div class="scroll-content">
                <table class="table table-bordered table-striped" style="border: 3px solid #003A4F;">
                    <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >
                        <tr>
                          <th style="width: 10%">Phòng Sản Xuất</th>
                          <th style="width: 20%">Sản Phẩm Theo Lịch Sản Xuất</th>
                          <th style="width: 20%">Sản Phẩm Đang Sản Xuất</th>
                          <th style="width: 45%" class="text-center">Thông Báo</th>
                        </tr>
                    </thead>
                    <tbody class="text-lg font-bold ">
                      @php $current_stage = 0 @endphp
                      @foreach ($datas as $index => $data)
                          @if ($data->stage_code != $current_stage) <!-- ví dụ khi đến S16 thì chèn hàng ngang -->
                              <tr class=" text-center" style="background-color: #CDC717; color:#f8f9fa; font-size: 30px;">
                                  <td colspan="4"> Công Đoạn {{$data->stage}} </td> <!-- colspan = số cột của table -->
                              </tr>
                          @endif
                           @php $current_stage = $data->stage_code @endphp
                          <tr>
                              <td>{{ $data->code . " - " . $data->name }}</td>
                              <td>{{ $data->code . " - " . $data->name }}</td>
                              <td>{{ $data->code . " - " . $data->name }}</td>
                              <td class="relative overflow-hidden whitespace-nowrap" style="max-width: 100%;">
                                  <div class="animate-scroll inline-block">
                                      {{ $data->code . " - " . $data->name }}
                                  </div>
                              </td>
                          </tr>
                      @endforeach
                  </tbody>
                </table>
            </div>
        </div>
  {{-- </div> --}}


<!-- ====== SCRIPT ====== -->
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
  $(document).ready(function() {
    document.body.style.overflowY = "auto";
  });
</script>

<!-- ====== STYLE ====== -->
<style>
  /* ===== Hiệu ứng chạy ngang (cho thông báo hoặc cột text) ===== */
  @keyframes scrollText {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
  }

  .animate-scroll {
    animation: scrollText 60s linear infinite;
    white-space: nowrap;
  }

  .animate-scroll:hover {
    animation-play-state: paused;
  }

  /* ===== Hiệu ứng cuộn dọc tự động (cho bảng) ===== */
  @keyframes scrollTable {
    0% { transform: translateY(0); }
    100% { transform: translateY(-50%); } /* cuộn nửa bảng, bạn có thể chỉnh */
  }

  .scroll-container {
    max-height: 100vh; /* chiều cao vùng hiển thị bảng */
    overflow: hidden;
    position: relative;
  }

  .scroll-content {
    animation: scrollTable 30s linear infinite;
  }

  .scroll-content:hover {
    animation-play-state: paused;
  }

  /* Làm footer dính ở cuối màn hình */
  .card-footer {
    z-index: 999;
  }

  table thead th {
    position: sticky;
    top: 50px;
    z-index: 2;
    background-color: #f8f9fa;
  }

  .table.table-bordered td,
  .table.table-bordered th {
      border: 3px solid #003A4F; /* tăng độ dày viền ô */
  }
</style>
