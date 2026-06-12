<style>
    .history-modal-dialog {
        max-width: 80% !important;
        width: 80% !important;
        margin: 1.75rem auto;
    }

    #historyModal .modal-content {
        background-color: #ffffff;
        border-radius: 10px;
        overflow: hidden;
    }

    #historyModal .modal-header {
        background-color: #ffffff;
        border-bottom: 2px solid #CDC717;
        padding: 14px 20px;
    }

    #historyModal .modal-title {
        color: #003A4F;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    #historyModal .modal-body {
        padding: 0;
        max-height: 75vh;
        overflow-y: auto;
        overflow-x: auto;
        background: #ffffff;
    }

    #historyModal .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }

    #data_table_history {
        font-size: 14px;
        margin-bottom: 0;
    }

    #data_table_history thead th {
        background-color: #f4f6f9 !important;
        color: #003A4F !important;
        font-weight: 700;
        white-space: nowrap;
        padding: 10px;
        position: sticky;
        top: 0;
        z-index: 10;
        text-align: center;
        border-bottom: 2px solid #dee2e6;
    }

    #data_table_history tbody td {
        padding: 8px 10px;
        vertical-align: middle;
        text-align: center;
    }
</style>

<div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog history-modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title w-100 text-center font-weight-bold" id="historyModalLabel">
                    <img src="{{ asset('img/logo/logo.png') }}" style="width: 25px; margin-right: 10px; margin-bottom: 5px;">
                    Lịch Sử Thay Đổi: Phòng
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="overflow-x: auto;">
                <table class="table table-bordered table-striped" style="white-space: nowrap;">
                    <thead id="data_table_history_head">
                        <tr>
                            <th class="text-center align-middle">Ngày Sửa</th>
                            <th class="text-center align-middle">Người Sửa</th>
                            <th class="text-center align-middle">Trạng Thái</th>
                            <th class="text-center align-middle">Mã Phòng</th>
                            <th class="text-center align-middle">Tên Phòng</th>
                            <th class="text-center align-middle">Thiết Bị Chính</th>
                            <th class="text-center align-middle">Công Suất</th>
                            <th class="text-center align-middle">Công Đoạn</th>
                            <th class="text-center align-middle">Loại Máy Ép Vỉ</th>
                            <th class="text-center align-middle">Tổ Quản Lý</th>
                            <th class="text-center align-middle">Phân Xưởng</th>
                        </tr>
                    </thead>
                    <tbody id="data_table_history_body">
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Đóng</button>
            </div>
        </div>
    </div>
</div>
