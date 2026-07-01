@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')

    <div class="content-wrapper" style="height: 100vh; overflow-y: auto; overflow-x: hidden; padding-bottom: 50px;">

        {{-- ═══════════════════════════════════════════════════════════
     CHÍNH SÁCH SẢN LƯỢNG — YIELD POLICY
    ═══════════════════════════════════════════════════════════ --}}

        @php
            $can_set_yield_policy = user_has_permission(session('user')['userId'] ?? 0, 'set_yield_policy', 'boolean');
        @endphp


        <style>
            /* ── Variables ──────────────────────────────────────────── */
            :root {
                --yp-primary: #2563eb;
                --yp-success: #16a34a;
                --yp-warning: #d97706;
                --yp-danger: #dc2626;
                --yp-muted: #6b7280;
                --yp-bg: #f8fafc;
                --yp-card-bg: #ffffff;
                --yp-border: #e2e8f0;
                --yp-radius: 12px;
                --yp-shadow: 0 2px 12px rgba(0, 0, 0, .07);
            }

            /* ── Page wrapper ───────────────────────────────────────── */
            .yp-page {
                padding: 20px 24px;
                background: var(--yp-bg);
                min-height: 100vh;
            }

            /* ── Page header ────────────────────────────────────────── */
            .yp-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 20px;
                flex-wrap: wrap;
                gap: 12px;
            }

            .yp-header h1 {
                font-size: 1.35rem;
                font-weight: 700;
                color: #1e293b;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .yp-header h1 .icon-circle {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: linear-gradient(135deg, #2563eb, #7c3aed);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: .9rem;
            }

            /* ── Filter bar ─────────────────────────────────────────── */
            .yp-filter-bar {
                background: var(--yp-card-bg);
                border-radius: var(--yp-radius);
                box-shadow: var(--yp-shadow);
                border: 1px solid var(--yp-border);
                padding: 14px 20px;
                display: flex;
                align-items: center;
                gap: 16px;
                flex-wrap: wrap;
                margin-bottom: 20px;
            }

            .yp-filter-bar label {
                font-size: .82rem;
                font-weight: 600;
                color: var(--yp-muted);
                margin-bottom: 2px;
                display: block;
            }

            .yp-filter-bar select,
            .yp-filter-bar input {
                border: 1.5px solid var(--yp-border);
                border-radius: 8px;
                padding: 6px 12px;
                font-size: .88rem;
                color: #1e293b;
                background: #fff;
                transition: border .2s;
            }

            .yp-filter-bar select:focus,
            .yp-filter-bar input:focus {
                border-color: var(--yp-primary);
                outline: none;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
            }

            .yp-btn {
                padding: 7px 20px;
                border-radius: 8px;
                font-size: .88rem;
                font-weight: 600;
                cursor: pointer;
                border: none;
                transition: all .18s;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .yp-btn-primary {
                background: var(--yp-primary);
                color: #fff;
            }

            .yp-btn-primary:hover {
                background: #1d4ed8;
                transform: translateY(-1px);
            }

            .yp-btn-success {
                background: var(--yp-success);
                color: #fff;
            }

            .yp-btn-success:hover {
                background: #15803d;
                transform: translateY(-1px);
            }

            .yp-btn-outline {
                background: #fff;
                color: var(--yp-primary);
                border: 1.5px solid var(--yp-primary);
            }

            .yp-btn-outline:hover {
                background: #eff6ff;
            }

            /* ── Summary cards ──────────────────────────────────────── */
            .yp-summary-row {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 14px;
                margin-bottom: 20px;
            }

            @media(max-width:900px) {
                .yp-summary-row {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            .yp-stat-card {
                background: var(--yp-card-bg);
                border-radius: var(--yp-radius);
                box-shadow: var(--yp-shadow);
                border: 1px solid var(--yp-border);
                padding: 16px 20px;
                position: relative;
                overflow: hidden;
            }

            .yp-stat-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
            }

            .yp-stat-card.blue::before {
                background: linear-gradient(90deg, #2563eb, #7c3aed);
            }

            .yp-stat-card.green::before {
                background: linear-gradient(90deg, #16a34a, #059669);
            }

            .yp-stat-card.warn::before {
                background: linear-gradient(90deg, #d97706, #f59e0b);
            }

            .yp-stat-card.red::before {
                background: linear-gradient(90deg, #dc2626, #ef4444);
            }

            .yp-stat-card .label {
                font-size: .76rem;
                font-weight: 600;
                color: var(--yp-muted);
                text-transform: uppercase;
                letter-spacing: .5px;
            }

            .yp-stat-card .value {
                font-size: 1.65rem;
                font-weight: 800;
                color: #1e293b;
                line-height: 1.2;
                margin: 4px 0 2px;
            }

            .yp-stat-card.blue .value {
                color: #2563eb;
            }

            .yp-stat-card.green .value {
                color: #16a34a;
            }

            .yp-stat-card.warn .value {
                color: #d97706;
            }

            .yp-stat-card.red .value {
                color: #dc2626;
            }

            .yp-stat-card .sub {
                font-size: .78rem;
                color: var(--yp-muted);
            }

            .yp-stat-card .icon {
                position: absolute;
                right: 16px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 2rem;
                opacity: .12;
                color: #334155;
            }

            /* ── Main 2-col layout ──────────────────────────────────── */
            .yp-main-grid {
                display: grid;
                grid-template-columns: 320px 1fr;
                gap: 18px;
                margin-bottom: 20px;
            }

            @media(max-width:1100px) {
                .yp-main-grid {
                    grid-template-columns: 1fr;
                }
            }

            /* ── Policy card ────────────────────────────────────────── */
            .yp-policy-card {
                background: var(--yp-card-bg);
                border-radius: var(--yp-radius);
                box-shadow: var(--yp-shadow);
                border: 1px solid var(--yp-border);
                padding: 22px 20px;
            }

            .yp-policy-card h5 {
                font-size: .95rem;
                font-weight: 700;
                color: #1e293b;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
                padding-bottom: 10px;
                border-bottom: 2px solid var(--yp-border);
            }

            .yp-field-group {
                margin-bottom: 14px;
            }

            .yp-field-group label {
                font-size: .8rem;
                font-weight: 600;
                color: #475569;
                margin-bottom: 4px;
                display: block;
            }

            .yp-field-group .input-wrap {
                display: flex;
                align-items: center;
                border: 1.5px solid var(--yp-border);
                border-radius: 8px;
                overflow: hidden;
                transition: border .2s;
            }

            .yp-field-group .input-wrap:focus-within {
                border-color: var(--yp-primary);
                box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
            }

            .yp-field-group .input-wrap input {
                flex: 1;
                border: none;
                outline: none;
                padding: 8px 12px;
                font-size: .9rem;
                background: transparent;
            }

            .yp-field-group .input-wrap .unit-badge {
                background: #f1f5f9;
                padding: 8px 12px;
                font-size: .8rem;
                font-weight: 700;
                color: #64748b;
                border-left: 1.5px solid var(--yp-border);
                white-space: nowrap;
            }

            .yp-divider {
                border: none;
                border-top: 1.5px dashed var(--yp-border);
                margin: 16px 0;
            }

            /* ── Chart card ─────────────────────────────────────────── */
            .yp-chart-card {
                background: var(--yp-card-bg);
                border-radius: var(--yp-radius);
                box-shadow: var(--yp-shadow);
                border: 1px solid var(--yp-border);
                padding: 22px 20px;
            }

            .yp-chart-card h5 {
                font-size: .95rem;
                font-weight: 700;
                color: #1e293b;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding-bottom: 10px;
                border-bottom: 2px solid var(--yp-border);
            }

            .yp-chart-tabs {
                display: flex;
                gap: 6px;
            }

            .yp-chart-tab {
                padding: 4px 12px;
                border-radius: 6px;
                font-size: .78rem;
                font-weight: 600;
                cursor: pointer;
                border: 1.5px solid var(--yp-border);
                background: #fff;
                color: var(--yp-muted);
                transition: all .15s;
            }

            .yp-chart-tab.active {
                background: var(--yp-primary);
                color: #fff;
                border-color: var(--yp-primary);
            }

            /* ── Table card ─────────────────────────────────────────── */
            .yp-table-card {
                background: var(--yp-card-bg);
                border-radius: var(--yp-radius);
                box-shadow: var(--yp-shadow);
                border: 1px solid var(--yp-border);
                overflow: hidden;
            }

            .yp-table-card .card-header {
                padding: 16px 20px;
                background: linear-gradient(135deg, #1e293b, #334155);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .yp-table-card .card-header h5 {
                margin: 0;
                font-size: .95rem;
                font-weight: 700;
            }

            .yp-table {
                width: 100%;
                border-collapse: collapse;
            }

            .yp-table thead th {
                background: #f8fafc;
                padding: 10px 14px;
                font-size: .78rem;
                font-weight: 700;
                color: #475569;
                text-transform: uppercase;
                letter-spacing: .4px;
                border-bottom: 2px solid var(--yp-border);
                text-align: center;
            }

            .yp-table thead th:first-child {
                text-align: left;
            }

            .yp-table tbody tr {
                transition: background .15s;
            }

            .yp-table tbody tr:hover {
                background: #f8fafc;
            }

            .yp-table tbody td {
                padding: 10px 14px;
                font-size: .86rem;
                color: #334155;
                border-bottom: 1px solid var(--yp-border);
                text-align: center;
                vertical-align: middle;
            }

            .yp-table tbody td:first-child {
                text-align: left;
                font-weight: 600;
            }

            /* inline target input */
            .target-inline {
                width: 120px;
                border: 1.5px solid var(--yp-border);
                border-radius: 6px;
                padding: 5px 8px;
                font-size: .85rem;
                text-align: right;
                transition: border .2s;
                background: #fafafa;
            }

            .target-inline:focus {
                border-color: var(--yp-primary);
                outline: none;
                background: #fff;
            }

            .target-inline.edited {
                border-color: #f59e0b;
                background: #fffbeb;
            }

            .target-inline.saved {
                border-color: var(--yp-success);
                background: #f0fdf4;
            }

            /* status badge */
            .yp-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 3px 10px;
                border-radius: 20px;
                font-size: .76rem;
                font-weight: 700;
            }

            .yp-badge-ok {
                background: #dcfce7;
                color: #15803d;
            }

            .yp-badge-warn {
                background: #fef9c3;
                color: #a16207;
            }

            .yp-badge-fail {
                background: #fee2e2;
                color: #b91c1c;
            }

            .yp-badge-none {
                background: #f1f5f9;
                color: #64748b;
            }

            /* progress mini */
            .yp-progress {
                background: #e2e8f0;
                border-radius: 4px;
                height: 6px;
                margin-top: 3px;
                overflow: hidden;
            }

            .yp-progress-bar {
                height: 100%;
                border-radius: 4px;
                transition: width .4s;
            }

            /* toast */
            #yp-toast {
                position: fixed;
                bottom: 24px;
                right: 24px;
                z-index: 9999;
                min-width: 260px;
                border-radius: 10px;
                padding: 14px 20px;
                font-size: .88rem;
                font-weight: 600;
                box-shadow: 0 8px 24px rgba(0, 0, 0, .18);
                display: none;
                animation: slideIn .25s ease;
            }

            @keyframes slideIn {
                from {
                    transform: translateX(40px);
                    opacity: 0;
                }

                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            #yp-toast.success {
                background: #16a34a;
                color: #fff;
            }

            #yp-toast.error {
                background: #dc2626;
                color: #fff;
            }
        </style>

        <div class="yp-page">

            {{-- ── Header ──────────────────────────────────────── --}}
            <div class="yp-header">
                <h1>
                    <span class="icon-circle"><i class="fas fa-chart-line"></i></span>
                    Chính Sách Sản Lượng
                    <span style="font-size:.85rem;font-weight:500;color:#64748b;"> — {{ $productionName }}</span>
                </h1>
                <div style="display:flex;gap:10px;">
                    <button class="yp-btn yp-btn-outline"
                        onclick="window.location.href='{{ route('pages.Schedual.yield.index') }}'">
                        <i class="fas fa-chart-bar"></i> Xem Sản Lượng
                    </button>
                </div>
            </div>

            {{-- ── Filter bar ───────────────────────────────────── --}}
            <form method="GET" action="{{ route('pages.Schedual.yield_policy.index') }}" class="yp-filter-bar"
                id="filterForm">
                <div>
                    <label>Tháng</label>
                    <select name="month" id="selMonth" onchange="document.getElementById('filterForm').submit()">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>Tháng
                                {{ $m }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label>Năm</label>
                    <select name="year" id="selYear" onchange="document.getElementById('filterForm').submit()">
                        @for ($y = now()->year - 1; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div style="margin-left:auto;">
                    <label>&nbsp;</label>
                    <span style="font-size:.82rem;color:#64748b;">
                        <i class="fas fa-circle" style="color:#16a34a;font-size:.6rem;"></i> ≥ 100% &nbsp;
                        <i class="fas fa-circle" style="color:#d97706;font-size:.6rem;"></i> <span id="legend-warning">{{ $policy->min_submit_pct ?? 100 }}</span>–99% &nbsp;
                        <i class="fas fa-circle" style="color:#dc2626;font-size:.6rem;"></i> &lt; <span id="legend-danger">{{ $policy->min_submit_pct ?? 100 }}</span>%
                    </span>
                </div>
            </form>

            {{-- ── Summary Cards ────────────────────────────────── --}}
            @php
                $theoryCardColor = 'blue';
                if ($policy && $policy->target_month_dvl) {
                    if ($totalTheoryDvl >= $policy->target_month_dvl) {
                        $theoryCardColor = 'green';
                    } else {
                        $theoryCardColor = 'red';
                    }
                }
            @endphp
            <div class="yp-summary-row">
                <div class="yp-stat-card {{ $theoryCardColor }}">
                    <div class="label">Tổng SL Lý Thuyết (ĐVL)</div>
                    <div class="value">{{ number_format($totalTheoryDvl, 0, '.', ',') }}</div>
                    <div class="sub">Đơn vị lớn nhất</div>
                    <div class="icon"><i class="fas fa-box-open"></i></div>
                </div>
                <div class="yp-stat-card green">
                    <div class="label">Ngày Đạt Target</div>
                    <div class="value">{{ $summary['days_ok'] }} <span
                            style="font-size:1rem;font-weight:500;color:#6b7280;">/
                            {{ $summary['total_days'] }}</span></div>
                    <div class="sub">{{ $summary['days_warn'] }} ngày cần chú ý, {{ $summary['days_fail'] }} ngày thiếu
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="yp-stat-card {{ $summary['days_fail'] > 0 ? 'red' : 'green' }}">
                    <div class="label">Target Tháng (ĐVL)</div>
                    <div class="value">
                        @if ($policy && $policy->target_month_dvl)
                            {{ number_format($policy->target_month_dvl, 0, '.', ',') }}
                        @else
                            <span style="font-size:1rem;color:#94a3b8;">Chưa đặt</span>
                        @endif
                    </div>
                    <div class="sub">
                        @if ($policy && $policy->target_month_dvl && $totalTheoryDvl > 0)
                            {{ round(($totalTheoryDvl / $policy->target_month_dvl) * 100, 1) }}% so với target
                        @else
                            Thiết lập target bên dưới
                        @endif
                    </div>
                    <div class="icon"><i class="fas fa-bullseye"></i></div>
                </div>
            </div>

            {{-- ── Main: Policy Card + Chart ───────────────────── --}}
            <div class="yp-main-grid">

                {{-- Policy Config Card --}}
                <div class="yp-policy-card">
                    <h5>
                        <i class="fas fa-sliders-h" style="color:#2563eb;"></i>
                        Chính Sách Tháng {{ $month }}/{{ $year }}
                    </h5>

                    <div
                        style="background:#f8fafc;border-radius:6px;padding:10px;margin-bottom:15px;font-size:0.85rem;border:1px solid #e2e8f0;color:#475569;">
                        <i class="fas fa-calendar-alt" style="color:#64748b;margin-right:5px;"></i>
                        Tháng này có <strong style="color:#2563eb;">{{ $totalWorkingDays ?? 0 }}</strong> ngày làm việc và
                        <strong style="color:#dc2626;">{{ $totalOffDays ?? 0 }}</strong> ngày nghỉ.
                    </div>

                    <div class="yp-field-group">
                        <label>Target cả tháng — ĐVL</label>
                        <div class="input-wrap">
                            <input type="number" id="pol_month_dvl" step="1" min="0"
                                value="{{ $policy->target_month_dvl ?? '' }}" placeholder="Nhập target/tháng ĐVL..."
                                {{ !$can_set_yield_policy ? 'disabled' : '' }}>
                            <span class="unit-badge">ĐVL</span>
                        </div>
                    </div>

                    <hr class="yp-divider">


                    <div class="yp-field-group">
                        <label>Target mỗi ngày — ĐVL</label>
                        <div class="input-wrap">
                            <input type="number" id="pol_daily_dvl" step="1" min="0"
                                value="{{ $policy->target_daily_dvl ?? '' }}" placeholder="Nhập target/ngày ĐVL..."
                                {{ !$can_set_yield_policy ? 'disabled' : '' }}>
                            <span class="unit-badge">ĐVL/ngày</span>
                        </div>
                    </div>

                    <hr class="yp-divider">

                    {{-- Ngưỡng đáp ứng submit --}}
                    <div class="yp-field-group">
                        <label style="display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-shield-alt" style="color:#7c3aed;font-size:.85rem;"></i>
                            Ngưỡng đáp ứng mỗi ngày để Submit lịch
                            <span style="font-size:.75rem;color:#94a3b8;font-weight:400;">(% SL lý thuyết / target
                                ngày)</span>
                        </label>
                        <div class="input-wrap">
                            <input type="number" id="pol_min_submit_pct" step="0.1" min="0" max="100"
                                value="{{ $policy->min_submit_pct ?? 100 }}" placeholder="100"
                                style="font-weight:700;color:#7c3aed;" {{ !$can_set_yield_policy ? 'disabled' : '' }}>
                            <span class="unit-badge" style="color:#7c3aed;background:#f5f3ff;">%</span>
                        </div>
                        <div
                            style="font-size:.76rem;color:#64748b;margin-top:4px;padding:8px 10px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0;line-height:1.6;">
                            <div><i class="fas fa-calendar-day" style="color:#7c3aed;width:14px;"></i>
                                <strong>Mỗi ngày làm việc</strong> phải đạt ≥ <strong
                                    id="pct-preview">{{ $policy->min_submit_pct ?? 100 }}%</strong> target/ngày (bỏ qua
                                ngày nghỉ)
                            </div>
                            <div style="margin-top:4px;"><i class="fas fa-calendar-alt"
                                    style="color:#dc2626;width:14px;"></i>
                                <strong>Target cả tháng</strong> luôn yêu cầu đạt <strong
                                    style="color:#dc2626;">100%</strong> (cố định)
                            </div>
                        </div>
                    </div>

                    <hr class="yp-divider">

                    <div class="yp-field-group">
                        <label>Ghi chú</label>
                        <textarea id="pol_note" rows="2"
                            style="width:100%;border:1.5px solid var(--yp-border);border-radius:8px;padding:8px 12px;font-size:.88rem;resize:vertical;"
                            placeholder="Ghi chú chính sách..." {{ !$can_set_yield_policy ? 'disabled' : '' }}>{{ $policy->note ?? '' }}</textarea>
                    </div>

                    @if ($can_set_yield_policy)
                        <button class="yp-btn yp-btn-success" style="width:100%;" onclick="savePolicy()">
                            <i class="fas fa-save"></i> Lưu Chính Sách
                        </button>
                    @endif

                    @if ($policy)
                        <div style="margin-top:10px;font-size:.76rem;color:#94a3b8;text-align:center;">
                            Cập nhật lần cuối: {{ $policy->updated_by ?? 'N/A' }}
                            @if ($policy->updated_at)
                                — {{ \Carbon\Carbon::parse($policy->updated_at)->format('d/m/Y H:i') }}
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Chart Card --}}
                <div class="yp-chart-card" style="display:flex; flex-direction:column;">
                    <h5>
                        <span><i class="fas fa-chart-bar" style="color:#7c3aed;margin-right:8px;"></i>Biểu Đồ SL Lý Thuyết
                            vs Target (ĐVL)</span>
                    </h5>
                    <div style="position:relative;flex:1;min-height:300px;">
                        <canvas id="yieldChart"></canvas>
                    </div>
                    <div style="display:flex;gap:20px;margin-top:12px;font-size:.78rem;">
                        <span><i class="fas fa-square" style="color:#6366f1;"></i> SL Lý Thuyết</span>
                        <span><i class="fas fa-minus" style="color:#f59e0b;font-size:.5rem;vertical-align:middle;"></i>
                            <span
                                style="display:inline-block;width:20px;height:2px;background:#f59e0b;vertical-align:middle;border-top:2px dashed #f59e0b;"></span>
                            Target/ngày</span>
                        <span><i class="fas fa-square" style="color:#16a34a;font-size:.6rem;"></i> Đạt &nbsp;
                            <i class="fas fa-square" style="color:#dc2626;font-size:.6rem;"></i> Thiếu</span>
                    </div>
                </div>
            </div>

            {{-- ── Daily Detail Table ───────────────────────────── --}}
            <div class="yp-table-card">
                <div class="card-header">
                    <h5><i class="fas fa-table" style="margin-right:8px;"></i>Chi Tiết Sản Lượng Theo Ngày</h5>
                    <div style="display:flex;gap:10px;">
                        <span style="font-size:.8rem;color:#94a3b8;align-self:center;" id="unsavedCount"></span>
                        @if ($can_set_yield_policy)
                            <button class="yp-btn"
                                style="color:#fff; border: 1px solid rgba(255,255,255,0.4); background: transparent;"
                                onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                                onmouseout="this.style.background='transparent'" onclick="applyDefaultTargetAll()">
                                <i class="fas fa-magic"></i> Áp Dụng Target Mặc Định
                            </button>
                            <button class="yp-btn yp-btn-success" onclick="saveAllDailyOverrides()">
                                <i class="fas fa-save"></i> Lưu Tất Cả Override
                            </button>
                        @endif
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="yp-table">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Thứ</th>
                                <th>SL LT (ĐVL)</th>
                                <th>Target Ngày (ĐVL)</th>
                                <th>% Đạt</th>
                                <th>Trạng Thái</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dailyYield as $day)
                                @php
                                    $override = $dailyOverrides[$day['date']] ?? null;
                                    $isOffDay = $day['is_off_day'] ?? false;
                                    $tDvl = $override
                                        ? $override->target_qty_dvl
                                        : ($isOffDay
                                            ? 0
                                            : $policy->target_daily_dvl ?? null);
                                    $theoryDvl = $day['theory_dvl'];

                                    if ($tDvl > 0) {
                                        $pct = round(($theoryDvl / $tDvl) * 100, 1);
                                        if ($pct >= 100) {
                                            $badge = 'ok';
                                            $badgeText = "✅ Đạt ({$pct}%)";
                                        } elseif ($pct >= 90) {
                                            $badge = 'warn';
                                            $badgeText = "⚠️ Cần chú ý ({$pct}%)";
                                        } else {
                                            $badge = 'fail';
                                            $badgeText = "❌ Thiếu ({$pct}%)";
                                        }
                                    } else {
                                        $pct = null;
                                        $badge = 'none';
                                        $badgeText = '—';
                                    }
                                    $isOverride = $override && $override->target_qty_dvl !== null;
                                    $isToday = $day['date'] === now()->format('Y-m-d');
                                @endphp
                                <tr id="row-{{ $day['date'] }}" class="{{ $isToday ? 'today-row' : '' }}"
                                    style="{{ $isOffDay ? 'background-color:#fef2f2;' : '' }}">
                                    <td>
                                        <span
                                            style="font-weight:700;{{ $isToday ? 'color:#2563eb;' : '' }}{{ $isOffDay ? 'color:#dc2626;' : '' }}">
                                            {{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}
                                        </span>
                                        @if ($isToday)
                                            <span
                                                style="background:#dbeafe;color:#1d4ed8;border-radius:4px;padding:1px 6px;font-size:.7rem;margin-left:4px;">HÔM
                                                NAY</span>
                                        @endif
                                        @if ($isOffDay)
                                            <span
                                                style="background:#fee2e2;color:#dc2626;border-radius:4px;padding:1px 6px;font-size:.7rem;margin-left:4px;">Nghỉ</span>
                                        @endif
                                    </td>
                                    <td style="color:#64748b;font-size:.82rem;">{{ $day['dow'] }}</td>
                                    <td>
                                        <span style="font-weight:600;color:{{ $theoryDvl > 0 ? '#1e293b' : '#94a3b8' }};">
                                            {{ $theoryDvl > 0 ? number_format($theoryDvl, 0) : '—' }}
                                        </span>
                                        @if ($theoryDvl > 0 && $tDvl > 0)
                                            <div class="yp-progress">
                                                <div class="yp-progress-bar"
                                                    style="width:{{ min($pct, 100) }}%;background:{{ $badge == 'ok' ? '#16a34a' : ($badge == 'warn' ? '#d97706' : '#dc2626') }};">
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="0"
                                            class="target-inline target-dvl {{ $isOverride ? 'saved' : '' }}"
                                            data-date="{{ $day['date'] }}" data-type="dvl" value="{{ $tDvl ?? '' }}"
                                            placeholder="{{ $policy->target_daily_dvl ?? '—' }}"
                                            data-is-off="{{ $isOffDay ? '1' : '0' }}" oninput="markEdited(this)"
                                            {{ !$can_set_yield_policy ? 'disabled' : '' }}>
                                    </td>
                                    <td>
                                        @if ($pct !== null)
                                            <span
                                                style="font-weight:700;color:{{ $badge == 'ok' ? '#16a34a' : ($badge == 'warn' ? '#d97706' : '#dc2626') }};">
                                                {{ $pct }}%
                                            </span>
                                        @else
                                            <span style="color:#94a3b8;">—</span>
                                        @endif
                                    </td>
                                    <td><span class="yp-badge yp-badge-{{ $badge }}">{{ $badgeText }}</span>
                                    </td>
                                    <td>
                                        @if ($can_set_yield_policy)
                                            <button class="yp-btn yp-btn-primary"
                                                style="padding:4px 12px;font-size:.78rem;"
                                                onclick="saveSingleDay('{{ $day['date'] }}', this)">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Toast --}}
        <div id="yp-toast"></div>

    @section('script')
        <script src="{{ asset('assets/plugins/local_cdn/chart.umd.min.js') }}"></script>
        <script>
            // ──────────────────────────────────────────────────────
            // DATA từ PHP
            // ──────────────────────────────────────────────────────
            const DAILY_YIELD = @json($dailyYield);
            const POLICY = @json($policy);
            const YEAR = {{ $year }};
            const MONTH = {{ $month }};
            const CSRF = '{{ csrf_token() }}';
            const ROUTE_STORE = '{{ route('pages.Schedual.yield_policy.store') }}';
            const ROUTE_DAILY = '{{ route('pages.Schedual.yield_policy.daily') }}';

            // ──────────────────────────────────────────────────────
            // CHART
            // ──────────────────────────────────────────────────────
            let chartInstance = null;

            function buildChartData() {
                const labels = DAILY_YIELD.map(d => d.date_label);
                const theory = DAILY_YIELD.map(d => d.theory_dvl);
                const target = parseFloat(document.getElementById('pol_daily_dvl').value) || (POLICY?.target_daily_dvl ?? 0);
                const minPct = parseFloat(document.getElementById('pol_min_submit_pct').value) || (POLICY?.min_submit_pct ?? 100);

                const thresholdLineData = labels.map((_, i) => {
                    const tRow = getDailyTarget(DAILY_YIELD[i].date);
                    const t2 = tRow || target;
                    return t2 * (minPct / 100);
                });

                const barColors = theory.map((v, i) => {
                    const tRow = getDailyTarget(DAILY_YIELD[i].date);
                    const t2 = tRow || target;
                    if (!t2 || t2 <= 0) return 'rgba(99,102,241,.6)';
                    const pct = v / t2 * 100;
                    if (pct >= 100) return 'rgba(22,163,74,.75)';
                    if (pct >= minPct) return 'rgba(217,119,6,.75)';
                    return 'rgba(220,38,38,.75)';
                });

                return {
                    labels,
                    theory,
                    target,
                    barColors,
                    thresholdLineData
                };
            }

            function getDailyTarget(date) {
                const inp = document.querySelector(`.target-dvl[data-date="${date}"]`);
                return parseFloat(inp?.value) || null;
            }

            function renderChart() {
                const {
                    labels,
                    theory,
                    target,
                    barColors,
                    thresholdLineData
                } = buildChartData();
                const ctx = document.getElementById('yieldChart').getContext('2d');
                if (chartInstance) chartInstance.destroy();

                chartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                                type: 'bar',
                                label: `SL Lý Thuyết (ĐVL)`,
                                data: theory,
                                backgroundColor: barColors,
                                borderRadius: 5,
                                order: 2,
                            },
                            ...(target > 0 ? [{
                                type: 'line',
                                label: `Target/ngày`,
                                data: Array(labels.length).fill(target),
                                borderColor: '#f59e0b',
                                borderWidth: 2,
                                borderDash: [6, 4],
                                pointRadius: 0,
                                fill: false,
                                order: 1,
                                tension: 0,
                            }, {
                                type: 'line',
                                label: `Ngưỡng Submit`,
                                data: thresholdLineData,
                                borderColor: '#dc2626',
                                borderWidth: 1.5,
                                borderDash: [4, 4],
                                pointRadius: 0,
                                fill: false,
                                order: 0,
                                tension: 0,
                            }] : []),
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => {
                                        return ` ${ctx.dataset.label}: ${ctx.parsed.y?.toLocaleString('vi-VN')} ĐVL`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f1f5f9'
                                },
                                ticks: {
                                    callback: v => v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v,
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        }
                    }
                });
            }

            document.getElementById('pol_daily_dvl').addEventListener('input', () => {
                renderChart();
            });

            document.getElementById('pol_min_submit_pct').addEventListener('input', (e) => {
                let val = e.target.value;
                if (!val) val = 100;
                const elPreview = document.getElementById('pct-preview');
                if (elPreview) elPreview.innerText = val + '%';
                
                const legendWarning = document.getElementById('legend-warning');
                if (legendWarning) legendWarning.innerText = val;
                
                const legendDanger = document.getElementById('legend-danger');
                if (legendDanger) legendDanger.innerText = val;
                
                renderChart();
            });

            // ──────────────────────────────────────────────────────
            // TOAST
            // ──────────────────────────────────────────────────────
            function showToast(msg, type = 'success') {
                const t = document.getElementById('yp-toast');
                t.className = type;
                t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'times-circle'}"></i> ${msg}`;
                t.style.display = 'block';
                clearTimeout(t._timer);
                t._timer = setTimeout(() => t.style.display = 'none', 3500);
            }

            // ──────────────────────────────────────────────────────
            // SAVE POLICY
            // ──────────────────────────────────────────────────────
            function savePolicy() {
                const minPctEl = document.getElementById('pol_min_submit_pct');
                const payload = {
                    year: YEAR,
                    month: MONTH,
                    target_month_dvl: document.getElementById('pol_month_dvl').value || null,
                    target_daily_dvl: document.getElementById('pol_daily_dvl').value || null,
                    min_submit_pct: minPctEl?.value !== '' ? parseFloat(minPctEl.value) : 100,
                    note: document.getElementById('pol_note').value,
                    _token: CSRF,
                };

                fetch(ROUTE_STORE, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF
                        },
                        body: JSON.stringify(payload),
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            showToast(d.message, 'success');
                            renderChart();
                            setTimeout(() => location.reload(), 1200);
                        } else {
                            showToast(d.message || 'Lỗi lưu chính sách!', 'error');
                        }
                    })
                    .catch(() => showToast('Lỗi kết nối!', 'error'));
            }

            // ──────────────────────────────────────────────────────
            // DAILY OVERRIDE — đánh dấu đã sửa
            // ──────────────────────────────────────────────────────
            let editedDates = new Set();

            function markEdited(el) {
                el.classList.remove('saved');
                el.classList.add('edited');
                editedDates.add(el.dataset.date);
                document.getElementById('unsavedCount').textContent =
                    editedDates.size > 0 ? `⚠ ${editedDates.size} ngày chưa lưu` : '';
            }

            function saveSingleDay(date, btnEl) {
                const dvlInp = document.querySelector(`.target-dvl[data-date="${date}"]`);

                fetch(ROUTE_DAILY, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF
                        },
                        body: JSON.stringify({
                            year: YEAR,
                            month: MONTH,
                            target_date: date,
                            target_qty_dvl: dvlInp?.value || null,
                            _token: CSRF,
                        }),
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            showToast(d.message, 'success');
                            dvlInp?.classList.remove('edited');
                            dvlInp?.classList.add('saved');
                            editedDates.delete(date);
                            document.getElementById('unsavedCount').textContent =
                                editedDates.size > 0 ? `⚠ ${editedDates.size} ngày chưa lưu` : '';
                            renderChart();
                        } else {
                            showToast('Lỗi lưu!', 'error');
                        }
                    });
            }

            async function saveAllDailyOverrides() {
                const dates = [...editedDates];
                if (!dates.length) {
                    showToast('Không có thay đổi nào!', 'error');
                    return;
                }

                for (const date of dates) {
                    await fetch(ROUTE_DAILY, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF
                        },
                        body: JSON.stringify({
                            year: YEAR,
                            month: MONTH,
                            target_date: date,
                            target_qty_dvl: document.querySelector(`.target-dvl[data-date="${date}"]`)
                                ?.value || null,
                        }),
                    });
                    document.querySelector(`.target-dvl[data-date="${date}"]`)?.classList.replace('edited', 'saved');
                }
                editedDates.clear();
                document.getElementById('unsavedCount').textContent = '';
                showToast(`Đã lưu ${dates.length} ngày override!`, 'success');
                renderChart();
            }

            function applyDefaultTargetAll() {
                const defDvl = document.getElementById('pol_daily_dvl').value;
                if (!defDvl) {
                    showToast('Vui lòng nhập Target/ngày trước!', 'error');
                    return;
                }

                document.querySelectorAll('.target-dvl').forEach(inp => {
                    if (!inp.value && defDvl && inp.dataset.isOff !== '1') {
                        inp.value = defDvl;
                        markEdited(inp);
                    }
                });
                showToast('Đã áp dụng target mặc định cho tất cả ngày trống (bỏ qua ngày nghỉ)!', 'success');
            }

            // ── Init ──
            renderChart();

            // Live-preview cho ngưỡng submit
            const pctInput = document.getElementById('pol_min_submit_pct');
            const pctPreview = document.getElementById('pct-preview');
            if (pctInput && pctPreview) {
                pctInput.addEventListener('input', () => {
                    pctPreview.textContent = (pctInput.value || 100) + '%';
                });
            }
        </script>

        <style>
            .today-row {
                background: #eff6ff !important;
            }

            .today-row:hover {
                background: #dbeafe !important;
            }
        </style>
    @endsection
</div>
@endsection
