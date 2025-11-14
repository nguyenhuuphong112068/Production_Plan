{{-- resources/views/pages/status_HPLC/index.blade.php --}}
@extends('layout.master')
@include('pages.status_HPLC.create')
@section('mainContent')
    @php
        $now = now();
        $dates = [$firstDate->toDateString()];
        $allCodes = collect($datas)->flatten(1)->pluck('code')->unique();
    @endphp

    <!-- ====== HEADER ====== -->
    <div class="mb-2">
        <div class="row align-items-center">
            <a href="" class="mx-5" data-toggle="modal" data-target="#Modal">
                <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:35px;">
            </a>

            <div class="mx-auto text-center" style="color: #CDC717; font-weight: bold; line-height: 0.8;">
                <h1>{{ session('title') }}</h1>
            </div>

            <a href="{{ route('logout') }}" class="nav-link text-primary mx-4" style="font-size: 20px">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>

        <div class="text-white w-100" style="background-color: #CDC717">
            <div class="animate-scroll inline-block text-xl text-red">
                <i class="nav-icon fas fa-capsules"></i>
                <<--- {{ $general_notication?->notification ?? 'Không có thông báo mới!' }} --->
                    <i class="nav-icon fas fa-tablets"></i>
            </div>
        </div>
    </div>

    <!-- ====== BẢNG HPLC ====== -->
    <div class="row mt-1">
        <div class="col-md-12">
            <div class="card">
                <table class="table table-bordered table-striped" style="border: 3px solid #003A4F;">
                    <thead style="color:#003A4F; font-size: 30px;">
                        <tr>
                            <th rowspan="2" style="width:5%" class="text-center align-middle">
                                Mã Thiết Bị
                            </th>

                            <th colspan="6" class="text-center align-middle" >
                                <button id="prevDay" class="btn btn-lg text-start"><i class="fa fa-angle-double-left"></i></button>
                                {{ \Carbon\Carbon::parse($firstDate)->format('d/m/Y') }}
                                <button id="nextDay" class="btn btn-lg"><i class="fa fa-angle-double-right"></i></button> 
                            </th>

                            {{-- <th colspan="4" class="text-center align-middle">
                                {{ \Carbon\Carbon::parse($firstDate)->addDays(1)->format('d/m/Y') }}
                               <button id="nextDay" class="btn btn-lg"><i class="fa fa-angle-double-right"></i></button>
                            </th> --}}

                        </tr>
                        <tr>
                            <th class="text-center align-middle">Phân Công Mẫu 1</th>
                            <th class="text-center align-middle">TG KN Mẫu 1</th>
                            <th class="text-center align-middle">Phân Công Mẫu 2</th>
                            <th class="text-center align-middle">TG KN Mẫu 2</th>
                            <th class="text-center align-middle">Phân Công Mẫu 3</th>
                            <th class="text-center align-middle">TG KN Mẫu 3</th>
                        </tr>
                    </thead>

                    <tbody style="color:#003A4F; font-size: 24px; font-weight: bold;">
                        @foreach ($allCodes as $code)
                            <tr>
                                <td class="text-center align-middle">{{ $code }}</td>

                                @foreach ($dates as $day)
              
                                    @php
                                        $records = $datas[$day] ?? collect();
                                        $deviceData = $records->where('code', $code)->values();
                                        $first = $deviceData->get(0);
                                        $second = $deviceData->get(1);
                              
                                        // Hàm xác định màu nền
                                        $getBgColor = function ($data) use ($now) {
                                            if (!$data) {
                                                return '#ffffff';
                                            }
                                            if (
                                                $data->start_time &&
                                                $data->end_time &&
                                                $now->between($data->start_time, $data->end_time)
                                            ) {
                                                return '#46f905';
                                            } // Đang kiểm mẫu
                                            return '#ffffff'; // Mặc định
                                        };
                                    @endphp

                                    {{-- Mẫu 1 --}}
                                    <td class="multi-line" style="background-color: {{ $getBgColor($first) }}">
                                        @if ($first && $first->sample_name)
                                            Mẫu: {{ $first->sample_name ?? 'NA' }} - {{ $first->batch_no ?? 'NA' }} -{{ $first->stage ?? 'NA' }}<br>
                                            Chỉ Tiêu: {{ $first->test ?? 'NA' }}<br>
                                            Cột: {{ $first->column ?? 'NA' }}<br>
                                            KNV: {{ $first->analyst ?? 'NA' }}<br>
                                            Lưu Ý: {{ $first->notes ?? '' }}<br>
                                            Ghi Chú: {{ $first->remark ?? '' }}
                                        @endif
                                    </td>
                                    <td style="background-color: {{ $getBgColor($first) }}">
                                        @if ($first && $first->end_time)
                                            {{ \Carbon\Carbon::parse($first->start_time)->format('H:i d/m') }}<br>
                                            {{ \Carbon\Carbon::parse($first->end_time)->format('H:i d/m') }}
                                        @endif
                                    </td>

                                    {{-- Mẫu 2 --}}
                                    <td class="multi-line" style="background-color: {{ $getBgColor($second) }}">
                                        @if ($second && $second->sample_name)
                                            Mẫu: {{ $second->sample_name ?? 'NA' }} - {{ $second->batch_no ?? 'NA' }} - {{ $second->stage ?? 'NA' }}<br>
                                            Chỉ Tiêu: {{ $second->test ?? 'NA' }}<br>
                                            Cột: {{ $second->column ?? 'NA' }}<br>
                                            KNV: {{ $second->analyst ?? 'NA' }}<br>
                                            Lưu Ý: {{ $second->notes ?? '' }}<br>
                                            Ghi Chú: {{ $second->remark ?? '' }}
                                        @endif
                                    </td>
                                    <td style="background-color: {{ $getBgColor($second) }}">
                                        @if ($second && $second->end_time)
                                            {{ \Carbon\Carbon::parse($second->start_time)->format('H:i d/m') }}<br>
                                            {{ \Carbon\Carbon::parse($second->end_time)->format('H:i d/m') }}
                                        @endif
                                    </td>

                                    
                                    {{-- Mẫu 2 --}}
                                    <td class="multi-line" style="background-color: {{ $getBgColor($second) }}">
                                        @if ($second && $second->sample_name)
                                            Mẫu: {{ $second->sample_name ?? 'NA' }} - {{ $second->batch_no ?? 'NA' }} - {{ $second->stage ?? 'NA' }}<br>
                                            Chỉ Tiêu: {{ $second->test ?? 'NA' }}<br>
                                            Cột: {{ $second->column ?? 'NA' }}<br>
                                            KNV: {{ $second->analyst ?? 'NA' }}<br>
                                            Lưu Ý: {{ $second->notes ?? '' }}<br>
                                            Ghi Chú: {{ $second->remark ?? '' }}
                                        @endif
                                    </td>
                                    <td style="background-color: {{ $getBgColor($second) }}">
                                        @if ($second && $second->end_time)
                                            {{ \Carbon\Carbon::parse($second->start_time)->format('H:i d/m') }}<br>
                                            {{ \Carbon\Carbon::parse($second->end_time)->format('H:i d/m') }}
                                        @endif
                                    </td>



                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

