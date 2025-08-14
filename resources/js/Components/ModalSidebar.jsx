
import React, { useEffect, useState } from 'react';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Container, Row, Col } from 'react-bootstrap';
import { Tag } from 'primereact/tag';
import { Checkbox } from 'primereact/checkbox';

const ModalSidebar = ({ visible, onClose, events = [], percentShow, setPercentShow }) => {
  const [selectedRows, setSelectedRows] = useState([]);
  const [stageFilter, setStageFilter] = useState(1);
  const [visibleColumns, setVisibleColumns] = useState([]);
  const sizes = ["15%", "30%", "100%"];
  const [currentIndex, setCurrentIndex] = useState(1);

  const handleToggle = () => {
    const nextIndex = (currentIndex + 1) % sizes.length;
    setCurrentIndex(nextIndex);
    setPercentShow(sizes[nextIndex]);
  };

  const statusOrderBodyTemplate = (rowData) => <Tag value={rowData.level}></Tag>;

  const ValidationBodyTemplate = (rowData) => (
    <Checkbox checked={rowData.is_val ? true : false} disabled />
  );

  const allColumns = [
    { field: "intermediate_code", header: "Mã Sản Phẩm", filter: true, sortable: true },
    { field: "name", header: "Sản Phẩm", filter: true, sortable: true },
    { field: "batch", header: "Số Lô", filter: true, sortable: true },
    { field: "expected_date", header: "Ngày DK KCS", filter: true },
    { field: "market", header: "Thị Trường", filter: true, sortable: true },
    { field: "level", header: "Ưu tiên", sortable: true, body: statusOrderBodyTemplate },
    { field: "is_val", header: "Lô Thẩm Định", filter: true, body: ValidationBodyTemplate },
    { field: "after_weigth_date", header: "Cân Trước", filter: true, sortable: true },
    { field: "before_weigth_date", header: "Cân Sau", filter: true, sortable: true },
    { field: "after_parkaging_date", header: "Đóng Gói Trước", filter: true, sortable: true },
    { field: "before_parkaging_date", header: "Đóng Gói Sau", filter: true, sortable: true },
    { field: "material_source", header: "Nguồn NL", filter: true, sortable: true },
    { field: "note", header: "Ghi chú", filter: true, sortable: true },
  ];

  useEffect(() => {
    const handleResize = () => {
      if (percentShow === "100%") {
        setVisibleColumns(allColumns);
      } else if (percentShow === "30%") {
        setVisibleColumns(allColumns.filter(col => ["name", "batch", "level"].includes(col.field)));
      } else {
        setVisibleColumns(allColumns.filter(col => ["name", "batch"].includes(col.field)));
      }
    };
    handleResize();
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, [percentShow]);

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
      if (currentSelection.length <= 1) {
        setSelectedRows(currentSelection.map(ev => ({ ...ev, isMulti: false })));
        //setSelectedRows(currentSelection);
        return;
      }
      const firstCode = currentSelection[0].intermediate_code;
      const allSame = currentSelection.every((row) => row.intermediate_code === firstCode);
      if (allSame) {
        setSelectedRows(currentSelection.map(ev => ({ ...ev, isMulti: true })));
        //setSelectedRows(currentSelection);
      } else {
        const lastSelected = currentSelection[currentSelection.length - 1];
        setSelectedRows([{ ...lastSelected, isMulti: false }]);
      }
  };
  


  const handlePrevStage = () => setStageFilter((prev) => Math.max(1, prev - 1));
  const handleNextStage = () => setStageFilter((prev) => Math.min(7, prev + 1));

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
                className="fc-event cursor-move px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center"
                data-rows={JSON.stringify(selectedRows)}
                draggable="true"
                onDragStart={handleDragStart}
              >
               <i className="fas fa-arrows-alt"></i> ({selectedRows.length})
              </div>
            </Col>

            <Col md={6}>
              <div className="p-inputgroup flex-1">
                <Button icon="pi pi-angle-double-left" className="p-button-success" onClick={handlePrevStage} disabled={stageFilter === 1} />
                <InputText value={stageNames[stageFilter]} className="text-center" style={{ fontSize: '25px' }} readOnly />
                <Button icon="pi pi-angle-double-right" className="p-button-success" onClick={handleNextStage} disabled={stageFilter === 7} />
              </div>
            </Col>


            <Col md={3} className='d-flex justify-content-end'>
              <button
                onClick={handleToggle}
                className="fc-event cursor-move px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center"
                style={{ fontSize: "1.5rem" }}
              > 
                <i className="pi pi-arrows-h"></i> {/* icon đổi chiều */}
              </button>
            </Col>
          </Row>



      </div>

      {/* Khu vực bảng + paginator cố định */}
      <div style={{ flex: 1, overflow: 'auto' }}>
        <DataTable
          value={filteredEvents}
          selection={selectedRows}
          onSelectionChange={handleSelectionChange}
          selectionMode="multiple"
          dataKey="id"
          size="small"
          paginator
          paginatorPosition="bottom"
          rows={20}
          rowsPerPageOptions={[5, 10, 25, 50]}
          scrollable
          scrollHeight="calc(100vh - 180px)" // để vừa khung trừ header & paginator
        >
          
          <Column
            header="-"
            body={(rowData) => (
              <div
                className="fc-event cursor-move px-2 py-1 bg-blue-100 border border-blue-400 rounded text-sm text-center"
                draggable="true"

                data-id={rowData.id}
                data-title={rowData.name + "-" + rowData.batch + "-" + rowData.market}
                data-intermediate_code={rowData.intermediate_code}
                data-stage_code={rowData.stage_code}
                data-expected-date={rowData.expected_date}
                
              >
                <i className="fas fa-arrows-alt"></i>
              </div>
            )}
            style={{ width: '60px', textAlign: 'center' }}
          />
          <Column selectionMode="multiple" headerStyle={{ width: '3em' }} />
          {visibleColumns.map(col => (
            <Column
              key={col.field}
              field={col.field}
              header={col.header}
              filter={col.filter}
              sortable={col.sortable}
              body={col.body}
            />
          ))}
        </DataTable>
      </div>
    </div>
  );
};

