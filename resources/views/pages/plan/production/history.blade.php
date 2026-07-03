<style>
  .selectProductModal-modal-size {
    max-width: 100% !important;
    width: 100% !important;
  }
</style>

<div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog selectProductModal-modal-size" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <a href="{{ route('pages.general.home') }}">
          <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
        </a>
        
        <h4 class="modal-title w-100 text-center" id="createModal" style="color: #CDC717; font-size: 30px">
            Lịch Sử Thay Đổi Kế Hoạch Sản Xuất
        </h4>

        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
          <span aria-hidden="true">&times;</span>
        </button>

      </div>

      <div class="modal-body" style="max-height: 100%; overflow-x: auto; ">
          <div class="card-body">
            <div class="table-responsive">
                <table id="data_table_history" class="table table-bordered table-striped" style="font-size: 20px">
                  <thead >
                    <tr>
                        <th>STT</th>
                        <th>Mã Sản Phẩm</th>
                        <th style="width:7%">Sản Phẩm</th>
                        <th style="width:5%">
                            Số Lô Dự Kiến <br>
                            Số Lô Thực Tế <br>
                            Số lượng ĐG
                        </th>
                        <th>Thị Trường/ Qui Cách</th>
                        <th style="width:4%">Ngày dự kiến KCS</th>
                        <th>Ưu Tiên</th>
                        <th>Lô Thẩm định</th>

                        <th>
                            <div>(1) Ngày có đủ NL</div>
                            <div>(2) Ngày có đủ BB</div>
                            <div>(3) Ngày được phép cân</div>
                            <div>(4) Ngày HH NL chính</div>
                            <div>(5) Ngày HH BB</div>
                        </th>

                        <th>
                            <div>(1) PC trước</div>
                            <div>(2) THT trước</div>
                            <div>(3) BP trước</div>
                            <div>(4) ĐG trước</div>
                        </th>

                        <th>Nguồn</th>
                        <th style="width:15%">Ghi Chú</th>
                        <th>Version</th>
                        <th style="width: 100px">Lý Do</th>
                        <th>Người Tạo/ Ngày Tạo</th>
                    </tr>
                  </thead>
                  <tbody id = "data_table_history_body">
   
                  </tbody>
                </table>

            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

