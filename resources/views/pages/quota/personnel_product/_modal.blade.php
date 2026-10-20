{{-- Modal khai báo sản phẩm + script chuyển tab của trang Danh sách nhân viên (Nhân sự – Sản phẩm).
     Cần: $productTab, $activeTab, $currentDepartment, $filterGroupId, $filterRoomId.
     Trang này nạp 2 bản jQuery/Bootstrap (1.12 trong view + 3.x của layout) nên tab được chuyển bằng JS riêng,
     không dùng data-toggle="tab" (2 bản Bootstrap cùng bắt click và sự kiện shown.bs.tab không tới được bản kia). --}}
<style>
    #pp-modal .modal-xl {
        max-width: 1200px;
    }

    #pp-modal .pp-list-wrap {
        max-height: 58vh;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }

    #pp-modal table thead th {
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 2;
        box-shadow: inset 0 -1px 0 #dee2e6;
        font-size: 0.8rem;
    }

    #pp-modal table td {
        font-size: 0.82rem;
        vertical-align: middle;
        padding: 3px 8px;
    }

    #pp-modal tr.pp-row.checked {
        background: #fffbe6;
    }

    #pp-modal tr.pp-row.changed td:first-child {
        box-shadow: inset 3px 0 0 #fd7e14;
    }

    #pp-modal .pp-extra {
        color: #6c757d;
        font-size: 0.75rem;
    }

    #pp-modal .pp-no-room {
        color: #c62828;
        font-size: 0.75rem;
        font-style: italic;
    }
</style>

