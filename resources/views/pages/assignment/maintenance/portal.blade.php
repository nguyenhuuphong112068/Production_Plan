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
            flex: 0 1 220px;
            min-width: 200px;
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
            box-shadow: 0 15px 45px rgba(205, 199, 23, 0.15);
            border-color: #CDC717;
        }

        .portal-card i {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: #003A4F;
            transition: transform 0.3s ease;
        }

        .portal-card:hover i {
            transform: scale(1.1);
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
    </style>
    <div class="content-wrapper">
        <div class="portal-container">
            <div class="w-100">
                {{-- <div class="portal-header">
                    <h2>CHỌN TỔ PHÂN CÔNG BẢO TRÌ</h2>
                    <div class="underline"></div>
                </div> --}}

                <div class="portal-card-grid">
                    @foreach ($groups as $g)
                        @php
                            $icon = 'fas fa-tools';
                            $desc = 'Quản lý lịch bảo trì thiết bị';

                            if (str_contains($g->name, 'Điện Lạnh') && str_contains($g->name, 'Nước Tinh Khiết')) {
                                $icon = 'fas fa-faucet';
                                $desc = 'Hệ thống điện lạnh & nước tinh khiết';
                            } elseif (str_contains($g->name, 'Điện Lạnh')) {
                                $icon = 'fas fa-snowflake';
                                $desc = 'Hệ thống điều hòa, làm lạnh';
                            } elseif (str_contains($g->name, 'Nước Tinh Khiết')) {
                                $icon = 'fas fa-tint';
                                $desc = 'Hệ thống RO, nước tinh khiết';
                            } elseif (str_contains($g->name, 'HC')) {
                                $icon = 'fas fa-clipboard-check';
                                $desc = 'Hiệu chuẩn thiết bị (QA)';
                            }
                        @endphp
                        <a href="{{ route('pages.assignment.maintenance.index', ['group_code' => $g->code]) }}"
                            class="portal-card">
                            <i class="{{ $icon }}"></i>
                            <h5>{{ str_replace('Tổ ', '', $g->name) }}</h5>
                            <p>{{ $desc }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
