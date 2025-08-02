import React, { useState } from 'react';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';

import 'primereact/resources/themes/saga-blue/theme.css';
import 'primereact/resources/primereact.min.css';
import 'primeicons/primeicons.css';

const ModalSidebar = ({ visible, onClose, events = [] }) => {
  const [selectedRows, setSelectedRows] = useState([]);

  // Xử lý khi bắt đầu kéo
  const handleDragStart = (e, rowData) => {
    // Truyền dữ liệu kiểu text/plain, hoặc JSON nếu cần
    e.dataTransfer.setData('text/plain', JSON.stringify(rowData));
  };

  return (
    <div id = "external-events"
      className={`fixed top-0 left-0 h-full z-50 transition-transform duration-300 bg-white ${
        visible ? 'translate-x-0' : '-translate-x-full'
      }`}
      style={{ width: '30%', boxShadow: '2px 0 10px rgba(0,0,0,0.3)' }}
    >
      <div className="p-4 border-b flex justify-between items-center">
        <h2 className="text-lg font-semibold">Danh sách lịch</h2>
        <button><i className="pi pi-expand"></i></button>
        <button onClick={onClose} className="text-gray-600 hover:text-red-500 text-xl">&times;</button>
      </div>

      <DataTable
        value={events}
        selection={selectedRows}
        onSelectionChange={(e) => setSelectedRows(e.value)}
        selectionMode="multiple"
        dataKey="id"
        paginator
        rows={5}
      >
          

        <Column selectionMode="multiple" headerStyle={{ width: '3em' }} />

        <Column field="title" header="Sản Phẩm - Số lô" />
        <Column field="duration" header="Mực Ưu tiên" />
        <Column field="expertedDate" header="Ngày DK KCS" />
        <Column field="resourceIdGroup" header="Thiết Bị" />

        {/* Cột kéo thả */}
        <Column
          header="Kéo"
          body={(rowData) => (
            <div
              className="fc-event cursor-move px-2 py-1 bg-blue-100 border border-blue-400 rounded text-sm text-center"
              draggable="true"
              data-id={rowData.id}
              data-title={rowData.title}
              data-duration={rowData.duration}
              data-plan_stage_code={rowData.plan_stage_code}
              data-expertedDate={rowData.expertedDate}
              data-resourceIdGroup={rowData.resourceIdGroup}
              onDragStart={(e) => handleDragStart(e, rowData)}
            >
              ⬍
            </div>
          )}
          style={{ width: '60px', textAlign: 'center' }}
        />
      </DataTable>

      {selectedRows.length > 0 && (
        <div className="mt-4 p-2">
          <h3 className="font-semibold">Bạn đã chọn:</h3>
          <ul className="list-disc ml-6">
            {selectedRows.map((row) => (
              <li key={row.id}>{row.title}</li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
};

export default ModalSidebar;
