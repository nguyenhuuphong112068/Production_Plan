import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Dialog } from 'primereact/dialog';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';

import { formatFull, formatKg, formatDate, colorOfGroup, SUPPLY_COLOR } from './wipCoverageShared';

const KIND_LABELS = { stock: 'Tồn', in: 'Nhập', out: 'Xuất' };
const KIND_COLORS = { stock: '#334155', in: '#0d9488', out: '#b45309' };

/** "2026-08-26 06:00:00" -> hai dòng "26/08/2026" + "06:00", để không bị vỡ dòng giữa chừng */
function MomentCell({ value }) {
    if (!value) {
        return <span style={styles.momentDash}>—</span>;
    }

    const d = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) {
        return <span style={styles.momentDash}>—</span>;
    }

    return (
        <div style={styles.momentCell}>
            <span style={styles.momentDate}>
                {d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })}
            </span>
            <span style={styles.momentTime}>
                {d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}
            </span>
        </div>
    );
}

/**
 * Modal xem lô: bấm vào một con số trên bảng ngày (tồn, nhập, xuất, hoặc
 * lượng Pha chế nhập vào) là xem được ngay lô nào gộp lại thành số đó.
 *
 * request = { date, groupCode, kind, groupLabel } | null — null nghĩa là đóng.
 * Component tự gọi API khi request đổi, không giữ cache giữa các lần mở vì
 * dữ liệu tính trực tiếp (không đọc bản chốt) nên luôn mới nhất tại lúc bấm.
 */
const WipCoverageDayDetailModal = ({ request, onHide }) => {
    const [rows, setRows] = useState([]);
    const [totalDvl, setTotalDvl] = useState(0);
    const [totalKg, setTotalKg] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!request) return;

        setLoading(true);
        setError(null);

        axios
            .post('/Schedual/wip_coverage/day_detail', {
                date: request.date,
                group_code: request.groupCode,
                kind: request.kind,
            })
            .then(({ data }) => {
                if (!data.success) {
                    setError(data.message || 'Không tải được dữ liệu.');
                    return;
                }
                setRows(data.rows || []);
                setTotalDvl(data.total_dvl || 0);
                setTotalKg(data.total_kg === undefined ? null : data.total_kg);
            })
            .catch(() => setError('Không tải được danh sách lô.'))
            .finally(() => setLoading(false));
    }, [request]);

    const isSupply = request && request.groupCode === 'SUPPLY';
    const accent = request ? (isSupply ? SUPPLY_COLOR : colorOfGroup(request.groupCode)) : '#64748b';
    const kindLabel = isSupply ? 'Sản lượng ngày' : KIND_LABELS[request && request.kind] || '';
    const kindColor = isSupply ? SUPPLY_COLOR : KIND_COLORS[request && request.kind] || '#334155';

    const header = request && (
        <div style={styles.header}>
            <span style={{ ...styles.headerDot, background: accent }} />
            <span style={styles.headerLabel}>{request.groupLabel}</span>
            <span style={{ ...styles.headerKind, color: kindColor, borderColor: kindColor }}>{kindLabel}</span>
            <span style={styles.headerDate}>{formatDate(request.date)}</span>
        </div>
    );

    return (
        <Dialog
            header={header}
            visible={!!request}
            onHide={onHide}
            style={{ width: 'min(1220px, 95vw)' }}
            contentStyle={{ padding: '4px 22px 18px' }}
            headerStyle={{ padding: '16px 22px 12px', borderBottom: `2px solid ${accent}22` }}
            dismissableMask
        >
            {error ? (
                <div style={{ padding: 30, textAlign: 'center', color: '#dc2626' }}>{error}</div>
            ) : (
                <>
                    <DataTable
                        value={rows}
                        loading={loading}
                        size="small"
                        stripedRows
                        showGridlines
                        paginator={rows.length > 10}
                        rows={10}
                        emptyMessage="Không có lô nào trong khoảng này."
                        dataKey={(r) =>
                            `${r.intermediate_code}-${r.batch}-${r.stage_code}-${r.prev_moment}-${r.next_moment}`
                        }
                        tableStyle={{ fontSize: 13 }}
                    >
                        <Column
                            field="intermediate_code"
                            header="Mã SP"
                            style={{ width: 118 }}
                            bodyStyle={{ fontWeight: 600, color: '#334155' }}
                        />
                        <Column field="product_name" header="Tên sản phẩm" style={{ minWidth: 240 }} />
                        <Column
                            field="batch"
                            header="Số lô"
                            style={{ width: 96, textAlign: 'center' }}
                            bodyStyle={{ textAlign: 'center', fontVariantNumeric: 'tabular-nums' }}
                        />
                        <Column
                            header="Công đoạn"
                            style={{ width: 130 }}
                            body={(row) => (
                                <span style={styles.stagePill}>{row.stage_name || `CĐ${row.stage_code}`}</span>
                            )}
                        />
                        <Column
                            header="Xuất công đoạn trước"
                            style={{ width: 128 }}
                            body={(row) => <MomentCell value={row.prev_moment} />}
                        />
                        <Column
                            header="Dự kiến công đoạn sau"
                            style={{ width: 128 }}
                            body={(row) => <MomentCell value={row.next_moment} />}
                        />
                        <Column
                            header="Sản lượng"
                            style={{ width: 172, textAlign: 'right' }}
                            bodyStyle={{ textAlign: 'right' }}
                            body={(row) => (
                                <span style={{ fontVariantNumeric: 'tabular-nums' }}>
                                    <span style={styles.qtyMain}>{formatFull(row.qty_dvl)}</span>{' '}
                                    <span style={styles.qtyUnit}>{row.unit || 'ĐVL'}</span>
                                    {row.qty_kg !== null && row.qty_kg !== undefined && (
                                        <div style={styles.qtyKg}>{formatKg(row.qty_kg)} Kg</div>
                                    )}
                                </span>
                            )}
                        />
                    </DataTable>

                    <div style={{ ...styles.footer, background: `${accent}0d`, borderColor: `${accent}33` }}>
                        <span style={styles.footerCount}>{rows.length} lô</span>
                        <span style={styles.footerTotal}>
                            Tổng <b style={{ color: accent }}>{formatFull(totalDvl)}</b> ĐVL
                            {isSupply && totalKg !== null && (
                                <>
                                    {' '}
                                    · <b style={{ color: accent }}>{formatKg(totalKg)}</b> Kg
                                </>
                            )}
                        </span>
                    </div>
                </>
            )}
        </Dialog>
    );
};

