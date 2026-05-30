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
            flex: 0 1 240px;
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
            box-shadow: 0 15px 45px rgba(0, 123, 255, 0.15);
            border-color: #007bff;
        }

        .portal-card i {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: #003A4F;
            transition: transform 0.3s ease;
        }

        .portal-card:hover i {
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
    </style>
    <div class="content-wrapper">
        <div class="portal-container">
            <div class="w-100">
                <div class="portal-card-grid">
                    @foreach ($groups as $g)
                        @php
                            $icon = 'fas fa-industry';
                            $desc = 'Quản lý lịch công tác và phân công';

                            if ($g->group_code == 1) {
                                $icon = 'fas fa-balance-scale';
                                $desc = 'Cân nguyên liệu đầu vào';
                            } elseif ($g->group_code == 3) {
                                $icon = 'fas fa-flask';
                                $desc = 'Pha chế thuốc và cốm';
                            } elseif ($g->group_code == 4) {
                                $icon = 'fas fa-briefcase';
                                $desc = 'Nhân sự hành chính & văn phòng';
                            } elseif ($g->group_code == 5) {
                                $icon = 'fas fa-shapes';
                                $desc = 'Cốm dập viên, đóng nang';
                            } elseif ($g->group_code == 6) {
                                $icon = 'fas fa-layer-group';
                                $desc = 'Bao phim bảo vệ sản phẩm';
                            } elseif ($g->group_code == 7) {
                                $icon = 'fas fa-box';
                                $desc = 'Đóng gói trực tiếp (vỉ/chai)';
                            } elseif ($g->group_code == 8) {
                                $icon = 'fas fa-boxes';
                                $desc = 'Đóng thùng, dán tem (thứ cấp)';
                            } elseif ($g->group_code == 9) {
                                $icon = 'fas fa-soap';
                                $desc = 'Vệ sinh công nghiệp & Bán thành phẩm';
                            }
                        @endphp
                        <a href="{{ route('pages.assignment.production.chart', ['group_code' => $g->group_code]) }}"
                            class="portal-card">
                            <i class="{{ $icon }}"></i>
                            <h5>{{ $g->production_group }}</h5>
                            <p>{{ $desc }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
