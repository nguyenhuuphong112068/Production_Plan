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

<div style="font-size: 16px; margin:0; text-align: center;">
  <span style="display: inline-block; width: 200px; height: 30px; background-color: #46f905; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); font-weight: 600;">
    Đang Sản Xuất
  </span>
  <span style="display: inline-block; width: 200px; height: 30px; background-color: #a1a2a2; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); font-weight: 600;">
    Đang Vệ Sinh
  </span>
  <span style="display: inline-block; width: 200px; height: 30px; background-color: #f99e02; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); font-weight: 600;">
    Đang Bảo Trì
  </span>
  <span style="display: inline-block; width: 200px; height: 30px; background-color: #ff0000; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); color: white; font-weight: 600;">
    Máy Hư
  </span>
  <span style="display: inline-block; width: 200px; height: 30px; background-color: #ffffff; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); font-weight: 600;">
    Không Sản Xuất
  </span>
</div>


        <div class="text-white w-100 " style="background-color: #CDC717">
            <div class="animate-scroll inline-block text-xl text-red">
                <i class="nav-icon fas fa-capsules"></i> <<--- {{ $general_notication?->notification ?? 'Không có thông báo mới!' }} --->> <i class="nav-icon fas fa-tablets"></i>
            </div>
        </div>

    </div>

    <div class="row mt-1">

      @php $now = now();@endphp
      @if (count($datas) < 25)
        <div class="col-md-12">
          <div class="card">
            <div class="d-flex justify-content-between w-100" style="background-color: #CDC717; color:#003A4F; font-size: 24px; padding: 0px; font-weight: bold">
                Cập nhật: {{$lasestupdate[0] ?? '' }}  
            </div>
            <table class="table table-bordered table-striped" style="border: 3px solid #003A4F;">
              <thead style=" color:#003A4F; font-size: 30px; padding: 2px 0;">
                <tr>
                  <th style="width: 13%">Phòng SX</th>
                  <th style="width: 25%">Lịch SX</th>
                  <th style="width: 5%">TG theo Lịch</th>
                  <th style="width: 25%">Đang SX</th>
                  <th style="width: 5%">TG Thực tế</th>
                  <th style="width: 27%" class="text-center">Thông Báo</th>
                </tr>
              </thead>
              <tbody class="font-bold"  style=" color:#003A4F; font-size: 24px;  padding: 5px; font-weight: bold">
                
                @foreach ($datas as $data)
 
                  @php 
                      $current_stage = $data->production_group; 
                      switch ($data->status) {
                          case 0: $color = "#ffffff"; break; // xám - chưa sản xuất
                          case 1: $color = "#46f905ff"; break; // xanh dương - chuẩn bị
                          case 2: $color = "#a1a2a2ff"; break; // xanh lá - đang sản xuất
                          case 3: $color = "#f99e02ff"; break; // đỏ - lỗi/dừng
                          case 4: $color = "#FF0000"; break;
                      }
                  @endphp
                  <tr>
                    <td style="background-color: {{ $color }};" >
                        <div>
                          {{ $data->room_name }}
                        </div>
                        {{-- <div>
                          {{ $data->sheet }}
                        </div> --}}
                    </td>

                    {{-- sp theo lịch 1--}}
                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                          @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                {{$data->title }}
                          @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                {{$data->title_clearning}}
                          @else
                                <div>KSX</div>
                          @endif
                    </td>
                    
                    <td style="max-width: 250px; overflow: hidden;">
                      <div class="scroll-text-wrapper" >
                        @if ($data->start && $data->end && $now->between($data->start, $data->end))
                            <div>{{ \Carbon\Carbon::parse($data->start)->format('H:i d/m') }}</div>
                            <div>{{ \Carbon\Carbon::parse($data->end)->format('H:i d/m') }}</div>
                        @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                            <div>{{ \Carbon\Carbon::parse($data->start_clearning)->format('H:i d/m') }}</div>
                            <div>{{ \Carbon\Carbon::parse($data->end_clearning)->format('H:i d/m') }}</div>
                        @else
                            <div>-</div>
                        @endif
                      </div>
                    </td>


                    {{-- sp đang sx 1 --}}
                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                            {{ $data->in_production}}
                    </td>

                    <td style="max-width: 250px; overflow: hidden;">
                        @if ($data->start && $data->end && $now->between($data->start, $data->end))
                            <div>{{ \Carbon\Carbon::parse($data->start)->format('H:i d/m') }}</div>
                            <div>{{ \Carbon\Carbon::parse($data->end)->format('H:i d/m') }}</div>
                        @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                            <div>{{ \Carbon\Carbon::parse($data->start_clearning)->format('H:i d/m') }}</div>
                            <div>{{ \Carbon\Carbon::parse($data->end_clearning)->format('H:i d/m') }}</div>
                        @else
                            <div>-</div>
                        @endif
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
                  <th style="width: 15%">Phòng Sản xuất</th>
                  <th style="width: 25%">Lịch Sản Xuất</th>
                  <th style="width: 5%">T.Gian LT</th>
                  <th style="width: 25%">Đang Sản Xuất</th>
                  <th style="width: 5%">T.Gian TT</th>
                  <th style="width: 25%" class="text-center">Thông Báo</th>
                </tr>
              </thead>
              <tbody class="font-bold"  style=" color:#003A4F; font-size: 24px;  padding: 5px; font-weight: bold">
                @php $current_stage = null; @endphp
                @foreach ($leftData as $data)

                  @if ($data->production_group != $current_stage)
                    <tr style="background-color: #CDC717; color:#003A4F; font-size: 24px; padding: 0px; font-weight: bold">
                      <td colspan="6">
                        <div class="d-flex justify-content-between w-100">
                          <div class="text-left text-red text-xl">
                            {{ $data->production_group }}
                          </div>
                          @php
                              $info = $lasestupdate[$data->production_group] ?? '';
                              [$user, $datetime] = explode('_', $info . '_');
                          @endphp
                          <div class="text-right">
                            Người cập nhật: {{ $user ?? '' }} <br>
                            Thời gian cập nhật: {{ $datetime ? \Carbon\Carbon::parse($datetime)->format('H:i d/m/Y') : '' }}
                          </div>
                        </div>
                      </td>
                    </tr>
                  @endif

                  @php 
                      $current_stage = $data->production_group; 
                      switch ($data->status) {
                          case 0: $color = "#ffffff"; break; // xám - chưa sản xuất
                          case 1: $color = "#46f905ff"; break; // xanh dương - chuẩn bị
                          case 2: $color = "#a1a2a2ff"; break; // xanh lá - đang sản xuất
                          case 3: $color = "#f99e02ff"; break; // đỏ - lỗi/dừng
                          case 4: $color = "#FF0000"; break;
                      }

                      if (!($data->start_realtime && $data->end_realtime && $now->between($data->start_realtime, $data->end_realtime))){
                        $color = "#ffffff";
                      } 
                        

                  @endphp
                  <tr>
                    <td style="background-color: {{ $color }};" class ="multi-line" >
                       
                        {{ $data->room_name }}
                        
                        {{-- <div>
                          {{ $data->sheet }}
                        </div> --}}
                    </td>

                    {{-- sp theo lịch 1--}}
                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                          @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                {{$data->title }}
                          @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                {{$data->title_clearning}}
                          @else
                                <div>KSX</div>
                          @endif
                    </td>
                    
                    <td style="max-width: 250px; overflow: hidden;">
                      <div class="scroll-text-wrapper" >
                        @if ($data->start && $data->end && $now->between($data->start, $data->end))
                            <div>{{ \Carbon\Carbon::parse($data->start)->format('H:i d/m') }}</div>
                            <div>{{ \Carbon\Carbon::parse($data->end)->format('H:i d/m') }}</div>
                        @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                            <div>{{ \Carbon\Carbon::parse($data->start_clearning)->format('H:i d/m') }}</div>
                            <div>{{ \Carbon\Carbon::parse($data->end_clearning)->format('H:i d/m') }}</div>
                        @else
                            <div>-</div>
                        @endif
                      </div>
                    </td>
                    {{-- sp đang sx 1 --}}
                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">  
                        @if ($data->start_realtime && $data->end_realtime && $now->between($data->start_realtime, $data->end_realtime))
                             {{ $data->in_production}}
                        @else
                            <div>KSX</div>
                        @endif
                    </td>

                    <td style="max-width: 250px; overflow: hidden;">
                        @if ($data->start_realtime && $data->end_realtime && $now->between($data->start_realtime, $data->end_realtime))
                            <div>{{ \Carbon\Carbon::parse($data->start_realtime)->format('H:i d/m') }}</div>
                            <div>{{ \Carbon\Carbon::parse($data->end_realtime)->format('H:i d/m') }}</div>
                        @else
                            <div>-</div>
                        @endif
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
                  <th style="width: 15%">Phòng Sản xuất</th>
                  <th style="width: 25%">Lịch Sản Xuất</th>
                  <th style="width: 5%">T.Gian LT</th>
                  <th style="width: 25%">Đang Sản Xuất</th>
                  <th style="width: 5%">T.Gian TT</th>
                  <th style="width: 25%" class="text-center">Thông Báo</th>
                </tr>
              </thead>
              <tbody class="font-bold"  style=" color:#003A4F; font-size: 24px;  padding: 5px; font-weight: bold">
                @php $current_stage = null; @endphp
                @foreach ($rightData as $data)

                  @if ($data->production_group != $current_stage)
                    <tr style="background-color: #CDC717; color:#003A4F; font-size: 24px; padding: 0px; font-weight: bold">
                      <td colspan="6">
                        <div class="d-flex justify-content-between w-100">
                          <div class="text-left text-red text-xl">
                            {{ $data->production_group }}
                          </div>

                          @php
                                $info = $lasestupdate[$data->production_group] ?? '';
                                [$user, $datetime] = explode('_', $info . '_'); // tránh lỗi nếu null
                          @endphp

                          <div class="text-right">
                            Người cập nhật: {{ $user ?? '' }} <br>
                            Thời gian cập nhật: {{ $datetime ? \Carbon\Carbon::parse($datetime)->format('H:i d/m/Y') : '' }}
                          </div>
                        </div>
                      </td>
                    </tr>
                  @endif

                  @php 
                      $current_stage = $data->production_group; 
                      switch ($data->status) {
                          case 0: $color = "#ffffff"; break; // xám - chưa sản xuất
                          case 1: $color = "#46f905ff"; break; // xanh dương - chuẩn bị
                          case 2: $color = "#a1a2a2ff"; break; // xanh lá - đang sản xuất
                          case 3: $color = "#f99e02ff"; break; // đỏ - lỗi/dừng
                          case 4: $color = "#FF0000"; break;
                      }

                      if (!($data->start_realtime && $data->end_realtime && $now->between($data->start_realtime, $data->end_realtime))){
                        $color = "#ffffff";
                      } 
                  @endphp
                  <tr>
                    <td style="background-color: {{ $color }};" class ="multi-line" >
                       
                        {{ $data->room_name }}
                        
                        {{-- <div>
                          {{ $data->sheet }}
                        </div> --}}
                    </td>

                    {{-- sp theo lịch 1--}}
                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                          @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                {{$data->title }}
                          @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                {{$data->title_clearning}}
                          @else
                                <div>KSX</div>
                          @endif
                    </td>
                    
                    <td style="max-width: 250px; overflow: hidden;">
                      <div class="scroll-text-wrapper" >
                        @if ($data->start && $data->end && $now->between($data->start, $data->end))
                            <div>{{ \Carbon\Carbon::parse($data->start)->format('H:i d/m') }}</div>
                            <div>{{ \Carbon\Carbon::parse($data->end)->format('H:i d/m') }}</div>
                        @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                            <div>{{ \Carbon\Carbon::parse($data->start_clearning)->format('H:i d/m') }}</div>
                            <div>{{ \Carbon\Carbon::parse($data->end_clearning)->format('H:i d/m') }}</div>
                        @else
                            <div>-</div>
                        @endif
                      </div>
                    </td>
                    {{-- sp đang sx 1 --}}
                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                        @if ($data->start_realtime && $data->end_realtime && $now->between($data->start_realtime, $data->end_realtime))
                            {{ $data->in_production}}
                        @else
                            <div>-</div>
                        @endif
                    </td>

                    <td style="max-width: 250px; overflow: hidden;">
                        @if ($data->start_realtime && $data->end_realtime && $now->between($data->start_realtime, $data->end_realtime))
                            <div>{{ \Carbon\Carbon::parse($data->start_realtime)->format('H:i d/m') }}</div>
                            <div>{{ \Carbon\Carbon::parse($data->end_realtime)->format('H:i d/m') }}</div>
                        @else
                            <div>-</div>
                        @endif
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


      //setTimeout(() => location.reload(), 60000);

</script>

    <!-- ====== STYLE ====== -->
    <style>
        /* ===== Hiệu ứng chạy ngang (cho thông báo hoặc cột text) ===== */
        @keyframes scrollText {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-80%);
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
          overflow: hidden;
          text-overflow: clip;      /* hoặc bỏ luôn dòng này */
          word-break: break-word;   /* Ngắt từ khi quá dài */
          line-height: 1.2;
          vertical-align: middle;
        }

        .multi-line {
          white-space: normal;
          word-break: break-word;
          overflow-wrap: anywhere;
        }


    </style>

