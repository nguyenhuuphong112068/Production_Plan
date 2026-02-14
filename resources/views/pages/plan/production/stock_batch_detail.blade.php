
<style>
     #data_table_batch_detail {
        font-size: 14px; /* từ 16 → 14 là vừa đẹp */
    }

    #data_table_batch_detail th,
    #data_table_batch_detail td {
        padding: 6px 6px;
    }

     .batch-detail-modal-size {
        max-width: 100% !important;
        width: 100% !important;
        max-height: 100% !important;
        height: 100% !important;
        margin-left: 10px;
        margin-top: 0px;
    }
 
  
    #batch-detail-modal-size .modal-content {
        height: 95vh;
        display: flex;
        flex-direction: column;
    }

    #batch-detail-modal-size .modal-body {
        flex: 1;
        overflow-y: auto;
    }
</style>

<div class="modal fade" id="batchDetialModal" tabindex="-1"
     data-backdrop="static" data-keyboard="false">

    <div class="modal-dialog batch-detail-modal-size">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Các Lô Liên Quan</h5>

                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
            <div class="card" >
            @php
                $auth_update = user_has_permission(session('user')['userId'], 'plan_production_update', 'disabled');
                $auth_deActive = user_has_permission(session('user')['userId'], 'plan_production_deActive', 'disabled');
                $auth_view_material = user_has_permission(session('user')['userId'], 'plan_production_view_material', 'disabled');
            @endphp
            
        <!-- /.card-Body -->
        <div class="card-body">
            {{-- @if (!$send)
                <div class="row">
                    <div class="col-md-2">
                        @if (user_has_permission(session('user')['userId'], 'plan_production_create', 'boolean'))
                            <button class="btn btn-success btn-add mb-2" data-toggle="modal"
                                data-target="#selectProductModal" style="width: 155px;">
                                <i class="fas fa-plus"></i> Thêm
                            </button>
                        @endif
                       
                    </div>

                    <div class="col-md-8 text-center">
                        @if (user_has_permission(session('user')['userId'], 'plan_production_create', 'boolean'))
                            <form action="{{ route('pages.plan.production.open_stock') }}" 
                                method="get"
                                class="d-inline-block">
                                @csrf
                                <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                                <input type="hidden" name="material_packaging_type" value="0">
                                <input type="hidden" name="title" value="BẢNG TÍNH NGUYÊN LIỆU">
                                <input type="hidden" name="selected" value="1">
                                <input type="hidden" name="current_url" value="{{ url()->full() }}">
                                <button type="submit" class="btn btn-success" {{ $auth_view_material }} style="width: 300px">
                                    <i class="fas fa-table"></i> Bảng Dự Trù Nguyên Liệu
                                </button>
                            </form>

                            <form action="{{ route('pages.plan.production.open_stock') }}" 
                                method="get"
                                class="d-inline-block ms-2">
                                @csrf
                                <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                                <input type="hidden" name="material_packaging_type" value="1">
                                <input type="hidden" name="title" value="BẢNG TÍNH BAO BÌ">
                                <input type="hidden" name="selected" value="1">
                                <input type="hidden" name="current_url" value="{{ url()->full() }}">
                                <button type="submit" class="btn btn-success"  style="width: 300px" {{ $auth_view_material }}>
                                    <i class="fas fa-table"></i> Bảng Dự Trù Bao Bì
                                </button>
                            </form>
                        @endif

                    </div>


                    <div class="col-md-2" style="text-align: right;">

                        <form id = "send_form" action="{{ route('pages.plan.production.send') }}" method="post">

                            @csrf
                            <input type="hidden" name="plan_list_id" value="{{ $plan_list_id }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="production" value="{{ $production }}">
                            @if (user_has_permission(session('user')['userId'], 'plan_production_send', 'boolean'))
                            <button class="btn btn-success btn-send mb-2 " style="width: 177px;">
                                <i id = "send_btn" class="fas fa-paper-plane"></i> Gửi
                            </button>
                            @endif
                        </form>

                    </div>
                </div>
            @endif --}}

            <table id="data_table_batch_detail" class="table table-bordered table-striped" style="font-size: 16px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

                    <tr>
                        <th>STT</th>
                        <th>Tình Trạng</th>
                        <th >Mã Sản Phẩm</th>
                        <th style="width:10%" >Sản Phẩm</th>
                        <th>Số Lô/Số lượng ĐG</th>
                        <th>Thị Trường/ Qui Cách</th>
                        <th style="width:5%">Ngày dự kiến KCS</th>
                        <th>Ưu Tiên</th>
                        <th style="width:2%" >Lô TĐ</th>
                       
                        <th>
                            <div> {{ "(1) Ngày có đủ NL" }}  </div>
                            <div> {{ "(2) Ngày có đủ BB" }}  </div>
                            <div> {{ "(3) Ngày được phép cân" }}  </div>
                            <div> {{ "(4) Ngày HH NL chính" }}  </div>
                            <div> {{ "(5) Ngày HH BB" }}  </div>
                        </th>

                        <th>
                            <div> {{ "(1) PC trước" }}  </div>
                            <div> {{ "(2) THT trước" }}  </div>
                            <div> {{ "(3) BP trước" }}  </div>
                            <div> {{ "(4) ĐG trước" }}  </div>
                        </th >
                       
                        <th style="width:30%" >Ghi Chú</th>
                </thead>
                <tbody id = "data_table_batch_detail_body">

                </tbody>
            </table>

            </div>
        </div>
    </div>
</div>


    <script>
        let batchTable;

        $(document).ready(function () {

            batchTable = $('#data_table_batch_detail').DataTable({
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

            $(document).on('blur', '.updateInput', function () {
                
                let id = $(this).data('id');
                let name = $(this).attr('name');
                let updateValue = $(this).val();
                let oldValue = $(this).data('old-value');
              
                if (updateValue === oldValue)return;
                
                if (id == ''){
                    Swal.fire({
                    title: 'Cảnh Báo!',
                    text: 'id Không xác định',
                    icon: 'warning',
                    timer: 1000, // tự đóng sau 2 giây
                    showConfirmButton: false
                });
                    $(this).val('');
                    return
                }

                if (name == "level"){
                    const pattern = /^[1-9]\d*$/;
                    if (updateValue && !pattern.test(updateValue)) {
                        Swal.fire({
                            title: 'Lỗi định dạng!',
                            text: 'Thời gian phải có dạng hh:mm (phút là 00, 15, 30, 45)',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $(this).focus();
                        $(this).css('border', '1px solid red');
                        return;
                    } else {
                        $(this).css('border', '');
                    }
                }

            


                $.ajax({
                    url: "{{ route('pages.plan.production.updateInput') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    name: name,
                    updateValue: updateValue
                    }
                });
            });


        });


    </script>
