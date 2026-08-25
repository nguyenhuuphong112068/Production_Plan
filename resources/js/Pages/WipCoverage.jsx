import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import {
    ComposedChart,
    Bar,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';

import {
    colorOfGroup,
    formatDvl,
    formatFull,
    formatKg,
    groupLabel,
    limitStateOf,
    SUPPLY_COLOR,
    formatDate,
    formatDateShort,
} from '../Components/wipCoverageShared';

const WipCoverage = () => {
    const [allGroups, setAllGroups] = useState([]);
    const [supply, setSupply] = useState([]);
    const [limits, setLimits] = useState({});
    const [meta, setMeta] = useState(null);
    const [selected, setSelected] = useState(null);
    const [details, setDetails] = useState([]);
    const [loading, setLoading] = useState(true);
    const [detailLoading, setDetailLoading] = useState(false);
    const [error, setError] = useState(null);
    const [expanded, setExpanded] = useState(null);
    const [showFlows, setShowFlows] = useState(false);

    // Cho phép mở thẳng vào một công đoạn từ nơi khác, ví dụ ?group=DG
    const initialGroup = useMemo(
        () => new URLSearchParams(window.location.search).get('group'),
        []
    );

    const load = useCallback(
        (live = false) => {
            setLoading(true);
            setError(null);

            axios
                .post('/Schedual/wip_coverage/view', { live: live ? 1 : 0 })
                .then(({ data }) => {
                    if (!data.success) {
                        setError(data.message || 'Không tải được dữ liệu.');
                        return;
                    }

                    const list = (data.groups || []).filter((g) => !g.is_empty);
                    setAllGroups(list);
                    setSupply(data.supply || []);

                    // Tra theo mã công đoạn cho nhanh khi tô từng ô của bảng
                    const byGroup = {};
                    (data.stock_limits || []).forEach((l) => {
                        byGroup[l.stage_group_code] = l;
                    });
                    setLimits(byGroup);

                    setMeta({ snapshot_at: data.snapshot_at, source: data.source });

                    const mainList = list.filter((g) => g.group_code !== 'NA');

                    setSelected((prev) => {
                        if (prev && mainList.some((g) => g.group_code === prev)) return prev;
                        if (mainList.some((g) => g.group_code === initialGroup)) return initialGroup;
                        if (mainList.length === 0) return null;
                        // Công đoạn đang chờ nhiều hàng nhất là công đoạn đáng xem trước
                        return mainList.reduce((a, b) => (a.stock_dvl >= b.stock_dvl ? a : b)).group_code;
                    });
                })
                .catch((err) => {
                    setError(
                        err.response && err.response.status === 403
                            ? 'Bạn không có quyền xem chức năng này.'
                            : 'Không tải được dữ liệu tồn bán thành phẩm.'
                    );
                })
                .finally(() => setLoading(false));
        },
        [initialGroup]
    );

    useEffect(() => {
        load(false);
    }, [load]);

    useEffect(() => {
        if (!selected) return;

        setDetailLoading(true);
        setExpanded(null);

        axios
            .post('/Schedual/wip_coverage/detail', { group_code: selected })
            .then(({ data }) => setDetails(data.success ? data.details || [] : []))
            .catch(() => setDetails([]))
            .finally(() => setDetailLoading(false));
    }, [selected]);

    // Ba công đoạn đích chính là trọng tâm của trang; lô chưa lần ra được công
    // đoạn sau chỉ hiện như một dòng ghi chú, không đứng ngang hàng với chúng
    const groups = useMemo(() => allGroups.filter((g) => g.group_code !== 'NA'), [allGroups]);
    const naGroup = useMemo(() => allGroups.find((g) => g.group_code === 'NA') || null, [allGroups]);

    const current = groups.find((g) => g.group_code === selected) || null;

    /**
     * Gộp chuỗi của mọi công đoạn về CÙNG một trục ngày, để nhìn được tồn
     * đang chờ cả ba công đoạn tại từng mốc 06:00 mà không phải bấm qua lại.
     */
    const byDate = useMemo(() => {
        const map = new Map();

        groups.forEach((g) => {
            (g.daily_series || []).forEach((p) => {
                if (!map.has(p.date)) map.set(p.date, { date: p.date, label: formatDateShort(p.date) });
                const row = map.get(p.date);
                const c = g.group_code;
                row[c] = Number(p.stock_dvl) || 0;
                row[`${c}_in`] = Number(p.in_dvl) || 0;
                row[`${c}_out`] = Number(p.out_dvl) || 0;
                row[`${c}_lots`] = p.stock_lots;
            });
        });

        // Nguồn vào Pha chế dùng chung trục ngày với các cột tồn, để đọc được
        // ngay: hôm nào Pha chế đổ vào nhiều thì hôm sau tồn phía sau dâng lên
        supply.forEach((p) => {
            if (!map.has(p.date)) map.set(p.date, { date: p.date, label: formatDateShort(p.date) });
            const row = map.get(p.date);
            row.supply_dvl = Number(p.output_dvl) || 0;
            row.supply_kg = Number(p.output_kg) || 0;
            row.supply_lots = p.lots;
        });

        return Array.from(map.values()).sort((a, b) => a.date.localeCompare(b.date));
    }, [groups, supply]);

    // Tổng tồn chờ sản xuất của cả phân xưởng tại từng mốc, để thấy bức tranh chung
    const totalByDate = useMemo(
        () =>
            byDate.map((row) =>
                groups.reduce((sum, g) => sum + (Number(row[g.group_code]) || 0), 0)
            ),
        [byDate, groups]
    );

    // Ngày tồn thấp nhất và cao nhất của từng công đoạn, để tô dấu trong bảng
    const marks = useMemo(() => {
        const out = {};
        groups.forEach((g) => {
            out[g.group_code] = { low: g.lowest_stock_date, high: g.highest_stock_date };
        });
        return out;
    }, [groups]);

    // Tổng nguồn vào Pha chế trong đúng khoảng đang vẽ
    const supplyTotal = useMemo(() => {
        const dvl = supply.reduce((s, p) => s + (Number(p.output_dvl) || 0), 0);
        const kg = supply.reduce((s, p) => s + (Number(p.output_kg) || 0), 0);
        const lots = supply.reduce((s, p) => s + (Number(p.lots) || 0), 0);
        // Ngày nghỉ không có mẻ nào, chia cho tổng số ngày sẽ ra một con số không
        // giống bất kỳ ngày chạy thật nào, nên bình quân tính trên ngày CÓ mẻ
        const activeDays = supply.filter((p) => (Number(p.output_dvl) || 0) > 0).length;

        return { dvl, kg, lots, activeDays, avgDvl: activeDays > 0 ? dvl / activeDays : 0 };
    }, [supply]);

    // Số ngày vượt giới hạn của từng công đoạn, đếm trên chính khoảng đang vẽ
    const breaches = useMemo(() => {
        const out = {};
        groups.forEach((g) => {
            out[g.group_code] = (g.daily_series || []).filter(
                (p) => limitStateOf(p.stock_dvl, limits[g.group_code]) !== null
            ).length;
        });
        return out;
    }, [groups, limits]);

    if (loading && allGroups.length === 0) {
        return <div style={styles.state}>Đang tải dữ liệu tồn bán thành phẩm…</div>;
    }

    if (error) {
        return <div style={{ ...styles.state, color: '#dc2626' }}>{error}</div>;
    }

    if (groups.length === 0) {
        return (
            <div style={styles.state}>
                Phân xưởng này chưa có tồn bán thành phẩm nào đang chờ Định hình, Bao phim hay Đóng gói.
            </div>
        );
    }

    return (
        <div style={styles.page}>
            <div style={styles.header}>
                <div>
                    <h2 style={styles.h1}>Tồn kho lý thuyết theo công đoạn</h2>
                    <p style={styles.lede}>
                        Lượng bán thành phẩm đang chờ để bước vào Định hình, Bao phim hoặc Đóng gói,
                        tính lại tại 06:00 từng ngày theo lịch lý thuyết đã sắp. Mỗi công đoạn đích là
                        MỘT tổng duy nhất, gộp mọi nguồn đổ vào nó — ví dụ tồn chờ Đóng gói cộng cả
                        hàng đi đủ tuần tự qua Bao phim lẫn hàng bỏ qua Bao phim hoặc Định hình.
                    </p>
                </div>
                <div style={styles.headerRight}>
                    {meta && (
                        <span style={styles.stamp}>
                            {meta.source === 'live' ? 'Tính lúc ' : 'Chốt '}
                            {formatDate(meta.snapshot_at)}
                            {meta.snapshot_at ? ` ${String(meta.snapshot_at).slice(11, 16)}` : ''}
                        </span>
                    )}
                    <button type="button" style={styles.btn} onClick={() => load(true)} disabled={loading}>
                        {loading ? 'Đang tính…' : 'Tính lại theo hiện tại'}
                    </button>
                </div>
            </div>

            {/* ── Phần chính: tồn đang chờ MỖI công đoạn theo từng ngày ── */}
            <div style={styles.box}>
                <div style={styles.boxHead}>
                    <div>
                        <h3 style={styles.h3}>Tồn chờ từng công đoạn theo ngày</h3>
                        <p style={styles.cap}>
                            Mỗi nhóm cột là mức tồn lúc 06:00 của ngày đó. Cột cao dần nghĩa là các công
                            đoạn trước sản xuất nhanh hơn công đoạn này tiêu thụ. Đường xanh là sản lượng
                            Pha chế đổ vào dây chuyền — nguồn cấp duy nhất cho mọi công đoạn sau.
                        </p>
                    </div>
                    <label style={styles.toggle}>
                        <input
                            type="checkbox"
                            checked={showFlows}
                            onChange={(e) => setShowFlows(e.target.checked)}
                        />
                        Hiện cột nhập / xuất
                    </label>
                </div>

                {/* 30 ngày x 3 công đoạn: giữ bề rộng tối thiểu để cột không bị bóp dẹt,
                    màn hẹp thì cuộn ngang trong khung riêng chứ không tràn cả trang. */}
                <div style={styles.chartScroll}>
                  <div style={{ minWidth: Math.max(720, byDate.length * 44), height: 340 }}>
                    <ResponsiveContainer width="100%" height="100%">
                        <ComposedChart data={byDate} margin={{ top: 8, right: 16, left: 4, bottom: 4 }} barGap={2}>
                            <CartesianGrid stroke="#eef2f4" vertical={false} />
                            <XAxis
                                dataKey="label"
                                tick={{ fontSize: 11, fill: '#64748b' }}
                                interval={0}
                                angle={-45}
                                textAnchor="end"
                                height={48}
                            />
                            <YAxis
                                tick={{ fontSize: 11, fill: '#64748b' }}
                                tickFormatter={formatDvl}
                                width={62}
                                label={{
                                    value: 'ĐVL',
                                    angle: -90,
                                    position: 'insideLeft',
                                    fontSize: 11,
                                    fill: '#94a3b8',
                                }}
                            />
                            <Tooltip
                                cursor={{ fill: '#0f172a', fillOpacity: 0.04 }}
                                content={({ active, payload, label }) => {
                                    if (!active || !payload || payload.length === 0) return null;
                                    const d = payload[0].payload;
                                    const total = groups.reduce(
                                        (sum, g) => sum + (Number(d[g.group_code]) || 0),
                                        0
                                    );
                                    return (
                                        <div style={styles.tooltip}>
                                            <div style={{ fontWeight: 700, marginBottom: 5 }}>
                                                {label} lúc 06:00
                                            </div>
                                            {groups.map((g) => {
                                                const c = g.group_code;
                                                if (d[c] === undefined) return null;
                                                return (
                                                    <div key={c} style={styles.tipRow}>
                                                        <span
                                                            style={{
                                                                ...styles.tipDot,
                                                                background: colorOfGroup(c),
                                                            }}
                                                        />
                                                        <span style={styles.tipName}>{groupLabel(g)}</span>
                                                        <b>{formatFull(d[c])}</b>
                                                        <span style={styles.tipMuted}>
                                                            ({d[`${c}_lots`]} lô · +{formatDvl(d[`${c}_in`])} / −
                                                            {formatDvl(d[`${c}_out`])})
                                                        </span>
                                                    </div>
                                                );
                                            })}
                                            <div style={styles.tipTotal}>
                                                Tổng cả phân xưởng <b>{formatFull(total)}</b> ĐVL
                                            </div>
                                            <div style={{ ...styles.tipRow, marginTop: 4 }}>
                                                <span
                                                    style={{ ...styles.tipDot, background: SUPPLY_COLOR }}
                                                />
                                                <span style={styles.tipName}>Pha chế nhập vào</span>
                                                <b>{formatFull(d.supply_dvl)}</b>
                                                <span style={styles.tipMuted}>
                                                    ({formatKg(d.supply_kg)} Kg · {d.supply_lots || 0} lô)
                                                </span>
                                            </div>
                                        </div>
                                    );
                                }}
                            />
                            <Legend wrapperStyle={{ fontSize: 12 }} />
                            {groups.map((g) => (
                                <Bar
                                    key={g.group_code}
                                    dataKey={g.group_code}
                                    name={groupLabel(g)}
                                    fill={colorOfGroup(g.group_code)}
                                    radius={[4, 4, 0, 0]}
                                    maxBarSize={26}
                                />
                            ))}
                            {/* Vẽ dạng đường chứ không phải cột: đây là LƯỢNG CHẢY VÀO
                                trong ngày, không phải mức tồn đứng như ba cột kia. */}
                            <Line
                                type="monotone"
                                dataKey="supply_dvl"
                                name="Pha chế nhập vào"
                                stroke={SUPPLY_COLOR}
                                strokeWidth={2}
                                dot={{ r: 2.5 }}
                                activeDot={{ r: 4 }}
                            />
                        </ComposedChart>
                    </ResponsiveContainer>
                  </div>
                </div>

                {/* Thống kê gọn của từng công đoạn trong đúng khoảng đang vẽ */}
                <div style={styles.statList}>
                    {groups.map((g) => (
                        <div key={g.group_code} style={styles.statRow}>
                            <span style={{ ...styles.statDot, background: colorOfGroup(g.group_code) }} />
                            <span style={styles.statName}>{groupLabel(g)}</span>
                            <span style={styles.statCell}>
                                hiện tại <b>{formatFull(g.stock_dvl)}</b> ĐVL · {g.stock_lots} lô
                            </span>
                            <span style={styles.statCell}>
                                thấp nhất <b>{formatDvl(g.lowest_stock_dvl)}</b>
                                {g.lowest_stock_date ? ` (${formatDateShort(g.lowest_stock_date)})` : ''}
                            </span>
                            <span style={styles.statCell}>
                                cao nhất <b>{formatDvl(g.highest_stock_dvl)}</b>
                                {g.highest_stock_date ? ` (${formatDateShort(g.highest_stock_date)})` : ''}
                            </span>
                            <span style={styles.statCell}>
                                trung bình <b>{formatDvl(g.avg_stock_dvl)}</b>
                            </span>
                            <span style={styles.statCell}>
                                cả kỳ +{formatDvl(g.in_total_dvl)} / −{formatDvl(g.out_total_dvl)}
                            </span>
                            {(limits[g.group_code] &&
                                (limits[g.group_code].min_stock_dvl !== null ||
                                    limits[g.group_code].max_stock_dvl !== null)) && (
                                <span style={styles.statLimit}>
                                    giới hạn{' '}
                                    {limits[g.group_code].min_stock_dvl !== null
                                        ? formatDvl(limits[g.group_code].min_stock_dvl)
                                        : '—'}
                                    {' – '}
                                    {limits[g.group_code].max_stock_dvl !== null
                                        ? formatDvl(limits[g.group_code].max_stock_dvl)
                                        : '—'}
                                    {breaches[g.group_code] > 0 && (
                                        <b style={{ marginLeft: 6, color: '#b45309' }}>
                                            {breaches[g.group_code]} ngày vượt
                                        </b>
                                    )}
                                </span>
                            )}
                        </div>
                    ))}

                    {/* Nguồn vào: đặt ngay dưới ba công đoạn để so được lượng đổ vào
                        với lượng đang đọng lại */}
                    <div style={{ ...styles.statRow, ...styles.statSupplyRow }}>
                        <span style={{ ...styles.statDot, background: SUPPLY_COLOR }} />
                        <span style={styles.statName}>Pha chế nhập vào</span>
                        <span style={styles.statCell}>
                            cả kỳ <b>{formatFull(supplyTotal.dvl)}</b> ĐVL ·{' '}
                            <b>{formatKg(supplyTotal.kg)}</b> Kg · {supplyTotal.lots} lô
                        </span>
                        <span style={styles.statCell}>
                            bình quân <b>{formatDvl(supplyTotal.avgDvl)}</b> / ngày
                        </span>
                        <span style={styles.statCell}>
                            {supplyTotal.activeDays}/{supply.length} ngày có mẻ
                        </span>
                    </div>
                    {naGroup && naGroup.stock_dvl > 0 && (
                        <div style={styles.naNote}>
                            {formatFull(naGroup.stock_dvl)} ĐVL ({naGroup.stock_lots} lô) chưa lần ra được
                            công đoạn sau qua dữ liệu kế hoạch — chưa cộng vào ba công đoạn ở trên.
                        </div>
                    )}
                </div>

                {/* Bảng số: cùng dữ liệu với biểu đồ, đọc được con số chính xác từng ngày */}
                <div style={styles.tableWrap}>
                    <table style={styles.dayTable}>
                        <thead>
                            <tr>
                                <th style={{ ...styles.th, textAlign: 'left' }} rowSpan={2}>
                                    Ngày
                                </th>
                                <th
                                    style={{
                                        ...styles.th,
                                        borderLeft: '1px solid #e2e8f0',
                                        color: SUPPLY_COLOR,
                                    }}
                                    rowSpan={2}
                                    title="Sản lượng Pha chế đổ vào dây chuyền trong ngày — nguồn cấp bán thành phẩm cho mọi công đoạn sau"
                                >
                                    Pha chế nhập vào
                                    <div style={styles.thUnit}>ĐVL · Kg</div>
                                </th>
                                {groups.map((g) => (
                                    <th
                                        key={g.group_code}
                                        colSpan={showFlows ? 3 : 1}
                                        style={{
                                            ...styles.th,
                                            borderLeft: '1px solid #e2e8f0',
                                            color: colorOfGroup(g.group_code),
                                        }}
                                        title={groupLabel(g)}
                                    >
                                        {groupLabel(g)}
                                    </th>
                                ))}
                                <th style={{ ...styles.th, borderLeft: '1px solid #cbd5e1' }} rowSpan={2}>
                                    Tổng
                                </th>
                            </tr>
                            <tr>
                                {groups.map((g) => (
                                    <React.Fragment key={g.group_code}>
                                        <th style={{ ...styles.th2, borderLeft: '1px solid #e2e8f0' }}>Tồn</th>
                                        {showFlows && <th style={styles.th2}>Nhập</th>}
                                        {showFlows && <th style={styles.th2}>Xuất</th>}
                                    </React.Fragment>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {byDate.map((row, idx) => (
                                <tr key={row.date} style={idx === 0 ? styles.todayRow : undefined}>
                                    <td style={{ ...styles.td, textAlign: 'left', whiteSpace: 'nowrap' }}>
                                        {formatDate(row.date)}
                                        {idx === 0 && <span style={styles.todayTag}>hôm nay</span>}
                                    </td>
                                    <td
                                        style={{
                                            ...styles.td,
                                            borderLeft: '1px solid #e2e8f0',
                                            color: row.supply_dvl > 0 ? SUPPLY_COLOR : '#cbd5e1',
                                            fontWeight: row.supply_dvl > 0 ? 600 : 400,
                                        }}
                                        title={
                                            row.supply_dvl > 0
                                                ? `${formatFull(row.supply_dvl)} ĐVL · ${formatKg(
                                                      row.supply_kg
                                                  )} Kg · ${row.supply_lots} lô`
                                                : 'Không có mẻ Pha chế nào trong ngày'
                                        }
                                    >
                                        {row.supply_dvl > 0 ? (
                                            <>
                                                {formatFull(row.supply_dvl)}
                                                <span style={styles.tdKg}>{formatKg(row.supply_kg)} Kg</span>
                                            </>
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                    {groups.map((g) => {
                                        const c = g.group_code;
                                        const mark = marks[c] || {};
                                        const isLow = mark.low === row.date;
                                        const isHigh = mark.high === row.date;
                                        // Vượt giới hạn tô bằng NỀN, còn đáy/đỉnh của kỳ tô
                                        // bằng CHỮ, để hai thông tin không tranh nhau một ô
                                        const breach = limitStateOf(row[c], limits[c]);
                                        return (
                                            <React.Fragment key={c}>
                                                <td
                                                    style={{
                                                        ...styles.td,
                                                        borderLeft: '1px solid #e2e8f0',
                                                        fontWeight: isLow || isHigh ? 700 : 500,
                                                        color: isLow ? '#dc2626' : isHigh ? '#0d9488' : '#0f172a',
                                                        background:
                                                            breach === 'low'
                                                                ? '#fee2e2'
                                                                : breach === 'high'
                                                                ? '#fef3c7'
                                                                : undefined,
                                                    }}
                                                    title={
                                                        breach === 'low'
                                                            ? `Dưới giới hạn dưới (${formatFull(
                                                                  limits[c].min_stock_dvl
                                                              )} ĐVL) — thiếu hàng cho công đoạn sau`
                                                            : breach === 'high'
                                                            ? `Trên giới hạn trên (${formatFull(
                                                                  limits[c].max_stock_dvl
                                                              )} ĐVL) — ứ hàng, công đoạn sau không tiêu thụ kịp`
                                                            : isLow
                                                            ? 'Mức tồn thấp nhất trong kỳ'
                                                            : isHigh
                                                            ? 'Mức tồn cao nhất trong kỳ'
                                                            : undefined
                                                    }
                                                >
                                                    {formatFull(row[c])}
                                                    {isLow && <span style={styles.lowTag}>thấp nhất</span>}
                                                    {isHigh && !isLow && (
                                                        <span style={styles.highTag}>cao nhất</span>
                                                    )}
                                                </td>
                                                {showFlows && (
                                                    <td style={{ ...styles.td, color: '#0d9488' }}>
                                                        {row[`${c}_in`] > 0 ? `+${formatDvl(row[`${c}_in`])}` : '—'}
                                                    </td>
                                                )}
                                                {showFlows && (
                                                    <td style={{ ...styles.td, color: '#b45309' }}>
                                                        {row[`${c}_out`] > 0 ? `−${formatDvl(row[`${c}_out`])}` : '—'}
                                                    </td>
                                                )}
                                            </React.Fragment>
                                        );
                                    })}
                                    <td
                                        style={{
                                            ...styles.td,
                                            borderLeft: '1px solid #cbd5e1',
                                            fontWeight: 700,
                                        }}
                                    >
                                        {formatFull(totalByDate[idx])}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {Object.keys(limits).some(
                    (k) =>
                        limits[k] &&
                        (limits[k].min_stock_dvl !== null || limits[k].max_stock_dvl !== null)
                ) ? (
                    <div style={styles.tableNote}>
                        <span style={{ ...styles.noteChip, background: '#fee2e2', color: '#dc2626' }}>
                            dưới giới hạn
                        </span>
                        thiếu hàng cho công đoạn sau
                        <span
                            style={{
                                ...styles.noteChip,
                                background: '#fef3c7',
                                color: '#b45309',
                                marginLeft: 12,
                            }}
                        >
                            trên giới hạn
                        </span>
                        ứ hàng, công đoạn sau không tiêu thụ kịp. Cài đặt ở trang Chính sách sản lượng.
                    </div>
                ) : (
                    <div style={styles.tableNote}>
                        Chưa cài giới hạn tồn cho công đoạn nào. Vào trang Chính sách sản lượng để đặt
                        giới hạn trên/dưới, ngày vượt ngưỡng sẽ được tô màu ở bảng trên.
                    </div>
                )}
            </div>

            {current && (
                <div style={styles.box2}>
                    <div style={styles.boxHead}>
                        <div>
                            <h3 style={styles.h3}>Chi tiết theo mã bán thành phẩm</h3>
                            <p style={styles.cap}>
                                Mã đang giữ nhiều hàng nhất xếp lên đầu. Bấm mũi tên để xem từng lô đang
                                nằm chờ trong kho.
                            </p>
                        </div>
                        {/* Chọn công đoạn cần xem, thay cho hàng thẻ tóm tắt trước đây */}
                        <div style={styles.tabs}>
                            {groups.map((g) => {
                                const active = g.group_code === selected;
                                const hue = colorOfGroup(g.group_code);
                                return (
                                    <button
                                        type="button"
                                        key={g.group_code}
                                        onClick={() => setSelected(g.group_code)}
                                        style={{
                                            ...styles.tab,
                                            color: active ? '#fff' : hue,
                                            background: active ? hue : '#fff',
                                            borderColor: active ? hue : '#e2e8f0',
                                        }}
                                        title={`${groupLabel(g)} — ${formatFull(g.stock_dvl)} ĐVL`}
                                    >
                                        {groupLabel(g)}
                                        <span
                                            style={{
                                                ...styles.tabQty,
                                                color: active ? 'rgba(255,255,255,.85)' : '#94a3b8',
                                            }}
                                        >
                                            {formatDvl(g.stock_dvl)}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    <DataTable
                        value={details}
                        loading={detailLoading}
                        size="small"
                        stripedRows
                        paginator
                        rows={10}
                        emptyMessage="Không có mã bán thành phẩm nào đang tồn."
                        expandedRows={expanded}
                        onRowToggle={(e) => setExpanded(e.data)}
                        dataKey="intermediate_code"
                        rowExpansionTemplate={(row) => (
                            <div style={styles.expansion}>
                                <table style={styles.innerTable}>
                                    <thead>
                                        <tr>
                                            <th style={styles.thSmall}>Số lô</th>
                                            <th style={styles.thSmall}>Công đoạn</th>
                                            <th style={styles.thSmall}>Bắt đầu</th>
                                            <th style={{ ...styles.thSmall, textAlign: 'right' }}>
                                                Lượng (ĐVL)
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(row.batches || []).map((b) => (
                                            <tr key={b.plan_master_id}>
                                                <td style={styles.tdSmall}>{b.batch || '—'}</td>
                                                <td style={styles.tdSmall}>{b.stage_code}</td>
                                                <td style={styles.tdSmall}>{formatDate(b.start)}</td>
                                                <td style={{ ...styles.tdSmall, textAlign: 'right' }}>
                                                    {formatFull(b.qty_dvl)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    >
                        <Column expander style={{ width: 42 }} />
                        <Column field="intermediate_code" header="Mã BTP" style={{ width: 130 }} />
                        <Column field="product_name" header="Tên sản phẩm" />
                        <Column
                            header="Tồn"
                            style={{ width: 150, textAlign: 'right' }}
                            body={(row) => (
                                <span style={{ fontVariantNumeric: 'tabular-nums' }}>
                                    {formatFull(row.stock_dvl)}{' '}
                                    <span style={{ color: '#94a3b8', fontSize: 11 }}>
                                        {row.unit || 'ĐVL'}
                                    </span>
                                </span>
                            )}
                        />
                        <Column field="stock_lots" header="Số lô" style={{ width: 70, textAlign: 'right' }} />
                        <Column
                            header="Tỉ trọng"
                            style={{ width: 110, textAlign: 'right' }}
                            body={(row) => (
                                <span style={styles.sharePill}>
                                    {row.share_pct === null || row.share_pct === undefined
                                        ? '—'
                                        : `${Number(row.share_pct).toLocaleString('vi-VN', {
                                              maximumFractionDigits: 1,
                                          })}%`}
                                </span>
                            )}
                        />
                        <Column
                            header="Xuất cuối"
                            style={{ width: 110 }}
                            body={(row) =>
                                row.last_out_date ? (
                                    formatDate(row.last_out_date)
                                ) : (
                                    <span
                                        style={{ color: '#b45309', fontSize: 11.5 }}
                                        title="Chưa sắp lịch cho công đoạn sau trong khoảng dự báo"
                                    >
                                        chưa sắp lịch
                                    </span>
                                )
                            }
                        />
                    </DataTable>
                </div>
            )}
        </div>
    );
};

const styles = {
    // Vỏ layout của app vốn chỉ chứa các trang React vừa khít một màn hình
    // (Lịch sản xuất, Phân công) nên không xử lý trường hợp nội dung dài hơn.
    page: {
        padding: '16px 18px 40px',
        fontFamily: 'inherit',
        color: '#0f172a',
        height: 'calc(100vh - 125px)',
        overflowY: 'auto',
        overflowX: 'auto',
    },
    state: { padding: 40, textAlign: 'center', color: '#64748b' },
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        gap: 16,
        flexWrap: 'wrap',
        marginBottom: 14,
    },
    headerRight: { display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' },
    h1: { fontSize: 20, fontWeight: 700, margin: 0 },
    lede: { margin: '4px 0 0', color: '#64748b', fontSize: 13.5, maxWidth: '78ch' },
    stamp: { fontSize: 12.5, color: '#64748b', fontVariantNumeric: 'tabular-nums' },
    btn: {
        fontSize: 12.5,
        fontWeight: 600,
        color: '#fff',
        background: '#0e7490',
        border: 'none',
        borderRadius: 5,
        padding: '6px 12px',
        cursor: 'pointer',
    },

    box: { border: '1px solid #e2e8f0', borderRadius: 8, background: '#fff', padding: 14 },
    box2: {
        border: '1px solid #e2e8f0',
        borderRadius: 8,
        background: '#fff',
        padding: 14,
        marginTop: 14,
    },
    boxHead: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        gap: 16,
        flexWrap: 'wrap',
    },
    toggle: {
        display: 'flex',
        alignItems: 'center',
        gap: 6,
        fontSize: 12.5,
        color: '#475569',
        cursor: 'pointer',
        whiteSpace: 'nowrap',
    },
    h3: {
        margin: 0,
        fontSize: 11.5,
        fontWeight: 700,
        letterSpacing: '.09em',
        textTransform: 'uppercase',
        color: '#334155',
    },
    cap: { margin: '3px 0 12px', fontSize: 12.5, color: '#64748b' },

    chartScroll: { width: '100%', overflowX: 'auto', overflowY: 'hidden' },

    statList: {
        marginTop: 12,
        paddingTop: 10,
        borderTop: '1px dashed #e2e8f0',
        display: 'flex',
        flexDirection: 'column',
        gap: 5,
    },
    statRow: {
        display: 'flex',
        alignItems: 'center',
        gap: 12,
        flexWrap: 'wrap',
        fontSize: 12,
        color: '#64748b',
        fontVariantNumeric: 'tabular-nums',
    },
    statDot: { width: 9, height: 9, borderRadius: 2, flex: 'none' },
    statName: { minWidth: 120, fontWeight: 600, color: '#334155' },
    statCell: { whiteSpace: 'nowrap' },
    statLimit: {
        whiteSpace: 'nowrap',
        color: '#475569',
        background: '#f1f5f9',
        borderRadius: 3,
        padding: '1px 7px',
    },
    statSupplyRow: {
        marginTop: 3,
        paddingTop: 7,
        borderTop: '1px dashed #e2e8f0',
    },
    naNote: {
        marginTop: 4,
        fontSize: 11.5,
        color: '#b45309',
        background: '#fef3c7',
        borderRadius: 5,
        padding: '5px 9px',
    },

    tabs: { display: 'flex', gap: 6, flexWrap: 'wrap' },
    tab: {
        display: 'flex',
        alignItems: 'baseline',
        gap: 6,
        border: '1px solid #e2e8f0',
        borderRadius: 999,
        padding: '4px 11px',
        fontSize: 12,
        fontWeight: 700,
        cursor: 'pointer',
        font: 'inherit',
        fontVariantNumeric: 'tabular-nums',
        whiteSpace: 'nowrap',
    },
    tabQty: { fontSize: 11, fontWeight: 500 },

    tableWrap: {
        marginTop: 14,
        maxHeight: 360,
        overflow: 'auto',
        border: '1px solid #e2e8f0',
        borderRadius: 6,
    },
    dayTable: {
        width: '100%',
        borderCollapse: 'separate',
        borderSpacing: 0,
        fontSize: 12.5,
        fontVariantNumeric: 'tabular-nums',
    },
    th: {
        position: 'sticky',
        top: 0,
        zIndex: 2,
        background: '#f8fafc',
        padding: '7px 10px',
        textAlign: 'right',
        fontSize: 11,
        fontWeight: 700,
        letterSpacing: '.04em',
        borderBottom: '1px solid #e2e8f0',
        whiteSpace: 'nowrap',
    },
    th2: {
        position: 'sticky',
        top: 28,
        zIndex: 2,
        background: '#f8fafc',
        padding: '4px 10px',
        textAlign: 'right',
        fontSize: 10.5,
        fontWeight: 600,
        color: '#64748b',
        borderBottom: '1px solid #e2e8f0',
        whiteSpace: 'nowrap',
    },
    thUnit: {
        fontSize: 9.5,
        fontWeight: 500,
        color: '#94a3b8',
        letterSpacing: 0,
        marginTop: 1,
    },
    td: {
        padding: '6px 10px',
        textAlign: 'right',
        borderBottom: '1px solid #f1f5f9',
        whiteSpace: 'nowrap',
    },
    tdKg: { marginLeft: 6, fontSize: 10.5, fontWeight: 400, color: '#94a3b8' },
    todayRow: { background: '#f0fdfa' },
    todayTag: {
        marginLeft: 6,
        fontSize: 9.5,
        fontWeight: 700,
        textTransform: 'uppercase',
        color: '#0d9488',
        background: '#ccfbf1',
        borderRadius: 3,
        padding: '1px 5px',
    },
    lowTag: {
        marginLeft: 5,
        fontSize: 9.5,
        fontWeight: 700,
        color: '#dc2626',
        background: '#fee2e2',
        borderRadius: 3,
        padding: '1px 5px',
    },
    highTag: {
        marginLeft: 5,
        fontSize: 9.5,
        fontWeight: 700,
        color: '#0d9488',
        background: '#ccfbf1',
        borderRadius: 3,
        padding: '1px 5px',
    },

    tableNote: {
        marginTop: 8,
        fontSize: 11.5,
        color: '#64748b',
        display: 'flex',
        alignItems: 'center',
        gap: 5,
        flexWrap: 'wrap',
    },
    noteChip: {
        borderRadius: 3,
        padding: '1px 7px',
        fontSize: 10.5,
        fontWeight: 700,
    },

    tooltip: {
        background: '#fff',
        border: '1px solid #e2e8f0',
        borderRadius: 6,
        padding: '9px 11px',
        fontSize: 12,
        boxShadow: '0 2px 10px rgba(15,23,42,.14)',
        fontVariantNumeric: 'tabular-nums',
    },
    tipRow: { display: 'flex', alignItems: 'center', gap: 6, marginTop: 2 },
    tipDot: { width: 9, height: 9, borderRadius: 2, flex: 'none' },
    tipName: { minWidth: 100, color: '#475569' },
    tipMuted: { color: '#94a3b8', fontSize: 11 },
    tipTotal: {
        marginTop: 6,
        paddingTop: 5,
        borderTop: '1px solid #f1f5f9',
        color: '#334155',
        fontSize: 11.5,
    },

    sharePill: {
        background: '#f1f5f9',
        color: '#334155',
        borderRadius: 4,
        padding: '2px 7px',
        fontSize: 12,
        fontWeight: 600,
        fontVariantNumeric: 'tabular-nums',
    },

    expansion: { padding: '8px 12px', background: '#f8fafc' },
    innerTable: { width: '100%', borderCollapse: 'collapse', fontSize: 12 },
    thSmall: {
        textAlign: 'left',
        padding: '4px 8px',
        borderBottom: '1px solid #e2e8f0',
        color: '#64748b',
        fontWeight: 600,
    },
    tdSmall: {
        padding: '4px 8px',
        borderBottom: '1px solid #eef2f4',
        fontVariantNumeric: 'tabular-nums',
    },
};

export default WipCoverage;
