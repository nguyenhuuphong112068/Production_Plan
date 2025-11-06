<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<style>
  .time {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    text-align: center;
    height: 100%;
    padding: 2px 4px;
    box-sizing: border-box;
  }

  .time:focus {
    border: 1px solid #007bff;
    border-radius: 2px;
    background-color: #fff;
  }

  td input.time {
    display: block;
    margin: auto;
  }

  canvas {
    width: 100% !important;
    height: 400px !important;
  }
</style>

<div class="content-wrapper">
  <div class="card ">
    <div class="card-header">
      <h3 class="card-title">Biểu đồ tồn lý thuyết theo Stage</h3>
    </div>



    <div class="card-body">

      <form id="filterForm" method="GET" action="{{ route('pages.quarantine.room.list') }}"
            class="d-flex flex-wrap gap-2">
            @csrf
              <div class="row w-100 align-items-center mt-3">
                    <!-- Filter From/To -->
                    <div class="col-md-6 d-flex gap-2">
                        @php
                            $defaultFrom = \Carbon\Carbon::now()->startOfMonth()->toDateString(); // ngày đầu tháng
                            $defaultTo   = \Carbon\Carbon::now()->endOfMonth()->toDateString();  
                            $defaultWeek = \Carbon\Carbon::parse($defaultTo)->weekOfYear; // số tuần trong năm
                            $defaultMonth = \Carbon\Carbon::parse($defaultTo)->month; // tháng
                            $defaultYear = \Carbon\Carbon::parse($defaultTo)->year;
                            $stage_name = [
                                              1 => 'Cân Nguyên Liệu',
                                              3 => 'Pha Chế' ,
                                              4 => 'THT',
                                              5 => 'Định Hình',
                                              6 => 'Bao Phim',
                                              7 => 'ĐGSC-ĐGTC'
                                      ];
                        @endphp

                        <div class="form-group d-flex align-items-center mr-2">
                            <label for="from_date" class="mr-2 mb-0">From:</label>
                            <input type="date" id="from_date" name="from_date"
                                value="{{ request('from_date') ?? $defaultFrom }}" class="form-control" />
                        </div>
                        <div class="form-group d-flex align-items-center mr-2">
                            <label for="to_date" class="mr-2 mb-0">To:</label>
                            <input type="date" id="to_date" name="to_date"
                                value="{{ request('to_date') ?? $defaultTo }}" class="form-control" />
                        </div>
                    </div>
                    <div class="col-md-6 d-flex gap-2 justify-content-end">
                        <!-- Tuần -->
                        <select id="week_number" name="week_number" class="form-control mr-2">
                            @for ($i = 1; $i <= 52; $i++)
                                <option value="{{ $i }}"
                                    {{ (request('week_number') ?? $defaultWeek) == $i ? 'selected' : '' }}>
                                    Tuần {{ $i }}
                                </option>
                            @endfor
                        </select>

                        <!-- Tháng -->
                        <select id="month" name="month" class="form-control mr-2">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}"
                                    {{ (request('month') ?? $defaultMonth) == $m ? 'selected' : '' }}>
                                    Tháng {{ $m }}
                                </option>
                            @endfor
                        </select>

                        <!-- Năm -->
                        <select id="year" name="year" class="form-control">
                            @php $currentYear = now()->year; @endphp
                            @for ($y = $currentYear - 5; $y <= $currentYear + 5; $y++)
                                <option value="{{ $y }}"
                                    {{ (request('year') ?? $defaultYear) == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>

                    </div>
                </div>
            </form>

      @php
          $stageTimeSeries = collect($stageTimeSeries)
              ->flatMap(function($data, $stageCode) {
                  return collect($data)->map(function($d) use ($stageCode) {
                      $d['stage_code'] = $stageCode;
                      return $d;
                  });
              })
              ->groupBy('stage_code');

          $stage_name = [
              1 => "Nguyên Liệu Sau Cân",
              3 => "Cốm Sau Pha Chế",
              4 => "Cốm Hoàn Tất",
              5 => "Viên Nhân",
              6 => "Viên Bao Phim",
              7 => "Thành Phẩm",
          ]
      @endphp

      {{-- Hiển thị từng stage_code --}}
      @foreach (collect($stageTimeSeries)->sortKeys()  as $stage_code => $data)
          <div class="card card-success mb-4">

          <div class="card-header border-transparent">
            <h3 class="card-title">Tổng Lượng {{ $stage_name[$stage_code] }} Biệt Trữ Lý Thuyết {{$stage_code <5 ? '(Kg)': '(ĐVL)'}}</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <button type="button" class="btn btn-tool" data-card-widget="remove">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

 
              
            </div>
            <div class="card-body">
              <canvas id="stageChart{{ $stage_code }}"></canvas>

              {{-- Bảng tổng hợp theo ngày/phòng --}}
              <div class="table-responsive mt-4">
                <table class="table table-bordered table-striped text-center mb-0" id="summaryTable{{ $stage_code }}">
                  <thead class="table-success">
                    <tr id="summaryHeader{{ $stage_code }}">
                     
                      <!-- Ngày sẽ được thêm bằng JS -->
                    </tr>
                  </thead>
                  <tbody id="summaryBody{{ $stage_code }}">
                    <!-- Dữ liệu sẽ được render bằng JS -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
      @endforeach

    </div>
  </div>
</div>

{{-- Script --}}
<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('dataTable/plugins/chart.js/Chart.min.js') }}"></script>

