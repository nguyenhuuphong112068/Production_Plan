{{-- Modal "Lịch sử nhận trả phòng" (nút trên card phòng). Dùng chung trang Thực Thi Sản Xuất và Ghi Nhận Sản Xuất;
     JS ở _actions_js (openHistory). --}}
<div class="modal fade exec-modal" id="execHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header exec-mh-hist">
                <h5 class="modal-title"><i class="fas fa-history"></i> Lịch sử nhận trả phòng <span class="js-room"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="execHistoryHint" class="small text-muted mb-2"></div>
                <div class="table-responsive exec-hist-wrap">
                    <table class="table table-sm table-hover exec-hist">
                        <thead>
                            <tr>
                                <th style="width: 36px">#</th>
                                <th>Lô / lịch</th>
                                <th>Nhận phòng</th>
                                <th>Kết thúc BT / Nhận VS</th>
                                <th>Trả phòng</th>
                                <th style="width: 110px">Giữ phòng</th>
                            </tr>
                        </thead>
                        <tbody id="execHistoryBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
