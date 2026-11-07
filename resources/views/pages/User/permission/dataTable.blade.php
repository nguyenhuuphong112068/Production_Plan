<style>
    .permission-list-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .06);
        /* topNAV cố định cao ~60px che phần đầu content-wrapper */
        margin-top: 66px;
    }

    .permission-list-title {
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 1px;
        color: #003A4F;
        margin: 0;
    }

    #data_table_permission thead th {
        background-color: #EEF5F7;
        color: #003A4F;
        font-weight: 700;
        border-bottom: 2px solid #CDC717;
    }

    #data_table_permission tbody tr:hover {
        background-color: #EEF5F7;
    }

    .permission-group-badge {
        background-color: #EEF5F7;
        color: #003A4F;
        border-radius: 8px;
        padding: 3px 10px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }

    .permission-code {
        font-size: 12px;
        color: #94A3B8;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }
</style>

<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="card permission-list-card">

            <div class="card-header" style="background-color: #fff; border-bottom: 3px solid #CDC717; padding: 20px">
                <h3 class="permission-list-title">
                    <i class="fas fa-key mr-2"></i> Danh Sách Quyền
                </h3>
            </div>

            <!-- /.card-Body -->
            <div class="card-body" style="padding: 20px">
                <table id="data_table_permission" class="table table-bordered table-striped" style="font-size: 15px">
                    <thead style="position: sticky; top: 60px; z-index: 1020">
                        <tr>
                            <th style="width: 60px">STT</th>
                            <th style="width: 220px">Nhóm Chức Năng</th>
                            <th>Phân Quyền</th>
                            <th>Diễn Giải</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datas as $data)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="permission-group-badge">{{ $data->group_name }}</span></td>
                                <td>
                                    <div>{{ $data->display_name ?: $data->name }}</div>
                                    <div class="permission-code">{{ $data->name }}</div>
                                </td>
                                <td>{{ $data->description !== $data->display_name ? $data->description : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </section>
    <!-- /.content -->
</div>
