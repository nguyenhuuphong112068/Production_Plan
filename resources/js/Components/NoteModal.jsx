import { Modal, Button, Table, Badge } from "react-bootstrap";

export default function NoteModal({ show, setShow }) {
  const handleClose = () => setShow(false);
  const handleShow = () => setShow(true);

  const legends = [
    { color: "#46f905ff", label: "Lô Thương Mại - Đáp Ứng Ngày Cần Hàng" },
    { color: "#40E0D0", label: "Lô Thẩm Định - Đáp Ứng Ngày Cần Hàng" },
    { color: "#e4e405e2", label: "Lô Thẩm Định Vệ Sinh" },
    { color: "#bda124ff", label: "Quá Hạn Biệt Trữ" },
    { color: "#f99e02ff", label: "Nguyên Liệu Hoặc Bao Bì Không Đáp Ứng Kế Hoạch" },
    { color: "#e54a4aff", label: "Không Đáp Ứng Ngày Cần Hàng Theo Kế Hoạch" },
    { color: "#920000ff", label: "Cảnh Báo Ngày Đáp Ứng NL/BB" },
    { color: "#a1a2a2ff", label: "Vệ Sinh Phòng" },
    { color: "#eb0cb3ff", label: "Sự Kiện Khác Ngoài Kế Hoạch" },
    { color: "#4d4b4bff", label: "Lịch Vi Phạm, Bắt Đầu Công Đoạn Sau < Kết Thúc Công Đoạn Trước" },
    { color: "#002af9ff", label: "Lịch Sản Xuất/Bảo Trì/Hiệu Chuẩn Lý Thực Tế đã hoàn tất" },
    { color: "#8195f5ff", label: "Lịch Sản Xuất Lý Thuyết" },
    { color: "#003A4F", label: "Lịch Bảo Trì Thiết Bị " },
    { color: "#b06c0cff", label: "Lịch Bảo Trì Tiện Ích" },
    { color: "#830cbfff", label: "Lịch Hiệu Chuẩn" },
    { color: "#aed9f1", label: "Lịch HC-BT đã xác nhận hoàn thành" },
    { color: "transparent", label: "Viền Xanh: Lịch BT-HC đã được chấp nhận bởi phân xưởng", border: "3px solid #22ff00ff" },
    { color: "transparent", label: "Viền Đỏ Dày: Lịch BT-HC quá hạn hoặc trễ kế hoạch", border: "6px solid #ff0000ff" },
    { color: "transparent", label: "Viền Xanh + Outline Đỏ: Lịch BT-HC đã được chấp nhận nhưng bị trễ kế hoạch", border: "3px solid #22ff00ff", outline: "3px solid #ff0000" },
    { color: "#fff3cd", label: "⚠️ Tên: Lịch bị Phân xưởng thay đổi", icon: "⚠️", border: "1px solid #ffeeba" },
  ];

  return (
    <>
      <Modal show={show} onHide={handleClose} centered size="lg">
        <Modal.Header closeButton>
          <img src="/img/iconstella.svg" style={{ width: '40px', height: '40px' }} />
          <Modal.Title style={{ color: '#CDC717', textAlign: 'center' }} className="mx-auto fw-bold">Chú Thích Màu Sự Kiện</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Table bordered hover size="sm">
            <thead>
              <tr>
                <th style={{ width: "100px" }}>Màu</th>
                <th>Ý nghĩa</th>
              </tr>
            </thead>
            <tbody>
              {legends.map((item, idx) => (
                <tr key={idx}>
                  <td>
                    <div
                      style={{
                        backgroundColor: item.color,
                        width: "100%",
                        height: "30px",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        border: item.border || "none",
                        outline: item.outline || "none",
                        outlineOffset: item.outline ? "2px" : "0",
                      }}
                    >
                      {item.icon && <span style={{ fontSize: '14px' }}>{item.icon}</span>}
                    </div>
                  </td>
                  <td style={{ fontSize: '18px', verticalAlign: 'middle' }}>{item.label}</td>
                </tr>
              ))}
            </tbody>
          </Table>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={handleClose}>
            Đóng
          </Button>
        </Modal.Footer>
      </Modal>
    </>
  );
}
