import { Modal, Button } from "react-bootstrap";

// tag: "block" = chặn submit, "warn" = cảnh báo, không chặn submit
// Lô vi phạm nhiều thứ cùng lúc: màu nặng nhất làm nền sự kiện, các vi phạm còn lại
// hiện thành dãy sọc dọc ở cạnh phải. Thứ tự ưu tiên nằm ở $severityOrder trong colorEvent().
const LEGEND_GROUPS = [
  {
    title: "Lô sản xuất",
    items: [
      { color: "#46f905ff", label: "Lô Thương Mại - Đáp Ứng Ngày Cần Hàng" },
      { color: "#40E0D0", label: "Lô Thẩm Định - Đáp Ứng Ngày Cần Hàng" },
      { color: "#e4e405e2", label: "Lô Thẩm Định Vệ Sinh" },
      { color: "#a1a2a2ff", label: "Vệ Sinh Phòng" },
      { color: "#8195f5ff", label: "Lịch Sản Xuất Lý Thuyết" },
      { color: "#eb0cb3ff", label: "Sự Kiện Khác Ngoài Kế Hoạch" },
    ],
  },
  {
    title: "Cảnh báo & vi phạm",
    items: [
      { color: "#bda124ff", label: "Quá Hạn Biệt Trữ" },
      { color: "#ffd500ff", label: "Thiếu Khuôn / Sai Khuôn / Trùng Khuôn", tag: "warn" },
      { color: "#e67e22", label: "Sai Thiết Bị Nguồn NL", tag: "warn" },
      { color: "#f99e02ff", label: "Nguyên Liệu Hoặc Bao Bì Không Đáp Ứng Kế Hoạch" },
      { color: "#e54a4aff", label: "Không Đáp Ứng Ngày Cần Hàng Theo Kế Hoạch", tag: "block" },
      { color: "#920000ff", label: "Cảnh Báo Ngày Đáp Ứng NL/BB", tag: "block" },
      { color: "#4d4b4bff", label: "Bắt Đầu Công Đoạn Sau < Kết Thúc Công Đoạn Trước", tag: "block" },
      { color: "#ffcc80", label: "Đã Qua Giờ Kết Thúc Kế Hoạch Nhưng Chưa Xác Nhận Hoàn Thành (sản xuất hoặc vệ sinh, không áp dụng BT-HC)" },
    ],
  },
  {
    title: "Lịch thực tế",
    items: [
      { color: "#002af9ff", label: "Lịch Sản Xuất / Bảo Trì / Hiệu Chuẩn Thực Tế Đã Hoàn Tất" },
    ],
  },
  {
    title: "Bảo trì - Hiệu chuẩn",
    items: [
      { color: "#003A4F", label: "Lịch Bảo Trì Thiết Bị" },
      { color: "#b06c0cff", label: "Lịch Bảo Trì Tiện Ích" },
      { color: "#830cbfff", label: "Lịch Hiệu Chuẩn" },
      { color: "#aed9f1", label: "Lịch HC-BT Đã Xác Nhận Hoàn Thành" },
      { color: "#ffffff", border: "3px solid #22ff00ff", label: "Viền Xanh: Lịch BT-HC Đã Được Phân Xưởng Chấp Nhận" },
      { color: "#ffffff", border: "5px solid #ff0000ff", label: "Viền Đỏ Dày: Lịch BT-HC Quá Hạn Hoặc Trễ Kế Hoạch" },
      { color: "#ffffff", border: "3px solid #22ff00ff", outline: "2px solid #ff0000", label: "Viền Xanh + Outline Đỏ: Lịch BT-HC Đã Được Chấp Nhận Nhưng Bị Trễ Kế Hoạch" },
    ],
  },
  {
    title: "Đánh dấu khác",
    items: [
      { color: "#fff3cd", border: "1px solid #ffeeba", icon: "⚠️", label: "⚠️ Trước Tên: Lịch Bị Phân Xưởng Thay Đổi" },
    ],
  },
];

