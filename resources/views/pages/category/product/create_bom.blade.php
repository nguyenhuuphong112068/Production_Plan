<style>
  .selectProductModal-modal-size {
    max-width: 90% !important;
    width: 90% !important;
  }
</style>

<div class="modal fade" id="createBOMModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog selectProductModal-modal-size" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <a href="{{ route('pages.general.home') }}">
          <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
        </a>
        
        {{-- <h4 class="modal-title w-100 text-center" id="createModal" style="color: #CDC717; font-size: 30px">
            Công Thức Bán Thành Phẩm
        </h4> --}}

        <h4 class="modal-title w-100 text-center" id="createModal"
            style="color: #CDC717; font-size: 30px">
              Tạo Mới Công Thức Bao Bì Đóng Gói Giả Định
            <br> 
            <span id="recipe_i_title" class="ml-2" ></span>
            
        </h4>

        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
          <span aria-hidden="true">&times;</span>
        </button> 
      </div>


      <div class="modal-body" style="max-height: 100%; overflow-x: auto; ">
          <div class="card-body">

            <div class="mb-3 p-3" style="background-color: #f1f3f5; border-radius: 5px; display:flex; gap:10px; align-items:center;">
                <strong style="white-space: nowrap;"><i class="fas fa-magic"></i> Gợi ý từ MMS:</strong>
                <input type="text" id="input_mms_code" class="form-control" placeholder="Nhập Mã MMS (ví dụ: FP...)" style="flex: 1;">
                <button type="button" class="btn btn-info" id="btn_fetch_mms" style="white-space: nowrap;">
                    <i class="fas fa-search"></i> Lấy công thức
                </button>
            </div>

            <div class="mb-3" style="display:flex; gap:10px; align-items:center; font-size:18px;">
                <input type="hidden" id="product_caterogy_id">
                <input type="text" id="input_code" class="form-control" placeholder="Mã Bao Bì">
                <input type="text" id="input_name" class="form-control" placeholder="Tên Bao Bì">
                <input type="number" id="input_qty" class="form-control" placeholder="Số Lượng">
                
                <select class="form-control" id="input_uom" >
                  <option> - Đơn Vị - </option>
                  @foreach ($units as $unit)
                  <option value="{{ $unit->code }}"
                       {{ old('unit_batch_qty') == $unit->code ? 'selected' : '' }}>
                  {{ $unit->code}}
                  </option>
                  @endforeach
                </select> 

                <button type="button" class="btn btn-success" id="btn_add_row">
                    <i class="fa fa-plus"></i>
                </button>

            </div>

            <div class="table-responsive">
                <table id="data_table_recipe" class="table table-bordered table-striped" style="font-size: 20px">
                  <thead >
                    <tr>
                      <th>STT</th>
                      <th>Mã Bao Bì</th>
                      <th>Tên Bao Bì</th>
                      <th>Số Lượng Bao Bì</th>
                      <th>Đơn Vị</th>
                      <th>Xóa</th>
                    
                    </tr>
                  </thead>

                  <tbody id = "data_table_create_recipe_body">
   
                  </tbody>
                </table>
              
            </div>
            <div class="modal-footer">
                    <button type="button" class="btn btn-secondary " data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary btn_save_recipe">
                       Lưu
                    </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  let rowIndex = 1;
  $('#btn_fetch_mms').on('click', function() {
      let mmsCode = $('#input_mms_code').val().trim();
      if (!mmsCode) {
          alert("Vui lòng nhập mã MMS để lấy công thức!");
          return;
      }
      
      let btn = $(this);
      let originalHtml = btn.html();
      btn.html('<i class="fas fa-spinner fa-spin"></i> Đang tải...').prop('disabled', true);

      $.ajax({
          url: "{{ route('pages.category.intermediate.recipe') }}",
          type: "POST",
          data: {
              _token: "{{ csrf_token() }}",
              intermediate_code: mmsCode,
              IsHypothesis: 0
          },
          success: function(res) {
              btn.html(originalHtml).prop('disabled', false);
              if (res && res.length > 0) {
                  res.forEach(function(item) {
                      let code = item.MatID || '';
                      let name = item.MaterialName || item.MatName || '';
                      let qty = item.MatQty || '';
                      let uom = item.uom || item.MatUOM || '';
                      
                      let newRow = `
                          <tr>
                              <td>${rowIndex}</td>
                              <td><input type="text" class="form-control code" value="${code}"></td>
                              <td><input type="text" class="form-control name" value="${name}"></td>
                              <td><input type="number" class="form-control qty" value="${qty}"></td>
                              <td><input type="text" class="form-control uom" value="${uom}"></td>
                            
                              <td>
                                  <button class="btn btn-danger btn-sm btn_remove">
                                      <i class="fa fa-trash"></i>
                                  </button>
                              </td>
                          </tr>
                      `;
                      $('#data_table_create_recipe_body').append(newRow);
                      rowIndex++;
                  });
                  alert("Đã lấy " + res.length + " nguyên liệu từ MMS!");
                  $('#input_mms_code').val('');
              } else {
                  alert("Không tìm thấy công thức cho mã này trên MMS!");
              }
          },
          error: function(err) {
              btn.html(originalHtml).prop('disabled', false);
              alert("Có lỗi xảy ra khi lấy dữ liệu từ MMS!");
          }
      });
  });

  $('#btn_add_row').on('click', function () {
     
      let code = $('#input_code').val().trim();
      let name = $('#input_name').val().trim();
      let qty = $('#input_qty').val().trim();
      let uom = $('#input_uom').val().trim();
      

      if (!code || !name || !qty || !uom) {
          alert("Vui lòng nhập đầy đủ thông tin bắt buộc!");
          return;
      }

      let newRow = `
          <tr>
              <td>${rowIndex}</td>
              <td><input type="text" class="form-control code" value="${code}"></td>
              <td><input type="text" class="form-control name" value="${name}"></td>
              <td><input type="number" class="form-control qty" value="${qty}"></td>
              <td><input type="text" class="form-control uom" value="${uom}"></td>
            
              <td>
                  <button class="btn btn-danger btn-sm btn_remove">
                      <i class="fa fa-trash"></i>
                  </button>
              </td>
          </tr>
      `;

      $('#data_table_create_recipe_body').append(newRow);

      rowIndex++;

      // Clear input trên cùng
      $('#input_code, #input_name, #input_qty, #input_uom').val('');
  });

  $(document).on('click', '.btn_remove', function () {
    $(this).closest('tr').remove();
  });

  $('.btn_save_recipe').on('click', function () {
 
    let data = [];

    $('#data_table_create_recipe_body tr').each(function () {

        let row = {
            product_caterogy_id: $('#product_caterogy_id').val(),
            code: $(this).find('.code').val(),
            name: $(this).find('.name').val(),
            qty: $(this).find('.qty').val(),
            uom: $(this).find('.uom').val(),
            version: $(this).find('.version').val(),
            mat_par_type : 1
        };

        data.push(row);
    });

    if (data.length === 0) {
        alert("Chưa có dữ liệu để lưu!");
        return;
    }

    $.ajax({
        url: "{{ route('pages.category.product.save_bom') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            items: data
        },
        success: function (res) {
            alert("Lưu thành công!");
            location.reload();
        },
        error: function (err) {
            alert("Có lỗi xảy ra!");
        }
    });

  });
  
</script>

