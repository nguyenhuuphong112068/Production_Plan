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
                            <table id="data_table_plan_master" class="table table-bordered table-striped" style="font-size: 16px">
                <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">

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
                        <th>Người Tạo/ Ngày Tạo</th>
                    </tr>

                </thead>
                  <tbody id = "data_table_first_val_batch">
   
                  </tbody>
                </table>

            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<!-- Scripts -->



