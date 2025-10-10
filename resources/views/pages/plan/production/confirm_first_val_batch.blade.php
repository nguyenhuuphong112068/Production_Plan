<style>
  .fist_batch_modal_size {
    max-width: 90% !important;
    width: 90% !important;
  }
</style>

<div class="modal fade" id="fist_batch_modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog fist_batch_modal_size" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <a href="{{ route('pages.general.home') }}">
          <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
        </a>
        
        <h4 class="modal-title w-100 text-center" id="createModal" style="color: #CDC717; font-size: 30px">
            Xác Định Lô Đâu Thẩm Định Thứ Nhất
        </h4>

        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
          <span aria-hidden="true">&times;</span>
        </button>

      </div>

      <div class="modal-body" style="max-height: 100%; overflow-x: auto; ">
          <div class="card-body">
            <div class="table-responsive">
              <table id="data_table_first_batch" class="table table-bordered table-striped" style="font-size: 20px">
                <thead >

                    <tr>
                        <th>STT</th>
                        <th>Mã Sản Phẩm</th>
                        <th>Sản Phẩm</th>
                        <th>Số Lô</th>
                        <th>Thị Trường/ Qui Cách</th>
                        <th>Ngày dự kiến KCS</th>
                        <th>Ưu Tiên</th>
                        <th>Lô Thẩm định</th>
                        <th>Nguồn</th>
                        <th>Nguyên Liệu</th>
                        <th>Bao Bì</th>
                        <th>Ghi Chú</th>
                        <th>Chọn</th>
                       
                    </tr>

                </thead>
                  <tbody id = "tbody_first_val_batch">
   
                  </tbody>
                </table>

            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>


<script>
$(document).ready(function () {

    // Bắt sự kiện động
    $(document).on('click', '.btn-confirm-first-batch', function () {
        
        const batch = $(this).data('batch');
        const code_val = $(this).data('code_val'); // hoặc data('batch') nếu bạn dùng tên đó
        const createModal = $('#createModal')
        
        createModal.find('input[name="batchNo1"]').val(batch);
        createModal.find('input[name="code_val_first"]').val(code_val);

       
    });

})
</script>