<div class="modal fade" id="pp-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title mb-0"><i class="fas fa-user-check text-primary"></i>
                        <span id="pp-emp-name"></span> <small class="text-muted" id="pp-emp-code"></small>
                    </h5>
                    <div id="pp-emp-rooms" class="mt-1"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body pt-2">
                <ul class="nav nav-tabs mb-2" id="pp-type-tabs">
                    @foreach ($productTab['types'] as $code => $label)
                        <li class="nav-item">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }}" href="javascript:void(0)"
                                data-type="{{ $code }}">{{ $label }}
                                <span class="badge badge-secondary ml-1 pp-tab-count" data-type="{{ $code }}">0</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="d-flex flex-wrap align-items-center mb-2">
                    <input type="text" class="form-control form-control-sm mr-2 mb-1" id="pp-search"
                        placeholder="Tìm mã / tên sản phẩm..." style="width: 260px;">
                    <select class="form-control form-control-sm mr-2 mb-1" id="pp-room-filter" style="width: 260px;">
                        <option value="">-- Tất cả phòng --</option>
                    </select>
                    <div class="custom-control custom-checkbox mr-3 mb-1">
                        <input type="checkbox" class="custom-control-input" id="pp-only-checked">
                        <label class="custom-control-label small" for="pp-only-checked">Chỉ hiện đã chọn</label>
                    </div>
                    @if ($productTab['canEdit'])
                        <button class="btn btn-xs btn-outline-primary mr-1 mb-1" id="pp-check-visible"><i
                                class="far fa-check-square"></i> Chọn tất cả đang hiển thị</button>
                        <button class="btn btn-xs btn-outline-secondary mb-1" id="pp-uncheck-visible"><i
                                class="far fa-square"></i> Bỏ chọn đang hiển thị</button>
                    @endif
                    <span class="ml-auto small text-muted mb-1" id="pp-visible-info"></span>
                </div>

                <div class="pp-list-wrap">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 36px;" class="text-center">✓</th>
                                <th style="width: 120px;">Mã SP</th>
                                <th>Tên sản phẩm</th>
                                <th>Phòng được làm (suy ra)</th>
                                <th style="width: 170px;">Khai báo bởi</th>
                            </tr>
                        </thead>
                        <tbody id="pp-list"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <span class="mr-auto small text-muted" id="pp-change-info"></span>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
                @if ($productTab['canEdit'])
                    <button type="button" class="btn btn-primary btn-sm" id="pp-save"><i class="fas fa-save"></i>
                        Lưu khai báo</button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const $ = window.jQuery;
        const canEdit = @json($productTab['canEdit']);
        const typeLabels = @json($productTab['types']);
        const baseUrl = "{{ url()->current() }}";
        const urls = {
            employee: "{{ url('/quota/personnel-products/employee') }}/",
            save: "{{ route('pages.quota.personnel_products.save') }}",
        };

        const dtLanguage = {
            search: "Tìm kiếm:",
            lengthMenu: "Hiển thị _MENU_ dòng",
            info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
            infoEmpty: "Không có dữ liệu",
            infoFiltered: "(lọc từ _MAX_ dòng)",
            emptyTable: "Chưa có khai báo nào",
            zeroRecords: "Không tìm thấy",
            paginate: {
                previous: "Trước",
                next: "Sau"
            }
        };

        function esc(s) {
            return $('<div>').text(s == null ? '' : String(s)).html();
        }

        // ---------------- Chuyển tab ----------------
        const empTable = $('#tbl-pp-employees').DataTable({
            pageLength: 25,
            lengthMenu: [
                [25, 50, 100, -1],
                [25, 50, 100, "Tất cả"]
            ],
            autoWidth: false,
            columnDefs: [{
                orderable: false,
                targets: [7]
            }],
            language: dtLanguage
        });

        function showTab(key) {
            $('#pn-tabs .nav-link').removeClass('active').filter(`[data-pn-tab="${key}"]`).addClass('active');
            $('[data-pn-pane]').removeClass('show active').filter(`[data-pn-pane="${key}"]`).addClass('show active');
            // Dashboard + Xuất Excel chỉ thuộc tab Tổ & Phòng công tác
            $('.pn-rooms-only').toggleClass('d-none', key !== 'rooms');

            // Giữ tab khi lọc Tổ / Phòng, bấm Reset hoặc tải lại trang
            const tabParam = key === 'rooms' ? '' : key;
            $('#filter-form input[name="tab"]').val(tabParam);
            $('#btn-reset-filter').attr('href', baseUrl + (tabParam ? '?tab=' + tabParam : ''));
            try {
                const url = new URL(window.location.href);
                if (tabParam) url.searchParams.set('tab', tabParam);
                else url.searchParams.delete('tab');
                window.history.replaceState(null, '', url.toString());
            } catch (e) {}

            if (key === 'rooms' && $.fn.dataTable.isDataTable('#data_table_personnel')) {
                $('#data_table_personnel').DataTable().columns.adjust();
            }
            if (key === 'products') empTable.columns.adjust();
        }

        $('#pn-tabs').on('click', '.nav-link', function(e) {
            e.preventDefault();
            showTab($(this).attr('data-pn-tab'));
        });
        showTab(@json($activeTab));

        // ---------------- Modal khai báo ----------------
        const state = {
            employeeId: null,
            type: 'BTP',
            rooms: {},
            items: {}, // type => [item]
            checked: {}, // type => Set(code) hiện tại
            original: {} // type => Set(code) lúc mở
        };

        function roomBadges(roomIds) {
            return roomIds.map(id => {
                const r = state.rooms[id];
                return `<span class="pp-room" title="${esc(r ? r.name : '')}">${esc(r ? r.code : id)}</span>`;
            }).join('');
        }

        $(document).on('click', '.btn-pp-open', function() {
            const id = $(this).data('id');
            $.getJSON(urls.employee + id).done(function(res) {
                if (!res.success) {
                    Swal.fire('Lỗi', res.message || 'Không tải được dữ liệu', 'error');
                    return;
                }
                state.employeeId = res.employee.id;
                state.rooms = {};
                res.rooms.forEach(r => state.rooms[r.id] = r);
                Object.keys(typeLabels).forEach(type => {
                    state.items[type] = res.products[type] || [];
                    const declared = state.items[type].filter(i => i.declared).map(i => i.code);
                    state.checked[type] = new Set(declared);
                    state.original[type] = new Set(declared);
                });

                $('#pp-emp-name').text(res.employee.name);
                $('#pp-emp-code').text('(' + res.employee.code + ')');
                $('#pp-emp-rooms').html(res.rooms.length ? 'Phòng: ' + roomBadges(res.rooms.map(r => r.id)) :
                    '<span class="text-danger small">Chưa được cấp phòng nào</span>');

                $('#pp-search').val('');
                $('#pp-only-checked').prop('checked', false);
                switchType('BTP');
                $('#pp-modal').modal('show');
            }).fail(() => Swal.fire('Lỗi', 'Không tải được dữ liệu', 'error'));
        });

        function switchType(type) {
            state.type = type;
            $('#pp-type-tabs .nav-link').removeClass('active');
            $(`#pp-type-tabs .nav-link[data-type="${type}"]`).addClass('active');

            // Bộ lọc phòng: chỉ các phòng của NV có sản phẩm thuộc loại này
            const roomSet = new Set();
            state.items[type].forEach(i => i.rooms.forEach(r => roomSet.add(r)));
            let opts = '<option value="">-- Tất cả phòng --</option>';
            Object.values(state.rooms).filter(r => roomSet.has(r.id)).forEach(r => {
                opts += `<option value="${r.id}">${esc(r.code + ' - ' + r.name)}</option>`;
            });
            $('#pp-room-filter').html(opts);

            renderList();
        }

        function renderList() {
            const type = state.type;
            const checked = state.checked[type];
            const original = state.original[type];
            let html = '';
            state.items[type].forEach(i => {
                const isChecked = checked.has(i.code);
                const changed = isChecked !== original.has(i.code);
                const search = (i.code + ' ' + i.name + ' ' + i.extra).toLowerCase();
                html += `<tr class="pp-row ${isChecked ? 'checked' : ''} ${changed ? 'changed' : ''}"
                            data-code="${esc(i.code)}" data-rooms="${i.rooms.join(',')}" data-search="${esc(search)}">
                    <td class="text-center"><input type="checkbox" class="pp-check" ${isChecked ? 'checked' : ''} ${canEdit ? '' : 'disabled'}></td>
                    <td>${esc(i.code)}</td>
                    <td>${esc(i.name)}<div class="pp-extra">${esc(i.extra)}</div></td>
                    <td>${i.rooms.length ? roomBadges(i.rooms) : '<span class="pp-no-room">Không còn phòng phù hợp</span>'}</td>
                    <td class="small">${i.declared ? esc(i.by || '') + '<br><span class="text-muted">' + esc(fmtDate(i.at)) + '</span>' : ''}</td>
                </tr>`;
            });
            $('#pp-list').html(html ||
                '<tr><td colspan="5" class="text-center text-muted py-3">Không có sản phẩm nào có định mức ở các phòng của nhân viên này.</td></tr>'
            );
            applyFilter();
            updateCounts();
        }

        function fmtDate(s) {
            if (!s) return '';
            const m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
            return m ? `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}` : s;
        }

        function applyFilter() {
            const q = $('#pp-search').val().trim().toLowerCase();
            const room = $('#pp-room-filter').val();
            const onlyChecked = $('#pp-only-checked').is(':checked');
            let visible = 0;
            $('#pp-list tr.pp-row').each(function() {
                const $tr = $(this);
                let show = true;
                if (q && $tr.attr('data-search').indexOf(q) === -1) show = false;
                if (show && room && (',' + $tr.attr('data-rooms') + ',').indexOf(',' + room + ',') === -1) show = false;
                if (show && onlyChecked && !$tr.hasClass('checked')) show = false;
                $tr.toggle(show);
                if (show) visible++;
            });
            $('#pp-visible-info').text(`Đang hiển thị ${visible}/${state.items[state.type].length} sản phẩm`);
        }

        function updateCounts() {
            let changes = [];
            Object.keys(typeLabels).forEach(type => {
                const cur = state.checked[type];
                const orig = state.original[type];
                $(`.pp-tab-count[data-type="${type}"]`).text(cur.size);
                let add = 0,
                    rem = 0;
                cur.forEach(c => {
                    if (!orig.has(c)) add++;
                });
                orig.forEach(c => {
                    if (!cur.has(c)) rem++;
                });
                if (add || rem) changes.push(`${type}: +${add} / -${rem}`);
            });
            $('#pp-change-info').html(changes.length ? '<i class="fas fa-pen text-warning"></i> Chưa lưu – ' + changes
                .join(', ') : '');
        }

        function setChecked($tr, val) {
            const code = $tr.attr('data-code');
            const set = state.checked[state.type];
            if (val) set.add(code);
            else set.delete(code);
            $tr.toggleClass('checked', val).toggleClass('changed', val !== state.original[state.type].has(code));
            $tr.find('.pp-check').prop('checked', val);
        }

        $('#pp-type-tabs').on('click', '.nav-link', function() {
            switchType($(this).data('type'));
        });
        $('#pp-search').on('input', applyFilter);
        $('#pp-room-filter, #pp-only-checked').on('change', applyFilter);

        $('#pp-list').on('change', '.pp-check', function() {
            setChecked($(this).closest('tr'), this.checked);
            updateCounts();
        });
        $('#pp-list').on('click', 'tr.pp-row td:not(:first-child)', function() {
            if (!canEdit) return;
            const $tr = $(this).closest('tr');
            setChecked($tr, !$tr.hasClass('checked'));
            updateCounts();
        });
        $('#pp-check-visible, #pp-uncheck-visible').on('click', function() {
            const val = this.id === 'pp-check-visible';
            $('#pp-list tr.pp-row:visible').each(function() {
                setChecked($(this), val);
            });
            updateCounts();
        });

        $('#pp-save').on('click', async function() {
            const $btn = $(this);
            const changedTypes = Object.keys(typeLabels).filter(type => {
                const cur = state.checked[type],
                    orig = state.original[type];
                if (cur.size !== orig.size) return true;
                for (const c of cur)
                    if (!orig.has(c)) return true;
                return false;
            });
            if (!changedTypes.length) {
                Swal.fire('Không có thay đổi', '', 'info');
                return;
            }

            $btn.prop('disabled', true);
            const messages = [];
            try {
                for (const type of changedTypes) {
                    const res = await $.ajax({
                        url: urls.save,
                        method: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            employee_id: state.employeeId,
                            type: type,
                            codes: Array.from(state.checked[type])
                        }
                    });
                    if (!res.success) throw {
                        responseJSON: res
                    };
                    messages.push(`${type}: ${res.message}`);
                    state.original[type] = new Set(state.checked[type]);

                    // Dựng lại ô "mã - tên" (kèm người / lúc cập nhật) theo danh sách server trả về
                    const $tr = $(`#tbl-pp-employees tr[data-employee-id="${state.employeeId}"]`);
                    const saved = res.items || [];
                    const savedByCode = {};
                    saved.forEach(i => savedByCode[i.code] = i);
                    state.items[type].forEach(i => {
                        const s = savedByCode[i.code];
                        i.declared = !!s;
                        i.by = s ? s.by : null;
                        i.at = s ? s.at : null;
                    });
                    $tr.find(`.pp-cell-${type}`).attr('data-order', saved.length).html(saved.length ?
                        `<div class="pp-list ${type.toLowerCase()}">` + saved.map(i =>
                            `<div class="pp-item"><span class="pp-code">${esc(i.code)}</span> - ${esc(i.name)}` +
                            `<span class="pp-meta"><i class="fas fa-user-edit"></i> ${esc(i.by)} · ${esc(i.at)}</span></div>`
                        ).join('') + '</div>' :
                        '<span class="pp-list-empty">—</span>');
                    if ($tr.length) empTable.row($tr).invalidate('dom');
                }
                renderList();
                Swal.fire({
                    icon: 'success',
                    title: 'Đã lưu',
                    html: messages.join('<br>'),
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (xhr) {
                const msg = xhr && xhr.responseJSON ? (xhr.responseJSON.message || 'Lỗi') : 'Lỗi kết nối server';
                Swal.fire('Lỗi', msg, 'error');
            } finally {
                $btn.prop('disabled', false);
            }
        });

        $('#pp-modal').on('hide.bs.modal', function(e) {
            if (!canEdit || !$('#pp-change-info').text()) return;
            if (!confirm('Có thay đổi chưa lưu. Đóng và bỏ các thay đổi này?')) {
                e.preventDefault();
            }
        });
    });
</script>
