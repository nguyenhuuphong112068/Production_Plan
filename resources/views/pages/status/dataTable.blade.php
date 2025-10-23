{{-- <div class="content-wrapper"> --}}


    <!-- ====== HEADER ====== -->
    <div>
        <div class="row align-items-center">

            <a href="{{ route('pages.general.home') }}" class=" mx-5">
                <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:35px;">
            </a>

            <div class="mx-auto text-center" style="color: #CDC717;  font-weight: bold; line-height: 0.8; text-shadow: 8px 8px 20px rgba(0,0,0,0.4);">
              <h1>{{ session('title') }} </h1>
            </div>
            <a href="{{ route('logout') }}" class="nav-link text-primary" style="font-size: 20px">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
        <div class="text-white w-100 " style="background-color: #CDC717">
            <div class="animate-scroll inline-block text-xl text-red">
                Thông Báo Chung: Lorem ipsum dolor sit amet consectetur adipisicing elit. Ex, autem? Veniam quasi modi
                soluta expedita a maxime commodi eius error fugit. Dicta laborum ea quae vero fugit, excepturi
                exercitationem id.
            </div>
        </div>

    </div>
    
    @php
        // Chia dữ liệu thành 2 phần đều nhau
        $half = ceil(count($datas) / 2) - 1;
        //dd (count($datas),$half);
        $leftData = $datas->slice(0, $half);
        $rightData = $datas->slice($half);
    @endphp

    <div class="row">
      {{-- BẢNG TRÁI --}}
      <div class="col-md-6">
        <div class="card">
          <table class="table table-bordered table-striped" style="border: 3px solid #003A4F;">
            <thead style=" color:#003A4F; font-size: 30px; padding: 2px 0;">
              <tr>
                <th style="width: 13%">Phòng SX</th>
                <th style="width: 20%">Sản Phẩm Theo Lịch SX</th>
                <th style="width: 20%">Sản Phẩm Đang SX</th>
                <th style="width: 42%" class="text-center">Thông Báo</th>
              </tr>
            </thead>
            <tbody class="font-bold"  style=" color:#003A4F; font-size: 24px;  padding: 0px; font-weight: bold">
              @php $current_stage = 0; @endphp
              @foreach ($leftData as $data)
                @if ($data->stage_code != $current_stage)
                  <tr class="text-center" style="background-color: #CDC717; color:#003A4F; font-size: 24px; padding: 0px; font-weight: bold">
                    <td colspan="4">Công Đoạn {{ $data->stage }}</td>
                  </tr>
                @endif
                @php 
                    $current_stage = $data->stage_code; 
                    switch ($data->status) {
                        case 0: $color = "#6c757d"; break; // xám - chưa sản xuất
                        case 1: $color = "#46f905ff"; break; // xanh dương - chuẩn bị
                        case 2: $color = "#a1a2a2ff"; break; // xanh lá - đang sản xuất
                        case 3: $color = "#f99e02ff"; break; // đỏ - lỗi/dừng
                        default: $color = "#CDC717"; break; // mặc định
                    }
                @endphp
                <tr>
                  <td style="background-color: {{ $color }};" >{{ $data->room_name }}</td>

                  {{-- sp theo lịch 1--}}
                  <td style="max-width: 250px; overflow: hidden;">
                    <div class="scroll-text-wrapper">
                      <div class="scroll-text unnote">
                        {{ $data->product_name . "_" . $data->batch }}
                      </div>
                    </div>
                  </td>


                  {{-- sp đang sx 1 --}}
                  <td style="max-width: 250px; overflow: hidden;">
                    <div class="scroll-text-wrapper">
                      <div class="scroll-text unnote">
                        {{ $data->in_production}}
                      </div>
                    </div>
                  </td>


                  {{-- thông báo 1 --}}
                  <td style="max-width: 250px; overflow: hidden;">
                    <div class="scroll-text-wrapper">
                      <div class="scroll-text note">
                        {{ $data->notification}}
                      </div>
                    </div>
                  </td>


                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{-- BẢNG PHẢI --}}
      <div class="col-md-6">
        <div class="card">
          <table class="table table-bordered table-striped" style="border: 3px solid #003A4F;">
            <thead style=" color:#003A4F; font-size: 30px; padding: 2px 0;">
              <tr>
                <th style="width: 12%">Phòng SX</th>
                <th style="width: 20%">Sản Phẩm Theo Lịch SX</th>
                <th style="width: 20%">Sản Phẩm Đang SX</th>
                <th style="width: 43%" class="text-center">Thông Báo</th>
              </tr>
            </thead>
            <tbody class="font-bold"  style=" color:#003A4F; font-size: 24px;  padding: 0px; font-weight: bold">
              @php $current_stage = 0; @endphp
              @foreach ($rightData as $data)
                @if ($data->stage_code != $current_stage)
                  <tr class="text-center" style="background-color: #CDC717; color:#003A4F; font-size: 24px; padding: 0px; font-weight: bold">
                    <td colspan="4">Công Đoạn {{ $data->stage }}</td>
                  </tr>
                @endif
                @php $current_stage = $data->stage_code; @endphp
               <tr>
                  <td style="background-color: {{ $color }};" >{{ $data->room_name }}</td>

                  {{-- sp theo lịch 2--}}
                  <td style="max-width: 250px; overflow: hidden;">
                    <div class="scroll-text-wrapper">
                      <div class="scroll-text unnote">
                        {{ $data->product_name . "_" . $data->batch }}
                      </div>
                    </div>
                  </td>


                  {{-- sp đang sx 2 --}}
                  <td style="max-width: 250px; overflow: hidden;">
                    <div class="scroll-text-wrapper">
                      <div class="scroll-text unnote">
                        {{ $data->in_production}}
                      </div>
                    </div>
                  </td>


                  {{-- thông báo 2 --}}
                  <td style="max-width: 250px; overflow: hidden;">
                    <div class="scroll-text-wrapper">
                      <div class="scroll-text note">
                         {{ $data->notification}}
                      </div>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>



    <!-- ====== SCRIPT ====== -->
    <script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
    <script src="{{ asset('js/popper.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

    <script>
      $(document).ready(function() {
        // Cho phép cuộn trang
        document.body.style.overflowY = "auto";
        max_charater_unnote = 27;
        max_charater = 45;

        const adjustRowHeight = () => {
          const totalHeight = window.innerHeight - 180;
          const allRows = document.querySelectorAll("tbody tr");
          const rowCount = allRows.length/2;
              //alert (totalHeight)
          if (rowCount > 0) {
                const rowHeight = Math.floor(totalHeight / rowCount);
                allRows.forEach(row => {
                  row.style.height = `${rowHeight}px`;
          });

          max_charater_unnote = 27;
          max_charater = 45;
          
          if (totalHeight > 1500){
            max_charater_unnote = 45;
          }

          if (totalHeight > 1500){
            max_charater = 85;
          }
          //alert (totalHeight)
        }};
        
        // Kiểm tra từng dòng .scroll-text trong bảng
        $(".unnote").each(function() {
          const el = this;
          // Nếu nội dung dài hơn khung chứa thì thêm class animate
         
          if ( el.innerText.length > max_charater_unnote) {
            el.classList.add("animate");
          }
        });

        $(".note").each(function() {
          const el = this;
          // Nếu nội dung dài hơn khung chứa thì thêm class animate
          if ( el.innerText.length > max_charater) {
            el.classList.add("animate");
          }
        });
      
          // Gọi khi tải trang và khi thay đổi kích thước
          adjustRowHeight();
          window.addEventListener('resize', adjustRowHeight);

      });
    </script>

    <!-- ====== STYLE ====== -->
    <style>
        /* ===== Hiệu ứng chạy ngang (cho thông báo hoặc cột text) ===== */
        @keyframes scrollText {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(0%);
            }
        }

        .animate-scroll {
            animation: scrollText 30s linear infinite;
            white-space: nowrap;
        }

        .animate-scroll:hover {
            animation-play-state: paused;
        }

    

        .table.table-bordered td,
        .table.table-bordered th {
            border: 3px solid #003A4F;
            /* tăng độ dày viền ô */
        }

        /* Giới hạn vùng hiển thị trong ô bảng */
        td {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        

        /* Chạy chữ trong ô */

       .scroll-text {
          display: inline-block;
          white-space: nowrap;
        }

        .scroll-text-wrapper {
          position: relative;
          overflow: hidden;
          white-space: nowrap;
          width: 100%;
        }

        .scroll-text.animate {
          animation: scrollTextLoop 20s linear infinite;
          padding-right: 50px;
        }

        @keyframes scrollTextLoop {
          0% , 10% { transform: translateX(0%); }
          50% { transform: translateX(-50%); }
          90%, 100% { transform: translateX(0%); }
        }

      
        .table td, .table th {
          padding: 0 8px !important;
          text-align: center;
          vertical-align: middle;
          line-height: 1.1;
        }


    </style>