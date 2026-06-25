@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @php
        $can_propose = user_has_permission(session('user')['userId'] ?? 0, 'schedual_warning_propose', 'boolean');
        $can_approve = user_has_permission(session('user')['userId'] ?? 0, 'schedual_warning_approve', 'boolean');
    @endphp
    <style>
        .table-responsive thead th {
            position: sticky;
            top: 0;
            background-color: #f4f6f9;
            z-index: 10;
            border-top: 0 !important;
            box-shadow: inset 0 1px 0 #dee2e6, inset 0 -2px 0 #dee2e6;
        }

        .chat-container {
            display: flex;
            flex-direction: column;
            height: 300px;
            overflow-y: auto;
            background-color: #f4f6f9;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .chat-msg {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 15px;
            max-width: 80%;
            word-wrap: break-word;
        }

        .chat-msg.mine {
            align-self: flex-end;
            background-color: #007bff;
            color: white;
        }

        .chat-msg.others {
            align-self: flex-start;
            background-color: #e9ecef;
            color: black;
        }

        .chat-msg-header {
            font-size: 0.8em;
            margin-bottom: 3px;
            opacity: 0.8;
        }
    </style>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark">
                            <i class="fas fa-exclamation-triangle text-warning"></i> Cảnh Báo Lịch Sản Xuất
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="warningTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="unmet-tab" data-toggle="tab" href="#unmet" role="tab"
                            aria-controls="unmet" aria-selected="true">
                            Danh sách không Đáp Ứng Ngày KSC <span
                                class="badge badge-danger">{{ $unmetPlans->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="material-tab" data-toggle="tab" href="#material" role="tab"
                            aria-controls="material" aria-selected="false">
                            Cảnh Báo Ngày Đáp Ứng NL/BB <span
                                class="badge badge-warning">{{ $materialWarnings->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="proposed-tab" data-toggle="tab" href="#proposed" role="tab"
                            aria-controls="proposed" aria-selected="false">
                            Đề Nghị Đổi Ngày KCS <span
                                class="badge badge-info">{{ isset($proposedChanges) ? $proposedChanges->count() : 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="proposed-material-tab" data-toggle="tab" href="#proposed-material"
                            role="tab" aria-controls="proposed-material" aria-selected="false">
                            Xem Xét Đổi Ngày NL/BB <span
                                class="badge badge-primary">{{ isset($proposedMaterialChanges) ? $proposedMaterialChanges->count() : 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="history-tab" data-toggle="tab" href="#history-tab-pane" role="tab"
                            aria-controls="history-tab-pane" aria-selected="false">
                            Lịch Sử Đề Nghị <span
                                class="badge badge-secondary">{{ isset($proposalHistories) ? $proposalHistories->count() : 0 }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="warningTabsContent">
                    <!-- Tab 1: Không Đáp Ứng Ngày Cần Hàng -->
                    <div class="tab-pane fade show active" id="unmet" role="tabpanel" aria-labelledby="unmet-tab">
                        <div class="card card-danger card-outline">
                            <div class="card-header">
                                @if ($can_propose)
                                    <button type="button" class="btn btn-sm btn-primary" id="btn-propose-date">Đề nghị chấp
                                        nhận ngày đáp ứng</button>
                                @endif
                            </div>
                            <div class="card-body table-responsive" style="height: calc(100vh - 200px);">
                                <table id="table_unmet" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 40px;">
                                                @if ($can_propose)
                                                    <input type="checkbox" id="selectAllUnmet">
                                                @endif
                                            </th>
                                            <th>Mã Sản Phẩm</th>
                                            <th>Tên Sản Phẩm</th>
                                            <th>Mã Lô</th>
                                            <th>Bắt Đầu (Dự Kiến)</th>
                                            <th>Kết Thúc (Dự Kiến)</th>
                                            <th>Ngày Đáp Ứng Dự Kiến</th>
                                            <th>Ngày KCS Dự Kiến</th>
                                            <th>Số Ngày Trễ Hạn</th>
                                            <th>Trao đổi thông tin</th>
                                            <th>Lịch Sử</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($unmetPlans as $item)
                                            @php
                                                $responseDate = $item->max_end
                                                    ? \Carbon\Carbon::parse($item->max_end)->addDays(5)->startOfDay()
                                                    : null;
                                                $kcsDate = $item->expected_date
                                                    ? \Carbon\Carbon::parse($item->expected_date)->startOfDay()
                                                    : null;
                                                $delay = 0;
                                                if ($responseDate && $kcsDate && $responseDate > $kcsDate) {
                                                    $delay = $kcsDate->diffInDays($responseDate);
                                                }
                                            @endphp
                                            <tr>
                                                <td class="text-center">
                                                    @if ($can_propose && !$item->expected_date_change)
                                                        <input type="checkbox" class="row-checkbox"
                                                            value="{{ $item->id }}">
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $item->finished_product_code }}
                                                    @if ($item->expected_date_change)
                                                        <br><span class="badge badge-info mt-1">Đã đề nghị thay đổi ngày
                                                            KCS</span>
                                                    @endif
                                                </td>
                                                <td>{{ $item->product_name }}</td>
                                                <td>{{ $item->batch }}</td>
                                                <td>{{ $item->min_start ? \Carbon\Carbon::parse($item->min_start)->format('d/m/Y H:i') : '' }}
                                                </td>
                                                <td class="text-danger font-weight-bold">
                                                    {{ $item->max_end ? \Carbon\Carbon::parse($item->max_end)->format('d/m/Y H:i') : '' }}
                                                </td>
                                                <td class="text-info font-weight-bold">
                                                    {{ $responseDate ? $responseDate->format('d/m/Y') : '' }}
                                                </td>
                                                <td class="text-danger font-weight-bold">
                                                    {{ $kcsDate ? $kcsDate->format('d/m/Y') : '' }}
                                                </td>
                                                <td
                                                    class="text-center font-weight-bold {{ $delay > 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ $delay > 0 ? $delay . ' ngày' : '-' }}
                                                </td>
                                                <td style="min-width:300px">
                                                    {{-- ===== LIST COMMENT ===== --}}
                                                    <div class="chat-box"
                                                        style="max-height:150px; overflow-y:auto; font-size:14px; text-align: left;">
                                                        @forelse ($commentsGrouped[$item->id] ?? [] as $comment)
                                                            <div class="mb-2 p-2 border rounded"
                                                                style="background-color: {{ \Illuminate\Support\Str::startsWith($comment->deparment, 'PX') ? '#d4edda' : '#d1ecf1' }}; border-radius:15px; padding:6px;">
                                                                <div style="font-weight:600">
                                                                    {{ $comment->user_name }}
                                                                    <small class="text-muted">
                                                                        {{ \Carbon\Carbon::parse($comment->created_at)->format('d/m H:i') }}
                                                                    </small>
                                                                </div>
                                                                <div>{{ $comment->message }}</div>
                                                            </div>
                                                        @empty
                                                            <div class="text-muted">Chưa có trao đổi</div>
                                                        @endforelse
                                                    </div>
                                                    {{-- ===== INPUT CHAT ===== --}}
                                                    @if ($can_propose || $can_approve)
                                                        <div class="chat-input-wrapper d-flex mt-2">
                                                            <input type="text"
                                                                class="form-control form-control-sm chat-input"
                                                                data-row-id="{{ $item->id }}"
                                                                placeholder="Nhập trao đổi...">
                                                            <button class="btn btn-sm btn-primary send-comment"
                                                                data-row-id="{{ $item->id }}">Gửi</button>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button type="button" class="btn btn-sm btn-info btn-view-history"
                                                        style="position: relative;" data-id="{{ $item->id }}"
                                                        title="Lịch sử đề nghị">
                                                        <i class="fas fa-history"></i>
                                                        @if (isset($proposalHistoryCounts) && isset($proposalHistoryCounts[$item->id]) && $proposalHistoryCounts[$item->id] > 0)
                                                            <span class="badge badge-danger"
                                                                style="position: absolute; top: -8px; right: -8px; border-radius: 50%; font-size: 10px; padding: 3px 5px;">
                                                                {{ $proposalHistoryCounts[$item->id] }}
                                                            </span>
                                                        @endif
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Cảnh Báo Ngày Đáp Ứng NL/BB -->
                    <div class="tab-pane fade" id="material" role="tabpanel" aria-labelledby="material-tab">
                        <div class="card card-warning card-outline">
                            <div class="card-header">
                                @if ($can_propose)
                                    <button type="button" class="btn btn-sm btn-primary"
                                        id="btn-propose-material-date">Đề
                                        nghị chấp nhận thay đổi NL/BB</button>
                                @endif
                            </div>
                            <div class="card-body table-responsive" style="height: calc(100vh - 200px);">
                                <table id="table_material" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 40px;">
                                                @if ($can_propose)
                                                    <input type="checkbox" id="selectAllMaterial">
                                                @endif
                                            </th>
                                            <th>Mã Sản Phẩm</th>
                                            <th>Tên Sản Phẩm</th>
                                            <th>Mã Lô</th>
                                            <th>Công đoạn</th>
                                            <th>Bắt Đầu (Dự Kiến)</th>
                                            <th>Loại Ngày Cảnh Báo</th>
                                            <th>Trao đổi thông tin</th>
                                            <th>Lịch Sử</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($materialWarnings as $item)
                                            @php
                                                $violations = is_array($item->violations)
                                                    ? $item->violations
                                                    : json_decode($item->violations, true) ?? [];
                                                $types = [];
                                                $stages = [];
                                                $stage_name = [
                                                    1 => 'Cân Nguyên Liệu',
                                                    3 => 'Pha Chế',
                                                    4 => 'Trộn Hoàn Tất',
                                                    5 => 'Định Hình',
                                                    6 => 'Bao Phim',
                                                    7 => 'ĐGSC - ĐGTC',
                                                ];
                                                foreach ($violations as $violation) {
                                                    $types[] =
                                                        $violation['label'] .
                                                        ' (' .
                                                        \Carbon\Carbon::parse($violation['date'])->format('d/m/Y') .
                                                        ')';
                                                    if (isset($violation['stage_code'])) {
                                                        $sCode = $violation['stage_code'];
                                                        $sName = isset($stage_name[$sCode])
                                                            ? $stage_name[$sCode]
                                                            : 'CĐ ' . $sCode;
                                                        if (!in_array($sName, $stages)) {
                                                            $stages[] = $sName;
                                                        }
                                                    }
                                                }
                                                $typeStr = implode('<br>', $types);
                                                $stageStr = implode('<br>', $stages);
                                                if (empty($typeStr)) {
                                                    $typeStr = 'Khác';
                                                }
                                                if (empty($stageStr)) {
                                                    $stageStr = '-';
                                                }
                                            @endphp
                                            <tr>
                                                <td class="text-center">
                                                    @if ($can_propose && !$item->responsed_date_change)
                                                        <input type="checkbox" class="row-checkbox-material"
                                                            value="{{ $item->id }}">
                                                    @endif
                                                </td>
                                                <td>{{ $item->finished_product_code }}</td>
                                                <td>{{ $item->product_name }}</td>
                                                <td>{{ $item->batch }}</td>
                                                <td class="font-weight-bold text-secondary text-center">
                                                    {{ $stageStr }}
                                                </td>
                                                <td class="text-danger font-weight-bold">
                                                    {{ $item->min_start ? \Carbon\Carbon::parse($item->min_start)->format('d/m/Y H:i') : '' }}
                                                </td>
                                                <td class="font-weight-bold text-info">
                                                    {!! $typeStr !!}
                                                </td>
                                                <td style="min-width:300px">
                                                    {{-- ===== LIST COMMENT ===== --}}
                                                    <div class="chat-box"
                                                        style="max-height:150px; overflow-y:auto; font-size:14px; text-align: left;">
                                                        @forelse ($commentsGrouped[$item->id] ?? [] as $comment)
                                                            <div class="mb-2 p-2 border rounded"
                                                                style="background-color: {{ \Illuminate\Support\Str::startsWith($comment->deparment, 'PX') ? '#d4edda' : '#d1ecf1' }}; border-radius:15px; padding:6px;">
                                                                <div style="font-weight:600">
                                                                    {{ $comment->user_name }}
                                                                    <small class="text-muted">
                                                                        {{ \Carbon\Carbon::parse($comment->created_at)->format('d/m H:i') }}
                                                                    </small>
                                                                </div>
                                                                <div>{{ $comment->message }}</div>
                                                            </div>
                                                        @empty
                                                            <div class="text-muted">Chưa có trao đổi</div>
                                                        @endforelse
                                                    </div>
                                                    {{-- ===== INPUT CHAT ===== --}}
                                                    @if ($can_propose || $can_approve)
                                                        <div class="chat-input-wrapper d-flex mt-2">
                                                            <input type="text"
                                                                class="form-control form-control-sm chat-input"
                                                                data-row-id="{{ $item->id }}"
                                                                placeholder="Nhập trao đổi...">
                                                            <button class="btn btn-sm btn-primary send-comment"
                                                                data-row-id="{{ $item->id }}">Gửi</button>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button type="button" class="btn btn-sm btn-info btn-view-history"
                                                        style="position: relative;" data-id="{{ $item->id }}"
                                                        title="Lịch sử đề nghị">
                                                        <i class="fas fa-history"></i>
                                                        @if (isset($proposalHistoryCounts) && isset($proposalHistoryCounts[$item->id]) && $proposalHistoryCounts[$item->id] > 0)
                                                            <span class="badge badge-danger"
                                                                style="position: absolute; top: -8px; right: -8px; border-radius: 50%; font-size: 10px; padding: 3px 5px;">
                                                                {{ $proposalHistoryCounts[$item->id] }}
                                                            </span>
                                                        @endif
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Đề Nghị Đổi Ngày KCS -->
                    <div class="tab-pane fade" id="proposed" role="tabpanel" aria-labelledby="proposed-tab">
                        <div class="card card-info card-outline">
                            <div class="card-header">
                                @if ($can_approve)
                                    <button type="button" class="btn btn-sm btn-success" id="btn-accept-bulk">Chấp nhận
                                        mục
                                        đã
                                        chọn</button>
                                @endif
                            </div>
                            <div class="card-body table-responsive" style="height: calc(100vh - 200px);">
                                <table id="table_proposed" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 40px;">
                                                @if ($can_approve)
                                                    <input type="checkbox" id="selectAllProposed">
                                                @endif
                                            </th>
                                            <th>Mã Sản Phẩm</th>
                                            <th>Tên Sản Phẩm</th>
                                            <th>Mã Lô</th>
                                            <th>Bắt Đầu (Dự Kiến)</th>
                                            <th>Kết Thúc (Dự Kiến)</th>
                                            <th>Ngày Đáp Ứng Dự Kiến</th>
                                            <th>Ngày KCS Dự Kiến</th>
                                            <th>Số Ngày Trễ Hạn</th>
                                            <th>Trao đổi thông tin</th>
                                            <th>Lịch Sử</th>
                                            <th>
                                                @if ($can_approve)
                                                    Hành Động
                                                @endif
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (isset($proposedChanges))
                                            @foreach ($proposedChanges as $item)
                                                @php
                                                    $responseDate = $item->max_end
                                                        ? \Carbon\Carbon::parse($item->max_end)
                                                            ->addDays(5)
                                                            ->startOfDay()
                                                        : null;
                                                    $kcsDate = $item->expected_date
                                                        ? \Carbon\Carbon::parse($item->expected_date)->startOfDay()
                                                        : null;
                                                    $delay = 0;
                                                    if ($responseDate && $kcsDate && $responseDate > $kcsDate) {
                                                        $delay = $kcsDate->diffInDays($responseDate);
                                                    }
                                                @endphp
                                                <tr>
                                                    <td class="text-center">
                                                        @if ($can_approve)
                                                            <input type="checkbox" class="row-checkbox-proposed"
                                                                value="{{ $item->id }}"
                                                                data-response-date="{{ $responseDate ? $responseDate->format('Y-m-d') : '' }}">
                                                        @endif
                                                    </td>
                                                    <td>{{ $item->finished_product_code }}</td>
                                                    <td>{{ $item->product_name }}</td>
                                                    <td>{{ $item->batch }}</td>
                                                    <td>{{ $item->min_start ? \Carbon\Carbon::parse($item->min_start)->format('d/m/Y H:i') : '' }}
                                                    </td>
                                                    <td class="text-danger font-weight-bold">
                                                        {{ $item->max_end ? \Carbon\Carbon::parse($item->max_end)->format('d/m/Y H:i') : '' }}
                                                    </td>
                                                    <td class="text-info font-weight-bold">
                                                        {{ $responseDate ? $responseDate->format('d/m/Y') : '' }}
                                                    </td>
                                                    <td class="text-danger font-weight-bold">
                                                        {{ $kcsDate ? $kcsDate->format('d/m/Y') : '' }}
                                                    </td>
                                                    <td
                                                        class="text-center font-weight-bold {{ $delay > 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ $delay > 0 ? $delay . ' ngày' : '-' }}
                                                    </td>
                                                    <td style="min-width:300px">
                                                        {{-- ===== LIST COMMENT ===== --}}
                                                        <div class="chat-box"
                                                            style="max-height:150px; overflow-y:auto; font-size:14px; text-align: left;">
                                                            @forelse ($commentsGrouped[$item->id] ?? [] as $comment)
                                                                <div class="mb-2 p-2 border rounded"
                                                                    style="background-color: {{ \Illuminate\Support\Str::startsWith($comment->deparment, 'PX') ? '#d4edda' : '#d1ecf1' }}; border-radius:15px; padding:6px;">
                                                                    <div style="font-weight:600">
                                                                        {{ $comment->user_name }}
                                                                        <small class="text-muted">
                                                                            {{ \Carbon\Carbon::parse($comment->created_at)->format('d/m H:i') }}
                                                                        </small>
                                                                    </div>
                                                                    <div>{{ $comment->message }}</div>
                                                                </div>
                                                            @empty
                                                                <div class="text-muted">Chưa có trao đổi</div>
                                                            @endforelse
                                                        </div>
                                                        {{-- ===== INPUT CHAT ===== --}}
                                                        @if ($can_propose || $can_approve)
                                                            <div class="chat-input-wrapper d-flex mt-2">
                                                                <input type="text"
                                                                    class="form-control form-control-sm chat-input"
                                                                    data-row-id="{{ $item->id }}"
                                                                    placeholder="Nhập trao đổi...">
                                                                <button class="btn btn-sm btn-primary send-comment"
                                                                    data-row-id="{{ $item->id }}">Gửi</button>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <button type="button"
                                                            class="btn btn-sm btn-info btn-view-history mb-1"
                                                            style="position: relative;" data-id="{{ $item->id }}"
                                                            title="Lịch sử đề nghị">
                                                            <i class="fas fa-history"></i>
                                                            @if (isset($proposalHistoryCounts) && isset($proposalHistoryCounts[$item->id]) && $proposalHistoryCounts[$item->id] > 0)
                                                                <span class="badge badge-danger"
                                                                    style="position: absolute; top: -8px; right: -8px; border-radius: 50%; font-size: 10px; padding: 3px 5px;">
                                                                    {{ $proposalHistoryCounts[$item->id] }}
                                                                </span>
                                                            @endif
                                                        </button>
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($can_approve)
                                                            <button type="button"
                                                                class="btn btn-sm btn-success btn-accept-single mb-1"
                                                                data-id="{{ $item->id }}"
                                                                data-response-date="{{ $responseDate ? $responseDate->format('Y-m-d') : '' }}">
                                                                Chấp nhận
                                                            </button>
                                                            <button type="button"
                                                                class="btn btn-sm btn-danger btn-reject-single mb-1"
                                                                data-id="{{ $item->id }}">
                                                                Không chấp nhận
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Tab 5: Lịch sử đề nghị -->
                    <div class="tab-pane fade" id="history-tab-pane" role="tabpanel" aria-labelledby="history-tab">
                        <div class="card card-secondary card-outline">
                            <div class="card-body table-responsive" style="height: calc(100vh - 200px);">
                                <table id="table_history_all" class="table table-bordered table-striped">
                                    <thead>
                                        <th>Mã sản phẩm</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Số Lô</th>
                                        <th>Loại</th>
                                        <th style="min-width:400px">Lịch sử hành động</th>
                                        <th style="min-width:300px">Trao đổi thông tin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (isset($proposalHistories))
                                            @foreach ($proposalHistories->groupBy('plan_master_id') as $plan_master_id => $histories)
                                                @php $firstHist = $histories->first(); @endphp
                                                <tr>
                                                    <td>{{ $firstHist->product_code }}</td>
                                                    <td>{{ $firstHist->product_name }}</td>
                                                    <td>{{ $firstHist->batch }}</td>
                                                    <td>
                                                        <span
                                                            class="badge badge-{{ $firstHist->type == 'KCS' ? 'info' : 'primary' }}">
                                                            {{ $firstHist->type }}
                                                        </span>
                                                    </td>
                                                    <td style="min-width:400px">
                                                        <div class="action-history-box"
                                                            style="max-height:150px; overflow-y:auto; font-size:14px; text-align: left;">
                                                            @foreach ($histories as $hist)
                                                                <div class="mb-2 p-2 border rounded"
                                                                    style="background-color: #f8f9fa; border-radius:10px;">
                                                                    <div
                                                                        style="font-weight:600; display:flex; justify-content:space-between;">
                                                                        <span>{{ $hist->user_name }}</span>
                                                                        <small
                                                                            class="text-muted">{{ \Carbon\Carbon::parse($hist->created_at)->format('d/m/Y H:i') }}</small>
                                                                    </div>
                                                                    <div class="mt-1">
                                                                        @if ($hist->action == 'PROPOSE')
                                                                            <span class="badge badge-warning">Đề
                                                                                nghị</span>
                                                                        @elseif ($hist->action == 'ACCEPT')
                                                                            <span class="badge badge-success">Chấp
                                                                                nhận</span>
                                                                        @elseif ($hist->action == 'REJECT')
                                                                            <span class="badge badge-danger">Từ chối</span>
                                                                        @else
                                                                            <span
                                                                                class="badge badge-secondary">{{ $hist->action }}</span>
                                                                        @endif

                                                                        @if ($hist->old_date || $hist->new_date)
                                                                            <span class="ml-2 font-weight-bold">
                                                                                {{ $hist->old_date ? \Carbon\Carbon::parse($hist->old_date)->format('d/m/Y') : '' }}
                                                                                <i
                                                                                    class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                                {{ $hist->new_date ? \Carbon\Carbon::parse($hist->new_date)->format('d/m/Y') : '' }}
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    @if ($hist->reason)
                                                                        <div class="text-muted mt-1"
                                                                            style="font-size: 12px;"><i>Lý do:
                                                                                {{ $hist->reason }}</i></div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td style="min-width:300px">
                                                        <div class="chat-box"
                                                            style="max-height:150px; overflow-y:auto; font-size:14px; text-align: left;">
                                                            @forelse ($commentsGrouped[$plan_master_id] ?? [] as $comment)
                                                                <div class="mb-2 p-2 border rounded"
                                                                    style="background-color: {{ \Illuminate\Support\Str::startsWith($comment->deparment, 'PX') ? '#d4edda' : '#d1ecf1' }}; border-radius:15px; padding:6px;">
                                                                    <div style="font-weight:600">
                                                                        {{ $comment->user_name }}
                                                                        <small class="text-muted">
                                                                            {{ \Carbon\Carbon::parse($comment->created_at)->format('d/m H:i') }}
                                                                        </small>
                                                                    </div>
                                                                    <div>{{ $comment->message }}</div>
                                                                </div>
                                                            @empty
                                                                <div class="text-muted">Chưa có trao đổi</div>
                                                            @endforelse
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    </section>
    </div>

    <!-- Modal Lịch Sử -->
    <div class="modal fade" id="proposalHistoryModal" tabindex="-1" role="dialog"
        aria-labelledby="proposalHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="proposalHistoryModalLabel"><i class="fas fa-history text-secondary"></i>
                        Lịch Sử Đề Nghị</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body table-responsive">
                    <table class="table table-bordered table-sm" id="table_modal_history">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Người thực hiện</th>
                                <th>Loại</th>
                                <th>Hành động</th>
                                <th>Chi tiết (Ngày)</th>
                                <th>Lý do / Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody id="proposal-history-tbody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script>
        window.addEventListener('load', function() {
            $('#table_unmet').DataTable({
                "paging": true,
                "lengthChange": true,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Tất cả"]
                ],
                "searching": true,
                "ordering": true,
                "order": [
                    [8, "asc"]
                ],
                "columnDefs": [{
                    "orderable": false,
                    "targets": [0, 9]
                }],
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
                }
            });

            $('#table_material').DataTable({
                "paging": true,
                "lengthChange": true,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Tất cả"]
                ],
                "searching": true,
                "ordering": true,
                "columnDefs": [{
                    "orderable": false,
                    "targets": [0, 7, 8]
                }],
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
                }
            });

            $('#table_proposed_material').DataTable({
                "paging": true,
                "lengthChange": true,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Tất cả"]
                ],
                "searching": true,
                "ordering": true,
                "columnDefs": [{
                    "orderable": false,
                    "targets": [0, 7, 8]
                }],
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
                }
            });

            $('#table_proposed').DataTable({
                "paging": true,
                "lengthChange": true,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Tất cả"]
                ],
                "searching": true,
                "ordering": true,
                "order": [
                    [8, "asc"]
                ],
                "columnDefs": [{
                    "orderable": false,
                    "targets": [0, 9, 10, 11]
                }],
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
                }
            });

            // Checkbox logic
            $('#selectAllUnmet').on('change', function() {
                $('.row-checkbox').prop('checked', $(this).prop('checked'));
            });

            $('#selectAllProposed').on('change', function() {
                $('.row-checkbox-proposed').prop('checked', $(this).prop('checked'));
            });

            $('#selectAllMaterial').on('change', function() {
                $('.row-checkbox-material').prop('checked', $(this).prop('checked'));
            });

            $('#selectAllProposedMaterial').on('change', function() {
                $('.row-checkbox-proposed-material').prop('checked', $(this).prop('checked'));
            });

            $('#btn-propose-date').on('click', function() {
                let ids = [];
                $('.row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) {
                    Swal.fire('Thông báo', 'Vui lòng chọn ít nhất 1 mục để đề nghị!', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Xác nhận',
                    text: 'Bạn có chắc chắn gửi đề nghị chấp nhận ngày đáp ứng cho ' + ids.length +
                        ' mục?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Huỷ'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('pages.Schedual.warning.proposeDateChange') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                plan_master_ids: ids
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Thành công', response.message, 'success')
                                        .then(() => {
                                            removeRowsAndUpdateUI('table_unmet',
                                                'unmet-tab', ids);
                                        });
                                } else {
                                    Swal.fire('Lỗi', 'Có lỗi xảy ra: ' + response
                                        .message, 'error');
                                }
                            },
                            error: function(err) {
                                Swal.fire('Lỗi', 'Có lỗi xảy ra khi gọi server!',
                                    'error');
                                console.error(err);
                            }
                        });
                    }
                });
            });

            // Logic cập nhật UI sau khi thao tác thành công (không reload trang)
            function removeRowsAndUpdateUI(tableId, tabId, ids) {
                let table = $('#' + tableId).DataTable();
                let badge = $('#' + tabId + ' .badge');
                let currentCount = parseInt(badge.text()) || 0;

                ids.forEach(function(id) {
                    let btn = $('#' + tableId + ' button[data-id="' + id + '"]');
                    let chk = $('#' + tableId + ' input[value="' + id + '"]');
                    let row = btn.length ? btn.closest('tr') : (chk.length ? chk.closest('tr') : null);

                    if (row && row.length) {
                        table.row(row).remove();
                        currentCount--;
                    }
                });


                table.draw(false);
                badge.text(Math.max(0, currentCount));
            }

            // Logic Chấp nhận ngày cho Tab 3
            function submitAcceptDate(ids, date, rowDates) {
                $.ajax({
                    url: "{{ route('pages.Schedual.warning.acceptDateChange') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        plan_master_ids: ids,
                        new_date: date,
                        row_dates: rowDates
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Thành công', response.message, 'success').then(() => {
                                removeRowsAndUpdateUI('table_proposed', 'proposed-tab', ids);
                            });
                        } else {
                            Swal.fire('Lỗi', 'Có lỗi xảy ra: ' + response.message, 'error');
                        }
                    },
                    error: function(err) {
                        Swal.fire('Lỗi', 'Có lỗi xảy ra khi gọi server!', 'error');
                        console.error(err);
                    }
                });
            }

            $('.btn-accept-single').on('click', function() {
                let id = $(this).data('id');
                let defaultDate = $(this).data('response-date');

                Swal.fire({
                    title: 'Chấp nhận đổi ngày',
                    html: '<label for="swal-input-date" class="form-label">Chọn ngày KCS mới:</label>' +
                        '<input id="swal-input-date" class="form-control" type="date" value="' +
                        defaultDate + '">',
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Huỷ',
                    preConfirm: () => {
                        return document.getElementById('swal-input-date').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitAcceptDate([id], result.value, {
                            [id]: defaultDate
                        });
                    }
                });
            });

            $('#btn-accept-bulk').on('click', function() {
                let ids = [];
                let rowDates = {};
                $('.row-checkbox-proposed:checked').each(function() {
                    let id = $(this).val();
                    ids.push(id);
                    rowDates[id] = $(this).data('response-date');
                });

                if (ids.length === 0) {
                    Swal.fire('Thông báo', 'Vui lòng chọn ít nhất 1 mục để chấp nhận!', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Chấp nhận đổi ngày hàng loạt',
                    html: '<p>Đang áp dụng cho <b>' + ids.length + '</b> mục.</p>' +
                        '<label for="swal-input-date-bulk" class="form-label">Chọn ngày KCS mới chung (Để trống sẽ lấy Ngày Đáp Ứng của từng mục):</label>' +
                        '<input id="swal-input-date-bulk" class="form-control" type="date">',
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Huỷ',
                    preConfirm: () => {
                        return document.getElementById('swal-input-date-bulk').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitAcceptDate(ids, result.value, rowDates);
                    }
                });
            });

            // Logic Chat Mới (Inline)
            $(document).on('click', '.send-comment', function() {
                let button = $(this);
                let rowId = button.data('row-id');
                let input = $('.chat-input[data-row-id="' + rowId + '"]');
                let message = input.val().trim();

                if (!message) return;

                if (button.prop('disabled')) return;

                button.prop('disabled', true);
                button.html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                );

                $.ajax({
                    url: "{{ route('pages.Schedual.warning.postComment') }}",
                    type: "POST",
                    data: {
                        plan_master_id: rowId,
                        message: message,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        if (res.success) {
                            let bgColor = res.department && res.department.startsWith('PX') ?
                                '#d4edda' : '#d1ecf1';

                            // Xóa text "Chưa có trao đổi" nếu có
                            let chatBox = input.closest('td').find('.chat-box');
                            chatBox.find('.text-muted:contains("Chưa có trao đổi")').remove();

                            let newComment = `
                                <div class="mb-2 p-2 border rounded" style="background-color: ${bgColor}; border-radius:15px; padding:6px;">
                                    <div style="font-weight:600">
                                        ${res.user_name}
                                        <small class="text-muted">${res.time}</small>
                                    </div>
                                    <div>${res.message.replace(/@(.*?)\[\d+\]/g, '<b class="text-primary">@$1</b>')}</div>
                                </div>
                            `;

                            chatBox.append(newComment);
                            input.val('');
                            chatBox.scrollTop(chatBox[0].scrollHeight);
                        } else {
                            Swal.fire('Lỗi', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Lỗi', 'Gửi thất bại!', 'error');
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        button.html('Gửi');
                    }
                });
            });

            $(document).on('keypress', '.chat-input', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    $(this).siblings('.send-comment').click();
                }
            });

            // Logic Tab 2/4 NL BB
            $('#btn-propose-material-date').on('click', function() {
                let ids = [];
                $('.row-checkbox-material:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) {
                    Swal.fire('Thông báo', 'Vui lòng chọn ít nhất 1 mục để đề nghị!', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Xác nhận',
                    text: 'Bạn có chắc chắn gửi đề nghị thay đổi ngày đáp ứng NL/BB cho ' + ids
                        .length + ' lô này?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('pages.Schedual.warning.proposeMaterialDateChange') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                plan_master_ids: ids
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Thành công', res.message, 'success')
                                        .then(() => {
                                            removeRowsAndUpdateUI('table_material',
                                                'material-tab', ids);
                                        });
                                } else {
                                    Swal.fire('Lỗi', res.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Lỗi', 'Đã xảy ra lỗi hệ thống!', 'error');
                            }
                        });
                    }
                });
            });

            function acceptMaterialDate(id, defaultDate, violations) {
                let optionsHtml = '';
                if (violations && typeof violations === 'object') {
                    // Object with field keys
                    for (const [field, v] of Object.entries(violations)) {
                        optionsHtml += `<option value="${field}">${v.label}</option>`;
                    }
                }

                Swal.fire({
                    title: 'Nhập ngày đáp ứng NL/BB mới',
                    html: `
                        <div class="form-group text-left">
                            <label>Trường cần cập nhật:</label>
                            <select id="swal-material-field" class="form-control mb-3">
                                ${optionsHtml}
                            </select>
                            <label>Ngày mới:</label>
                            <input type="date" id="swal-material-date" class="form-control" value="${defaultDate}">
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Hủy',
                    preConfirm: () => {
                        return {
                            date: document.getElementById('swal-material-date').value,
                            field: document.getElementById('swal-material-field').value
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let newDate = result.value.date;
                        let updateField = result.value.field;
                        if (!newDate || !updateField) {
                            Swal.fire('Lỗi', 'Vui lòng chọn trường và ngày!', 'error');
                            return;
                        }

                        $.ajax({
                            url: "{{ route('pages.Schedual.warning.acceptMaterialDateChange') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                plan_master_id: id,
                                new_date: newDate,
                                field_name: updateField
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Thành công', res.message, 'success').then(() => {
                                        removeRowsAndUpdateUI('table_proposed_material',
                                            'proposed-material-tab', [id]);
                                    });
                                } else {
                                    Swal.fire('Lỗi', res.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Lỗi', 'Đã xảy ra lỗi hệ thống!', 'error');
                            }
                        });
                    }
                });
            }

            $('.btn-accept-material-single').on('click', function() {
                let id = $(this).data('id');
                let violations = $(this).data('violations');
                // Extract default date from min-start text, assume format DD/MM/YYYY HH:ii
                let defaultText = $('#min-start-material-' + id).text().trim();
                let defaultDate = '';
                if (defaultText) {
                    let parts = defaultText.split(' ')[0].split('/');
                    if (parts.length === 3) {
                        defaultDate = parts[2] + '-' + parts[1] + '-' + parts[0];
                    }
                }
                acceptMaterialDate(id, defaultDate, violations);
            });

            $('#btn-accept-bulk-material').on('click', function() {
                let ids = [];
                $('.row-checkbox-proposed-material:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) {
                    Swal.fire('Thông báo', 'Vui lòng chọn ít nhất 1 mục để chấp nhận!', 'warning');
                    return;
                }

                if (ids.length > 1) {
                    Swal.fire('Tính năng đang phát triển',
                        'Chấp nhận hàng loạt cần thiết kế lại UI, vui lòng duyệt từng lô.', 'info');
                    return;
                }

                let id = ids[0];
                let defaultText = $('#min-start-material-' + id).text().trim();
                let defaultDate = '';
                if (defaultText) {
                    let parts = defaultText.split(' ')[0].split('/');
                    if (parts.length === 3) {
                        defaultDate = parts[2] + '-' + parts[1] + '-' + parts[0];
                    }
                }
                acceptMaterialDate(id, defaultDate);
            });

            // Logic Reject KCS
            $('.btn-reject-single').on('click', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Từ chối đề nghị đổi ngày KCS',
                    input: 'textarea',
                    inputLabel: 'Lý do từ chối',
                    inputPlaceholder: 'Nhập lý do tại đây...',
                    inputAttributes: {
                        'aria-label': 'Nhập lý do tại đây'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Hủy',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Vui lòng nhập lý do từ chối!'
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('pages.Schedual.warning.rejectDateChange') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                plan_master_id: id,
                                reason: result.value
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Thành công', response.message, 'success')
                                        .then(() => {
                                            removeRowsAndUpdateUI('table_proposed',
                                                'proposed-tab', [id]);
                                        });
                                } else {
                                    Swal.fire('Lỗi', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Lỗi', 'Đã xảy ra lỗi hệ thống!', 'error');
                            }
                        });
                    }
                });
            });

            // Logic Reject NL/BB
            $('.btn-reject-material-single').on('click', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Từ chối đề nghị NL/BB',
                    input: 'textarea',
                    inputLabel: 'Lý do từ chối',
                    inputPlaceholder: 'Nhập lý do tại đây...',
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Hủy',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Vui lòng nhập lý do từ chối!'
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('pages.Schedual.warning.rejectMaterialDateChange') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                plan_master_id: id,
                                reason: result.value
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Thành công', response.message, 'success')
                                        .then(() => {
                                            removeRowsAndUpdateUI(
                                                'table_proposed_material',
                                                'proposed-material-tab', [id]);
                                        });
                                } else {
                                    Swal.fire('Lỗi', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Lỗi', 'Đã xảy ra lỗi hệ thống!', 'error');
                            }
                        });
                    }
                });
            });

            // Xem Lịch sử Đề nghị
            $('.btn-view-history').on('click', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: "/Schedual/warning/history/" + id,
                    type: "GET",
                    success: function(res) {
                        if (res.success) {
                            let tbody = $('#proposal-history-tbody');
                            tbody.empty();
                            if (res.data && res.data.length > 0) {
                                res.data.forEach(function(hist) {
                                    let typeBadge = hist.type == 'KCS' ? 'info' :
                                        'primary';
                                    let actionBadge = '';
                                    let actionText = hist.action;
                                    if (hist.action == 'PROPOSE') {
                                        actionBadge = 'warning';
                                        actionText = 'Đề nghị';
                                    } else if (hist.action == 'ACCEPT') {
                                        actionBadge = 'success';
                                        actionText = 'Chấp nhận';
                                    } else if (hist.action == 'REJECT') {
                                        actionBadge = 'danger';
                                        actionText = 'Từ chối';
                                    }

                                    let time = new Date(hist.created_at).toLocaleString(
                                        'vi-VN', {
                                            day: '2-digit',
                                            month: '2-digit',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        });

                                    let detailText = '';
                                    if (hist.field_name) {
                                        detailText += '<b>' + hist.field_name +
                                            '</b><br>';
                                    }
                                    if (hist.old_date) {
                                        detailText += 'Cũ: ' + new Date(hist.old_date)
                                            .toLocaleDateString('vi-VN') + '<br>';
                                    }
                                    if (hist.new_date) {
                                        detailText += 'Mới: ' + new Date(hist.new_date)
                                            .toLocaleDateString('vi-VN');
                                    }

                                    let html = `<tr>
                                        <td>${time}</td>
                                        <td>${hist.user_name || ''}</td>
                                        <td><span class="badge badge-${typeBadge}">${hist.type}</span></td>
                                        <td><span class="badge badge-${actionBadge}">${actionText}</span></td>
                                        <td>${detailText}</td>
                                        <td>${hist.reason || ''}</td>
                                    </tr>`;
                                    tbody.append(html);
                                });
                            } else {
                                tbody.append(
                                    '<tr><td colspan="6" class="text-center text-muted">Chưa có lịch sử đề nghị nào.</td></tr>'
                                );
                            }
                            $('#proposalHistoryModal').modal('show');
                        } else {
                            Swal.fire('Lỗi', 'Không thể tải dữ liệu!', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Lỗi', 'Lỗi kết nối đến máy chủ!', 'error');
                    }
                });
            });

            // Initialize History Table
            $('#table_history_all').DataTable({
                "paging": true,
                "lengthChange": true,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Tất cả"]
                ],
                "searching": true,
                "ordering": true,
                "order": [
                    [0, "desc"]
                ],
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
                }
            });
        });
    </script>
@endsection
