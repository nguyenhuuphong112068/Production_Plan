import React, { useRef, useState, useEffect, useCallback } from 'react';

import '@fullcalendar/daygrid/index.js';
import '@fullcalendar/resource-timeline/index.js';
import ReactDOM from 'react-dom/client';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';
import { Calendar } from 'primereact/calendar';
import { Stepper } from 'primereact/stepper';
import { StepperPanel } from 'primereact/stepperpanel';
import { createRoot } from 'react-dom/client';
import axios from "axios";
import 'moment/locale/vi';
import dayjs from 'dayjs';
import Selecto from "react-selecto";
import Swal from 'sweetalert2';

import './calendar.css';
import CalendarSearchBox from '../Components/CalendarSearchBox';
import EventFontSizeInput from '../Components/EventFontSizeInput';
import ModalSidebar from '../Components/ModalSidebar';
import NoteModal from '../Components/NoteModal';
//import History from '../Components/History';
//import { CheckAuthorization } from '../Components/CheckAuthorization';



const ScheduleTest = () => {
  
  const calendarRef = useRef(null);
  const selectoRef = useRef(null);
  moment.locale('vi');
  const [showSidebar, setShowSidebar] = useState(false);
  const [viewConfig, setViewConfig] = useState({ timeView: 'resourceTimelineWeek', slotDuration: '00:15:00', is_clearning: true });
  const [pendingChanges, setPendingChanges] = useState([]);
  const [saving, setSaving] = useState(false);
  const [selectedEvents, setSelectedEvents] = useState([]);
  const [percentShow, setPercentShow] = useState("100%");
  const searchResultsRef = useRef([]);
  const currentIndexRef = useRef(-1);
  const lastQueryRef = useRef("");
  const slotViewWeeks = ['resourceTimelineWeek15', 'resourceTimelineWeek30', 'resourceTimelineWeek60', 'resourceTimelineWeek4h'];
  const slotViewMonths = ['resourceTimelineMonth1d', 'resourceTimelineMonth4h', 'resourceTimelineMonth1h',];
  const [slotIndex, setSlotIndex] = useState(0);
  const [eventFontSize, setEventFontSize] = useState(22); // default 14px
  const [selectedRows, setSelectedRows] = useState([]);
  const [showNoteModal, setShowNoteModal] = useState(false);
  const [viewName, setViewName] = useState("resourceTimelineWeek");
  const [showRenderBadge, setShowRenderBadge] = useState(false);


  const [events, setEvents] = useState([]);
  const [resources, setResources] = useState([]);
  const [sumBatchByStage, setSumBatchByStage] = useState([]);
  const [plan, setPlan] = useState([]);
  const [quota, setQuota] = useState([]);
  const [stageMap, setStageMap] = useState({});
  const [type, setType] = useState(true);
  const [loading, setLoading] = useState(false);
  const [authorization, setAuthorization] = useState(false);
  const [heightResource, setHeightResource] = useState("1px");
  const [production, setProduction] = useState("PXV1");
  const [currentPassword, setCurrentPassword] = useState(null);
  
  //const [cleaningHidden, setCleaningHidden] = useState(false); 
  //const [showHistoryModal, setShowHistoryModal] = useState(false);
  //const [quarantineRoom, setQuarantineRoom] = useState([]);
  //const [historyData, setHistoryData] = useState([]);
  // const renderCount = useRef(0);
  // renderCount.current++;
  // console.log("Render lần:", renderCount.current);

  function toLocalISOString(date) {
      const pad = (n) => String(n).padStart(2, '0');
    
      return (
        date.getFullYear() + '-' +
        pad(date.getMonth() + 1) + '-' +
        pad(date.getDate()) + 'T' +
        pad(date.getHours()) + ':' +
        pad(date.getMinutes()) + ':' +
        pad(date.getSeconds()) + '.000Z'
      );
  }

  /// Get dữ liệu ban đầu
  useEffect(() => {
    
    Swal.fire({
      title: "Đang tải...",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
    axios.post("/Schedual/view", {
      startDate: toLocalISOString(activeStart),
      endDate: toLocalISOString(activeEnd),
      viewtype: viewName,
    })
      .then(res => {

        let data = res.data;

        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }

        setAuthorization (['Admin', 'Schedualer'].includes(data.authorization))
        
        setEvents(data.events);
        setResources(data.resources);
        setType(data.type)
        setStageMap(data.stageMap);
        setSumBatchByStage(data.sumBatchByStage);
        setProduction(data.production)
       
        
        if (!authorization){
          setPlan(data.plan);
          setCurrentPassword (data.currentPassword??'')
          setQuota(data.quota);
         
        }
        
        switch (data.production) {
          case "PXV1":
            setHeightResource('1px');
            break;
          case "PXV2":
            setHeightResource('50px');
            break;
          case "PXVH":
            setHeightResource('50px');
            break;
          case "PXTN":
            setHeightResource('50px');
            break;
          case "PXDN":
            setHeightResource('60px');
            break;
        }

        setTimeout(() => {
          Swal.close();

        }, 100);

        
      })
      .catch(err =>
        console.error("API error:", err)
      );

  }, [loading]);

  /// Get dư liệu row được chọn
  useEffect(() => {
    if (!authorization) return;

    const externalEl = document.getElementById('external-events');
    if (!externalEl) return;

    // Tạo instance draggable
    const draggable = new Draggable(externalEl, {
      itemSelector: '.fc-event',
      eventData: (eventEl) => {
        const draggedData = selectedRows.length ? selectedRows : [];
        return {
          title:
            draggedData.length > 1
              ? `(${draggedData.length}) sản phẩm`
              : draggedData[0]?.product_code || 'Trống',
          extendedProps: { rows: draggedData },
        };
      },
    });

    // 🧹 Cleanup khi component unmount hoặc re-run effect
    return () => {
      draggable.destroy();
    };
  }, [authorization, selectedRows]);

  /// UseEffect cho render nut search
  useEffect(() => {
    // sau khi calendar render xong, inject vào toolbar
    const calendarApi = calendarRef.current?.getApi();
    if (!calendarApi) return;

    const toolbarEl = document.querySelector(".fc-searchBox-button");

    const container = document.createElement("div");
    toolbarEl.appendChild(container);

    const root = ReactDOM.createRoot(container);
    root.render(
      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
        <CalendarSearchBox onSearch={handleSearch} />

      </div>

    );
    return () => {
      root.unmount();
      if (toolbarEl.contains(container)) {
        toolbarEl.removeChild(container);
      }
    };

  }, []);

  ///
  useEffect(() => {
    const toolbarEl = document.querySelector(".fc-fontSizeBox-button");
    if (!toolbarEl) return;

    const container = document.createElement("div");
    toolbarEl.appendChild(container);

    const root = ReactDOM.createRoot(container);
    root.render(<EventFontSizeInput fontSize={eventFontSize} setFontSize={setEventFontSize} />);

    return () => {
      root.unmount();
      toolbarEl.removeChild(container);
    };
  }, [eventFontSize]); // chỉ chạy 1 lần

  ///
  const handleSearch = (query, direction = "next") => {
    const calendarApi = calendarRef.current?.getApi();
    if (!calendarApi) return;

    const events = calendarApi.getEvents();

    const matches = events.filter(ev =>

      ev.title.toLowerCase().includes(query.toLowerCase())


    );


    // Nếu không tìm thấy
    if (matches.length === 0) {
      Swal.fire({
        icon: "info",
        title: "Không tìm thấy",
        text: "Không có sự kiện nào khớp.",
        confirmButtonText: "OK",
      });
      clearHighlights();
      searchResultsRef.current = [];
      currentIndexRef.current = -1;
      lastQueryRef.current = "";
      return;
    }

    // Nếu query mới, reset
    if (query !== lastQueryRef.current) {
      searchResultsRef.current = matches;
      currentIndexRef.current = 0;
      lastQueryRef.current = query;
    } else {
      // Next hoặc Previous
      if (direction === "next") {
        currentIndexRef.current = (currentIndexRef.current + 1) % matches.length;
      } else if (direction === "prev") {
        currentIndexRef.current =
          (currentIndexRef.current - 1 + matches.length) % matches.length;
      }
    }

    highlightAllEvents();
  };

  /// --- Highlight tất cả sự kiện ---
  const highlightAllEvents = () => {
    const matches = searchResultsRef.current;
    if (!matches || matches.length === 0) return;

    // Xoá highlight cũ
    clearHighlights();

    matches.forEach((ev, index) => {
      const el = document.querySelector(`[data-event-id="${ev.id}"]`);
      if (el) {
        if (index === currentIndexRef.current) {
          el.classList.add("highlight-current-event"); // màu đậm
          scrollToEvent(el);
        } else {
          el.classList.add("highlight-event"); // màu nhạt
        }
      }
    });
  };

  /// --- Xoá highlight ---
  const clearHighlights = () => {
    document.querySelectorAll(".highlight-event, .highlight-current-event").forEach(el => {
      el.classList.remove("highlight-event", "highlight-current-event");
    });
  };

  // / --- Scroll sự kiện hiện tại vào view ---
  const scrollToEvent = (el) => {
    if (!el) return;

    el.scrollIntoView({
      behavior: "auto", // không smooth để tránh rung
      block: "center",
      inline: "center",
    });

    setTimeout(() => {
      window.scrollBy({ top: -50, left: -500, behavior: "auto" });
    }, 1);

  };


  const handleShowList = () => {
      if (!authorization) return;

      setShowSidebar(true);
  }
      
  ///  Thay đôi khung thời gian
  const handleViewChange = useCallback(async (viewType = null, action = null) => {
    

    const api = calendarRef.current?.getApi();
    if (!api) return;
    try {
      // 🔹 1. Thay đổi view nếu có yêu cầu
      if (viewType && api.view.type !== viewType) {
        api.changeView(viewType);
      }

      // 🔹 2. Điều hướng ngày
      if (action === "prev") api.prev();
      else if (action === "next") api.next();
      else if (action === "today") api.today();

      // ✅ Đợi 1 chút để FullCalendar cập nhật hoàn tất
      await new Promise(resolve => setTimeout(resolve, 150));

      // 🔹 3. Lấy khoảng thời gian hiện tại sau khi chuyển view
      const { activeStart, activeEnd, type: currentView } = api.view;

      const cleaningHidden = JSON.parse(sessionStorage.getItem('cleaningHidden'));
      
      // 🔹 4. Gọi API backend
      const { data } = await axios.post(`/Schedual/view`, {
        startDate: toLocalISOString(activeStart),
        endDate: toLocalISOString(activeEnd),
        viewtype: currentView,
        clearning: cleaningHidden,
      });

      let cleanData = data;
      if (typeof cleanData === "string") {
        cleanData = JSON.parse(cleanData.replace(/^<!--.*?-->/, "").trim());
      }
      // 🔹 5. Cập nhật dữ liệu mới
      setEvents(cleanData.events);
      setResources(cleanData.resources);
      setSumBatchByStage(cleanData.sumBatchByStage);
      setViewName(viewType);
    }  finally {

    }
  }, []);

  const toggleCleaningEvents = () => {
    const current = JSON.parse(sessionStorage.getItem('cleaningHidden')) || false;
    const newHidden = !current;
    sessionStorage.setItem('cleaningHidden', JSON.stringify(newHidden));
   
    handleViewChange(null, null);
  };

  /// Tô màu các event trùng khớp
  const handleEventHighlightGroup = (event, isCtrlPressed = false) => {
    const calendarApi = calendarRef.current?.getApi();
    if (!calendarApi) return;

    const pm = event.extendedProps.plan_master_id;

    if (!isCtrlPressed) {
      searchResultsRef.current = [];
      currentIndexRef.current = -1;
    }

    // Lấy tất cả event có cùng plan_master_id
    const matches = calendarApi.getEvents().filter(
      ev => ev.extendedProps.plan_master_id === pm
    );

    // Gộp vào danh sách (tránh trùng nếu đã có)
    matches.forEach(m => {
      if (!searchResultsRef.current.some(ev => ev.id === m.id)) {
        searchResultsRef.current.push(m);
      }
    });

    // Sau khi có matches
    setSelectedEvents(
      matches.map(ev => ({
        id: ev.id,
        stage_code: ev.extendedProps.stage_code,
        plan_master_id: ev.extendedProps.plan_master_id
      }))
    );

    // Đặt index ở phần tử đầu tiên
    currentIndexRef.current = searchResultsRef.current.length > 0 ? 0 : -1;

    highlightAllEvents();
  };


  // Nhân Dữ liệu để tạo mới event
  const handleEventReceive = (info) => {
   
    // chưa chọn row
    //if (!info?.event || !calendarRef?.current) return;
    const start = info.event.start;
    const now = new Date();
    const resourceId = info.event.getResources?.()[0]?.id ?? null;
    info.event.remove();

    if (selectedRows.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Vui Lòng Chọn Sản Phẩm Muốn Sắp Lịch',
        timer: 1000,
        showConfirmButton: false,
      });
      return false
    }
    // chưa định mức
    if (selectedRows[0].permisson_room.length == 0 && selectedRows[0].stage_code !== 9) {
      Swal.fire({
        icon: 'warning',
        title: 'Sản Phẩm Chưa Được Định Mức',
        timer: 1000,
        showConfirmButton: false,
      });
      return false;
    }

    // Phòng được chọn và định mực k giống
    const hasPermission = selectedRows.some(row => {
      if (!row.permisson_room) return false;

      if (Array.isArray(row.permisson_room)) {
        // Nếu backend trả mảng thì check trực tiếp
        return row.permisson_room.includes(resourceId);
      } else if (typeof row.permisson_room === "object") {
        // Nếu backend trả object {id_room: code}
        return Object.keys(row.permisson_room).includes(String(resourceId));
      }
      return false;
    });

    if (!hasPermission && selectedRows[0].stage_code < 8) {
      Swal.fire({
        icon: "warning",
        title: "Sản Phẩm Sắp Lịch Không Đúng Phòng Đã Định Mức",
        timer: 1000,
        showConfirmButton: false,
      });

      return false;
    }

    if (start <= now) {
      Swal.fire({
        icon: "warning",
        title: "Thời gian bắt đầu nhỏ hơn thời gian hiện tại!",
        timer: 1000,
        showConfirmButton: false,
      });
      return false;
    }

    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;

    if (selectedRows[0].stage_code !== 8) {
      axios.put('/Schedual/store', {
        room_id: resourceId,
        stage_code: selectedRows[0].stage_codes,
        start: moment(start).format("YYYY-MM-DD HH:mm:ss"),
        products: selectedRows,
        startDate: toLocalISOString(activeStart),
        endDate: toLocalISOString(activeEnd),
      })
        .then(res => {
          let data = res.data;
          if (typeof data === "string") {
            data = data.replace(/^<!--.*?-->/, "").trim();
            data = JSON.parse(data);
          }

          setEvents(data.events);
          setResources(data.resources);
          setSumBatchByStage(data.sumBatchByStage);
          setPlan(data.plan);

          setSelectedRows([]);
        })
        .catch(err => {
          console.error("Lỗi tạo lịch:", err.response?.data || err.message);
        });
    } else if (selectedRows[0].stage_code == 8) {

      axios.put('/Schedual/store_maintenance', {
        stage_code: 8,
        start: moment(start).format("YYYY-MM-DD HH:mm:ss"),
        products: selectedRows,
        is_HVAC: selectedRows[0]?.is_HVAC ?? false,
        startDate: toLocalISOString(activeStart),
        endDate: toLocalISOString(activeEnd),
      })
        .then(res => {
          let data = res.data;
          if (typeof data === "string") {
            data = data.replace(/^<!--.*?-->/, "").trim();
            data = JSON.parse(data);
          }
          setEvents(data.events);
          setResources(data.resources);
          setSumBatchByStage(data.sumBatchByStage);
          setPlan(data.plan);

          setSelectedRows([]);
        })
        .catch(err => {
          console.error("Lỗi tạo lịch bảo trì:", err.response?.data || err.message);
        });
    }
  };

  
  const timeToMilliseconds = (time) => {
    const [h, m] = time.split(":").map(Number);
    return (h * 3600 + m * 60) * 1000;
  };

  const isInSundayToMondayWindow = (date) => {
  const day = date.getDay();     // 0 = Sunday, 1 = Monday
  const hour = date.getHours();  // 0–23
  const minutes = date.getMinutes();

  // Tạo thời điểm 06:00
  const timeInMinutes = hour * 60 + minutes;
  const sixAM = 6 * 60;

  // Chủ Nhật từ 06:00 → hết ngày
  if (day === 0 && timeInMinutes >= sixAM) {
    return true;
  }

  // Thứ Hai từ 00:00 → 06:00
  if (day === 1 && timeInMinutes < sixAM) {
    return true;
  }

  return false;
  };




  /// 3 Ham sử lý thay đôi sự kiện
  const handleGroupEventDrop = (info, selectedEvents, toggleEventSelect, handleEventChange) => {
    
    if (!authorization) {
      info.revert();
      return false;
    }

    const draggedEvent = info.event;
    const delta = info.delta;
    const calendarApi = info.view.calendar;
    

    // Nếu chưa được chọn thì tự động chọn
    if (!selectedEvents.some(ev => ev.id === draggedEvent.id)) {
      info.revert();
      toggleEventSelect(draggedEvent);
      
    }

    // Nếu đã chọn thì xử lý nhóm
    if (selectedEvents.some(ev => ev.id === draggedEvent.id)) {
      info.revert();
    
      // Gom thay đổi tạm
      const batchUpdates = [];
     
      selectedEvents.forEach(sel => {
        const event = calendarApi.getEventById(sel.id);
        
        /// kiểm tra lại định mức
        if (event) {

          let process_code =  event._def.extendedProps.process_code +"_"+ event._def.resourceIds[0]
          let stage_code = event._def.extendedProps.stage_code
          let is_clearning = event._def.extendedProps.is_clearning
          let quota_event = quota.find(q => q.process_code == process_code && q.stage_code == stage_code);
          let newEnd = null;

          if (!quota_event){
              Swal.fire({
                icon: 'warning',
                title: 'Thiếu Định Mức',
                timer: 1000,
                showConfirmButton: false,
              });
              info.revert();
              return false;
          } 
            

          const offset = delta.milliseconds + delta.days * 24 * 60 * 60 * 1000;
          const event_start = event.start.getTime()
          const newStart = new Date(event_start + offset);
          
          let quota_event_m_time_seconds = timeToMilliseconds(quota_event.m_time)
          if (is_clearning){
            if(event._def.title == "VS-II"){
                quota_event_m_time_seconds = timeToMilliseconds(quota_event.C2_time)
              }else{
                quota_event_m_time_seconds = timeToMilliseconds(quota_event.C1_time)
              }
               
          }
            
            newEnd = new Date(event_start + offset + quota_event_m_time_seconds);
           
            //return
            if (isInSundayToMondayWindow (newEnd)){
                    newEnd = new Date(event_start + offset + quota_event_m_time_seconds + 86400000)
            }

          event.setDates(newStart, newEnd, { maintainDuration: true, skipRender: true }); // skipRender nếu có

          batchUpdates.push({
            id: event.id,
            start: newStart.toISOString(),
            end: newEnd.toISOString(),
            resourceId: event.getResources?.()[0]?.id ?? null,
            title: event.title
          });
        }
      });

      // Cập nhật pendingChanges 1 lần
      setPendingChanges(prev => {
        const ids = new Set(batchUpdates.map(e => e.id));
        const filtered = prev.filter(e => !ids.has(e.id));
        return [...filtered, ...batchUpdates];
      });

      // Gọi rerender một lần
      calendarApi.render();
    } else {
      // Nếu không nằm trong selectedEvents thì xử lý đơn lẻ
      handleEventChange(info);
    }
  };

  ///
  const handleEventChange = (changeInfo) => {
    const changedEvent = changeInfo.event;
    // Thêm hoặc cập nhật event vào pendingChanges
    setPendingChanges(prev => {

      const exists = prev.find(e => e.id === changedEvent.id);
      const updated = {
        id: changedEvent.id,
        start: changedEvent.start.toISOString(),
        end: changedEvent.end.toISOString(),
        resourceId: changeInfo.event.getResources?.()[0]?.id ?? null,
        title: changedEvent.title
        // các dữ liệu khác nếu cần
      };

      if (exists) {
        // Cập nhật lại nếu đã có
        return prev.map(e => e.id === changedEvent.id ? updated : e);
      } else {
        // Thêm mới
        return [...prev, updated];
      }
    });

  };

  ///
  const handleSaveChanges = async () => {
    if (!authorization) {
      info.revert();
      return false;
    }

    if (pendingChanges.length === 0) {
      Swal.fire({
        icon: 'info',
        title: 'Không có thay đổi',
        text: 'Bạn chưa thay đổi sự kiện nào.',
        timer: 2000,
        showConfirmButton: false,
      });
      return;
    }

    // 🟨 Hỏi người dùng nhập lý do thay đổi
    const { value: reason } = await Swal.fire({
      title: 'Nhập lý do thay đổi',
      input: 'textarea',
      inputLabel: 'Vui lòng ghi rõ lý do chỉnh sửa lịch sản xuất',
      inputPlaceholder: 'Nhập lý do tại đây...',
      inputAttributes: {
        'aria-label': 'Lý do thay đổi'
      },
      showCancelButton: true,
      confirmButtonText: 'Xác nhận lưu',
      cancelButtonText: 'Hủy',
      inputValidator: (value) => {
        if (!value || value.trim() === '') {
          return 'Bạn phải nhập lý do thay đổi!';
        }
      }
    });

    // Nếu người dùng bấm “Hủy” thì dừng
    if (!reason) return;

    setSaving(true);
    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
    let startDate = toLocalISOString(activeStart);
    let endDate = toLocalISOString(activeEnd);

    axios.put('/Schedual/update', {
      reason, // 🟢 gửi thêm lý do
      changes: pendingChanges.map(change => ({
        id: change.id,
        start: dayjs(change.start).format('YYYY-MM-DD HH:mm:ss'),
        end: dayjs(change.end).format('YYYY-MM-DD HH:mm:ss'),
        resourceId: change.resourceId,
        title: change.title,
        C_end: change.C_end || false
      })),
      startDate,
      endDate
    })
    .then(res => {
      let data = res.data;
      if (typeof data === "string") {
        data = data.replace(/^<!--.*?-->/, "").trim();
        data = JSON.parse(data);
      }
      setEvents(data.events);
      setSumBatchByStage(data.sumBatchByStage);
      setPlan(data.plan);
      setPendingChanges([]);
      setSaving(false);

      Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: 'Đã lưu tất cả thay đổi.',
        timer: 1200,
        showConfirmButton: false,
      });

      // Xóa border đánh dấu sự kiện đã sửa
      document.querySelectorAll('.fc-event[data-event-id]')
        .forEach(el => { el.style.border = 'none'; });
    })
    .catch(err => {
      console.error("Lỗi khi lưu events:", err.response?.data || err.message);
      setSaving(false);
      Swal.fire({
        icon: 'error',
        title: 'Lỗi!',
        text: 'Không thể lưu thay đổi. Vui lòng thử lại.',
      });
    });
  };


  /// Xử lý Toggle sự kiện đang chọn: if đã chọn thì bỏ ra --> selectedEvents
  const toggleEventSelect = (event) => {
    setSelectedEvents((prevSelected) => {
      const exists = prevSelected.some(ev => ev.id === event.id);
      const newSelected = exists
        ? prevSelected.filter(ev => ev.id !== event.id)
        : [...prevSelected, { id: event.id, stage_code: event.extendedProps.stage_code }];

      // highlight DOM ngay lập tức
      const el = document.querySelector(`[data-event-id="${event.id}"]`);
      if (el) {
        el.style.border = exists ? 'none' : '5px solid yellow';
      }

      return newSelected;
    });
  };

  /// Xử lý chọn 1 sự kiện -> selectedEvents
  const handleEventClick = (clickInfo) => {
   
    const event = clickInfo.event;
    toggleEventSelect(event);
    
    // if ( clickInfo.jsEvent.ctrlKey) {
    //   setSelectedEvents([{ id: event.id, stage_code: event.extendedProps.stage_code }]); // ghi đề toạn bọ các sự kiện chỉ giử lại sự kiện cuối
    // } else {
      
    // }

  };

  /// bỏ chọn tất cả sự kiện đã chọn ở select sidebar -->  selectedEvents
  const handleClear = () => {
      const sel = selectoRef.current;
      document.querySelectorAll('.fc-event[data-event-id]').forEach(el => {el.style.border = 'none';});

      // 1) Nếu thư viện expose clear trực tiếp
      if (typeof sel?.clear === 'function') {
        sel.clear();
      }
      // 2) Nếu wrapper chứa instance trong trường `selecto` hoặc `instance`
      else if (typeof sel?.selecto?.clear === 'function') {
        sel.selecto.clear();
      } else if (typeof sel?.instance?.clear === 'function') {
        sel.instance.clear();
      }
      // 3) Một phương án khác hay có: setSelectedTargets([])
      else if (typeof sel?.setSelectedTargets === 'function') {
        sel.setSelectedTargets([]);
      }
      // 4) Fallback: remove class selected trên DOM (giao diện) + reset state
      else {
        document.querySelectorAll('.fc-event.selected').forEach(el => el.classList.remove('selected'));
      }

      // Reset react state
      setSelectedEvents([]);

      // Tùy: gọi hàm un-highlight
      handleEventUnHightLine?.();
  };


  const handleEventUnHightLine = () => {
    document.querySelectorAll('.fc-event').forEach(el => el.classList.remove('highlight-event', 'highlight-current-event'));
  };

  /// Xử lý Chạy Lịch Tư Động
  let emptyPermission = null;

  const handleAutoSchedualer = () => {

    if (!authorization) return;
    let plansort = plan.sort((a, b) => a.stage_code - b.stage_code);

    const hasEmptyPermission = plansort.some(item => {
      const perm = item.permisson_room
      const isEmptyArray = Array.isArray(perm) && perm.length === 0;

      const matched = (
        item.stage_code >= 3 &&
        item.stage_code <= 7 &&
        isEmptyArray
      );

      if (matched) {
        emptyPermission = item; // 🔹 Ghi ra biến bên ngoài
      }

      return matched; // some() sẽ dừng ngay khi true
    });

    // true hoặc false

    let selectedDates = [];
    Swal.fire({
      title: 'Cấu Hình Chung Sắp Lịch',
      // html: `
      //     <div class="cfg-wrapper">
      //       <div class="cfg-card">
      //         <!-- Hàng Ngày chạy -->
              
      //         <div class="cfg-row">
      //           <div class="cfg-col">
      //             <label class="cfg-label" for="schedule-date">Ngày chạy bắt đầu sắp lịch:</label>
      //             <input id="schedule-date" type="date"
      //                   class="swal2-input cfg-input cfg-input--half" name="start_date"
      //                   value="${new Date().toISOString().split('T')[0]}">
      //           </div>
      //         </div>

      //         <!-- Hàng 2 cột -->
      //         <label class="cfg-label">Thời Gian Chờ Kết Quả Kiểm Nghiệm (ngày)</label>
      //         <div class="cfg-row cfg-grid-2">
      //           <div class="cfg-col">
      //             <label class="cfg-label" for="wt_bleding">Trộn Hoàn Tất Lô Thẩm Định</label>
      //             <input id="wt_bleding" type="number" class="swal2-input cfg-input cfg-input--full" min="0" value="1" name="wt_bleding_val">
      //             <label class="cfg-label" for="wt_forming">Định Hình Lô Thẩm Định</label>
      //             <input id="wt_forming" type="number" class="swal2-input cfg-input cfg-input--full" min="0" value="5" name="wt_forming_val">
      //             <label class="cfg-label" for="wt_coating">Bao Phim Lô Thẩm Định</label>
      //             <input id="wt_coating" type="number" class="swal2-input cfg-input cfg-input--full" min="0" value="5" name="wt_coating_val">
      //             <label class="cfg-label" for="wt_blitering">Đóng Gói Lô Thẩm Định</label>
      //             <input id="wt_blitering" type="number" class="swal2-input cfg-input cfg-input--full" min="0" value="5" name="wt_blitering_val">
      //           </div>
      //           <div class="cfg-col">
      //             <label class="cfg-label" for="wt_bleding_val">Trộn Hoàn Tất Lô Thương Mại</label>
      //             <input id="wt_bleding_val" type="number" class="swal2-input cfg-input cfg-input--full" min="0" value="0" name="wt_bledingl">
      //             <label class="cfg-label" for="wt_forming_val">Định Hình Lô Thương Mại</label>
      //             <input id="wt_forming_val" type="number" class="swal2-input cfg-input cfg-input--full" min="0" value="0" name="wt_forming">
      //             <label class="cfg-label" for="wt_coating_val">Bao Phim Lô Thương Mại</label>
      //             <input id="wt_coating_val" type="number" class="swal2-input cfg-input cfg-input--full" min="0" value="0" name="wt_coating">
      //             <label class="cfg-label" for="wt_blitering_val">Đóng Gói Lô Thương Mại</label>
      //             <input id="wt_blitering_val" type="number" class="swal2-input cfg-input cfg-input--full" min="0" value="0" name="wt_blitering">
      //           </div>
      //         </div>

      //         <div class="cfg-row">
      //         <!-- ✅ Vùng để gắn stepper -->
      //         <label class="cfg-label" for="stepper-container">Sắp Lịch Theo Công Đoạn:</label> 
      //         <div id="stepper-container" style="margin-top: 15px;"></div>
      //         </div>

      //         <div class="cfg-row">
      //           <label class="cfg-label" for="work-sunday">Làm Chủ Nhật:</label>
      //           <label class="switch">
      //             <input id="work-sunday" type="checkbox">
      //             <span class="slider round"></span>
      //             <span class="switch-labels">
      //               <span class="off">No</span>
      //               <span class="on">Yes</span>
      //             </span>
      //           </label>
      //         </div>

      //         <div class="cfg-row">
      //         <!-- ✅ Vùng để gắn Calendar -->
      //         <label class="cfg-label" for="calendar-container">Ngày Không Sắp Lịch:</label> 
      //         <div id="calendar-container" style="margin-top: 15px;"></div>
      //         </div>


      //         ${hasEmptyPermission
      //         ? `<p style="color:red;font-weight:600;margin-top:10px;">
      //                     ⚠️ Một hoặc nhiều sản phẩm chưa được định mức!<br>
      //                     Bạn cần định mức đầy đủ trước khi chạy Auto Scheduler.
      //                   </p>`
      //         : ''
      //       }

      //       </div>
      //     </div>
      //   `,
      html: `
      <div class="cfg-wrapper">

        <!-- Cột trái -->
        <div class="cfg-card cfg-left">
          <div class="cfg-row">
            <label class="cfg-label" for="schedule-date">Ngày bắt đầu sắp lịch:</label>
            <input id="schedule-date" type="date"
                  class="swal2-input cfg-input cfg-input--half"
                  name="start_date"
                  value="${new Date().toISOString().split('T')[0]}">
          </div>

          <label class="cfg-label">Thời Gian Chờ Kết Quả Kiểm Nghiệm (ngày)</label>
          <div class="cfg-row cfg-grid-2">
            <div class="cfg-col">
              <label class="cfg-label">Trộn Hoàn Tất Lô Thẩm Định</label>
              <input type="number" class="swal2-input cfg-input cfg-input--full" value="1">
              <label class="cfg-label">Định Hình Lô Thẩm Định</label>
              <input type="number" class="swal2-input cfg-input cfg-input--full" value="5">
              <label class="cfg-label">Bao Phim Lô Thẩm Định</label>
              <input type="number" class="swal2-input cfg-input cfg-input--full" value="5">
              <label class="cfg-label">Đóng Gói Lô Thẩm Định</label>
              <input type="number" class="swal2-input cfg-input cfg-input--full" value="5">
            </div>

            <div class="cfg-col">
              <label class="cfg-label">Trộn Hoàn Tất Lô Thương Mại</label>
              <input type="number" class="swal2-input cfg-input cfg-input--full" value="0">
              <label class="cfg-label">Định Hình Lô Thương Mại</label>
              <input type="number" class="swal2-input cfg-input cfg-input--full" value="0">
              <label class="cfg-label">Bao Phim Lô Thương Mại</label>
              <input type="number" class="swal2-input cfg-input cfg-input--full" value="0">
              <label class="cfg-label">Đóng Gói Lô Thương Mại</label>
              <input type="number" class="swal2-input cfg-input cfg-input--full" value="0">
            </div>
          </div>

          <label class="cfg-label" for="stepper-container">Sắp Lịch Theo Công Đoạn:</label> 
          <div id="stepper-container" style="margin-top: 15px;"></div>

          ${hasEmptyPermission
            ? `<p style="color:red;font-weight:600;margin-top:10px;">
                ⚠️ Một hoặc nhiều sản phẩm chưa được định mức!<br>
                Bạn cần định mức đầy đủ trước khi chạy Auto Scheduler.
              </p>`
            : ''
          }
        </div>

        <!-- Cột phải -->
        <div class="cfg-card cfg-right">
          <div class="cfg-row">
            <label class="cfg-label" for="work-sunday">Làm Chủ Nhật:</label>
            <label class="switch">
              <input id="work-sunday" type="checkbox">
              <span class="slider round"></span>
              <span class="switch-labels">
                <span class="off">No</span>
                <span class="on">Yes</span>
              </span>
            </label>
          </div>

          <div class="cfg-row">
            <label class="cfg-label" for="calendar-container">Ngày Không Sắp Lịch:</label>
            <div id="calendar-container" style="margin-top: 15px;"></div>
          </div>

          <div class="cfg-row">
            <label class="cfg-label" for="reason">Lý do chạy Auto Scheduler:</label>
            <input id="reason" type="text"
                  class="swal2-input cfg-input cfg-input--full"
                  name="reason"
                  placeholder="Nhập lý do..."
                  required>
          </div>
        </div>
        


      </div>
      `,

      width: '40%',
      customClass: { htmlContainer: 'cfg-html-left', title: 'my-swal-title' },
      showCancelButton: true,
      confirmButtonText: 'Chạy',
      cancelButtonText: 'Hủy',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',

      didOpen: () => {

        // ------------------ Calendar ------------------
        const calendarContainer = document.getElementById("calendar-container");
        const calendarRoot = ReactDOM.createRoot(calendarContainer);

        const CalendarPopup = () => {
          const [localDates, setLocalDates] = React.useState([]);

          const handleChange = (e) => {
            const selected = e.value.map(d => {
              const date = new Date(d);
              const year = date.getFullYear();
              const month = String(date.getMonth() + 1).padStart(2, "0");
              const day = String(date.getDate()).padStart(2, "0");
              return `${year}-${month}-${day}`;
            });

            setLocalDates(e.value);
            selectedDates = selected;
          };

          return (
            <div className="card flex justify-content-center">
              <Calendar
                value={localDates}
                onChange={handleChange}
                selectionMode="multiple"
                inline
                readOnlyInput
              />
            </div>
          );
        };

        calendarRoot.render(<CalendarPopup />);

        // ------------------ Stepper ------------------
        const stepperContainer = document.getElementById("stepper-container");

        if (stepperContainer) {
          const stepperRoot = createRoot(stepperContainer);

          const StepperPopup = () => {
            const [selected, setSelected] = React.useState(null);

            const getClass = (value) =>
              `border-2 border-dashed surface-border border-round surface-ground flex justify-content-center align-items-center h-12rem fs-4 cursor-pointer ${selected === value ? "bg-primary text-white" : ""
              }`;
           
            return (
              <Stepper style={{ width: "100%" }}>
                {(emptyPermission == null || emptyPermission.stage_code >= 4) && (
                  <StepperPanel header="PC" >
                    <div className="flex flex-column h-12rem" >
                      <div
                        className={getClass("Pha Chế")}>
                        Pha Chế
                      </div>
                    </div>
                  </StepperPanel>)}

                {(emptyPermission == null || emptyPermission.stage_code >= 5) && (
                  <StepperPanel header="THT" readOnlyInput>
                    <div className="flex flex-column h-12rem">
                      <div
                        className={getClass("THT")}
                      >
                        Pha Chế ➡ Trộn Hoàn Tất
                      </div>
                    </div>
                  </StepperPanel>)}
                {(emptyPermission == null || emptyPermission.stage_code >= 6) && (
                  <StepperPanel header="ĐH">
                    <div className="flex flex-column h-12rem">
                      <div
                        className={getClass("ĐH")}

                      >
                        Pha Chế ➡ Định Hình
                      </div>
                    </div>
                  </StepperPanel>)}
                {(emptyPermission == null || emptyPermission.stage_code >= 7) && (
                  <StepperPanel header="BP" disabled={true}>
                    <div className="flex flex-column h-12rem">
                      <div
                        className={getClass("BP")}

                      >
                        Pha Chế ➡ Bao Phim
                      </div>
                    </div>
                  </StepperPanel>)}
                {(emptyPermission == null || emptyPermission.stage_code >= 8) && (
                  <StepperPanel header="ĐG">
                    <div className="flex flex-column h-12rem">
                      <div
                        className={getClass("ĐG")}

                      >
                        Pha Chế ➡ Đóng Gói
                      </div>
                    </div>
                  </StepperPanel>)}

              </Stepper>
            );
          };

          stepperRoot.render(<StepperPopup />);
        }

        // ------------------ Disable Confirm if missing permission ------------------
        

        if (emptyPermission != null && emptyPermission.stage_code < 4) {
          const confirmBtn = Swal.getConfirmButton();
          confirmBtn.disabled = false;
          confirmBtn.style.opacity = "0.5";
          confirmBtn.style.cursor = "not-allowed";
        }



      }
      ,
      preConfirm: () => {

        if (emptyPermission != null && emptyPermission.stage_code < 4) {
          Swal.showValidationMessage('Vui lòng định mức đầy đủ ít nhất một công đoạn trước khi sắp lịch tự động!');
          return false;
        }

        const formValues = {};
          document.querySelectorAll('.swal2-input').forEach(input => {
            formValues[input.name] = input.value;
        });

        const activeStep = document.querySelector('li[data-p-active="true"]');
        const activeStepText = activeStep ? activeStep.querySelector('span.p-stepper-title')?.textContent : null;


        const workSunday = document.getElementById('work-sunday');
        formValues.work_sunday = workSunday.checked;

        formValues.selectedDates = selectedDates;
        formValues.selectedStep = activeStepText ?? "PC";

        if (!formValues.start_date) {
          Swal.showValidationMessage('Vui lòng chọn ngày!');
          return false;
        }

        return formValues;
      }

    }).then((result) => {

      if (result.isConfirmed) {
        Swal.fire({
          title: 'Đang chạy Auto Scheduler...',
          text: 'Vui lòng chờ trong giây lát',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          },
        });

        const { activeStart, activeEnd } = calendarRef.current?.getApi().view;

        axios.post('/Schedual/scheduleAll', {
          ...result.value,
          startDate: toLocalISOString(activeStart),
          endDate: toLocalISOString(activeEnd),
        }, { timeout: 300000 })
          .then(res => {
            let data = res.data;
            if (typeof data === "string") {
              data = data.replace(/^<!--.*?-->/, "").trim();
              data = JSON.parse(data);
            }

            Swal.fire({
              icon: 'success',
              title: 'Hoàn Thành Sắp Lịch',
              timer: 1000,
              showConfirmButton: false,
            });

            // setEvents(data.events);
            // setSumBatchByStage(data.sumBatchByStage);
            // setPlan(data.plan);

            setLoading(!loading)

          })
          .catch(err => {
            // Swal.fire({
            //   icon: 'error',
            //   title: 'Lỗi',
            //   timer: 1000,
            //   showConfirmButton: false,
            // });
            setLoading(!loading)
            console.error("ScheduleAll error:", err.response?.data || err.message);
          });
      }
    });
  };

  /// Xử lý Xóa Toàn Bộ Lịch
  const handleDeleteAllScheduale = () => {
    if (!authorization) return;

    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;

    Swal.fire({
      width: '700px',
      title: 'Bạn có chắc muốn xóa toàn bộ lịch?',
      html: `
        <div class="cfg-wrapper">
          <div class="cfg-card">

            <div class="cfg-row">
              <div class="cfg-col">
                <label class="cfg-label" for="schedule-date">Xóa Lịch Từ Ngày:</label>
                <input id="schedule-date" type="date"
                        class="swal2-input cfg-input cfg-input--half" name="start_date"
                        value="${new Date().toISOString().split('T')[0]}">
              </div>
            </div>

            <div class="cfg-row">
              <!-- 🔘 Chọn chế độ xóa -->
              <div style="margin-bottom: 15px;">
                <label><b>Chọn chế độ xóa:</b></label><br>
                <label><input type="radio" name="deleteMode" value="step" checked> Xóa theo công đoạn</label>
                &nbsp;&nbsp;
                <label><input type="radio" name="deleteMode" value="resource"> Xóa theo phòng SX</label>
              </div>

              <!-- ✅ Stepper -->
              <div id="stepper-container" style="margin-top: 15px;"></div>

              <!-- ✅ Resource Dropdown -->
              <div id="resource-container" style="margin-top:20px; display:none; text-align:center;">
                <label for="resource-select" style="display:block; margin-bottom:5px;">Chọn Nguồn (Resource):</label>
                <select 
                  id="resource-select" 
                  class="swal2-select" 
                  style="width:80%; max-width:400px; padding:5px; margin:auto; display:block;">
                  <option value="">-- Tất cả --</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',

      didOpen: () => {

        // ------------------ Stepper ------------------
        const stepperContainer = document.getElementById("stepper-container");

        if (stepperContainer) {
          const stepperRoot = createRoot(stepperContainer);
          const StepperPopup = () => {
            const [selected, setSelected] = React.useState(null);

            const getClass = (value) =>
              `border-2 border-dashed surface-border border-round surface-ground flex justify-content-center align-items-center h-12rem fs-4 cursor-pointer ${selected === value ? "bg-primary text-white" : ""}`;

            return (
              <Stepper style={{ width: "100%" }}>
                <StepperPanel header="PC">
                  <div className="flex flex-column h-12rem">
                    <div className={getClass("Pha Chế")}>Pha Chế ➡ Đóng Gói</div>
                  </div>
                </StepperPanel>
                <StepperPanel header="THT">
                  <div className="flex flex-column h-12rem">
                    <div className={getClass("THT")}>Trộn Hoàn Tất ➡ Đóng Gói</div>
                  </div>
                </StepperPanel>
                <StepperPanel header="ĐH">
                  <div className="flex flex-column h-12rem">
                    <div className={getClass("ĐH")}>Định Hình ➡ Đóng Gói</div>
                  </div>
                </StepperPanel>
                <StepperPanel header="BP" disabled={true}>
                  <div className="flex flex-column h-12rem">
                    <div className={getClass("BP")}>Bao Phim ➡ Đóng Gói</div>
                  </div>
                </StepperPanel>
                <StepperPanel header="ĐG">
                  <div className="flex flex-column h-12rem">
                    <div className={getClass("ĐG")}>Đóng Gói</div>
                  </div>
                </StepperPanel>
              </Stepper>
            );
          };

          stepperRoot.render(<StepperPopup />);
        }

        // ✅ Thêm resource options
        const resourceSelect = document.getElementById("resource-select");
        if (resourceSelect && resources?.length) {
          resources.forEach(r => {
            const opt = document.createElement("option");
            opt.value = r.id;
            opt.textContent = r.title ?? r.name ?? `Resource ${r.id}`;
            resourceSelect.appendChild(opt);
          });
        }

        // ✅ Toggle giữa 2 chế độ
        const radios = document.querySelectorAll('input[name="deleteMode"]');
        const stepperDiv = document.getElementById("stepper-container");
        const resourceDiv = document.getElementById("resource-container");

        radios.forEach(r => {
          r.addEventListener("change", e => {
            if (e.target.value === "step") {
              stepperDiv.style.display = "block";
              resourceDiv.style.display = "none";
            } else {
              stepperDiv.style.display = "none";
              resourceDiv.style.display = "block";
            }
          });
        });
      },

    preConfirm: () => {
        // Lấy giá trị deleteMode trước
        const deleteMode = document.querySelector('input[name="deleteMode"]:checked')?.value;

        // Tạo object formValues ban đầu
        const formValues = { mode: deleteMode };

        // Lấy các input từ Swal (nếu có)
        document.querySelectorAll('.swal2-input').forEach(input => {
          formValues[input.name] = input.value;
        });

        // Nếu chọn xóa theo step
        if (deleteMode === "step") {
          const activeStep = document.querySelector('li[data-p-active="true"]');
          const activeStepText = activeStep
            ? activeStep.querySelector('span.p-stepper-title')?.textContent
            : null;
          formValues.selectedStep = activeStepText ?? "PC";
        }

        // Nếu chọn xóa theo resource
        if (deleteMode === "resource") {
          const resourceSelect = document.getElementById("resource-select");
          formValues.resourceId = resourceSelect?.value || null;
        }

        return formValues;
    }


    }).then((result) => {
      if (!result.isConfirmed) return;

      Swal.fire({
        title: "Đang tải...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
      });

      axios.put('/Schedual/deActiveAll', {
        ...result.value,
        startDate: toLocalISOString(activeStart),
        endDate: toLocalISOString(activeEnd),

      })
        .then(res => {
          let data = res.data;
          if (typeof data === "string") {
            data = data.replace(/^<!--.*?-->/, "").trim();
            data = JSON.parse(data);
          }

          setLoading(!loading);
          Swal.close();

          Swal.fire({
            icon: 'success',
            title: 'Đã xóa lịch thành công',
            showConfirmButton: false,
            timer: 1500
          });
        })
        .catch(err => {
          Swal.close();
          Swal.fire({
            icon: 'error',
            title: 'Xóa lịch thất bại',
            text: 'Vui lòng thử lại sau.',
            timer: 1500
          });
          console.error("API error:", err.response?.data || err.message);
        });
    });
  };

  /// Xử lý xoa các lịch được chọn
  const handleDeleteScheduale = (e) => {

    if (!authorization) { return };




    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
    if (!selectedEvents || selectedEvents.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Chọn Lịch Cần Xóa',
        showConfirmButton: false,
        timer: 1000
      });
      return; // Dừng hàm ở đây
    }
    e.stopPropagation();
    Swal.fire({
      title: 'Bạn có chắc muốn xóa lịch này?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',

    }).then((result) => {
      if (result.isConfirmed) {
        axios.put('/Schedual/deActive', {
          ids: selectedEvents,
          startDate: toLocalISOString(activeStart),
          endDate: toLocalISOString(activeEnd)
        })
          .then((res) => {
            let data = res.data;
            if (typeof data === "string") {
              data = data.replace(/^<!--.*?-->/, "").trim();
              data = JSON.parse(data);
            }
            setEvents(data.events);
            setSumBatchByStage(data.sumBatchByStage);
            setPlan(data.plan);

            Swal.fire({
              icon: 'success',
              title: 'Đã xóa lịch thành công',
              showConfirmButton: false,
              timer: 1500
            });
          })

          .catch((error) => {
            Swal.fire({
              icon: 'error',
              title: 'Xóa lịch thất bại',
              text: 'Vui lòng thử lại sau.',
            });
            console.error("API error:", error.response?.data || error.message);
          });
      }
      setSelectedEvents([]);
    });
  }

  /// Xử lý độ chia thời gian nhỏ nhất
  const toggleSlotDuration = () => {
    const calendarApi = calendarRef.current?.getApi();
    var currentView = calendarApi.view.type;


    setSlotIndex((prevIndex) => {
      if (currentView.includes("Week")) {

        const nextIndex = (prevIndex + 1) % slotViewWeeks.length;
        calendarApi.changeView(slotViewWeeks[nextIndex]);
        return nextIndex;
      } else if (currentView.includes("Month")) {
        const nextIndex = (prevIndex + 1) % slotViewMonths.length;
        calendarApi.changeView(slotViewMonths[nextIndex]);
        return nextIndex;
      }
    });
  };

  /// Xử lý format số thập phân
  const formatNumberWithComma = (x) => {
    if (x == null) return "0";
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  const finisedEvent = (dropInfo, draggedEvent) => {
    if (draggedEvent.extendedProps.finished) { return false; }
    return true;
  };

  const toggleNoteModal = () => {
    setShowNoteModal(!showNoteModal)
  }

  const handleSubmit = (e) => {

    if (!authorization) return;

    e.stopPropagation();
    Swal.fire({
      title: 'Bạn muốn submit toàn bộ lịch đã sắp?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Submit',
      cancelButtonText: 'Hủy',
      confirmButtonColor: 'rgba(5, 107, 9, 1)',
      cancelButtonColor: '#3085d6',

    }).then((result) => {
      if (result.isConfirmed) {
        axios.put('/Schedual/submit')

        .then(res => {
            let data = res.data;
            if (typeof data === "string") {
              data = data.replace(/^<!--.*?-->/, "").trim();
              data = JSON.parse(data);
            }
          

            Swal.fire({
              icon: 'success',
              title: data.message,
              timer: 1500,
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

      }})
  }

  const EventContent = (arg) => {
    const event = arg.event;
    const props = event._def.extendedProps;
    const now = new Date();

    const isTimelineMonth = arg.view.type === 'resourceTimelineMonth';
    const isSelected = arg.selectedEvents?.some(ev => ev.id === event.id);
    //const showRenderBadge = false; // nếu bạn có điều kiện riêng thì thay vào

    const getBadge = (text, color, left) => `
      <div
        class="absolute top-[-15px]"
        style="left:${left}px; font-size:11px; padding:1px 4px; border-radius:3px; color:white; box-shadow:0 1px 2px rgba(0,0,0,0.2); background:${color}"
      >${text}</div>
    `;

    let html = `
      <div class="relative group custom-event-content" data-event-id="${event.id}">
        <div style="font-size:${arg.eventFontSize || 12}px;">
          <b>${props.is_clearning ? event.title.split('-')[1] : event.title}</b>
          ${!isTimelineMonth ? `
            <br/>
            ${arg.view.type !== 'resourceTimelineQuarter' && !props.is_clearning ?
              `<span>${moment(event.start).format('HH:mm')} - ${moment(event.end).format('HH:mm')}</span>`
            : ''}
          ` : ''}
        </div>
    `;

    // Badge ngày cần hàng
    if (props.expected_date && showRenderBadge) {
      const colors = {1: 'red', 2: 'orange', 3: 'green'};
      const color = colors[props.level] || 'blue';
      html += getBadge(props.expected_date, color, 50);
    }

    // Badge % biệt trữ
    if (!props.is_clearning && showRenderBadge) {
    html += `
        <button 
          class="absolute top-[-15px] right-5 text-15 px-1 rounded shadow bg-white text-red-600"
          title="% biệt trữ"
        ><b>${props.storage_capacity ?? ''}</b></button>
      `;
    }


    // // Nút chọn
    // html += `
    //   <button
    //     data-select-event="${event.id}"
    //     class="absolute top-0 left-0 text-xs px-1 rounded shadow
    //     ${isSelected ? 'block bg-blue-500 text-white' : 'hidden group-hover:block bg-white text-blue-500 border border-blue-500'}"
    //     title="${isSelected ? 'Bỏ chọn' : 'Chọn sự kiện'}"
    //   >${isSelected ? '✓' : '+'}</button>
    // `;

    // // Nút Hoàn thành
    // if (props.finished === 0 && !props.is_clearning  && event.end < now) {
    //   html += `
    //     <button
    //       data-finish-event="${event.id}"
    //       class="absolute bottom-0 left-0 hidden group-hover:block text-blue-500 text-sm bg-white px-1 rounded shadow"
    //       title="Xác Nhận Hoàn Thành Lô Sản Xuất"
         
    //     >🎯</button>
    //   `;
    // }

  
    // Nút xem lịch sử
    // if (showRenderBadge && props.number_of_history) {
    //   html += `
    //     <button
    //       data-show-history="${event.id}"
    //       class="absolute top-[-15px] left-[150px] text-xs px-1 rounded shadow bg-red-500 text-white"
    //       title="Xem Lịch Sử Thay Đổi"
    //     >${props.number_of_history}</button>
    //   `;
    // }
    
    html += `</div>`;
    return { html };
  };


  return (

    <div className={`transition-all duration-300 ${showSidebar ? percentShow == "30%" ? 'w-[70%]' : 'w-[85%]' : 'w-full'} float-left pt-4 pl-2 pr-2`}>
      <FullCalendar
        schedulerLicenseKey="GPL-My-Project-Is-Open-Source"
        ref={calendarRef}
        plugins={[dayGridPlugin, resourceTimelinePlugin, interactionPlugin]}
        initialView="resourceTimelineWeek"
        firstDay={1}
        events={events}
        eventResourceEditable={true}
        resources={resources}
        resourceAreaHeaderContent="Phòng Sản Xuất"

        locale="vi"
        resourceAreaWidth="250px"
        expandRows={false}

        editable={true}
        droppable={true}
        selectable={true}
        eventResizableFromStart={true}

        slotDuration="01:00:00"
        eventDurationEditable={true}

       
        eventClick={authorization? handleEventClick: false}
        eventResize={authorization? handleEventChange: false}
        eventDrop={authorization? (info) => handleGroupEventDrop(info, selectedEvents, toggleEventSelect, handleEventChange):false}
        eventReceive={authorization? handleEventReceive: false}
        dateClick={authorization? handleClear: false}
        eventAllow={finisedEvent}

        resourceGroupField="stage_name"
        resourceOrder='order_by'

        // stage
        resourceGroupLabelContent={(arg) => {

          const stage_code = stageMap[arg.groupValue] || {};
          const sumItem = sumBatchByStage.find(s => s.stage_code == stage_code)
          const qty = sumItem ? formatNumberWithComma(sumItem.total_qty) : "0";
          const unit = sumItem?.unit || "";
          const yields = `${qty} ${unit}`.trim();

          const highlight = selectedRows.some(row => row.stage_code == stage_code);

          return (
            <div style={{ fontWeight: "bold", color: highlight ? "red" : "black" }}>
              {arg.groupValue + " :"}
              <span style={{ marginLeft: "10px", color: "green" }}>
                {yields}
              </span>
            </div>
          );

        }}

        // Phòng
        resourceLabelContent={(arg) => {
          const res = arg.resource.extendedProps;
          const busy = parseFloat(res.busy_hours) || 0;
          const yields = parseFloat(res.yield) || 0;
          const unit = res.unit || "";
          const total = parseFloat(res.total_hours) || 1;
          const efficiency = ((busy / total) * 100).toFixed(1);

          const highlight = selectedRows.some(row => {
            if (!row.permisson_room) return false;

            if (Array.isArray(row.permisson_room)) {
              return row.permisson_room.includes(res.code);
            } else if (typeof row.permisson_room === "object") {
              return Object.values(row.permisson_room).includes(res.code);
            } else {
              return row.permisson_room == arg.resource.id;
            }
        });

          const bgColor = highlight ? "#c6f7d0" : "transparent";
          const busyWidth = ((busy / total) * 100).toFixed(1);
          const heightResourcePx = heightResource || 40; // fallback nếu thiếu

          const html = `
            <div 
              style="
                background-color:${bgColor};
                padding:0;
                border-radius:6px;
                margin-top:0;
                position:relative;
                height:${heightResourcePx}px;
              "
            >
              <div
                style="
                  font-size:22px;
                  font-weight:bold;
                  margin-bottom:2px;
                  width:8%;
                  position:relative;
                  top:-26px;
                "
              >
                ${arg.resource.title} - ${res.main_equiment_name ?? ""}
              </div>

              <div
                class="resource-bar"
                style="
                  position:relative;
                  top:-26px;
                  height:15px;
                  background:#eeeeeeff;
                  border-radius:20px;
                  overflow:hidden;
                  display:flex;
                  align-items:center;
                "
              >
                <div
                  class="busy"
                  style="
                    width:${busyWidth}%;
                    background:red;
                    height:100%;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                  "
                ></div>

                <b
                  style="
                    position:absolute;
                    top:50%;
                    left:50%;
                    transform:translate(-50%,-50%);
                    font-size:70%;
                    color:#060606ff;
                  "
                >
                  ${efficiency}% - ${formatNumberWithComma(yields)} ${unit}
                </b>
              </div>
            </div>
          `;

          return { html }; // 🔹 FullCalendar sẽ render nội dung này trực tiếp
        }}
        
        headerToolbar={{
          left: 'customPre,myToday,customNext noteModal hiddenClearning autoSchedualer deleteAllScheduale changeSchedualer unSelect ShowBadge',
          center: 'title',
          right: 'Submit fontSizeBox searchBox slotDuration customDay,customWeek,customMonth,customQuarter customList' //customYear
        }}

        views={{
          resourceTimelineDay: {
            slotDuration: '00:05:00',
            slotMinTime: '00:00:00',
            slotMaxTime: '24:00:00',
            buttonText: 'Ngày',
            titleFormat: { year: 'numeric', month: 'short', day: 'numeric' },
          },
          resourceTimelineWeek: {
            slotDuration: '00:15:00',
            slotMinTime: '00:00:00',
            slotMaxTime: '24:00:00',
            buttonText: 'Tuần',
            titleFormat: { year: 'numeric', month: 'short', day: 'numeric' },
          },
          resourceTimelineMonth: {
            slotDuration: { days: 1 },
            slotMinTime: '00:00:00',
            slotMaxTime: '24:00:00',
            buttonText: 'Tháng',
            titleFormat: { year: 'numeric', month: 'short' },
          },
          resourceTimelineQuarter: {
            slotDuration: { days: 1 },
            duration: { months: 3 },
            buttonText: 'Quý',
            titleFormat: { year: 'numeric', month: 'short' },
            type: 'resourceTimeline',
          },
          resourceTimelineYear: {
            slotDuration: { days: 1 },
            slotMinTime: '00:00:00',
            slotMaxTime: '24:00:00',
            buttonText: 'Năm',
            titleFormat: { year: 'numeric' }
          },
          resourceTimelineWeek15: { type: 'resourceTimelineWeek', slotDuration: '00:15:00' },
          resourceTimelineWeek30: { type: 'resourceTimelineWeek', slotDuration: '00:30:00' },
          resourceTimelineWeek60: { type: 'resourceTimelineWeek', slotDuration: '01:00:00' },
          resourceTimelineWeek4h: { type: 'resourceTimelineWeek', slotDuration: '04:00:00' },

          resourceTimelineMonth1h: { type: 'resourceTimelineMonth', slotDuration: '01:00:00' },
          resourceTimelineMonth4h: { type: 'resourceTimelineMonth', slotDuration: '04:00:00' },
          resourceTimelineMonth1d: { type: 'resourceTimelineMonth', slotDuration: { days: 1 } },
        }}

        customButtons={{
          customNext: {
            text: '⏵',
            click: () => handleViewChange(null, 'next'),
          },
          customPre: {
            text: '⏴',
            click: () => handleViewChange(null, 'prev'),
          },

          customList: {
            text: 'KHSX',
            click: handleShowList
          },
          customDay: {
            text: 'Ngày',
            click: () => handleViewChange('resourceTimelineDay'),

          },
          customWeek: {
            text: 'Tuần',
            click: () => handleViewChange('resourceTimelineWeek')
          },
          customMonth: {
            text: 'Tháng',
            click: () => handleViewChange('resourceTimelineMonth')
          },
          customQuarter: {
            text: '3 Tháng',
            click: () => handleViewChange('resourceTimelineQuarter')
          },

          myToday: {
            text: 'Hiện Tại',
            click: () => calendarRef.current.getApi().today()
          },
          noteModal: {
            text: 'ℹ️',
            click: toggleNoteModal
          },
          hiddenClearning: {
            text: '🙈',
            click: toggleCleaningEvents
          },
          autoSchedualer: {
            text: '🤖',
            click: handleAutoSchedualer,

          },
          deleteAllScheduale: {
            text: '🗑️',
            click: handleDeleteAllScheduale
          },

          changeSchedualer: {
            text: '♻️',
            click: handleSaveChanges
          },
          unSelect: {
            text: '🚫',
            click: handleDeleteScheduale
          },
          dateRange: { text: '' },
          searchBox: { text: '' },
          fontSizeBox: { text: '' },

          slotDuration: {
            text: 'Slot',
            click: toggleSlotDuration
          },

          ShowBadge: {
            text: '👁️',
            click: () => setShowRenderBadge(!showRenderBadge)
          },

          Submit: {
            text: '📤',
            click: handleSubmit
          },

        }}

        eventClassNames={(arg) => arg.event.extendedProps.isHighlighted ? ['highlight-event'] : []}

        eventDidMount={(info) => {

          // gắn data-event-id để tìm kiếm
          info.el.setAttribute("data-event-id", info.event.id);
          info.el.setAttribute("data-stage_code", info.event.extendedProps.stage_code);

          // cho select evetn => pendingChanges
          const isPending = pendingChanges.some(e => e.id === info.event.id);
          if (isPending) {
            info.el.style.border = '2px dashed orange';
          }

          info.el.addEventListener("dblclick", (e) => {
            e.stopPropagation();
            handleEventHighlightGroup(info.event, e.ctrlKey || e.metaKey);
          });
        }}

        slotLaneDidMount={(info) => {
          if (info.date < new Date()) {
            info.el.style.backgroundColor = "rgba(0,0,0,0.05)";
          }
        }}

        eventContent={EventContent}

      />
      <NoteModal show={showNoteModal} setShow={setShowNoteModal} />
      
      {/* <div className="modal-sidebar"> */}
      { authorization && (
        <ModalSidebar
          visible={showSidebar}
          onClose={setShowSidebar}
          waitPlan={plan}
          setPlan={setPlan}
          percentShow={percentShow}
          setPercentShow={setPercentShow}
          selectedRows={selectedRows}
          setSelectedRows={setSelectedRows}
          quota={quota}
          resources={resources}
          type={type}
          currentPassword = {currentPassword}
        />)}

      {/* Selecto cho phép quét chọn nhiều .fc-event */}
      { authorization && (
      <Selecto
          ref={selectoRef}
          // ✅ Khu vực cho phép kéo chọn
          container=".calendar-wrapper"
          // ✅ Các phần tử có thể được chọn
          selectableTargets={[".fc-event"]}
          // ✅ Phải giữ Shift mới kích hoạt (nếu không thì FullCalendar drag event)
          onDragStart={(e) => {
            if (!e.inputEvent.shiftKey) e.stop(); 
          }}
          selectByClick={false}
          selectFromInside={true}
          toggleContinueSelect={["shift"]}
          hitRate={100}

          // 🎯 Khi kết thúc kéo chọn
          onSelectEnd={(e) => {
            const newlySelected = e.selected.map((el) => ({
              id: el.getAttribute("data-event-id"),
              stage_code: el.getAttribute("data-stage_code"),
            }));

            setSelectedEvents((prev) => {
              // ✅ Gộp với vùng chọn cũ, tránh trùng
              const merged = [...prev, ...newlySelected].filter(
                (v, i, arr) => arr.findIndex(o => o.id === v.id) === i
              );

              // 🔹 Nếu kéo ra vùng trống => bỏ chọn hết
              if (e.selected.length === 0) {
                document.querySelectorAll(".fc-event[data-event-id]").forEach((el) => {
                  el.style.border = "none";
                });
                return [];
              }

              // 🔹 Reset viền cũ
              document.querySelectorAll(".fc-event[data-event-id]").forEach((el) => {
                const id = el.getAttribute("data-event-id");
                el.style.border = merged.some((ev) => ev.id === id)
                  ? "5px solid yellow"
                  : "none";
              });
              console.log (selectedEvents)
              return merged;
            });
          }}
      />
        )}
    </div>


  );
};

export default ScheduleTest;

  /// Xử lý hoản thành lô

  // const handleFinished = (event) => {

  //   if (!CheckAuthorization(authorization, ['Admin', 'Schedualer'])) return;

  //   let unit = event._def.extendedProps.stage_code <= 4 ? "Kg" : "ĐVL";
  //   let id = event._def.publicId;

  //   Swal.fire({
  //     title: 'Hoàn Thành Sản Xuất',
  //     html: `
  //           <div class="cfg-wrapper">
  //             <div class="cfg-card">
  //               <!-- Hàng 2 cột -->
  //               <div class="cfg-row cfg-grid-2">
  //                 <div class="cfg-col">
  //                   <label class="cfg-label" for="wt_bleding">Sản Lượng Thực Tế</label>
  //                   <input id="yields" type="number" class="swal2-input cfg-input cfg-input--full" min="0" name="wt_bleding">
  //                 </div>
  //                 <div class="cfg-col">
  //                   <label class="cfg-label" for="unit">Đơn Vị</label>
  //                   <input id="unit" type="text" class="swal2-input cfg-input cfg-input--full" readonly>
  //                   <input id="stag_plan_id" type="hidden">
  //                 </div>
  //               </div>

  //               <!-- Thêm select Quarantine Room -->
  //               <div class="cfg-row mt-3" style="text-align:center;">
  //                 <label class="cfg-label" for="quarantineRoomSelect">Phòng Biệt Trữ</label>
  //                 <select
  //                   id="quarantineRoomSelect"
  //                   class="swal2-input cfg-input cfg-input--full"
  //                   style="display:inline-block; text-align:center; border:1px solid #ccc; border-radius:8px; padding:6px; width:80%;"
  //                 >
  //                   <option value="">-- Chọn phòng --</option>
  //                 </select>
  //               </div>


  //             </div>
  //           </div>
  //         `,
  //     didOpen: () => {
  //       document.getElementById('unit').value = unit;
  //       document.getElementById('stag_plan_id').value = id;

  //       // 🔽 Gắn dữ liệu cho select từ biến quarantineRoom
  //       const select = document.getElementById('quarantineRoomSelect');
  //       if (Array.isArray(quarantineRoom)) {
  //         quarantineRoom.forEach(room => {
  //           const opt = document.createElement('option');
  //           opt.value = room.code;
  //           opt.textContent = room.code + " - " + room.name;
  //           select.appendChild(opt);
  //         });
  //       }
  //     },
  //     width: 700,
  //     customClass: { htmlContainer: 'cfg-html-left', title: 'my-swal-title' },
  //     showCancelButton: true,
  //     confirmButtonText: 'Lưu',
  //     cancelButtonText: 'Hủy',
  //     confirmButtonColor: '#3085d6',
  //     cancelButtonColor: '#d33',
  //     preConfirm: () => {
  //       const yields_input = document.getElementById('yields');
  //       const stag_plan_id = document.getElementById('stag_plan_id').value;
  //       const yields = yields_input ? yields_input.value.trim() : "";
  //       const room = document.getElementById('quarantineRoomSelect').value;

  //       if (!yields) {
  //         Swal.showValidationMessage('Vui lòng nhập sản lượng thực tế');
  //         return false;
  //       }

  //       if (!room) {
  //         Swal.showValidationMessage('Vui lòng chọn phòng cách ly');
  //         return false;
  //       }

  //       return { yields, id: stag_plan_id, room };
  //     }
  //   }).then((result) => {
  //     if (result.isConfirmed) {
  //       axios.put('/Schedual/finished', result.value)
  //         .then(res => {
  //           let data = res.data;
  //           if (typeof data === "string") {
  //             data = data.replace(/^<!--.*?-->/, "").trim();
  //             data = JSON.parse(data);
  //           }
  //           setEvents(data.events);

  //           Swal.fire({
  //             icon: 'success',
  //             title: 'Hoàn Thành',
  //             timer: 500,
  //             showConfirmButton: false,
  //           });
  //         })
  //         .catch(err => {
  //           Swal.fire({
  //             icon: 'error',
  //             title: 'Lỗi',
  //             timer: 500,
  //             showConfirmButton: false,
  //           });
  //           console.error("Finished error:", err.response?.data || err.message);
  //         });
  //     }
  //   });
  // };
  // const handleShowHistory = (event) => {
  //   let stage_code_id = event._def.extendedProps.plan_id;

  //   axios.put('/Schedual/history', { stage_code_id: stage_code_id })
  //     .then(res => {
  //       // Nếu Laravel trả về JSON
  //       let data = res.data;
  //       if (typeof data === "string") {
  //         data = data.replace(/^<!--.*?-->/, "").trim();
  //         data = JSON.parse(data);
  //       }
  //       setHistoryData(data.history_data);


  //     })
  //     .catch(err => {
  //       Swal.fire({
  //         icon: 'error',
  //         title: 'Lỗi',
  //         timer: 500,
  //         showConfirmButton: false,
  //       });
  //       console.error("Confirm_source error:", err.response?.data || err.message);
  //     });


  //   setShowHistoryModal(true)
  // }

    // const handleConfirmSource = (event) => {
  //   if (!CheckAuthorization(authorization, ['Admin', 'Schedualer'])) { return };

  //   let room_id = event._def.resourceIds[0];
  //   let plan_master_id = event._def.extendedProps.plan_master_id;
  //   let resource = resources.filter(i => i.id == room_id)[0].title;

  //   axios.put('/Schedual/getInforSoure', { plan_master_id })
  //     .then(res => {
  //       const source_infor = res.data.sourceInfo;
  //       Swal.fire({
  //         title: 'Xác Nhận Nguồn Nguyên Liệu Đã Thẩm Định Trên Thiết Bị',
  //         html: `
  //             <div class="cfg-wrapper">
  //               <div class="cfg-card">

  //                   <div class="cfg-col">
  //                     <label class="cfg-label" for="intermediate_code">Mã BTP</label>
  //                     <input id="intermediate_code" type="text"
  //                           class="swal2-input cfg-input cfg-input--full" readonly>
  //                   </div>
  //                   <div class="cfg-col">
  //                     <label class="cfg-label" for="name">Sản Phẩm</label>
  //                     <textarea id="name" rows="2"
  //                               class="swal2-textarea cfg-input cfg-input--full" readonly></textarea>
  //                   </div>

  //                   <div class="cfg-col">
  //                     <label class="cfg-label" for="room">Phòng Sản Xuất</label>
  //                     <input id="room" type="text"
  //                           class="swal2-input cfg-input cfg-input--full" readonly>
  //                   </div>

  //                   <div class="cfg-col">
  //                     <label class="cfg-label" for="material_source_id">Nguồn Nguyên Liệu</label>
  //                     <textarea id="material_source_id" rows="2"
  //                               class="swal2-textarea cfg-input cfg-input--full" readonly></textarea>
  //                   </div>
  //               </div>
  //             </div>
  //           `,
  //         didOpen: () => {
  //           document.getElementById('intermediate_code').value = source_infor.intermediate_code ?? '';
  //           document.getElementById('name').value = source_infor.product_name ?? '';
  //           document.getElementById('room').value = resource ?? '';
  //           document.getElementById('material_source_id').value = source_infor.name ?? '';


  //         },
  //         width: 700,
  //         customClass: { htmlContainer: 'cfg-html-left', title: 'my-swal-title' },
  //         showCancelButton: true,
  //         confirmButtonText: 'Xác Nhận',
  //         cancelButtonText: 'Hủy',
  //         confirmButtonColor: '#3085d6',
  //         cancelButtonColor: '#d33',
  //         preConfirm: () => {
  //           const intermediate_code = document.getElementById('intermediate_code');

  //           if (!intermediate_code) {
  //             Swal.showValidationMessage('Lỗi: dữ liệu trống');
  //             return false;
  //           }

  //           // Trả dữ liệu về để .then(result) nhận được
  //           return {
  //             source_id: source_infor.material_source_id,
  //             room_id,
  //             intermediate_code: source_infor.intermediate_code,
  //           };
  //         }
  //       }).then((result) => {
  //         if (result.isConfirmed) {
  //           axios.put('/Schedual/confirm_source', result.value)
  //             .then(res => {
  //               // Nếu Laravel trả về JSON
  //               let data = res.data;
  //               if (typeof data === "string") {
  //                 data = data.replace(/^<!--.*?-->/, "").trim();
  //                 data = JSON.parse(data);
  //               }

  //               Swal.fire({
  //                 icon: 'success',
  //                 title: 'Hoàn Thành',
  //                 timer: 500,
  //                 showConfirmButton: false,
  //               });

  //               // Nếu có dữ liệu mới trả về thì cập nhật state
  //               if (data.events) setEvents(data.events);
  //             })
  //             .catch(err => {
  //               Swal.fire({
  //                 icon: 'error',
  //                 title: 'Lỗi',
  //                 timer: 500,
  //                 showConfirmButton: false,
  //               });
  //               console.error("Confirm_source error:", err.response?.data || err.message);
  //             });
  //         }
  //       });
  //     })
  //     .catch(() => {
  //       Swal.fire({
  //         icon: 'error',
  //         title: 'Lỗi tải dữ liệu',
  //         timer: 500,
  //         showConfirmButton: false
  //       });
  //     });
  // };


  /// Ngăn xụ thay đổi lô Sau khi hoàn thành
      // const EventContent = ({ arg, selectedEvents, toggleEventSelect, handleDeleteScheduale, handleShowHistory, handleFinished, viewConfig, viewName, eventFontSize, type, authorization }) => {
  //   //const adminAutho 
  //   const event = arg.event;
  //   const props = event._def.extendedProps;
  //   const isSelected = selectedEvents.some(ev => ev.id === event.id);
  //   const now = new Date();

  //   const isTimelineMonth = viewConfig.timeView === 'resourceTimelineMonth';
  //   //const isWeekView = viewName === 'resourceTimelineWeek';

  //   const renderBadge = (text, color, left) => (
  //     <div
  //       className={`absolute top-[-15px] left-[${left}px] text-xs px-1 rounded shadow text-white ${color}`}
  //     >
  //       {text}
  //     </div>
  //   );

  //   return (
  //     <div className="relative group custom-event-content" data-event-id={event.id}>
  //       {/* Tiêu đề + thời gian */}
  //       <div style={{ fontSize: `${eventFontSize}px` }}>
  //         <b>{props.is_clearning ? event.title.split("-")[1] : event.title}</b>
  //         {!isTimelineMonth && (
  //           <>
  //             <br />
  //             {viewName !== 'resourceTimelineQuarter' && !props.is_clearning && (
  //               <span>{moment(event.start).format('HH:mm')} - {moment(event.end).format('HH:mm')}</span>
  //             )}
  //           </>
  //         )}
  //       </div>

  //       {/* Nút Chọn */}
  //       <button
  //         onClick={(e) => { e.stopPropagation(); toggleEventSelect(event); }}
  //         className={`absolute top-0 left-0 text-xs px-1 rounded shadow
  //               ${isSelected ? 'block bg-blue-500 text-white' : 'hidden group-hover:block bg-white text-blue-500 border border-blue-500'}
  //             `}
  //         title={isSelected ? 'Bỏ chọn' : 'Chọn sự kiện'}
  //       >
  //         {isSelected ? '✓' : '+'}
  //       </button>

  //       {/* 🎯 Hoàn thành */}
  //       {props.finished === 0 && type && event.end < now && (
  //         <button
  //           onClick={(e) => { e.stopPropagation(); handleFinished(event); }}
  //           className="absolute bottom-0 left-0 hidden group-hover:block text-blue-500 text-sm bg-white px-1 rounded shadow"
  //           title="Xác Nhận Hoàn Thành Lô Sản Xuất"
  //         >
  //           🎯
  //         </button>
  //       )}

  //       {/* Nút Xem Lịch Sử && isWeekView  
  //       {showRenderBadge && (
  //         <button
  //           onClick={(e) => { e.stopPropagation(); handleShowHistory(event); }}
  //           className="absolute top-[-15px] left-[100px] text-xs px-1 rounded shadow bg-red-500 text-white"
  //           title="Xem Lịch Sử Thay Đổi"
  //         >
  //           {props.number_of_history}

  //         </button>
  //       )}*/}

  //       {/* Badge Ngày cần hàng
  //       {props.expected_date && showRenderBadge && renderBadge(
  //         props.expected_date,
  //         {
  //           1: 'bg-red-500',
  //           2: 'bg-orange-500',
  //           3: 'bg-green-500'
  //         }[props.level] || 'bg-blue-500',
  //         50
  //       )} */}


  //       {/* Hướng công đoạn 
  //       {!props.is_clearning && showRenderBadge && (
  //         <button
  //           className="absolute top-[-15px] right-5 text-15 px-1 rounded shadow bg-white text-red-600"
  //           title="% biệt trữ"
  //         >
  //           <b>{props.storage_capacity}</b>
  //         </button>
  //       )}*/}

  //        {/* Nút Xóa 
  //       {!props.finished && (
  //         <button
  //           onClick={(e) => { e.stopPropagation(); handleDeleteScheduale(e); }}
  //           className="absolute top-0 right-0 hidden group-hover:block text-red-500 text-sm bg-white px-1 rounded shadow"
  //           title="Xóa lịch"
  //         >
  //           ×
  //         </button>
  //       )}*/}

  //       {/* {isWeekView && props.tank && showRenderBadge ? renderBadge('⚗️', 'bg-red-500', 170) : ''}
  //       {isWeekView && props.keep_dry && showRenderBadge ? renderBadge('🌡', 'bg-red-500', 200) : ''} */}

  //       {/* 📦 Nguồn nguyên liệu */}
  //       {/* {props.room_source === false && type && (
  //             <button
  //               onClick={(e) => { e.stopPropagation(); handleConfirmSource(event); }}
  //               className="absolute bottom-0 left-0 hidden group-hover:block text-blue-500 text-sm bg-white px-1 rounded shadow"
  //               title="Khai báo nguồn nguyên liệu"
  //             >
  //               📦
  //             </button>
  //         )} */}

  //     </div>
  //   );
  // };

  {/* <History show={showHistoryModal} setShow={setShowHistoryModal} historyData={historyData} /> */}

    // resourceLabelContent={(arg) => {
        
        //   const res = arg.resource.extendedProps;
        //   const busy = parseFloat(res.busy_hours) || 0;
        //   const yields = parseFloat(res.yield) || 0;
        //   const unit = res.unit || null;
        //   const total = parseFloat(res.total_hours) || 1;
        //   const efficiency = ((busy / total) * 100).toFixed(1);


        //   const highlight = selectedRows.some(row => {
        //     if (!row.permisson_room) return false;

        //     if (Array.isArray(row.permisson_room)) {
        //       // nếu backend đổi thành array thì vẫn chạy
        //       return row.permisson_room.includes(arg.resource.extendedProps.code);
        //     } else if (typeof row.permisson_room === "object") {
        //       // trường hợp {id_room: code}
        //       return Object.values(row.permisson_room).includes(arg.resource.extendedProps.code);
        //     } else {
        //       // fallback: string / id
        //       return row.permisson_room == arg.resource.id;
        //     }
        //   });

        //   return (
        //     <div
        //       style={{
        //         backgroundColor: highlight ? "#c6f7d0" : "transparent",
        //         padding: "0px",
        //         borderRadius: "6px",
        //         marginTop: "0px",
        //         position: "relative",
        //         height: heightResource // cần để con có thể dịch lên
        //       }}
        //     >
        //       <div
        //         style={{
        //           fontSize: "22px",
        //           fontWeight: "bold",
        //           marginBottom: "2px",
        //           width: "8%",
        //           position: "relative",
        //           top: "-26px", // dịch lên trên 6px
        //         }}
        //       >
        //         {arg.resource.title}-{arg.resource.extendedProps.main_equiment_name}
        //       </div>

        //       <div
        //         className="resource-bar"
        //         style={{
        //           position: "relative",
        //           top: "-26px", // dịch luôn cả progress bar lên
        //           height: "15px",
        //           background: "#eeeeeeff",
        //           borderRadius: "20px",
        //           overflow: "hidden",
        //           display: "flex",
        //           alignItems: "center",
        //         }}
        //       >
        //         <div
        //           className="busy"
        //           style={{
        //             width: `${(busy / total) * 100}%`,
        //             background: "red",
        //             height: "100%",
        //             display: "flex",
        //             alignItems: "center",
        //             justifyContent: "center",
        //           }}
        //         />
        //         <b
        //           style={{
        //             position: "absolute",
        //             top: "50%",
        //             left: "50%",
        //             transform: "translate(-50%, -50%)",
        //             fontSize: "70%",
        //             color: "#060606ff",
        //           }}
        //         >
        //           {efficiency}% - {formatNumberWithComma(yields)} {unit}
        //         </b>
        //       </div>
        //     </div>

        //   );
        // }}
