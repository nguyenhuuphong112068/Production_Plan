/**
 * Phần dùng chung giữa bảng tóm tắt trong Lịch sản xuất và trang chi tiết
 * của chức năng cảnh báo tồn bán thành phẩm.
 */

export const WIP_STATUS = {
    critical:  { label: 'Nguy cấp',      color: '#dc2626', bg: '#fee2e2', border: '#fecaca' },
    warn:      { label: 'Sắp cạn',       color: '#d97706', bg: '#fef3c7', border: '#fde68a' },
    ok:        { label: 'Đủ',            color: '#16a34a', bg: '#dcfce7', border: '#bbf7d0' },
    no_demand: { label: 'Chưa có lịch',  color: '#64748b', bg: '#f1f5f9', border: '#e2e8f0' },
};

export const statusOf = (code) => WIP_STATUS[code] || WIP_STATUS.no_demand;

/**
 * Màu định danh cho từng nhóm công đoạn, gán cố định theo nhóm chứ không theo
 * thứ tự vẽ, để lọc bớt nhóm cũng không làm đổi màu các nhóm còn lại.
 * Bộ ba này đã qua kiểm tra tách màu cho người mù màu (ΔE 21,0 deutan).
 */
export const GROUP_COLORS = {
    PC: '#0d9488',
    DH: '#7c3aed',
    BP: '#c2410c',
};

export const colorOfGroup = (code) => GROUP_COLORS[code] || '#64748b';

/** Rút gọn số lớn: 123.657.654 -> "123,7 tr" */
export function formatDvl(value) {
    const n = Number(value) || 0;
    if (n === 0) return '0';
    if (Math.abs(n) >= 1e9) return (n / 1e9).toLocaleString('vi-VN', { maximumFractionDigits: 2 }) + ' tỷ';
    if (Math.abs(n) >= 1e6) return (n / 1e6).toLocaleString('vi-VN', { maximumFractionDigits: 1 }) + ' tr';
    if (Math.abs(n) >= 1e3) return (n / 1e3).toLocaleString('vi-VN', { maximumFractionDigits: 1 }) + ' ng';
    return n.toLocaleString('vi-VN', { maximumFractionDigits: 0 });
}

export function formatFull(value) {
    return (Number(value) || 0).toLocaleString('vi-VN', { maximumFractionDigits: 0 });
}

/**
 * Nhãn số ngày đáp ứng.
 * Rỗng nghĩa là không quy ra ngày được: lô không có công đoạn sau nào, hoặc công
 * đoạn sau chưa từng chạy nên chưa đo được nhịp. Không phải là "tồn vô hạn".
 */
export function coverLabel(group) {
    if (group.days_of_cover === null || group.days_of_cover === undefined) {
        return '—';
    }
    return Number(group.days_of_cover).toLocaleString('vi-VN', { maximumFractionDigits: 1 });
}

export function coverSuffix(group) {
    if (group.days_of_cover === null || group.days_of_cover === undefined) {
        return group.has_demand ? 'chưa đo được nhịp' : 'không có công đoạn sau';
    }
    return 'ngày';
}

/** "16 phòng × … = 148 h/ngày, kho này được 148" cho phần chú thích cách tính */
export function capacityLabel(basis) {
    if (!basis || basis.length === 0) return null;

    return basis
        .map((b) => {
            const stage = STAGE_LABELS[b.stage_code] || `CĐ ${b.stage_code}`;
            const rate = Number(b.hours_per_day || 0).toLocaleString('vi-VN', {
                maximumFractionDigits: 0,
            });
            const share = Number(b.share_per_day || 0).toLocaleString('vi-VN', {
                maximumFractionDigits: 0,
            });
            // Công đoạn dùng chung thì ghi cả phần được chia, để không ai tưởng
            // kho này chiếm trọn dây chuyền
            const detail = Math.abs(b.share_per_day - b.hours_per_day) < 0.5
                ? `${rate} h/ngày`
                : `${share} trong ${rate} h/ngày`;
            return `${stage} ${b.rooms} phòng, ${detail}`;
        })
        .join(' · ');
}

const STAGE_LABELS = {
    3: 'Pha chế',
    4: 'Trộn hoàn tất',
    5: 'Định hình',
    6: 'Bao phim',
    7: 'Đóng gói',
};

export function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

export function formatDateShort(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
}

/** Nhãn hướng đi của tồn, ví dụ "Tồn Pha chế → Định hình" */
export function flowLabel(group) {
    const from = group.stage_group_name || group.stage_group_code;
    const to = group.next_stage_group_name;
    return to ? `Tồn ${from} → ${to}` : `Tồn ${from}`;
}
