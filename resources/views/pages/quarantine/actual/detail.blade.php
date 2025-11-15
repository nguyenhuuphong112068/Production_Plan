<style>
  .selectProductModal-modal-size {
    max-width: 90% !important;
    width: 90% !important;
  }
</style>

<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog selectProductModal-modal-size" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <a href="{{ route('pages.general.home') }}">
          <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8; max-width:45px;">
        </a>
        
        <h4 class="modal-title w-100 text-center" id="createModal" style="color: #CDC717; font-size: 30px">
            Chi Tiết Bán Thành Phẩm Biệt Trữ
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
                      <th>Tên Sản Phẩm</th>
                      <th>Số Lô</th>
                      <th>Phòng SX CĐ Trước</th>
                      <th>Sản Lượng Thực Tế</th>
                      <th>Công Đoạn Tiếp Theo</th>
                      <th>Thời Gian SX Dự Kiến</th>
                      <th>Phòng Biệt Trữ</th>
                  </tr>
                  </thead>
                  <tbody id = "data_table_detail_body">
   
                  </tbody>
                </table>

            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

