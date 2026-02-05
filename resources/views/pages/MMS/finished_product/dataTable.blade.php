<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>

        <!-- /.card-Body -->
        <div class="card-body ">
            @php
            function lable_status(int $GRNAPSTS): array{
                    // Chờ tái kiểm
                    if ( $GRNAPSTS == 1) {
                        return [
                            'text'  => 'Approver Bởi Thủ Kho',
                            'color' => '#facc15', // vàng nhạt
                        ];
                    }

                    // Chấp nhận
                    if ( $GRNAPSTS <= 2) {
                        return [
                            'text'  => 'QC Đã Lấy Mẫu',
                            'color' => '#ca8a04', // vàng đậm
                        ];
                    }

                    // Đã lấy mẫu
                    if ($GRNAPSTS <= 3) {
                        return [
                            'text'  => 'QA Đã Release',
                            'color' => '#166534', // xanh lá đậm
                            
                        ];
                    }

                    // Biệt trữ
                    return [
                        'text'  => 'Biệt Trữ',
                        'color' =>  '#facc15', // vàng nhạt
                    ];
            }
            @endphp

            <table id="data_table" class="table table-bordered table-striped">

                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th style = "width: 15px">STT</th>
                        <th>Mã Thành Phẩm/GRN No.</th>
                        <th>Số Lệnh</th>
                        <th>Tên Sản Phẩm</th>
                        <th>Số Lô</th>
                        <th>Qui Cách</th>
                        <th>Số Thùng Chẳn</th>
                        <th>Số Thùng Lẻ</th>
                        <th>Ngày Sản Xuất</th>
                        <th>Hạn Dùng</th>
                        <th>Tình Trạng</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->MatID }} 
                                {{ $data->GRNNO }}  
                            </td>
                            <td>{{ $data->prdorderno }} </td>
                            <td>{{ $data->MatNM }} </td>
                            <td>{{ $data->Mfgbatchno }} </td>
                            <td>{{ $data->pacqty }} </td>
                            <td>{{ $data->FullShipper }} </td>
                            <td>{{ $data->Semishipper }} </td>
                            <td>{{ $data->mfgdate?\Carbon\Carbon::parse($data->mfgdate)->format('d/m/Y') : '' }}</td>
                            <td>{{ $data->Expirydate?\Carbon\Carbon::parse($data->Expirydate)->format('d/m/Y') : '' }}</td>

                            @php
                                $label = lable_status($data->GRNAPSTS);
                            @endphp
                            
                            <td class="text-center">
                                <span style="
                                        background-color: {{ $label['color'] }};
                                        color: white;
                                        padding: 4px 12px;
                                        border-radius: 14px;
                                        font-size: 0.85rem;
                                        font-weight: 600;
                                        white-space: nowrap;
                                ">
                                    {{ $label['text'] }}
                                </span>
                                @if (session('user')['userGroup'] == 'Admin')
                                    <div> {{ $data->GRNAPSTS }} </div>
                                @endif
                                
                            </td>

                           
                            
                        </tr>
                    @endforeach

                </tbody>
                </div>
            </table>
        
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>



<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";
        $('#data_table').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            }
        });
        
    });




</script>
