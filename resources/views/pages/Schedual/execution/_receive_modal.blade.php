{{-- Modal Nhận phòng (chọn lô / lịch bảo trì). Dùng chung trang Thực Thi Sản Xuất và Ghi Nhận Sản Xuất; JS ở _actions_js. --}}
<div class="modal fade exec-modal" id="execReceiveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header exec-mh-go">
                <h5 class="modal-title"><i class="fas fa-door-open"></i> Nhận phòng <span class="js-room"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="search" id="execPlanSearch" class="form-control mb-2"
                    style="max-width: 360px" placeholder="Tìm sản phẩm, số lô, mã...">
                <div id="execPlanHint" class="text-muted mb-2"></div>
                <div id="execPlanList" class="exec-plan-list"></div>

                <div class="exec-now mt-3">
                    <i class="far fa-clock"></i> Thời gian nhận phòng ghi theo giờ hệ thống lúc bấm nút · bây giờ
                    <b class="js-now"></b>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success js-submit" id="execReceiveSubmit">
                    <i class="fas fa-door-open"></i> Nhận phòng
                </button>
            </div>
        </div>
    </div>
</div>
