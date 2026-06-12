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
                <a href="{{ route('pages.general.home') }}" class="mr-3">
                    <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.85; max-width: 42px;">
                </a>

                <h5 class="modal-title w-100 text-center" id="historyModalLabel">
                    Lịch Sử Thay Đổi: Nguồn Nguyên Liệu
                </h5>

                <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table id="data_table_history" class="table table-bordered table-striped w-100">
                    <thead id="data_table_history_head">
                        <tr>
                            <th class="text-center align-middle">Ngày Sửa</th>
                            <th class="text-center align-middle">Người Sửa</th>
                            <th class="text-center align-middle">Trạng Thái</th>
                            <th class="text-center align-middle">Mã Bán Thành Phẩm</th>
                            <th class="text-center align-middle">Nguồn</th>
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
