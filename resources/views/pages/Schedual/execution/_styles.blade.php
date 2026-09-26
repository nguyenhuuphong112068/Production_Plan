{{-- CSS trang Thực Thi Sản Xuất, dùng chung cho trang công khai Trạng Thái Sản Xuất --}}
    <style>
        .exec-page {
            --navy: #003A4F;
            --c-clean: #16a34a;
            --c-preparing: #0e7490;
            --c-producing: #2563eb;
            --c-paused: #7c3aed;
            --c-dirty: #ea580c;
            --c-cleaning: #b7791f;
            --c-expired: #dc2626;
            color: #1f2937;
        }

        .st-clean { --sc: var(--c-clean); --sc-bg: rgba(22, 163, 74, .10); }
        .st-preparing { --sc: var(--c-preparing); --sc-bg: rgba(14, 116, 144, .10); }
        .st-producing { --sc: var(--c-producing); --sc-bg: rgba(37, 99, 235, .10); }
        .st-paused { --sc: var(--c-paused); --sc-bg: rgba(124, 58, 237, .10); }
        .st-dirty { --sc: var(--c-dirty); --sc-bg: rgba(234, 88, 12, .10); }
        .st-cleaning { --sc: var(--c-cleaning); --sc-bg: rgba(217, 119, 6, .12); }
        .st-expired { --sc: var(--c-expired); --sc-bg: rgba(220, 38, 38, .10); }

        /* ── Thanh công cụ ─────────────────────────────── */
        .exec-toolbar {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0, 58, 79, .08);
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .exec-title { display: flex; align-items: center; margin-bottom: 10px; }
        .exec-title-icon {
            width: 46px; height: 46px; border-radius: 12px; background: var(--navy); color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-right: 12px; flex-shrink: 0;
        }
        .exec-title h4 { margin: 0; font-weight: 800; color: var(--navy); font-size: 1.2rem; }
        .exec-title .small { color: #6b7785; }

        .exec-filters { display: flex; flex-wrap: wrap; align-items: center; margin: -4px; }
        .exec-filters > * { margin: 4px; }

        .exec-pill {
            border: 1px solid #d5dde6; background: #fff; color: var(--navy); border-radius: 999px;
            padding: 5px 12px; font-size: .82rem; font-weight: 600; cursor: pointer;
        }
        .exec-pill.active { background: var(--navy); border-color: var(--navy); color: #fff; }

        .exec-filter-state {
            border: 1px solid transparent; background: var(--sc-bg); color: var(--sc); border-radius: 999px;
            padding: 5px 11px; font-size: .8rem; font-weight: 700; cursor: pointer;
        }
        .exec-filter-state b { margin-left: 3px; }
        .exec-filter-state.active { border-color: var(--sc); box-shadow: 0 0 0 2px var(--sc-bg); }
        .exec-filter-state:focus, .exec-pill:focus { outline: none; box-shadow: 0 0 0 3px rgba(0, 58, 79, .2); }

        .exec-search { max-width: 260px; border-radius: 999px; font-size: .85rem; }

        /* Công tắc tịnh tuyến lịch (không dùng .custom-switch vì layout nạp Bootstrap 4.1.3) */
        .exec-reroute {
            display: flex; flex-wrap: wrap; align-items: center; padding: 7px 12px; margin-bottom: 10px;
            border: 1px dashed #f0ad4e; border-radius: 8px; background: #fffaf0; font-size: .85rem;
        }
        .exec-reroute > label + label { margin-left: 10px; }
        .exec-reroute.on { border-color: #dc3545; background: #fff5f5; color: #9b1c1c; }
        .exec-switch { position: relative; display: inline-block; width: 42px; height: 22px; flex-shrink: 0; }
        .exec-switch input { opacity: 0; width: 0; height: 0; }
        .exec-slider { position: absolute; inset: 0; cursor: pointer; background: #ccc; border-radius: 22px; transition: background .2s; }
        .exec-slider::before {
            content: ""; position: absolute; width: 16px; height: 16px; left: 3px; top: 3px;
            background: #fff; border-radius: 50%; transition: transform .2s;
        }
        .exec-switch input:checked + .exec-slider { background: #dc3545; }
        .exec-switch input:checked + .exec-slider::before { transform: translateX(20px); }
        .exec-switch input:focus-visible + .exec-slider { box-shadow: 0 0 0 2px #80bdff; }
        .exec-updated { font-size: .78rem; color: #6b7785; }

        /* ── Card công đoạn ────────────────────────────── */
        .exec-stage {
            border-radius: 14px; overflow: hidden; background: #f4f6f9;
            box-shadow: 0 2px 10px rgba(0, 58, 79, .08); margin-bottom: 18px;
        }
        .exec-stage-head {
            display: flex; align-items: center; flex-wrap: wrap; padding: 10px 16px; color: #fff; cursor: pointer; user-select: none;
        }
        .exec-stage-icon {
            width: 38px; height: 38px; border-radius: 10px; background: rgba(255, 255, 255, .95); color: var(--navy);
            display: flex; align-items: center; justify-content: center; margin-right: 12px;
        }
        .exec-stage-title { font-weight: 800; font-size: 1.05rem; text-transform: uppercase; margin-right: auto; }
        .exec-stage-title small { font-weight: 500; text-transform: none; opacity: .85; margin-left: 6px; }
        .exec-stage-counts { display: flex; flex-wrap: wrap; }
        .exec-stage-counts .exec-count { margin: 2px 3px; background: rgba(255, 255, 255, .95); }
        .exec-stage-caret { margin-left: 10px; transition: transform .2s; }
        .exec-stage.collapsed .exec-stage-caret { transform: rotate(180deg); }
        .exec-stage.collapsed .exec-stage-body { display: none; }
        .exec-stage-body { padding: 14px 14px 2px; }
        .exec-stage-empty { color: #6b7785; font-size: .85rem; padding: 0 4px 12px; }

        .g-blue { background: linear-gradient(135deg, #0d47a1, #1976d2); }
        .g-teal { background: linear-gradient(135deg, #004d40, #00796b); }
        .g-purple { background: linear-gradient(135deg, #4a148c, #7b1fa2); }
        .g-orange { background: linear-gradient(135deg, #bf360c, #ef6c00); }
        .g-rose { background: linear-gradient(135deg, #880e4f, #c2185b); }
        .g-slate { background: linear-gradient(135deg, #263238, #455a64); }

        .exec-count {
            display: inline-block; background: var(--sc-bg); color: var(--sc); border-radius: 999px;
            padding: 2px 9px; font-size: .75rem; font-weight: 700; white-space: nowrap;
        }

        /* ── Card phòng ────────────────────────────────── */
        @media (min-width: 1600px) {
            .exec-room-col { flex: 0 0 25%; max-width: 25%; }
        }

        .exec-room {
            background: #fff; border: 1px solid #e3e8ee; border-top: 4px solid var(--sc); border-radius: 12px;
            height: 100%; display: flex; flex-direction: column; transition: box-shadow .2s, transform .2s;
        }
        .exec-room:hover { box-shadow: 0 8px 20px rgba(0, 58, 79, .12); transform: translateY(-2px); }

        .exec-room-head { display: flex; justify-content: space-between; align-items: flex-start; padding: 10px 12px 6px; }
        .exec-room-title { min-width: 0; margin-right: 8px; }
        .exec-room-code {
            display: inline-block; background: var(--navy); color: #fff; border-radius: 999px; padding: 1px 10px;
            font-family: SFMono-Regular, Consolas, monospace; font-weight: 700; font-size: .8rem;
        }
        .exec-room-name { font-weight: 700; color: var(--navy); margin-top: 4px; line-height: 1.25; }
        .exec-room-equip { font-size: .75rem; color: #6b7785; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .exec-chip {
            background: var(--sc-bg); color: var(--sc); border: 1px solid var(--sc); border-radius: 999px;
            padding: 3px 9px; font-size: .7rem; font-weight: 800; text-transform: uppercase; white-space: nowrap; flex-shrink: 0;
        }
        .st-producing .exec-chip i { animation: exec-spin 3s linear infinite; }
        .st-expired .exec-chip { animation: exec-blink 1.4s ease-in-out infinite; }
        @keyframes exec-spin { to { transform: rotate(360deg); } }
        @keyframes exec-blink { 50% { opacity: .55; } }

        .exec-room-body { padding: 4px 12px 8px; flex: 1; font-size: .84rem; }
        .exec-batch {
            background: var(--sc-bg); border-left: 3px solid var(--sc); border-radius: 8px; padding: 7px 10px; margin-bottom: 6px;
        }
        .exec-batch-name { font-weight: 700; color: #111827; line-height: 1.3; }
        .exec-batch-meta { font-size: .78rem; color: #4b5563; }
        .exec-kv { display: flex; justify-content: space-between; color: #4b5563; font-size: .8rem; padding: 1px 0; }
        .exec-kv span { margin-right: 8px; white-space: nowrap; }
        .exec-kv b { color: #111827; font-weight: 600; text-align: right; }
        .exec-progress { height: 6px; background: #eef1f5; border-radius: 999px; overflow: hidden; margin-top: 3px; }
        .exec-progress > div { height: 100%; background: var(--sc); }
        .exec-note { font-size: .76rem; color: #6b7785; font-style: italic; margin-top: 4px; }
        .exec-next { font-size: .77rem; color: #4b5563; border-top: 1px dashed #e3e8ee; margin-top: 8px; padding-top: 6px; }

        /* ── Hoạt động đang diễn ra: phần chủ đạo của card ── */
        .exec-room.is-active { box-shadow: 0 0 0 2px var(--sc-bg), 0 6px 16px rgba(0, 58, 79, .12); }
        /* Nền nhạt theo trạng thái: --lv-seg màu đoạn đã khai báo, --lv-run lần đang chạy */
        .exec-live {
            --lv-bg: #eef6ff; --lv-bd: #cfe3fb; --lv-acc: #3b82f6; --lv-ink: #0f2f57; --lv-strong: #1d4ed8; --lv-seg: #2563eb; --lv-run: #60a5fa;
            background: var(--lv-bg); color: var(--lv-ink); border: 1px solid var(--lv-bd); border-left: 4px solid var(--lv-acc);
            border-radius: 10px; padding: 9px 11px; margin-bottom: 8px;
        }
        .st-preparing .exec-live { --lv-bg: #ecfeff; --lv-bd: #bae6f0; --lv-acc: #06b6d4; --lv-ink: #083344; --lv-strong: #0e7490; }
        .st-paused .exec-live { --lv-bg: #f6f3ff; --lv-bd: #e2dafb; --lv-acc: #8b5cf6; --lv-ink: #2e1065; --lv-strong: #6d28d9; --lv-seg: #7c3aed; --lv-run: #a78bfa; }
        .st-cleaning .exec-live { --lv-bg: #fff8eb; --lv-bd: #fde3b5; --lv-acc: #f59e0b; --lv-ink: #451a03; --lv-strong: #b45309; }
        .exec-live-product { font-weight: 800; font-size: 1.05rem; line-height: 1.25; }
        .exec-live-tag {
            font-size: .62rem; font-weight: 700; color: var(--lv-strong); background: #fff; border: 1px solid var(--lv-bd);
            border-radius: 999px; padding: 1px 6px; vertical-align: middle; white-space: nowrap;
        }
        .exec-live-batch { font-size: .78rem; color: #4b5563; margin-top: 1px; }
        .exec-live-batch b { font-size: .9rem; color: var(--lv-ink); }
        .exec-live-pause {
            display: inline-block; margin-top: 6px; padding: 2px 8px; border-radius: 6px;
            background: #fff; border: 1px solid var(--lv-bd); color: var(--lv-strong); font-size: .76rem; font-weight: 700;
        }
        .exec-live-stats { display: flex; justify-content: space-between; margin-top: 7px; }
        .exec-live-stat { min-width: 0; }
        .exec-live-stat + .exec-live-stat { margin-left: 10px; }
        .exec-live-label { font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
        .exec-live-big { font-size: 1.55rem; font-weight: 800; line-height: 1.2; color: var(--lv-strong); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .exec-live-big.long { font-size: 1.12rem; line-height: 1.66; }
        .exec-live-big.muted { color: #94a3b8; }
        .exec-live-small { font-size: .7rem; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .exec-live-foot { display: flex; flex-wrap: wrap; margin-top: 5px; font-size: .72rem; color: #475569; }
        .exec-live-foot span { margin-right: 10px; white-space: nowrap; }
        .exec-live-foot b { color: var(--lv-ink); }

        /* Thanh tiến trình thời gian từ BĐSX: đoạn xanh ngọc = chuẩn bị (BĐSX → BĐCM), đoạn đậm = lần đã khai báo sản lượng,
           sọc chạy = lần đang chạy / đang chuẩn bị, vạch chấm = khoảng dừng giữa 2 lần, vạch đậm = hết thời lượng theo lịch,
           nền nhạt = phần còn lại theo lịch. Mỗi đoạn rộng tối thiểu 3px để thấy ngay từ lúc vừa bắt đầu */
        .exec-tl { margin-top: 7px; }
        .exec-tl-track { position: relative; height: 18px; border-radius: 5px; overflow: hidden; background: #dde8f5; box-shadow: inset 0 0 0 1px rgba(15, 47, 87, .08); }
        .exec-tl-track > div { position: absolute; top: 0; bottom: 0; }
        .exec-tl-gap { width: 4px; transform: translateX(-50%); background: repeating-linear-gradient(180deg, #334155 0 2px, #fff 2px 4px); }
        .exec-tl-plan { width: 2px; transform: translateX(-50%); background: #0f172a; }
        .exec-tl-seg {
            background: var(--lv-seg); border-right: 1px solid #fff; color: #fff; font-size: .64rem; font-weight: 800;
            line-height: 18px; text-align: center; overflow: hidden; white-space: nowrap; cursor: default;
        }
        .exec-tl-seg.nolabel span { display: none; }
        .exec-tl-run {
            background-color: var(--lv-run); background-size: 17px 17px;
            background-image: linear-gradient(135deg, rgba(255, 255, 255, .45) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, .45) 50%, rgba(255, 255, 255, .45) 75%, transparent 75%);
            animation: exec-stripes 1s linear infinite;
        }
        .exec-tl-prep, .exec-tl-seg, .exec-tl-run { min-width: 3px; }
        .exec-tl-prep { background: var(--c-preparing); border-right: 1px solid #fff; cursor: default; }
        .exec-tl-run.prep { background-color: #06b6d4; }
        .exec-tl-run.over { background-color: #f59e0b; }
        @keyframes exec-stripes { from { background-position: 0 0; } to { background-position: 17px 0; } }
        .exec-tl-axis { display: flex; justify-content: space-between; margin-top: 2px; font-size: .68rem; color: #64748b; }
        .exec-tl-axis span + span { text-align: right; margin-left: 8px; }

        .exec-activity {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; padding: 7px 9px;
            background: linear-gradient(135deg, #fff7ed, #ffedd5); border: 1px solid #fdba74; border-left: 4px solid #ea580c; border-radius: 8px;
        }
        .exec-activity-main { min-width: 0; margin-right: 8px; }
        .exec-activity-name { font-weight: 800; color: #9a3412; font-size: .88rem; line-height: 1.25; }
        .exec-activity-note, .exec-activity-time { font-size: .74rem; color: #7c2d12; }
        .exec-activity-clock { font-size: 1.05rem; font-weight: 800; color: #c2410c; font-variant-numeric: tabular-nums; margin-right: 3px; }
        .exec-activity .btn { font-size: .72rem; padding: 3px 8px; white-space: nowrap; flex-shrink: 0; }

        /* ── Nhân sự đang được phân công (Lịch Công Tác) ── */
        .exec-staff {
            margin-bottom: 8px; padding: 5px 8px; background: #f8fafc; border: 1px solid #e3e8ee; border-radius: 8px; font-size: .76rem;
        }
        .exec-staff-shift { display: flex; flex-wrap: wrap; align-items: center; }
        .exec-staff-head { font-weight: 700; color: var(--navy); margin-right: 6px; white-space: nowrap; }
        .exec-staff-head span { font-weight: 500; color: #6b7785; }
        .exec-staff-person {
            display: inline-flex; align-items: center; margin: 2px 4px 2px 0; padding: 1px 8px 1px 2px; white-space: nowrap;
            background: #fff; border: 1px solid #dbe3ec; border-radius: 999px; font-weight: 600; color: #1f2937;
        }
        .exec-staff-person i {
            display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; margin-right: 4px;
            border-radius: 50%; background: var(--navy); color: #fff; font-size: .6rem; font-style: normal; font-weight: 700;
        }
        .exec-staff-job { color: #6b7785; font-size: .72rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .exec-staff.empty { color: #92400e; background: #fffbeb; border-color: #fde68a; }

        /* ── Thiết bị HC-BT trên card ─────────────────── */
        .exec-equip {
            display: block; width: 100%; margin-top: 8px; padding: 5px 8px;
            background: #f8fafc; border: 1px solid #e3e8ee; border-radius: 8px; font-size: .75rem; text-align: left; cursor: pointer;
        }
        .exec-equip:hover { background: #eef4fb; border-color: #c9d6e3; }
        .exec-equip-head { display: flex; align-items: center; }
        .exec-equip-title { font-weight: 700; color: var(--navy); margin-right: 4px; white-space: nowrap; }
        .exec-equip-status { flex: 1; display: flex; justify-content: space-between; align-items: center; min-width: 0; }
        .exec-eq-state { font-weight: 700; white-space: nowrap; }
        .exec-eq-state.lv0 { color: #15803d; }
        .exec-eq-state.lv1 { color: #b45309; }
        .exec-eq-state.lv2 { color: #b91c1c; }
        .exec-equip-list { display: block; line-height: 1.5; }
        .exec-equip-list:not(:empty) { margin-top: 3px; }
        .exec-eq-chip {
            display: inline-block; border-radius: 999px; padding: 0 6px; margin: 1px 2px 1px 0; font-size: .7rem; font-weight: 700;
            white-space: nowrap; border: 1px solid transparent;
        }
        .exec-eq-chip .fas { font-size: .65rem; }
        .exec-eq-chip small { font-weight: 800; }
        .exec-eq-chip[data-code]:hover { border-color: currentColor; }
        .exec-eq-chip.lv0 { background: rgba(22, 163, 74, .12); color: #15803d; }
        .exec-eq-chip.lv1 { background: rgba(245, 158, 11, .18); color: #92400e; }
        .exec-eq-chip.lv2 { background: rgba(220, 38, 38, .12); color: #b91c1c; }
        .exec-eq-chip.more { background: #e9eef4; color: #4b5563; }
        .exec-eq-none { color: #6b7785; font-style: italic; }

        /* ── Nhãn HC-BT (giống nhãn in bên eBMR) ────────── */
        .exec-equip-table td { vertical-align: middle; font-size: .85rem; }
        .exec-equip-table .btn { font-size: .75rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
        .exec-label-row > td { background: #f8fafc; padding: 10px 14px !important; }
        .exec-label { border: 2px solid; border-radius: 8px; overflow: hidden; background: #fff; margin: 4px 0; }
        .exec-label-head { padding: 7px 12px; font-weight: 800; display: flex; justify-content: space-between; align-items: center; }
        .exec-label.lv0 { border-color: #8bc34a; } .exec-label.lv0 .exec-label-head { background: #8bc34a; color: #1f2937; }
        .exec-label.lv1 { border-color: #fd7e14; } .exec-label.lv1 .exec-label-head { background: #fd7e14; color: #fff; }
        .exec-label.lv2 { border-color: #dc3545; } .exec-label.lv2 .exec-label-head { background: #dc3545; color: #fff; }
        .exec-label-info { padding: 6px 12px; font-size: .88rem; }
        .exec-label-info b { display: inline-block; min-width: 120px; }
        .exec-label table { margin: 0; font-size: .82rem; }
        .exec-label th { background: #f4f6f9; }

        .exec-room-actions { display: flex; flex-wrap: wrap; padding: 0 9px 9px; }
        .exec-room-actions:empty { display: none; }
        .btn-exec {
            flex: 1 1 0; margin: 3px; min-height: 42px; border: 0; border-radius: 10px; color: #fff !important;
            font-weight: 700; font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .12);
        }
        .btn-exec:hover { filter: brightness(1.08); transform: translateY(-1px); }
        .btn-exec i { margin-right: 4px; }
        .btn-exec-go { background: linear-gradient(135deg, #16a34a, #15803d); }
        .btn-exec-pause { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
        .btn-exec-stop { background: linear-gradient(135deg, #dc2626, #b91c1c); }
        .btn-exec-clean { background: linear-gradient(135deg, #f97316, #ea580c); }
        .btn-exec-cleanend { background: linear-gradient(135deg, #d97706, #b45309); }
        .btn-exec-danger { background: linear-gradient(135deg, #ef4444, #b91c1c); }

        .exec-room-foot { display: flex; border-top: 1px solid #eef1f5; }
        .exec-room-foot button {
            flex: 1; background: none; border: 0; padding: 7px 4px; font-size: .74rem; font-weight: 600; color: var(--navy);
            white-space: nowrap;
        }
        .exec-room-foot button + button { border-left: 1px solid #eef1f5; }
        .exec-room-foot button:hover { background: #f4f6f9; }
        .exec-room-foot button:first-child { border-bottom-left-radius: 12px; }
        .exec-room-foot button:last-child { border-bottom-right-radius: 12px; }
        .exec-undo-left { font-variant-numeric: tabular-nums; }

        .exec-empty { text-align: center; color: #6b7785; padding: 60px 0; }

        /* ── Modal ─────────────────────────────────────── */
        /* Bootstrap 4.1.3 của layout chưa có .modal-xl */
        @media (min-width: 992px) { .exec-modal .modal-xl { max-width: 960px; } }
        @media (min-width: 1200px) { .exec-modal .modal-xl { max-width: 1140px; } }

        .exec-modal .modal-header { color: #fff; }
        .exec-modal .modal-header .close { color: #fff; opacity: .9; text-shadow: none; }
        .exec-modal .modal-title { font-weight: 700; }
        .exec-modal label { font-weight: 600; font-size: .85rem; margin-bottom: 3px; }
        .exec-modal .form-text { font-size: .76rem; }
        .exec-mh-navy { background: #003A4F; }
        .exec-mh-go { background: linear-gradient(135deg, #16a34a, #15803d); }
        .exec-mh-pause { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
        .exec-mh-stop { background: linear-gradient(135deg, #dc2626, #b91c1c); }
        .exec-mh-clean { background: linear-gradient(135deg, #f97316, #ea580c); }
        .exec-mh-cleanend { background: linear-gradient(135deg, #d97706, #b45309); }

        .exec-modal-batch { background: #f4f6f9; border-radius: 10px; padding: 8px 12px; margin-bottom: 12px; }
        .exec-modal-batch b { color: #111827; }
        .exec-now { font-size: .84rem; color: #334155; background: #eef6ff; border: 1px dashed #bcd4ee; border-radius: 8px; padding: 7px 11px; }
        .exec-now .fa-clock { color: #1d4ed8; }
        .exec-now b { color: #0f2f57; font-variant-numeric: tabular-nums; }

        .exec-plan-list { max-height: 46vh; overflow-y: auto; border: 1px solid #e3e8ee; border-radius: 10px; }
        .exec-plan {
            display: flex; align-items: flex-start; padding: 8px 12px; margin: 0; border-bottom: 1px solid #eef1f5;
            cursor: pointer; font-weight: 400 !important;
        }
        .exec-plan:last-child { border-bottom: 0; }
        .exec-plan:hover { background: #f8fafc; }
        .exec-plan.selected { background: rgba(22, 163, 74, .08); box-shadow: inset 3px 0 0 #16a34a; }
        .exec-plan input { margin: 5px 10px 0 0; }
        .exec-plan-main { flex: 1; min-width: 0; }
        .exec-plan-name { font-weight: 700; color: #111827; }
        .exec-plan-meta { font-size: .8rem; color: #4b5563; }
        .exec-plan-side { text-align: right; margin-left: 8px; }
        .exec-plan-side .badge { display: inline-block; margin-bottom: 3px; }
        .exec-plan-empty { padding: 24px; text-align: center; color: #6b7785; }

        .exec-history td { font-size: .82rem; vertical-align: top; }
        .exec-history .exec-cancelled td { text-decoration: line-through; color: #9ca3af; }
        .exec-history .exec-cancelled td .text-danger { text-decoration: none; }
    </style>