// Giữ đồng bộ với .fc-event-overlap trong calendar.css
const OVERLAP_LEGEND = {
  color: "#46f905ff",
  backgroundImage: "repeating-linear-gradient(-45deg, rgba(220,38,38,0.35) 0 4px, transparent 4px 9px)",
  outline: "2px dashed #dc2626",
  outlineOffset: "-1px",
  label: "Trùng Giờ: Lô Chưa Hoàn Thành Chồng Giờ Với Lô Khác Cùng Phòng, Cùng Công Đoạn (tính cả vệ sinh, bỏ qua Cân NL)",
  tag: "warn",
};

const TAGS = {
  block: { text: "Chặn submit", className: "note-tag note-tag-block" },
  warn: { text: "Cảnh báo", className: "note-tag note-tag-warn" },
};

export default function NoteModal({ show, setShow, showOverlapLegend = false }) {
  const handleClose = () => setShow(false);

  const groups = showOverlapLegend
    ? LEGEND_GROUPS.map(g => g.title === "Đánh dấu khác" ? { ...g, items: [OVERLAP_LEGEND, ...g.items] } : g)
    : LEGEND_GROUPS;

  return (
    <Modal show={show} onHide={handleClose} centered scrollable size="xl" dialogClassName="note-modal">
      <style>{`
        .note-modal .modal-content { border: none; border-radius: 12px; overflow: hidden; }
        .note-modal .modal-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 20px; }
        .note-modal .modal-body { padding: 16px 20px; background: #fff; }
        .note-modal .modal-footer { padding: 8px 20px; border-top: 1px solid #e2e8f0; }
        .note-group + .note-group { margin-top: 16px; }
        .note-group-title {
          font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
          color: #64748b; padding-bottom: 8px; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0;
        }
        .note-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 20px; }
        @media (max-width: 991px) { .note-grid { grid-template-columns: 1fr; } }
        .note-item {
          display: flex; align-items: center; gap: 14px; padding: 8px; border-radius: 6px;
          transition: background-color .15s;
        }
        .note-item:hover { background: #f1f5f9; }
        .note-swatch {
          flex: 0 0 64px; height: 30px; border-radius: 4px; box-sizing: border-box;
          display: flex; align-items: center; justify-content: center; font-size: 15px;
          box-shadow: inset 0 0 0 1px rgba(0,0,0,.08);
        }
        .note-label { font-size: 17px; line-height: 1.4; color: #1e293b; }
        .note-tag {
          display: inline-block; margin-left: 6px; padding: 2px 9px; border-radius: 999px;
          font-size: 13px; font-weight: 700; white-space: nowrap; vertical-align: 1px;
        }
        .note-tag-block { background: #fee2e2; color: #b91c1c; }
        .note-tag-warn { background: #fef3c7; color: #92400e; }
      `}</style>

      <Modal.Header closeButton>
        <img src="/img/iconstella.svg" style={{ width: 32, height: 32 }} />
        <Modal.Title style={{ color: '#CDC717', fontSize: 22 }} className="mx-auto fw-bold">Chú Thích Màu Sự Kiện</Modal.Title>
      </Modal.Header>

      <Modal.Body style={{ maxHeight: '75vh' }}>
        {groups.map(group => (
          <div className="note-group" key={group.title}>
            <div className="note-group-title">{group.title}</div>
            <div className="note-grid">
              {group.items.map((item, idx) => (
                <div className="note-item" key={idx}>
                  <div
                    className="note-swatch"
                    style={{
                      backgroundColor: item.color,
                      backgroundImage: item.backgroundImage || "none",
                      border: item.border || "none",
                      outline: item.outline || "none",
                      outlineOffset: item.outlineOffset ?? (item.outline ? "2px" : "0"),
                    }}
                  >
                    {item.icon}
                  </div>
                  <div className="note-label">
                    {item.label}
                    {item.tag && <span className={TAGS[item.tag].className}>{TAGS[item.tag].text}</span>}
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </Modal.Body>

      <Modal.Footer>
        <Button variant="secondary" size="sm" onClick={handleClose}>Đóng</Button>
      </Modal.Footer>
    </Modal>
  );
}
