{{-- <div class="content-wrapper"> --}}
@extends ('layout.master')

@section('mainContent')
    <!-- ====== HEADER ====== -->
    <div>
        <div class="row align-items-center">

            <a href="{{ route('pages.status.next', ['production' => $production]) }}" class=" mx-5">
                <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:35px;">
            </a>

           
            <div class="mx-auto text-center" style="color: #CDC717;  font-weight: bold; line-height: 0.8; rgba(0,0,0,0.4);">
              <h1>{{ session('title') }} </h1>
            </div>
           

            <a href="{{ route('logout') }}" class="nav-link text-primary mx-4" style="font-size: 20px">
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
    
    <div class="row mt-1">
      
      @if (count($datas) < 25)
        <div class="col-md-12">
          <div class="card">
            <table class="table table-bordered table-striped" style="border: 3px solid #003A4F;">
              <thead style=" color:#003A4F; font-size: 30px; padding: 2px 0;">
                <tr>
                  <th style="width: 13%">Phòng SX</th>
                  <th style="width: 40%">Lịch SX</th>
                  <th style="width: 30%">Đang SX</th>
                  <th style="width: 17%" class="text-center">Thông Báo</th>
                </tr>
              </thead>
              <tbody class="font-bold"  style=" color:#003A4F; font-size: 24px;  padding: 5px; font-weight: bold">
                @php $current_stage = 0; @endphp
                @foreach ($datas as $data)
                  @if ($data->stage_code != $current_stage)
                    <tbody class="font-bold"  style=" color:#003A4F; font-size: 20px;  padding: 0px; font-weight: bold">
                      <td colspan="4">Công Đoạn {{ $data->stage }}</td>
                    </tr>
                  @endif
                  @php 
                      $current_stage = $data->stage_code; 
                      switch ($data->status) {
                          case 0: $color = "#ffffff"; break; // xám - chưa sản xuất
                          case 1: $color = "#46f905ff"; break; // xanh dương - chuẩn bị
                          case 2: $color = "#a1a2a2ff"; break; // xanh lá - đang sản xuất
                          case 3: $color = "#f99e02ff"; break; // đỏ - lỗi/dừng
                         
                      }
                  @endphp
                  <tr>
                    <td style="background-color: {{ $color }};" >{{ $data->room_name }}</td>

                    {{-- sp theo lịch 1--}}
                    <td style="max-width: 250px; overflow: hidden;">
                      <div class="scroll-text-wrapper">
                        <div class="scroll-text unnote">

                          @if ($data->batch)
                          {{ $data->product_name . "_" . $data->batch }}
                          @else
                          {{ $data->product_name }}
                          @endif

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
      @else
          @php
              // Chia dữ liệu thành 2 phần đều nhau
              $half = ceil(count($datas) / 2) - 1;
              //dd (count($datas),$half);
              $leftData = $datas->slice(0, $half);
              $rightData = $datas->slice($half);
          @endphp
        {{-- BẢNG TRÁI --}}
        <div class="col-md-6">
          <div class="card">
            <table class="table table-bordered table-striped" style="border: 3px solid #003A4F;">
              <thead style=" color:#003A4F; font-size: 30px; padding: 2px 0;">
                <tr>
                  <th style="width: 13%">Phòng SX</th>
                  <th style="width: 35%">Lịch SX</th>
                  <th style="width: 5%">TG</th>
                  <th style="width: 30%">Đang SX</th>
                  <th style="width: 5%">TG</th>
                  <th style="width: 17%" class="text-center">Thông Báo</th>
                </tr>
              </thead>
              <tbody class="font-bold"  style=" color:#003A4F; font-size: 24px;  padding: 5px; font-weight: bold">
                @php $current_stage = 0; @endphp
                @foreach ($leftData as $data)
                  @if ($data->stage_code != $current_stage)
                    <tr class="text-center" style="background-color: #CDC717; color:#003A4F; font-size: 24px; padding: 0px; font-weight: bold">
                      <td colspan="6">{{ $stage[$data->stage]  }}</td>
                    </tr>
                  @endif
                  @php 
                      $current_stage = $data->stage_code; 
                      switch ($data->status) {
                          case 0: $color = "#ffffff"; break; // xám - chưa sản xuất
                          case 1: $color = "#46f905ff"; break; // xanh dương - chuẩn bị
                          case 2: $color = "#a1a2a2ff"; break; // xanh lá - đang sản xuất
                          case 3: $color = "#f99e02ff"; break; // đỏ - lỗi/dừng
                         
                      }
                  @endphp
                  <tr>
                    <td style="background-color: {{ $color }};" >{{ $data->room_name }}</td>

                    {{-- sp theo lịch 1--}}
                    <td style="max-width: 250px; overflow: hidden;">
                      <div class="scroll-text-wrapper" >
                        <div class="scroll-text unnote">
                          @if ($data->batch != null)
                            {{ $data->product_name . "_" . $data->batch }}
                          @else
                            {{ $data->product_name }}
                          @endif
                        </div>
                      </div>
                    </td>
                    
                    <td style="max-width: 250px; overflow: hidden;">
                      <div class="scroll-text-wrapper" >
                          <div>12:54 27/10</div>
                          <div>12:54 27/10</div>
                      </div>
                    </td>


                    {{-- sp đang sx 1 --}}
                    <td style="max-width: 250px; overflow: hidden;">
                      <div class="scroll-text-wrapper">
                        <div class="scroll-text unnote">
                            {{ $data->in_production }}
                          
                        </div>
                      </div>
                       <td style="max-width: 250px; overflow: hidden;">
                        <div class="scroll-text-wrapper" >
                            <div>12:54 27/10</div>
                            <div>12:54 27/10</div>
                        </div>
                      </td>
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
                  <th style="width: 13%">Phòng SX</th>
                  <th style="width: 35%">Lịch SX</th>
                  <th style="width: 5%">TG</th>
                  <th style="width: 30%">Đang SX</th>
                  <th style="width: 5%">TG</th>
                  <th style="width: 17%" class="text-center">Thông Báo</th>
                </tr>
              </thead>
              <tbody class="font-bold"  style=" color:#003A4F; font-size: 24px;  padding: 5px; font-weight: bold">
                @php $current_stage = 0; @endphp
                @foreach ($rightData as $data)
                  @if ($data->stage_code != $current_stage)
                    <tr class="text-center" style="background-color: #CDC717; color:#003A4F; font-size: 24px; padding: 0px; font-weight: bold">
                      <td colspan="6">{{ $stage[$data->stage]  }}</td>
                    </tr>
                  @endif
                  @php 
                      $current_stage = $data->stage_code; 
                      switch ($data->status) {
                          case 0: $color = "#ffffff"; break; // xám - chưa sản xuất
                          case 1: $color = "#46f905ff"; break; // xanh dương - chuẩn bị
                          case 2: $color = "#a1a2a2ff"; break; // xanh lá - đang sản xuất
                          case 3: $color = "#f99e02ff"; break; // đỏ - lỗi/dừng
                         
                      }
                  @endphp
                  <tr>
                    <td style="background-color: {{ $color }};" >{{ $data->room_name }}</td>

                    {{-- sp theo lịch 1--}}
                    <td style="max-width: 250px; overflow: hidden;">
                      <div class="scroll-text-wrapper" >
                        <div class="scroll-text unnote">
                          @if ($data->batch != null)
                            {{ $data->product_name . "_" . $data->batch }}
                          @else
                            {{ $data->product_name }}
                          @endif
                        </div>
                      </div>
                    </td>
                    
                    <td style="max-width: 250px; overflow: hidden;">
                      <div class="scroll-text-wrapper" >
                          <div>12:54 27/10</div>
                          <div>12:54 27/10</div>
                      </div>
                    </td>


                    {{-- sp đang sx 1 --}}
                    <td style="max-width: 250px; overflow: hidden;">
                      <div class="scroll-text-wrapper">
                        <div class="scroll-text unnote">
                            {{ $data->in_production }}
                          
                        </div>
                      </div>
                       <td style="max-width: 250px; overflow: hidden;">
                        <div class="scroll-text-wrapper" >
                            <div>12:54 27/10</div>
                            <div>12:54 27/10</div>
                        </div>
                      </td>
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
      @endif
    </div>
    @endsection

    <!-- ====== SCRIPT ====== -->
    <script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
    <script src="{{ asset('js/popper.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
  
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
          let tem = 1;

          if (rowCount < 30){
              tem = 2;
          }          
      
          if (rowCount > 0) {
            
                const rowHeight = Math.floor(totalHeight/rowCount);
               
                allRows.forEach(row => {
                row.style.height = `${rowHeight/tem}px`;
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
        // $(".unnote").each(function() {
        //   const el = this;
        //   // Nếu nội dung dài hơn khung chứa thì thêm class animate
         
        //   if ( el.innerText.length > max_charater_unnote) {
        //     el.classList.add("animate");
        //   }
        // });

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


      setTimeout(() => location.reload(), 60000);

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

        .table th {
          padding: 10px 8px !important;
          line-height: 1.1;
        }
        .table td {
          padding: 8 2px !important;
          text-align: left;
          vertical-align: middle;
          line-height: 1.1;
        }


    </style>

