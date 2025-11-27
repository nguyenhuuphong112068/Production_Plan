
<link rel="stylesheet" href="{{asset ('dataTable/plugins/fontawesome-free/css/all.min.css')}} ">
<link rel="stylesheet" href="{{asset ('dataTable/plugins/fontawesome-free/css/all.min.css')}}">
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<!-- ====== HEADER ====== -->
<div class="content-wrapper">

    <div class="card">
        <div class="card-body">

        <div class="row">
            <div class="col-md-3">
                <a href="{{ route('pages.status.history.next', ['production' => $production]) }}" class=" mx-5">
                    <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:35px;">
                </a>

            </div>

            <div class="col-md-6">
                <div class="mx-auto text-center" style="color: #CDC717;  font-weight: bold; line-height: 0.8; rgba(0,0,0,0.4);">
                <h1 class="inline-block">{{ session('title') }} </h1>
                </div>
            </div>
            
            <div class="col-md-3 text-right">
                <a href="{{ route('logout') }}" class="nav-link text-primary mx-4" style="font-size: 20px">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
       
        <div class="row mt-1">
            <div class="col-md-3">
            <form id="filterForm" method="GET" action="{{ route('pages.status.history.show') }}" class="d-flex flex-wrap gap-0">
                    @csrf
                    <div class="row w-100 align-items-center">
                        <!-- Filter From/To -->
                        <div class="col-md-4 d-flex gap-2">
                            @php
                                use Carbon\Carbon;
                                $defaultFrom = Carbon::now()->format('Y-m-d');
                                
                            @endphp
                            <div class="form-group d-flex align-items-center">
                                <label for="startDate" class="mr-2 mb-0">Chọn Ngày:</label>
                                <input type="date" id="startDate" name="startDate" value="{{ request('startDate') ?? $defaultFrom }}" class="form-control" />
                            </div>
                        </div>
                    </div>
            </form>
            </div>
            <div class="col-md-6">
                <div style="font-size: 16px; margin:0; text-align: center;">
                <span style="display: inline-block; width: 100px; height: 30px; background-color: #46f905; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); font-weight: 600;">
                    Sản Xuất
                </span>
                <span style="display: inline-block; width: 100px; height: 30px; background-color: #a1a2a2; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); font-weight: 600;">
                    Vệ Sinh
                </span>
                <span style="display: inline-block; width: 100px; height: 30px; background-color: #f99e02; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); font-weight: 600;">
                    Bảo Trì
                </span>
                <span style="display: inline-block; width: 100px; height: 30px; background-color: #ff0000; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); color: white; font-weight: 600;">
                    Máy Hư
                </span>
                <span style="display: inline-block; width: 100px; height: 30px; background-color: #ffffff; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); font-weight: 600;">
                    Sản Xuất
                </span>
                <span style="display: inline-block; width: 100px; height: 30px; background-color: #3f2643; border: 1px solid #000; margin: 6px; line-height: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); font-weight: 600; color: white;">
                    Hủy
                </span>
                </div>
            </div>

            <div class="col-md-3">
            </div>

        </div>
        <div class="row mt-1">
            @php $now = now();@endphp

            @php
                    $datas = collect($datas);   // ép sang Collection
                    //$half = ceil($datas->count() / 2);
                    $leftData = $datas //$datas->slice(0, $half);
                    //$rightData = $datas->slice($half);
                @endphp
                {{-- BẢNG TRÁI --}}
                <div class="col-md-12">
                    <div class="card">
                        <table class="table table-bordered table-striped" style="border: 3px solid #003A4F;">
                            <thead style=" color:#003A4F; font-size: 30px; padding: 2px 0;">
                                <tr>
                                <th style="width: 10%">Phòng Sản xuất</th>
                                    <th style="width: 25%">Lịch Sản Xuất</th>
                                    <th style="width: 5%">T.Gian LT</th>
                                    <th style="width: 25%">Sản Xuất Thực Tế</th>
                                    <th style="width: 5%">T.Gian TT</th>
                                    <th style="width: 20%" class="text-center">Thông Báo</th>
                                    <th  class="text-center" style="width: 5%">Người/Ngày Cập Nhật</th>
                                </tr>
                            </thead>

                            <tbody style="font-size: 22px; font-weight: bold; color: #003A4F;">
                                @php $current_stage = null; @endphp

                                @php
                                    $current_stage = null;
                                @endphp

                                @foreach ($leftData as $roomName => $roomData)

                                    @php
                                        $thero  = $roomData['thero']  ?? [];
                                        $actual = $roomData['actual'] ?? [];
                                   
                                        // Nếu phòng không có dữ liệu => vẫn hiển thị 1 dòng
                                        $maxRows = max(1, count($thero), count($actual));

                                        $production_group = $roomData['production_group'] ?? '';


                                       
                                    @endphp


                                    {{-- ==== HEADER GROUP ==== --}}
                                    @if ($production_group !== $current_stage)
                                        <tr style="background:#CDC717; text-align:center; font-size:24px;">
                                            <td colspan="8">{{ $production_group }}</td>
                                        </tr>
                                    @endif

                                    @php $current_stage = $production_group; @endphp


                                    {{-- ==== RENDER TỪNG DÒNG ==== --}}
                                    @for ($i = 0; $i < $maxRows; $i++)

                                        @php
                                            $t = $thero[$i]  ?? null;   // THERO
                                            $a = $actual[$i] ?? null;   // ACTUAL
                                            $color = "#ffffff";
                                            $font_color = '#003A4F;';
                                            if ($a && isset($a['status'])) {
                                                switch ($a['status']) {
                                                    case 0: $color = "#ffffff"; break; 
                                                    case 1: $color = "#46f905ff"; break;
                                                    case 2: $color = "#a1a2a2ff"; break;
                                                    case 3: $color = "#f99e02ff"; break;
                                                    case 4: $color = "#FF0000"; break;
                                                }
                                            }

                                            if (isset($a['active']) && $a['active'] == 0){
                                                    $color = "#3f2643";
                                                    $font_color = 'white';
                                            }
                                        @endphp

                                        <tr>
                                            {{-- ROOM — chỉ hiện ở dòng đầu --}}
                                            @if ($i === 0)
                                                <td rowspan="{{ $maxRows }}" style="background:#fff;">
                                                    <div style="display:flex; align-items:center; gap:6px;">
                                                        <span>{{ $roomName }}</span>
                                                    </div>
                                                </td>
                                            @endif


                                            {{-- THERO title --}}
                                            <td>
                                                {{ $t['title'] ?? '-' }}
                                            </td>

                                            {{-- THERO time --}}
                                            <td>
                                                @if ($t)
                                                    <div>{{ \Carbon\Carbon::parse($t['start'])->format('H:i d/m') }}</div>
                                                    <div>{{ \Carbon\Carbon::parse($t['end'])->format('H:i d/m') }}</div>
                                                @else
                                                    -
                                                @endif
                                            </td>


                                            {{-- ACTUAL in_production --}}
                                            <td style="background: {{ $color }}; color: {{ $font_color }};">
                                                {{ $a['in_production'] ?? '-' }} {{ (isset($a['active']) && $a['active'] == 0 && !empty($a['created_by']))? " => Đã Hủy bởi {$a['created_by']}": ''}}
                                            </td>


                                            {{-- ACTUAL time --}}
                                            <td>
                                                @if ($a)
                                                    <div>{{ \Carbon\Carbon::parse($a['start'])->format('H:i d/m') }}</div>
                                                    <div>{{ \Carbon\Carbon::parse($a['end'])->format('H:i d/m') }}</div>
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            {{-- Notification --}}
                                            <td>{{ $a['notification'] ?? '-' }}</td>

                                            <td>
                                                <div> {{ $a['created_by'] ?? '-'  }} </div>
                                                <div> {{ isset($a['created_at']) ? \Carbon\Carbon::parse($a['created_at'])->format('d/m/Y H:i') : '-' }} </div>
                                               
                                            </td>
                                        </tr>

                                    @endfor

                                @endforeach

                            </tbody>
                    </div>
                </div>

        </div>
    </div>
    </div>
    
</div>

<!-- ====== SCRIPT ====== -->
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>


<script>
    $(document).ready(function() {

        const startDate = document.getElementById('startDate');
        const form = document.getElementById('filterForm');

        startDate.addEventListener('input', function () {
            form.submit();
        });

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
