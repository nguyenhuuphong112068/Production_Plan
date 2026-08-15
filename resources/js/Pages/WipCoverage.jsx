import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import {
    BarChart,
    Bar,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ReferenceLine,
    ResponsiveContainer,
} from 'recharts';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';

import {
    statusOf,
    colorOfGroup,
    formatDvl,
    formatFull,
    coverLabel,
    coverSuffix,
    capacityLabel,
    flowLabel,
    formatDate,
    formatDateShort,
} from '../Components/wipCoverageShared';

const WipCoverage = () => {
    const [groups, setGroups] = useState([]);
    const [thresholds, setThresholds] = useState([]);
    const [meta, setMeta] = useState(null);
    const [selected, setSelected] = useState(null);
    const [details, setDetails] = useState([]);
    const [history, setHistory] = useState([]);
    const [loading, setLoading] = useState(true);
    const [detailLoading, setDetailLoading] = useState(false);
    const [error, setError] = useState(null);
    const [expanded, setExpanded] = useState(null);
    const [showFlows, setShowFlows] = useState(false);

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
                    setGroups(list);
                    setThresholds(data.thresholds || []);
                    setMeta({ snapshot_at: data.snapshot_at, source: data.source });

                    setSelected((prev) => {
                        if (prev && list.some((g) => g.stage_group_code === prev)) return prev;
                        const wanted = list.find((g) => g.stage_group_code === initialGroup);
                        if (wanted) return wanted.stage_group_code;
                        const risky = list.filter((g) => g.days_of_cover !== null);
                        if (risky.length > 0) {
                            return risky.reduce((a, b) => (a.days_of_cover <= b.days_of_cover ? a : b))
                                .stage_group_code;
                        }
                        return list.length > 0 ? list[0].stage_group_code : null;
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
            .post('/Schedual/wip_coverage/detail', { stage_group_code: selected })
            .then(({ data }) => setDetails(data.success ? data.details || [] : []))
            .catch(() => setDetails([]))
            .finally(() => setDetailLoading(false));

        axios
            .post('/Schedual/wip_coverage/history', { stage_group_code: selected })
            .then(({ data }) => setHistory(data.success ? data.history || [] : []))
            .catch(() => setHistory([]));
    }, [selected]);

    const current = groups.find((g) => g.stage_group_code === selected) || null;

    /**
     * Gộp chuỗi của mọi nhóm về CÙNG một trục ngày, để nhìn được tồn của tất cả
     * công đoạn tại từng mốc 06:00 mà không phải bấm qua lại từng thẻ.
     */
    const byDate = useMemo(() => {
        const map = new Map();

        groups.forEach((g) => {
            (g.daily_series || []).forEach((p) => {
                if (!map.has(p.date)) map.set(p.date, { date: p.date, label: formatDateShort(p.date) });
                const row = map.get(p.date);
                const c = g.stage_group_code;
                row[c] = Number(p.stock_dvl) || 0;
                row[`${c}_in`] = Number(p.in_dvl) || 0;
                row[`${c}_out`] = Number(p.out_dvl) || 0;
                row[`${c}_lots`] = p.stock_lots;
                row[`${c}_cover`] = p.days_of_cover;
            });
        });

        return Array.from(map.values()).sort((a, b) => a.date.localeCompare(b.date));
    }, [groups]);

    /**
     * Số ngày đáp ứng có đơn vị khác hẳn lượng tồn nên phải vẽ ở biểu đồ riêng,
     * không chồng lên cùng một trục.
     */
    const coverByDate = useMemo(
        () =>
            byDate.map((r) => {
                const o = { date: r.date, label: r.label };
                groups.forEach((g) => {
                    const v = r[`${g.stage_group_code}_cover`];
                    o[g.stage_group_code] = v === null || v === undefined ? null : v;
                });
                return o;
            }),
        [byDate, groups]
    );

    // Chỉ vẽ được một vạch ngưỡng khi mọi nhóm đang hiển thị cùng chung một mức,
    // ngược lại vạch sẽ gây hiểu nhầm là áp cho cả ba
    const sharedWarnDays = useMemo(() => {
        if (groups.length === 0 || thresholds.length === 0) return null;

        const values = groups.map((g) => {
            const t = thresholds.find((x) => x.stage_group_code === g.stage_group_code);
            return t && t.warn_days !== null && t.warn_days !== undefined ? Number(t.warn_days) : null;
        });

        if (values.some((v) => v === null)) return null;
        return values.every((v) => v === values[0]) ? values[0] : null;
    }, [groups, thresholds]);

    // Ngày tồn thấp nhất của từng nhóm, để tô dấu trong bảng
    const lowestDates = useMemo(() => {
        const out = {};
        groups.forEach((g) => {
            if (g.lowest_stock_date) out[g.stage_group_code] = g.lowest_stock_date;
        });
        return out;
    }, [groups]);

    if (loading && groups.length === 0) {
        return <div style={styles.state}>Đang tải dữ liệu tồn bán thành phẩm…</div>;
    }

    if (error) {
        return <div style={{ ...styles.state, color: '#dc2626' }}>{error}</div>;
    }

    if (groups.length === 0) {
        return (
            <div style={styles.state}>
                Phân xưởng này chưa có công đoạn nào sinh ra tồn bán thành phẩm.
            </div>
        );
    }

    return (
        <div style={styles.page}>
            <div style={styles.header}>
                <div>
                    <h2 style={styles.h1}>Tồn kho lý thuyết theo công đoạn</h2>
                    <p style={styles.lede}>
                        Lượng bán thành phẩm chờ giữa hai công đoạn, tính lại tại 06:00 từng ngày theo
                        lịch lý thuyết đã sắp. Số ngày đáp ứng tính theo định mức giờ máy và nhịp chạy
                        của công đoạn sau, nên không phụ thuộc việc lịch đã sắp tới đâu.
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

            {/* Tóm tắt hiện trạng, bấm để chọn nhóm xem chi tiết bên dưới */}
            <div style={styles.cards}>
                {groups.map((g) => {
                    const st = statusOf(g.status);
                    const active = g.stage_group_code === selected;
                    const hue = colorOfGroup(g.stage_group_code);

                    return (
                        <button
                            type="button"
                            key={g.stage_group_code}
                            onClick={() => setSelected(g.stage_group_code)}
                            style={{
                                ...styles.card,
                                borderColor: active ? hue : '#e2e8f0',
                                boxShadow: active ? `0 0 0 2px ${hue}22` : 'none',
                            }}
                        >
                            <span style={{ ...styles.stripe, background: hue }} />
                            <div style={styles.flow}>{flowLabel(g)}</div>

                            <div style={styles.numRow}>
                                <span style={{ ...styles.num, color: st.color }}>{coverLabel(g)}</span>
                                <span style={styles.unit}>{coverSuffix(g)}</span>
                            </div>

                            <div style={{ ...styles.pill, background: st.bg, color: st.color }}>{st.label}</div>

                            <div style={styles.sub}>
                                <span>{formatDvl(g.stock_dvl)} ĐVL</span>
                                <span style={styles.dot}>|</span>
                                <span>{g.stock_lots} lô</span>
                                <span style={styles.dot}>|</span>
                                <span>
                                    {Number(g.load_hours || 0).toLocaleString('vi-VN', {
                                        maximumFractionDigits: 0,
                                    })}{' '}
                                    giờ máy
                                </span>
                            </div>

                            {/* Cách ra con số: giờ máy chia cho nhịp chạy công đoạn sau */}
                            {capacityLabel(g.capacity_basis) && (
                                <div style={styles.basis}>{capacityLabel(g.capacity_basis)}</div>
                            )}
                        </button>
                    );
                })}
            </div>

            <p style={styles.method}>
                Số ngày đáp ứng = giờ máy mà lượng tồn chiếm ở công đoạn sau theo định mức
                (<code style={styles.code}>quota.m_time</code>) chia cho nhịp chạy thực tế của công đoạn
                đó, đo trên 90 ngày gần nhất. Công đoạn được nhiều kho cùng nuôi thì nhịp được chia
                theo tỉ lệ giờ máy mỗi kho gửi tới.
            </p>

            {/* ── Phần chính: tồn của MỌI công đoạn theo từng ngày ── */}
            <div style={styles.box}>
                <div style={styles.boxHead}>
                    <div>
                        <h3 style={styles.h3}>Tồn từng công đoạn theo ngày</h3>
                        <p style={styles.cap}>
                            Mỗi nhóm cột là mức tồn lúc 06:00 của ngày đó. Cột cao dần nghĩa là công đoạn
                            trước sản xuất nhanh hơn công đoạn sau tiêu thụ.
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

                {/* 30 ngày x 3 cột: giữ bề rộng tối thiểu để cột không bị bóp dẹt,
                    màn hẹp thì cuộn ngang trong khung riêng chứ không tràn cả trang. */}
                <div style={styles.chartScroll}>
                  <div style={{ minWidth: Math.max(720, byDate.length * 44), height: 330 }}>
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={byDate} margin={{ top: 8, right: 16, left: 4, bottom: 4 }} barGap={2}>
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
                                    return (
                                        <div style={styles.tooltip}>
                                            <div style={{ fontWeight: 700, marginBottom: 5 }}>
                                                {label} lúc 06:00
                                            </div>
                                            {groups.map((g) => {
                                                const c = g.stage_group_code;
                                                if (d[c] === undefined) return null;
                                                const cover = d[`${c}_cover`];
                                                return (
                                                    <div key={c} style={styles.tipRow}>
                                                        <span
                                                            style={{
                                                                ...styles.tipDot,
                                                                background: colorOfGroup(c),
                                                            }}
                                                        />
                                                        <span style={styles.tipName}>{g.stage_group_name}</span>
                                                        <b>{formatFull(d[c])}</b>
                                                        <span style={styles.tipMuted}>
                                                            ({d[`${c}_lots`]} lô · +{formatDvl(d[`${c}_in`])} / −
                                                            {formatDvl(d[`${c}_out`])})
                                                        </span>
                                                        <span style={styles.tipCover}>
                                                            {cover === null || cover === undefined
                                                                ? 'chưa quy ra ngày được'
                                                                : `đáp ứng ${cover} ngày`}
                                                        </span>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    );
                                }}
                            />
                            <Legend wrapperStyle={{ fontSize: 12 }} />
                            {groups.map((g) => (
                                <Bar
                                    key={g.stage_group_code}
                                    dataKey={g.stage_group_code}
                                    name={g.stage_group_name}
                                    fill={colorOfGroup(g.stage_group_code)}
                                    radius={[4, 4, 0, 0]}
                                    maxBarSize={18}
                                />
                            ))}
                        </BarChart>
                    </ResponsiveContainer>
                  </div>
                </div>

                {/* Biểu đồ thứ hai, cùng trục ngày: tồn của mỗi công đoạn tại mốc đó
                    còn nuôi được công đoạn sau bao nhiêu ngày nữa. Tách riêng vì đơn
                    vị là ngày, không cùng thang với ĐVL ở biểu đồ trên. */}
                <div style={styles.subHead}>
                    <h4 style={styles.h4}>Số ngày còn đáp ứng được</h4>
                    <span style={styles.subCap}>
                        Tính từ mốc 06:00 của ngày đó, giả định công đoạn trước ngừng cấp hàng và công
                        đoạn sau chạy đúng nhịp thường ngày.
                    </span>
                </div>

                <div style={styles.chartScroll}>
                  <div style={{ minWidth: Math.max(720, byDate.length * 44), height: 210 }}>
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={coverByDate} margin={{ top: 8, right: 16, left: 4, bottom: 4 }} barGap={2}>
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
                                width={62}
                                allowDecimals={false}
                                label={{
                                    value: 'ngày',
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
                                    return (
                                        <div style={styles.tooltip}>
                                            <div style={{ fontWeight: 700, marginBottom: 5 }}>
                                                {label} lúc 06:00
                                            </div>
                                            {groups.map((g) => {
                                                const c = g.stage_group_code;
                                                if (d[c] === undefined) return null;
                                                return (
                                                    <div key={c} style={styles.tipRow}>
                                                        <span
                                                            style={{
                                                                ...styles.tipDot,
                                                                background: colorOfGroup(c),
                                                            }}
                                                        />
                                                        <span style={styles.tipName}>
                                                            {g.stage_group_name}
                                                        </span>
                                                        <b>{d[c] === null ? '—' : `${d[c]} ngày`}</b>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    );
                                }}
                            />
                            <Legend wrapperStyle={{ fontSize: 12 }} />
                            {sharedWarnDays !== null && (
                                <ReferenceLine
                                    y={sharedWarnDays}
                                    stroke="#d97706"
                                    strokeDasharray="4 3"
                                    label={{
                                        value: `Ngưỡng cảnh báo ${sharedWarnDays} ngày`,
                                        fontSize: 10,
                                        fill: '#d97706',
                                        position: 'insideTopLeft',
                                    }}
                                />
                            )}
                            {groups.map((g) => (
                                <Bar
                                    key={g.stage_group_code}
                                    dataKey={g.stage_group_code}
                                    name={g.stage_group_name}
                                    fill={colorOfGroup(g.stage_group_code)}
                                    radius={[4, 4, 0, 0]}
                                    maxBarSize={18}
                                />
                            ))}
                        </BarChart>
                    </ResponsiveContainer>
                  </div>
                </div>

                {/* Bảng số: cùng dữ liệu với biểu đồ, đọc được con số chính xác từng ngày */}
                <div style={styles.tableWrap}>
                    <table style={styles.dayTable}>
                        <thead>
                            <tr>
                                <th style={{ ...styles.th, ...styles.thSticky, textAlign: 'left' }} rowSpan={2}>
                                    Ngày
                                </th>
                                {groups.map((g) => (
                                    <th
                                        key={g.stage_group_code}
                                        colSpan={showFlows ? 4 : 2}
                                        style={{
                                            ...styles.th,
                                            ...styles.thSticky,
                                            borderLeft: '1px solid #e2e8f0',
                                            color: colorOfGroup(g.stage_group_code),
                                        }}
                                    >
                                        {g.stage_group_name}
                                    </th>
                                ))}
                            </tr>
                            <tr>
                                {groups.map((g) => (
                                    <React.Fragment key={g.stage_group_code}>
                                        <th style={{ ...styles.th2, borderLeft: '1px solid #e2e8f0' }}>Tồn</th>
                                        {showFlows && <th style={styles.th2}>Nhập</th>}
                                        {showFlows && <th style={styles.th2}>Xuất</th>}
                                        <th style={styles.th2}>Đáp ứng</th>
                                    </React.Fragment>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {byDate.map((row, idx) => (
                                <tr
                                    key={row.date}
                                    style={idx === 0 ? styles.todayRow : undefined}
                                >
                                    <td style={{ ...styles.td, textAlign: 'left', whiteSpace: 'nowrap' }}>
                                        {formatDate(row.date)}
                                        {idx === 0 && <span style={styles.todayTag}>hôm nay</span>}
                                    </td>
                                    {groups.map((g) => {
                                        const c = g.stage_group_code;
                                        const isLow = lowestDates[c] === row.date;
                                        return (
                                            <React.Fragment key={c}>
                                                <td
                                                    style={{
                                                        ...styles.td,
                                                        borderLeft: '1px solid #e2e8f0',
                                                        fontWeight: isLow ? 700 : 500,
                                                        color: isLow ? '#dc2626' : '#0f172a',
                                                    }}
                                                    title={isLow ? 'Mức tồn thấp nhất trong kỳ' : undefined}
                                                >
                                                    {formatFull(row[c])}
                                                    {isLow && <span style={styles.lowTag}>thấp nhất</span>}
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
                                                <td style={{ ...styles.td, color: '#475569' }}>
                                                    {row[`${c}_cover`] === null || row[`${c}_cover`] === undefined
                                                        ? '—'
                                                        : Number(row[`${c}_cover`]).toLocaleString('vi-VN', {
                                                              maximumFractionDigits: 1,
                                                          })}
                                                </td>
                                            </React.Fragment>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {current && (
                <div style={styles.grid}>
                    <div style={styles.box}>
                        <h3 style={styles.h3}>Chi tiết theo mã bán thành phẩm — {flowLabel(current)}</h3>
                        <p style={styles.cap}>
                            Mã chiếm nhiều giờ máy của công đoạn sau nhất xếp lên đầu. Cột "Chiếm" là
                            phần mà riêng mã đó góp vào{' '}
                            {coverLabel(current)} ngày đáp ứng của cả nhóm, cộng các dòng lại thì đúng
                            bằng con số tổng hợp.
                        </p>

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
                                style={{ width: 130, textAlign: 'right' }}
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
                                header="Giờ máy"
                                style={{ width: 90, textAlign: 'right' }}
                                body={(row) => (
                                    <span style={{ fontVariantNumeric: 'tabular-nums' }}>
                                        {Number(row.load_hours || 0).toLocaleString('vi-VN', {
                                            maximumFractionDigits: 0,
                                        })}
                                    </span>
                                )}
                            />
                            <Column
                                header="Chiếm"
                                style={{ width: 100, textAlign: 'right' }}
                                body={(row) => (
                                    <span
                                        style={{
                                            background: '#f1f5f9',
                                            color: '#334155',
                                            borderRadius: 4,
                                            padding: '2px 7px',
                                            fontSize: 12,
                                            fontWeight: 600,
                                            fontVariantNumeric: 'tabular-nums',
                                        }}
                                    >
                                        {row.days_of_cover === null
                                            ? '—'
                                            : `${Number(row.days_of_cover).toLocaleString('vi-VN', {
                                                  maximumFractionDigits: 1,
                                              })} ngày`}
                                    </span>
                                )}
                            />
                            <Column
                                header="Xuất cuối"
                                style={{ width: 100 }}
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

                    <div style={styles.box}>
                        <h3 style={styles.h3}>Xu hướng số ngày đáp ứng</h3>
                        <p style={styles.cap}>Theo các bản chốt 6h sáng đã lưu.</p>

                        {history.length < 2 ? (
                            <div style={styles.empty}>
                                Cần ít nhất 2 bản chốt mới vẽ được xu hướng. Lệnh chạy tự động mỗi 6h sáng.
                            </div>
                        ) : (
                            <div style={{ width: '100%', height: 240 }}>
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={history} margin={{ top: 8, right: 8, left: 4, bottom: 4 }}>
                                        <CartesianGrid stroke="#eef2f4" vertical={false} />
                                        <XAxis
                                            dataKey="date"
                                            tickFormatter={formatDateShort}
                                            tick={{ fontSize: 11, fill: '#64748b' }}
                                        />
                                        <YAxis tick={{ fontSize: 11, fill: '#64748b' }} width={34} />
                                        <Tooltip
                                            labelFormatter={formatDate}
                                            formatter={(v) => [`${v} ngày`, 'Đáp ứng']}
                                            contentStyle={{ fontSize: 12, borderRadius: 6 }}
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey={selected}
                                            name={current.stage_group_name}
                                            stroke={colorOfGroup(selected)}
                                            strokeWidth={2}
                                            dot={{ r: 3 }}
                                            connectNulls
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        )}
                    </div>
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
    lede: { margin: '4px 0 0', color: '#64748b', fontSize: 13.5, maxWidth: '70ch' },
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
    cards: { display: 'flex', gap: 12, flexWrap: 'wrap', marginBottom: 14 },
    card: {
        position: 'relative',
        flex: '1 1 240px',
        minWidth: 220,
        textAlign: 'left',
        border: '1px solid #e2e8f0',
        borderRadius: 8,
        background: '#fff',
        padding: '11px 13px 11px 16px',
        cursor: 'pointer',
        overflow: 'hidden',
        font: 'inherit',
    },
    stripe: { position: 'absolute', left: 0, top: 0, bottom: 0, width: 4 },
    flow: {
        fontSize: 10.5,
        fontWeight: 700,
        letterSpacing: '.08em',
        textTransform: 'uppercase',
        color: '#64748b',
    },
    numRow: { display: 'flex', alignItems: 'baseline', gap: 6, marginTop: 4 },
    num: { fontSize: 28, fontWeight: 700, lineHeight: 1, fontVariantNumeric: 'tabular-nums' },
    unit: { fontSize: 12.5, color: '#475569' },
    pill: {
        display: 'inline-block',
        marginTop: 6,
        fontSize: 10,
        fontWeight: 700,
        letterSpacing: '.05em',
        textTransform: 'uppercase',
        borderRadius: 999,
        padding: '2px 8px',
    },
    sub: {
        marginTop: 6,
        fontSize: 11.5,
        color: '#64748b',
        display: 'flex',
        gap: 7,
        flexWrap: 'wrap',
        fontVariantNumeric: 'tabular-nums',
    },
    dot: { color: '#cbd5e1' },
    basis: {
        marginTop: 5,
        paddingTop: 5,
        borderTop: '1px dashed #e2e8f0',
        fontSize: 10.5,
        color: '#94a3b8',
        lineHeight: 1.4,
    },
    method: {
        margin: '0 0 14px',
        fontSize: 12,
        color: '#64748b',
        lineHeight: 1.55,
        maxWidth: '95ch',
    },
    code: {
        background: '#f1f5f9',
        borderRadius: 3,
        padding: '0 4px',
        fontSize: 11.5,
        color: '#334155',
    },
    grid: {
        display: 'grid',
        gridTemplateColumns: 'minmax(0, 1.6fr) minmax(0, 1fr)',
        gap: 14,
        marginTop: 14,
    },
    box: { border: '1px solid #e2e8f0', borderRadius: 8, background: '#fff', padding: 14 },
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
    empty: { padding: '40px 12px', textAlign: 'center', color: '#94a3b8', fontSize: 12.5 },

    chartScroll: { width: '100%', overflowX: 'auto', overflowY: 'hidden' },
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
    thSticky: {},
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
    td: {
        padding: '6px 10px',
        textAlign: 'right',
        borderBottom: '1px solid #f1f5f9',
        whiteSpace: 'nowrap',
    },
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
    tipName: { minWidth: 68, color: '#475569' },
    tipMuted: { color: '#94a3b8', fontSize: 11 },
    tipCover: { marginLeft: 'auto', paddingLeft: 10, color: '#334155', fontSize: 11, whiteSpace: 'nowrap' },
    subHead: { display: 'flex', alignItems: 'baseline', gap: 10, flexWrap: 'wrap', margin: '16px 0 6px' },
    h4: {
        margin: 0,
        fontSize: 11,
        fontWeight: 700,
        letterSpacing: '.09em',
        textTransform: 'uppercase',
        color: '#334155',
    },
    subCap: { fontSize: 12, color: '#64748b' },

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
