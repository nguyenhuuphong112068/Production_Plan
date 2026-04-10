import React, { useEffect, useRef, useState } from 'react';
import moment from 'moment';

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
  currentPassword, userID, userGroup, userGroupName, production, userDepartment, isMaintenance = true, maintenanceType, setMaintenanceType }) => {

  const wrapperRef = useRef(null);

  const [stageFilter] = useState(8); // Luôn là 8 cho bảo trì
  const [visibleColumns, setVisibleColumns] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const sizes = ["close", "100%", "30%"];
  const [currentIndex, setCurrentIndex] = useState(1);
  const [tableData, setTableData] = useState([]);
  const [isResizing, setIsResizing] = useState(false);

  useEffect(() => {
    const handleMouseMove = (e) => {
      if (!isResizing) return;
      const newWidth = window.innerWidth - e.clientX;
      if (newWidth > 350 && newWidth < window.innerWidth - 250) {
        setPercentShow(`${newWidth}px`);
      }
    };

    const handleMouseUp = () => {
      setIsResizing(false);
      document.body.style.cursor = 'default';
      // Hiệu ứng hoàn thành resize
    };

    if (isResizing) {
      window.addEventListener('mousemove', handleMouseMove);
      window.addEventListener('mouseup', handleMouseUp);
      document.body.style.cursor = 'ew-resize';
      document.body.style.userSelect = 'none'; // Chặn bôi đen khi kéo
    } else {
      document.body.style.userSelect = 'auto';
    }

    return () => {
      window.removeEventListener('mousemove', handleMouseMove);
      window.removeEventListener('mouseup', handleMouseUp);
    };
  }, [isResizing, setPercentShow]);


  const columnWidths100 = {
    Inst_Name: '180px',
    Parent_Equip_id: '130px',
    Eqp_name: '180px',
    PM: '100px',
    room_codes: '100px',
    expected_date: '120px',
    name: '130px',
    note: '200px',
  };

  const columnWidths30 = {
    Parent_Equip_id: '120px',
    Eqp_name: '150px',
    name: '100px',
    Inst_Name: '150px',
    PM: '100px',
    room_codes: '100px',
    expected_date: '100px',
  };

  useEffect(() => {
    if (Array.isArray(waitPlan) && waitPlan.length > 0) {
      let filtered = waitPlan.filter(event => event && Number(event.stage_code) === 8);

      // Role-based filtering
      const roles = Array.isArray(userGroup) ? userGroup : (userGroup ? [userGroup] : []);
      const isAdminOrSchedualer = roles.some(role => ['Admin', 'Schedualer'].includes(role));
      const isLeader = roles.includes('Leader');

      // PXV1: Leader chỉ thấy theo tổ của mình. 
      // Các xưởng khác: Leader thấy toàn bộ như Admin/Schedualer
      if (!isAdminOrSchedualer && isLeader && userGroupName && production === 'PXV1') {
        filtered = filtered.filter(event => {
          const rooms = event.related_rooms || [];
          // So sánh production_group của phòng với groupName của user
          return rooms.some(room => room && room.production_group === userGroupName);
        });
      }

      filtered = filtered.filter(event => {
        if (!event || !event.code) return false;
        const code = String(event.code);
        if (code.endsWith(`_${maintenanceType}`)) return true;
        if (maintenanceType === 'TB' && code.endsWith('_8')) return true;
        return false;
      });

      const withRoomCodes = filtered.map(item => ({
        ...item,
        room_codes: (item.related_rooms || []).map(r => r ? r.room_code : '').filter(Boolean).join(', ')
      }));

      setTableData(withRoomCodes);
    } else {
      setTableData([]);
    }
  }, [waitPlan, maintenanceType, userGroup, userGroupName, production]);

  useEffect(() => {
    let visibleCols = [];
    if (percentShow === "close") {
      setVisibleColumns([]);
      return;
    }

    const currentWidthPx = typeof percentShow === 'string' && percentShow.includes('px')
      ? parseInt(percentShow)
      : (percentShow === '100%' ? window.innerWidth : window.innerWidth * 0.3);

    if (currentWidthPx > window.innerWidth * 0.6) { // Chế độ xem rộng
      const order = ["stt", "id", "room_codes", "Parent_Equip_id", "Eqp_name", "name", "Inst_Name", "PM", "expected_date", "note"];
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
      const order = ["room_codes", "Parent_Equip_id", "Eqp_name", "name", "Inst_Name", "expected_date", "PM"];
      visibleCols = order.map(f => {
        const col = allColumns.find(c => c.field === f);
        if (!col) return null;
        let header = col.header;
        if (f === "name") header = "Mã Thiết Bị";
        if (f === "room_codes") header = "Phòng SX";
        if (f === "PM") header = "TG thực hiện";
        if (f === "expected_date") header = "Hạn BT";
        if (f === "Parent_Equip_id") header = "Mã Thiết Bị Lớn";
        if (f === "Eqp_name") header = "Tên Thiết Bị Lớn";

        return {
          ...col,
          header,
          style: { ...col.style, minWidth: columnWidths30[f] || col.style?.width || 'auto', maxWidth: columnWidths30[f] || col.style?.width || 'auto' }
        };
      }).filter(Boolean);
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
      const newSize = sizes[nextIndex] === "100%" ? `calc(100vw - 250px)` : sizes[nextIndex];
      setPercentShow(newSize);
      setSelectedRows([]);
    }
  };

  const naBody = (field) => (rowData) => rowData[field] || "NA";

  const indexBodyTemplate = (rowData, options) => {
    return options.rowIndex + 1;
  };

  const relatedRoomBodyTemplate = (rowData) => {
    const rooms = rowData.related_rooms || [];
    if (rooms.length === 0) return <span className="text-muted">NA</span>;

    return (
      <div className="d-flex flex-wrap gap-1">
        {rooms.map((room, idx) => (
          <div key={idx} className="room-badge" title={room.room_name}>
            {room.room_code}
          </div>
        ))}
      </div>
    );
  };

  const isAdmin = (userID == 1 || userID == 5);

  const expectedDateBodyTemplate = (rowData) => {
    if (!rowData.expected_date) return <span className="text-muted">NA</span>;

    const today = moment().startOf('day');
    const expDate = moment(rowData.expected_date).startOf('day');
    const diff = expDate.diff(today, 'days');

    let className = "";
    // User requested: Expected Date >= Today (Đỏ), Distance < 7 days (Vàng)
    // Most logical interpretation for maintenance:
    // Red: Past due or Today (diff <= 0)
    // Yellow: Upcoming within 7 days (0 < diff < 7)
    if (diff <= 0) {
      className = "expected-date future";
    } else if (diff < 7) {
      className = "expected-date near";
    }

    return (
      <div className={className} style={{ textAlign: 'center', fontWeight: className ? 'bold' : 'normal', borderRadius: '4px' }}>
        {moment(rowData.expected_date).format('DD/MM/YYYY')}
      </div>
    );
  };

  const rowClassName = (rowData) => {
    if (!rowData.expected_date) return '';
    const today = moment().startOf('day');
    const expDate = moment(rowData.expected_date).startOf('day');
    const diff = expDate.diff(today, 'days');

    if (diff <= 0) return 'row-maintenance-critical';
    if (diff < 7) return 'row-maintenance-warning';
    return '';
  };

  const allColumns = [
    // { rowReorder: true, style: { width: '3rem' }, headerStyle: { width: '3rem' } },
    { field: "stt", header: "STT", body: indexBodyTemplate, style: { width: '50px', textAlign: 'center' } },
    { field: "id", header: "ID", sortable: true, body: naBody("id"), visible: isAdmin, style: { width: '80px', textAlign: 'center' } },
    { field: "room_codes", header: "Phòng sản xuất liên quan", sortable: true, body: relatedRoomBodyTemplate },
    { field: "Parent_Equip_id", header: "Mã Thiết Bị Lớn", sortable: true, body: naBody("Parent_Equip_id") },
    { field: "Eqp_name", header: "Tên Thiết Bị Lớn", sortable: true, body: naBody("Eqp_name") },
    { field: "name", header: "Mã Thiết Bị", sortable: true, body: naBody("name") },
    { field: "Inst_Name", header: "Tên Thiết Bị", sortable: true, body: naBody("Inst_Name") },
    { field: "expected_date", header: "Hạn Bảo Trì", sortable: true, body: expectedDateBodyTemplate },
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
      className="maintenance-sidebar-container">

      <div id="external-events"
        className={`fixed right-0 h-full z-50 bg-white shadow-2xl ${visible ? 'translate-x-0' : 'translate-x-full'} transition-transform duration-300`}
        style={{
          width: percentShow,
          maxWidth: "calc(100vw - 250px)",
          display: "flex",
          flexDirection: "column",
          top: "40px",
          height: 'calc(100vh - 40px)',
          transition: isResizing ? 'none' : 'transform 0.3s ease-in-out, width 0.3s ease-in-out'
        }}
      >
        {/* Resize Handle */}
        <div
          className="maintenance-resizer"
          style={{
            position: 'absolute',
            left: 0,
            top: 0,
            width: '8px',
            height: '100%',
            cursor: 'ew-resize',
            zIndex: 1000,
            backgroundColor: isResizing ? '#3b82f6' : 'transparent'
          }}
          onMouseDown={(e) => {
            e.preventDefault();
            setIsResizing(true);
          }}
        />
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
            rowClassName={rowClassName}
            tableStyle={{
              minWidth: (typeof percentShow === 'string' && parseInt(percentShow) > window.innerWidth * 0.5) || percentShow === '100%'
                ? "1300px" : "850px"
            }}
            paginator
            rows={10}
            rowsPerPageOptions={[5, 10, 20, 50, 100, 500]}
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Đang xem {first} đến {last} trong tổng số {totalRecords} mục"
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
