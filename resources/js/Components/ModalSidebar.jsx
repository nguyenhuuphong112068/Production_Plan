import React, { useEffect, useRef, useState } from 'react';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Row, Col, Modal, Form } from 'react-bootstrap';
import { Checkbox } from 'primereact/checkbox';
import { Dropdown } from 'primereact/dropdown';
import './ModalSidebar.css'
import { InputSwitch } from 'primereact/inputswitch';
import Swal from 'sweetalert2';
import axios from "axios";


const ModalSidebar = ({ visible, onClose, waitPlan, setPlan, percentShow,
  setPercentShow, selectedRows, setSelectedRows, resources,
  type, currentPassword, lines, multiStage, setMultiStage,
  isMaintenance = false, excludeMaintenance = false }) => {

  const wrapperRef = useRef(null);

  const [stageFilter, setStageFilter] = useState(isMaintenance ? 8 : 1);
  const [visibleColumns, setVisibleColumns] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const sizes = ["close", "100%", "30%"];
  const [currentIndex, setCurrentIndex] = useState(1);
  const [tableData, setTableData] = useState([]);
  const [showModalCreate, setShowModalCreate] = useState(false);
  const [showModalQuota, setShowModalQuota] = useState(false);
  const [orderPlan, setOrderPlan] = useState({ checkedClearning: false, title: null, batch: "NA", level: 1 });
  const [modalQuotaData, setModalQuotaData] = useState({ name: "", intermediate_code: "" });
  const [errorsModal, setErrorsModal] = useState(null);
  const [isSaving, setIsSaving] = useState(false);
  const [optionRooms, setOptionRooms] = useState([]);
  const [unQuota, setUnQuota] = useState(0);
  const [isShowLine, setIsShowLine] = useState(false);
  const [selectedLine, setSelectedLine] = useState("S16");


  const columnWidths100 = {
    code: '8%',                // Mã sản phẩm
    permisson_room: '6%',      // Phòng SX
    name: '10%',               // Sản phẩm
    batch: '8%',               // Số lô
    expected_date: '6%',       // Ngày DK KCS
    responsed_date: '6%',       // Ngày DK KCS
    market: '3%',              // Thị trường
    level: '4%',               // Ưu tiên
    is_val: '5%',              // Thẩm định
    weight_dates: '5%',        // Cân NL
    pakaging_dates: '5%',      // Đóng gói
    source_material_name: '10%',// Nguồn nguyên liệu
    campaign_code: '6%',       // Mã chiến dịch
    note: '23%',               // Ghi chú
  };

  const columnWidths30 = {
    name: '45%',           // Sản phẩm
    batch: '20%',
    is_val: '2%',          // Số lô
    expected_date: '20%',  // Ngày DK KCS
    market: '7%',
    level: '8%',          // Ưu tiên
  };

  useEffect(() => {

    if (resources && resources.length > 0 && stageFilter) {
      let index_filter = stageFilter;

      if (index_filter == 2) {
        index_filter = 1;
      }

      const filtered = resources.filter(
        (q) => Number(q.stage_code) == Number(index_filter)
      );
      setOptionRooms(filtered);

    }
  }, [resources, stageFilter]);

  useEffect(() => {

    if (waitPlan && waitPlan.length > 0) {

      const filtered = waitPlan.filter(event => Number(event.stage_code) === stageFilter)
      setTableData(filtered);
      setUnQuota(tableData.filter(event => Array.isArray(event.permisson_room) && event.permisson_room.length === 0).length)
    }
  }, [waitPlan]);

  // chọn các cột cần show ở các độ rộng của modalsidebar
  useEffect(() => {
    let visibleCols = [];

    if (percentShow === "100%") {

      if (stageFilter === 1) {
        visibleCols = allColumns.filter(col => !["pakaging_dates",
          , "preperation_before_date", "blending_before_date", "coating_before_date"].includes(col.field));
      } else if (stageFilter === 2) {
        visibleCols = allColumns.filter(col => !["pakaging_dates",
          "expired_material_date", "preperation_before_date", "blending_before_date", "coating_before_date"].includes(col.field));
      } else if (stageFilter === 3) {
        visibleCols = allColumns.filter(col => !["pakaging_dates",
          , "blending_before_date", "coating_before_date", "allow_weight_before_date"].includes(col.field));
      } else if (stageFilter === 4) {
        visibleCols = allColumns.filter(col => !["pakaging_dates",
          "expired_material_date", "preperation_before_date", "coating_before_date", "allow_weight_before_date"].includes(col.field));
      } else if (stageFilter === 5) {
        visibleCols = allColumns.filter(col => !["pakaging_dates",
          "expired_material_date", "preperation_before_date", "coating_before_date", "blending_before_date", "allow_weight_before_date"].includes(col.field));
      } else if (stageFilter === 6) {
        visibleCols = allColumns.filter(col => !["pakaging_dates",
          "expired_material_date", "preperation_before_date", "blending_before_date", "allow_weight_before_date"].includes(col.field));
      } else if (stageFilter === 7) {
        visibleCols = allColumns.filter(col => !["weight_dates",
          "expired_material_date", "preperation_before_date", "coating_before_date", "blending_before_date", "allow_weight_before_date"].includes(col.field));
      } else if (stageFilter === 8) {
        visibleCols = allColumns.filter(col => ![
          "weight_dates", "pakaging_dates", "level",
          "batch", "market", "is_val", "source_material_name", "campaign_code",
          "clearning_validation", "immediately", "expected_date", "responsed_date",
          "expired_material_date", "preperation_before_date", "coating_before_date", "blending_before_date", "allow_weight_before_date"
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
      visibleCols = allColumns.filter(col => ["name", "batch", "is_val", "expected_date", "market", "level"].includes(col.field))
        .map(col => ({
          ...col,
          style: { ...col.style, width: columnWidths30[col.field] || 'auto', maxWidth: columnWidths30[col.field] || 'auto' }
        }));
    }

    setVisibleColumns(visibleCols);
  }, [percentShow, stageFilter]);


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

  const ImmediatelyBodyTemplate = (rowData) => (
    <Checkbox checked={rowData.immediately ? true : false} />
  );

  const clearningValidationBodyTemplate = (rowData) => (
    <Checkbox checked={rowData.clearning_validation ? true : false} />
  );

  const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    if (isNaN(date)) return dateStr; // nếu không parse được thì giữ nguyên
    return date.toLocaleDateString("vi-VN"); // sẽ thành dd/MM/yyyy
  };

  const weightPBodyTemplate = (rowData) => {
    const isEmpty = !rowData.after_weigth_date;

    return (
      <div style={{ display: "flex", flexDirection: "column" }}>
        <span
          style={{
            color: isEmpty ? "red" : "inherit",
            fontWeight: isEmpty ? 600 : 400,
          }}
        >
          {isEmpty
            ? "Chưa xác định"
            : formatDate(rowData.after_weigth_date)}
        </span>
      </div>
    );
  };

  const allowWeightBeforeDateTemplate = (rowData) => {
    const isEmpty = !rowData.allow_weight_before_date;

    return (
      <div style={{ display: "flex", flexDirection: "column" }}>
        <span
          style={{
            color: isEmpty ? "inherit" : "red",
            fontWeight: isEmpty ? 600 : 400,
          }}
        >
          {isEmpty
            ? "-"
            : formatDate(rowData.allow_weight_before_date)}
        </span>
      </div>
    );
  };


  const preperationBeforeDateTemplate = (rowData) => {
    const isEmpty = !rowData.preperation_before_date;

    return (
      <div style={{ display: "flex", flexDirection: "column" }}>
        <span
          style={{
            color: isEmpty ? "inherit" : "red",
            fontWeight: isEmpty ? 600 : 400,
          }}
        >
          {isEmpty
            ? "-"
            : formatDate(rowData.preperation_before_date)}
        </span>
      </div>
    );
  };

  const blendingBeforeDateTemplate = (rowData) => {
    const isEmpty = !rowData.blending_before_date;

    return (
      <div style={{ display: "flex", flexDirection: "column" }}>
        <span
          style={{
            color: isEmpty ? "inherit" : "red",
            fontWeight: isEmpty ? 600 : 400,
          }}
        >
          {isEmpty
            ? "-"
            : formatDate(rowData.blending_before_date)}
        </span>
      </div>
    );
  };

  const coatingBeforeDateTemplate = (rowData) => {
    const isEmpty = !rowData.coating_before_date;

    return (
      <div style={{ display: "flex", flexDirection: "column" }}>
        <span
          style={{
            color: isEmpty ? "inherit" : "red",
            fontWeight: isEmpty ? 600 : 400,
          }}
        >
          {isEmpty
            ? "-"
            : formatDate(rowData.coating_before_date)}
        </span>
      </div>
    );
  };

  const expiredMaterialDateTemplate = (rowData) => {
    const isEmpty = !rowData.expired_material_date;

    return (
      <div style={{ display: "flex", flexDirection: "column" }}>
        <span
          style={{
            color: isEmpty ? "inherit" : "red",
            fontWeight: isEmpty ? 600 : 400,
          }}
        >
          {isEmpty
            ? "-"
            : formatDate(rowData.expired_material_date)}
        </span>
      </div>
    );
  };

  const packagingBodyTemplate = (rowData) => {
    const isEmpty = !rowData.after_parkaging_date;

    return (
      <div style={{ display: "flex", flexDirection: "column" }}>
        <span
          style={{
            color: isEmpty ? "red" : "inherit",
            fontWeight: isEmpty ? 600 : 400,
          }}
        >
          {isEmpty
            ? "Chưa xác định"
            : formatDate(rowData.after_parkaging_date)}
        </span>
      </div>
    );
  };

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
          display: `${rowData.campaign_code ? 'block' : 'none'}`
        }}
      >
        {rowData.campaign_code}
      </div>
    );
  };

  const stageNames = {
    1: 'Cân',
    2: 'NL Khác',
    3: 'Pha Chế',
    4: 'Trộn Hoàn Tất',
    5: 'Định Hình',
    6: 'Bao Phim',
    7: 'ĐGSC-ĐGTC',
    8: 'Hiệu Chuẩn - Bảo Trì',
    9: 'Sự Kiện Khác',
  };

  const stageOptions = Object.keys(stageNames)
    .filter(key => !excludeMaintenance || Number(key) !== 8)
    .map(key => ({
      label: `${key}. ${stageNames[key]}`,
      value: Number(key),
    }));

  const handleSelectionChange = (e) => {
    const currentSelection = e.value ?? null;

    if (!currentSelection || currentSelection.length === 0) {
      setSelectedRows([]); // reset nếu không có selection
      return;
    }

    // ✅ check stage_code = 8
    const lastSelected = currentSelection[currentSelection.length - 1];


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

  const handleChangeStage = (selected_stage) => {

    if (isSaving) return;
    setIsSaving(true);
    let stage_plan = waitPlan.filter(event => Number(event.stage_code) === selected_stage)
    setTableData(stage_plan);
    setUnQuota(stage_plan.filter(event => Array.isArray(event.permisson_room) && event.permisson_room.length === 0).length)

    setStageFilter(selected_stage);

    setSelectedRows([]);
    setIsSaving(false);
  }

  const handleRowReorder = (e) => {

    const { value: newData, dropIndex, dragIndex } = e;

    //console.log (selectedRows.length)
    // Nếu chưa chọn gì thì chỉ cần đánh lại order_by
    if (!selectedRows || selectedRows.length == 0) {
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
      //console.log (payload)
      setPlan(updateOrderData)


      // Gửi lên server
      axios.put('/Schedual/updateOrder', { updateOrderData: payload })
        .then(res => {
          alert("sa")
          let data = res.data;
          if (typeof data === "string") {
            data = data.replace(/^<!--.*?-->/, "").trim();
            data = JSON.parse(data);
          }
          setPlan(data.plan);
        }
        ).catch(err => {
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

    setPlan(updateOrderData)

    axios.put('/Schedual/updateOrder',
      {
        updateOrderData: payload,
        isShowLine: isShowLine
      })
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }
        setPlan(data.plan);
      }
      ).catch(err => {
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
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }
        setPlan(data.plan);
      }
      ).catch(err => {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi',
          timer: 1500
        });
        console.error("API error:", err.response?.data || err.message);
      }
      );

    setIsSaving(false);
    setSelectedRows([]);
    return;
  }

  const handleImmediately = (e) => {
    if (isSaving) return;
    setIsSaving(true);

    const filteredRows = selectedRows.map(row => ({
      id: row.id,
      plan_master_id: row.plan_master_id,
      product_caterogy_id: row.product_caterogy_id,
      predecessor_code: row.predecessor_code,
      campaign_code: row.campaign_code,
      code: row.code,
      immediately: row.immediately
    }));

    axios.put('/Schedual/immediately', { data: filteredRows })
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }
        setPlan(data.plan);
      }
      ).catch(err => {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi',
          timer: 1500
        });
        console.error("API error:", err.response?.data || err.message);
      }
      );

    setIsSaving(false);
    setSelectedRows([]);
    return;
  }

  const handleCreateManualCampainStage = (e) => {
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

    axios.put('/Schedual/createManualCampainStage', { data: filteredRows, stage_code: stageFilter })
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }
        setPlan(data.plan);
      }
      ).catch(err => {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi',
          timer: 1500
        });
        console.error("API error:", err.response?.data || err.message);
      }
      );

    setIsSaving(false);
    setSelectedRows([]);
    return;
  }

  const handleCreateAutoCampain = async () => {


    const { value } = await Swal.fire({
      title: "Tạo Mã Chiến Dịch Tự Động",
      width: "500px",
      html: `
        <div style="text-align:center">

          <div class="sort-option">
            <label class="sort-card">
              <input type="radio" name="sortType" value="kcs" checked>
              <span>📅 Theo ngày KCS</span>
            </label>

            <label class="sort-card">
              <input type="radio" name="sortType" value="response">
              <span>📦 Theo ngày đáp ứng</span>
            </label>
          </div>

          <hr/>

          <input
            id="swal-password"
            type="password"
            class="swal2-input passWord-swal-input"
            placeholder="Nhập mật khẩu..."
          />
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: "Xác nhận",
      cancelButtonText: "Hủy",

      preConfirm: () => {
        const password = document.getElementById('swal-password').value;
        const sortType = document.querySelector('input[name="sortType"]:checked')?.value;

        if (!password) {
          Swal.showValidationMessage('Bạn phải nhập mật khẩu!');
          return false;
        }

        return {
          password,
          sortType
        };
      }
    });


    if (!value) return;
    const { password, sortType } = value;

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

    axios.put('/Schedual/createAutoCampain', { stage_code: stageFilter, mode: sortType })
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }

        setPlan(data.plan);
        const filtered = data.plan.filter(event => Number(event.stage_code) === stageFilter)
        setTableData(filtered);

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
      ).catch(err => {
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

  const handleDeleteCampainStage = async () => {

    const { value: password } = await Swal.fire({
      title: "Nhập Mật Khẩu",
      width: "500px",
      text: "Bạn Muốn Xóa Toạn Bộ Mã Chiến Dịch!",
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

    const filteredRows = selectedRows.map(row => ({
      id: row.id,
      plan_master_id: row.plan_master_id,
    }));

    axios.put('/Schedual/DeleteAutoCampain', { data: filteredRows })
      .then(res => {
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
          text: 'Mã Chiến Dịch Đã Xóa',
          icon: 'success',
          confirmButtonText: 'OK'
        }).then(() => {
          setSelectedRows([]);
        });

      }
      ).catch(err => {
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

    if (orderPlan.title === null) {
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
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }
        setPlan(data.plan);
        setShowModalCreate(false)
        Swal.fire({
          title: 'Thành công!',
          text: 'Tạo Mới Kế Hoạch Khác',
          icon: 'success',
          showConfirmButton: false,
          timer: 1500
        }).then(() => {
          setOrderPlan({});
          setErrorsModal({})
        });

      }
      ).catch(err => {
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
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }
        setPlan(data.plan);
        setShowModalCreate(false)
        Swal.fire({
          title: 'Thành công!',
          text: 'Xóa Sự Kiện Khác Thành Công',
          icon: 'success',
          showConfirmButton: false,
          timer: 1500
        }).then(() => {
          setOrderPlan({});
          setErrorsModal({})
        });

      }
      ).catch(err => {
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
    if (field === "responsed_date") {
      if (!rowData.responsed_date) return "NA";

      const date = new Date(rowData.responsed_date);
      if (isNaN(date)) return rowData.responsed_date; // giá trị không hợp lệ thì giữ nguyên
      return date.toLocaleDateString("vi-VN"); // format mặc định: dd/MM/yyyy
    }

    return rowData[field] ?? "NA";
  };

  const handleOpenQuota = (rowData) => {
    if (rowData.stage_code !== 9) {
      setModalQuotaData({
        name: rowData.name,
        finished_product_code: rowData.finished_product_code,
        intermediate_code: rowData.intermediate_code,
        stage_code: rowData.stage_code
      });
      setShowModalQuota(true);
    }
  };

  const handleCreateQuota = () => {
    if (isSaving) return;
    setIsSaving(true);

    axios.put('/quota/production/store', modalQuotaData)
      .then(res => {
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
          setErrorsModal({})
          setIsSaving(false);
        });

      }
      ).catch(err => {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi',
          timer: 1500
        });
        setErrorsModal(errors)
        setIsSaving(false);
        console.error("API error:", err.response?.data || err.message);
      }
      );

  }

  const handleFinished = () => {
    if (isSaving) return;
    setIsSaving(true);
    if (selectedRows.length == 0) {
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


    axios.put('/Schedual/finished', { id: ids, temp: true, stage_code: stageCode })

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

  const handleSorted = async () => {

    // 1️⃣ Popup chọn kiểu sắp xếp + mật khẩu
    const { value: formData } = await Swal.fire({
      title: "Sắp Xếp Thứ Tự Lô Sản Phẩm",
      width: "600px",
      html: `
        <div style="text-align:center">

          <div class="sort-option">
            <label class="sort-card">
              <input type="radio" name="sortType" value="kcs" checked>
              <span>📅 Theo ngày KCS</span>
            </label>

            <label class="sort-card">
              <input type="radio" name="sortType" value="response">
              <span>📦 Theo ngày đáp ứng</span>
            </label>
          </div>

          <div id="response-date-wrap" class="response-date-wrap" style="display: none;">
              <input
                id="response-date"
                type="date"
                class="swal2-input response-date-input"
              />
          </div>

          <hr/>

          <input
            id="swal-password"
            type="password"
            class="swal2-input passWord-swal-input"
            placeholder="Nhập mật khẩu..."
          />

        </div>
      `,
      didOpen: () => {
        const radios = document.querySelectorAll('input[name="sortType"]');
        const dateWrap = document.getElementById('response-date-wrap');

        radios.forEach(radio => {
          radio.addEventListener('change', () => {
            dateWrap.style.display =
              radio.value === 'response' && radio.checked
                ? 'block'
                : 'none';
          });
        });
      },
      showCancelButton: true,
      confirmButtonText: "Xác nhận",
      cancelButtonText: "Hủy",
      focusConfirm: false,
      preConfirm: () => {
        const password = document.getElementById("swal-password").value;
        const sortType = document.querySelector('input[name="sortType"]:checked')?.value;
        const responseDate = document.getElementById("response-date")?.value;

        if (!password) {
          Swal.showValidationMessage("Bạn phải nhập mật khẩu!");
          return false;
        }

        if (sortType === "response" && !responseDate && selectedRows.length > 0) {
          Swal.showValidationMessage("Vui lòng chọn ngày đáp ứng!");
          return false;
        }

        return { password, sortType, responseDate };
      }
    });

    if (!formData) return;

    // 2️⃣ Validate mật khẩu
    const { password, sortType, responseDate } = formData;

    if (password !== currentPassword) {
      Swal.fire({
        icon: "error",
        title: "Sai mật khẩu!",
        timer: 1500,
        showConfirmButton: false,
      });
      return;
    }

    // 3️⃣ Nếu sort theo response → bắt buộc chọn dòng

    // if (
    //   sortType === "response" &&
    //   (!selectedRows || selectedRows.length === 0)
    // ) {
    //   Swal.fire({
    //     icon: "warning",
    //     title: "Chưa chọn kế hoạch",
    //     text: "Vui lòng chọn ít nhất một dòng!",
    //     timer: 1500,
    //     showConfirmButton: false,
    //   });
    //   return;
    // }

    // 4️⃣ Chuẩn bị plan_master_ids
    const planMasterIds =
      sortType === "response"
        ? selectedRows.map(row => row.plan_master_id)
        : [];

    // 5️⃣ Gửi request
    if (isSaving) return;
    setIsSaving(true);

    Swal.fire({
      title: "Đang Sắp Xếp Lại, vui lòng đợi giây lát..",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    axios.put('/Schedual/Sorted', {
      stage_code: stageFilter,
      sortType,
      response_date: responseDate || null,
      plan_master_ids: planMasterIds,
    })
      .then(res => {
        let data = res.data;

        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }

        setPlan(data.plan);

        Swal.fire({
          icon: 'success',
          title: 'Hoàn Thành',
          timer: 600,
          showConfirmButton: false,
        });
      })
      .catch(err => {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi',
          timer: 1500,
          showConfirmButton: false,
        });
        console.error("Sorted error:", err.response?.data || err.message);
      })
      .finally(() => {
        setIsSaving(false);
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

  const roomBody = (rowData) => {

    if (!rowData.permisson_room || rowData.permisson_room.length === 0) {
      return (
        <span
          className='btn'
          onClick={() => { if (rowData.stage_code !== 9) { handleOpenQuota(rowData) } }}
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
          {`${rowData.stage_code !== 9 ? "Thiếu Định Mức" : "Không Định Mức"}`}
        </span>
      );
    } else {

      const permisson_room = Object.values(rowData.permisson_room || {});

      if (permisson_room.length === 1) {
        return Object.values(rowData.permisson_room).join(", ");
      }

      const handleRoomChange = (room, id, checked) => {
        axios.put('/Schedual/required_room', { room_code: room, stage_plan_id: id, checked: checked })
          .then(res => {
            let data = res.data;
            if (typeof data === "string") {
              data = data.replace(/^<!--.*?-->/, "").trim();
              data = JSON.parse(data);
            }

            setPlan(data.plan)

          }).catch(err => {
            console.log(err)
            Swal.fire({
              icon: 'warning',
              title: err.message,
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
                onChange={(e) => handleRoomChange(room, rowData.id, e.checked)}
              />
              <label htmlFor={room + rowData.id}>{room}</label>
            </div>
          ))}
        </div>
      );
    };
  }

  const handleConfirmClearningValidation = (e) => {
    if (isSaving) return;
    setIsSaving(true);

    const filteredRows = selectedRows.map(row => ({
      id: row.id,
      clearning_validation: row.clearning_validation
    }));



    axios.put('/Schedual/clearningValidation', { data: filteredRows })
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }
        setPlan(data.plan);
      }
      ).catch(err => {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi',
          timer: 1500
        });
        console.error("API error:", err.response?.data || err.message);
      }
      );

    setIsSaving(false);
    setSelectedRows([]);
    return;
  }

  const hasAnyRoom = (filterStr, userRoomStr) => {
    if (!filterStr || !userRoomStr) return false;

    const filterArr = filterStr
      .split(',')
      .map(r => r.trim());

    return filterArr.includes(userRoomStr.trim());
  };

  const handleShowLine = (room, type_search) => {
    //if (isSaving) return;
    //setIsSaving(true); Number(event.stage_code) === stageFilter &&
    if (type_search) {
      setIsShowLine(!isShowLine)
      const filtered = waitPlan.filter(event => Number(event.stage_code) === stageFilter)
      setTableData(filtered);
      setSelectedRows([]);
      return;
    }


    const filtered = waitPlan
      .filter(event =>
        (
          hasAnyRoom(event.permisson_room_filter, room) &&
          Object.values(event.permisson_room || {}).length === 1
        ) ||
        event.required_room_code === room
      )
      .sort((a, b) => {
        const aOrder = Number(a.order_by_line ?? 0);
        const bOrder = Number(b.order_by_line ?? 0);
        return aOrder - bOrder; // ASC
      });

    setTableData(filtered);

    setSelectedRows([]);

  };


  const longTextStyle = { whiteSpace: 'normal', wordBreak: 'break-word' };

  const allColumns = [
    { field: "month", header: "tháng", sortable: true, filter: false, filterField: "month" },
    { field: "code", header: "Mã Sản Phẩm", sortable: true, body: productCodeBody, filter: false, filterField: "code", style: { width: '5%', maxWidth: '5%', ...longTextStyle } },
    { field: "permisson_room", header: "Phòng SX", sortable: true, body: roomBody, filter: false, filterField: "permisson_room", style: { minWidth: '3%', maxWidth: '3%', ...longTextStyle } },
    { field: "name", header: "Sản Phẩm", sortable: true, body: naBody("name"), filter: false, filterField: "name", style: { width: '20%', maxWidth: '20%', ...longTextStyle } },
    { field: "batch", header: "Số Lô", sortable: true, body: naBody("batch"), filter: false, filterField: "batch", style: { width: '10%', maxWidth: '15%', ...longTextStyle } },
    { field: "market", header: "TT", sortable: true, body: naBody("market"), filter: false, filterField: "market", style: { width: '8rem', maxWidth: '8rem', ...longTextStyle } },
    { field: "expected_date", header: "Ngày DK KCS", body: naBody("expected_date"), filter: false, filterField: "expected_date", style: { width: '5%', maxWidth: '7.5%', ...longTextStyle } },
    { field: "responsed_date", header: "Ngày đáp ứng", body: naBody("responsed_date"), filter: false, filterField: "responsed_date", style: { width: '5%', maxWidth: '7.5%', ...longTextStyle } },
    { field: "level", header: "Ưu tiên", sortable: true, body: statusOrderBodyTemplate, style: { width: '5%', maxWidth: '5%', ...longTextStyle } },
    { field: "is_val", header: "Thẩm Định", body: ValidationBodyTemplate, style: { width: '5rem', maxWidth: '5rem', ...longTextStyle } },

    { field: "weight_dates", header: "Ngày có NL", sortable: true, body: weightPBodyTemplate },
    { field: "allow_weight_before_date", header: "Ngày Được Cân", sortable: true, body: allowWeightBeforeDateTemplate },
    { field: "expired_material_date", header: "Ngày HH NL", sortable: true, body: expiredMaterialDateTemplate, filter: false },

    { field: "preperation_before_date", header: "PC trước", sortable: true, body: preperationBeforeDateTemplate, filter: false },
    { field: "blending_before_date", header: "THT trước", sortable: true, body: blendingBeforeDateTemplate, filter: false },
    { field: "coating_before_date", header: "BP trước", sortable: true, body: coatingBeforeDateTemplate, filter: false },

    { field: "pakaging_dates", header: "Ngày có BB", sortable: true, body: packagingBodyTemplate },

    { field: "source_material_name", header: "Nguồn nguyên liệu", sortable: true, body: naBody("source_material_name"), style: { width: '25rem', maxWidth: '25rem', ...longTextStyle } },
    { field: "campaign_code", header: "Mã Chiến Dịch", sortable: true, body: campaignCodeBody, style: { width: '8rem', maxWidth: '8rem', ...longTextStyle } },
    { field: "immediately", header: (<><i className="fa fa-bolt me-1"></i></>), body: ImmediatelyBodyTemplate, style: { width: '5rem', maxWidth: '5rem', ...longTextStyle } },
    { field: "clearning_validation", header: "🚿", body: clearningValidationBodyTemplate, style: { width: '5rem', maxWidth: '5rem', ...longTextStyle } },
    { field: "note", header: "Ghi chú", sortable: true, body: naBody("note"), filter: false, filterField: "note", style: { width: '20%', maxWidth: '20%', ...longTextStyle } },
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

              {percentShow === "100%" && stageFilter <= 7 ? (
                <>
                  {unQuota > 0 && (
                    <div className="fc-event px-3 py-1 bg-red-400 border border-red-400 rounded text-md text-center cursor-pointer mr-3"
                      onClick={filterUnQuotaRow}>
                      {unQuota} Lô Thiếu Định Mức
                    </div>)}

                  {/* <div className="fc-event px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Mã Chiến Dịch Với Các Sản Phẩm Đã Chọn Ở Công Đoạn Hiện Tại"
                onClick={handleCreateManualCampain}>
                 {isSaving === false ?<i className="fas fa-cube"></i> :<i className="fas fa-spinner fa-spin fa-lg"></i>} ({selectedRows.length})
              </div> */}

                  <div className="fc-event px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Mã Chiến Dịch Với Các Sản Phẩm Được Chọn Ở Tất Cả Các Công Đoạn"
                    onClick={handleCreateManualCampainStage}>
                    {isSaving === false ? <i className="fas fa-cubes"></i> : <i className="fas fa-spinner fa-spin fa-lg"></i>} ({selectedRows.length})
                  </div>

                  <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Xóa Mã Chiến Dịch Công Đoạn Của Các Dòng Được Chọn"
                    onClick={handleDeleteCampainStage}>
                    {isSaving === false ? <i className="fas fa-trash"></i> : <i className="fas fa-spinner fa-spin fa-lg"></i>}
                  </div>

                  <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Lệnh Sắp Ngay Sau Kết Thúc Công Đoạn Trước"
                    onClick={handleImmediately}>
                    {isSaving === false ? <i className="fa fa-bolt"></i> : <i className="fas fa-spinner fa-spin fa-lg"></i>}
                  </div>

                  {stageFilter == 3 && (
                    <>
                      <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Mã Chiến Dịch tự Động Cho Tất Cả Công Đoạn"
                        onClick={handleCreateAutoCampain}>
                        {isSaving === false ? <i className="fas fa-flag-checkered"></i> : <i className="fas fa-spinner fa-spin fa-lg"></i>}
                      </div>
                    </>
                  )}

                  <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Sắp xếp lại theo kế hoạch tháng"
                    onClick={handleSorted}>
                    {isSaving === false ? <i className="fas fa-sort"></i> : <i className="fas fa-spinner fa-spin fa-lg"></i>}
                  </div>

                  <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Xác định lô thẩm định vệ sinh"
                    onClick={handleConfirmClearningValidation}>
                    {isSaving === false ? "🚿" : <i className="fas fa-spinner fa-spin fa-lg"></i>}
                  </div>

                  {stageFilter > 2 && stageFilter < 8 ? (
                    <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Xác định lô thẩm định vệ sinh"
                      onClick={() => handleShowLine(null, true)}>
                      {isShowLine === false ? "Line" : "Stage"}
                    </div>)
                    : <></>}
                </>) : <>

                <div
                  className={`fc-event px-3 py-1 border rounded text-md text-center cursor-pointer mr-3
                    ${multiStage
                      ? 'bg-green-100 border-green-800 text-green-700'
                      : 'bg-gray-200 border-gray-400 text-gray-600'
                    }`}
                  title="Bật / Tắt chế độ sắp liên tục các công đoạn sau"
                  onClick={() => setMultiStage(prev => !prev)}
                >
                  {isSaving === false
                    ? <i className="fas fa-project-diagram"></i>
                    : <i className="fas fa-spinner fa-spin fa-lg"></i>
                  }
                </div>


              </>}

              {percentShow === "100%" && stageFilter === 9 && type ? (
                <>
                  <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Sự Kiện Khác"
                    onClick={() => setShowModalCreate(true)}>
                    <i className="fas fa-plus"></i>
                  </div>

                  <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Xóa Sự Kiện Khác"
                    onClick={handleDeActiveOrderPlan}>
                    <i className="fas fa-trash"></i>
                  </div>
                </>
              ) : <></>}



            </Col>

            <Col md={6}>
              <div className="p-inputgroup flex-1">
                {isMaintenance ? (
                  <div className="maintenance-title w-full text-center p-2 rounded bg-blue-50 text-blue-800 fw-bold border border-blue-200">
                    <i className="fas fa-tools me-2"></i>HIỆU CHUẨN - BẢO TRÌ
                  </div>
                ) : percentShow === "100%" && !isShowLine ? (
                  <>
                    <Dropdown
                      value={stageFilter}
                      options={stageOptions}
                      onChange={(e) => handleChangeStage(e.value)}
                      className="stage-dropdown"
                      panelClassName="stage-dropdown-panel"
                      itemTemplate={(option) => (
                        <div className="d-flex justify-content-between w-100">
                          <span>{option.label}</span>
                          <span className="badge bg-secondary"></span>
                        </div>
                      )}
                    />
                  </>
                ) : percentShow === "100%" && isShowLine ? (
                  <Dropdown
                    value={selectedLine}
                    onChange={(e) => {
                      setSelectedLine(e.value);
                      handleShowLine(e.value.name, false); // truyền cả object
                    }}
                    options={lines[stageFilter]}
                    optionLabel="name_code"
                    placeholder="Chọn Phòng Sản Xuất"
                    className="w-full md:w-14rem fw-bold text-center"
                  />
                ) : (
                  <>
                    <Dropdown
                      value={stageFilter}
                      options={stageOptions}
                      onChange={(e) => handleChangeStage(e.value)}
                      className="stage-dropdown"
                      panelClassName="stage-dropdown-panel"
                      itemTemplate={(option) => (
                        <div className="d-flex justify-content-between w-100">
                          <span>{option.label}</span>
                          <span className="badge bg-secondary"></span>
                        </div>
                      )}
                    />
                  </>
                )}
              </div>
            </Col>
            <Col md={3} className='d-flex justify-content-end'>

              {/* <div className="fc-event  px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Xác Nhận Hoàn Thành Lô Sản Xuất"
                onClick={handleFinished}>
                {isSaving === false ? <i className="fas fa-check"></i>:<i className="fas fa-spinner fa-spin fa-lg"></i>}
            </div> */}



              {/* {percentShow === "100%" ? ():""} */}
              <InputText className='border mr-5'
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Tìm kiếm..."
                style={{ width: "50%" }}
              />



              {percentShow != "close" ? (
                <div onClick={handleToggle} className='me-3' style={{ width: '30px', height: '30px', marginRight: '1%' }} title="Điều Chỉnh Độ Rộng Side Bar">
                  <img src="/img/iconstella.svg" style={{ width: '40px', height: '40px' }} />
                </div>) : ""}
            </Col>
          </Row>

        </div>

        {/* Khu vực bảng */}
        <div style={{ flex: 1, overflow: 'auto' }}>
          <DataTable
            className="p-datatable-gridlines prime-gridlines"
            key={percentShow}
            value={tableData}
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
              "responsed_date",
              "level",
              "note",
              "code",
              "source_material_name",
              "instrument_code",
              "finished_product_code",
              "month"
            ]}



          >
            {percentShow === "100%" ? (
              <Column
                header="STT"
                body={(rowData, options) => (
                  <div style={{ textAlign: "center" }}>
                    <div>{options.rowIndex + 1}</div>
                    <div>{rowData.id}</div>
                  </div>
                )}
                style={{ width: "60px", textAlign: "center" }}
              />) : ""}



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
        <Modal size="lg" show={showModalCreate} aria-labelledby="example-modal-sizes-title-lg" onHide={() => setShowModalCreate(false)}>
          <Modal.Header style={{ color: '#cdc717', backgroundColor: '#ffffffff' }}>
            <img src="/img/iconstella.svg" style={{ width: '30px', marginRight: '10px' }} />
            <Modal.Title
              id="example-modal-sizes-title-lg"
              className="mx-auto fw-bold"
            >
              Tạo Mới Sự Kiện
            </Modal.Title>
          </Modal.Header>
          <Modal.Body style={{ fontSize: '20px' }}>
            <Form>
              <Row className="mb-3">
                <Form.Group as={Col} >
                  <Form.Label>Tên sự kiện</Form.Label>
                  <Form.Control type="text" name='fullName' value={orderPlan.title ?? ""} onChange={(e) => setOrderPlan({ ...orderPlan, title: e.target.value })} placeholder="Bắt buộc" />
                  {errorsModal?.create_inter_Errors?.room_id && (
                    <div className="alert alert-danger mt-1">
                      {errorsModal.create_inter_Errors.room_id}
                    </div>)}
                </Form.Group>
              </Row>

              <Row className="mb-3">
                <Form.Group as={Col} >
                  <Form.Label>Số lô</Form.Label>
                  <Form.Control type="text" name='fullName' value={orderPlan.batch ?? ""} onChange={(e) => setOrderPlan({ ...orderPlan, batch: e.target.value })} placeholder="Không Bắt buộc, nếu không có mặc định NA" />
                </Form.Group>

                <Form.Group as={Col} >
                  <Form.Label>Số Lượng sự kiện</Form.Label>
                  <Form.Control type="number" name='number_of_batch' min="1" value={orderPlan.number_of_batch ?? 1} onChange={(e) => setOrderPlan({ ...orderPlan, number_of_batch: e.target.value })} />
                </Form.Group>

              </Row>

              <Row className="mb-3">
                <Form.Group as={Col} >
                  <div className='text-center d-flex justify-content-start'>
                    <Form.Label className='mr-5'>Có vệ sinh lại không: </Form.Label>
                    <span className='ml-5 mr-5'> Không </span>
                    <InputSwitch checked={orderPlan.checkedClearning ?? ""} onChange={(e) => setOrderPlan({ ...orderPlan, checkedClearning: !orderPlan.checkedClearning })} />
                    <span className='ml-5 mr-5'> Có </span>
                  </div>
                </Form.Group>
              </Row>

              <Row className="mb-3">
                <Form.Group as={Col} >
                  <Form.Label>Ghi chú</Form.Label>
                  <Form.Control type="text" name='fullName' value={orderPlan.note ?? ""} onChange={(e) => setOrderPlan({ ...orderPlan, note: e.target.value })} placeholder="Bắt buộc" />
                </Form.Group>
              </Row>

            </Form>

          </Modal.Body>

          <Modal.Footer >
            <Button className='btn btn-primary' onClick={() => setShowModalCreate(false)}>
              Hủy
            </Button>
            <Button className='btn btn-primary' onClick={() => handleCreateOrderPlan(false)}>
              Lưu
            </Button>

          </Modal.Footer>
        </Modal>


        {/* Tạo Đinh Mức */}
        <Modal size="xl" show={showModalQuota} aria-labelledby="example-modal-sizes-title-lg" onHide={() => setShowModalQuota(false)}>
          <Modal.Header style={{ color: '#cdc717', backgroundColor: '#ffffffff' }}>
            <img src="/img/iconstella.svg" style={{ width: '30px', marginRight: '10px' }} />
            <Modal.Title
              id="example-modal-sizes-title-lg"
              className="mx-auto fw-bold"
            >
              Định Mức Sản Xuất
            </Modal.Title>
          </Modal.Header>
          <Modal.Body style={{ fontSize: '20px' }}>

            <Form  >
              <Row className="mb-3">
                <Col md={9}>
                  <Form.Group >
                    <Form.Label>Tên Sản Phẩm</Form.Label>
                    <Form.Control type="text" name='name' value={modalQuotaData.name ?? ""} readOnly />
                  </Form.Group>
                </Col>
                <Col md={3}>
                  <Form.Group >
                    <Form.Label>Mã Sản Phẩm</Form.Label>
                    <Form.Control type="text" name='product_code' value={modalQuotaData.stage_code <= 6 ? modalQuotaData.intermediate_code : modalQuotaData.finished_product_code} readOnly />
                  </Form.Group>
                </Col>
              </Row>

              <Row className="mb-3">
                <Form.Group as={Col} >
                  <Form.Label>Phòng Sản Xuất</Form.Label>

                  <select className="form-control" name="room_id[]" multiple="multiple" style={{ width: "100%", height: "30mm" }}
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
                    <Form.Control type="text" name='p_time' value={modalQuotaData.p_time ?? ""} onChange={(e) => setModalQuotaData({ ...modalQuotaData, p_time: e.target.value })} placeholder="HH:mm"
                      pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$" required
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
                    <Form.Control type="text" name='m_time' value={modalQuotaData.m_time ?? ""} onChange={(e) => setModalQuotaData({ ...modalQuotaData, m_time: e.target.value })} placeholder="HH:mm"
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
                    <Form.Control type="text" name='C1_time' value={modalQuotaData.C1_time ?? ""} onChange={(e) => setModalQuotaData({ ...modalQuotaData, C1_time: e.target.value })} placeholder="HH:mm"
                      pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$" required />
                    {errorsModal?.create_inter_Errors?.C1_time && (
                      <div className="alert alert-danger mt-1">
                        {errorsModal.create_inter_Errors.C1_time}
                      </div>)}
                  </Form.Group>
                </Col>
                <Col md={3}>
                  <Form.Group >
                    <Form.Label>Vệ Sịnh Cấp II</Form.Label>
                    <Form.Control type="text" name='C2_time' value={modalQuotaData.C2_time ?? ""} onChange={(e) => setModalQuotaData({ ...modalQuotaData, C2_time: e.target.value })} placeholder="HH:mm"
                      pattern="^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$" required />

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
                    <Form.Control type="number" min="0" name='maxofbatch_campaign' value={modalQuotaData.maxofbatch_campaign ?? ""} onChange={(e) => setModalQuotaData({ ...modalQuotaData, maxofbatch_campaign: e.target.value })} required placeholder="Bắt buộc" />
                    {errorsModal?.create_inter_Errors?.maxofbatch_campaign && (
                      <div className="alert alert-danger mt-1">
                        {errorsModal.create_inter_Errors.maxofbatch_campaign}
                      </div>)}

                  </Form.Group>
                </Col>

                {stageFilter <= 2 && (
                  <Col md={3}>
                    <Form.Group >
                      <Form.Label>Hệ Số Chiến Dịch</Form.Label>
                      <Form.Control type="number" min="1" step="0.1" name='campaign_index' value={modalQuotaData.campaign_index ?? 1.0} onChange={(e) => setModalQuotaData({ ...modalQuotaData, campaign_index: e.target.value })} required placeholder="Bắt buộc" />
                      {errorsModal?.create_inter_Errors?.maxofbatch_campaign && (
                        <div className="alert alert-danger mt-1">
                          {errorsModal.create_inter_Errors.maxofbatch_campaign}
                        </div>)}

                    </Form.Group>
                  </Col>)}

                <Col md={6}>
                  <Form.Group >
                    <Form.Label>Ghi Chú</Form.Label>
                    <Form.Control type="text" name='note' value={modalQuotaData.note ?? ""} onChange={(e) => setModalQuotaData({ ...modalQuotaData, note: e.target.value })} placeholder="Không Bắt Buộc" />
                  </Form.Group>
                </Col>
              </Row>

            </Form>

          </Modal.Body>

          <Modal.Footer >
            <Button className='btn btn-primary' onClick={() => setShowModalQuota(false)}>
              Đóng
            </Button>
            <Button id="btnQuotaSave" type='submit' className='btn btn-primary'
              icon={isSaving ? "pi pi-spin pi-spinner" : ""}
              onClick={() => handleCreateQuota()}>
              Lưu
            </Button>
          </Modal.Footer>
        </Modal>

      </div>
    </div>
  );
};

export default ModalSidebar;


