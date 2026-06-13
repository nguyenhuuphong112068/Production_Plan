@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark">
                            <i class="fas fa-exclamation-triangle text-warning"></i> Cảnh Báo Lịch Sản Xuất
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="warningTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="unmet-tab" data-toggle="tab" href="#unmet" role="tab"
                            aria-controls="unmet" aria-selected="true">
                            Không Đáp Ứng Ngày Cần Hàng <span class="badge badge-danger">{{ $unmetPlans->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="material-tab" data-toggle="tab" href="#material" role="tab"
                            aria-controls="material" aria-selected="false">
                            Cảnh Báo Ngày Đáp Ứng NL/BB <span class="badge badge-warning">{{ $materialWarnings->count() }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="warningTabsContent">
                    <!-- Tab 1: Không Đáp Ứng Ngày Cần Hàng -->
                    <div class="tab-pane fade show active" id="unmet" role="tabpanel" aria-labelledby="unmet-tab">
                        <div class="card card-danger card-outline">
                            <div class="card-body">
                                <table id="table_unmet" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Mã Sản Phẩm</th>
                                            <th>Tên Sản Phẩm</th>
                                            <th>Mã Lô</th>
                                            <th>Ngày Cần Hàng</th>
                                            <th>Bắt Đầu (Dự Kiến)</th>
                                            <th>Kết Thúc (Dự Kiến)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($unmetPlans as $item)
                                            <tr>
                                                <td>{{ $item->finished_product_code }}</td>
                                                <td>{{ $item->product_name }}</td>
                                                <td>{{ $item->batch }}</td>
                                                <td class="text-danger font-weight-bold">
                                                    {{ $item->expected_date ? \Carbon\Carbon::parse($item->expected_date)->format('d/m/Y') : '' }}
                                                </td>
                                                <td>{{ $item->min_start ? \Carbon\Carbon::parse($item->min_start)->format('d/m/Y H:i') : '' }}</td>
                                                <td class="text-danger font-weight-bold">
                                                    {{ $item->max_end ? \Carbon\Carbon::parse($item->max_end)->format('d/m/Y H:i') : '' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Cảnh Báo Ngày Đáp Ứng NL/BB -->
                    <div class="tab-pane fade" id="material" role="tabpanel" aria-labelledby="material-tab">
                        <div class="card card-warning card-outline">
                            <div class="card-body">
                                <table id="table_material" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Mã Sản Phẩm</th>
                                            <th>Tên Sản Phẩm</th>
                                            <th>Mã Lô</th>
                                            <th>Bắt Đầu (Dự Kiến)</th>
                                            <th>Ngày Đáp Ứng NL/BB</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($materialWarnings as $item)
                                            <tr>
                                                <td>{{ $item->finished_product_code }}</td>
                                                <td>{{ $item->product_name }}</td>
                                                <td>{{ $item->batch }}</td>
                                                <td class="text-danger font-weight-bold">
                                                    {{ $item->min_start ? \Carbon\Carbon::parse($item->min_start)->format('d/m/Y H:i') : '' }}
                                                </td>
                                                <td class="text-warning font-weight-bold">
                                                    {{ $item->responsed_date ? \Carbon\Carbon::parse($item->responsed_date)->format('d/m/Y') : '' }}
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
        </section>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#table_unmet').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
                }
            });

            $('#table_material').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
                }
            });
        });
    </script>
@endsection
