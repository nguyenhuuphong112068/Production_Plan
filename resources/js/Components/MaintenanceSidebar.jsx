import React, { useEffect, useRef, useState } from 'react';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Row, Col, Modal, Form } from 'react-bootstrap';
import { Checkbox } from 'primereact/checkbox';
import { Dropdown } from 'primereact/dropdown';
import './ModalSidebar.css';
import './MaintenanceSidebar.css';
import Swal from 'sweetalert2';
import axios from "axios";

const MaintenanceSidebar = ({ visible, onClose, waitPlan, setPlan, percentShow,
  setPercentShow, selectedRows, setSelectedRows, resources,
  currentPassword, userID, isMaintenance = true }) => {

  const wrapperRef = useRef(null);

  const [stageFilter] = useState(8); // Luôn là 8 cho bảo trì
  const [maintenanceType, setMaintenanceType] = useState('HC'); // HC, TB, TI
  const [visibleColumns, setVisibleColumns] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const sizes = ["close", "100%", "30%"];
  const [currentIndex, setCurrentIndex] = useState(1);
  const [tableData, setTableData] = useState([]);


  const columnWidths100 = {
    Inst_Name: '200px',
    Parent_Equip_id: '150px',
    Eqp_name: '200px',
    PM: '120px',
    related_room: '250px',
    name: '150px',
    note: '250px',
  };

  const columnWidths30 = {
    name: '150px',
    Inst_Name: '200px',
    PM: '100px',
  };

  useEffect(() => {
    if (waitPlan && waitPlan.length > 0) {
      let filtered = waitPlan.filter(event => Number(event.stage_code) === 8);

      filtered = filtered.filter(event => {
        if (!event.code) return false;
        const code = String(event.code);
        if (code.endsWith(`_${maintenanceType}`)) return true;
        if (maintenanceType === 'TB' && code.endsWith('_8')) return true;
        return false;
      });

      setTableData(filtered);
    } else {
      setTableData([]);
    }
  }, [waitPlan, maintenanceType]);

  useEffect(() => {
    let visibleCols = [];
    if (percentShow === "close") {
      setVisibleColumns([]);
      return;
    }

    if (percentShow === "100%") {
      const order = ["stt", "id", "Parent_Equip_id", "Eqp_name", "name", "Inst_Name", "related_room", "PM", "note"];
      visibleCols = order.map(f => {
        const col = allColumns.find(c => c.field === f);
        if (!col) return null;
        if (f === "id" && !isAdmin) return null; // Ẩn cột ID nếu không phải Admin
        let header = col.header;
        if (f === "name") header = "Mã Thiết Bị";
        if (f === "Parent_Equip_id") header = "Mã Thiết Bị Lớn";
        if (f === "Eqp_name") header = "Tên Thiết Bị Lớn";
        return {
          ...col,
          header,
          style: { ...col.style, minWidth: columnWidths100[f] || col.style?.width || 'auto', maxWidth: columnWidths100[f] || col.style?.width || 'auto' }
        };
      }).filter(Boolean);
    } else {
      visibleCols = allColumns.filter(col => ["stt", "name", "Inst_Name", "PM"].includes(col.field))
        .map(col => ({
          ...col,
          style: { ...col.style, minWidth: columnWidths30[col.field] || col.style?.width || 'auto', maxWidth: columnWidths30[col.field] || col.style?.width || 'auto' }
        }));
    }
    setVisibleColumns(visibleCols);
  }, [percentShow]);

  const handleToggle = () => {
    const nextIndex = (currentIndex + 1) % sizes.length;
    if (sizes[nextIndex] == "close") {
      setCurrentIndex(0)
      onClose(false)
    } else {
      setCurrentIndex(nextIndex);
      setPercentShow(sizes[nextIndex]);
      setSelectedRows([]);
    }
  };

  const naBody = (field) => (rowData) => rowData[field] || "NA";

  const indexBodyTemplate = (rowData, options) => {
    return options.rowIndex + 1;
  };

  const relatedRoomBodyTemplate = (rowData) => {
    if (!rowData.room_code) return "NA";
    return `${rowData.room_code} - ${rowData.room_name}`;
  }

  const isAdmin = (userID == 1 || userID == 5);

  const allColumns = [
    { rowReorder: true, style: { width: '3rem' }, headerStyle: { width: '3rem' } },
    { field: "stt", header: "STT", body: indexBodyTemplate, style: { width: '50px', textAlign: 'center' } },
    { field: "id", header: "ID", sortable: true, body: naBody("id"), visible: isAdmin, style: { width: '80px', textAlign: 'center' } },
    { field: "name", header: "Mã Thiết Bị", sortable: true, body: naBody("name") },
    { field: "Inst_Name", header: "Tên Thiết Bị", sortable: true, body: naBody("Inst_Name") },
    { field: "Parent_Equip_id", header: "Mã Thiết Bị Lớn", sortable: true, body: naBody("Parent_Equip_id") },
    { field: "Eqp_name", header: "Tên Thiết Bị Lớn", sortable: true, body: naBody("Eqp_name") },
    { field: "related_room", header: "Phòng sản xuất liên quan", sortable: true, body: relatedRoomBodyTemplate },
    { field: "PM", header: "Thời gian thực hiện", sortable: true, body: naBody("PM") },
    { field: "note", header: "Ghi chú", sortable: true, body: naBody("note") },
  ];

  const handleSelectionChange = (e) => {
    // Cho phép chọn bất kỳ hàng được chọn (đa chọn), hỗ trợ toggle bỏ chọn nếu đã chọn
    const selection = e.value ?? [];
    setSelectedRows(selection);
  };

  const handleRowReorder = (e) => {
    const { value: newData, dropIndex } = e;

    // Nếu chưa chọn gì hoặc chỉ kéo 1 dòng bình thường
    if (!selectedRows || selectedRows.length === 0) {
      const updateOrderData = newData.map((row, i) => ({ ...row, order_by: i + 1 }));
      const payload = updateOrderData.map(r => ({ code: r.code, order_by: r.order_by }));

      setTableData(updateOrderData);

      axios.put('/Schedual/updateOrder', { updateOrderData: payload })
        .then(res => {
          if (res.data.plan) setPlan(res.data.plan);
        })
        .catch(err => {
          Swal.fire({ icon: 'error', title: 'Lỗi sắp xếp' });
          console.error(err);
        });
      return;
    }

    // Xử lý kéo thả theo cụm (Group drag)
    const selectedIds = new Set(selectedRows.map(r => r.id));
    const selectedGroup = newData.filter(r => selectedIds.has(r.id));
    const nonSelected = newData.filter(r => !selectedIds.has(r.id));

    const removedBefore = newData.slice(0, dropIndex).filter(r => selectedIds.has(r.id)).length;
    const insertAt = Math.max(0, Math.min(nonSelected.length, dropIndex - removedBefore));

    const merged = [
      ...nonSelected.slice(0, insertAt),
      ...selectedGroup,
      ...nonSelected.slice(insertAt)
    ];

    const updateOrderData = merged.map((row, i) => ({ ...row, order_by: i + 1 }));
    const payload = updateOrderData.map(r => ({ code: r.code, order_by: r.order_by }));

    setTableData(updateOrderData);

    axios.put('/Schedual/updateOrder', { updateOrderData: payload })
      .then(res => {
        if (res.data.plan) setPlan(res.data.plan);
      })
      .catch(err => {
        Swal.fire({ icon: 'error', title: 'Lỗi sắp xếp cụm' });
        console.error(err);
      });
  };

  const handleDragOver = (e) => {
    const wrapper = wrapperRef.current?.querySelector(".p-datatable-wrapper");
    if (!wrapper) return;

    const rect = wrapper.getBoundingClientRect();
    const offset = 100; // vùng nhạy cảm cuộn
    const speed = 500;  // tốc độ cuộn

    if (e.clientY < rect.top + offset) wrapper.scrollTop -= speed;
    else if (e.clientY > rect.bottom - offset) wrapper.scrollTop += speed;
  };

  return (

    <div
      ref={wrapperRef}
      onDragOver={handleDragOver}
      className="maintenance-sidebar-container bg-white shadow-lg"
      style={{ height: "600px", overflow: "hidden" }}>

      <div id="external-events"
        className={`absolute right-0 h-100 z-50 transition-transform duration-300 bg-white ${visible ? 'translate-x-0' : 'translate-x-full'}`}
        style={{
          width: percentShow,
          maxWidth: "100%",
          boxShadow: "-2px 0 15px rgba(0,0,0,0.15)",
          display: "flex",
          flexDirection: "column",
          top: "40px"
        }}
      >
        {/* Header Section */}
        {/* Header Section */}
        <div className="p-2 border-bottom bg-light">
          <Row className="align-items-center g-2 m-0">
            {/* Cột trái: Tìm kiếm */}
            <Col md={4} className="d-flex align-items-center">
              <div className="maintenance-search-wrap flex-grow-1 w-full m-0">
                <i className="pi pi-search"></i>
                <InputText
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  placeholder="Tìm máy, thiết bị..."
                  className="p-inputtext-sm w-full"
                />
              </div>
            </Col>

            {/* Cột giữa: Bộ lọc loại bảo trì */}
            <Col md={6}>
              <div className="maintenance-type-selector shadow-sm m-0">
                <Button
                  label="Hiệu Chuẩn"
                  icon="pi pi-check-circle"
                  badge={String(waitPlan?.filter(e => Number(e.stage_code) === 8 && e.code?.endsWith('_HC')).length || 0)}
                  badgeClassName="maintenance-badge-hc"
                  className={`p-button-sm ${maintenanceType === 'HC' ? 'p-button-info' : 'p-button-outlined p-button-secondary'}`}
                  onClick={() => setMaintenanceType('HC')}
                  style={{ flex: 1 }}
                />
                <Button
                  label="Bảo Trì"
                  icon="pi pi-cog"
                  badge={String(waitPlan?.filter(e => Number(e.stage_code) === 8 && (e.code?.endsWith('_TB') || e.code?.endsWith('_8'))).length || 0)}
                  badgeClassName="maintenance-badge-tb"
                  className={`p-button-sm ${maintenanceType === 'TB' ? 'p-button-warning' : 'p-button-outlined p-button-secondary'}`}
                  onClick={() => setMaintenanceType('TB')}
                  style={{ flex: 1 }}
                />
                <Button
                  label="Tiện Ích"
                  icon="pi pi-bolt"
                  badge={String(waitPlan?.filter(e => Number(e.stage_code) === 8 && e.code?.endsWith('_TI')).length || 0)}
                  badgeClassName="maintenance-badge-ti"
                  className={`p-button-sm ${maintenanceType === 'TI' ? 'p-button-success' : 'p-button-outlined p-button-secondary'}`}
                  onClick={() => setMaintenanceType('TI')}
                  style={{ flex: 1 }}
                />
              </div>
            </Col>

            {/* Cột phải: Thao tác đóng/mở */}
            <Col md={2} className="d-flex justify-content-end gap-1">
              <Button icon="pi pi-arrows-h" className="p-button-rounded p-button-text p-button-secondary p-button-sm" onClick={handleToggle} />
              <Button icon="pi pi-times" className="p-button-rounded p-button-text p-button-danger p-button-sm" onClick={() => onClose(false)} />
            </Col>
          </Row>
        </div>

        {/* Bảng dữ liệu */}
        <div className="flex-grow-1 overflow-hidden p-2">
          <DataTable
            value={tableData}
            selection={selectedRows}
            onSelectionChange={handleSelectionChange}
            onRowReorder={handleRowReorder}
            reorderableRows
            globalFilter={searchTerm}
            scrollable
            scrollHeight="flex"
            className="maintenance-datatable p-datatable-gridlines p-datatable-sm shadow-sm"
            selectionMode="multiple"
            dataKey="id"
          >
            <Column rowReorder style={{ width: '3rem' }} />
            <Column selectionMode="multiple" headerStyle={{ width: '3rem' }}></Column>

            {/* Cột Kéo thả ra lịch */}
            <Column
              header="-"
              body={(rowData) => (
                <div
                  className="fc-event cursor-move px-2 py-1 bg-blue-100 border border-blue-400 rounded text-sm text-center"
                  draggable="true"
                  onClick={() => {
                    // Tự động chọn nhóm khi click vào icon kéo thả
                    const targetRoom = rowData.room_code;
                    const targetType = rowData.code ? String(rowData.code).slice(-2) : "";
                    const sameGroup = waitPlan.filter((row) => {
                      const rowType = row.code ? String(row.code).slice(-2) : "";
                      return row.room_code === targetRoom && rowType === targetType;
                    });
                    setSelectedRows(sameGroup);
                  }}
                >
                  <i className="fas fa-arrows-alt"></i>
                </div>
              )}
              style={{ width: "60px", textAlign: "center" }}
            />
            {visibleColumns.map((col, i) => (
              <Column
                key={i}
                field={col.field}
                header={col.header}
                sortable={col.sortable}
                body={col.body}
                style={col.style}
              />
            ))}
          </DataTable>
        </div>



      </div>
    </div>
  );
};

export default MaintenanceSidebar;
