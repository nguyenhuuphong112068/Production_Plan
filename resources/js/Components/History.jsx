import { Modal, Button, Table, Badge } from "react-bootstrap";
import dayjs from 'dayjs';
export default function History({ show, setShow, historyData}) {
  const handleClose = () => setShow(false);
  return (
    <>
      <Modal show={show} onHide={handleClose} centered size="xl">
        <Modal.Header closeButton>
           <img src="/img/iconstella.svg" style={{width: '40px', height: '40px'}} />
          <Modal.Title style={{color: '#CDC717', textAlign: 'center'}} className="mx-auto fw-bold">Lịch Sử Thay Đổi Lịch Sản Xuất</Modal.Title>
        </Modal.Header>
        <Modal.Body>


          <Table bordered hover size="xl">
            <thead>
              <tr>
                <th>Sản phẩm</th>
                <th>Version</th>
                <th>Phòng Sản Xuất</th>
                <th>Thời Gian Sản Xuất</th>
                <th>Người lập</th>
                <th>Ngày lập</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody>
              {historyData.map((item, idx) => (
                <tr key={idx}>
                    <td> {item.title} </td>
                    <td>{item.version}</td>
                    <td>{item.room_name}</td>
                    <td> 
                        <span>{dayjs(item.start).format("DD/MM/YYYY HH:mm")}</span>
                        <br></br>
                        <span>{dayjs(item.end).format("DD/MM/YYYY HH:mm")}</span>
                         
                    </td>
                    <td> {item.schedualed_by} </td>
                    <td> {dayjs(item.schedualed_at).format("DD/MM/YYYY HH:mm")} </td>
                    <td> {item.type_of_change} </td>
                  
                </tr>
              ))}
            </tbody>
          </Table>


        </Modal.Body>
        <Modal.Footer>
          <Button variant="primary" onClick={handleClose}>
            Đóng
          </Button>
        </Modal.Footer>
      </Modal>
    </>
  );
}
