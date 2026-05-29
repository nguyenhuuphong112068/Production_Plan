<style>
    .history-modal-dialog {
        max-width: 95% !important;
        width: 95% !important;
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
        font-size: 13px;
    }

    #data_table_history thead th {
        background-color: #CDC717 !important;
        color: #003A4F !important;
        font-weight: 700;
        white-space: nowrap;
        padding: 6px 10px;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    #data_table_history tbody td {
        padding: 5px 8px;
        vertical-align: middle;
    }

    #data_table_history tbody tr.table-success td {
        background-color: #d4edda !important;
    }
</style>

<div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog history-modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                {{-- Logo --}}
                <a href="{{ route('pages.general.home') }}" class="mr-3">
                    <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.85; max-width: 42px;">
                </a>

                <h5 class="modal-title" id="historyModalLabel">
                    Lịch Sử Thay Đổi Lịch Sản Xuất
                </h5>

                <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="table-responsive">
                    <table id="data_table_history" class="table table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã Sản Phẩm</th>
                                <th>Sản Phẩm</th>
                                <th>Cỡ Lô</th>
                                <th>Số Lô</th>
                                <th>Phòng Sản Xuất</th>
                                <th>Thời Gian Sản Xuất</th>
                                <th>Thời Gian Vệ Sinh</th>
                                <th>Lý Do</th>
                                <th>Người Tạo / Ngày Tạo</th>
                                <th>Versions</th>
                            </tr>
                        </thead>
                        <tbody id="data_table_history_body">
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Đóng
                </button>
            </div>

        </div>
    </div>
</div>