export default ModalSidebar;


// import React, { useEffect, useState } from 'react';
// import { DataTable } from 'primereact/datatable';
// import { Column } from 'primereact/column';


// import { Button } from 'primereact/button';
// import { InputText } from 'primereact/inputtext';
// import 'bootstrap/dist/css/bootstrap.min.css';
// import { Container, Row, Col } from 'react-bootstrap';
// import { classNames } from 'primereact/utils';
// import { Tag } from 'primereact/tag';
// import { Checkbox } from 'primereact/checkbox';
// const ModalSidebar = ({ visible, onClose, events = [] , percentShow, setPercentShow}) => {
//   const [selectedRows, setSelectedRows] = useState([]);
  
//   const [stageFilter, setStageFilter] = useState(1)

//   const statusOrderBodyTemplate = (rowData) => {
//         return <Tag value={rowData.level} ></Tag>;
//   };

//   const ValidationBodyTemplate = (rowData) => {
//     console.log (rowData)
//     return <Checkbox checked={rowData.is_val? true:false} disabled />;
//   };

//   const allColumns = [
//     { field: "intermediate_code", header: "Mã Sản Phẩm", filter: true, sortable: true },
//     { field: "name", header: "Sản Phẩm", filter: true, sortable: true },
//     { field: "batch", header: "Số Lô", filter: true, sortable: true },
//     { field: "expected_date", header: "Ngày DK KCS", filter: true },
//     { field: "market", header: "Thị Trường", filter: true, sortable: true },
//     { field: "level", header: "Ưu tiên", sortable: true, body: statusOrderBodyTemplate },
//     { field: "is_val", header: "Lô Thẩm Định", filter: true, body: ValidationBodyTemplate },
//     { field: "after_weigth_date", header: "Cân Trước", filter: true, sortable: true  },
//     { field: "before_weigth_date", header: "Cân Sau", filter: true, sortable: true  },
//     { field: "after_parkaging_date", header: "Đóng Gói Trước", filter: true, sortable: true  },
//     { field: "before_parkaging_date", header: "Đóng Gói Sau", filter: true, sortable: true  },
//     { field: "material_source", header: "Nguồn NL", filter: true, sortable: true  },
//     { field: "note", header: "Ghi chú", filter: true, sortable: true  },
//   ];
                   

//   const [visibleColumns, setVisibleColumns] = useState([]);

//   useEffect(() => {
//     const handleResize = () => {
//       const width = window.innerWidth;

//       if (percentShow == "100%") {
//         // Desktop full - hiển thị tất cả
//         setVisibleColumns(allColumns);
//       } else if (percentShow == "30%") {
//         // Tablet - hiển thị một số cột
//         setVisibleColumns(allColumns.filter(col =>
//           [ "name", "batch", "level"].includes(col.field)
//         ));
//       } else {
//         // Mobile - chỉ 1-2 cột
//         setVisibleColumns(allColumns.filter(col =>
//           ["name", "batch"].includes(col.field)
//         ));
//       }
//     };

//     handleResize(); // chạy ngay lần đầu
//     window.addEventListener('resize', handleResize);

//     return () => window.removeEventListener('resize', handleResize);

//   }, [percentShow]);



//   const filteredEvents = events.filter((event) => Number(event.stage_code) === stageFilter);

//   const stageNames = {
//       1: 'Cân',
//       2: 'NL Khác',
//       3: 'Pha Chế',
//       4: 'Trộn Hoàn Tất',
//       5: 'Định Hình',
//       6: 'Bao Phim',
//       7: 'Đóng Đói',
//   };

