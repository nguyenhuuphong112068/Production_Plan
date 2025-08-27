<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- /.card-header -->
                    <div class="card">
                        <div class="card-header mt-4">
                            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
                        </div>
                        <!-- /.card-Body -->
                        <div class="card-body">
                            <form id="filterForm" method="GET" action="{{ route('pages.statistics.product.list') }}"
                                class="d-flex flex-wrap gap-2">
                                @csrf
                                <div class="row w-100 align-items-center mt-3">
                                    <!-- Filter From/To -->
                                    <div class="col-md-6 d-flex gap-2">
                                        @php
                                            
                                            $defaultFrom =\Carbon\Carbon::now()->subMonth(1)->toDateString();
                                            $defaultTo = \Carbon\Carbon::now()->toDateString();
                                            $defaultWeek = \Carbon\Carbon::parse($defaultTo)->weekOfYear; // số tuần trong năm
                                            $defaultMonth = \Carbon\Carbon::parse($defaultTo)->month; // tháng
                                            $defaultYear = \Carbon\Carbon::parse($defaultTo)->year;
                                        @endphp

                                        <div class="form-group d-flex align-items-center mr-2">
                                            <label for="from_date" class="mr-2 mb-0">From:</label>
                                            <input type="date" id="from_date" name="from_date"
                                                value="{{ request('from_date') ?? $defaultFrom }}"
                                                class="form-control" />
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
                            <section class="content">
                                <div class="container-fluid">
                                    {{-- Card Table --}}
                                    <div class="card">
                                        <div class="card-header border-transparent">
                                            {{-- <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div> --}}
                                        </div>
                                        <div class="card-body">
                                            <table id="example1" class="table table-bordered table-striped"
                                                style="font-size: 20px">
                                                <thead
                                                    style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                                                    <tr>
                                                        <th class ="text-center" style="width:1%">STT</th>
                                                        <th class ="text-center" style="width:3%">Mã Sản Phẩm</th>
                                                        <th class ="text-center" style="width:15%">Tên Sản Phẩm</th>
                                                        <th class ="text-center">Thống Kê</th>
                                                       
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($products as $product)
                                                        <tr class = "mb-0">
                                                            <td>{{ $loop->iteration }} </td>
                                                            <td> 
                                                                <span>{{$product->finished_product_code}}</span>
                                                                <span>{{$product->intermediate_code}}</span>
                                                            <td> {{ $product->name . '-' . $product->batch_qty . $product->unit_batch_qty}} </td>
                                                            <td>
                                                                <div class="row mb-0">
                                                                    <div class="col-md-3">
                                                                        <div class="info-box">
                                                                            <span class="info-box-icon bg-info"><i
                                                                                    class="fas fa-box"></i></span>
                                                                            <div class="info-box-content">
                                                                                <span class="info-box-text">Số Lượng Lô Sản Xuất</span>
                                                                                <span
                                                                                    class="info-box-number">{{ $product->so_lo }}</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <div class="info-box">
                                                                            <span class="info-box-icon bg-danger"><i
                                                                                    class="fas fa-clock"></i></span>
                                                                            <div class="info-box-content">
                                                                                <span class="info-box-text">Thời Gian: {{ $totalHours }}h
                                                                                    </span>
                                                                                <span class="info-box-number">
                                                                                    @php
                                                                                      $H_SX = round(($product->tong_thoi_gian_sanxuat / $totalHours) * 100, 2);
                                                                                      $H_VS = round(($product->tong_thoi_gian_vesinh / $totalHours) * 100, 2);
                                                                                    @endphp
                                                                                    <span>SX: {{ $product->tong_thoi_gian_sanxuat }}h # {{ $H_SX }}% - </span>
                                                                                    <span>VS: {{ $product->tong_thoi_gian_vesinh }}h # {{ $H_VS }}% </span>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <div class="info-box">
                                                                            <span class="info-box-icon bg-success"><i
                                                                                    class="fas fa-crosshairs"
                                                                                    style="color: white;"></i></span>
                                                                            <div class="info-box-content">
                                                                                <span class="info-box-text">Tổng Sản Lượng Lý Thuyết</span>
                                                                                <span
                                                                                    class="info-box-number">{{ number_format($product->san_luong_ly_thuyet) }}
                                                                                    {{$product->stage_code >=5?"ĐVL":"Kg"}}</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <div class="info-box">
                                                                            <span class="info-box-icon bg-primary"><i
                                                                                    class="fas fa-flag-checkered"></i></span>
                                                                            <div class="info-box-content">
                                                                                <span class="info-box-text">Tổng Sản Lượng Thực Tế</span>
                                                                                @php
                                                                                    $H = round(($product->san_luong_thuc_te / $product->san_luong_ly_thuyet) * 100, 2);
                                                                                @endphp
                                                                                <span
                                                                                    class="info-box-number">{{ number_format($product->san_luong_thuc_te)}}
                                                                                     {{$product->stage_code >=5?"ĐVL":"Kg"}} # {{ $H }}% </span>
                                                                            </div>
                                                                        </div>
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
                        </div>
                    </div>
                </div>

            </div><!-- /.container-fluid -->
    </section>




</div>
<!-- /.card-body -->
</div>
<!-- /.card -->
</div>
<!-- /.col -->
</div>
<!-- /.row -->
</div>
<!-- /.container-fluid -->
</section>
<!-- /.content -->
</div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>



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

