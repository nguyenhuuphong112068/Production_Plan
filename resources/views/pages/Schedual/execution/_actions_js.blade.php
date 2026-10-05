{{-- JS thao tác trên card phòng: Nhận phòng (modal _receive_modal) / Trả phòng. Include trong callback DOMContentLoaded,
     sau _live_js. Trang include phải định nghĩa: R (plans, receive, release), afterRender(), refreshBoard(force). --}}
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
            const isMulti = () => ctx.weighing && !plans.some(p => selected.includes(p.id) && p.maintenance);

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
                $('#execPlanHint').html(ctx.weighing ?
                    (selected.length > 1 ? `Đã chọn <b>${selected.length}</b> lô cùng mã BTP <b>${esc(btp)}</b>` :
                        'Phòng cân: chọn được nhiều lô cùng mã BTP; lô cùng lịch và cùng BTP được chọn theo nhau (bỏ chọn được).') : '');
                $('#execPlanList').html(rows.map(p => {
                    const on = selected.includes(p.id);
                    // Phòng cân: lịch bảo trì nhận riêng, lô khác BTP không chọn chung
                    const off = ctx.weighing && selected.length && !on &&
                        (p.maintenance || !isMulti() || p.btp !== btp);
                    const same = ctx.weighing && !p.maintenance && sameCount(p.plan_key) > 1 ?
                        `<span class="exec-plan-same" title="Cùng lịch lý thuyết và cùng BTP">${sameCount(p.plan_key)} lô cùng lịch</span>` : '';
                    // Cỡ lô (sản lượng lý thuyết) bên phải, số lô nổi bật ngay dưới tên sản phẩm
                    const side = p.maintenance ? '<span class="badge badge-info">Bảo trì</span>' :
                        `<div class="exec-plan-size-label">Cỡ lô</div><div class="exec-plan-size">${num(p.theory)} <small>${esc(p.unit)}</small></div>`;
                    const meta = p.maintenance ? '' :
                        `<div class="exec-plan-batch">Lô <b>${esc(p.batch)}</b></div><div class="exec-plan-meta">${esc(p.codes)}</div>`;
                    return `<label class="exec-plan ${on ? 'selected' : ''} ${off ? 'disabled' : ''}">
                        <input type="${ctx.weighing ? 'checkbox' : 'radio'}" name="execPlan" value="${p.id}" ${on ? 'checked' : ''} ${off ? 'disabled' : ''}>
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
                if (!ctx.weighing) {
                    selected = [id];
                } else if (this.checked) {
                    // Lô đầu tiên: các lô cùng lịch lý thuyết + cùng BTP được chọn theo (bỏ chọn được)
                    const auto = !selected.length && !p.maintenance && p.plan_key ?
                        plans.filter(x => x.plan_key === p.plan_key && !x.maintenance).map(x => x.id) : [];
                    selected = [...new Set([...selected, id, ...auto])];
                } else {
                    selected = selected.filter(x => x !== id);
                }
                renderPlans();
            });

            $('#execReceiveSubmit').on('click', function() {
                if (!selected.length) return Swal.fire({
                    icon: 'warning',
                    title: 'Chọn lô cần nhận phòng'
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
                        '<small>Thời gian trả phòng ghi theo giờ hệ thống lúc bấm nút. Phòng chuyển sang <b>Sẵn Sàng</b>.</small>',
                    showCancelButton: true,
                    confirmButtonText: 'Trả phòng',
                    cancelButtonText: 'Không',
                    confirmButtonColor: '#dc2626',
                }).then(r => {
                    if (r.isConfirmed) send(R.release, {}, null);
                });
            }

            /* ---------- Nút trên card phòng ---------- */
            $(document).on('click', '.js-act', function() {
                ctx = JSON.parse($(this).closest('.exec-room-col').attr('data-ctx'));
                switch ($(this).data('act')) {
                    case 'receive':
                        return openReceive();
                    case 'release':
                        return confirmRelease();
                }
            });
