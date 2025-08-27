
import React, { useEffect, useState } from 'react';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Row, Col, Modal, Form } from 'react-bootstrap';
import { Checkbox } from 'primereact/checkbox';
import { router } from '@inertiajs/react';
import './ModalSidebar.css'
import { InputSwitch } from 'primereact/inputswitch';
import Swal from 'sweetalert2'; 

const ModalSidebar = ({ visible, onClose, events = [], percentShow, setPercentShow }) => {

  const [selectedRows, setSelectedRows] = useState([]);
  const [stageFilter, setStageFilter] = useState(1);
  const [visibleColumns, setVisibleColumns] = useState([]);
  const [searchTerm, setSearchTerm] = useState(""); 
  const sizes = ["20%", "30%", "100%", "close"];
  const [currentIndex, setCurrentIndex] = useState(0);
  const [tableData, setTableData] = useState(events);
  const [showModalCreate, setShowModalCreate] = useState(false); 
  const [orderPlan, setOrderPlan] = useState({checkedClearning: false, title: null});


  useEffect(() => {
      setTableData(events); 
  }, [events]);

  useEffect(() => {
      if (percentShow === "100%") {

        setVisibleColumns(allColumns);
      } else if (percentShow === "30%") {

        setVisibleColumns(allColumns.filter(col => ["name", "batch", "level"].includes(col.field)));
      } else {
        setVisibleColumns(allColumns.filter(col => ["name", "batch"].includes(col.field)));
      }
  }, [percentShow]);

  const handleToggle = () => {
    
    const nextIndex = (currentIndex + 1) % sizes.length;
    
    if (sizes[nextIndex] == "close"){
      setCurrentIndex(0)
      onClose (false)
    }else {    
      setCurrentIndex(nextIndex);
      setPercentShow(sizes[nextIndex]);
      setSelectedRows([]);}

  };

  const statusOrderBodyTemplate = (rowData) => {
    const colors = {
      1: { backgroundColor: "#f44336", color: "white" },
      2: { backgroundColor: "#ff9800", color: "white" },
      3: { backgroundColor: "blue", color: "white" },
      4: { backgroundColor: "#4caf50", color: "white" },
    };

    const style = {
      display: "inline-block",
      padding: "6px 16px",
      width: "40px",
      borderRadius: "20px",
      textAlign: "center",
      ...colors[rowData.level],
    };

    return <span style={style}><b>{rowData.level}</b></span>;
  };

  const ValidationBodyTemplate = (rowData) => (
    <Checkbox checked={rowData.is_val ? true : false} />
  );

  const weightPBodyTemplate = (rowData) => (
    <div style={{ display: "flex", flexDirection: "column" }}>
      <span>{rowData.after_weigth_date || ''}</span>
      <span>{rowData.before_weigth_date || ''}</span>
    </div>
  );

  const packagingBodyTemplate = (rowData) => (
    <div style={{ display: "flex", flexDirection: "column" }}>
      <span>{rowData.after_parkaging_date || ''}</span>
      <span>{rowData.before_parkaging_date || ''}</span>
    </div>
  );

  const productCodeBody = (rowData) => {
    return (
      <div style={{ display: "flex", flexDirection: "column" }}>
        <span>{rowData.intermediate_code || ''}</span>
        <span>{rowData.finished_product_code || ''}</span>
      </div>
    );
  };

  const stringToColor = (str) => {
      let hash = 0;
      for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
      }
      let color = "#";
      for (let i = 0; i < 3; i++) {
        const value = (hash >> (i * 8)) & 0xff;
        color += ("00" + value.toString(16)).slice(-2);
      }
      return color;
    };

  const campaignCodeBody = (rowData) => {
    const color = stringToColor(rowData.campaign_code || "");
    return (
      <div
        style={{
          backgroundColor: color + "33", // màu nhạt (33 = alpha ~20%)
          color: "#000",
          padding: "2px 6px",
          borderRadius: "6px",
          textAlign: "center",
          fontWeight: 600,
          border: `1px solid ${color}`,
          display: `${rowData.campaign_code?'block':'none'}`
        }}
      >
        {rowData.campaign_code}
      </div>
    );
  };
  
  const stageNames = {
    1: 'Cân', 2: 'NL Khác', 3: 'Pha Chế', 4: 'Trộn Hoàn Tất',
    5: 'Định Hình', 6: 'Bao Phim', 7: 'ĐGSC-ĐGTC', 8: 'Hiệu Chuẩn - Bảo Trì', 9: 'Kế Hoạch Khác',
  };

  const handleSelectionChange = (e) => {
      const currentSelection = e.value;
      if (percentShow !== "100%"){    
        if (currentSelection.length <= 1) {
          setSelectedRows(currentSelection.map(ev => ({ ...ev, isMulti: false })));
          return;
        }
        const firstCode = currentSelection[0].intermediate_code;
        const allSame = currentSelection.every((row) => row.intermediate_code === firstCode);
        if (allSame) {
          setSelectedRows(currentSelection.map(ev => ({ ...ev, isMulti: true })));
        } else {
          const lastSelected = currentSelection[currentSelection.length - 1];
          setSelectedRows([{ ...lastSelected, isMulti: false }]);
        }}
      else {
          setSelectedRows(currentSelection.map(ev => ({ ...ev, isMulti: false })));
      }

  };

  const handlePrevStage = () =>  {setStageFilter((prev) => Math.max(1, prev - 1)); setSelectedRows([])}
  const handleNextStage = () => {setStageFilter((prev) => Math.min(9, prev + 1)); console.log (tableData); setSelectedRows([])}

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

  const handleRowReorder = (e) => {
    const { value: newData, dropIndex, dragIndex } = e;

    // Nếu chưa chọn gì thì chỉ cần đánh lại order_by
    if (!selectedRows || selectedRows.length === 0) {
      // Đánh lại order_by liên tục
      const updateOrderData = newData.map((row, i) => ({
        ...row,
        order_by: i + 1
      }));

      // Payload nhẹ hơn chỉ gồm code + order_by
      const payload = updateOrderData.map(r => ({
        code: r.code,
        order_by: r.order_by
      }));

      // Cập nhật state
      setTableData(updateOrderData);

      // Gửi lên server
      router.put('/Schedual/updateOrder', { updateOrderData: payload }, {
        onSuccess: () => console.log('Đã cập nhật thứ tự'),
        onError: (errors) => console.error('Lỗi cập nhật', errors),
      });

      return;
    }

    const selectedIds = new Set(selectedRows.map(r => r.id));

    // 1) Cụm các dòng đã chọn theo thứ tự đang hiển thị trong newData
    const selectedGroup = newData.filter(r => selectedIds.has(r.id));

    // 2) Danh sách còn lại (đã bỏ các dòng selected)
    const nonSelected = newData.filter(r => !selectedIds.has(r.id));

    // 3) Tính vị trí chèn trong mảng nonSelected để khi ghép lại
    //    cụm selectedGroup sẽ xuất hiện tại đúng dropIndex trong toàn bảng.
    //    Vì đã loại bỏ selected ra khỏi newData, các vị trí phía trước dropIndex
    //    bị "tụt" đi bằng số phần tử selected ở trước đó.
    const removedBefore = newData
      .slice(0, dropIndex)
      .filter(r => selectedIds.has(r.id)).length;

    const insertAt = Math.max(0, Math.min(nonSelected.length, dropIndex - removedBefore));

    // 4) Ghép lại: [nonSelected trước insertAt] + [selectedGroup] + [nonSelected sau insertAt]
    const merged = [
      ...nonSelected.slice(0, insertAt),
      ...selectedGroup,
      ...nonSelected.slice(insertAt)
    ];

    // 5) Đánh lại order_by liên tục
    const updateOrderData = merged.map((row, i) => ({ ...row, order_by: i + 1 }));

    // (Nếu bạn chỉ muốn payload nhẹ gửi server)
    const payload = updateOrderData.map(r => ({
      code: r.code,
      order_by: r.order_by
    }));

    setTableData(updateOrderData);


    router.put('/Schedual/updateOrder', { updateOrderData: payload }, {
      onSuccess: () => console.log('Đã cập nhật thứ tự'),
      onError: (errors) => console.error('Lỗi cập nhật', errors),
    });

  };

  const handleCreateManualCampain = (e) => {
      const filteredRows = selectedRows.map(row => ({
        id: row.id,
        plan_master_id: row.plan_master_id,
        product_caterogy_id: row.product_caterogy_id,
        predecessor_code: row.predecessor_code,
        campaign_code: row.campaign_code,
        code: row.code,
      }));

      router.put('/Schedual/createManualCampain', { data: filteredRows }, {
        onSuccess: () =>  console.log('Đã cập nhật thứ tự'),
        onError: (errors) => console.error('Lỗi cập nhật', errors),
      });

      setSelectedRows ([]);
      return;
  }

  const handleCreateAutoCampain = () => {
      router.put('/Schedual/createAutoCampain', {
        onSuccess: () => {
            Swal.fire({
              title: 'Thành công!',
              text: 'Đã cập nhật thứ tự.',
              icon: 'success',
              confirmButtonText: 'OK'
            }).then(() => {
              setSelectedRows([]);
            });
          },
          onError: (errors) => {
            Swal.fire({
              title: 'Lỗi!',
              text: 'Có lỗi khi cập nhật: ' + (errors?.message || ''),
              icon: 'error',
              confirmButtonText: 'Đóng'
            });
            console.error('Lỗi cập nhật', errors);
          },
        });

  }

  const handleCreateOrderPlan = () => {

      if (orderPlan.title === null){
          Swal.fire({
              title: 'Lỗi!',
              text: 'Nội Dung Kế Hoạch Không Để Trống ',
              icon: 'error',
              showConfirmButton: false,  // ẩn nút Đóng
              timer: 500 
            });

        return
      }

       router.put('/Schedual/createOrderPlan', orderPlan, {
        onSuccess: () => {
            setShowModalCreate (false)
            Swal.fire({
              title: 'Thành công!',
              text: 'Tạo Mới Kế Hoạch Khác',
              icon: 'success',
              showConfirmButton: false,
              timer: 1000 
            }).then(() => {
              setOrderPlan({});
            });
          },
          onError: (errors) => {
            Swal.fire({
              title: 'Lỗi!',
              text: 'Có lỗi khi cập nhật: ' + (errors?.message || ''),
              icon: 'error',
              confirmButtonText: 'Đóng'
            });
            console.error('Lỗi cập nhật', errors);
          },
        });

     
  }

    const naBody = (field) => (rowData) => {
      if (field === "name" && rowData.stage_code === 9) {
        return rowData.title ?? "NA";
      }
      return rowData[field] ?? "NA";
    };


    const allColumns = [
      { field: "intermediate_code", header: "Mã Sản Phẩm", sortable: true, body: productCodeBody },
      { field: "name", header: "Sản Phẩm", sortable: true, body: naBody("name") },
      { field: "batch", header: "Số Lô", sortable: true, body: naBody("batch") },
      { field: "expected_date", header: "Ngày DK KCS", body: naBody("expected_date") },
      { field: "market", header: "Thị Trường", sortable: true, body: naBody("market"),
        style: { width: '8rem', maxWidth: '8rem', whiteSpace: 'normal', wordBreak: 'break-word' } 
      },
      { field: "level", header: "Ưu tiên", sortable: true, body: statusOrderBodyTemplate },
      { field: "is_val", header: "Thẩm Định", body: ValidationBodyTemplate,
        style: { width: '5rem', maxWidth: '5rem', whiteSpace: 'normal', wordBreak: 'break-word' } 
      },
      { field: "weight_dates", header: "Cân NL", sortable: true, body: naBody("weight_dates") },
      { field: "pakaging_dates", header: "Đóng gói", sortable: true, body: naBody("pakaging_dates") },
      { field: "source_material_name", header: "Nguồn nguyên liệu", sortable: true, body: naBody("source_material_name"),
        style: { width: '25rem', maxWidth: '25rem', whiteSpace: 'normal', wordBreak: 'break-word' } 
      },
      { field: "campaign_code", header: "Mã Chiến Dịch", sortable: true, body: campaignCodeBody,
        style: { width: '8rem', maxWidth: '8rem', whiteSpace: 'normal', wordBreak: 'break-word' } 
      },
      { field: "note", header: "Ghi chú", sortable: true, body: naBody("note") },
    ];


  


  return (
    <div
      id="external-events"
      className={`fixed top-0 right-0 h-full z-50 transition-transform duration-300 bg-white ${visible ? 'translate-x-0' : 'translate-x-full'}`}
      style={{ width: percentShow, boxShadow: '2px 0 10px rgba(0,0,0,0.3)', display: 'flex', flexDirection: 'column' }}
    >
      {/* Thanh điều khiển */}
      <div className="p-4 border-b">
        <Row className="align-items-center">

          <Col md={3} className='d-flex justify-content-start'>
            <div
              className="fc-event cursor-move px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center mr-3"
              data-rows={JSON.stringify(selectedRows)}
              draggable="true"
              onDragStart={handleDragStart}  title="Tạo Lịch Với Các Sản Phẩm Đã Chọn">
              <i className="fas fa-arrows-alt"></i> ({selectedRows.length})
            </div>
          
          
            {percentShow === "100%" ? (
              <>

              <div className="fc-event px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Mã Chiến Dịch Với Các Sản Phẩm Đã Chọn"
              onClick={handleCreateManualCampain}>
              <i className="fas fa-flag"></i> ({selectedRows.length})
              </div>

              <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Mã Chiến Dịch tự Động"
              onClick={handleCreateAutoCampain}>
                <i className="fas fa-flag-checkered"></i>
              </div> 
              </>):<></>}
              
              {percentShow === "100%" && stageFilter === 9 ? (
              <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Kế Hoạch Khác"
                onClick={ () => setShowModalCreate (true)}>
                <i className="fas fa-plus"></i>
              </div> 
              ):<></>}

          </Col>

          <Col md={6}>
            <div className="p-inputgroup flex-1">
              <Button icon="pi pi-angle-double-left" className="p-button-success" onClick={handlePrevStage} disabled={stageFilter === 1} title="Chuyển Công Đoạn"/>
              {percentShow === "100%" ? (

              <InputText value={"Công Đoạn " + stageNames[stageFilter] + " - Còn " + 
                tableData.filter(event => Number(event.stage_code) === stageFilter)
                .filter(ev => (ev.name ?? "").toLowerCase().includes((searchTerm ?? "").toLowerCase())).length + " Sản Phẩm Chờ Sắp Lịch"} className="text-center  fw-bold" style={{ fontSize: '25px' , color: ' #CDC171'}} readOnly 
                /> 
              ):

              <InputText value={ stageNames[stageFilter]} className="text-center fw-bold" style={{ fontSize: '15px', color: ' #CDC171'}} readOnly />
              }

              <Button icon="pi pi-angle-double-right" className="p-button-success" onClick={handleNextStage} disabled={stageFilter === 9}  title="Chuyển Công Đoạn" />
            </div>
          </Col>
          <Col md={2} >
            {percentShow === "100%" ? (
              <InputText className='border'
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Tìm kiếm..."
                style={{ width: "100%" }}
              />):""}
          </Col>

          <Col md={1} className='d-flex justify-content-end'>
            {percentShow != "close" ? (
            <button
              onClick={handleToggle}
              className="fc-event cursor-pointer rounded text-lg text-center mr-2"
              style={{ fontSize: "1.5rem" }}
              title="Điều Chỉnh Độ Rộng Side Bar"
            >
              <img src="/img/iconstella.svg" style={{width: '30px', marginRight: '10px'}} />
              {/* <i className="pi pi-arrows-h"></i> */}
            </button>):""}
          </Col>

        </Row>

      </div>

      {/* Khu vực bảng */}
      <div style={{ flex: 1, overflow: 'auto' }}>
        <DataTable
          key={percentShow}
          value={tableData.filter(event => Number(event.stage_code) === stageFilter).filter(ev => (ev.name ?? "").toLowerCase().includes((searchTerm ?? "").toLowerCase()))}
          selection={selectedRows}
          onSelectionChange={handleSelectionChange}
          selectionMode="multiple"
          dataKey="id"
          size="medium"
          paginator paginatorPosition="bottom" rows={20} rowsPerPageOptions={[5, 10, 25, 50, 100, 500, 1000]}
          scrollable scrollHeight="calc(100vh - 160px)"
          columnResizeMode="expand" resizableColumns
          globalFilter={searchTerm} 
          reorderableColumns reorderableRows onRowReorder={handleRowReorder}
          
        >
           {percentShow === "100%" ? (
            <Column
            header="STT"
            body={(rowData, options) => options.rowIndex + 1}
            style={{ width: "60px", textAlign: "center" }}
          />):""}
          
          {/* Cột Kéo thả */}

          <Column selectionMode="multiple" headerStyle={{ width: '3em' }} />

          {percentShow === "100%" ? (
            <Column rowReorder style={{ width: '3rem' }} />) : (
            <Column
                  header="-"
                  body={(rowData) => (
                    <div
                      className="fc-event cursor-move px-2 py-1 bg-blue-100 border border-blue-400 rounded text-sm text-center"
                      draggable="true"
                      data-id={rowData.id}
                      data-title={`${rowData.name}-${rowData.batch}-${rowData.market}`}
                      data-intermediate_code={rowData.intermediate_code}
                      data-stage_code={rowData.stage_code}
                      data-expected-date={rowData.expected_date}
                    >
                      <i className="fas fa-arrows-alt"></i>
                    </div>
                  )}
                  style={{ width: "60px", textAlign: "center" }}
                />
          )}

        
          {visibleColumns.map(col => (
            <Column
              key={col.field}
              field={col.field}
              header={col.header}
              filter={col.filter}
              sortable={col.sortable}
              body={col.body}
              style={col.style}
            />
          ))}
        </DataTable>


      </div>
      
      <Modal size="lg" show={showModalCreate} aria-labelledby="example-modal-sizes-title-lg" onHide = {() => setShowModalCreate (false)}>
                <Modal.Header style={{color:'#cdc717', backgroundColor: '#ffffffff'}}>
                  <img src="/img/iconstella.svg" style={{width: '30px', marginRight: '10px'}} /> 
                  <Modal.Title 
                      id="example-modal-sizes-title-lg" 
                      className="mx-auto fw-bold"
                  >
                      Tạo Mới Kế Hoạch Khác
                  </Modal.Title>
                </Modal.Header>
                <Modal.Body  style={{fontSize:'20px'}}>
                  <Form>
                    <Row className="mb-3">
                          <Form.Group as={Col} >
                          <Form.Label>Tên Kế Hoạch</Form.Label>
                          <Form.Control type="text" name ='fullName' value = {orderPlan.title?? ""} onChange={(e) => setOrderPlan({...orderPlan, title: e.target.value})}   placeholder="Bắt buộc" />
                          </Form.Group>

                    </Row>

                    <Row className="mb-3">
                          <Form.Group as={Col} >
                            <div className='text-center d-flex justify-content-start'>
                              <Form.Label className='mr-5'>Có Vệ Sinh Lại Không: </Form.Label>
                              <span className='ml-5 mr-5'> Có </span>
                              <InputSwitch checked={orderPlan.checkedClearning?? ""} onChange={(e) => setOrderPlan({...orderPlan, checkedClearning : !orderPlan.checkedClearning})} />
                              <span className='ml-5 mr-5'> Không </span>
                            </div>
                          </Form.Group>     
                    </Row> 
                  </Form>

                </Modal.Body>

                <Modal.Footer >
                    <Button className='btn btn-primary' onClick={() => handleCreateOrderPlan (false)}> 
                        Lưu
                    </Button>
                    <Button  className='btn btn-primary' onClick={() => setShowModalCreate (false)}>
                        Hủy
                    </Button>
                </Modal.Footer>
      </Modal>
    </div>
  );
};

export default ModalSidebar;