<script>
  $(document).ready(function () {
    document.body.style.overflowY = "auto";
  });

  // Nhận dữ liệu từ controller
  const stageTimeSeries = @json($stageTimeSeries);

  // 🔧 Hàm định dạng ngày yyyy-mm-dd -> dd/mm
  function formatDate(dateStr) {
    const [y, m, d] = dateStr.split("-");
    return `${d}/${m}`;
  }

  // 🎨 Hàm tạo màu ngẫu nhiên
  function randomColor() {
    const r = Math.floor(Math.random() * 180);
    const g = Math.floor(Math.random() * 180);
    const b = Math.floor(Math.random() * 180);
    return `rgb(${r}, ${g}, ${b})`;
  }

  // 🧩 Vẽ biểu đồ và bảng theo từng stage
  Object.entries(stageTimeSeries).forEach(([stage_code, data]) => {
    // Gom dữ liệu theo room
    const byRoom = {};
    data.forEach(d => {
      if (!byRoom[d.room_id]) byRoom[d.room_id] = [];
      byRoom[d.room_id].push(d);
    });

    // Lấy danh sách các ngày
    const labels = [...new Set(data.map(d => d.time_point.split(" ")[0]))].sort();

    // Chuẩn bị dataset cho từng phòng
    const datasets = Object.entries(byRoom).map(([roomId, list]) => {
      const roomName = list[0]?.room_name || `Room ${roomId}`;
      const dataPoints = labels.map(date => {
        const found = list.find(d => d.time_point.startsWith(date));
        return found ? found.total_stock : 0;
      });
      return {
        label: roomName,
        data: dataPoints,
        borderWidth: 2,
        borderColor: randomColor(),
        fill: false,
        tension: 0.25,
        pointRadius: 4,
        pointHoverRadius: 6
      };
    });

    // 🎯 Vẽ biểu đồ
    const ctx = document.getElementById(`stageChart${stage_code}`).getContext("2d");
    new Chart(ctx, {
      type: "line",
      data: {
        labels: labels.map(formatDate),
        datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "nearest",
          intersect: false
        },
        plugins: {
          title: {
            display: true,
            //text: `Biểu đồ tồn lý thuyết - Stage ${stage_code}`,
            font: { size: 16 }
          },
          legend: {
            display: false,
            position: "bottom"
          },
          tooltip: {
            enabled: true,
            backgroundColor: "rgba(0,0,0,0.8)",
            titleFont: { size: 13, weight: "bold" },
            bodyFont: { size: 13 },
            callbacks: {
              label: function (context) {
                const val = context.parsed.y.toLocaleString("vi-VN");
                return `${context.dataset.label}: ${val}`;
              }
            }
          }
        },
        scales: {
          x: {
            title: { display: true, text: "Ngày" }
          },
          y: {
            title: { display: true, text: "Tổng Tồn lý thuyết" },
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return value.toLocaleString("vi-VN");
              }
            }
          }
        }
      }
    });

    // === Bảng tổng hợp ===
    const summaryHeader = document.getElementById(`summaryHeader${stage_code}`);
    const summaryBody = document.getElementById(`summaryBody${stage_code}`);

    // 1️⃣ Lấy danh sách ngày
    const allDates = [...new Set(data.map(d => d.time_point.split(" ")[0]))].sort();

    // 2️⃣ Tiêu đề bảng
    const thEmpty = document.createElement("th");
    thEmpty.innerText = "Ngày";
    summaryHeader.appendChild(thEmpty);

    allDates.forEach(date => {
      const th = document.createElement("th");
      th.innerText = formatDate(date);
      summaryHeader.appendChild(th);
    });

    // 3️⃣ Hàng tổng tồn
    const trTotal = document.createElement("tr");
    const tdLabel = document.createElement("td");
    tdLabel.innerText = "Tổng tồn";
    trTotal.appendChild(tdLabel);

    allDates.forEach(date => {
      const sum = data
        .filter(d => d.time_point.startsWith(date))
        .reduce((acc, d) => acc + (d.total_stock || 0), 0);
      const td = document.createElement("td");
      const decimals = stage_code < 5 ? 2 : 0;
      td.innerText = sum.toLocaleString("vi-VN", { minimumFractionDigits: decimals});
      trTotal.appendChild(td);
    });

    summaryBody.appendChild(trTotal);
  });
