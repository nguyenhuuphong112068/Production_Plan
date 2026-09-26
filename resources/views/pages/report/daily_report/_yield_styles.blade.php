{{-- CSS card "Sản Lượng" của Báo cáo ngày: bảng công đoạn → phòng và cột Chi tiết (dòng thời gian trong ngày) --}}
<style>
    /* ── Card ─────────────────────────────────────── */
    .card.yc-card {
        border: 1px solid #e2e8f0; border-radius: 12px; background: #fff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .06), 0 8px 24px rgba(15, 23, 42, .05);
    }
    .card.yc-card > .card-header {
        display: flex; align-items: center; padding: 16px 20px; background: #fff;
        border-top: 4px solid #CDC717; border-bottom: 1px solid #e2e8f0; border-radius: 12px 12px 0 0;
    }
    .card.yc-card > .card-header::after { display: none; }
    .yc-head { display: flex; align-items: center; min-width: 0; }
    .yc-head-icon {
        width: 42px; height: 42px; margin-right: 14px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; background: #003A4F; color: #CDC717; font-size: 19px;
    }
    .card.yc-card .card-title { float: none; margin: 0; color: #003A4F; font-size: 21px; font-weight: 800; line-height: 1.2; }
    .yc-head-sub { margin-top: 3px; color: #64748b; font-size: 14px; }
    .card.yc-card > .card-header .card-tools { margin-left: auto; }
    .card.yc-card > .card-header .btn-tool { color: #64748b; font-size: 16px; }
    .card.yc-card > .card-header .btn-tool:hover { color: #003A4F; }
    .card.yc-card > .card-body { padding: 16px 20px 20px; }

    /* Chú thích màu dòng thời gian */
    .yc-legend { margin-bottom: 14px; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; color: #334155; font-size: 13.5px; }
    .yc-legend-row { display: flex; flex-wrap: wrap; align-items: center; }
    .yc-legend-row + .yc-legend-row { margin-top: 6px; }
    .yc-legend-group { width: 150px; color: #003A4F; font-size: 12px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .yc-lg { display: inline-flex; align-items: center; margin-right: 18px; white-space: nowrap; }
    .yc-sw { display: inline-block; width: 18px; height: 11px; margin-right: 7px; border-radius: 3px; }

    /* ── Bảng ─────────────────────────────────────── */
    #data_table_yield.yc-table { width: 100%; table-layout: fixed; margin: 0; border-collapse: separate; border-spacing: 0; color: #1e293b; font-size: 18px; }
    #data_table_yield thead { position: sticky; top: 60px; z-index: 1020; }
    #data_table_yield thead th {
        padding: 12px 14px; background: #003A4F; color: #fff; vertical-align: middle; white-space: normal; line-height: 1.25; border: 0;
        font-size: 15.6px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
    }
    #data_table_yield thead th:first-child { border-top-left-radius: 10px; }
    #data_table_yield thead th:last-child { border-top-right-radius: 10px; }
    #data_table_yield td { padding: 14px; border-top: 0; border-bottom: 1px solid #e8edf3; vertical-align: top; }
    #data_table_yield td:first-child { border-left: 1px solid #e8edf3; }
    #data_table_yield td:last-child { border-right: 1px solid #e8edf3; }
    #data_table_yield .yc-col-room { width: 170px; }
    #data_table_yield .yc-col-unit { width: 60px; text-align: center; }
    #data_table_yield .yc-col-lt { width: 220px; text-align: right; }
    #data_table_yield .yc-col-tt { width: 140px; text-align: right; }
    #data_table_yield .yc-col-pct { width: 120px; }
    #data_table_yield .yc-col-detail { width: auto; }
    #data_table_yield td.yc-right { text-align: right; }

    /* Phân biệt cột: vạch màu dưới tiêu đề + nền nhạt cùng tông cho cả cột, đường ngăn dọc giữa các cột */
    #data_table_yield thead th { border-bottom: 4px solid transparent; }
    #data_table_yield thead th:nth-child(3) { border-bottom-color: #22c55e; }
    #data_table_yield thead th:nth-child(4) { border-bottom-color: #3b82f6; }
    #data_table_yield thead th:nth-child(5) { border-bottom-color: #f59e0b; }
    #data_table_yield thead th:nth-child(6) { border-bottom-color: #CDC717; }
    #data_table_yield thead th + th { box-shadow: inset 1px 0 0 rgba(255, 255, 255, .15); }
    #data_table_yield td + td { border-left: 1px solid #e8edf3; }
    #data_table_yield tr.yc-room-row > td:nth-child(3) { background: #f4fbf6; }
    #data_table_yield tr.yc-room-row > td:nth-child(4) { background: #f3f7ff; }
    #data_table_yield tr.yc-room-row > td:nth-child(5) { background: #fffaf0; }
    #data_table_yield tr.yc-stage > td + td { border-left-color: rgba(0, 58, 79, .15); }

    /* Dòng công đoạn: nền vàng Stella cả dòng, chữ navy */
    #data_table_yield tr.yc-stage > td { background: #CDC717 !important; border-color: #b8b300; color: #003A4F; }
    #data_table_yield tr.yc-stage > td:first-child { box-shadow: inset 5px 0 0 #003A4F; }
    #data_table_yield tr.yc-stage .yc-stage-meta,
    #data_table_yield tr.yc-stage .yc-unit,
    #data_table_yield tr.yc-stage .yc-sub,
    #data_table_yield tr.yc-stage .yc-break span,
    #data_table_yield tr.yc-stage .yc-explain-label,
    #data_table_yield tr.yc-stage .yc-explain-text.empty { color: #3d4a1a; }
    #data_table_yield tr.yc-stage .yc-num,
    #data_table_yield tr.yc-stage .yc-break b,
    #data_table_yield tr.yc-stage .yc-explain-text { color: #003A4F; }
    #data_table_yield tr.yc-stage .yc-num-actual { color: #0b3d91; }
    #data_table_yield tr.yc-stage .yc-bar { background: rgba(255, 255, 255, .7); }
    #data_table_yield tr.yc-stage .yc-pct-val { box-shadow: 0 0 0 1px rgba(0, 0, 0, .06); }
    #data_table_yield tr.yc-stage .yc-toggle,
    #data_table_yield tr.yc-stage .btn-explain { border-color: rgba(0, 58, 79, .3); }

    /* Dòng công đoạn: nền xám nhạt, vạch vàng thương hiệu bên trái */
    #data_table_yield tr.yc-stage > td { background: #f1f5f9; border-top: 1px solid #dbe3ec; border-bottom: 1px solid #dbe3ec; padding-top: 12px; padding-bottom: 12px; }
    #data_table_yield tr.yc-stage > td:first-child { box-shadow: inset 5px 0 0 #CDC717; padding-left: 16px; }
    .yc-stage-head { display: flex; align-items: flex-start; }
    #data_table_yield .yc-toggle {
        width: 28px; height: 28px; margin: 0 10px 0 0; padding: 0; flex-shrink: 0; border-radius: 7px;
        border: 1px solid #cbd5e1; background: #fff; color: #003A4F; font-size: 14.4px; line-height: 26px;
    }
    #data_table_yield .yc-toggle:hover { background: #e2e8f0; }
    .yc-toggle i { transition: transform .15s; }
    .yc-toggle.collapsed i { transform: rotate(-90deg); }
    .yc-stage-name { color: #003A4F; font-size: 20.4px; font-weight: 800; line-height: 1.25; }
    .yc-stage-meta { margin-top: 2px; color: #64748b; font-size: 15.6px; }

    /* Phòng */
    .yc-room { display: flex; align-items: flex-start; }
    .yc-code {
        margin-right: 8px; padding: 2px 8px; border-radius: 6px; background: #e8eef5; color: #003A4F;
        font-size: 15.6px; font-weight: 800; white-space: nowrap;
    }
    .yc-room-name { color: #0f172a; font-size: 18px; font-weight: 600; line-height: 1.35; }
    .yc-unit { color: #64748b; font-size: 16.8px; font-weight: 700; text-align: center; }

    /* Số liệu: canh phải, cùng cỡ để dễ so lý thuyết ↔ thực tế */
    .yc-num { color: #0f172a; font-size: 21.6px; font-weight: 800; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .yc-num-actual { color: #1d4ed8; }
    .yc-sub { margin-top: 2px; color: #64748b; font-size: 15.6px; font-weight: 600; white-space: nowrap; }
    .yc-break {
        display: inline-grid; grid-template-columns: auto auto; column-gap: 14px; row-gap: 2px; margin-top: 6px;
        font-size: 16.2px; text-align: right;
    }
    .yc-break span { color: #64748b; text-align: left; }
    .yc-break b { color: #334155; font-variant-numeric: tabular-nums; }

    /* Lô theo lịch lý thuyết */
    .yc-plans { margin-top: 10px; padding-top: 8px; border-top: 1px dashed #e2e8f0; color: #475569; font-size: 15.6px; line-height: 1.4; text-align: left; }
    .yc-plan { display: grid; grid-template-columns: minmax(0, 1fr) auto; column-gap: 10px; padding: 4px 0; }
    .yc-plan + .yc-plan { border-top: 1px solid #f1f5f9; }
    .yc-plan-time { color: #64748b; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .yc-plan-title { grid-column: 1 / -1; grid-row: 2; color: #334155; overflow-wrap: break-word; }
    .yc-plan-qty { color: #0f172a; font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; }
    .yc-more > summary { margin-top: 4px; color: #0e7490; font-weight: 700; cursor: pointer; list-style: none; }
    .yc-more > summary::-webkit-details-marker { display: none; }
    .yc-more[open] > summary { display: none; }

    /* Phần trăm đáp ứng: vạch mốc 90% */
    .yc-pct-val { display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 18px; font-weight: 800; font-variant-numeric: tabular-nums; }
    .yc-pct.ok .yc-pct-val { color: #15803d; background: #dcfce7; }
    .yc-pct.low .yc-pct-val { color: #b91c1c; background: #fee2e2; }
    .yc-pct.none .yc-pct-val { color: #94a3b8; background: #f1f5f9; }
    .yc-bar { position: relative; height: 7px; margin-top: 10px; border-radius: 4px; background: #e2e8f0; }
    .yc-bar > span { position: absolute; top: 0; bottom: 0; left: 0; border-radius: 4px; }
    .yc-bar::after { content: ''; position: absolute; top: -3px; bottom: -3px; left: 90%; width: 2px; border-radius: 1px; background: #64748b; }
    .yc-pct.ok .yc-bar > span { background: #22c55e; }
    .yc-pct.low .yc-bar > span { background: #ef4444; }

    /* Giải trình / lý do của công đoạn: 1 dòng gọn, có nội dung mới nổi bật */
    .yc-explain { display: flex; align-items: flex-start; }
    .yc-explain-item { flex: 1 1 0; min-width: 0; margin-right: 16px; font-size: 16.8px; line-height: 1.45; }
    .yc-explain-label { margin-right: 6px; color: #64748b; font-size: 14.4px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .yc-explain-text { color: #0f172a; white-space: pre-line; overflow-wrap: anywhere; }
    .yc-explain-text.empty { color: #94a3b8; }
    #data_table_yield .yc-explain .btn-explain {
        width: 30px; height: 30px; padding: 0; border-radius: 7px; font-size: 14.4px; line-height: 28px;
        border: 1px solid #cbd5e1; background: #fff; color: #003A4F; flex-shrink: 0;
    }
    #data_table_yield .yc-explain .btn-explain:hover { background: #e2e8f0; }
    /* ── Cột Chi tiết của phòng: các khoảng thời gian trong ngày + dòng thời gian 06:00 → 06:00 ── */
    #data_table_yield td.dr-detail-cell { background: #f8fafc; color: #1e293b; font-size: 16.8px; line-height: 1.4; padding: 12px 14px; }
    .dr-items { background: #fff; border: 1px solid #dfe7f1; border-radius: 8px; overflow: hidden; }
    .dr-item {
        display: grid; grid-template-columns: 104px 64px 152px minmax(0, 1fr) auto; column-gap: 10px;
        align-items: start; padding: 6px 10px;
    }
    .dr-item + .dr-item { border-top: 1px solid #eef2f7; }
    .dr-item:hover { background: #f8fbff; }
    .dr-item.is-idle { background: #fafbfc; color: #475569; }
    .dr-item.is-idle:hover { background: #f5f7fa; }
    .dr-time { color: #334155; font-weight: 600; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .dr-item.is-idle .dr-time { color: #64748b; font-weight: 500; }
    .dr-dur {
        justify-self: start; padding: 0 7px; border-radius: 999px; background: #eef2f7; color: #475569;
        font-size: 14.4px; font-weight: 700; white-space: nowrap; font-variant-numeric: tabular-nums;
    }
    .dr-main { min-width: 0; }
    .dr-title { color: #0f172a; font-weight: 600; overflow-wrap: break-word; }
    .dr-item.is-idle .dr-title { display: flex; align-items: center; color: #334155; }
    .dr-note { margin-top: 1px; color: #64748b; font-size: 15.6px; overflow-wrap: break-word; }
    .dr-live {
        display: inline-block; margin-left: 6px; padding: 0 6px; border-radius: 999px; background: #e0f2fe; color: #0369a1;
        font-size: 13.2px; font-weight: 700; vertical-align: 1px;
    }
    .dr-end { display: flex; align-items: center; justify-self: end; }
    .dr-yield { color: #0f172a; font-weight: 700; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .dr-yield small { display: block; color: #64748b; font-size: 13.8px; font-weight: 600; }

    /* Badge loại: khoảng đã ghi nhận nền màu nhạt, khoảng ngưng hoạt động viền nét đứt */
    .dr-state { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 14.4px; font-weight: 700; white-space: nowrap; }
    .dr-state-producing { color: #1d4ed8; background: rgba(37, 99, 235, .10); }
    .dr-state-preparing { color: #0e7490; background: rgba(8, 145, 178, .11); }
    .dr-state-cleaning { color: #a16207; background: rgba(234, 179, 8, .16); }
    .dr-state-paused { color: #6d28d9; background: rgba(124, 58, 237, .10); }
    .dr-state-activity { color: #c2410c; background: rgba(249, 115, 22, .12); }
    .dr-state-idle { color: #475569; background: #fff; border: 1px dashed #94a3b8; padding: 0 7px; }

    /* Màu từng loại trên dòng thời gian; khoảng ngưng hoạt động tô sọc */
    .dr-seg-producing { background: #3b82f6; }
    .dr-seg-preparing { background: #06b6d4; }
    .dr-seg-cleaning { background: #eab308; }
    .dr-seg-paused { background: #8b5cf6; }
    .dr-seg-activity { background: #f97316; }
    /* Không hoạt động (khoảng không có dữ liệu ghi nhận): 1 màu xám sọc chung cho mọi lý do */
    .dr-idle { background: repeating-linear-gradient(135deg, #94a3b8 0 3px, #e2e8f0 3px 6px); }
    .dr-dot { display: inline-block; width: 12px; height: 12px; margin-right: 7px; border-radius: 3px; flex-shrink: 0; }

    /* Nút sửa / hủy hoạt động nhập tay (kèm id bảng để thắng kích thước .btn của theme) */
    #data_table_yield .dr-act {
        width: 26px; height: 26px; padding: 0; margin-left: 4px; border: 1px solid #e2e8f0; border-radius: 6px;
        background: #fff; font-size: 14.4px; line-height: 24px;
    }
    #data_table_yield .dr-act-edit { color: #b45309; }
    #data_table_yield .dr-act-edit:hover { background: #fef3c7; color: #92400e; }
    #data_table_yield .dr-act-del { color: #dc2626; }
    #data_table_yield .dr-act-del:hover { background: #fee2e2; color: #b91c1c; }

    /* Dòng thời gian trong ngày: vạch mỗi 6 giờ, vạch "bây giờ" khi xem ngày hôm nay */
    .dr-day { margin-top: 10px; }
    .dr-day-track {
        position: relative; height: 12px; border-radius: 6px; overflow: hidden; background-color: #f1f5f9;
        box-shadow: inset 0 0 0 1px #e2e8f0;
    }
    .dr-day-track::after {
        content: ''; position: absolute; inset: 0; pointer-events: none;
        background-image: repeating-linear-gradient(to right, transparent 0 calc(25% - 1px), rgba(255, 255, 255, .95) calc(25% - 1px) 25%);
    }
    .dr-seg { position: absolute; top: 0; bottom: 0; min-width: 2px; }
    .dr-now { position: absolute; top: -3px; bottom: -3px; width: 2px; margin-left: -1px; background: #0f172a; z-index: 1; }
    .dr-day-ticks { position: relative; height: 14px; margin-top: 3px; color: #94a3b8; font-size: 12.6px; font-variant-numeric: tabular-nums; }
    .dr-day-ticks span { position: absolute; top: 0; transform: translateX(-50%); }
    .dr-day-ticks span:first-child { transform: none; }
    .dr-day-ticks span:last-child { transform: translateX(-100%); }

    /* Tổng thời gian */
    .dr-foot { display: flex; align-items: center; flex-wrap: wrap; margin-top: 4px; }
    .dr-total { margin-right: 16px; color: #475569; font-size: 15px; white-space: nowrap; }
    .dr-total b { color: #0f172a; }
    .dr-total-ok i { color: #16a34a; }
    .dr-total-idle i { color: #64748b; }
    .dr-empty { color: #94a3b8; font-style: italic; }
    .dr-reasons { display: flex; flex-wrap: wrap; align-items: center; margin-top: 6px; }
    #data_table_yield .dr-add { margin: 0 0 4px auto; padding: 2px 10px; border-radius: 6px; font-size: 15px; line-height: 1.4; }
    .dr-reason {
        display: inline-flex; align-items: center; margin: 0 6px 4px 0; padding: 2px 8px; border-radius: 999px;
        background: #fff; border: 1px solid #e2e8f0; color: #475569; font-size: 14.4px; white-space: nowrap;
    }
    .dr-reason .dr-dot { width: 10px; height: 10px; margin-right: 6px; }
    .dr-reason b { margin-left: 5px; color: #0f172a; font-variant-numeric: tabular-nums; }
</style>
