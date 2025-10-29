<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<!-- ====== HEADER ====== -->
<div class="content-wrapper">

    <div class="card">
        <div class="row align-items-center">
            <a href="{{ route('pages.status.next', ['production' => $production]) }}" class=" mx-5">
                <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:35px;">
            </a>
            <div class="mx-auto text-center"
                style="color: #CDC717;  font-weight: bold; line-height: 0.8; rgba(0,0,0,0.4);">
                <h1>{{ session('title') }} </h1>
            </div>
            <a href="{{ route('logout') }}" class="nav-link text-primary mx-4" style="font-size: 20px">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
        <div class="text-white w-100" style="background-color: #CDC717; padding: 6px 10px;">
            <div style="display: flex; align-items: center; gap: 10px; white-space: nowrap;">

                @if (user_has_permission(session('user')['userId'], 'create_general_notification', 'boolean'))
                <button class="btn btn-success btn-sm d-flex align-items-center justify-content-center"
                    style="width: 35px; height: 35px; padding: 0; font-weight: bold;"
                    data-toggle="modal"
                    data-target="#notification_Modal"
                    >+</button>
                @endif

                <div class="animate-scroll text-xl text-red" style="overflow: hidden;">
                    {{ $general_notication?->notification ?? 'Không có thông báo mới!' }}
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-1">
        @php $now = now();@endphp
        @if (count($datas) < 25)
            <div class="col-md-12">
                <div class="card">
                    <table class="table table-bordered table-striped" style="border: 3px solid #003A4F;">
                        <thead style=" color:#003A4F; font-size: 30px; padding: 2px 0;">
                            <tr>
                                <th style="width: 15%">Phòng SX</th>
                                <th style="width: 25%">Lịch SX</th>
                                <th style="width: 5%">TG</th>
                                <th style="width: 25%">Đang SX</th>
                                <th style="width: 5%">TG</th>
                                <th style="width: 25%" class="text-center">Thông Báo</th>
                            </tr>
                        </thead>
                        <tbody class="font-bold"
                            style=" color:#003A4F; font-size: 24px;  padding: 5px; font-weight: bold">
                            @php $current_stage = 0; @endphp
                            @foreach ($datas as $data)
                                @if ($data->stage_code != $current_stage)
                                    <tr class="text-center"
                                        style="background-color: #CDC717; color:#003A4F; font-size: 24px; padding: 0px; font-weight: bold">
                                        <td colspan="6">{{ $stage[$data->stage] }}</td>
                                    </tr>
                                @endif
                                @php
                                    $current_stage = $data->stage_code;
                                    switch ($data->status) {
                                        case 0:
                                            $color = '#ffffff';
                                            break; // xám - chưa sản xuất
                                        case 1:
                                            $color = '#46f905ff';
                                            break; // xanh dương - chuẩn bị
                                        case 2:
                                            $color = '#a1a2a2ff';
                                            break; // xanh lá - đang sản xuất
                                        case 3:
                                            $color = '#f99e02ff';
                                            break; // đỏ - lỗi/dừng
                                    }
                                @endphp
                                <tr>
                                    <td style="background-color: {{ $color }};">
                                        <div>
                                            {{ $data->room_name }}
                                        </div>
                                        {{-- <div>
                                            {{ $data->sheet }}
                                        </div> --}}
                                    </td>

                                    {{-- sp theo lịch 1 --}}
                                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                                        @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                            {{ $data->title }}
                                        @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                            {{ $data->title_clearning }}
                                        @else
                                            <div>KSX</div>
                                        @endif
                                    </td>

                                    <td style="max-width: 250px; overflow: hidden;">
                                        <div class="scroll-text-wrapper">
                                            @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                                <div>{{ \Carbon\Carbon::parse($data->start)->format('H:i d/m') }}</div>
                                                <div>{{ \Carbon\Carbon::parse($data->end)->format('H:i d/m') }}</div>
                                            @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                                <div>
                                                    {{ \Carbon\Carbon::parse($data->start_clearning)->format('H:i d/m') }}
                                                </div>
                                                <div>
                                                    {{ \Carbon\Carbon::parse($data->end_clearning)->format('H:i d/m') }}
                                                </div>
                                            @else
                                                <div>-</div>
                                            @endif
                                        </div>
                                    </td>


                                    {{-- sp đang sx 1 --}}
                                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                                        {{ $data->in_production }}
                                    </td>

                                    <td style="max-width: 250px; overflow: hidden;">
                                        @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                            <div>{{ \Carbon\Carbon::parse($data->start)->format('H:i d/m') }}</div>
                                            <div>{{ \Carbon\Carbon::parse($data->end)->format('H:i d/m') }}</div>
                                        @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                            <div>{{ \Carbon\Carbon::parse($data->start_clearning)->format('H:i d/m') }}
                                            </div>
                                            <div>{{ \Carbon\Carbon::parse($data->end_clearning)->format('H:i d/m') }}
                                            </div>
                                        @else
                                            <div>-</div>
                                        @endif
                                    </td>


                                    {{-- thông báo 1 --}}
                                    <td style="max-width: 250px; overflow: hidden;">
                                        <div class="scroll-text-wrapper">
                                            <div class="scroll-text note">
                                                {{ $data->notification }}
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
                        <tbody class="font-bold"
                            style=" color:#003A4F; font-size: 24px;  padding: 5px; font-weight: bold">
                            @php $current_stage = null; @endphp
                            @foreach ($leftData as $data)
                                @if ($data->production_group != $current_stage)
                                    <tr class="text-center" style="background-color: #CDC717; color:#003A4F; font-size: 24px; padding: 0px; font-weight: bold">
                                    <td colspan="6">{{$data->production_group }}</td>
                                    </tr>
                                @endif

                                @php
                                    $current_stage = $data->production_group;
                                    switch ($data->status) {
                                        case 0:
                                            $color = '#ffffff';
                                            break; // xám - chưa sản xuất
                                        case 1:
                                            $color = '#46f905ff';
                                            break; // xanh dương - chuẩn bị
                                        case 2:
                                            $color = '#a1a2a2ff';
                                            break; // xanh lá - đang sản xuất
                                        case 3:
                                            $color = '#f99e02ff';
                                            break; // đỏ - lỗi/dừng
                                    }
                                @endphp
                                <tr>
                                    <td style="background-color: {{ $color }};">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                          <button class="btn btn-success btn-sm btn-plus" 
                                                  style="width: 20px; height: 20px; padding: 0; line-height: 0;"
                                                  data-room_name ="{{ $data->room_name }}"
                                                  data-room_id ="{{ $data->room_id }}"
                                                  data-in_production = "{{ $data->title}}"
                                                  data-toggle="modal"
                                                  data-target="#Modal"
                                                  
                                                  >+</button>
                                          <span>{{ $data->room_name }}</span>
                                        </div>
                                        {{-- <div>
                                            {{ $data->sheet }}
                                        </div> --}}
                                    </td>

                                    {{-- sp theo lịch 1 --}}
                                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                                        @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                            {{ $data->title }}
                                        @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                            {{ $data->title_clearning }}
                                        @else
                                            <div>KSX</div>
                                        @endif
                                    </td>

                                    <td style="max-width: 250px; overflow: hidden;">
                                        <div class="scroll-text-wrapper">
                                            @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                                <div>{{ \Carbon\Carbon::parse($data->start)->format('H:i d/m') }}</div>
                                                <div>{{ \Carbon\Carbon::parse($data->end)->format('H:i d/m') }}</div>
                                            @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                                <div>
                                                    {{ \Carbon\Carbon::parse($data->start_clearning)->format('H:i d/m') }}
                                                </div>
                                                <div>
                                                    {{ \Carbon\Carbon::parse($data->end_clearning)->format('H:i d/m') }}
                                                </div>
                                            @else
                                                <div>-</div>
                                            @endif
                                        </div>
                                    </td>


                                    {{-- sp đang sx 1 --}}
                                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                                        {{ $data->in_production }}
                                    </td>

                                    <td style="max-width: 250px; overflow: hidden;">
                                        @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                            <div>{{ \Carbon\Carbon::parse($data->start)->format('H:i d/m') }}</div>
                                            <div>{{ \Carbon\Carbon::parse($data->end)->format('H:i d/m') }}</div>
                                        @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                            <div>{{ \Carbon\Carbon::parse($data->start_clearning)->format('H:i d/m') }}
                                            </div>
                                            <div>{{ \Carbon\Carbon::parse($data->end_clearning)->format('H:i d/m') }}
                                            </div>
                                        @else
                                            <div>-</div>
                                        @endif
                                    </td>


                                    {{-- thông báo 1 --}}
                                    <td style="max-width: 250px; overflow: hidden;">
                                        <div class="scroll-text-wrapper">
                                            <div class="scroll-text note">
                                                {{ $data->notification }}
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
                        <tbody class="font-bold"
                            style=" color:#003A4F; font-size: 24px;  padding: 5px; font-weight: bold">
                            @php $current_stage = null; @endphp
                            @foreach ($rightData as $data)
                                @if ($data->production_group != $current_stage)
                                    <tr class="text-center" style="background-color: #CDC717; color:#003A4F; font-size: 24px; padding: 0px; font-weight: bold">
                                    <td colspan="6">{{$data->production_group }}</td>
                                    </tr>
                                @endif

                                @php
                                    $current_stage = $data->production_group;
                                    switch ($data->status) {
                                        case 0:
                                            $color = '#ffffff';
                                            break; // xám - chưa sản xuất
                                        case 1:
                                            $color = '#46f905ff';
                                            break; // xanh dương - chuẩn bị
                                        case 2:
                                            $color = '#a1a2a2ff';
                                            break; // xanh lá - đang sản xuất
                                        case 3:
                                            $color = '#f99e02ff';
                                            break; // đỏ - lỗi/dừng
                                    }
                                @endphp
                                <tr>
                                    <td style="background-color: {{ $color }};">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                          <button class="btn btn-success btn-sm btn-plus" 
                                                  style="width: 20px; height: 20px; padding: 0; line-height: 0;"
                                                  data-room_name ="{{ $data->room_name }}"
                                                  data-room_id ="{{ $data->room_id }}"
                                                  data-in_production = "{{ $data->title}}"
                                                  data-toggle="modal"
                                                  data-target="#Modal"
                                                  
                                                  >+</button>
                                          <span>{{ $data->room_name }}</span>
                                        </div>
                                        {{-- <div>
                                            {{ $data->sheet }}
                                        </div> --}}
                                    </td>

                                    {{-- sp theo lịch 1 --}}
                                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                                        @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                            {{ $data->title }}
                                        @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                            {{ $data->title_clearning }}
                                        @else
                                            <div>KSX</div>
                                        @endif
                                    </td>

                                    <td style="max-width: 250px; overflow: hidden;">
                                        <div class="scroll-text-wrapper">
                                            @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                                <div>{{ \Carbon\Carbon::parse($data->start)->format('H:i d/m') }}</div>
                                                <div>{{ \Carbon\Carbon::parse($data->end)->format('H:i d/m') }}</div>
                                            @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                                <div>
                                                    {{ \Carbon\Carbon::parse($data->start_clearning)->format('H:i d/m') }}
                                                </div>
                                                <div>
                                                    {{ \Carbon\Carbon::parse($data->end_clearning)->format('H:i d/m') }}
                                                </div>
                                            @else
                                                <div>-</div>
                                            @endif
                                        </div>
                                    </td>


                                    {{-- sp đang sx 1 --}}
                                    <td style="max-width: 250px; overflow: hidden;" class ="multi-line">
                                        {{ $data->in_production }}
                                    </td>

                                    <td style="max-width: 250px; overflow: hidden;">
                                        @if ($data->start && $data->end && $now->between($data->start, $data->end))
                                            <div>{{ \Carbon\Carbon::parse($data->start)->format('H:i d/m') }}</div>
                                            <div>{{ \Carbon\Carbon::parse($data->end)->format('H:i d/m') }}</div>
                                        @elseif ($data->start_clearning && $data->end_clearning && $now->between($data->start_clearning, $data->end_clearning))
                                            <div>{{ \Carbon\Carbon::parse($data->start_clearning)->format('H:i d/m') }}
                                            </div>
                                            <div>{{ \Carbon\Carbon::parse($data->end_clearning)->format('H:i d/m') }}
                                            </div>
                                        @else
                                            <div>-</div>
                                        @endif
                                    </td>


                                    {{-- thông báo 1 --}}
                                    <td style="max-width: 250px; overflow: hidden;">
                                        <div class="scroll-text-wrapper">
                                            <div class="scroll-text note">
                                                {{ $data->notification }}
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
            const rowCount = allRows.length / 2;
            let tem = 1;

            if (rowCount < 30) {
                tem = 2;
            }

            if (rowCount > 0) {
                const rowHeight = Math.floor(totalHeight / rowCount);
                allRows.forEach(row => {
                    row.style.height = `${rowHeight/tem}px`;
                });

                max_charater_unnote = 27;
                max_charater = 45;

                if (totalHeight > 1500) {
                    max_charater_unnote = 45;
                }

                if (totalHeight > 1500) {
                    max_charater = 85;
                }
                //alert (totalHeight)
            }
        };

        // Kiểm tra từng dòng .scroll-text trong bảng
        $(".unnote").each(function() {
            const el = this;
            // Nếu nội dung dài hơn khung chứa thì thêm class animate

            if (el.innerText.length > max_charater_unnote) {
                el.classList.add("animate");
            }
        });

        $(".note").each(function() {
            const el = this;
            // Nếu nội dung dài hơn khung chứa thì thêm class animate
            if (el.innerText.length > max_charater) {
                el.classList.add("animate");
            }
        });

        // Gọi khi tải trang và khi thay đổi kích thước
        adjustRowHeight();
        window.addEventListener('resize', adjustRowHeight);



        $('.btn-plus').click(function() {

            const button = $(this);
            const modal = $('#Modal');
            //alert (button.data('in_production'))
            let  lastStatusRoom  = null;

            $.ajax({
                    url: "{{ route('pages.status.getLastStatusRoom') }}",
                    type: 'post',
                    data: {
                        room_id: button.data('room_id'),
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                      lastStatusRoom = res.last_row;
                      
                      modal.find('input[name="start"]').val(res.last_row.start);
                      modal.find('input[name="end"]').val(res.last_row.end);
                      modal.find('select[name="in_production"]').val(res.last_row.in_production);
                      modal.find('textarea[name="notification"]').val(res.last_row.notification);

                      modal.find(`input[name="status"][value="${lastStatusRoom.status}"]`).prop('checked', true);

                      // Nếu "sheet" là chuỗi như "Đầu Ca", "Giữa Ca", "Cuối Ca", "NA"
                    //   switch (lastStatusRoom.sheet) {
                    //       case "Đầu Ca":
                    //           modal.find('input[name="sheet"][value="1"]').prop('checked', true);
                    //           break;
                    //       case "Giữa Ca":
                    //           modal.find('input[name="sheet"][value="2"]').prop('checked', true);
                    //           break;
                    //       case "Cuối Ca":
                    //           modal.find('input[name="sheet"][value="3"]').prop('checked', true);
                    //           break;
                    //       default:
                    //           modal.find('input[name="sheet"][value="0"]').prop('checked', true);
                    //   }

                    //   // Nếu "step_batch" là chuỗi như "Đầu Lô", "Giữa Lô", "Cuối Lô", "NA"
                    //   switch (lastStatusRoom.step_batch) {
                    //       case "Đầu Lô":
                    //           modal.find('input[name="step_batch"][value="1"]').prop('checked', true);
                    //           break;
                    //       case "Giữa Lô":
                    //           modal.find('input[name="step_batch"][value="2"]').prop('checked', true);
                    //           break;
                    //       case "Cuối Lô":
                    //           modal.find('input[name="step_batch"][value="3"]').prop('checked', true);
                    //           break;
                    //       default:
                    //           modal.find('input[name="step_batch"][value="0"]').prop('checked', true);
                    //   }


                    }
            });
            

            modal.find('input[name="room_name"]').val(button.data('room_name'));
            modal.find('select [name="in_production"]').val(button.data('in_production'));

        });

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

        0%,
        10% {
            transform: translateX(0%);
        }

        50% {
            transform: translateX(-50%);
        }

        90%,
        100% {
            transform: translateX(0%);
        }
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