</script>

<script>
    const form = document.getElementById('filterForm');
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');
    const weekInput = document.getElementById('week_number');
    const monthInput = document.getElementById('month');
    const yearInput = document.getElementById('year');

    // Submit form với kiểm tra From/To
    function submitForm() {
        const fromDate = new Date(fromInput.value);
        const toDate = new Date(toInput.value);

        if (fromDate > toDate) {
            Swal.fire({
                icon: "warning",
                title: "Ngày không hợp lệ",
                text: "⚠️ Ngày bắt đầu (From) không được lớn hơn ngày kết thúc (To).",
                confirmButtonText: "OK"
            });
            return;
        }
        form.requestSubmit();
    }

    // Khi thay đổi From/To => cập nhật tháng/năm theo From
    function updateMonthYearFromDates() {
        const fromDate = new Date(fromInput.value);
        if (isNaN(fromDate)) return;
        monthInput.value = fromDate.getMonth() + 1;
        yearInput.value = fromDate.getFullYear();
    }

    // Tính tuần ISO dựa trên ngày
    function getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    // Khi thay đổi tuần => cập nhật From/To dựa trên tuần/month/year
    function updateDatesFromWeekMonthYear() {
        const year = parseInt(yearInput.value);
        const week = parseInt(weekInput.value);
        if (!year || !week) return;

        // ISO tuần: ngày đầu tuần là thứ 2
        const simple = new Date(year, 0, 1 + (week - 1) * 7);
        const dayOfWeek = simple.getDay();
        // điều chỉnh để ngày đầu tuần là thứ 2
        const diff = simple.getDay() <= 0 ? 1 : 2 - dayOfWeek; // Chủ nhật=0
        const fromDate = new Date(simple);
        fromDate.setDate(simple.getDate() + diff);

        const toDate = new Date(fromDate);
        toDate.setDate(fromDate.getDate() + 6);

        fromInput.value = fromDate.toISOString().slice(0, 10);
        toInput.value = toDate.toISOString().slice(0, 10);
    }

    // Khi thay đổi tháng => cập nhật From/To dựa trên tháng
    function updateDatesFromMonth() {
        const year = parseInt(yearInput.value);
        const month = parseInt(monthInput.value);
        if (!year || !month) return;

        const fromDate = new Date(year, month - 1, 1);
        const toDate = new Date(year, month, 0);

        fromInput.value = fromDate.toISOString().slice(0, 10);
        toInput.value = toDate.toISOString().slice(0, 10);

        weekInput.value = getWeekNumber(toDate);
    }

    function updateDatesFromYear() {
        const year = parseInt(yearInput.value);
        if (!year) return;

        // Ngày đầu năm
        const fromDate = new Date(year, 0, 1);
        // Ngày cuối năm
        const toDate = new Date(year, 11, 31);

        fromInput.value = fromDate.toISOString().slice(0, 10);
        toInput.value = toDate.toISOString().slice(0, 10);

        // Tuần cuối năm theo ISO week
        weekInput.value = getWeekNumber(toDate);
    }

    // Hàm lấy số tuần ISO
    function getWeekNumber(d) {
        d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    // Lắng nghe event
    [fromInput, toInput].forEach(input => {
        input.addEventListener('change', () => {
            updateMonthYearFromDates();
            submitForm();
        });
    });

    weekInput.addEventListener('change', () => {
        updateDatesFromWeekMonthYear();
        submitForm();
    });

    monthInput.addEventListener('change', () => {
        updateDatesFromMonth();
        submitForm();
    });

    yearInput.addEventListener('change', () => {
        updateDatesFromYear();
        submitForm();
    });
</script>




