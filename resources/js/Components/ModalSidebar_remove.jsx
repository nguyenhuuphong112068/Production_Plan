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
  excludeMaintenance = true }) => {

  const wrapperRef = useRef(null);

  const [stageFilter, setStageFilter] = useState(1);
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
      let filtered = waitPlan.filter(event => Number(event.stage_code) === stageFilter);

      setTableData(filtered);
      setUnQuota(filtered.filter(event => Array.isArray(event.permisson_room) && event.permisson_room.length === 0).length);
    } else {
      setTableData([]);
    }
  }, [waitPlan, stageFilter]);

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
          "expired_material_date", "preperation_before_date", "coating_before_date", "allow_weight_before_date"].includes(col.field));
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
        visibleCols = allColumns.filter(col => !["weight_dates", "pakaging_dates", "Inst_Name", "Parent_Equip_id", "Eqp_name"].includes(col.field));
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
    if (isNaN(date)) return dateStr;
    return date.toLocaleDateString("vi-VN");
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
          backgroundColor: color + "33",
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
    9: 'Sự Kiện Khác',
  };
  const stageOptions = Object.keys(stageNames)
    .map(key => ({
      label: `${key}. ${stageNames[key]}`,
      value: Number(key),
    }));

  const handleSelectionChange = (e) => {
    const currentSelection = e.value ?? null;

    if (!currentSelection || currentSelection.length === 0) {
      setSelectedRows([]);
      return;
    }

    const lastSelected = currentSelection[currentSelection.length - 1];

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

    setStageFilter(selected_stage);

    setSelectedRows([]);
    setIsSaving(false);
  }

  const handleRowReorder = (e) => {

    const { value: newData, dropIndex } = e;

    if (!selectedRows || selectedRows.length == 0) {
      const updateOrderData = newData.map((row, i) => ({
        ...row,
        order_by: i + 1
      }));

      const payload = updateOrderData.map(r => ({
        code: r.code,
        order_by: r.order_by
      }));

      setPlan(updateOrderData)

      axios.put('/Schedual/updateOrder', { updateOrderData: payload })
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

      return;
    }

    const selectedIds = new Set(selectedRows.map(r => r.id));
    const selectedGroup = newData.filter(r => selectedIds.has(r.id));
    const nonSelected = newData.filter(r => !selectedIds.has(r.id));
    const removedBefore = newData
      .slice(0, dropIndex)
      .filter(r => selectedIds.has(r.id)).length;

    const insertAt = Math.max(0, Math.min(nonSelected.length, dropIndex - removedBefore));

    const merged = [
      ...nonSelected.slice(0, insertAt),
      ...selectedGroup,
      ...nonSelected.slice(insertAt)
    ];

    const updateOrderData = merged.map((row, i) => ({ ...row, order_by: i + 1 }));

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
          <input id="swal-password" type="password" class="swal2-input passWord-swal-input" placeholder="Nhập mật khẩu..."/>
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
        return { password, sortType };
      }
    });

    if (value) {
      if (currentPassword != value.password) {
        Swal.fire({ icon: 'error', title: 'Mật khẩu không đúng', timer: 1500 });
        return;
      }
      setIsSaving(true);
      axios.put('/Schedual/createAutoCampain', { sortType: value.sortType })
        .then(res => {
          let data = res.data;
          if (typeof data === "string") {
            data = data.replace(/^<!--.*?-->/, "").trim();
            data = JSON.parse(data);
          }
          setPlan(data.plan);
        })
        .finally(() => setIsSaving(false));
    }
  }

  const handleDeleteCampainStage = (e) => {
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

    axios.put('/Schedual/deleteCampainStage', { data: filteredRows, stage_code: stageFilter })
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

  const handleSorted = (e) => {
    if (isSaving) return;
    setIsSaving(true);

    axios.put('/Schedual/sorted', { stage_code: stageFilter })
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

  const handleConfirmClearningValidation = (e) => {
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

    axios.put('/Schedual/confirm_clearning_validation', { data: filteredRows, stage_code: stageFilter })
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

  const handleCreateOrderPlan = (e) => {
    if (isSaving) return;
    setIsSaving(true);

    axios.post('/Schedual/createOrderPlan', { data: orderPlan })
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }
        setPlan(data.plan);
        setShowModalCreate(false);
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
    return;
  }

  const handleDeActiveOrderPlan = (e) => {
    if (isSaving) return;
    setIsSaving(true);
    const filteredRows = selectedRows.map(row => ({
      id: row.id,
      code: row.code,
    }));

    axios.put('/Schedual/deActiveOrderPlan', { data: filteredRows })
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

  const handleCreateQuota = (e) => {
    if (isSaving) return;
    setIsSaving(true);

    axios.post('/Quota/create', { data: modalQuotaData })
      .then(res => {
        setQuota(res.data.quota_id);
        setShowModalQuota(false);
      }
      ).catch(err => {
        setErrorsModal(err.response.data);
      }
      );

    setIsSaving(false);
    return;
  }

  const hasAnyRoom = (filterStr, userRoomStr) => {
    if (!filterStr || !userRoomStr) return false;
    const filterArr = filterStr.split(',').map(r => r.trim());
    return filterArr.includes(userRoomStr.trim());
  };

  const handleShowLine = (room, type_search) => {
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
        return aOrder - bOrder;
      });

    setTableData(filtered);
    setSelectedRows([]);
  };

  const naBody = (field) => (rowData) => rowData[field] || "NA";
  const longTextStyle = { whiteSpace: 'normal', wordBreak: 'break-word' };

  const allColumns = [
    { field: "month", header: "tháng", sortable: true, filter: false, filterField: "month" },
    { field: "code", header: "Mã Sản Phẩm", sortable: true, body: productCodeBody, filter: false, filterField: "code", style: { width: '5%', maxWidth: '5%', ...longTextStyle } },
    { field: "permisson_room", header: "Phòng SX", sortable: true, body: (r) => r.permisson_room ? JSON.stringify(r.permisson_room) : 'NA', filter: false, filterField: "permisson_room", style: { minWidth: '3%', maxWidth: '3%', ...longTextStyle } },
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
    <div ref={wrapperRef} style={{ height: "600px", overflow: "auto" }}>
      <div id="external-events"
        className={`absolute right-0 h-100 z-50 transition-transform duration-300 bg-white ${visible ? 'translate-x-0' : 'translate-x-full'}`}
        style={{
          width: percentShow,
          maxWidth: "100%",
          boxShadow: "2px 0 10px rgba(0,0,0,0.3)",
          display: "flex",
          flexDirection: "column",
          top: "40px"
        }}
      >
        <div className="p-4 border-b">
          <Row className="align-items-center">
            <Col md={3} className='d-flex justify-content-start'>
              {percentShow === "100%" && stageFilter <= 7 ? (
                <>
                  {unQuota > 0 && (
                    <div className="fc-event px-3 py-1 bg-red-400 border border-red-400 rounded text-md text-center cursor-pointer mr-3"
                      onClick={handleShowLine}>
                      {unQuota} Lô Thiếu Định Mức
                    </div>)}
                  <div className="fc-event px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" title="Tạo Mã Chiến Dịch"
                    onClick={handleCreateManualCampainStage}>
                    <i className="fas fa-cubes"></i> ({selectedRows.length})
                  </div>
                  <div className="fc-event px-3 py-1 bg-green-100 border border-green-400 rounded text-md text-center cursor-pointer mr-3" onClick={handleDeleteCampainStage}>
                    <i className="fas fa-trash"></i>
                  </div>
                </>) : <></>}
            </Col>

            <Col md={6}>
              <div className="p-inputgroup flex-1">
                <Dropdown
                  value={stageFilter}
                  options={stageOptions}
                  onChange={(e) => handleChangeStage(e.value)}
                  className="stage-dropdown"
                  panelClassName="stage-dropdown-panel"
                />
              </div>
            </Col>

            <Col md={3} className='d-flex justify-content-end'>
              <InputText
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Tìm kiếm..."
                style={{ width: "50%" }}
                className="me-2"
              />
              <Button icon="pi pi-arrows-h" className="p-button-text p-button-secondary" onClick={handleToggle} />
              <Button icon="pi pi-times" className="p-button-text p-button-danger" onClick={() => onClose(false)} />
            </Col>
          </Row>
        </div>

        <div style={{ flex: 1, overflow: 'auto' }}>
          <DataTable
            value={tableData}
            selection={selectedRows}
            onSelectionChange={handleSelectionChange}
            selectionMode="multiple"
            dataKey="id"
            paginator rows={20}
            scrollable scrollHeight="flex"
            globalFilter={searchTerm}
            reorderableRows onRowReorder={handleRowReorder}
          >
            <Column rowReorder style={{ width: '3rem' }} />
            <Column selectionMode="multiple" headerStyle={{ width: '3rem' }} />
            {visibleColumns.map(col => (
              <Column key={col.field} field={col.field} header={col.header} body={col.body} style={col.style} sortable={col.sortable} />
            ))}
          </DataTable>
        </div>
      </div>
    </div>
  );
};

export default ModalSidebar;
