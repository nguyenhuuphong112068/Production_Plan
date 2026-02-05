<style>
  .selectProductModal-modal-size {
    max-width: 90% !important;
    width: 90% !important;
  }
</style>

<div class="modal fade" id="intermediateRecipeModal" tabindex="-1" role="dialog" aria-hidden="true">
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
            Công Thức Bán Thành Phẩm
            <br>
            <span id="recipe_intermediate_code" class="ml-2" ></span>
        </h4>

        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
          <span aria-hidden="true">&times;</span>
        </button> 

      </div>

      <div class="modal-body" style="max-height: 100%; overflow-x: auto; ">
          <div class="card-body">
            <div class="table-responsive">
                <table id="data_table_recipe" class="table table-bordered table-striped" style="font-size: 20px">
                  <thead >
                    <tr>
                      <th>STT</th>
                      <th>Mã Nguyên Liệu</th>
                      <th>Tên Nguyên Liệu</th>
                      <th>Khối Lượng Nguyên Liệu</th>
                      <th>Đơn Vị</th>
                      <th>Version</th>
                    </tr>
                  </thead>

                  <tbody id = "data_table_recipe_body">
   
                  </tbody>
                </table>

            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

