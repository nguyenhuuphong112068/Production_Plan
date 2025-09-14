import { Modal, Button, Table, Badge } from "react-bootstrap";

export default function NoteModal({ show, setShow }) {
  const handleClose = () => setShow(false);
  const handleShow = () => setShow(true);

  const legends = [
    { color: "#46f905ff", label: "Lô Thương Mại - Đáp Ứng Ngày Cần Hàng" },
    { color: "#40E0D0", label: "Lô Thẩm Định - Đáp Ứng Ngày Cần Hàng" },
    { color: "#bda124ff", label: "Quá Hạn Biệt Trữ" },
    { color: "#f99e02ff", label: "Nguyên Liệu Hoặc Bao Bì Không Đáp Ứng Kế Hoạch" },
    { color: "#dc02f9ff", label: "Sản Phẩm Có Nguồn Nguyên Liệu Chưa Được Khai Báo Tại Phòng Sản Xuất"},
    { color: "#f90202ff", label: "Không Đáp Ứng Ngày Cần Hàng Theo Kế Hoạch"},
    { color: "#a1a2a2ff", label: "Vệ Sinh Phòng" },
    { color: "#003A4F", label: "Hiệu Chuẩn - Bảo Trì" },
    { color: "#CDC717", label: "Sự Kiện Khác Ngoài Kế Hoạch" },
    { color: "#002af9ff", label: "Công Đoạn Sản Xuất Hoàn Thành" },
   
  ];

  return (
    <>
      <Modal show={show} onHide={handleClose} centered size="lg">
        <Modal.Header closeButton>
           <img src="/img/iconstella.svg" style={{width: '40px', height: '40px'}} />
          <Modal.Title style={{color: '#CDC717', textAlign: 'center'}} className="mx-auto fw-bold">Chú Thích Màu Sự Kiện</Modal.Title>
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
                        display: "inline-block",
                      }}
                    ></div>
                  </td>
                  <td style={{fontSize:'18px' }}>{item.label}</td>
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
