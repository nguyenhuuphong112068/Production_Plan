import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
} from 'recharts';

import {
    colorOfGroup,
    formatDvl,
    formatFull,
    formatDate,
    formatDateShort,
} from './wipCoverageShared';

const GROUP_ORDER = ['DH', 'BP', 'DG'];

/**
 * Màu cho từng phòng trong cột chồng. Một công đoạn có tới hơn chục phòng nên
 * dùng bảng màu riêng xoay vòng; phần "chưa xếp phòng" luôn xám để không bị
 * lẫn với một phòng thật.
 */
const ROOM_PALETTE = [
    '#2563eb', '#db2777', '#16a34a', '#ea580c', '#7c3aed', '#0891b2',
    '#ca8a04', '#dc2626', '#4f46e5', '#059669', '#c026d3', '#65a30d',
    '#0284c7', '#e11d48', '#9333ea', '#d97706',
];
const UNASSIGNED_COLOR = '#94a3b8';

const roomLabel = (r) => (r.room_code ? `${r.room_code} · ${r.room_name}` : r.room_name);

/** "#0369a1" + 0.3 -> "rgba(3,105,161,0.3)", để tô nền ô theo mức tồn */
function tint(hex, alpha) {
    const n = parseInt(hex.slice(1), 16);
    return `rgba(${(n >> 16) & 255},${(n >> 8) & 255},${n & 255},${alpha})`;
}

/**
 * Tab tồn theo PHÒNG TIẾP THEO: cùng con số với tab theo công đoạn, nhưng tách
 * tồn chờ của một công đoạn ra từng phòng mà lô con phía sau đã được xếp vào.
 * Phần chưa sắp lịch công đoạn sau thì chưa có phòng, gom vào "Chưa xếp phòng".
 *
 * reloadKey đổi là tính lại; onOpenDay mở modal xem lô dùng chung với trang cha.
 */
