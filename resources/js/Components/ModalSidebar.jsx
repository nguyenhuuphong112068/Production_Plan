import React, { useState } from 'react';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';


import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Container, Row, Col } from 'react-bootstrap';
const ModalSidebar = ({ visible, onClose, events = [] }) => {
  const [selectedRows, setSelectedRows] = useState([]);
  const [percentShow, setPercentShow] = useState("30%");
  const [stageFilter, setStageFilter] = useState(1)

 
  const filteredEvents = events.filter((event) => Number(event.stage_code) === stageFilter);

    const stageNames = {
      1: 'Cân',
      2: 'NL Khác',
      3: 'Pha Chế',
      4: 'Trộn Hoàn Tất',
      5: 'Định Hình',
      6: 'Bao Phim',
      7: 'Đóng Đói',
    };

  const handleSelectionChange = (e) => {
    const currentSelection = e.value;

    // Nếu chỉ có 1 dòng thì không cần kiểm tra
    if (currentSelection.length <= 1) {
      setSelectedRows(currentSelection);
      return;
    }

    // Lấy mã intermediate_code của dòng đầu tiên
    const firstCode = currentSelection[0].intermediate_code;

    // Kiểm tra xem tất cả dòng đều cùng mã
    const allSame = currentSelection.every(
      (row) => row.intermediate_code === firstCode
    );

    if (allSame) {
      setSelectedRows(currentSelection);
    } else {
      // Nếu không giống, chỉ giữ lại dòng cuối cùng vừa chọn
      const lastSelected = currentSelection[currentSelection.length - 1];
      setSelectedRows([lastSelected]);
    }
  };


  const handlePrevStage = () => {
    setStageFilter((prev) => Math.max(1, prev - 1));
  };
 
  const handleNextStage = () => {
    setStageFilter((prev) => Math.min(7, prev + 1));
  };


  const handleDragStart = (e) => {

    if (selectedRows.length === 0) return;

    const data = selectedRows.map((row) => ({
      id: row.id,
      title: `${row.name}-${row.batch}-${row.market}`,
      intermediate_code: row.intermediate_code,
      stage_code: row.stage_code,
      expected_date: row.expected_date,
    }));


    e.dataTransfer.setData("application/json", JSON.stringify(data));
  };


  return (
    <div id = "external-events"

      className={`fixed top-0 right-0 h-full z-50 transition-transform duration-300 bg-white ${
        visible ? 'translate-x-0' : 'translate-x-full'
      }`}
      style={{ width: percentShow, boxShadow: '2px 0 10px rgba(0,0,0,0.3)' }}
    >

      <div
        className="fc-event cursor-move px-2 py-1 bg-green-100 border border-green-400 rounded text-sm text-center m-2"
        draggable="true"
        data-rows={JSON.stringify(selectedRows)}
        onDragStart={handleDragStart}
      >
        ⬍ Kéo các mục đã chọn ({selectedRows.length})
      </div>


      <div className="p-4 border-b flex justify-between items-center">
        <Container>
          <Row>

            <Col md={1} >
                <button onClick={()=> {setPercentShow((prev) => (prev === '30%' ? '100%' : '30%'))}} className="text-gray-600 right" ><i className="pi pi-expand"></i></button>
            </Col>

            <Col md={1} ></Col>
              <Col md={8} >
                <div className="p-inputgroup flex-1">
                  <Button icon="pi pi-angle-double-left" className="p-button-success" onClick={handlePrevStage} disabled={stageFilter === 1} />
                  <InputText  value={stageNames[stageFilter]} className="text-center" style={{ fontSize: '18px' }} readOnly />
                  <Button icon="pi pi-angle-double-right" className="p-button-success" onClick={handleNextStage} disabled={stageFilter === 7} />
                </div>
              </Col>
            <Col md={2} >
                
            </Col>
          </Row>
        </Container>
      </div>
    
       <DataTable
        value={filteredEvents}
        selection={selectedRows}
        onSelectionChange={handleSelectionChange}
        selectionMode="multiple"
        dataKey="id"
        paginator
        rows={20}
        > 

         {/* Cột kéo thả */}
        <Column
          header="Kéo"
          body={(rowData) => (
            <div
              className="fc-event cursor-move px-2 py-1 bg-blue-100 border border-blue-400 rounded text-sm text-center"
              draggable="true"
              data-id={rowData.id}
              data-title={rowData.name + "-" + rowData.batch + "-" + rowData.market}
              data-intermediate_code={rowData.intermediate_code}
              data-stage={rowData.stage_code}
              data-expected-date={rowData.expected_date}
             
            >
              ⬍
            </div>
          )}
          style={{ width: '60px', textAlign: 'center' }}
        />
        
        <Column selectionMode="multiple" headerStyle={{ width: '3em' }} />
        <Column field="intermediate_code" header="Mã Sản Phẩm" filter sortable/>
        <Column field="name" header="Sản Phẩm" filter sortable/>
        <Column field="batch" header="Mực Ưu tiên" filter sortable/>
        <Column field="expected_date" header="Ngày DK KCS" filter />
        <Column field="market" header="Thị Trường" filter sortable/>


       
      </DataTable>

  
    </div>



  );
};

export default ModalSidebar;
