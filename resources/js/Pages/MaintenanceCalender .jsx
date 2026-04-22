import React, { useRef, useState, useEffect, useCallback, useMemo } from 'react';

import '@fullcalendar/daygrid/index.js';
import '@fullcalendar/resource-timeline/index.js';
import ReactDOM from 'react-dom/client';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';
import { useHotkeys } from "react-hotkeys-hook";
import { Calendar } from 'primereact/calendar';
import { Stepper } from 'primereact/stepper';
import { StepperPanel } from 'primereact/stepperpanel';
import { createRoot } from 'react-dom/client';
import axios from "axios";
import 'moment/locale/vi';
import moment from 'moment';
import dayjs from 'dayjs';
import Selecto from "react-selecto";
import Swal from 'sweetalert2';

import './calendar.css';
import CalendarSearchBox from '../Components/CalendarSearchBox';
import EventFontSizeInput from '../Components/EventFontSizeInput';
import MaintenanceSidebar from '../Components/MaintenanceSidebar';
import NoteModal from '../Components/NoteModal';

//import History from '../Components/History';
//import { CheckAuthorization } from '../Components/CheckAuthorization';

const MaintenanceCalender = () => {

  const calendarRef = useRef(null);
  const selectoRef = useRef(null);
  const todayStr = dayjs().format('YYYY-MM-DD');
  moment.locale('vi');
  const [showSidebar, setShowSidebar] = useState(false);
  const [viewConfig, setViewConfig] = useState({ timeView: 'resourceTimelineWeek', slotDuration: '00:15:00', is_clearning: true });
  const [pendingChanges, setPendingChanges] = useState([]);
  const [selectedEvents, setSelectedEvents] = useState([]);
  const [percentShow, setPercentShow] = useState("100%");
  const searchResultsRef = useRef([]);
  const currentIndexRef = useRef(-1);
  const lastQueryRef = useRef("");
  const slotViewWeeks = ['resourceTimelineWeek1day', 'resourceTimelineWeek4h', 'resourceTimelineWeek1h', 'resourceTimelineWeek15'];
  const slotViewMonths = ['resourceTimelineMonth1d', 'resourceTimelineMonth4h', 'resourceTimelineMonth1h',];
  const [slotIndex, setSlotIndex] = useState(0);
  const [eventFontSize, setEventFontSize] = useState(22); // default 14px
  const [selectedRows, setSelectedRows] = useState([]);
  const [showNoteModal, setShowNoteModal] = useState(false);
  const [viewName, setViewName] = useState("resourceTimelineWeek");
  const [showRenderBadge, setShowRenderBadge] = useState(false);
  const [offDays, setOffDays] = useState([]);
  const [saving, setSaving] = useState(false);

  const [workingSunday, setWorkingSunday] = useState(false);
  const [multiStage, setMultiStage] = useState(false);

  const [events, setEvents] = useState([]);
  const [resources, setResources] = useState([]);
  const [sumBatchByStage, setSumBatchByStage] = useState([]);
  const [plan, setPlan] = useState([]);
  const [quota, setQuota] = useState([]);

  const [stageMap, setStageMap] = useState({});
  const [maintenanceType, setMaintenanceType] = useState('HC'); // HC, TB, TI
  const [type, setType] = useState(true);
  const [isProductionHidden, setIsProductionHidden] = useState(() => {
    return JSON.parse(sessionStorage.getItem('productionHidden')) || false;
  });
  const [loading, setLoading] = useState(false);
  const [authorization, setAuthorization] = useState(false);
  const [authorizationAccept, setAuthorizationAccept] = useState(false);
  const [heightResource, setHeightResource] = useState("1px");
  const [reasons, setReasons] = useState([]);
  const [bkcCode, setBkcCode] = useState([]);
  const [lines, setLines] = useState(['S16']);
  const [allLines, setAllLines] = useState([]);
  const [currentPassword, setCurrentPassword] = useState(null);
  const [userID, setUserID] = useState(null);
  const [userGroup, setUserGroup] = useState([]);
  const [userGroupName, setUserGroupName] = useState(null);
  const [production, setProduction] = useState(null);
  const [userDepartment, setUserDepartment] = useState(null);

  const [activePlanMasterId, setActivePlanMasterId] = useState(null);
  const [lockedResourceCodes, setLockedResourceCodes] = useState(null);

  const fontSizeRootRef = useRef(null);
  const searchRootRef = useRef(null);


  const stageName = {
    1: 'Cân Nguyên Liệu',
    3: 'Pha Chế',
    4: 'Trộn Hoàn Tất',
    5: 'Định Hình',
    6: 'Bao Phim',
    7: 'ĐGSC-ĐGTC'
  };

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

  // Get dữ liệu ban đầu
  useEffect(() => {

    Swal.fire({
      title: "Đang tải...",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    const api = calendarRef.current?.getApi();
    if (!api) return;

    const { activeStart, activeEnd } = api.view;
    axios.post("/MaintenanceSchedual/view", {
      startDate: toLocalISOString(activeStart),
      endDate: toLocalISOString(activeEnd),
      viewtype: viewName,
    })
      .then(res => {

        let data = res.data;


        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data); // 👈 bắt buộc nếu là string JSON
        }



        const isAuthorized = data.authorization_scheduler // (['Admin', 'Calibration and Maintenance Scheduler', 'Calibration and Maintenance Planning Manager'].includes(data.authorization) && data.production == data.department) || data.department == 'BOD';

        setAuthorization(isAuthorized);
        setAuthorizationAccept(data.authorization_accept);

        setEvents(data.events);
        setResources(data.resources);
        setType(data.type)
        setStageMap(data.stageMap);
        setSumBatchByStage(data.sumBatchByStage);
        setReasons(data.reason)
        setLines(data.Lines)
        setAllLines(data.allLines)

        sessionStorage.setItem('theoryHidden', 0);


        if (isAuthorized) {
          const dept = data.department;
          //const userGroups = Array.isArray(data.authorization) ? data.authorization : (data.authorization ? [data.authorization] : []);
          const isAdmin = data.authorization == 'Admin'; //userGroups.some(g => ['Admin'].includes(g));

          let filteredPlan = data.plan;

          if (!isAdmin && dept === 'QA') {
            setMaintenanceType('HC')
            filteredPlan = (data.plan || []).filter(p => {
              const code = String(p.code || '');
              return code.endsWith('_HC');
            });
          } else if (!isAdmin && dept === 'EN') {
            setMaintenanceType('TB')
            filteredPlan = (data.plan || []).filter(p => {
              const code = String(p.code || '');
              return code.endsWith('_TB') || code.endsWith('_8') || code.endsWith('_TI');
            });
          }



          setPlan(filteredPlan);
          setCurrentPassword(data.currentPassword ?? '')
          setQuota(data.quota);
          setOffDays(data.off_days);
          //setBkcCode(data.bkc_code);
          setUserID(data.UesrID);
          setUserGroup(data.authorization);
          setUserGroupName(data.groupName);
          setProduction(data.production);
          setUserDepartment(data.department);
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
      .catch(err => {
        console.error("API ERROR:", err);
      });

  }, [loading]);



  useEffect(() => {
    const toolbarEl = document.querySelector(".fc-fontSizeBox-button");
    if (!toolbarEl) return;

    if (!fontSizeRootRef.current) {
      const container = document.createElement("div");
      toolbarEl.appendChild(container);
      fontSizeRootRef.current = {
        root: ReactDOM.createRoot(container),
        container: container,
        parent: toolbarEl
      };
    }

    fontSizeRootRef.current.root.render(
      <EventFontSizeInput fontSize={eventFontSize} setFontSize={setEventFontSize} />
    );
  }, [eventFontSize]);

  useHotkeys("alt+q", (e) => {
    e.preventDefault();

    handleViewChange("resourceTimelineDay");
  },
    { enableOnFormTags: false }
  );

  useHotkeys("alt+w", (e) => {
    e.preventDefault();

    handleViewChange("resourceTimelineWeek");
  },
    { enableOnFormTags: false }
  );

  useHotkeys("alt+e", (e) => {
    e.preventDefault();

    handleViewChange("resourceTimelineMonth");
  },
    { enableOnFormTags: false }
  );

  useHotkeys("alt+r", (e) => {
    e.preventDefault();

    handleViewChange("resourceTimelineQuarter");
  },
    { enableOnFormTags: false }
  );

  useHotkeys("ctrl+s", (e) => {
    e.preventDefault();

    handleSaveChanges();
  },
    { enableOnFormTags: false }
  );

  /// Get dư liệu row được chọn
  useEffect(() => {

    if (!authorization) return;

    const externalEl = document.getElementById('external-events');
    if (!externalEl) {
      return;
    }

    const draggable = new Draggable(externalEl, {
      itemSelector: '.fc-event',
      eventData: (eventEl) => {
        const draggedData = selectedRows.length ? selectedRows : [];
        return {
          title:
            draggedData.length > 1
              ? `(${draggedData.length}) thiết bị`
              : draggedData[0]?.code || draggedData[0]?.name || 'Bảo trì',
          extendedProps: { rows: draggedData },
        };
      },
    });

    return () => {
      draggable.destroy();
    };
  }, [authorization, selectedRows, plan]);

  /// UseEffect cho render nut search
  useEffect(() => {
    // sau khi calendar render xong, inject vào toolbar
    const toolbarEl = document.querySelector(".fc-searchBox-button");
    if (!toolbarEl) return;

    if (!searchRootRef.current) {
      const container = document.createElement("div");
      toolbarEl.appendChild(container);
      searchRootRef.current = {
        root: ReactDOM.createRoot(container),
        container: container,
        parent: toolbarEl
      };
    }

    searchRootRef.current.root.render(
      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
        <CalendarSearchBox onSearch={handleSearch} />
      </div>
    );
  }, []);

  // Cleanup React roots safely
  useEffect(() => {
    return () => {
      if (fontSizeRootRef.current) {
        const { root, container, parent } = fontSizeRootRef.current;
        setTimeout(() => {
          try { root.unmount(); } catch (e) { }
        }, 0);
        if (parent && parent.contains(container)) {
          parent.removeChild(container);
        }
      }
      if (searchRootRef.current) {
        const { root, container, parent } = searchRootRef.current;
        setTimeout(() => {
          try { root.unmount(); } catch (e) { }
        }, 0);
        if (parent && parent.contains(container)) {
          parent.removeChild(container);
        }
      }
    };
  }, []);

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
      block: "nearest",
      inline: "nearest",
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

      const productionHidden = JSON.parse(sessionStorage.getItem('productionHidden'));
      const cleaningHidden = JSON.parse(sessionStorage.getItem('cleaningHidden'));
      const theoryHidden = JSON.parse(sessionStorage.getItem('theoryHidden'));

      // 🔹 4. Gọi API backend
      const { data } = await axios.post(`/MaintenanceSchedual/view`, {
        startDate: toLocalISOString(activeStart),
        endDate: toLocalISOString(activeEnd),
        viewtype: currentView,
        production: !productionHidden, // Trình trạng hiện/ẩn sản xuất
        clearning: !cleaningHidden,   // Vẫn giữ logic vệ sinh nếu cần
        theory: theoryHidden,
      });

      let cleanData = data;
      if (typeof cleanData === "string") {
        cleanData = JSON.parse(cleanData.replace(/^<!--.*?-->/, "").trim());
      }

      // 🔹 5. Cập nhật dữ liệu mới (Phân quyền & Lọc thông minh)
      const isAuthorized = cleanData.authorization_scheduler;
      setAuthorization(isAuthorized);
      setAuthorizationAccept(cleanData.authorization_accept);

      setEvents(cleanData.events);
      setResources(cleanData.resources);
      setSumBatchByStage(cleanData.sumBatchByStage);

      if (isAuthorized) {
        const dept = cleanData.department;
        const isAdmin = cleanData.authorization == 'Admin';
        let filteredPlan = cleanData.plan;

        if (!isAdmin && dept === 'QA') {
          setMaintenanceType('HC')
          filteredPlan = (cleanData.plan || []).filter(p => {
            const code = String(p.code || '');
            return code.endsWith('_HC');
          });
        } else if (!isAdmin && dept === 'EN') {
          setMaintenanceType('TB')
          filteredPlan = (cleanData.plan || []).filter(p => {
            const code = String(p.code || '');
            return code.endsWith('_TB') || code.endsWith('_8') || code.endsWith('_TI');
          });
        }
        setPlan(filteredPlan);
      } else {
        setPlan(cleanData.plan || []);
      }

      setUserID(cleanData.UesrID);
      setUserGroup(cleanData.authorization);
      setUserGroupName(cleanData.groupName);
      setProduction(cleanData.production);
      setUserDepartment(cleanData.department);
      if (viewType) setViewName(viewType);
    } finally {

    }
  }, []);

  const toggleProductionEvents = () => {
    const newHidden = !isProductionHidden;
    setIsProductionHidden(newHidden);
    sessionStorage.setItem('productionHidden', JSON.stringify(newHidden));
    // No handleViewChange call needed anymore for this toggle
  };

  const toggleTheoryEvents = () => {
    const current = Number(sessionStorage.getItem('theoryHidden')) || 0;
    const next = (current + 1) % 3; // 0 → 1 → 2 → 0

    sessionStorage.setItem('theoryHidden', next);

    handleViewChange(null, null);
  };

  // Nhân Dữ liệu để tạo mới event
  const handleEventReceive = (info) => {
    const now = new Date();
    const start = info.event.start;
    const resourceId = info.event.getResources?.()[0]?.id ?? null;
    const api = calendarRef.current?.getApi();
    const slotDuration = api.currentData.options.slotDuration['days'];

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

    if (start <= now) {
      Swal.fire({
        icon: "warning",
        title: "Thời gian bắt đầu sắp lịch nhỏ hơn thời gian hiện tại!",
        timer: 1000,
        showConfirmButton: false,
      });
      return false;
    }
    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;

    axios.put('/MaintenanceSchedual/store', {
      room_id: resourceId,
      stage_code: 8,
      start: moment(start).format("YYYY-MM-DD HH:mm:ss"),
      products: selectedRows,
      startDate: toLocalISOString(activeStart),
      endDate: toLocalISOString(activeEnd),
      slotDuration: slotDuration,
      type: maintenanceType,

    })
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }

        setEvents(data.events);
        setPlan(data.plan);

        // Lưu lại bộ lọc hiện tại trước khi xóa selection ở sidebar
        const currentValidCodes = new Set();
        selectedRows.forEach(row => {
          if (row.related_rooms && Array.isArray(row.related_rooms)) {
            row.related_rooms.forEach(room => currentValidCodes.add(room.room_code));
          }
        });
        if (currentValidCodes.size > 0) {
          setLockedResourceCodes(currentValidCodes);
        }

        setSelectedRows([]); // Restore this: clear sidebar selection

        // Tự động cuộn đến vị trí vừa sắp lịch
        setTimeout(() => {
          const api = calendarRef.current?.getApi();
          if (api) {
            // Đảm bảo nhảy đến ngày tương ứng
            api.gotoDate(start);

            // Tìm và cuộn đến event vừa tạo (thường có suffix -maintenance do group)
            // Lấy ID của một trong các row vừa chọn để tìm
            const firstId = selectedRows[0]?.id || "";
            const eventEl = document.querySelector(`[data-event-id*="${firstId}"]`);
            if (eventEl) {
              eventEl.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            } else {
              // Fallback nếu k tìm thấy DOM event
              api.scrollToTime(moment(start).format("HH:mm:ss"));
              const resourceCell = document.querySelector(`[data-resource-id="${resourceId}"]`);
              if (resourceCell) {
                resourceCell.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
              }
            }
          }
        }, 500);
      })
      .catch(err => {
        const errorMsg = err.response?.data?.message || err.message || "Lỗi không xác định";
        Swal.fire({
          icon: 'error',
          title: 'Lỗi tạo lịch',
          html: errorMsg,
        });
      });

  };

  const handleAutoSchedule = async () => {
    if (!authorization) return;

    const typeLabel = maintenanceType === 'HC' ? 'Hiệu Chuẩn' : maintenanceType === 'TB' ? 'Bảo Trì' : 'Tiện Ích';

    const result = await Swal.fire({
      title: `Tự Động Sắp Lịch ${typeLabel}`,
      text: `Hệ thống sẽ tự động sắp xếp các tác vụ ${typeLabel} đang chờ dựa trên ngày tới hạn và nhóm theo phòng. Bạn có chắc chắn muốn thực hiện?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Đồng ý',
      cancelButtonText: 'Hủy'
    });

    if (result.isConfirmed) {
      Swal.fire({
        title: "Đang sắp lịch...",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
      const currentView = calendarRef.current?.getApi().view.type;
      const theoryHidden = Number(sessionStorage.getItem('theoryHidden')) || 0;

      axios.post('/MaintenanceSchedual/autoSchedual', {
        startDate: toLocalISOString(new Date()), // Bắt đầu từ thời điểm hiện tại
        viewStart: toLocalISOString(activeStart),
        viewEnd: toLocalISOString(activeEnd),
        viewtype: currentView,
        theory: theoryHidden,
        type: maintenanceType
      })
        .then(res => {
          Swal.close();
          if (res.data.success) {
            setEvents(res.data.events);
            setPlan(res.data.plan);
            Swal.fire({
              icon: 'success',
              title: 'Thành công',
              text: `Đã tự động sắp xếp ${res.data.scheduled_count} tác vụ.`,
              timer: 2000
            });
          } else {
            Swal.fire({
              icon: 'warning',
              title: 'Thông báo',
              text: res.data.message
            });
          }
        })
        .catch(err => {
          Swal.close();
          Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: err.response?.data?.message || "Có lỗi xảy ra khi tự động sắp lịch."
          });
        });
    }
  };

  const handleCancelSchedule = (params) => {
    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
    const currentView = calendarRef.current?.getApi().view.type;
    const theoryHidden = Number(sessionStorage.getItem('theoryHidden')) || 0;

    return axios.post('/MaintenanceSchedual/cancelSchedule', {
      ...params,
      viewStart: toLocalISOString(activeStart),
      viewEnd: toLocalISOString(activeEnd),
      viewtype: currentView,
      theory: theoryHidden
    })
      .then(res => {
        if (res.data.success) {
          setEvents(res.data.events);
          setPlan(res.data.plan);
          return res.data;
        }
        throw new Error(res.data.message);
      });
  };


  const timeToMilliseconds = (time) => {
    const [h, m] = time.split(":").map(Number);
    return (h * 3600 + m * 60) * 1000;
  };

  const buildOffRanges = (offDays) => {
    if (!Array.isArray(offDays)) return [];

    return offDays.map(d => {
      const start = new Date(`${d}T06:00:00`);
      const end = new Date(start.getTime() + 24 * 60 * 60 * 1000);

      return { start, end };
    }).sort((a, b) => a.start - b.start);
  };

  const skipOffDays = (date, offRanges) => {
    let current = new Date(date);

    for (const off of offRanges) {

      // nếu current nằm trong khoảng nghỉ
      if (current >= off.start && current < off.end) {
        current = new Date(off.end);
        break;
      }

      // vì đã sort
      if (current < off.start) break;
    }

    return current;
  };

  const offRanges = useMemo(
    () => buildOffRanges(offDays),
    [offDays]
  );

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
          const offset = delta.milliseconds + delta.days * 24 * 60 * 60 * 1000;
          const event_start = event.start.getTime()
          const newStart = new Date(event_start + offset);
          let newEnd = new Date(event.end.getTime() + offset);

          event.setDates(newStart, newEnd, { maintainDuration: true, skipRender: true }); // skipRender nếu có

          batchUpdates.push({
            id: event.id,
            start: newStart.toISOString(),
            end: newEnd.toISOString(),
            resourceId: event.getResources?.()[0]?.id ?? null,
            title: event.title,
            submit: event._def.extendedProps.submit
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
        title: changedEvent.title,
        submit: changedEvent.submit
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

    // 🟨 Tạo datalist từ state "reasons"
    const htmlOptions = reasons
      .map(r => `<option value="${r}">`)
      .join("");

    // 🟨 Swal datalist (select hoặc nhập)
    const { value: reason } = await Swal.fire({
      title: 'Chọn lý do thay đổi',
      width: '800px',
      html: `
        <style>
          #reasonInput {
            width: 650px !important;   
            max-width: 650px !important;
          }
        </style>

      
          <input list="reasonList" id="reasonInput" name="reason"
                class="swal2-input"
                placeholder="Chọn hoặc nhập lý do">
          <datalist id="reasonList">
            ${htmlOptions}
          </datalist>


          <div class="cfg-row">
              <label class="mt-2 cfg-label" for="work-sunday">Lưu Lại Lý Do:</label>
              <label class="switch">
                <input id="saveReason" type="checkbox">
                <span class="slider round"></span>
                <span class="switch-labels">
                  <span class="off">No</span>
                  <span class="on">Yes</span>
                </span>
              </label>
          </div>
      
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Xác nhận lưu',
      cancelButtonText: 'Hủy',
      preConfirm: () => {
        const formValues = {};

        const reason = document.getElementById('reasonInput').value;

        const saveReason = document.getElementById('saveReason');
        formValues.saveReason = saveReason.checked;


        if (!reason || reason.trim() === '') {
          Swal.showValidationMessage('Bạn phải nhập hoặc chọn lý do!');
          return false;
        }
        formValues.reason = reason;

        return formValues;
      }
    });

    // Nếu người dùng bấm “Hủy” thì dừng
    if (!reason) return;

    setSaving(true);

    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
    let startDate = toLocalISOString(activeStart);
    let endDate = toLocalISOString(activeEnd);
    const theoryHidden = JSON.parse(sessionStorage.getItem('theoryHidden'));
    const productionHidden = JSON.parse(sessionStorage.getItem('productionHidden'));
    const cleaningHidden = JSON.parse(sessionStorage.getItem('cleaningHidden'));

    axios.put('/MaintenanceSchedual/update', {
      reason, // 🟢 gửi thêm lý do
      theory: theoryHidden,
      production: !productionHidden,
      clearning: !cleaningHidden,
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
        if (data.resources) setResources(data.resources);
        setSumBatchByStage(data.sumBatchByStage);
        //setPlan(data.plan);
        // Tự động cuộn đến vị trí vừa thay đổi
        if (pendingChanges.length > 0) {
          const firstChange = pendingChanges[0];
          setTimeout(() => {
            const api = calendarRef.current?.getApi();
            if (api) {
              // Đảm bảo nhảy đến ngày tương ứng
              api.gotoDate(firstChange.start);

              const eventId = firstChange.id.split('-')[0]; // Lấy phần số thực tế
              const eventEl = document.querySelector(`[data-event-id*="${eventId}"]`);
              if (eventEl) {
                eventEl.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
              } else {
                // Fallback nếu k tìm thấy DOM
                api.scrollToTime(dayjs(firstChange.start).format("HH:mm:ss"));
                const resourceCell = document.querySelector(`[data-resource-id="${firstChange.resourceId}"]`);
                if (resourceCell) {
                  resourceCell.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
              }
            }
          }, 500);
        }

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
        setSaving(false);
        Swal.fire({
          icon: 'error',
          title: 'Lỗi!',
          text: 'Không thể lưu thay đổi. Vui lòng thử lại.',
        });
      });
  };

  const handleEditEventClick = (event) => {

    if (!authorization) return;

    // Nếu đã chấp nhận (Tank == 1) thì không cho sửa nhanh
    if (event.extendedProps.tank == 1) {
      Swal.fire({
        icon: 'warning',
        title: 'Không thể thay đổi',
        text: 'Lịch đã được Chấp Nhân, Không được thay đổi. Cần Liên hệ phân xưởng để bỏ Chấp Nhân trước khi thay đổi!',
      });
      return;
    }

    const startStr = moment(event.start).format('YYYY-MM-DDTHH:mm');
    const endStr = moment(event.end).format('YYYY-MM-DDTHH:mm');
    const currentResourceId = event.getResources()[0]?.id;

    Swal.fire({
      title: 'Cập nhật Thời gian Bảo trì',
      width: '450px',
      html: `
        <div style="text-align: left; padding: 10px;">
          <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Thời gian bắt đầu:</label>
            <input type="datetime-local" id="swal-start" class="swal2-input" style="width: 100%; margin: 0;" value="${startStr}">
          </div>
          
          <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Thời gian kết thúc:</label>
            <input type="datetime-local" id="swal-end" class="swal2-input" style="width: 100%; margin: 0;" value="${endStr}">
          </div>
          
          <div style="margin-top: 5px; color: #666; font-size: 0.9em;">
            <strong>Ghi chú:</strong> Hệ thống sẽ tự động cập nhật tất cả các phòng liên quan của cùng kế hoạch bảo trì này.
          </div>
        </div>
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Lưu',
      cancelButtonText: 'Hủy',
      preConfirm: () => {
        const start = document.getElementById('swal-start').value;
        const end = document.getElementById('swal-end').value;

        if (!start || !end) {
          Swal.showValidationMessage('Vui lòng nhập đầy đủ thời gian');
          return false;
        }
        return { start, end };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const { start, end } = result.value;
        const api = calendarRef.current?.getApi();
        const { activeStart, activeEnd } = api.view;
        const theoryHidden = JSON.parse(sessionStorage.getItem('theoryHidden'));

        Swal.fire({
          title: 'Đang tải...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        axios.put('/MaintenanceSchedual/update', {
          theory: theoryHidden,
          changes: [{
            id: event.id,
            start: moment(start).format('YYYY-MM-DD HH:mm:ss'),
            end: moment(end).format('YYYY-MM-DD HH:mm:ss'),
            resourceId: currentResourceId,
            title: event.title,
            C_end: false
          }],
          startDate: toLocalISOString(activeStart),
          endDate: toLocalISOString(activeEnd),
          reason: { reason: 'Cập nhật nhanh qua modal' }
        })
          .then(res => {
            let data = res.data;
            if (typeof data === "string") {
              data = JSON.parse(data.replace(/^<!--.*?-->/, "").trim());
            }
            setEvents(data.events);
            if (data.resources) setResources(data.resources);
            setSumBatchByStage(data.sumBatchByStage);

            Swal.fire({
              icon: 'success',
              title: 'Thành công',
              timer: 1000,
              showConfirmButton: false
            });
          })
          .catch(err => {
            Swal.fire({
              icon: 'error',
              title: 'Lỗi',
              text: err.response?.data?.message || 'Không thể cập nhật lịch'
            });
          });
      }
    });
  };

  const handleConfirmFinish = () => {
    if (selectedEvents.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Chưa chọn sự kiện',
        text: 'Vui lòng nhấn Shift + Quét để chọn các sự kiện bảo trì cần xác nhận hoàn thành.'
      });
      return;
    }

    Swal.fire({
      title: 'Xác nhận hoàn thành?',
      text: "Các thiết bị được chọn sẽ được cập nhật trạng thái đã xong. Bạn có chắc chắn?",
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Đồng ý',
      cancelButtonText: 'Hủy'
    }).then((result) => {
      if (result.isConfirmed) {
        axios.put('/MaintenanceSchedual/confirmFinish', {
          ids: selectedEvents.map(e => e.id)
        })
          .then(res => {
            Swal.fire({
              icon: 'success',
              title: 'Thành công',
              text: res.data.message,
              timer: 1500,
              showConfirmButton: false
            });
            handleViewChange();
            setSelectedEvents([]);
            // Xóa viền vàng cũ
            document.querySelectorAll(".fc-event[data-event-id]").forEach((el) => {
              el.style.border = "none";
            });
          })
          .catch(err => {
            Swal.fire({
              icon: 'error',
              title: 'Lỗi',
              text: err.response?.data?.message || 'Không thể xác nhận hoàn thành'
            });
          });
      }
    });
  };

  const handleApproveMaintenance = () => {
    if (selectedEvents.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Chưa chọn sự kiện',
        text: 'Vui lòng nhấn Shift + Quét để chọn các sự kiện cần duyệt.'
      });
      return;
    }

    Swal.fire({
      title: 'Duyệt sự kiện bảo trì?',
      text: "Các sự kiện được chọn sẽ được chuyển sang trạng thái đã duyệt (Tank = 1).",
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Đồng ý',
      cancelButtonText: 'Hủy'
    }).then((result) => {
      if (result.isConfirmed) {
        axios.put('/MaintenanceSchedual/approveMaintenance', {
          ids: selectedEvents.map(e => e.id)
        })
          .then(res => {
            Swal.fire({
              icon: 'success',
              title: 'Thành công',
              text: res.data.message,
              timer: 1500,
              showConfirmButton: false
            });
            handleViewChange();
            setSelectedEvents([]);
            document.querySelectorAll(".fc-event[data-event-id]").forEach((el) => {
              el.style.border = "none";
            });
          })
          .catch(err => {
            Swal.fire({
              icon: 'error',
              title: 'Lỗi',
              text: err.response?.data?.message || 'Không thể duyệt sự kiện'
            });
          });
      }
    });
  };

  /// Xử lý Toggle sự kiện đang chọn: if đã chọn thì bỏ ra --> selectedEvents
  const selectedEventsRef = useRef([]);
  const toggleEventSelect = (event) => {
    // Không cho phép chọn sự kiện đã hoàn thành hoặc không phải là lịch bảo trì (stage_code != 8)
    if (event.extendedProps.finished == 1 || event.extendedProps.stage_code != 8) {
      return;
    }

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
      selectedEventsRef.current = newSelected;
      return newSelected;
    });
  };

  /// Xử lý chọn 1 sự kiện -> selectedEvents
  const handleEventClick = (clickInfo) => {
    const event = clickInfo.event;

    // Chặn tất cả tương tác nếu lịch đã hoàn thành
    if (event.extendedProps.finished == 1) {
      Swal.fire({
        icon: 'warning',
        title: 'Không thể thay đổi',
        text: 'Lịch đã được Hoàn Thành, Không được thay đổi',
      });
      return;
    }

    // Nếu đã chấp nhận (Tank == 1) thì không cho chọn/sửa
    if (event.extendedProps.tank == 1 && !authorizationAccept) {
      Swal.fire({
        icon: 'warning',
        title: 'Không thể thay đổi',
        text: 'Lịch đã được Chấp Nhân, Không được thay đổi. Cần Liên hệ phân xưởng để bỏ Chấp Nhân trước khi thay đổi!',
      });
      return;
    }

    // Ưu tiên xử lý nút sửa nhanh
    if (clickInfo.jsEvent && clickInfo.jsEvent.target.closest('.edit-single-event-btn')) {
      handleEditEventClick(clickInfo.event);
      return;
    }


    /// Copy nội dung event
    if (clickInfo.jsEvent.shiftKey) {
      const calendar = clickInfo.view.calendar;

      let eventsToCopy = selectedEventsRef.current;

      if (!eventsToCopy || eventsToCopy.length === 0) {
        eventsToCopy = [{ id: event.id }];
      }

      const rows = eventsToCopy.map(sel => {
        const ev = calendar.getEventById(sel.id);
        const resourceId = Number(ev._def.resourceIds?.[0]);

        if (!ev) return null;
        return [
          ev.title,
          resources.find(r => r.id == resourceId)?.title || '',
          ev.start?.toLocaleString(),
          ev.end?.toLocaleString(),
          stageName[ev.extendedProps.stage_code] || ''
        ].join('\t');
      }).filter(Boolean);

      const clipboardText = [
        ['Title', 'Room', 'Start', 'End', 'Stage'].join('\t'),
        ...rows
      ].join('\n');

      if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(clipboardText);
      } else {
        fallbackCopy(clipboardText);
      }

      // navigator.clipboard.writeText(clipboardText)
      //   .then(() => alert(`Đã copy ${rows.length} event`));

      return;
    }

    function fallbackCopy(text) {
      const textarea = document.createElement('textarea');
      textarea.value = text;
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
    }

    toggleEventSelect(event);


  };


  /// bỏ chọn tất cả sự kiện đã chọn ở select sidebar -->  selectedEvents
  const handleClear = () => {

    const sel = selectoRef.current;
    document.querySelectorAll('.fc-event[data-event-id]').forEach(el => { el.style.border = 'none'; });

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
        const theoryHidden = JSON.parse(sessionStorage.getItem('theoryHidden'));
        const productionHidden = JSON.parse(sessionStorage.getItem('productionHidden'));
        const cleaningHidden = JSON.parse(sessionStorage.getItem('cleaningHidden'));

        axios.put('/MaintenanceSchedual/deActive', {
          ids: selectedEvents,
          startDate: toLocalISOString(activeStart),
          endDate: toLocalISOString(activeEnd),
          theory: theoryHidden,
          production: !productionHidden,
          clearning: !cleaningHidden
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
    const stageCode = draggedEvent._def.extendedProps.stage_code;
    const finished = draggedEvent._def.extendedProps.finished;
    const tank = draggedEvent._def.extendedProps.tank;

    // Nếu là sự kiện đã tồn tại trên lịch (có stage_code)
    // Chỉ cho phép kéo thả nếu đó là công đoạn bảo trì (8)
    if (finished == 1 || tank == 1) {
      return false;
    }

    if (stageCode !== undefined && stageCode !== null && stageCode != 8) {
      return false;
    }

    // Cho phép đối với sự kiện bảo trì hoặc sự kiện mới từ Sidebar (chưa có stage_code)
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
        axios.put('/Schedual/submit', { submit_type: maintenanceType })

          .then(res => {
            let data = res.data;
            if (typeof data === "string") {
              data = data.replace(/^<!--.*?-->/, "").trim();
              data = JSON.parse(data);

              //setEvents(data.events);
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

      }
    })
  }

  const statusColors = {
    "Chưa làm": { backgroundColor: "white", color: "black" },
    "Đã Cân": { backgroundColor: "#e3f2fd", color: "#0d47a1" },
    "Đã PC": { backgroundColor: "#bbdefb", color: "#0d47a1" },
    "Đã THT": { backgroundColor: "#90caf9", color: "#0d47a1" },
    "Đã ĐH": { backgroundColor: "#64b5f6", color: "white" },
    "Đã BP": { backgroundColor: "#1e88e5", color: "white" },
    "Hoàn Tất": { backgroundColor: "#0d47a1", color: "white" }

  };

  const getStatusStyleString = (status) => {
    const style = statusColors[status];
    if (!style) return '';

    return `
        background-color: ${style.backgroundColor};
        color: ${style.color};
      `;
  };

  const EventContent = (arg) => {
    const event = arg.event;
    const props = event._def.extendedProps;
    const isTimelineMonth = arg.view.type === 'resourceTimelineMonth';
    const eventDate = dayjs(event.start).format('YYYY-MM-DD'); // Ngày thực hiện trên lịch

    const isTank = props.tank == 1 && props.stage_code == 8;
    const productionChangeEvent = props.keep_dry == 1 && props.stage_code == 8;

    let isLate = false;

    if (props.stage_code == 8) {
      // ƯU TIÊN: Bóc tách ngày tới hạn từ Title (Vì Tile chứa ngày gốc 'min_due' từ Backend)
      let rawDueDate = null;
      if (event.title && event.title.includes('Ngày tới hạn:')) {
        const match = event.title.match(/Ngày tới hạn:\s*([\d\/-]+)/);
        if (match) rawDueDate = match[1];
      }

      // Nếu title không có mới dùng đến props
      if (!rawDueDate) rawDueDate = props.expected_date;

      const cleanDueDate = rawDueDate ? String(rawDueDate).replace(/-$/, '').trim() : null;
      const dueDate = moment(cleanDueDate, ['YYYY-MM-DD', 'DD/MM/YYYY']);
      const planDate = moment(event.start);

      if (dueDate.isValid() && planDate.isValid()) {
        if (props.code?.includes('_HC')) {
          if (planDate.isAfter(dueDate, 'day')) isLate = true;
        } else {
          // Ngưỡng gia hạn: Monthly (7 ngày), các loại khác (21 ngày)
          const threshold = props.Inst_sch_type === "Monthly" ? 7 : 21;
          const limitDate = dueDate.clone().add(threshold, 'days');

          if (planDate.isAfter(limitDate, 'day')) isLate = true;
        }
      }
    }

    let tankStyle = '';

    if (isTank && isLate) {
      // Vừa xong vừa trễ: Tăng độ dày outline lên 6px để bao trùm nổi bật hơn
      tankStyle = 'border: 3px solid #22ff00ff; outline: 6px solid #ff0000; outline-offset: 2px; box-shadow: 0 0 15px #ff0000; border-radius: 4px; z-index: 10;';
    } else if (isTank) {
      tankStyle = 'border: 3px solid #22ff00ff; box-shadow: 0 0 8px #22ff00ff; border-radius: 4px;';
    } else if (isLate) {
      // Chỉ trễ: Tăng kích thước biên lên 6px
      tankStyle = 'border: 6px solid #ff0000ff; box-shadow: 0 0 15px #ff0000; border-radius: 4px;';
    }




    let html = `
      <div class="relative group custom-event-content" data-event-id="${event.id}" style="${tankStyle}">
        <div style="font-size:${arg.eventFontSize || 12}px; ${isTank ? 'padding: 0px;' : ''}">
          
        ${productionChangeEvent ? `
            <div 
              class="absolute top-[-15px] left-2 px-1 rounded shadow bg-[#fff3cd] text-[#856404] border border-[#ffeeba]"
              style="font-size: 10px;"
              title="Lịch bị thay đổi bởi Phân xưởng"
            >
              ⚠️ <b>${props.schedualed_by ?? ''}</b>
            </div>
          ` : ''}

        ${!props.is_clearning && props.finished == 0 ? `
            <span 
              style="
                position:absolute;
                top:2px;
                right:2px;
                display:inline-block;
                width:8px;
                height:8px;
                border-radius:50%;
                background:${props.submit ? 'green' : 'red'};
                z-index:10;
              ">
            </span>
          ` : ''}

          <b style="color: ${props.textColor};">  ${event.title} ${!props.is_clearning && showRenderBadge ? props.subtitle : ''} </b>
          ${!isTimelineMonth ? `
            <br/>
            ${arg.view.type !== 'resourceTimelineQuarter' && !props.is_clearning ?
          `<div style="color: ${props.textColor} ;" >${moment(event.start).format('HH:mm DD/MM/YY')} ➝ ${moment(event.end).format('HH:mm DD/MM/YY')}</div>`
          : ''}
          ` : ''}
      </div>
    `;

    if (!props.is_clearning && showRenderBadge && authorization) {
      html += `
              <div 
                class="absolute top-[20px] right-5 px-1 rounded shadow bg-white text-red-600"
                title="% biệt trữ"
              ><b>${props.campaign_code ?? ''}</b></div>`;
    }


    if (!props.is_clearning && showRenderBadge && props.status) {
      const style = getStatusStyleString(props.status);

      html += `
            <div 
              class="absolute top-[-20px] right-5 px-1 rounded shadow"
              style="${style}"
              title="Trạng Thái SX"
            >
              <b>${props.status ?? ''}</b>
            </div>
          `;
    }

    if (authorization && props.finished == 0 && props.tank == 0 && props.stage_code == 8) {
      html += `
        <button 
          class="edit-single-event-btn"
          data-event-id="${event.id}"
          title="Sửa nhanh"
        >
          ✏️
        </button>
      `;
    }

    html += `</div>`;
    return { html };
  };

  const buildOffDayEvents = (offDays) => {
    return offDays.map(dateStr => ({
      id: `off-${dateStr}`,
      start: `${dateStr}T06:00:00`,
      end: dayjs(dateStr).add(1, 'day').format('YYYY-MM-DD') + 'T06:00:00',
      display: 'background',
      className: 'fc-ngay-nghi'
    }));
  };

  const calendarEvents = useMemo(() => {
    return [
      ...(Array.isArray(events) ? events : []),                     // event sản xuất
      ...buildOffDayEvents(Array.isArray(offDays) ? offDays : []), // background ngày nghỉ
    ];
  }, [events, offDays]);

  // Lọc Resource dựa trên danh sách thiết bị sản xuất được chọn
  const resourceFiltered = useMemo(() => {
    const resList = Array.isArray(resources) ? resources : [];

    // 1. Ưu tiên lọc theo dòng đang chọn ở Sidebar (đang thao tác)
    if (selectedRows.length > 0) {
      const validRoomCodes = new Set();
      selectedRows.forEach(row => {
        if (row.related_rooms && Array.isArray(row.related_rooms)) {
          row.related_rooms.forEach(room => validRoomCodes.add(room.room_code));
        }
      });
      return resList.filter(res => validRoomCodes.has(res.code));
    }

    // 2. Nếu không chọn gì, kiểm tra xem có "khóa" (locked) bộ lọc từ lần sắp lịch trước không
    if (lockedResourceCodes) {
      return resList.filter(res => lockedResourceCodes.has(res.code));
    }

    // 3. Cuối cùng, hiển thị toàn bộ
    return resList;
  }, [resources, selectedRows, lockedResourceCodes]);

  // Xóa "khóa" bộ lọc khi người dùng bắt đầu chọn một công việc mới
  useEffect(() => {
    if (selectedRows.length > 0 && lockedResourceCodes) {
      setLockedResourceCodes(null);
    }
  }, [selectedRows, lockedResourceCodes]);

  const calendarWidth = useMemo(() => {
    if (!showSidebar) return '100%';
    if (percentShow === "close") return '100%';
    const isFull = percentShow === '100%' || (typeof percentShow === 'string' && percentShow.includes('calc'));
    if (isFull) return '0%';
    if (percentShow === '30%') return '70%';
    if (typeof percentShow === 'string' && percentShow.includes('px')) {
      return `calc(100% - ${percentShow})`;
    }
    return '100%';
  }, [showSidebar, percentShow]);

  useEffect(() => {
    let timer;
    if (calendarRef.current && calendarRef.current.getApi) {
      const api = calendarRef.current.getApi();
      if (api && typeof api.updateSize === 'function') {
        api.updateSize();
        // Cần thêm timeout để đảm bảo kích thước sau animation
        timer = setTimeout(() => {
          if (calendarRef.current) {
            const apiAsync = calendarRef.current.getApi();
            apiAsync?.updateSize();
          }
        }, 350);
      }
    }
    return () => clearTimeout(timer);
  }, [showSidebar, percentShow]);

  return (

    <div
      className={`calendar-wrapper transition-all duration-300 float-left pt-4 pl-2 pr-2 ${isProductionHidden ? 'hide-production-events' : ''}`}
      style={{ width: calendarWidth, overflow: 'hidden' }}
    >
      <style>{`
        .hide-production-events .production-event {
          display: none !important;
        }
      `}</style>
      <FullCalendar
        schedulerLicenseKey="GPL-My-Project-Is-Open-Source"
        ref={calendarRef}
        plugins={[dayGridPlugin, resourceTimelinePlugin, interactionPlugin]}
        initialView="resourceTimelineWeek"
        firstDay={1}
        events={calendarEvents}
        eventResourceEditable={true}
        resources={resourceFiltered}
        eventClassNames={(arg) => {
          const classes = [];
          const stageCode = arg.event.extendedProps.stage_code;
          const isCleaning = arg.event.extendedProps.is_clearning;
          const pmId = arg.event.extendedProps.plan_master_id;

          // Hiding/Showing production events & cleaning events
          if (stageCode != 8 || isCleaning) {
            classes.push('production-event');
          }

          // Active Plan Master focusing (original logic from line 1888)
          if (activePlanMasterId) {
            if (pmId === activePlanMasterId) {
              classes.push('fc-event-focus');
            } else {
              classes.push('fc-event-hidden');
            }
          }

          return classes;
        }}
        resourceAreaHeaderContent="Phòng Sản Xuất"

        locale="vi"
        resourceAreaWidth="250px"
        expandRows={false}

        editable={true}
        droppable={true}
        selectable={true}
        eventResizableFromStart={true}

        //slotDuration="01:00:00"
        eventDurationEditable={true}


        eventClick={(authorization || authorizationAccept) ? handleEventClick : false}
        eventResize={authorization ? handleEventChange : false}
        eventDrop={authorization ? (info) => handleGroupEventDrop(info, selectedEvents, toggleEventSelect, handleEventChange) : false}
        eventReceive={authorization ? handleEventReceive : false}
        dateClick={authorization ? handleClear : false}
        eventAllow={finisedEvent}

        resourceGroupField="stage_name"
        resourceOrder='order_by'


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
            const related = row.related_rooms || [];
            return related.some(r => r.room_code === res.code);
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


              <div style="display:flex; gap:12px; margin-top:4px; font-size:11px;">
                <label>
                  <input
                    type="checkbox"
                    data-room="${res.code}"
                    data-sheet="sheet_1"
                    ${res.sheet_1 == 1 ? "checked" : ""}
                  /> Ca 1
                </label>

                <label>
                  <input
                    type="checkbox"
                    data-room="${res.code}"
                    data-sheet="sheet_2"
                    ${res.sheet_2 == 1 ? "checked" : ""}
                  /> Ca 2
                </label>

                <label>
                  <input
                    type="checkbox"
                    data-room="${res.code}"
                    data-sheet="sheet_3"
                    ${res.sheet_3 == 1 ? "checked" : ""}
                  /> Ca 3
                </label>

                <label>
                  <input
                    type="checkbox"
                    data-room="${res.code}"
                    data-sheet="sheet_regular"
                    ${res.sheet_regular == 1 ? "checked" : ""}
                  /> HC
                </label>
              </div>


            </div>
          `;

          return { html };
        }}

        resourceLabelDidMount={(info) => {
          const handler = (e) => {
            const target = e.target;
            if (
              target.tagName !== "INPUT" ||
              target.type !== "checkbox" ||
              !target.dataset.room
            ) return;

            const room = target.dataset.room;
            const sheet = target.dataset.sheet;
            const checked = target.checked;

            axios.put('/MaintenanceSchedual/change_sheet', {
              room_code: room,
              sheet,
              checked
            })
              .then(res => {
                const updated = res.data.update;

                setResources(prev =>
                  (prev || []).map(r =>
                    r.code !== room
                      ? r
                      : { ...r, ...updated }
                  )
                );

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
                console.error(err);
              });
          };

          info.el.addEventListener("change", handler);

          // ✅ cleanup để tránh leak
          info.el._sheetHandler = handler;
        }}

        resourceLabelWillUnmount={(info) => {
          if (info.el._sheetHandler) {
            info.el.removeEventListener("change", info.el._sheetHandler);
          }
        }}
        //hiddenTheory 
        headerToolbar={{
          left: `customPre,myToday,customNext noteModal${authorization ? ' hiddenProduction changeSchedualer unSelect confirmFinish Submit' : ''}${authorizationAccept ? ' approveMaintenance' : ''}`,
          center: 'title',
          right: `fontSizeBox searchBox slotDuration customDay,customWeek,customMonth,customQuarter${authorization ? ' customList' : ''}` //customYear
        }}

        views={{
          resourceTimelineDay: {
            slotDuration: '00:15:00',
            slotMinTime: '00:00:00',
            slotMaxTime: '24:00:00',
            buttonText: 'Ngày',
            titleFormat: { year: 'numeric', month: 'short', day: 'numeric' },
          },
          resourceTimelineWeek: {
            slotDuration: { days: 1 },
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
          resourceTimelineWeek1h: { type: 'resourceTimelineWeek', slotDuration: '01:00:00' },
          resourceTimelineWeek4h: { type: 'resourceTimelineWeek', slotDuration: '04:00:00' },
          resourceTimelineWeek1day: { type: 'resourceTimelineWeek', slotDuration: { days: 1 } },

          resourceTimelineMonth1h: { type: 'resourceTimelineMonth', slotDuration: '01:00:00' },
          resourceTimelineMonth4h: { type: 'resourceTimelineMonth', slotDuration: '04:00:00' },
          resourceTimelineMonth1d: { type: 'resourceTimelineMonth', slotDuration: { days: 1 } },
        }}

        customButtons={{

          customNext: {
            text: '⏵',
            click: () => handleViewChange(null, 'next'),
            hint: 'Tiến tới 1 khung thời gian'
          },
          customPre: {
            text: '⏴',
            click: () => handleViewChange(null, 'prev'),
            hint: 'Lùi về 1 khung thời gian'
          },

          myToday: {
            text: 'Hiện Tại',
            click: () => handleViewChange(null, 'today'),
            hint: 'Trờ về ngày hiện tại của khung thời gian đã chọn'
          },

          customList: {
            text: 'Kế Hoạch',
            click: handleShowList,
            hint: 'Mở kế hoạch chờ sắp lịch'
          },
          customDay: {
            text: 'Ngày',
            click: () => handleViewChange('resourceTimelineDay'),
            hint: 'Thay đổi hiển thị lịch theo khung thời gian 1 ngày'

          },
          customWeek: {
            text: 'Tuần',
            click: () => handleViewChange('resourceTimelineWeek'),
            hint: 'Thay đổi hiển thị lịch theo khung thời gian 1 tuần'
          },
          customMonth: {
            text: 'Tháng',
            click: () => handleViewChange('resourceTimelineMonth'),
            hint: 'Thay đổi hiển thị lịch theo khung thời gian 1 tháng'
          },
          customQuarter: {
            text: '3 Tháng',
            click: () => handleViewChange('resourceTimelineQuarter'),
            hint: 'Thay đổi hiển thị lịch theo khung thời gian 3 tháng'
          },


          noteModal: {
            text: 'ℹ️',
            click: toggleNoteModal,
            hint: 'Ẩn/ Hiện chú thích màu của lịch'
          },
          hiddenProduction: {
            text: '🏭',
            click: toggleProductionEvents,
            hint: 'Ẩn/ Hiện lịch sản xuất'
          },

          hiddenTheory: {
            text: '🧭',
            click: toggleTheoryEvents,
            hint: 'Hiển thị lịch lý thuyết đôi với các lịch đã hoàn thành'
          },

          changeSchedualer: {
            text: '💾',
            click: handleSaveChanges,
            hint: 'Lưu thay đổi lịch: sau khi thay đổi lịch bấm 💾 hoặc Ctrl + S để lưu thay đổi'
          },
          unSelect: {
            text: '🗑️',
            click: handleDeleteScheduale,
            hint: 'Xóa lịch được chọn: Chọn các lịch cần xóa, sau đó bấm 🗑️'
          },

          searchBox: {
            text: '',
            hint: 'Thay đổi font chữ'
          },

          fontSizeBox: {
            text: '',
            hint: 'Thay đổi font chữ'
          },


          slotDuration: {
            text: 'Slot',
            click: toggleSlotDuration,
            hint: 'Tháy đổi độ chia thời gian tại khung tuần'
          },

          // ShowBadge: {
          //   text: '👁️',
          //   click: () => setShowRenderBadge(!showRenderBadge),
          //   hint: 'Xem các thông tin thêm như: lý do đổi màu lịch, mã chiến dịch'
          // },

          Submit: {
            text: '📤',
            click: handleSubmit,
            hint: 'Submit Lịch: Sau khi hoàn thành sắp lịch để các bộ phận khác có thể thấy bấm 📤'
          },

          confirmFinish: {
            text: '✅',
            click: handleConfirmFinish,
            hint: 'Xác nhận hoàn thành tạm thời các lịch bảo trì được chọn. Chọn các lịch cần hoàn thành, sau đó bấm ✅'
          },

          approveMaintenance: {
            text: '👌',
            click: handleApproveMaintenance,
            hint: 'Duyệt các sự kiện bảo trì được chọn (Tank = 1) (Shift + Quét)'
          },

        }}



        eventDidMount={(info) => {
          // Gắn data-event-id để tìm kiếm
          info.el.setAttribute("data-event-id", info.event.id);
          info.el.setAttribute("data-stage_code", info.event.extendedProps.stage_code);
          info.el.setAttribute("data-finished", info.event.extendedProps.finished);

          // Xử lý nút sửa nhanh
          const editBtn = info.el.querySelector('.edit-single-event-btn');
          if (editBtn) {
            editBtn.addEventListener('click', (e) => {
              handleEditEventClick(info.event);
            });
          }

          // cho select evetn => pendingChanges
          const isPending = pendingChanges.some(e => e.id === info.event.id);
          if (isPending) {
            info.el.style.border = '2px dashed orange';
          }

          info.el.addEventListener("dblclick", (e) => {
            e.stopPropagation();
            if (!e.ctrlKey) {
              handleEditEventClick(info.event);
              return;
            }
            const pm = info.event.extendedProps.plan_master_id;
            setActivePlanMasterId(prev =>
              prev === pm ? null : pm   // dbl click lần nữa → reset
            );
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
      {authorization && (
        <MaintenanceSidebar
          visible={showSidebar}
          onClose={setShowSidebar}
          waitPlan={plan}
          setPlan={setPlan}
          percentShow={percentShow}
          setPercentShow={setPercentShow}
          selectedRows={selectedRows}
          setSelectedRows={setSelectedRows}
          resources={resources}
          currentPassword={currentPassword}
          userID={userID}
          userGroup={userGroup}
          userGroupName={userGroupName}
          production={production}
          userDepartment={userDepartment}
          isMaintenance={true}
          maintenanceType={maintenanceType}
          setMaintenanceType={setMaintenanceType}
          onAutoSchedule={handleAutoSchedule}
          onCancelSchedule={handleCancelSchedule}
        />)}

      {/* Selecto cho phép quét chọn nhiều .fc-event */}
      {authorization && (
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
            const newlySelected = e.selected
              .filter(el =>
                el.getAttribute("data-finished") != "1" &&
                el.getAttribute("data-stage_code") == "8"
              ) // Không chọn lịch đã hoàn thành và chỉ chọn lịch bảo trì
              .map((el) => ({
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

              return merged;
            });
          }}
        />
      )}
    </div>


  );
};

export default MaintenanceCalender;

