@extends('layout.master')

@section('model')
@endsection

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid pt-3">

            {{-- Header Card --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header text-white d-flex align-items-center justify-content-between"
                    style="background-color: #003A4F;">
                    <h5 class="mb-0"><i class="fas fa-history mr-2"></i> So Sánh Lịch Sử Thay Đổi Lịch Sản Xuất</h5>
                    <span id="result-count" class="badge badge-light" style="font-size:13px; display:none;"></span>
                </div>
                <div class="card-body py-3">
                    <div class="row align-items-end">
                        <div class="col-md-5">
                            <label class="font-weight-bold mb-1" style="color: #003A4F; font-size:13px;">
                                <i class="fas fa-calendar-alt mr-1"></i> Chọn mốc thời gian để so sánh:
                            </label>
                            <div class="input-group">
                                <input type="datetime-local" id="target_date" class="form-control"
                                    value="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}">
                                <div class="input-group-append">
                                    <button class="btn text-white font-weight-bold px-4" style="background-color: #003A4F;"
                                        id="btn-compare">
                                        <i class="fas fa-search mr-1"></i> So Sánh
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-info-circle text-primary"></i>
                                Hệ thống sẽ hiển thị lịch hiện hành với các các bản lưu thay đổi trong khoảng thời gian làm
                                mốc so sánh đến hiên tại.
                            </small>
                        </div>
                        <div class="col-md-4 mt-3 mt-md-0">
                            {{-- Legend --}}
                            <div class="d-flex flex-column" style="font-size:12px; gap:4px;">
                                <span><span class="badge" style="background:#dc3545; color:white;">Cũ</span> &nbsp;Giá trị
                                    trước thay đổi</span>
                                <span><span class="badge" style="background:#28a745; color:white;">Mới</span> &nbsp;Giá trị
                                    hiện tại</span>
                                <span><span class="badge" style="background:#6c757d; color:white;">—</span> &nbsp;Không
                                    thay đổi</span>
                            </div>

                        </div>
                        <div class="col-md-3 mt-3 mt-md-0">
                            <div class="d-flex align-items-center mt-3">
                                <div class="custom-control custom-switch mr-3">
                                    <input type="checkbox" class="custom-control-input" id="toggle-finished">
                                    <label class="custom-control-label font-weight-bold" for="toggle-finished"
                                        style="font-size:13px; color:#003A4F;">
                                        Hiển thị lịch đã hoàn thành
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alert area --}}
            <div id="compare-alert-area" class="mb-2"></div>

            {{-- Results --}}
            <div style="max-height: calc(100vh - 220px); overflow-y: auto; padding-right: 4px;">
                <div id="compare-result">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-search fa-3x mb-3" style="opacity:0.3;"></i>
                        <p>Vui lòng chọn mốc thời gian và bấm <b>So Sánh</b></p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <style>
        .diff-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .diff-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        }

        .diff-card .card-header-info {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }

        .diff-card .card-body-inner {
            padding: 10px 14px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .diff-field {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 12px;
        }

        .diff-field .field-label {
            color: #6c757d;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .diff-field .field-old {
            background: #fff5f5;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 3px 8px;
            color: #dc3545;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .diff-field .field-new {
            background: #f0fff4;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 3px 8px;
            color: #155724;
            font-weight: 600;
            font-size: 12px;
        }

        .diff-field .field-same {
            color: #495057;
            font-size: 12px;
            padding: 3px 0;
        }

        .diff-field.changed {
            border-color: #ffc107;
            background: #fffdf0;
        }

        .plan-title-text {
            font-weight: bold;
            color: #003A4F;
            font-size: 13px;
        }

        .product-code {
            font-weight: bold;
            color: #495057;
            font-size: 13px;
        }

        .batch-text {
            font-size: 11px;
            color: #6c757d;
        }

        .badge-finished {
            background-color: #28a745;
            color: white;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 20px;
        }

        .badge-pending {
            background-color: #ffc107;
            color: #333;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 20px;
        }

        .change-count-badge {
            background: #dc3545;
            color: white;
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 20px;
            margin-left: 6px;
        }
    </style>

    <script>
        window.addEventListener('load', function() {

            function showAlert(type, message) {
                let alertHtml = `<div class="alert alert-${type} alert-dismissible fade show py-2" role="alert" style="font-size:13px;">
            ${message}
            <button type="button" class="close py-2" data-dismiss="alert"><span>&times;</span></button>
        </div>`;
                $('#compare-alert-area').html(alertHtml);
                setTimeout(function() {
                    $('#compare-alert-area .alert').alert('close');
                }, 4000);
            }

            let allData = [];

            function renderResults(data) {
                function formatDate(dateStr) {
                    if (!dateStr) return '—';
                    dateStr = dateStr.replace('T', ' ');
                    let parts = dateStr.split(' ');
                    if (parts.length < 2) return dateStr;
                    let ymd = parts[0].split('-');
                    let hms = parts[1].split(':');
                    if (ymd.length < 3 || hms.length < 2) return dateStr;
                    return `${hms[0]}:${hms[1]} ${ymd[2]}/${ymd[1]}/${ymd[0]}`;
                }

                if (data.length === 0) {
                    $('#compare-result').html(
                        '<div class="text-center text-muted py-5"><i class="fas fa-check-circle fa-3x mb-3 text-success"></i><p>Không tìm thấy thay đổi nào so với thời điểm này.</p></div>'
                    );
                    $('#result-count').hide();
                    return;
                }

                let showFinished = $('#toggle-finished').is(':checked');
                let filtered = showFinished ? data : data.filter(i => i.finished != 1);
                let displayed = filtered.length;

                $('#result-count').text(displayed + ' lịch thay đổi').show();

                if (displayed === 0) {
                    $('#compare-result').html(
                        '<div class="text-center text-muted py-5"><p>Không có lịch nào phù hợp với bộ lọc hiện tại.</p></div>'
                    );
                    return;
                }

                let html = '<div class="accordion" id="stageAccordion">';

                let grouped = {};
                filtered.forEach(item => {
                    let sc = item.stage_code || 0;
                    if (!grouped[sc]) grouped[sc] = [];
                    grouped[sc].push(item);
                });

                let stageNames = {
                    1: 'Cân Nguyên Liệu',
                    2: 'Cân Nguyên Liệu khác',
                    3: 'Pha Chế',
                    4: 'THT',
                    5: 'Định Hình',
                    6: 'Bao Phim',
                    7: 'ĐGSC-ĐGTC'
                };

                Object.keys(grouped).sort((a, b) => a - b).forEach((sc, index) => {
                    let stageItems = grouped[sc];
                    let title = stageNames[sc] ? `Công đoạn ${sc}: ${stageNames[sc]}` : `Công đoạn ${sc}`;
                    let collapseId = "collapseStage" + sc;
                    let showClass = index === 0 ? "show" :
                        ""; // Only open the first one by default, or all? Let's open all for better visibility
                    showClass = "show";

                    html += `
            <div class="card mb-3 shadow-sm border-info">
                <div class="card-header p-0 bg-info" id="heading${sc}">
                    <h2 class="mb-0">
                        <button class="btn btn-block text-left font-weight-bold text-white d-flex justify-content-between align-items-center p-3" 
                                type="button" data-toggle="collapse" data-target="#${collapseId}" 
                                aria-expanded="true" aria-controls="${collapseId}" style="text-decoration:none;">
                            <span><i class="fas fa-layer-group mr-2"></i> ${title} <span class="badge badge-light text-info ml-2">${stageItems.length} thay đổi</span></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </h2>
                </div>
                <div id="${collapseId}" class="collapse ${showClass}" aria-labelledby="heading${sc}">
                    <div class="card-body bg-light">
                        <div class="row">
            `;

                    let planGroups = {};
                    stageItems.forEach(item => {
                        if (!planGroups[item.plan_id]) planGroups[item.plan_id] = [];
                        planGroups[item.plan_id].push(item);
                    });

                    Object.values(planGroups).forEach(items => {
                        items.sort((a, b) => a.version - b.version);
                        let currentItem = items[0];

                        let newStart = formatDate(currentItem.current_start);
                        let newEnd = formatDate(currentItem.current_end);
                        let createdDate = formatDate(currentItem.current_created_date);

                        let changeCount = items.length;
                        let isNewPlan = changeCount === 1 && currentItem.history_saved_at === null;

                        let changeBadgeHtml = '';
                        if (isNewPlan) {
                            changeBadgeHtml =
                                `<span class="change-count-badge" style="background:#28a745">Lịch mới</span>`;
                        } else if (changeCount > 0) {
                            changeBadgeHtml =
                                `<span class="change-count-badge">${changeCount} thay đổi</span>`;
                        }

                        let statusBadge = currentItem.finished == 1 ?
                            '<span class="badge-finished"><i class="fas fa-check-circle mr-1"></i>Đã hoàn thành</span>' :
                            '<span class="badge-pending"><i class="fas fa-clock mr-1"></i>Lịch Lý Thuyết</span>';

                        function renderDiffField(label, icon, type, showBadge = false) {
                            let newVal = type === 'room' ? currentItem.current_room_name :
                                type === 'start' ? newStart :
                                type === 'end' ? newEnd :
                                createdDate;

                            if (isNewPlan) {
                                let valueHtml = `<div class="field-same d-flex justify-content-between align-items-center">
                                    <span>${newVal || '—'}</span>
                                    ${showBadge ? `<span class="badge badge-success">Lịch mới</span>` : ''}
                                </div>`;
                                return `<div class="diff-field">
                                            <div class="field-label"><i class="${icon}"></i> ${label}</div>
                                            ${valueHtml}
                                        </div>`;
                            }

                            let oldValsHtml = '';
                            items.forEach(h => {
                                let oldVal = type === 'room' ? h.old_room_name :
                                    type === 'start' ? formatDate(h.old_start) :
                                    type === 'end' ? formatDate(h.old_end) :
                                    formatDate(h.history_saved_at);

                                oldValsHtml += `<div class="field-old mb-1 d-flex justify-content-between align-items-center" title="Version ${h.version}">
                                    <span>${oldVal || '—'}</span>
                                    ${showBadge ? `<span class="badge badge-danger">v.${h.version}</span>` : ''}
                                </div>`;
                            });

                            let valueHtml = oldValsHtml + `<div class="field-new mt-1 d-flex justify-content-between align-items-center">
                                    <span>${newVal || '—'}</span>
                                    ${showBadge ? `<span class="badge badge-success">Hiện hành</span>` : ''}
                                </div>`;

                            return `<div class="diff-field changed">
                                        <div class="field-label"><i class="${icon}"></i> ${label}</div>
                                        ${valueHtml}
                                    </div>`;
                        }

                        html += `
                <div class="col-md-6 mb-3">
                    <div class="diff-card h-100 bg-white" data-finished="${currentItem.finished}">
                        <div class="card-header-info">
                            <div class="d-flex align-items-center" style="gap:10px;">
                                <span class="product-code">${currentItem.finished_product_code}</span>
    
                                <span class="plan-title-text">· ${currentItem.plan_title}</span>
                                ${changeBadgeHtml}
                            </div>
                            <div>${statusBadge}</div>
                        </div>
                        <div class="card-body-inner">
                            ${renderDiffField('Phòng', 'fas fa-door-open', 'room', true)}
                            ${renderDiffField('Bắt Đầu', 'fas fa-play-circle', 'start')}
                            ${renderDiffField('Kết Thúc', 'fas fa-stop-circle', 'end')}
                            ${renderDiffField('Ngày Tạo Lịch', 'fas fa-calendar-plus', 'created')}
                        </div>
                    </div>
                </div>`;
                    });

                    html += `
                        </div>
                    </div>
                </div>
            </div>`;
                });

                html += '</div>';

                $('#compare-result').html(html);
            }

            $('#btn-compare').click(function() {
                let targetDate = $('#target_date').val();
                if (!targetDate) {
                    showAlert('danger', 'Vui lòng chọn thời gian so sánh!');
                    return;
                }
                targetDate = targetDate.replace('T', ' ') + ':00';

                $('#compare-result').html(
                    '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Đang tải dữ liệu...</p></div>'
                );
                $('#result-count').hide();

                $.ajax({
                    url: '{{ route('pages.Schedual.audit.compare_data') }}',
                    method: 'GET',
                    data: {
                        target_date: targetDate
                    },
                    success: function(res) {
                        allData = res;
                        renderResults(allData);
                    },
                    error: function(err) {
                        console.error(err);
                        showAlert('danger', 'Có lỗi xảy ra khi lấy dữ liệu! Vui lòng thử lại.');
                        $('#compare-result').html(
                            '<div class="text-center text-danger py-5"><i class="fas fa-exclamation-triangle fa-2x"></i><p>Lỗi kết nối máy chủ.</p></div>'
                        );
                    }
                });
            });

            $('#toggle-finished').change(function() {
                if (allData.length > 0) {
                    renderResults(allData);
                }
            });
        });
    </script>
@endsection
