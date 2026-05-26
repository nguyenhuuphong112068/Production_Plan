@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <style>
        .portal-container {
            min-height: calc(100vh - 100px);
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }

        .portal-card-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            justify-content: center;
        }

        .portal-card {
            flex: 0 1 250px;
            min-width: 220px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            text-decoration: none !important;
            color: #333;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .portal-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 15px 45px rgba(23, 135, 205, 0.15);
            border-color: #007bff;
        }

        .portal-card i,
        .portal-card-svg {
            font-size: 3.5rem;
            width: 3.5rem;
            height: 3.5rem;
            margin-bottom: 20px;
            color: #003A4F;
            transition: transform 0.3s ease, color 0.3s ease;
            display: inline-block;
        }

        .portal-card:hover i,
        .portal-card:hover .portal-card-svg {
            transform: scale(1.1);
            color: #007bff;
        }

        .portal-card h5 {
            font-weight: 700;
            margin-bottom: 10px;
            color: #003A4F;
        }

        .portal-card p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }

        .portal-header {
            text-align: center;
            margin-bottom: 40px;
            width: 100%;
        }

        .portal-header h2 {
            font-weight: 800;
            color: #003A4F;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .portal-header .underline {
            width: 80px;
            height: 4px;
            background: #003A4F;
            margin: 0 auto;
            border-radius: 2px;
        }

        /* Shift badges styles */
        .shift-badges-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            margin-top: 15px;
            width: 100%;
        }

        .portal-shift-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 6px;
            border-radius: 4px;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .portal-shift-badge.shift-total {
            background-color: #003a4f;
            width: 100%;
            margin-bottom: 5px;
            font-size: 0.78rem;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .portal-shift-badge.shift-c1 { background-color: #28a745; }
        .portal-shift-badge.shift-c2 { background-color: #007bff; }
        .portal-shift-badge.shift-c3 { background-color: #dc3545; }
        .portal-shift-badge.shift-c4 { background-color: #6f42c1; }
        .portal-shift-badge.shift-hc { background-color: #ffc107; color: #212529; }
        .portal-shift-badge.shift-p { background-color: #6c757d; }
    </style>
    <div class="content-wrapper">
        <div class="portal-container">
            <div class="w-100">
                <div class="portal-header">
                    <h2>QUẢN LÝ NHÂN SỰ</h2>
                    <div class="underline"></div>
                </div>

                <div class="portal-card-grid">
                    @foreach ($departments as $d)
                        @php
                            $counts = $shiftCounts[$d['code']] ?? [
                                'total' => 0, 'c1' => 0, 'c2' => 0, 'c3' => 0, 'c4' => 0, 'hc' => 0, 'p' => 0
                            ];
                        @endphp
                        <a href="{{ route('pages.quota.personnel.list', ['department' => $d['code']]) }}" class="portal-card">
                            @if($d['icon'] === 'svg-tube')
                                <svg class="portal-card-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="6" y1="2" x2="18" y2="2" />
                                    <line x1="6" y1="4" x2="18" y2="4" stroke-width="1" />
                                    <path d="M 6,2 L 18,2 L 16,15 C 16,17.5 14,19 12,19 C 10,19 8,17.5 8,15 Z" fill="currentColor" stroke="none" />
                                    <rect x="10" y="19" width="4" height="4" rx="1" fill="currentColor" stroke="none" />
                                </svg>
                            @else
                                <i class="{{ $d['icon'] }}"></i>
                            @endif
                            <h5>{{ $d['name'] }}</h5>
                            
                            <div class="shift-badges-container">
                                <span class="portal-shift-badge shift-total" title="Tổng nhân sự">
                                    Tổng: {{ $counts['total'] }}
                                </span>
                                <span class="portal-shift-badge shift-c1" title="Ca 1">
                                    C1: {{ $counts['c1'] }}
                                </span>
                                <span class="portal-shift-badge shift-c2" title="Ca 2">
                                    Ca2: {{ $counts['c2'] }}
                                </span>
                                <span class="portal-shift-badge shift-c3" title="Ca 3">
                                    Ca3: {{ $counts['c3'] }}
                                </span>
                                <span class="portal-shift-badge shift-c4" title="Ca 4">
                                    C4: {{ $counts['c4'] }}
                                </span>
                                <span class="portal-shift-badge shift-hc" title="Hành chính">
                                    HC: {{ $counts['hc'] }}
                                </span>
                                <span class="portal-shift-badge shift-p" title="Nghỉ phép">
                                    P: {{ $counts['p'] }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
