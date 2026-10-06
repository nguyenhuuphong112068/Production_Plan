{{-- JS thao tác trên card phòng: Nhận phòng (modal _receive_modal) / Trả phòng. Include trong callback DOMContentLoaded,
     sau _live_js. Trang include phải định nghĩa: R (plans, receive, release, receiveCleaning, history), afterRender(), refreshBoard(force). --}}
            let ctx = null; // card phòng đang thao tác
            let busy = false;

            function replaceCard(html) {
                if (!html) return;
                const $new = $($.parseHTML(html.trim())).filter('.exec-room-col');
                $('#' + $new.attr('id')).replaceWith($new);
                afterRender();
            }

            /* ---------- Gửi thao tác ---------- */
            function send(url, data, $modal) {
                if (busy) return;
                busy = true;
                const $btn = $modal ? $modal.find('.js-submit').prop('disabled', true) : $();

                $.ajax({
                        url,
                        type: 'POST',
                        data: Object.assign({
                            room_id: ctx.room_id
                        }, data)
                    })
                    .done(res => {
                        replaceCard(res.html);
                        if ($modal) $modal.modal('hide');
                        if (res.reroute) return showReroute(res);
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            titleText: res.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    })
                    .fail(xhr => {
                        const res = xhr.responseJSON || {};
                        // 409: phòng vừa bị người khác đổi trạng thái → thay card mới, đóng modal
                        if (res.html) {
                            replaceCard(res.html);
                            if ($modal) $modal.modal('hide');
                        }
                        // Trang Ghi Nhận: hết giờ phân công tại phòng → đóng modal, tải lại danh sách phòng
                        if (res.gone) {
                            if ($modal) $modal.modal('hide');
                            setTimeout(() => refreshBoard(true), 300);
                        }
                        let msg = res.message || 'Có lỗi xảy ra, vui lòng thử lại';
                        if (xhr.status === 419) msg = 'Phiên làm việc đã hết hạn, vui lòng tải lại trang (F5).';
                        Swal.fire({
                            icon: xhr.status === 409 ? 'info' : 'warning',
                            title: 'Không thể thực hiện',
                            text: msg
                        });
                    })
                    .always(() => {
                        busy = false;
                        $btn.prop('disabled', false);
                    });
            }

            // Trả phòng khi công tắc tịnh tuyến của phân xưởng đang bật: kết quả dịch lịch lý thuyết theo giờ trả phòng
            function showReroute(res) {
                const r = res.reroute;
                if (r.error) {
                    return Swal.fire({
                        icon: 'warning',
                        title: 'Đã trả phòng',
                        text: res.message + '. Tịnh tuyến lịch bị lỗi (đã ghi log), lịch lý thuyết chưa được dịch.'
                    });
                }
                if (!r.count && !r.cleaning_moved) {
                    return Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        titleText: res.message + ' · Lịch không cần dịch',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Đã trả phòng',
                    html: `${esc(res.message)}<br>Lô hoàn thành ${r.delta < 0 ? 'sớm' : 'trễ'} <b>${Math.abs(r.delta)} phút</b> so với lịch lý thuyết.<br>` +
                        `Đã tịnh tuyến <b>${r.count}</b> lô liên quan.<br>` +
                        '<small>Xem chi tiết: chuột phải lên lô trên Lịch Sản Xuất → "Lịch sử tịnh tuyến".</small>',
                    confirmButtonText: 'Đóng',
                });
            }

            /* ---------- Nhận phòng ---------- */
            // Phòng cân chọn được nhiều lô cùng BTP (selected = danh sách id), phòng khác 1 lô / 1 lịch bảo trì
            let plans = [],
                selected = [];

            function openReceive() {
                const $m = $('#execReceiveModal');
                $m.find('.js-room').text(ctx.room);
                $('#execPlanSearch').val('');
                $('#execPlanHint').text('');
                selected = [];
                loadPlans();
                $m.modal('show');
            }

            function loadPlans() {
                $('#execPlanList').html(
                    '<div class="exec-plan-empty"><i class="fas fa-spinner fa-spin"></i> Đang tải danh sách lô...</div>');
                $.get(R.plans, {
                    room_id: ctx.room_id
                }).done(list => {
                    plans = list;
                    renderPlans();
                }).fail(xhr => {
                    $('#execPlanList').html(
                        `<div class="exec-plan-empty text-danger">${esc((xhr.responseJSON || {}).message || 'Không tải được danh sách lô')}</div>`
                    );
                });
            }

            // Mã BTP của các lô đang chọn (phòng cân chỉ nhận chung các lô cùng BTP)
            const selectedBtp = () => {
                const first = plans.find(x => x.id === selected[0]);
                return first ? first.btp : null;
            };
            // Chọn nhiều: phòng cân (lô cùng BTP) hoặc danh sách lịch bảo trì / hiệu chuẩn (EN, QA: mỗi dòng 1 thiết bị)
            const maintList = () => plans.length > 0 && plans.every(p => p.maintenance);
            const multi = () => ctx.weighing || maintList();
            const selectedMaint = () => plans.some(p => selected.includes(p.id) && p.maintenance);

            function renderPlans() {
                const q = $('#execPlanSearch').val().toLowerCase().trim();
                const rows = plans.filter(p => !q || `${p.product} ${p.batch || ''} ${p.codes}`.toLowerCase().includes(q));
                if (!rows.length) {
                    $('#execPlanHint').text(plans.length ? '' : 'Phòng chưa có lô hay lịch bảo trì nào được sắp lịch.');
                    $('#execPlanList').html('<div class="exec-plan-empty">Không có lô phù hợp.</div>');
                    return;
                }
                const scrollTop = $('#execPlanList').scrollTop();
                const btp = selectedBtp();
                // Số lô cùng lịch lý thuyết + cùng BTP (được chọn theo nhau)
                const sameCount = key => key ? plans.filter(x => x.plan_key === key).length : 0;
                $('#execPlanHint').html(maintList() ?
                    (selected.length > 1 ? `Đã chọn <b>${selected.length}</b> thiết bị` :
                        'Chọn được nhiều thiết bị; thiết bị cùng nhóm lịch được chọn theo nhau (bỏ chọn được).') :
                    ctx.weighing ?
                    (selected.length > 1 ? `Đã chọn <b>${selected.length}</b> lô cùng mã BTP <b>${esc(btp)}</b>` :
                        'Phòng cân: chọn được nhiều lô cùng mã BTP; lô cùng lịch và cùng BTP được chọn theo nhau (bỏ chọn được).') : '');
                $('#execPlanList').html(rows.map(p => {
                    const on = selected.includes(p.id);
                    // Không chọn chung lịch bảo trì với lô sản xuất; phòng cân không chọn chung lô khác BTP
                    const off = multi() && selected.length && !on &&
                        (selectedMaint() ? !p.maintenance : (p.maintenance || p.btp !== btp));
                    const same = multi() && sameCount(p.plan_key) > 1 ? (p.maintenance ?
                        `<span class="exec-plan-same" title="Cùng nhóm lịch bảo trì">${sameCount(p.plan_key)} thiết bị cùng lịch</span>` :
                        `<span class="exec-plan-same" title="Cùng lịch lý thuyết và cùng BTP">${sameCount(p.plan_key)} lô cùng lịch</span>`) : '';
                    // Cỡ lô (sản lượng lý thuyết) bên phải, số lô nổi bật ngay dưới tên sản phẩm
                    const side = p.maintenance ? `<span class="badge badge-info">${esc(p.type_label)}</span>` :
                        `<div class="exec-plan-size-label">Cỡ lô</div><div class="exec-plan-size">${num(p.theory)} <small>${esc(p.unit)}</small></div>`;
                    const meta = p.maintenance ? `<div class="exec-plan-meta">${esc(p.group)}</div>` :
                        `<div class="exec-plan-batch">Lô <b>${esc(p.batch)}</b></div><div class="exec-plan-meta">${esc(p.codes)}</div>`;
                    return `<label class="exec-plan ${on ? 'selected' : ''} ${off ? 'disabled' : ''}">
                        <input type="${multi() ? 'checkbox' : 'radio'}" name="execPlan" value="${p.id}" ${on ? 'checked' : ''} ${off ? 'disabled' : ''}>
                        <div class="exec-plan-main">
                            <div class="exec-plan-name">${p.maintenance ? '<i class="fas fa-tools"></i> ' : ''}${esc(p.product)}${p.market ? ' - ' + esc(p.market) : ''}
                                ${p.is_val ? '<i class="fas fa-check-circle text-primary" title="Lô thẩm định"></i>' : ''}${same}</div>
                            ${meta}
                            <div class="exec-plan-meta"><i class="far fa-calendar-alt"></i> Lịch: ${esc(p.start)} → ${esc(p.end)}</div>
                        </div>
                        <div class="exec-plan-side">${side}</div>
                    </label>`;
                }).join('')).scrollTop(scrollTop); // chọn lô không nhảy danh sách về đầu
            }

            $('#execPlanSearch').on('input', renderPlans);

            $(document).on('change', 'input[name="execPlan"]', function() {
                const id = +this.value;
                const p = plans.find(x => x.id === id);
                if (!multi()) {
                    selected = [id];
                } else if (this.checked) {
                    // Dòng đầu tiên: các lô cùng lịch + cùng BTP / thiết bị cùng nhóm lịch bảo trì được chọn theo (bỏ chọn được)
                    const auto = !selected.length && p.plan_key ?
                        plans.filter(x => x.plan_key === p.plan_key && x.maintenance === p.maintenance).map(x => x.id) : [];
                    selected = [...new Set([...selected, id, ...auto])];
                } else {
                    selected = selected.filter(x => x !== id);
                }
                renderPlans();
            });

            $('#execReceiveSubmit').on('click', function() {
                if (!selected.length) return Swal.fire({
                    icon: 'warning',
                    title: 'Chọn lô / lịch cần nhận phòng'
                });
                send(R.receive, {
                    stage_plan_ids: selected
                }, $('#execReceiveModal'));
            });

            /* ---------- Trả phòng ---------- */
            function confirmRelease() {
                Swal.fire({
                    icon: 'question',
                    title: 'Trả phòng ' + ctx.room + '?',
                    html: (ctx.plans.length ? 'Đang giữ phòng: <b>' + ctx.plans.map(esc).join(', ') + '</b><br>' : '') +
                        (ctx.since ? 'Nhận phòng lúc <b>' + esc(ctx.since) + '</b><br>' : '') +
                        '<small>Thời gian trả phòng ghi theo giờ hệ thống lúc bấm nút. Phòng chuyển sang <b>' + esc(ctx.after) + '</b>.</small>',
                    showCancelButton: true,
                    confirmButtonText: 'Trả phòng',
                    cancelButtonText: 'Không',
                    confirmButtonColor: '#dc2626',
                }).then(r => {
                    if (r.isConfirmed) send(R.release, {}, null);
                });
            }

            /* ---------- Nhận phòng vệ sinh sau bảo trì ---------- */
            function confirmReceiveCleaning() {
                Swal.fire({
                    icon: 'question',
                    title: 'Nhận phòng ' + ctx.room + ' vệ sinh sau BT?',
                    html: (ctx.plans.length ? 'Bảo trì: <b>' + ctx.plans.map(esc).join(', ') + '</b><br>' : '') +
                        (ctx.since ? 'Kết thúc bảo trì lúc <b>' + esc(ctx.since) + '</b><br>' : '') +
                        '<small>Thời gian bắt đầu vệ sinh ghi theo giờ hệ thống lúc bấm nút. Vệ sinh xong bấm <b>Trả phòng</b>.</small>',
                    showCancelButton: true,
                    confirmButtonText: 'Nhận phòng vệ sinh',
                    cancelButtonText: 'Không',
                    confirmButtonColor: '#d97706',
                }).then(r => {
                    if (r.isConfirmed) send(R.receiveCleaning, {}, null);
                });
            }

            /* ---------- Hoàn tác thao tác vừa rồi (trong 2 phút, đếm ngược ở _live_js) ---------- */
            function confirmUndo(label) {
                Swal.fire({
                    icon: 'question',
                    title: 'Hoàn tác ' + label + '?',
                    html: 'Phòng <b>' + esc(ctx.room) + '</b> sẽ trở về trạng thái trước đó (giờ đã ghi bị xóa).<br>' +
                        '<small>Nếu đã Trả phòng và có tịnh tuyến lịch thì lịch cũng được khôi phục.</small>',
                    showCancelButton: true,
                    confirmButtonText: 'Hoàn tác',
                    cancelButtonText: 'Không',
                    confirmButtonColor: '#dc2626',
                }).then(r => {
                    if (r.isConfirmed) send(R.undo, {}, null);
                });
            }

            /* ---------- Lịch sử nhận trả phòng (chỉ xem) ---------- */
            function openHistory(roomId, roomName) {
                const $m = $('#execHistoryModal');
                $m.find('.js-room').text(roomName);
                $('#execHistoryHint').text('');
                $('#execHistoryBody').html('<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>');
                $m.modal('show');
                $.get(R.history, {
                    room_id: roomId
                }).done(res => {
                    $('#execHistoryHint').text(`Ghi nhận từ ${res.since} · ${res.rows.length} lượt · mới nhất ở trên`);
                    if (!res.rows.length) {
                        $('#execHistoryBody').html('<tr><td colspan="6" class="text-center text-muted py-4">Phòng chưa có lượt nhận phòng nào.</td></tr>');
                        return;
                    }
                    const cell = (t, by) => t ? `<span class="h-time">${esc(t)}</span>${by ? `<span class="h-by"><i class="fas fa-user"></i> ${esc(by)}</span>` : ''}` : '';
                    $('#execHistoryBody').html(res.rows.map((h, i) => {
                        const open = !h.released_at;
                        const mid = h.maint_end_at ? `<div>${cell(h.maint_end_at, h.maint_end_by)}</div>` +
                            (h.clean_start_at ? `<div class="mt-1"><small class="text-muted">Nhận VS</small> ${cell(h.clean_start_at, h.clean_start_by)}</div>` : '') : '';
                        return `<tr class="${open ? 'is-open' : ''}">
                            <td>${res.rows.length - i}</td>
                            <td>${h.maintenance ? '<i class="fas fa-tools text-muted"></i> ' : ''}${h.labels.map(esc).join('<br>')}</td>
                            <td>${cell(h.received_at, h.received_by)}</td>
                            <td>${mid}</td>
                            <td>${open ? '<span class="h-open"><i class="fas fa-circle-notch fa-spin"></i> Đang giữ phòng</span>' : cell(h.released_at, h.released_by)}</td>
                            <td>${dur(h.minutes * 60000)}</td>
                        </tr>`;
                    }).join(''));
                }).fail(xhr => {
                    $('#execHistoryBody').html(`<tr><td colspan="6" class="text-center text-danger py-4">${esc((xhr.responseJSON || {}).message || 'Không tải được lịch sử')}</td></tr>`);
                });
            }

            $(document).on('click', '.js-history', function() {
                openHistory($(this).data('room-id'), $(this).data('room'));
            });

            /* ---------- Nút trên card phòng ---------- */
            $(document).on('click', '.js-act', function() {
                ctx = JSON.parse($(this).closest('.exec-room-col').attr('data-ctx'));
                switch ($(this).data('act')) {
                    case 'receive':
                        return openReceive();
                    case 'release':
                        return confirmRelease();
                    case 'receive_cleaning':
                        return confirmReceiveCleaning();
                    case 'undo':
                        return confirmUndo($(this).data('label'));
                }
            });
