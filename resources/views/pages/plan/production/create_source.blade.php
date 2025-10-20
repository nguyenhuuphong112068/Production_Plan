<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

<!-- Modal -->
<div class="modal fade" id="create_soure_modal" tabindex="-1" role="dialog" aria-labelledby="productNameModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.plan.production.store_source') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
                    </a>

                    <h4 class="modal-title w-100 text-center"  style="color: #CDC717">
                      Tạo Mới Nguồn Nguyên Liệu
                    </h4>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="text" class="form-control" name="mode" id = "create_soure_modal_mode" value="{{ old('mode') }}" readonly>
                    {{-- intermadiat_Code --}}
                    <div class="form-group">
                        <label for="intermediate_code">Mã Bán Thành Phẩm</label>
                        <input type="text" class="form-control" name="intermediate_code" value="{{ old('intermediate_code') }}" readonly>
                    </div>
                    {{--  Product_name --}}
                    <div class="form-group">
                        <label for="product_name">Tên Sản Phẩm</label>
                        <input type="text" class="form-control" name="product_name" value="{{ old('product_name') }}" readonly>
                    </div>
                    {{-- Source Name --}}
                     <div class="row mt-3">
                        <div class="col-md-12">
                            <label>Nguồn</label>
                            <textarea class="form-control" name="name" rows="4"></textarea>
                        </div>
                        @error('name', 'create_source_Errors')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary btn-save">
                        Lưu
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->create_source_Errors->any())
    <script>
        $(document).ready(function() {
            $('#create_soure_modal').modal('show');
        });
    </script>
@else
    <script>
    
      $('#create_soure_modal form').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function (res) {
                // res.id = material_source_id vừa insert
                alert ()
                if ( $('#create_soure_modal_mode').val() == "update"){
                    $('#updateModal').find('input[name="material_source_id"]').val(res.id);
                    $('#createModal').find('textarea[name="source_material_name"]').val(res.name);
                }else {
                    $('#createModal').find('input[name="material_source_id"]').val(res.id);
                    $('#createModal').find('textarea[name="source_material_name"]').val(res.name);
                    
                }
                $('#create_soure_modal').modal('hide');
            },
            error: function (xhr) {
                alert("Có lỗi xảy ra!");
            }
        });
    });
    </script>

@endif