<!-- ====== SCRIPT ====== -->
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

@if (session('success'))
    <div id="success-message" class="alert alert-success text-center" style="position: fixed; top: 20px; left: 10%; transform: translateX(-50%); z-index: 9999;">
        {{ session('success') }}
    </div>

    <script>
        // Tự ẩn thông báo sau 1 giây
        setTimeout(() => {
            const msg = document.getElementById('success-message');
            if (msg) {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            }
        }, 3000);
    </script>
@endif



<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        const adjustRowHeight = () => {
            const totalHeight = window.innerHeight - 180;
            const allRows = document.querySelectorAll("tbody tr");
            const rowCount = allRows.length / 2;
            let tem = rowCount < 30 ? 2 : 1;
            if (rowCount > 0) {
                const rowHeight = Math.floor(totalHeight / rowCount);
                allRows.forEach(row => row.style.height = `${rowHeight/tem}px`);
            }
        };

        adjustRowHeight();
        window.addEventListener('resize', adjustRowHeight);
        setTimeout(() => location.reload(), 300000); // reload 5 phút


        let firstDate = "{{ $firstDate->toDateString() }}";
        document.getElementById('prevDay').addEventListener('click', function() {
         
            window.location.href = `?firstDate=${moment(firstDate).subtract(1, 'days').format('YYYY-MM-DD')}`;
        });

        document.getElementById('nextDay').addEventListener('click', function() {
         
            window.location.href = `?firstDate=${moment(firstDate).add(1, 'days').format('YYYY-MM-DD')}`;
        });
    });
</script>

<!-- ====== STYLE ====== -->
<style>
    .animate-scroll {
        animation: scrollText 30s linear infinite;
        white-space: nowrap;
    }

    @keyframes scrollText {
        0% {
            transform: translateX(100%);
        }

        100% {
            transform: translateX(-80%);
        }
    }

    .animate-scroll:hover {
        animation-play-state: paused;
    }

    .table.table-bordered td,
    .table.table-bordered th {
        border: 3px solid #003A4F;
    }

    td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .multi-line {
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .table th {
        padding: 10px 8px !important;
        line-height: 1.1;
    }

    .table td {
        overflow: hidden;
        word-break: break-word;
        line-height: 1.2;
        vertical-align: middle;
    }
</style>

