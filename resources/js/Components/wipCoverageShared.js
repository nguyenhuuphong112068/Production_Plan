/**
 * Phần dùng chung của màn thống kê tồn bán thành phẩm lý thuyết đang chờ để
 * bước vào từng công đoạn: Định hình, Bao phim, Đóng gói.
 */

/**
 * Màu định danh cho từng công đoạn ĐÍCH, gán cố định theo mã nhóm chứ không
 * theo thứ tự vẽ, để lọc bớt nhóm cũng không làm đổi màu các nhóm còn lại.
 */
export const GROUP_COLORS = {
    DH: '#7c3aed',
    BP: '#c2410c',
    DG: '#0369a1',
    // Lô chưa lần ra được công đoạn sau: đó là chuyện dữ liệu chưa khai đủ,
    // không phải một công đoạn thật sự đứng ngang hàng với ba công đoạn trên
    NA: '#94a3b8',
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

/** Pha chế cân theo Kg, giữ một chữ số thập phân cho khớp phiếu cân */
export function formatKg(value) {
    const n = Number(value) || 0;
    if (n === 0) return '0';
    if (Math.abs(n) >= 1e6) return (n / 1e6).toLocaleString('vi-VN', { maximumFractionDigits: 2 }) + ' tr';
    if (Math.abs(n) >= 1e4) return (n / 1e3).toLocaleString('vi-VN', { maximumFractionDigits: 1 }) + ' ng';
    return n.toLocaleString('vi-VN', { maximumFractionDigits: 1 });
}

/** Màu riêng cho nguồn vào Pha chế, tách hẳn khỏi ba màu công đoạn đích */
export const SUPPLY_COLOR = '#0d9488';

/**
 * Đối chiếu một mức tồn với giới hạn đã cấu hình.
 * Trả về 'low' khi thiếu hàng cho công đoạn sau, 'high' khi ứ hàng, null khi
 * trong khoảng hoặc khi công đoạn đó chưa cấu hình giới hạn nào.
 */
export function limitStateOf(value, limit) {
    if (!limit) return null;

    const v = Number(value) || 0;
    if (limit.min_stock_dvl !== null && limit.min_stock_dvl !== undefined && v < Number(limit.min_stock_dvl)) {
        return 'low';
    }
    if (limit.max_stock_dvl !== null && limit.max_stock_dvl !== undefined && v > Number(limit.max_stock_dvl)) {
        return 'high';
    }
    return null;
}

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

/** Nhãn đầy đủ, ví dụ "Chờ Đóng gói" */
export function groupLabel(group) {
    if (!group) return '';
    if (group.group_code === 'NA') return group.group_name || 'Chưa rõ công đoạn sau';
    return `Chờ ${group.group_name || group.group_code}`;
}
