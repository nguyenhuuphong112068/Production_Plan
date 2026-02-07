<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<div class="content-wrapper">
    <div class="card">

        <div class="card-header mt-4">
            {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}
        </div>

        <!-- /.card-Body -->
        <div class="card-body ">
            @php
               $material_status = [
                    0 => "Biệt Trữ",
                    1 => "Approver Bởi Thủ Kho",
                    2 => "Đã Lấy Mẫu Gọp",
                    3 => "3 ??",
                    4 => "Chờ Lấy Mẫu ĐT",
                    5 => "Đã Lấy Mẫu ĐT",
                    6 => "6 ??",
                    7 => "Chờ Tái Kiểm",
               ];

              function lable_status(int $GRNSts, ?string $ARNO): array{
                    // Chờ tái kiểm
                    if (!empty($ARNO) && $GRNSts == 7) {
                        return [
                            'text'  => 'Chờ Tái Kiểm',
                            'color' => '#dc2626', // đỏ đậm
                        ];
                    }

                    // Chấp nhận
                    if (!empty($ARNO) && $GRNSts >= 2 && $GRNSts <= 5) {
                        return [
                            'text'  => 'Chấp Nhận',
                            'color' => '#166534', // xanh lá đậm
                        ];
                    }

                    // Đã lấy mẫu
                    if (empty($ARNO) && $GRNSts >= 2 && $GRNSts <= 5) {
                        return [
                            'text'  => 'Đã Lấy Mẫu',
                            'color' => '#ca8a04', // vàng đậm
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
                        <th>Mã Nguyên Liệu/GRN No.</th>
                     
                        <th>Tên Nguyên Liệu</th>
                        <th>Nhà Sản Xuất</th>
                        <th>Nhà Cung Cấp</th>
                        <th>Số Lô NSX</th>
                        <th>Số Lô NB</th>
                        <th>Hạn Dùng</th>
                        <th>Hạn TK</th>
                        <th>Nhập</th>
                        <th>Tồn</th>
                        <th>Tình Trạng Lấy Mẫu</th>
                        <th>Tình Trạng Nhãn/ 
                            CoA No.
                        </th>
                       
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td>{{ $data->MatID }} 
                                {{ $data->GRNNO }}  
                            </td>
                            <td>{{ $data->MatNM }} </td>
                            <td>{{ $data->Mfg }} </td>
                            <td>{{ $data->Supplier }} </td>
                            <td>{{ $data->Mfgbatchno }} </td>
                            <td>{{ $data->ARNO }} </td>
                            <td>{{ $data->Expirydate?\Carbon\Carbon::parse($data->Expirydate)->format('d/m/Y') : '' }}</td>
                            <td>{{ $data->Retestdate?\Carbon\Carbon::parse($data->Retestdate)->format('d/m/Y') : '' }}</td>
                            <td>{{ round($data->{'ReceiptQuantity'},4) . " " . $data->MatUOM }} </td>
                            <td>{{ round($data->{'Total Qty'},4) . " " . $data->MatUOM }} </td>
                            <td>{{ $material_status[$data->GRNSts] }} </td>
                          
                            @php
                                $label = lable_status($data->GRNSts, $data->ARNO);
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
                                <br>
                                {{ $data->ARNO }}

                                
                                @if (session('user')['userGroup'] == 'Admin')
                                    <div> {{ $data->GRNSts }} </div>
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
