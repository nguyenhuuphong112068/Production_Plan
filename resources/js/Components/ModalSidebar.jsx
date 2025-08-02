// resources/js/Components/ModalSidebar.jsx

import React from 'react';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';

import 'primereact/resources/themes/saga-blue/theme.css'; // or any theme you like
import 'primereact/resources/primereact.min.css';
import 'primeicons/primeicons.css';

const ModalSidebar = ({ visible, onClose, events = [] }) => {
  return (
    <div className={`fixed top-0 left-0 h-full z-50 transition-transform duration-300 bg-white ${visible ? 'translate-x-0' : '-translate-x-full'}`} style={{ width: '30%', boxShadow: '2px 0 10px rgba(0,0,0,0.3)' }}>
      <div className="p-4 border-b flex justify-between items-center">
        <h2 className="text-lg font-semibold">Danh sách lịch</h2>
        <button onClick={onClose} className="text-gray-600 hover:text-red-500 text-xl">&times;</button>
      </div>

      <div className="p-4 overflow-auto" style={{ height: 'calc(100% - 60px)' }}>
        <DataTable value={events} paginator rows={5} showGridlines responsiveLayout="scroll">
          <Column field="title" header="Tiêu đề"></Column>
          <Column field="start" header="Bắt đầu"></Column>
          <Column field="end" header="Kết thúc"></Column>
          <Column field="resourceId" header="Phòng"></Column>
        </DataTable>
      </div>
    </div>
  );
};

export default ModalSidebar;