const WipCoverageByRoom = ({ reloadKey, onOpenDay, onLoadingChange }) => {
    const [rooms, setRooms] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [group, setGroup] = useState(null);
    const [showEmpty, setShowEmpty] = useState(false);

    const load = useCallback(() => {
        setLoading(true);
        setError(null);
        onLoadingChange && onLoadingChange(true);

        axios
            .post('/Schedual/wip_coverage/room_view')
            .then(({ data }) => {
                if (!data.success) {
                    setError(data.message || 'Không tải được dữ liệu.');
                    return;
                }
                setRooms(data.rooms || []);
                setMeta({ snapshot_at: data.snapshot_at });
            })
            .catch((err) => {
                setError(
                    err.response && err.response.status === 403
                        ? 'Bạn không có quyền xem chức năng này.'
                        : 'Không tải được dữ liệu tồn theo phòng.'
                );
            })
            .finally(() => {
                setLoading(false);
                onLoadingChange && onLoadingChange(false);
            });
    }, [onLoadingChange]);

    useEffect(() => {
        load();
    }, [load, reloadKey]);

    // Tổng từng công đoạn, để làm nút chọn và mặc định mở công đoạn tồn nhiều nhất
    const groupTotals = useMemo(() => {
        const out = {};
        rooms.forEach((r) => {
            if (!out[r.group_code]) out[r.group_code] = { code: r.group_code, name: r.group_name, stock: 0, rooms: 0 };
            out[r.group_code].stock += Number(r.stock_dvl) || 0;
            if (!r.is_empty && r.room_id !== null) out[r.group_code].rooms++;
        });
        return GROUP_ORDER.filter((c) => out[c]).map((c) => out[c]);
    }, [rooms]);

    useEffect(() => {
        if (groupTotals.length === 0) return;
        if (group && groupTotals.some((g) => g.code === group)) return;
        setGroup(groupTotals.reduce((a, b) => (a.stock >= b.stock ? a : b)).code);
    }, [groupTotals, group]);

    // Phòng của công đoạn đang xem; phòng cả kỳ không có hàng ẩn đi cho gọn
    const groupRooms = useMemo(
        () => rooms.filter((r) => r.group_code === group && (showEmpty || !r.is_empty)),
        [rooms, group, showEmpty]
    );
    const hiddenCount = useMemo(
        () => rooms.filter((r) => r.group_code === group && r.is_empty).length,
        [rooms, group]
    );

    const colorOfRoom = useMemo(() => {
        const map = {};
        let i = 0;
        groupRooms.forEach((r) => {
            map[r.room_key] = r.room_id === null ? UNASSIGNED_COLOR : ROOM_PALETTE[i++ % ROOM_PALETTE.length];
        });
        return map;
    }, [groupRooms]);

    const dates = useMemo(() => {
        const first = groupRooms[0] || rooms[0];
        return first ? (first.daily_series || []).map((p) => p.date) : [];
    }, [groupRooms, rooms]);

    // Một dòng mỗi ngày cho biểu đồ cột chồng: mỗi phòng là một khúc của cột
    const chartData = useMemo(
        () =>
            dates.map((date, i) => {
                const row = { date, label: formatDateShort(date), total: 0 };
                groupRooms.forEach((r) => {
                    const v = Number((r.daily_series[i] || {}).stock_dvl) || 0;
                    row[r.room_key] = v;
                    row.total += v;
                });
                return row;
            }),
        [dates, groupRooms]
    );

    // Mức tồn lớn nhất của một ô trong công đoạn, làm thang tô đậm nhạt
    const cellMax = useMemo(() => {
        let max = 0;
        groupRooms.forEach((r) =>
            (r.daily_series || []).forEach((p) => {
                max = Math.max(max, Number(p.stock_dvl) || 0);
            })
        );
        return max;
    }, [groupRooms]);

    const hue = colorOfGroup(group);
    const groupName = (groupTotals.find((g) => g.code === group) || {}).name || '';

    if (loading && rooms.length === 0) {
        return <div style={styles.state}>Đang tính tồn theo phòng…</div>;
    }

    if (error) {
        return <div style={{ ...styles.state, color: '#dc2626' }}>{error}</div>;
    }

    if (groupTotals.length === 0) {
        return <div style={styles.state}>Chưa có tồn bán thành phẩm nào đang chờ phòng nào.</div>;
    }

    return (
        <div style={styles.box}>
            <div style={styles.boxHead}>
                <div>
                    <h3 style={styles.h3}>Tồn chờ từng phòng theo ngày</h3>
                    <p style={styles.cap}>
                        Tồn chờ một công đoạn được chia về phòng mà lô công đoạn sau đã được xếp lịch. Lô đóng
                        gói một phần ở nhiều phòng thì mỗi phòng nhận đúng phần của mình. Tổng các phòng của một
                        công đoạn bằng đúng tổng công đoạn đó ở tab bên cạnh.
                        {meta && ` Tính lúc ${formatDate(meta.snapshot_at)} ${String(meta.snapshot_at).slice(11, 16)}.`}
                    </p>
                </div>
                <div style={styles.tabs}>
                    {groupTotals.map((g) => {
                        const active = g.code === group;
                        const c = colorOfGroup(g.code);
                        return (
                            <button
                                type="button"
                                key={g.code}
                                onClick={() => setGroup(g.code)}
                                style={{
                                    ...styles.tab,
                                    color: active ? '#fff' : c,
                                    background: active ? c : '#fff',
                                    borderColor: active ? c : '#e2e8f0',
                                }}
                            >
                                Chờ {g.name}
                                <span
                                    style={{
                                        ...styles.tabQty,
                                        color: active ? 'rgba(255,255,255,.85)' : '#94a3b8',
                                    }}
                                >
                                    {formatDvl(g.stock)} · {g.rooms} phòng
                                </span>
                            </button>
                        );
                    })}
                </div>
            </div>

            <div style={styles.chartScroll}>
                <div style={{ minWidth: Math.max(720, dates.length * 32), height: 300 }}>
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={chartData} margin={{ top: 8, right: 16, left: 4, bottom: 4 }}>
                            <CartesianGrid stroke="#eef2f4" vertical={false} />
                            <XAxis
                                dataKey="label"
                                tick={{ fontSize: 11, fill: '#64748b' }}
                                interval={0}
                                angle={-45}
                                textAnchor="end"
                                height={48}
                            />
                            <YAxis tick={{ fontSize: 11, fill: '#64748b' }} tickFormatter={formatDvl} width={62} />
                            <Tooltip
                                cursor={{ fill: '#0f172a', fillOpacity: 0.04 }}
                                content={({ active, payload, label }) => {
                                    if (!active || !payload || payload.length === 0) return null;
                                    const d = payload[0].payload;
                                    const list = groupRooms
                                        .filter((r) => d[r.room_key] > 0)
                                        .sort((a, b) => d[b.room_key] - d[a.room_key]);
                                    return (
                                        <div style={styles.tooltip}>
                                            <div style={{ fontWeight: 700, marginBottom: 5 }}>
                                                Chờ {groupName} · {label} lúc 06:00
                                            </div>
                                            {list.map((r) => (
                                                <div key={r.room_key} style={styles.tipRow}>
                                                    <span
                                                        style={{ ...styles.tipDot, background: colorOfRoom[r.room_key] }}
                                                    />
                                                    <span style={styles.tipName}>{roomLabel(r)}</span>
                                                    <b>{formatFull(d[r.room_key])}</b>
                                                </div>
                                            ))}
                                            <div style={styles.tipTotal}>
                                                Tổng <b>{formatFull(d.total)}</b> ĐVL
                                            </div>
                                        </div>
                                    );
                                }}
                            />
                            {groupRooms.map((r) => (
                                <Bar
                                    key={r.room_key}
                                    dataKey={r.room_key}
                                    name={roomLabel(r)}
                                    stackId="rooms"
                                    fill={colorOfRoom[r.room_key]}
                                    maxBarSize={30}
                                />
                            ))}
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </div>

            <div style={styles.tableHead}>
                <span style={styles.cap}>
                    Mỗi ô là mức tồn lúc 06:00 chờ phòng đó, nền càng đậm càng nhiều hàng. Bấm vào ô để xem
                    từng lô.
                </span>
                {hiddenCount > 0 && (
                    <label style={styles.toggle}>
                        <input type="checkbox" checked={showEmpty} onChange={(e) => setShowEmpty(e.target.checked)} />
                        Hiện {hiddenCount} phòng không có hàng trong kỳ
                    </label>
                )}
            </div>

            {/* Phòng theo hàng, ngày theo cột: 30 ngày × hơn chục phòng vẫn đọc được,
                cột tên phòng dính bên trái khi cuộn ngang */}
            <div style={styles.tableWrap}>
                <table style={styles.table}>
                    <thead>
                        <tr>
                            <th style={{ ...styles.th, ...styles.stickyCol, textAlign: 'left', zIndex: 3 }}>Phòng</th>
                            <th style={styles.th}>Hiện tại</th>
                            <th style={styles.th}>TB</th>
                            <th style={{ ...styles.th, borderRight: '1px solid #cbd5e1' }}>Cao nhất</th>
                            {dates.map((d, i) => (
                                <th key={d} style={{ ...styles.th, ...(i === 0 ? styles.todayTh : null) }}>
                                    {formatDateShort(d)}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {groupRooms.map((r) => (
                            <tr key={r.room_key}>
                                <td
                                    style={{ ...styles.td, ...styles.stickyCol, textAlign: 'left' }}
                                    title={roomLabel(r)}
                                >
                                    <span style={{ ...styles.tipDot, background: colorOfRoom[r.room_key] }} />
                                    <span style={{ fontWeight: 600, color: r.room_id === null ? '#b45309' : '#334155' }}>
                                        {r.room_code || ''}
                                    </span>{' '}
                                    <span style={{ color: '#64748b' }}>{r.room_name}</span>
                                </td>
                                <td style={{ ...styles.td, fontWeight: 700 }}>
                                    {formatFull(r.stock_dvl)}
                                    <span style={styles.lots}>{r.stock_lots} lô</span>
                                </td>
                                <td style={styles.td}>{formatDvl(r.avg_stock_dvl)}</td>
                                <td style={{ ...styles.td, borderRight: '1px solid #cbd5e1' }}>
                                    {formatDvl(r.highest_stock_dvl)}
                                </td>
                                {(r.daily_series || []).map((p) => {
                                    const v = Number(p.stock_dvl) || 0;
                                    const alpha = cellMax > 0 && v > 0 ? 0.08 + 0.6 * (v / cellMax) : 0;
                                    return (
                                        <td
                                            key={p.date}
                                            style={{
                                                ...styles.cell,
                                                background: alpha > 0 ? tint(hue, alpha) : undefined,
                                                color: alpha > 0.45 ? '#fff' : v > 0 ? '#0f172a' : '#cbd5e1',
                                                cursor: v > 0 ? 'pointer' : 'default',
                                            }}
                                            onClick={
                                                v > 0
                                                    ? () =>
                                                          onOpenDay({
                                                              date: p.date,
                                                              groupCode: r.group_code,
                                                              kind: 'stock',
                                                              groupLabel: `Chờ ${roomLabel(r)}`,
                                                              roomKey: r.room_key,
                                                          })
                                                    : undefined
                                            }
                                            title={`${roomLabel(r)} · ${formatDate(p.date)}: ${formatFull(v)} ĐVL · ${
                                                p.stock_lots
                                            } lô · +${formatDvl(p.in_dvl)} / −${formatDvl(p.out_dvl)}`}
                                        >
                                            {v > 0 ? formatDvl(v) : '—'}
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                        <tr>
                            <td style={{ ...styles.td, ...styles.stickyCol, ...styles.totalCell, textAlign: 'left' }}>
                                Tổng chờ {groupName}
                            </td>
                            <td style={{ ...styles.td, ...styles.totalCell }}>
                                {formatFull(groupRooms.reduce((s, r) => s + (Number(r.stock_dvl) || 0), 0))}
                            </td>
                            <td style={{ ...styles.td, ...styles.totalCell }} />
                            <td style={{ ...styles.td, ...styles.totalCell, borderRight: '1px solid #cbd5e1' }} />
                            {chartData.map((d) => (
                                <td key={d.date} style={{ ...styles.cell, ...styles.totalCell }}>
                                    {formatDvl(d.total)}
                                </td>
                            ))}
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );
};

const styles = {
    state: { padding: 40, textAlign: 'center', color: '#64748b' },
    box: { border: '1px solid #e2e8f0', borderRadius: 8, background: '#fff', padding: 14 },
    boxHead: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        gap: 16,
        flexWrap: 'wrap',
    },
    h3: {
        margin: 0,
        fontSize: 11.5,
        fontWeight: 700,
        letterSpacing: '.09em',
        textTransform: 'uppercase',
        color: '#334155',
    },
    cap: { margin: '3px 0 12px', fontSize: 12.5, color: '#64748b', maxWidth: '110ch' },

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

    chartScroll: { width: '100%', overflowX: 'auto', overflowY: 'hidden' },

    tableHead: {
        marginTop: 12,
        paddingTop: 10,
        borderTop: '1px dashed #e2e8f0',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'baseline',
        gap: 12,
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

    tableWrap: {
        maxHeight: 520,
        overflow: 'auto',
        border: '1px solid #e2e8f0',
        borderRadius: 6,
    },
    table: {
        borderCollapse: 'separate',
        borderSpacing: 0,
        fontSize: 12,
        fontVariantNumeric: 'tabular-nums',
    },
    th: {
        position: 'sticky',
        top: 0,
        zIndex: 2,
        background: '#f8fafc',
        padding: '7px 8px',
        textAlign: 'right',
        fontSize: 11,
        fontWeight: 700,
        color: '#475569',
        borderBottom: '1px solid #e2e8f0',
        whiteSpace: 'nowrap',
    },
    todayTh: { color: '#0d9488', background: '#f0fdfa' },
    stickyCol: {
        position: 'sticky',
        left: 0,
        zIndex: 1,
        background: '#fff',
        minWidth: 220,
        maxWidth: 260,
        overflow: 'hidden',
        textOverflow: 'ellipsis',
        borderRight: '1px solid #e2e8f0',
    },
    td: {
        padding: '5px 8px',
        textAlign: 'right',
        borderBottom: '1px solid #f1f5f9',
        whiteSpace: 'nowrap',
    },
    cell: {
        padding: '5px 6px',
        minWidth: 52,
        textAlign: 'right',
        borderBottom: '1px solid #fff',
        borderRight: '1px solid #fff',
        whiteSpace: 'nowrap',
        fontSize: 11.5,
    },
    lots: { marginLeft: 5, fontSize: 10.5, fontWeight: 400, color: '#94a3b8' },
    totalCell: { fontWeight: 700, background: '#f8fafc', color: '#0f172a', borderTop: '1px solid #cbd5e1' },

    tooltip: {
        background: '#fff',
        border: '1px solid #e2e8f0',
        borderRadius: 6,
        padding: '9px 11px',
        fontSize: 12,
        boxShadow: '0 2px 10px rgba(15,23,42,.14)',
        fontVariantNumeric: 'tabular-nums',
        maxWidth: 360,
    },
    tipRow: { display: 'flex', alignItems: 'center', gap: 6, marginTop: 2 },
    tipDot: { display: 'inline-block', width: 9, height: 9, borderRadius: 2, flex: 'none', marginRight: 6 },
    tipName: { flex: 1, minWidth: 140, color: '#475569' },
    tipTotal: {
        marginTop: 6,
        paddingTop: 5,
        borderTop: '1px solid #f1f5f9',
        color: '#334155',
        fontSize: 11.5,
    },
};

export default WipCoverageByRoom;
