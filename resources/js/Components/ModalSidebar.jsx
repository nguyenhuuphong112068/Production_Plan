import React, { useEffect, useRef, useState } from 'react';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Row, Col, Modal, Form } from 'react-bootstrap';
import { Checkbox } from 'primereact/checkbox';
import './ModalSidebar.css'
import { InputSwitch } from 'primereact/inputswitch';
import Swal from 'sweetalert2'; 
import axios from "axios";


const ModalSidebar = ({ visible, onClose, waitPlan, setPlan, percentShow, setPercentShow,  selectedRows, setSelectedRows, resources, type, currentPassword }) => {

   const wrapperRef = useRef(null);

  const [stageFilter, setStageFilter] = useState(1);
  const [visibleColumns, setVisibleColumns] = useState([]);
  const [searchTerm, setSearchTerm] = useState(""); 
  const sizes = ["close", "100%" ,"30%" ];
  const [currentIndex, setCurrentIndex] = useState(1);
  const [tableData, setTableData] = useState([]);
  const [showModalCreate, setShowModalCreate] = useState(false); 
  const [showModalQuota, setShowModalQuota] = useState(false); 
  const [orderPlan, setOrderPlan] = useState({checkedClearning: false, title: null, batch: "NA", level: 1});
  const [modalQuotaData, setModalQuotaData] = useState({name: "",intermediate_code: ""});
  const [errorsModal, setErrorsModal] = useState(null);
  const [isSaving, setIsSaving] = useState(false);
  const [optionRooms, setOptionRooms] = useState([]);
  const [unQuota, setUnQuota] = useState(0);
 

  const columnWidths100 = {
    code: '8%',                // Mã sản phẩm
    permisson_room: '6%',      // Phòng SX
    name: '10%',               // Sản phẩm
    batch: '8%',               // Số lô
    expected_date: '8%',       // Ngày DK KCS
    market: '6%',              // Thị trường
    level: '4%',               // Ưu tiên
    is_val: '5%',              // Thẩm định
    weight_dates: '8%',        // Cân NL
    pakaging_dates: '8%',      // Đóng gói
    source_material_name: '10%',// Nguồn nguyên liệu
    campaign_code: '6%',       // Mã chiến dịch
    note: '23%',               // Ghi chú
  };

  const columnWidths30 = {
    name: '45%',           // Sản phẩm
    batch: '20%',          // Số lô
    expected_date: '20%',  // Ngày DK KCS
    level: '15%',          // Ưu tiên
  };

  useEffect(() => {
    
    if (resources && resources.length > 0 && stageFilter) {
      const filtered = resources.filter(
        (q) => Number(q.stage_code) == Number(stageFilter)
      );
      setOptionRooms(filtered);
     
    }
  }, [resources, stageFilter]); 

  useEffect(() => {

    if (waitPlan && waitPlan.length > 0) {

      const filtered = waitPlan.filter(event => Number(event.stage_code) === stageFilter)
      //.map(event => ({...event,permisson_room_filter: Object.values(event.permisson_room || {}).join(", ")}));
      setTableData(filtered);
      setUnQuota (tableData.filter(event => Array.isArray(event.permisson_room) && event.permisson_room.length === 0).length)
    }
  }, [waitPlan]);

  // chọn các cột cần show ở các độ rộng của modalsidebar
  useEffect(() => {
    let visibleCols = [];

    if (percentShow === "100%") {
      if (stageFilter === 1) {
        visibleCols = allColumns.filter(col => !["pakaging_dates"].includes(col.field));
      } else if (stageFilter === 7) {
        visibleCols = allColumns.filter(col => !["weight_dates"].includes(col.field));
      } else if (stageFilter === 8) {
        visibleCols = allColumns.filter(col => ![
          "weight_dates", "pakaging_dates", "level",
          "batch", "market", "is_val", "source_material_name", "campaign_code"
        ].includes(col.field));
      } else if (stageFilter === 9) {
        visibleCols = allColumns.filter(col => ["name", "batch", "note"].includes(col.field));
      } else {
        visibleCols = allColumns.filter(col => !["weight_dates", "pakaging_dates"].includes(col.field));
      }

      visibleCols = visibleCols.map(col => ({
        ...col,
        style: { ...col.style, width: columnWidths100[col.field] || 'auto', maxWidth: columnWidths100[col.field] || 'auto' }
      }));

    } else if (percentShow === "30%") {
      visibleCols = allColumns.filter(col => ["name", "batch", "expected_date", "level"].includes(col.field))
        .map(col => ({
          ...col,
          style: { ...col.style, width: columnWidths30[col.field] || 'auto', maxWidth: columnWidths30[col.field] || 'auto' }
        }));
    } 

    setVisibleColumns(visibleCols);
  }, [percentShow, stageFilter]);


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

  const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    if (isNaN(date)) return dateStr; // nếu không parse được thì giữ nguyên
    return date.toLocaleDateString("vi-VN"); // sẽ thành dd/MM/yyyy
  };

  const weightPBodyTemplate = (rowData) => (
    <div style={{ display: "flex", flexDirection: "column" }}>
      <span>{formatDate(rowData.after_weigth_date) || ''}</span>
      <span>{formatDate(rowData.before_weigth_date) || ''}</span>
    </div>
  );

  const packagingBodyTemplate = (rowData) => (
    <div style={{ display: "flex", flexDirection: "column" }}>
      <span>{formatDate(rowData.after_parkaging_date) || ''}</span>
      <span>{formatDate(rowData.before_parkaging_date) || ''}</span>
    </div>
  );

  const productCodeBody = (rowData) => {
    if (rowData.stage_code === 8) {
      return <span>{rowData.instrument_code || ''}</span>;
    }
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
    5: 'Định Hình', 6: 'Bao Phim', 7: 'ĐGSC-ĐGTC', 8: 'Hiệu Chuẩn - Bảo Trì', 9: 'Sự Kiện Khác',
  };

  const handleSelectionChange = (e) => {
    const currentSelection = e.value ?? null;

    if (!currentSelection || currentSelection.length === 0) {
      setSelectedRows([]); // reset nếu không có selection
      return;
    }

    // ✅ check stage_code = 8
    const lastSelected = currentSelection[currentSelection.length - 1];
   
    
    if (lastSelected.stage_code === 8 && lastSelected.is_HVAC) {
      // lấy instrument_code của row vừa chọn
      const code = lastSelected.instrument_code;
      // tìm tất cả row trong dataset có cùng instrument_code
      const sameInstrument = waitPlan.filter(
        (row) => row.instrument_code === code && row.stage_code === 8
      );
      // đánh dấu multi
      setSelectedRows(sameInstrument.map(ev => ({ ...ev, isMulti: true, is_HVAC: true  })));
      return;
    }

    if (lastSelected.stage_code === 8 && lastSelected.is_HVAC === 0) {
      // chọn theo permisson_room
      const targetRoom = JSON.stringify(lastSelected.permisson_room);

      const sameRoom = waitPlan.filter(
        (row) =>
          row.stage_code === 8 &&
          JSON.stringify(row.permisson_room) === targetRoom
      );

      setSelectedRows(sameRoom.map(ev => ({ ...ev, isMulti: true, is_HVAC: false })));
      return;
    }

    // ✅ các trường hợp khác
    if (percentShow !== "100%") {
      if (currentSelection.length <= 1) {
        setSelectedRows(currentSelection.map(ev => ({ ...ev, isMulti: false })));
        return;
      }
      const firstCode = currentSelection[0].intermediate_code;
      const allSame = currentSelection.every((row) => row.intermediate_code === firstCode);
      if (allSame) {
        setSelectedRows(currentSelection.map(ev => ({ ...ev, isMulti: true })));
      } else {
        setSelectedRows([{ ...lastSelected, isMulti: false }]);
      }
    } else {
      setSelectedRows(currentSelection.map(ev => ({ ...ev, isMulti: false })));
    }
  };

  const handlePrevStage = () =>  {
    if (isSaving) return;
    setIsSaving(true);

    setStageFilter((prev) => {
      const nextStage = prev === 1 ? 9 : prev - 1;

      let stage_plan = waitPlan.filter(event => Number(event.stage_code) === nextStage)
      //let stage_plan2 = stage_plan.map(event => ({...event,permisson_room_filter: Object.values(event.permisson_room || {}).join(", ")}));
      setTableData(stage_plan);
      setUnQuota (stage_plan.filter(event => Array.isArray(event.permisson_room) && event.permisson_room.length === 0).length)
      return nextStage;
    });
    setSelectedRows([]);
    setIsSaving(false);
  }

  const handleNextStage = () => {
    if (isSaving) return;
    setIsSaving(true);

    setStageFilter((prev) => {
      const nextStage = prev === 9 ? 1 : prev + 1;

      let stage_plan = waitPlan.filter(event => Number(event.stage_code) === nextStage)
      //let stage_plan2 = stage_plan.map(event => ({...event,permisson_room_filter: Object.values(event.permisson_room || {}).join(", ")}));

      setTableData(stage_plan);
      setUnQuota (stage_plan.filter(event => Array.isArray(event.permisson_room) && event.permisson_room.length === 0).length)
      return nextStage;
    });
    setSelectedRows([]);
    setIsSaving(false);
  }

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

      setPlan (updateOrderData)
      // Gửi lên server
      axios.put('/Schedual/updateOrder', { updateOrderData: payload })
      . then (res => {
                    let data = res.data;
                    if (typeof data === "string") {
                      data = data.replace(/^<!--.*?-->/, "").trim();
                      data = JSON.parse(data);
                    }
                    setPlan(data.plan);
                  }
      ).catch (err => {
                    Swal.fire({
                      icon: 'error',
                      title: 'Lỗi',
                      timer: 1500
                    });
                    console.error("API error:", err.response?.data || err.message);
                }
      );

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

    setPlan (updateOrderData)

    axios.put('/Schedual/updateOrder', { updateOrderData: payload })
      . then (res => {
                    let data = res.data;
                    if (typeof data === "string") {
                      data = data.replace(/^<!--.*?-->/, "").trim();
                      data = JSON.parse(data);
                    }
                    setPlan(data.plan);
                  }
      ).catch (err => {
                    Swal.fire({
                      icon: 'error',
                      title: 'Lỗi',
                      timer: 1500
                    });
                    console.error("API error:", err.response?.data || err.message);
                }
      );

  };

  const handleCreateManualCampain = (e) => {
    if (isSaving) return;
    setIsSaving(true);  
    const filteredRows = selectedRows.map(row => ({
        id: row.id,
        plan_master_id: row.plan_master_id,
        product_caterogy_id: row.product_caterogy_id,
        predecessor_code: row.predecessor_code,
        campaign_code: row.campaign_code,
        code: row.code,
      }));

      axios.put('/Schedual/createManualCampain', { data: filteredRows })
      . then (res => {
                    let data = res.data;
                    if (typeof data === "string") {
                      data = data.replace(/^<!--.*?-->/, "").trim();
                      data = JSON.parse(data);
                    }
                    setPlan(data.plan);
                  }
      ).catch (err => {
                    Swal.fire({
                      icon: 'error',
                      title: 'Lỗi',
                      timer: 1500
                    });
                    console.error("API error:", err.response?.data || err.message);
                }
      );

      setIsSaving(false);  
      setSelectedRows ([]);
      return;
  }

  const handleCreateAutoCampain = async () => {

     const { value: password } = await Swal.fire({
        title: "Nhập Mật Khẩu",
        width: "500px",
        text: "Bạn Muốn Tạo Lại Toạn Bộ Mã Chiến Dich, Chiến Dịch Cũ Sẽ Mất",
        input: "password",
        inputPlaceholder: "Nhập mật khẩu...",
        showCancelButton: true,
        confirmButtonText: "Xác nhận",
        cancelButtonText: "Hủy",
        customClass: {
          input: 'passWord-swal-input'
        },
        inputValidator: (value) => {
          if (!value) return "Bạn phải nhập mật khẩu!";
        },
    });

    if (!password) return;
    
    if (password !== currentPassword) {
      Swal.fire({
        icon: "error",
        title: "Sai mật khẩu!",
        text: "Vui lòng thử lại.",
        timer: 1500,
        showConfirmButton: false,
      });
      return;
    }


    if (isSaving) return;
    setIsSaving(true);
     Swal.fire({
              title: "Đang thực thi, vui lòng đợi giây lát..",
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
              },
      });

      axios.put('/Schedual/createAutoCampain', {stage_code: stageFilter})
      . then (res => {
                    let data = res.data;
                    if (typeof data === "string") {
                      data = data.replace(/^<!--.*?-->/, "").trim();
                      data = JSON.parse(data);
                    }

                    setPlan(data.plan);

                    setTimeout(() => {
                      Swal.close();
                    }, 100);
                    
                    Swal.fire({
                      title: 'Hoàn Thành!',
                      text: 'Tạo Mã Chiến Dịch Thành Công',
                      icon: 'success',
                      confirmButtonText: 'OK'
                    }).then(() => {
                      setSelectedRows([]);
                    });

                  }
      ).catch (err => {
                    setTimeout(() => {
                      Swal.close();
                    }, 100);

                    Swal.fire({
                      icon: 'error',
                      title: 'Lỗi',
                      timer: 1500
                    });
                    console.error("API error:", err.response?.data || err.message);
                }
      );

    setIsSaving(false);

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
      
      axios.put('/Schedual/createOrderPlan', orderPlan)
      . then (res => {
                    let data = res.data;
                    if (typeof data === "string") {
                      data = data.replace(/^<!--.*?-->/, "").trim();
                      data = JSON.parse(data);
                    }
                    setPlan(data.plan);
                    setShowModalCreate (false)
                    Swal.fire({
                      title: 'Thành công!',
                      text: 'Tạo Mới Kế Hoạch Khác',
                      icon: 'success',
                      showConfirmButton: false,
                      timer: 1500 
                    }).then(() => {
                      setOrderPlan({});
                      setErrorsModal ({})
                    });
                    
                  }
      ).catch (err => {
                    Swal.fire({
                      icon: 'error',
                      title: 'Lỗi',
                      timer: 1500
                    });
                    console.error("API error:", err.response?.data || err.message);
                }
      );    
  }

  const handleDeActiveOrderPlan = () => {

      axios.put('/Schedual/DeActiveOrderPlan', selectedRows)
      . then (res => {
                    let data = res.data;
                    if (typeof data === "string") {
                      data = data.replace(/^<!--.*?-->/, "").trim();
                      data = JSON.parse(data);
                    }
                    setPlan(data.plan);
                    setShowModalCreate (false)
                    Swal.fire({
                      title: 'Thành công!',
                      text: 'Xóa Sự Kiện Khác Thành Công',
                      icon: 'success',
                      showConfirmButton: false,
                      timer: 1500 
                    }).then(() => {
                      setOrderPlan({});
                      setErrorsModal ({})
                    });
                    
                  }
      ).catch (err => {
                    Swal.fire({
                      icon: 'error',
                      title: 'Lỗi',
                      timer: 1500
                    });
                    console.error("API error:", err.response?.data || err.message);
                }
      ); 

  }

  const naBody = (field) => (rowData) => {
      if (field === "name" && rowData.stage_code === 9) {
        return rowData.title ?? "NA";
      }

      if (field === "expected_date") {
          if (!rowData.expected_date) return "NA";
          const date = new Date(rowData.expected_date);
          if (isNaN(date)) return rowData.expected_date; // giá trị không hợp lệ thì giữ nguyên
          return date.toLocaleDateString("vi-VN"); // format mặc định: dd/MM/yyyy
      }

      return rowData[field] ?? "NA";
  };

  const handleOpenQuota = (rowData) => {
    if (rowData.stage_code !== 9) {
      setModalQuotaData({
        name: rowData.name,
        finished_product_code:  rowData.finished_product_code,
        intermediate_code:  rowData.intermediate_code,
        stage_code: rowData.stage_code
      });
      setShowModalQuota(true);
    }
  };

  const handleCreateQuota = () => {
    if (isSaving) return;
    setIsSaving(true);

    axios.put('/quota/production/store', modalQuotaData)
      . then (res => {
                    let data = res.data;
                    if (typeof data === "string") {
                      data = data.replace(/^<!--.*?-->/, "").trim();
                      data = JSON.parse(data);
                    }
                    setPlan(data.plan);

                    Swal.fire({
                      title: 'Thành công!',
                      text: 'Hoàn Thành Tạo Mới Định Mức',
                      icon: 'success',
                      showConfirmButton: false,
                      timer: 1500 
                    }).then(() => {
                        setShowModalQuota(false);
                        setErrorsModal ({})
                        setIsSaving(false);
                    });
                    
                  }
      ).catch (err => {
                    Swal.fire({
                      icon: 'error',
                      title: 'Lỗi',
                      timer: 1500
                    });
                    setErrorsModal (errors)
                    setIsSaving(false);
                    console.error("API error:", err.response?.data || err.message);
                }
      ); 
      
  }

  const handleFinished = () => {
    if (isSaving) return;
    setIsSaving(true);
    if (selectedRows.length ==0){
       Swal.fire({
              icon: 'warning',
              title: 'Không có dòng được chọn',
              timer: 500,
              showConfirmButton: false,
            });

      setIsSaving(false);
      return
    }
    const ids = selectedRows.map(row => row.plan_master_id);
    const stageCode = selectedRows[0].stage_code
    

      axios.put('/Schedual/finished',{id : ids, temp : true, stage_code: stageCode} )

        .then(res => {
            let data = res.data;
            if (typeof data === "string") {
              data = data.replace(/^<!--.*?-->/, "").trim();
              data = JSON.parse(data);
            }
           
            setPlan(data.plan_waiting)
            Swal.fire({
              icon: 'success',
              title: 'Hoàn Thành',
              timer: 500,
              showConfirmButton: false,
            });
          })
        .catch(err => {
            Swal.fire({
              icon: 'error',
              title: 'Lỗi',
              timer: 500,
              showConfirmButton: false,
            });
            console.error("Finished error:", err.response?.data || err.message);
        });

    setIsSaving(false);
  };

  const filterUnQuotaRow = () => {
    if (isSaving) return;
    setIsSaving(true);
  
    let UnQuotaRow = waitPlan.filter(event => Number(event.stage_code) === stageFilter && Array.isArray(event.permisson_room) && event.permisson_room.length === 0)
    setTableData(UnQuotaRow)

    setSelectedRows([]);
    setIsSaving(false);
  
  }

  const handleSorted = async  () => {

    const { value: password } = await Swal.fire({
        title: "Nhập Mật Khẩu",
        width: "500px",
        text: "Bạn Muốn Sắp Xếp Lại Toàn Bộ Kế Hoạch",
        input: "password",
        inputPlaceholder: "Nhập mật khẩu...",
        showCancelButton: true,
        confirmButtonText: "Xác nhận",
        cancelButtonText: "Hủy",
        customClass: {
          input: 'passWord-swal-input'
        },
        inputValidator: (value) => {
          if (!value) return "Bạn phải nhập mật khẩu!";
        },
    });

    if (!password) return;
    
    if (password !== currentPassword) {
      Swal.fire({
        icon: "error",
        title: "Sai mật khẩu!",
        text: "Vui lòng thử lại.",
        timer: 1500,
        showConfirmButton: false,
      });
      return;
    }

    Swal.fire({
            title: "Đang Sắp Xếp Lại, vui lòng đợi giây lát..",
            allowOutsideClick: false,
            didOpen: () => {
            Swal.showLoading();
            },
    });  

    if (isSaving) return;
      setIsSaving(true);
        axios.put('/Schedual/Sorted', {stage_code: stageFilter})
          .then(res => {
              let data = res.data;
              if (typeof data === "string") {
                data = data.replace(/^<!--.*?-->/, "").trim();
                data = JSON.parse(data);
              }
            
              setPlan(data.plan)
              
              Swal.fire({
                icon: 'success',
                title: 'Hoàn Thành',
                timer: 500,
                showConfirmButton: false,
              });
            })
          .catch(err => {
              Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                timer: 500,
                showConfirmButton: false,
              });
              console.error("Finished error:", err.response?.data || err.message);
          });
          
          setTimeout(() => {
                        Swal.close();
                      }, 1500);
          
      setIsSaving(false);

  }

  const handleDragOver = (e) => {
    const wrapper = wrapperRef.current?.querySelector(".p-datatable-wrapper");
    if (!wrapper) return;

    const rect = wrapper.getBoundingClientRect();
    const offset = 100; // vùng nhạy cảm cuộn
    const speed = 500;  // tốc độ cuộn

    if (e.clientY < rect.top + offset) wrapper.scrollTop -= speed;
    else if (e.clientY > rect.bottom - offset) wrapper.scrollTop += speed;
  };

  const roomBody = (rowData) => {
    
    if (!rowData.permisson_room ||  rowData.permisson_room.length === 0 ) {    
        return (
          <span
            className='btn'
            onClick={() => { if (rowData.stage_code !== 9){handleOpenQuota(rowData)}}}
            style={{
              backgroundColor: "#ffcccc", // đỏ nhạt
              color: "#b00000",           // chữ đỏ đậm
              padding: "2px 6px",
              borderRadius: "6px",
              display: "inline-block",
              minWidth: "100%",           // fill cả ô
              textAlign: "center"
            }}
          >
            {`${rowData.stage_code !==9?"Thiếu Định Mức":"Không Định Mức"}`} 
          </span>
        );
    }else{
        
        const permisson_room = Object.values(rowData.permisson_room || {});

        if (permisson_room.length === 1) {
          return Object.values(rowData.permisson_room).join(", ");
        }

        const handleRoomChange = (room, id , checked) => {
          axios.put('/Schedual/required_room', {room_code: room, stage_plan_id: id, checked: checked})
          . then (res => {
              let data = res.data;
              if (typeof data === "string") {
                  data = data.replace(/^<!--.*?-->/, "").trim();
                  data = JSON.parse(data);
              }

              setPlan(data.plan)

          }).catch (err => {
                        Swal.fire({
                          icon: 'error',
                          title: 'Lỗi',
                          timer: 1500
                        });
                        console.error("API error:", err.response?.data || err.message);
                    }
          ); 
        };
        
        return (
          <div className="flex flex-wrap gap-2">
            {permisson_room.map((room) => (
              <div key={room} className="flex align-items-center gap-1">
                <Checkbox
                  inputId={room + rowData.id}
                  checked={rowData.required_room_code == room}
                  onChange={(e) => handleRoomChange(room, rowData.id , e.checked)}
                />
                <label htmlFor={room + rowData.id}>{room}</label>
              </div>
            ))}
          </div>
        );
      };
  }

  const longTextStyle = { whiteSpace: 'normal', wordBreak: 'break-word' };

  const allColumns = [
     
      { field: "month", header: "tháng", sortable: true,  filter: false, filterField: "month" },
      { field: "code", header: "Mã Sản Phẩm", sortable: true, body: productCodeBody, filter: false, filterField: "code" , style: { width: '5%', maxWidth: '5%', ...longTextStyle }},
      { field: "permisson_room", header: "Phòng SX", sortable: true, body: roomBody, filter: false, filterField: "permisson_room",style: { minWidth: '3%', maxWidth: '3%', ...longTextStyle } },
      { field: "name", header: "Sản Phẩm", sortable: true, body: naBody("name"), filter: false,  filterField: "name" , style: { width: '20%', maxWidth: '20%', ...longTextStyle }},
      { field: "batch", header: "Số Lô", sortable: true, body: naBody("batch"), filter: false, filterField: "batch" , style: { width: '10%', maxWidth: '15%', ...longTextStyle }},
      { field: "market", header: "Thị Trường", sortable: true, body: naBody("market"), filter: false, filterField: "market", style: { width: '8rem', maxWidth: '8rem', ...longTextStyle }},
      { field: "expected_date", header: "Ngày DK KCS", body: naBody("expected_date") , filter: false, filterField: "expected_date", style: { width: '10%', maxWidth: '15%', ...longTextStyle }},
      { field: "level", header: "Ưu tiên", sortable: true, body: statusOrderBodyTemplate, style: { width: '5%', maxWidth: '5%', ...longTextStyle } },
      { field: "is_val", header: "Thẩm Định", body: ValidationBodyTemplate, style: { width: '5rem', maxWidth: '5rem', ...longTextStyle } },
      { field: "weight_dates", header: "Cân NL", sortable: true, body: weightPBodyTemplate },
      { field: "pakaging_dates", header: "Đóng gói", sortable: true, body: packagingBodyTemplate },
      { field: "source_material_name", header: "Nguồn nguyên liệu", sortable: true, body: naBody("source_material_name"), style: { width: '25rem', maxWidth: '25rem', ...longTextStyle } },
      { field: "campaign_code", header: "Mã Chiến Dịch", sortable: true, body: campaignCodeBody, style: { width: '8rem', maxWidth: '8rem', ...longTextStyle } },
      { field: "note", header: "Ghi chú", sortable: true, body: naBody("note") , filter: false, filterField: "note", style: { width: '20%', maxWidth: '20%', ...longTextStyle }},
  ];
    
  return (
    <div
      ref={wrapperRef}
      onDragOver={handleDragOver}
      style={{ height: "600px", overflow: "auto" }}
    >
    <div
        id="external-events"
        className={`absolute right-0 h-100 z-50 transition-transform duration-300 bg-white ${visible ? 'translate-x-0' : 'translate-x-full'}`}
        style={{
          width: percentShow,
          maxWidth: "100%", // ✅ tối đa bằng content-wrapper
          boxShadow: "2px 0 10px rgba(0,0,0,0.3)",
          display: "flex",
          flexDirection: "column",
          top: "40px"
        }}
    >
    
      {/* Thanh điều khiển */}
      <div className="p-4 border-b">
        <Row className="align-items-center">
          <Col md={3} className='d-flex justify-content-start'>
        
            {percentShow === "100%" && stageFilter <=7 ? (
              <>
              {unQuota > 0 && (
              <div className="fc-event px-3 py-1 bg-red-400 border border-red-400 rounded text-md text-center cursor-pointer mr-3"
                onClick={filterUnQuotaRow}>
                {unQuota} Lô Thiếu Định Mức 
              </div>)}

              <div className="fc-event px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Mã Chiến Dịch Với Các Sản Phẩm Đã Chọn"
                onClick={handleCreateManualCampain}>
                 {isSaving === false ?<i className="fas fa-flag"></i> :<i className="fas fa-spinner fa-spin fa-lg"></i>} ({selectedRows.length})
              </div>

              <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Mã Chiến Dịch tự Động"
              onClick={handleCreateAutoCampain}>
                {isSaving === false ? <i className="fas fa-flag-checkered"></i>:<i className="fas fa-spinner fa-spin fa-lg"></i>}
              </div> 

              <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Sắp xếp lại theo kế hoạch tháng"
                onClick={handleSorted}>
                {isSaving === false ? <i className="fas fa-sort"></i>:<i className="fas fa-spinner fa-spin fa-lg"></i>}
              </div>

              </>):<></>}
              {percentShow === "100%" && stageFilter === 9 && type ? (
                <>
                  <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Sự Kiện Khác"
                    onClick={ () => setShowModalCreate (true)}>
                    <i className="fas fa-plus"></i>
                  </div> 

                  <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Xóa Sự Kiện Khác"
                    onClick={handleDeActiveOrderPlan}>
                    <i className="fas fa-trash"></i> 
                  </div> 
                </>
              ):<></>}

          </Col>

          <Col md={6}>
            <div className="p-inputgroup flex-1">
              <Button icon="pi pi-angle-double-left" className="p-button-success rounded" onClick={handlePrevStage}  title="Chuyển Công Đoạn"/>
              {percentShow === "100%" ? (

              <InputText value= {stageFilter +". " + "Công Đoạn " + stageNames[stageFilter] + " - " + 
                tableData.length + " Mục Chờ Sắp Lịch"} className="text-center  fw-bold rounded" style={{ fontSize: '25px' , color: ' #CDC171'}} readOnly 
                /> 
              ):

              <InputText value={ stageNames[stageFilter]} className="text-center fw-bold" style={{ fontSize: '15px', color: ' #CDC171'}} readOnly />
              }

              <Button icon="pi pi-angle-double-right" className="p-button-success rounded" onClick={handleNextStage}  title="Chuyển Công Đoạn" />
            </div>
          </Col>
          <Col md={3} className='d-flex justify-content-end'>
              
            <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Mã Chiến Dịch tự Động"
                onClick={handleFinished}>
                {isSaving === false ? <i className="fas fa-check"></i>:<i className="fas fa-spinner fa-spin fa-lg"></i>}
            </div>

            {percentShow === "100%" ? (
              <InputText className='border mr-5'
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Tìm kiếm..."
                style={{ width: "50%" }}
              />):""}


          
            {percentShow != "close" ? (
            <div onClick={handleToggle} className='me-3' style={{ width: '30px', height: '30px' , marginRight: '1%' }} title="Điều Chỉnh Độ Rộng Side Bar">
              <img src="/img/iconstella.svg" style={{width: '40px', height: '40px'}} />
            </div>):""}
          </Col>
        </Row>

      </div>

      {/* Khu vực bảng */}
      <div style={{ flex: 1, overflow: 'auto'}}>
        <DataTable
          className="p-datatable-gridlines prime-gridlines"
          key={percentShow}
          value= {tableData}
          selection={selectedRows}
          onSelectionChange={handleSelectionChange}
          selectionMode="multiple"
          dataKey="id"
          size="medium"
          paginator paginatorPosition="bottom" rows={20} rowsPerPageOptions={[5, 10, 25, 50, 100, 500, 1000]}
          scrollable scrollHeight="calc(100vh - 200px)"
          columnResizeMode="expand" resizableColumns
          globalFilter={searchTerm} 
          reorderableColumns reorderableRows onRowReorder={handleRowReorder}
          globalFilterFields={[
            "name",
            "batch",
            "permisson_room_filter",
            "market",
            "expected_date",
            "level",
            "note",
            "code",
            "source_material_name",
            "instrument_code" , 
            "finished_product_code",
            "month"
          ]}
        >
           {percentShow === "100%" ? (
            <Column
            header="STT"
            body={(rowData, options) => options.rowIndex + 1}
            style={{ width: "60px", textAlign: "center" }}
          />):""}
          
          

          <Column selectionMode="multiple" headerStyle={{ width: '3em' }} />

          {percentShow === "100%" ? (
            <Column rowReorder style={{ width: '3rem' }} />) : (

            /* Cột Kéo thả */
            <Column
                  header="-"
                  body={(rowData) => (
                    <div
                      className="fc-event cursor-move px-2 py-1 bg-blue-100 border border-blue-400 rounded text-sm text-center"
                      draggable="true"
                      onClick={handleSelectionChange}
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

      {/* Thêm Kế Hoạch Khác */}
      <Modal size="lg" show={showModalCreate} aria-labelledby="example-modal-sizes-title-lg" onHide = {() => setShowModalCreate (false)}>
                <Modal.Header style={{color:'#cdc717', backgroundColor: '#ffffffff'}}>
                  <img src="/img/iconstella.svg" style={{width: '30px', marginRight: '10px'}} /> 
                  <Modal.Title 
                      id="example-modal-sizes-title-lg" 
                      className="mx-auto fw-bold"
                  >
                      Tạo Mới Sự Kiện
                  </Modal.Title>
                </Modal.Header>
                <Modal.Body  style={{fontSize:'20px'}}>
                  <Form>
                    <Row className="mb-3">
                          <Form.Group as={Col} >
                          <Form.Label>Tên sự kiện</Form.Label>
                          <Form.Control type="text" name ='fullName' value = {orderPlan.title?? ""} onChange={(e) => setOrderPlan({...orderPlan, title: e.target.value})}   placeholder="Bắt buộc" />
                          {errorsModal?.create_inter_Errors?.room_id && (
                              <div className="alert alert-danger mt-1">
                                {errorsModal.create_inter_Errors.room_id}
                              </div>)}
                          </Form.Group>
                    </Row>

                    <Row className="mb-3">
                          <Form.Group as={Col} >
                          <Form.Label>Số lô</Form.Label>
                          <Form.Control type="text" name ='fullName' value = {orderPlan.batch?? ""} onChange={(e) => setOrderPlan({...orderPlan, batch: e.target.value})}   placeholder="Không Bắt buộc, nếu không có mặc định NA" />
                          </Form.Group>

                          <Form.Group as={Col} >
                          <Form.Label>Số Lượng sự kiện</Form.Label>
                          <Form.Control type="number" name ='number_of_batch' min = "1" value = {orderPlan.number_of_batch?? 1} onChange={(e) => setOrderPlan({...orderPlan, number_of_batch: e.target.value})}  />
                          </Form.Group>

                    </Row>

                    <Row className="mb-3">
                          <Form.Group as={Col} >
                            <div className='text-center d-flex justify-content-start'>
                              <Form.Label className='mr-5'>Có vệ sinh lại không: </Form.Label>
                              <span className='ml-5 mr-5'> Không </span>
                              <InputSwitch checked={orderPlan.checkedClearning?? ""} onChange={(e) => setOrderPlan({...orderPlan, checkedClearning : !orderPlan.checkedClearning})} />
                              <span className='ml-5 mr-5'> Có </span>
                            </div>
                          </Form.Group>     
                    </Row> 
                    
                    <Row className="mb-3">
                          <Form.Group as={Col} >
                          <Form.Label>Ghi chú</Form.Label>
                          <Form.Control type="text" name ='fullName' value = {orderPlan.note?? ""} onChange={(e) => setOrderPlan({...orderPlan, note: e.target.value})}   placeholder="Bắt buộc" />
                          </Form.Group>
                    </Row>

                  </Form>

                </Modal.Body>

                <Modal.Footer >
                    <Button  className='btn btn-primary' onClick={() => setShowModalCreate (false)}>
                        Hủy
                    </Button>
                    <Button className='btn btn-primary' onClick={() => handleCreateOrderPlan (false)}> 
                        Lưu
                    </Button>

                </Modal.Footer>
      </Modal>


      {/* Tạo Đinh Mức */}
      <Modal size="xl" show={showModalQuota} aria-labelledby="example-modal-sizes-title-lg" onHide = {() => setShowModalQuota (false)}>
                <Modal.Header style={{color:'#cdc717', backgroundColor: '#ffffffff'}}>
                  <img src="/img/iconstella.svg" style={{width: '30px', marginRight: '10px'}} /> 
                  <Modal.Title 
                      id="example-modal-sizes-title-lg" 
                      className="mx-auto fw-bold"
                  >
                      Định Mức Sản Xuất
                  </Modal.Title>
                </Modal.Header>
                <Modal.Body  style={{fontSize:'20px'}}>

                  <Form  >
                    <Row className="mb-3">
                      <Col md={9}>
                          <Form.Group >
                          <Form.Label>Tên Sản Phẩm</Form.Label>
                          <Form.Control type="text" name ='name' value={modalQuotaData.name?? ""} readOnly />
                          </Form.Group>                      
                      </Col>
                      <Col md={3}>
                          <Form.Group >
                          <Form.Label>Mã Sản Phẩm</Form.Label>
                          <Form.Control type="text" name ='product_code' value={modalQuotaData.stage_code <=6? modalQuotaData.intermediate_code: modalQuotaData.finished_product_code}  readOnly  />
                          </Form.Group>                      
                      </Col>
                    </Row>

                    <Row className="mb-3">
                          <Form.Group as={Col} >
                          <Form.Label>Phòng Sản Xuất</Form.Label>

                          <select className="form-control" name="room_id[]"  multiple="multiple" style= {{width: "100%", height:"30mm" }}
                              onChange={(e) => {
                                const selectedValues = Array.from(e.target.selectedOptions, option => option.value);
                                setModalQuotaData({
                                  ...modalQuotaData,
                                  room_id: selectedValues,   // lưu mảng id vào state
                                });
                              }}>
                                {optionRooms.map((room, idx) => (
                                  <option key={idx} value={room.id}> 
                                      {room.code + " - " + room.title}
                                  </option>
                                ))}
                          </select>

                          {errorsModal?.create_inter_Errors?.room_id && (
                              <div className="alert alert-danger mt-1">
                                {errorsModal.create_inter_Errors.room_id}
                              </div>)}
                          </Form.Group>

                    </Row>

                    <Row className="mb-3">
                        <Col md={3}>
                            <Form.Group >
                            <Form.Label>Thời Gian Chuẩn Bi</Form.Label>
                            <Form.Control type="text" name ='p_time' value = {modalQuotaData.p_time?? ""} onChange={(e) => setModalQuotaData({...modalQuotaData, p_time: e.target.value})} placeholder="HH:mm" 
                                          pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$"  required
                                          />
                              {errorsModal?.create_inter_Errors?.p_time && (
                                <div className="alert alert-danger mt-1">
                                  {errorsModal.create_inter_Errors.p_time}
                                </div>)}
                            </Form.Group> 
                    
                        </Col>
                        <Col md={3}>
                            <Form.Group >
                            <Form.Label>Thời Gian Sản Xuất</Form.Label>
                            <Form.Control type="text" name ='m_time' value = {modalQuotaData.m_time?? ""} onChange={(e) => setModalQuotaData({...modalQuotaData, m_time: e.target.value})}  placeholder="HH:mm" 
                                          pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$" required />
                                {errorsModal?.create_inter_Errors?.m_time && (
                                <div className="alert alert-danger mt-1">
                                  {errorsModal.create_inter_Errors.m_time}
                                </div>)}
                            </Form.Group>                      
                        </Col> 
                        <Col md={3}>
                            <Form.Group >
                            <Form.Label>Vệ Sịnh Cấp I</Form.Label>
                            <Form.Control type="text" name ='C1_time' value = {modalQuotaData.C1_time?? ""} onChange={(e) => setModalQuotaData({...modalQuotaData, C1_time: e.target.value})}    placeholder="HH:mm" 
                                          pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$" required/>
                                {errorsModal?.create_inter_Errors?.C1_time && (
                                <div className="alert alert-danger mt-1">
                                  {errorsModal.create_inter_Errors.C1_time}
                                </div>)}
                            </Form.Group>                      
                        </Col>
                        <Col md={3}>
                            <Form.Group >
                            <Form.Label>Vệ Sịnh Cấp II</Form.Label>
                            <Form.Control type="text" name ='C2_time' value = {modalQuotaData.C2_time?? ""} onChange={(e) => setModalQuotaData({...modalQuotaData, C2_time: e.target.value})}    placeholder="HH:mm" 
                                          pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$" required/>
                            
                                {errorsModal?.create_inter_Errors?.C2_time && (
                                <div className="alert alert-danger mt-1">
                                  {errorsModal.create_inter_Errors.C2_time}
                                </div>)}
                            </Form.Group>                      
                        </Col>
                    </Row> 
                    
                    <Row className="mb-3">
                      <Col md={3}>
                          <Form.Group >
                          <Form.Label>Số lô chiến dịch tối đa</Form.Label>
                          <Form.Control type="number" min= "0" name ='maxofbatch_campaign' value = {modalQuotaData.maxofbatch_campaign?? ""} onChange={(e) => setModalQuotaData({...modalQuotaData, maxofbatch_campaign: e.target.value})}  required  placeholder="Bắt buộc" />
                                {errorsModal?.create_inter_Errors?.maxofbatch_campaign && (
                                <div className="alert alert-danger mt-1">
                                  {errorsModal.create_inter_Errors.maxofbatch_campaign}
                                </div>)}

                          </Form.Group>                      
                      </Col>
                      <Col md={9}>
                          <Form.Group >
                          <Form.Label>Ghi Chú</Form.Label>
                          <Form.Control type="text" name ='note' value = {modalQuotaData.note?? ""} onChange={(e) => setModalQuotaData({...modalQuotaData, note: e.target.value})} placeholder="Không Bắt Buộc" />
                          </Form.Group>                      
                      </Col>
                    </Row>

                  </Form>

                </Modal.Body>

                <Modal.Footer >
                    <Button  className='btn btn-primary' onClick={() => setShowModalQuota (false)}>
                        Đóng
                    </Button>
                    <Button id = "btnQuotaSave" type='submit' className='btn btn-primary' 
                        icon={isSaving ? "pi pi-spin pi-spinner" : ""}
                        onClick={() => handleCreateQuota ()}> 
                        Lưu
                    </Button>
                </Modal.Footer>
      </Modal>

    </div>
    </div>
  );
};

export default ModalSidebar;