//     const handleSelectionChange = (e) => {
//     const currentSelection = e.value;

//     // Nếu chỉ có 1 dòng thì không cần kiểm tra
//     if (currentSelection.length <= 1) {
//       setSelectedRows(currentSelection);
//       return;
//     }

//     // Lấy mã intermediate_code của dòng đầu tiên
//     const firstCode = currentSelection[0].intermediate_code;

//     // Kiểm tra xem tất cả dòng đều cùng mã
//     const allSame = currentSelection.every(
//       (row) => row.intermediate_code === firstCode
//     );

//     if (allSame) {
//       setSelectedRows(currentSelection);
//     } else {
//       // Nếu không giống, chỉ giữ lại dòng cuối cùng vừa chọn
//       const lastSelected = currentSelection[currentSelection.length - 1];
//       setSelectedRows([lastSelected]);
//     }
//   };


//   const handlePrevStage = () => {
//     setStageFilter((prev) => Math.max(1, prev - 1));
//   };
 
//   const handleNextStage = () => {
//     setStageFilter((prev) => Math.min(7, prev + 1));
//   };


//   const handleDragStart = (e) => {

//     if (selectedRows.length === 0) return;

//     const data = selectedRows.map((row) => ({
//       id: row.id,
//       title: `${row.name}-${row.batch}-${row.market}`,
//       intermediate_code: row.intermediate_code,
//       stage_code: row.stage_code,
//       expected_date: row.expected_date,
//     }));


//     e.dataTransfer.setData("application/json", JSON.stringify(data));
//   };



//   return (
//     <div id = "external-events"
//       className={`fixed top-0 right-0 h-80 z-50 transition-transform duration-300 bg-white ${visible ? 'translate-x-0' : 'translate-x-full'}`}
//       style={{ width: percentShow, boxShadow: '2px 0 10px rgba(0,0,0,0.3)' }}
//     >

//       <div className="p-4 border-b flex justify-between items-center">
//         <Container fluid>
//           <Row className="align-items-center">
//             <Col md={1} >
//                 <div
//                     className="fc-event cursor-move px-0 py-0 bg-green-100 border border-green-400 rounded text-sm text-center"
//                     draggable="true"
//                     data-rows={JSON.stringify(selectedRows)}
//                     onDragStart={handleDragStart}
//                   >
//                   ⬍ ({selectedRows.length})
//                 </div>
               
//             </Col>

//             <Col md={1} >
//                 <button   onClick={()=> {setPercentShow((prev) => (prev === '30%' ? '100%' : '30%'))}} className="text-gray-600 right" ><i className="pi pi-chevron-circle-left"></i></button>
//             </Col>

//             <Col md={1} >
//                 <button  onClick={()=> {setPercentShow((prev) => (prev === '30%' ? '15%' : '30%'))}} className="text-gray-600 right" ><i className="pi pi-chevron-circle-right"></i></button>
//             </Col>

          
//               <Col md={6} >
//                 <div className="p-inputgroup flex-1">
//                   <Button icon="pi pi-angle-double-left" className="p-button-success" onClick={handlePrevStage} disabled={stageFilter === 1} />
//                   <InputText  value={stageNames[stageFilter]} className="text-center " style={{ fontSize: '25px' }} readOnly />
//                   <Button icon="pi pi-angle-double-right" className="p-button-success" onClick={handleNextStage} disabled={stageFilter === 7} />
//                 </div>
//               </Col>
//             <Col md={3} >
                
//             </Col>
//           </Row>
//         </Container>
//       </div>
    
//        <DataTable
//         value={filteredEvents}
//         selection={selectedRows}
//         onSelectionChange={handleSelectionChange}
//         selectionMode="multiple"
//         dataKey="id"
//         paginator
//         rows={20}
//         size = "small"
//         rowsPerPageOptions={[5, 10, 25, 50]}
//         > 

//          {/* Cột kéo thả */}
//         <Column
//           header="Kéo"
//           body={(rowData) => (
//             <div
//               className="fc-event cursor-move px-2 py-1 bg-blue-100 border border-blue-400 rounded text-sm text-center"
//               draggable="true"
//               data-id={rowData.id}
//               data-title={rowData.name + "-" + rowData.batch + "-" + rowData.market}
//               data-intermediate_code={rowData.intermediate_code}
//               data-stage={rowData.stage_code}
//               data-expected-date={rowData.expected_date}
             
//             >
//               ⬍
//             </div>
//           )}
//           style={{ width: '60px', textAlign: 'center' }}
//         />

//         <Column selectionMode="multiple" headerStyle={{ width: '3em' }} />
//         {visibleColumns.map(col => (
//           <Column
//             key={col.field}
//             field={col.field}
//             header={col.header}
//             filter={col.filter}
//             sortable={col.sortable}
//             body={col.body}
//           />
//         ))}
              

//       </DataTable>

  
//     </div>



//   );
// };

// export default ModalSidebar;