const styles = {
    header: { display: 'flex', alignItems: 'center', gap: 10, flexWrap: 'wrap' },
    headerDot: { width: 11, height: 11, borderRadius: 3, flex: 'none' },
    headerLabel: { fontSize: 16, fontWeight: 700, color: '#0f172a' },
    headerKind: {
        fontSize: 11.5,
        fontWeight: 700,
        letterSpacing: '.03em',
        border: '1.5px solid',
        borderRadius: 999,
        padding: '2px 10px',
    },
    headerDate: {
        marginLeft: 'auto',
        marginRight: 28,
        fontSize: 13,
        color: '#64748b',
        fontVariantNumeric: 'tabular-nums',
    },

    stagePill: {
        display: 'inline-block',
        fontSize: 12,
        fontWeight: 500,
        color: '#475569',
        background: '#f1f5f9',
        borderRadius: 5,
        padding: '3px 9px',
        whiteSpace: 'nowrap',
    },

    momentCell: { display: 'flex', flexDirection: 'column', lineHeight: 1.35 },
    momentDate: { fontSize: 12.5, color: '#334155', fontVariantNumeric: 'tabular-nums' },
    momentTime: { fontSize: 11, color: '#94a3b8', fontVariantNumeric: 'tabular-nums' },
    momentDash: { color: '#cbd5e1' },

    qtyMain: { fontSize: 13.5, fontWeight: 700, color: '#0f172a' },
    qtyUnit: { fontSize: 11.5, color: '#94a3b8' },
    qtyKg: { fontSize: 11, color: '#94a3b8', marginTop: 1 },

    footer: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        flexWrap: 'wrap',
        gap: 10,
        marginTop: 12,
        padding: '10px 16px',
        borderRadius: 8,
        border: '1px solid',
        fontSize: 13.5,
    },
    footerCount: { color: '#64748b' },
    footerTotal: { color: '#0f172a', fontVariantNumeric: 'tabular-nums' },
};

export default WipCoverageDayDetailModal;
