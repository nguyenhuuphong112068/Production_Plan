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
import { OverlayPanel } from 'primereact/overlaypanel';
import { MultiSelect } from 'primereact/multiselect';
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Dialog } from 'primereact/dialog';
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
  const previewTargetRef = useRef(null); // Lưu sự kiện mục tiêu của Xem trước chuỗi
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
  const slotViewWeeks = ['resourceTimelineWeek1day', 'resourceTimelineWeek4h', 'resourceTimelineWeek1h', 'resourceTimelineWeek15'];
  const slotViewMonths = ['resourceTimelineMonth1d', 'resourceTimelineMonth4h', 'resourceTimelineMonth1h',];
  const slotViewQuarters = ['resourceTimelineQuarter1d', 'resourceTimelineQuarter4h'];
  const [slotIndex, setSlotIndex] = useState(0);
  const [eventFontSize, setEventFontSize] = useState(22); // default 14px
  const [selectedRows, setSelectedRows] = useState([]);
  const [showNoteModal, setShowNoteModal] = useState(false);
  const [viewName, setViewName] = useState("resourceTimelineWeek");
  const [showRenderBadge, setShowRenderBadge] = useState(false);
  const [workingSunday, setWorkingSunday] = useState(false);
  const [offDays, setOffDays] = useState([]);
  const [multiStage, setMultiStage] = useState(false);

  const [events, setEvents] = useState([]);
  const [moldWarningIndex, setMoldWarningIndex] = useState(-1);
  const moldWarningEvents = useMemo(() => events.filter(e => e.mold_warning), [events]);
  const [resources, setResources] = useState([]);

  const [selectedStagesFilter, setSelectedStagesFilter] = useState(null);
  const stageFilterOptions = useMemo(() => {
    const uniqueStages = [...new Set((resources || []).map(r => r.stage_name).filter(Boolean))];
    return uniqueStages.map(s => ({ label: s, value: s }));
  }, [resources]);

  const [selectedRoomsFilter, setSelectedRoomsFilter] = useState(null);
  const roomFilterOptions = useMemo(() => {
    let filteredResources = resources || [];
    if (selectedStagesFilter && selectedStagesFilter.length > 0) {
      filteredResources = filteredResources.filter(r => selectedStagesFilter.includes(r.stage_name));
    }
    const uniqueRooms = [...new Set(filteredResources.map(r => r.title).filter(Boolean))];
    return uniqueRooms.map(s => ({ label: s, value: s }));
  }, [resources, selectedStagesFilter]);
  const [personnelEvents, setPersonnelEvents] = useState([]);
  const [showPersonnel, setShowPersonnel] = useState(false);
  const [historyData, setHistoryData] = useState([]);
  const hoverTimeoutRef = useRef(null);
  const [loadingHistory, setLoadingHistory] = useState(false);
  const [showHistoryHover, setShowHistoryHover] = useState(false);
  const [showHistoryDialog, setShowHistoryDialog] = useState(false);

  const [showDetailHover, setShowDetailHover] = useState(false);
  const [hoverDetailData, setHoverDetailData] = useState(null);
  const detailHoverTimeoutRef = useRef(null);
  const [sumBatchByStage, setSumBatchByStage] = useState([]);
  const [plan, setPlan] = useState([]);
  const [quota, setQuota] = useState([]);
  const [stageMap, setStageMap] = useState({});
  const [contextMenuInfo, setContextMenuInfo] = useState({ visible: false, x: 0, y: 0, event: null });
  const [type, setType] = useState(true);
  const [isCleaningHidden, setIsCleaningHidden] = useState(() => {
    return JSON.parse(sessionStorage.getItem('cleaningHidden')) || false;
  });
  const [loading, setLoading] = useState(false);
  const [authorization, setAuthorization] = useState(false);
  const [heightResource, setHeightResource] = useState("1px");
  const [reasons, setReasons] = useState([]);
  const [bkcCode, setBkcCode] = useState([]);
  const [lines, setLines] = useState(['S16']);
  const [allLines, setAllLines] = useState([]);
  const [currentPassword, setCurrentPassword] = useState(null);
  const [userID, setUserID] = useState(null);
  const [production, setProduction] = useState(null);
  const [isCascadeMode, setIsCascadeMode] = useState(() => {
    return JSON.parse(sessionStorage.getItem('cascadeMode')) || false;
  });
  const [blackViolationCount, setBlackViolationCount] = useState(0);


  const [activePlanMasterIds, setActivePlanMasterIds] = useState([]); // Mảng để chứa nhiều mã lọc cùng lúc
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
    const handleClick = () => {
      if (contextMenuInfo.visible) {
        setContextMenuInfo({ ...contextMenuInfo, visible: false });
      }
    };
    window.addEventListener('click', handleClick);
    return () => window.removeEventListener('click', handleClick);
  }, [contextMenuInfo]);

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
          //data = JSON.parse(data);
        }


        setAuthorization(['Admin', 'Schedualer'].includes(data.authorization) && data.production == data.department)

        if (data.department == 'BOD') {
          setAuthorization(true);
        }

        setEvents(data.events);
        setResources(data.resources);
        if (data.personnel_events) {
          setPersonnelEvents(data.personnel_events);
        }
        setType(data.type)
        setStageMap(data.stageMap);
        setSumBatchByStage(data.sumBatchByStage);
        setReasons(data.reason)
        setLines(data.Lines)
        setAllLines(data.allLines)
        sessionStorage.setItem('theoryHidden', 0);

        if (!authorization) {
          setPlan(data.plan);
          setCurrentPassword(data.currentPassword ?? '')
          setQuota(data.quota);
          setOffDays(data.off_days);
          setBkcCode(data.bkc_code);
          setUserID(data.UesrID);
          setProduction(data.production);

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
        // console.error("API error:", err)
      });

  }, [loading]);

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

  // Tự động cuộn đến Resource liên quan khi chọn dòng ở Sidebar (Chỉ dành cho Lịch Sản Xuất)
  useEffect(() => {
    if (selectedRows.length > 0) {
      const firstRow = selectedRows[0];
      const perms = firstRow.permisson_room;
      let targetResourceId = null;

      if (perms) {
        if (Array.isArray(perms) && perms.length > 0) {
          // Lấy ID từ mã code phòng (cần tìm trong resources)
          const target = resources.find(r => r.code === perms[0]);
          if (target) targetResourceId = target.id;
        } else if (typeof perms === "object") {
          targetResourceId = Object.keys(perms)[0];
        } else {
          targetResourceId = String(perms);
        }
      }

      if (targetResourceId) {
        setTimeout(() => {
          const api = calendarRef.current?.getApi();
          if (api?.view?.scrollToResource) {
            api.view.scrollToResource(targetResourceId);
          } else {
            const el = document.querySelector(`[data-resource-id="${targetResourceId}"]`);
            if (el) {
              el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
          }
        }, 150);
      }
    }
  }, [selectedRows, resources]);

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

  useEffect(() => {
    if (selectedRows.length > 0) {
      const firstRow = selectedRows[0];
      const permissions = firstRow.permisson_room || {};
      let firstRoomCode = null;

      // Xử lý cả trường hợp mảng hoặc object định mức
      if (Array.isArray(permissions)) {
        firstRoomCode = permissions[0];
      } else if (typeof permissions === "object" && Object.values(permissions).length > 0) {
        firstRoomCode = Object.values(permissions)[0];
      }

      if (firstRoomCode) {
        const resource = resources.find(r => r.code === firstRoomCode);
        if (resource) {
          setTimeout(() => {
            const api = calendarRef.current?.getApi();
            // Ưu tiên dùng API nội bộ của FullCalendar
            if (api?.view?.scrollToResource) {
              api.view.scrollToResource(resource.id);
            } else {
              // Fallback DOM cuộn an toàn không mất header
              const el = document.querySelector(`[data-resource-id="${resource.id}"]`);
              if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
              }
            }
          }, 100);
        }
      }
    }
  }, [selectedRows, resources]);

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

  const handleNextMoldWarning = () => {
    if (moldWarningEvents.length === 0) return;

    // Lấy danh sách resourceId của các event trùng khuôn
    const resourceIds = [...new Set(moldWarningEvents.map(e => String(e.resourceId)))];

    // Tìm các resource tương ứng
    const affectedResources = resources.filter(r => resourceIds.includes(String(r.id)));

    // Lấy tên phòng (stage_name) và tên thiết bị (title)
    const affectedStages = [...new Set(affectedResources.map(r => r.stage_name).filter(Boolean))];
    const affectedTitles = [...new Set(affectedResources.map(r => r.title).filter(Boolean))];

    // Cập nhật filter
    setSelectedStagesFilter(affectedStages.length > 0 ? affectedStages : null);
    setSelectedRoomsFilter(affectedTitles.length > 0 ? affectedTitles : null);

    // Chuyển tới index tiếp theo
    let nextIndex = moldWarningIndex + 1;
    if (nextIndex >= moldWarningEvents.length) {
      nextIndex = 0;
    }
    setMoldWarningIndex(nextIndex);

    const ev = moldWarningEvents[nextIndex];
    if (ev) {
      setTimeout(() => {
        const api = calendarRef.current?.getApi();
        if (api?.view?.scrollToResource) {
          api.view.scrollToResource(ev.resourceId);
        }
        clearHighlights();
        setTimeout(() => {
          const el = document.querySelector(`[data-event-id="${ev.id}"]`);
          if (el) {
            el.classList.add("highlight-current-event");
            scrollToEvent(el);
          }
        }, 300);
      }, 100);
    }
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
      const theoryHidden = JSON.parse(sessionStorage.getItem('theoryHidden'));

      // 🔹 4. Gọi API backend
      const { data } = await axios.post(`/Schedual/view`, {
        startDate: toLocalISOString(activeStart),
        endDate: toLocalISOString(activeEnd),
        viewtype: currentView,
        clearning: true,
        theory: theoryHidden,
      });

      let cleanData = data;
      if (typeof cleanData === "string") {
        cleanData = JSON.parse(cleanData.replace(/^<!--.*?-->/, "").trim());
      }
      // 🔹 5. Cập nhật dữ liệu mới
      setEvents(cleanData.events);
      setResources(cleanData.resources);
      if (cleanData.personnel_events) {
        setPersonnelEvents(cleanData.personnel_events);
      }
      setSumBatchByStage(cleanData.sumBatchByStage);
      setViewName(viewType);

      return cleanData;
    } finally {

    }
  }, []);

  const toggleCleaningEvents = () => {
    const newHidden = !isCleaningHidden;
    setIsCleaningHidden(newHidden);
    sessionStorage.setItem('cleaningHidden', JSON.stringify(newHidden));
    // No handleViewChange call needed anymore for this toggle
  };

  const toggleHistoryHover = () => {
    const newState = !showHistoryHover;
    setShowHistoryHover(newState);
    sessionStorage.setItem('showHistoryHover', JSON.stringify(newState));
    if (!newState) {
      setShowHistoryDialog(false);
    }
  };

  // Cập nhật màu nền nút historyToggle khi showHistoryHover thay đổi
  useEffect(() => {
    const btn = document.querySelector('.fc-historyToggle-button');
    if (!btn) return;
    if (showHistoryHover) {
      btn.style.backgroundColor = '#51f50bff'; // amber/vàng khi bật
      btn.style.borderColor = '#51f50bff';
      btn.style.color = '#fff';
    } else {
      btn.style.backgroundColor = '';
      btn.style.borderColor = '';
      btn.style.color = '';
    }
  }, [showHistoryHover]);

  // Cập nhật màu nền nút cascadeToggle khi isCascadeMode thay đổi
  useEffect(() => {
    const btn = document.querySelector('.fc-cascadeToggle-button');
    if (!btn) return;
    if (isCascadeMode) {
      btn.style.backgroundColor = '#51f50bff';
      btn.style.borderColor = '#51f50bff';
      btn.style.color = '#fff';
    } else {
      btn.style.backgroundColor = '';
      btn.style.borderColor = '';
      btn.style.color = '';
    }
  }, [isCascadeMode]);

  // Cập nhật màu nền nút hiddenClearning khi isCleaningHidden thay đổi
  useEffect(() => {
    const btn = document.querySelector('.fc-hiddenClearning-button');
    if (!btn) return;
    if (isCleaningHidden) {
      btn.style.backgroundColor = '#51f50bff';
      btn.style.borderColor = '#51f50bff';
      btn.style.color = '#fff';
    } else {
      btn.style.backgroundColor = '';
      btn.style.borderColor = '';
      btn.style.color = '';
    }
  }, [isCleaningHidden]);

  // Cập nhật màu nền nút ShowBadge khi showRenderBadge thay đổi
  useEffect(() => {
    const btn = document.querySelector('.fc-ShowBadge-button');
    if (!btn) return;
    if (showRenderBadge) {
      btn.style.backgroundColor = '#51f50bff';
      btn.style.borderColor = '#51f50bff';
      btn.style.color = '#fff';
    } else {
      btn.style.backgroundColor = '';
      btn.style.borderColor = '';
      btn.style.color = '';
    }
  }, [showRenderBadge]);

  // Cập nhật màu nền nút detailToggle khi showDetailHover thay đổi
  useEffect(() => {
    const btn = document.querySelector('.fc-detailToggle-button');
    if (!btn) return;
    if (showDetailHover) {
      btn.style.backgroundColor = '#51f50bff';
      btn.style.borderColor = '#51f50bff';
      btn.style.color = '#fff';
    } else {
      btn.style.backgroundColor = '';
      btn.style.borderColor = '';
      btn.style.color = '';
    }
  }, [showDetailHover]);

  // const toggleTheoryEvents = () => {

  //   const current = JSON.parse(sessionStorage.getItem('theoryHidden'));
  //   const newTheory = !current;
  //   sessionStorage.setItem('theoryHidden', JSON.stringify(newTheory));
  //   handleViewChange(null, null);
  // };

  const toggleTheoryEvents = () => {
    const current = Number(sessionStorage.getItem('theoryHidden')) || 0;
    const next = (current + 1) % 3; // 0 → 1 → 2 → 0

    sessionStorage.setItem('theoryHidden', next);

    handleViewChange(null, null);
  };

  const toggleCascadeMode = () => {
    const newState = !isCascadeMode;
    setIsCascadeMode(newState);
    sessionStorage.setItem('cascadeMode', JSON.stringify(newState));
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
    const slotDuration = calendarRef.current?.getApi().currentData.options.slotDuration['days'];

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


    const { activeStart, activeEnd, type: view_type, props: viewProps } = calendarRef.current?.getApi().view;

    if (selectedRows[0].stage_code !== 8) {

      axios.put('/Schedual/store', {
        room_id: resourceId,
        stage_code: selectedRows[0].stage_codes,
        start: moment(start).format("YYYY-MM-DD HH:mm:ss"),
        products: selectedRows,
        startDate: toLocalISOString(activeStart),
        endDate: toLocalISOString(activeEnd),
        offdate: offDays,
        multiStage: multiStage,
        slotDuration: slotDuration
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
          setPlan(data.plan);


          setSelectedRows([]);
        })
        .catch(err => {
          // Lỗi tạo lịch
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
          if (data.resources) setResources(data.resources);
          setSumBatchByStage(data.sumBatchByStage);
          setPlan(data.plan);

          setSelectedRows([]);
        })
        .catch(err => {
          // Lỗi tạo lịch bảo trì
        });
    }
  };

  const timeToMilliseconds = (time) => {
    if (typeof time === 'number') {
      return time * 3600 * 1000;
    }
    if (typeof time !== 'string' || !time.includes(':')) {
      return 0;
    }
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

  const findNextAvailableSlot = (machineId, durationMs, earliestStart, allEvents, offRanges, ignoreIds = [], updatedTimesById = {}) => {
    let currentStart = new Date(earliestStart);
    currentStart = skipOffDays(currentStart, offRanges);

    const machineEvents = allEvents.filter(e => {
      if (e.getResources && e.getResources()[0]?.id !== machineId && e.resourceId !== machineId) return false;
      if (ignoreIds.includes(e.id)) return false;

      let eEnd = new Date(e.end);
      if (updatedTimesById[e.id]) {
        eEnd = new Date(updatedTimesById[e.id].end);
      }
      return eEnd > currentStart;
    }).map(e => {
      let start = new Date(e.start);
      let end = new Date(e.end);
      if (updatedTimesById[e.id]) {
        start = new Date(updatedTimesById[e.id].start);
        end = new Date(updatedTimesById[e.id].end);
      }
      return { start, end };
    }).sort((a, b) => a.start - b.start);

    for (let i = 0; i < machineEvents.length; i++) {
      const ev = machineEvents[i];
      const evStart = ev.start;
      const evEnd = ev.end;

      let potentialEnd = new Date(currentStart.getTime() + durationMs);
      potentialEnd = skipOffDays(potentialEnd, offRanges);

      let remainingMs = durationMs;
      let tempStart = new Date(currentStart);
      while (remainingMs > 0) {
        let tempEnd = new Date(tempStart.getTime() + remainingMs);
        let crossedOff = offRanges.find(off => off.end > tempStart && off.start < tempEnd);
        if (crossedOff) {
          let chunk = crossedOff.start.getTime() - tempStart.getTime();
          if (chunk > 0) remainingMs -= chunk;
          tempStart = new Date(crossedOff.end);
        } else {
          tempStart = tempEnd;
          remainingMs = 0;
        }
      }
      potentialEnd = tempStart;

      if (potentialEnd <= evStart) {
        return { start: currentStart, end: potentialEnd };
      }

      if (evEnd > currentStart) {
        currentStart = new Date(evEnd);
        currentStart = skipOffDays(currentStart, offRanges);
      }
    }

    let remainingMs = durationMs;
    let tempStart = new Date(currentStart);
    while (remainingMs > 0) {
      let tempEnd = new Date(tempStart.getTime() + remainingMs);
      let crossedOff = offRanges.find(off => off.end > tempStart && off.start < tempEnd);
      if (crossedOff) {
        let chunk = crossedOff.start.getTime() - tempStart.getTime();
        if (chunk > 0) remainingMs -= chunk;
        tempStart = new Date(crossedOff.end);
      } else {
        tempStart = tempEnd;
        remainingMs = 0;
      }
    }
    return { start: currentStart, end: tempStart };
  };

  const skipOffDaysBackward = (date, offRanges) => {
    let newDate = new Date(date);
    let crossed = true;
    while (crossed) {
      crossed = false;
      for (let off of offRanges) {
        if (newDate > off.start && newDate <= off.end) {
          newDate = new Date(off.start);
          crossed = true;
          break;
        }
      }
    }
    return newDate;
  };

  const findPreviousAvailableSlot = (machineId, durationMs, latestEnd, allEvents, offRanges, ignoreIds = [], updatedTimesById = {}) => {
    let currentEnd = new Date(latestEnd);
    currentEnd = skipOffDaysBackward(currentEnd, offRanges);

    const machineEvents = allEvents.filter(e => {
      if (e.getResources && String(e.getResources()[0]?.id) !== String(machineId) && String(e.resourceId) !== String(machineId)) return false;
      if (ignoreIds.includes(e.id)) return false;

      let eStart = new Date(e.start);
      if (updatedTimesById[e.id]) {
        eStart = new Date(updatedTimesById[e.id].start);
      }
      return eStart < currentEnd;
    }).map(e => {
      let start = new Date(e.start);
      let end = new Date(e.end);
      if (updatedTimesById[e.id]) {
        start = new Date(updatedTimesById[e.id].start);
        end = new Date(updatedTimesById[e.id].end);
      }
      return { start, end };
    }).sort((a, b) => b.end - a.end); // Giảm dần theo thời gian kết thúc

    for (let i = 0; i < machineEvents.length; i++) {
      const ev = machineEvents[i];
      const evStart = ev.start;
      const evEnd = ev.end;

      let remainingMs = durationMs;
      let tempEnd = new Date(currentEnd);
      while (remainingMs > 0) {
        let tempStart = new Date(tempEnd.getTime() - remainingMs);
        let crossedOff = offRanges.find(off => off.start < tempEnd && off.end > tempStart);
        if (crossedOff) {
          let chunk = tempEnd.getTime() - crossedOff.end.getTime();
          if (chunk > 0) remainingMs -= chunk;
          tempEnd = new Date(crossedOff.start);
        } else {
          tempEnd = tempStart;
          remainingMs = 0;
        }
      }
      let potentialStart = tempEnd;

      if (potentialStart >= evEnd) {
        return { start: potentialStart, end: currentEnd };
      }

      if (evStart < currentEnd) {
        currentEnd = new Date(evStart);
        currentEnd = skipOffDaysBackward(currentEnd, offRanges);
      }
    }

    let remainingMs = durationMs;
    let tempEnd = new Date(currentEnd);
    while (remainingMs > 0) {
      let tempStart = new Date(tempEnd.getTime() - remainingMs);
      let crossedOff = offRanges.find(off => off.start < tempEnd && off.end > tempStart);
      if (crossedOff) {
        let chunk = tempEnd.getTime() - crossedOff.end.getTime();
        if (chunk > 0) remainingMs -= chunk;
        tempEnd = new Date(crossedOff.start);
      } else {
        tempEnd = tempStart;
        remainingMs = 0;
      }
    }

    return { start: tempEnd, end: currentEnd };
  };

  const handlePreviewChain = async (targetEvent) => {
    const calendarApi = calendarRef.current.getApi();
    const allEvents = calendarApi.getEvents();

    let isSelected = selectedEvents.some(e => String(e.id) === String(targetEvent.id));
    let anchors = isSelected && selectedEvents.length > 0
      ? selectedEvents.map(e => allEvents.find(ev => String(ev.id) === String(e.id))).filter(Boolean)
      : [targetEvent];

    if (anchors.length === 0) return;

    let updates = [];
    let updatedTimesById = {};

    const pmIds = anchors.map(a => String(a.extendedProps.plan_master_id));
    window.previewPlanMasterIds = pmIds; // Lưu vào biến toàn cục để eventContent đọc và tô bóng
    previewTargetRef.current = targetEvent; // Lưu target để scrollToSelectedEvent dùng

    let targetStart = new Date(targetEvent.start);
    let viewStart = !isNaN(targetStart.getTime())
      ? new Date(new Date(targetStart).setMonth(targetStart.getMonth() - 1))
      : new Date();

    // gotoDate TRƯỚC khi handleViewChange để FC tính activeStart/activeEnd đúng range
    calendarRef.current?.getApi().gotoDate(viewStart);

    // Fetch dữ liệu 3 tháng MỚI NHẤT
    let cleanData = await handleViewChange('resourceTimelineQuarter4h');

    if (cleanData && cleanData.events && cleanData.resources) {
      // Tìm TẤT CẢ các event liên quan trong mảng data mới fetch (bao phủ cả 3 tháng và mọi phòng)
      let relatedEvents = cleanData.events.filter(e => pmIds.includes(String(e.plan_master_id)));

      // Trích xuất ID phòng từ dữ liệu gốc
      const relatedResourceIds = [...new Set(relatedEvents.map(e => String(e.resourceId)).filter(id => id !== "undefined" && id !== "null"))];

      // Tìm tên phòng tương ứng để set filter
      const filteredTitles = cleanData.resources.filter(r => relatedResourceIds.includes(String(r.id))).map(r => r.title);

      if (filteredTitles.length > 0) {
        setSelectedRoomsFilter(filteredTitles);
      }
    }
  };

  const scrollToSpecificEvent = (eventId, targetStart) => {
    const calendarApi = calendarRef.current?.getApi();
    if (!calendarApi || !eventId) return;

    if (targetStart) {
      const { activeStart, activeEnd } = calendarApi.view;
      const evDate = new Date(targetStart);
      if (evDate < activeStart || evDate >= activeEnd) {
        calendarApi.gotoDate(evDate);
      }
    }

    const doScroll = () => {
      const eventEl = document.querySelector(`[data-event-id="${eventId}"]`);
      if (!eventEl) return false;

      const scrollerEl = eventEl.closest('.fc-scroller');
      if (!scrollerEl) return false;

      const eventRect = eventEl.getBoundingClientRect();
      const scrollerRect = scrollerEl.getBoundingClientRect();

      // Cuộn ngang
      const currentScrollLeft = scrollerEl.scrollLeft;
      const newScrollLeft = currentScrollLeft + (eventRect.left - scrollerRect.left) - (scrollerRect.width / 2) + (eventRect.width / 2);

      // Cuộn dọc
      const currentScrollTop = scrollerEl.scrollTop;
      const newScrollTop = currentScrollTop + (eventRect.top - scrollerRect.top) - (scrollerRect.height / 2) + (eventRect.height / 2);

      scrollerEl.scrollTo({ left: newScrollLeft, top: newScrollTop, behavior: 'smooth' });
      return true;
    };

    setTimeout(() => {
      if (!doScroll()) setTimeout(doScroll, 600);
    }, 150);
  };

  // Cuộn lịch đến sự kiện đang được chọn đầu tiên trong selectedEvents
  const scrollToSelectedEvent = () => {
    const targetId = previewTargetRef.current?.id ?? selectedEvents[0]?.id;
    const targetStart = previewTargetRef.current?.start ?? selectedEvents[0]?.start;
    scrollToSpecificEvent(targetId, targetStart);
  };

  // Cuộn lịch đến sự kiện đầu tiên có lỗi đen
  const scrollToFirstBlackViolation = () => {
    const calendarApi = calendarRef.current?.getApi();
    if (!calendarApi) return;
    const allEvents = calendarApi.getEvents();
    const firstViolation = allEvents.find(e => (e.backgroundColor && e.backgroundColor.toLowerCase() === '#4d4b4bff') || (e.extendedProps?.violation_colors?.includes('#4d4b4bff')));
    if (firstViolation) {
      scrollToSpecificEvent(firstViolation.id, firstViolation.start);
    } else {
      Swal.fire("Thông báo", "Không tìm thấy sự kiện nào có lỗi chuỗi trên lịch hiện tại.", "info");
    }
  };

  const handleSmartRippleShift = async (targetEvent) => {
    Swal.fire({
      title: "Đang xử lý...",
      html: "Vui lòng đợi trong khi hệ thống điều chỉnh chuỗi.",
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    // Cho phép trình duyệt render UI loading trước khi xử lý nặng
    await new Promise(resolve => setTimeout(resolve, 50));

    const calendarApi = calendarRef.current.getApi();
    const allEvents = calendarApi.getEvents();

    let isSelected = selectedEvents.some(e => String(e.id) === String(targetEvent.id));
    let anchors = isSelected && selectedEvents.length > 0
      ? selectedEvents.map(e => allEvents.find(ev => String(ev.id) === String(e.id))).filter(Boolean)
      : [targetEvent];

    if (anchors.length === 0) return;


    const offRanges = offDays.map(d => {
      const start = new Date(`${d}T06:00:00`);
      const end = new Date(start.getTime() + 24 * 60 * 60 * 1000);
      return { start, end };
    }).sort((a, b) => a.start - b.start);

    let updates = [];
    let updatedTimesById = {};

    // Tập hợp ID của các anchor (cố định, không được di chuyển)
    const anchorIds = new Set(anchors.map(a => String(a.id)));

    // Đăng ký anchor vào updatedTimesById để các bước sau không dịch chuyển chúng
    anchors.forEach(a => {
      updatedTimesById[String(a.id)] = { start: new Date(a.start), end: new Date(a.end) };
      let anchorCleaning = allEvents.find(e => String(e.id) === String(a.id).replace('-main', '-cleaning'));
      if (anchorCleaning) {
        updatedTimesById[String(anchorCleaning.id)] = { start: new Date(anchorCleaning.start), end: new Date(anchorCleaning.end) };
      }
    });

    // ==================================================================
    // BƯỚC 1A: Tìm successor bị lỗi đen (anchor kết thúc sau khi successor bắt đầu)
    // → Đẩy successor về tương lai (FORWARD CASCADE)
    // ==================================================================
    const forwardViolationIds = new Set(); // Successors cần đẩy về tương lai

    anchors.forEach(anchor => {
      const anchorCode = anchor.extendedProps.code;
      const anchorEnd = new Date(anchor.end);
      if (!anchorCode) return;

      allEvents.forEach(e => {
        if (String(e.id).endsWith('-cleaning') || anchorIds.has(String(e.id))) return;
        if (String(e.extendedProps.predecessor_code) === String(anchorCode)) {
          if (new Date(e.start) < anchorEnd) {
            forwardViolationIds.add(String(e.id));
          }
        }
      });
    });

    // ==================================================================
    // BƯỚC 1B: Tìm predecessor bị lỗi đen (anchor bắt đầu trước predecessor kết thúc)
    // → Kéo predecessor về quá khứ (BACKWARD CASCADE)
    // ==================================================================
    const backwardViolationIds = new Set(); // Predecessors cần kéo về quá khứ

    anchors.forEach(anchor => {
      const anchorStart = new Date(anchor.start);
      const predCode = anchor.extendedProps.predecessor_code;
      if (!predCode) return;

      const predEv = allEvents.find(e =>
        String(e.extendedProps.code) === String(predCode) &&
        !String(e.id).endsWith('-cleaning')
      );
      if (predEv && new Date(predEv.end) > anchorStart) {
        backwardViolationIds.add(String(predEv.id));
      }
    });

    const violationIds = forwardViolationIds; // Dùng cho forward cascade phía dưới

    if (forwardViolationIds.size === 0 && backwardViolationIds.size === 0) {
      Swal.fire("Thông báo", "Không tìm thấy lỗi đen cần xử lý. Các công đoạn kế tiếp đã bắt đầu sau khi các sự kiện được chọn kết thúc.", "info");
      return;
    }

    // ==================================================================
    // BƯỚC 1C: Xử lý BACKWARD CASCADE nếu có
    // Kéo predecessor về sớm hơn để kết thúc trước khi anchor bắt đầu
    // ==================================================================
    if (backwardViolationIds.size > 0) {
      const buildBackwardChain = (startId) => {
        const chain = [];
        const visited = new Set();
        let queue = [startId];
        while (queue.length > 0) {
          const currentId = queue.shift();
          if (visited.has(currentId) || anchorIds.has(currentId)) continue;
          visited.add(currentId);
          const ev = allEvents.find(e => String(e.id) === currentId && !String(e.id).endsWith('-cleaning'));
          if (!ev) continue;
          chain.push(ev);
          // Đệ quy: tìm predecessor của predecessor
          const pCode = ev.extendedProps.predecessor_code;
          if (pCode) {
            const pEv = allEvents.find(e =>
              String(e.extendedProps.code) === String(pCode) &&
              !String(e.id).endsWith('-cleaning') &&
              !anchorIds.has(String(e.id)) &&
              !visited.has(String(e.id))
            );
            if (pEv) queue.push(String(pEv.id));
          }
        }
        // Sắp xếp theo stage_code GIẢM DẦN để xử lý từ gần anchor nhất ra ngoài
        return chain.sort((a, b) => (b.extendedProps.stage_code || 0) - (a.extendedProps.stage_code || 0));
      };

      const backwardChain = new Map();
      backwardViolationIds.forEach(vid => {
        buildBackwardChain(vid).forEach(ev => backwardChain.set(String(ev.id), ev));
      });

      const backwardSorted = [...backwardChain.values()]
        .sort((a, b) => (b.extendedProps.stage_code || 0) - (a.extendedProps.stage_code || 0));

      backwardSorted.forEach(ev => {
        const evId = String(ev.id);
        const cleaningEv = allEvents.find(e => String(e.id) === evId.replace('-main', '-cleaning'));

        // Xác định thời điểm cần kết thúc: trước khi successor của ev bắt đầu
        // Successor là anchor (hoặc ev trong backwardViolationIds có successor là anchor)
        let latestEnd = null;
        const myCode = ev.extendedProps.code;
        if (myCode) {
          // Tìm successor của ev (event có predecessor_code = myCode)
          const successorEv = allEvents.find(e =>
            String(e.extendedProps.predecessor_code) === String(myCode) &&
            !String(e.id).endsWith('-cleaning')
          );
          if (successorEv) {
            // latestEnd = thời điểm successor bắt đầu (đã có anchor lock)
            latestEnd = updatedTimesById[String(successorEv.id)]?.start || new Date(successorEv.start);
          }
        }

        if (!latestEnd) return;

        // Kiểm tra nếu đã hợp lệ → giữ nguyên
        const currentEnd = updatedTimesById[evId]?.end || new Date(ev.end);
        if (currentEnd <= latestEnd) {
          updatedTimesById[evId] = {
            start: updatedTimesById[evId]?.start || new Date(ev.start),
            end: currentEnd
          };
          if (cleaningEv) {
            const cId = String(cleaningEv.id);
            if (!updatedTimesById[cId]) updatedTimesById[cId] = { start: new Date(cleaningEv.start), end: new Date(cleaningEv.end) };
          }
          return;
        }

        const duration = new Date(ev.end).getTime() - new Date(ev.start).getTime();
        const resourceId = ev.getResources()[0]?.id;

        // Bỏ qua các backward chain events chưa xử lý
        const backwardChainIds = [...backwardChain.keys()];
        const unprocessedBackward = backwardChainIds.filter(id =>
          id !== evId && !updatedTimesById[id]
        );
        const ignoreBackIds = [...anchorIds, ...unprocessedBackward, evId, cleaningEv ? String(cleaningEv.id) : null].filter(Boolean);

        // Tính cleaning duration nếu có (cleaning phải đặt SAU main event)
        const cleaningDur = cleaningEv
          ? (new Date(cleaningEv.end).getTime() - new Date(cleaningEv.start).getTime())
          : 0;

        // Tìm slot KẾT THÚC TRƯỚC (latestEnd - cleaningDur) để vừa main + cleaning
        const adjustedLatestEnd = new Date(latestEnd.getTime() - cleaningDur);
        const newSlot = findPreviousAvailableSlot(resourceId, duration, adjustedLatestEnd, allEvents, offRanges, ignoreBackIds, updatedTimesById);

        updatedTimesById[evId] = { start: newSlot.start, end: newSlot.end };
        updates.push({ id: evId, start: newSlot.start, end: newSlot.end, resourceId, clearWarnings: true });

        if (cleaningEv) {
          // Cleaning event đặt SAU sự kiện chính
          const cId = String(cleaningEv.id);
          const cStart = newSlot.end;
          const cEnd = new Date(cStart.getTime() + cleaningDur);
          updatedTimesById[cId] = { start: cStart, end: cEnd };
          updates.push({ id: cId, start: cStart, end: cEnd, resourceId, clearWarnings: true });
        }
      });

      // Nếu chỉ có backward violations → kết thúc ở đây
      if (forwardViolationIds.size === 0) {
        if (updates.length > 0) {
          let newPending = [...pendingChanges];
          calendarApi.batchRendering(() => {
            updates.forEach(u => {
              const ev = calendarApi.getEventById(u.id);
              if (ev) {
                if (u.start && u.end) ev.setDates(u.start, u.end);
                if (u.clearWarnings) {
                  ev.setExtendedProp('warning_text', '');
                  ev.setExtendedProp('violation_colors', []);
                }
              }
              if (u.start && u.end && ev) {
                const changeObj = {
                  id: u.id, start: u.start, end: u.end,
                  resourceId: u.resourceId || (ev.getResources ? ev.getResources()[0]?.id : ev.resourceId),
                  title: ev.title, submit: ev.extendedProps?.submit, C_end: ev.extendedProps?.C_end || false
                };
                const existIdx = newPending.findIndex(p => String(p.id) === String(u.id));
                if (existIdx >= 0) newPending[existIdx] = { ...newPending[existIdx], ...changeObj };
                else newPending.push(changeObj);
              }
            });
          });
          setPendingChanges(newPending);
          Swal.fire("Thành công", `Đã điều chỉnh ${updates.filter(u => u.start).length} sự kiện.`, "success");
        } else {
          Swal.fire("Thông báo", "Không có sự kiện nào cần điều chỉnh.", "info");
        }
        return;
      }
    }


    // ==================================================================
    // BƯỚC 2: Xây dựng chuỗi cascade từ violation event trở đi
    // Chỉ đi theo liên kết predecessor_code trực tiếp, không lấy toàn bộ plan_master_id
    // ==================================================================
    const buildChain = (startId) => {
      const chain = [];
      const visited = new Set();
      let queue = [startId];

      while (queue.length > 0) {
        const currentId = queue.shift();
        if (visited.has(currentId) || anchorIds.has(currentId)) continue;
        visited.add(currentId);

        const ev = allEvents.find(e => String(e.id) === currentId && !String(e.id).endsWith('-cleaning'));
        if (!ev) continue;

        chain.push(ev);

        // Tìm sự kiện tiếp theo có predecessor_code = code của ev này
        const myCode = ev.extendedProps.code;
        if (myCode) {
          allEvents.forEach(e => {
            if (
              String(e.extendedProps.predecessor_code) === String(myCode) &&
              !String(e.id).endsWith('-cleaning') &&
              !anchorIds.has(String(e.id)) &&
              !visited.has(String(e.id))
            ) {
              queue.push(String(e.id));
            }
          });
        }
      }

      return chain.sort((a, b) => (a.extendedProps.stage_code || 0) - (b.extendedProps.stage_code || 0));
    };

    const allViolationChain = new Map();
    violationIds.forEach(vid => {
      buildChain(vid).forEach(ev => allViolationChain.set(String(ev.id), ev));
    });

    if (allViolationChain.size === 0) {
      Swal.fire("Thông báo", "Không tìm thấy sự kiện nào cần điều chỉnh.", "info");
      return;
    }

    const sortedChain = [...allViolationChain.values()]
      .sort((a, b) => (a.extendedProps.stage_code || 0) - (b.extendedProps.stage_code || 0));

    const chainIds = new Set(sortedChain.map(e => String(e.id)));

    // ==================================================================
    // BƯỚC 3: Forward cascade - đẩy từng sự kiện vi phạm về phía tương lai
    // ==================================================================
    sortedChain.forEach(ev => {
      const evId = String(ev.id);
      const cleaningEv = allEvents.find(e => String(e.id) === evId.replace('-main', '-cleaning'));

      const predCode = ev.extendedProps.predecessor_code;
      let earliestStart = null;

      if (predCode) {
        const predEv = allEvents.find(e =>
          String(e.extendedProps.code) === String(predCode) &&
          !String(e.id).endsWith('-cleaning')
        );
        if (predEv) {
          const predId = String(predEv.id);
          earliestStart = updatedTimesById[predId]?.end || new Date(predEv.end);

          const predCleaningId = predId.replace('-main', '-cleaning');
          if (updatedTimesById[predCleaningId]) {
            earliestStart = updatedTimesById[predCleaningId].end;
          } else {
            const predCleaning = allEvents.find(e => String(e.id) === predCleaningId);
            if (predCleaning) earliestStart = new Date(predCleaning.end);
          }
        }
      }

      const currentStart = updatedTimesById[evId]?.start || new Date(ev.start);
      if (!earliestStart || currentStart >= earliestStart) {
        // Đã hợp lệ → giữ nguyên
        updatedTimesById[evId] = { start: currentStart, end: updatedTimesById[evId]?.end || new Date(ev.end) };
        if (cleaningEv) {
          const cId = String(cleaningEv.id);
          if (!updatedTimesById[cId]) updatedTimesById[cId] = { start: new Date(cleaningEv.start), end: new Date(cleaningEv.end) };
        }
        return;
      }

      const duration = new Date(ev.end).getTime() - new Date(ev.start).getTime();
      const resourceId = ev.getResources()[0]?.id;

      // Chỉ bỏ qua các chain events CHƯA được xử lý
      // (events đã xử lý có trong updatedTimesById và sẽ được tôn trọng qua tham số đó)
      const unprocessedChainIds = [...chainIds].filter(id =>
        id !== evId &&
        !updatedTimesById[id] &&
        !(cleaningEv && String(cleaningEv.id) === id)
      );
      const unprocessedCleaningIds = unprocessedChainIds
        .map(id => id.replace('-main', '-cleaning'))
        .filter(cid => !updatedTimesById[cid]);

      const ignoreIds = [
        ...anchorIds,
        ...unprocessedChainIds,
        ...unprocessedCleaningIds,
        evId,
        cleaningEv ? String(cleaningEv.id) : null
      ].filter(Boolean);

      const newSlot = findNextAvailableSlot(resourceId, duration, earliestStart, allEvents, offRanges, ignoreIds, updatedTimesById);

      updatedTimesById[evId] = { start: newSlot.start, end: newSlot.end };
      updates.push({ id: evId, start: newSlot.start, end: newSlot.end, resourceId, clearWarnings: true });

      if (cleaningEv) {
        const cId = String(cleaningEv.id);
        const cDur = new Date(cleaningEv.end).getTime() - new Date(cleaningEv.start).getTime();
        const cStart = newSlot.end;
        const cEnd = new Date(cStart.getTime() + cDur);
        updatedTimesById[cId] = { start: cStart, end: cEnd };
        updates.push({ id: cId, start: cStart, end: cEnd, resourceId, clearWarnings: true });
      }
    });

    if (updates.length > 0) {
      let newPending = [...pendingChanges];
      calendarApi.batchRendering(() => {
        updates.forEach(u => {
          const ev = calendarApi.getEventById(u.id);
          if (ev) {
            if (u.start && u.end) ev.setDates(u.start, u.end);
            if (u.clearWarnings) {
              ev.setExtendedProp('warning_text', '');
              ev.setExtendedProp('violation_colors', []);
            }
          }
          if (u.start && u.end && ev) {
            const changeObj = {
              id: u.id,
              start: u.start,
              end: u.end,
              resourceId: u.resourceId || (ev.getResources ? ev.getResources()[0]?.id : ev.resourceId),
              title: ev.title,
              submit: ev.extendedProps?.submit,
              C_end: ev.extendedProps?.C_end || false
            };
            const existIdx = newPending.findIndex(p => String(p.id) === String(u.id));
            if (existIdx >= 0) newPending[existIdx] = { ...newPending[existIdx], ...changeObj };
            else newPending.push(changeObj);
          }
        });
      });
      setPendingChanges(newPending);
      Swal.fire("Thành công", `Đã điều chỉnh ${updates.filter(u => u.start).length} sự kiện.`, "success");
    } else {
      Swal.fire("Thông báo", "Không có sự kiện nào cần điều chỉnh.", "info");
    }
  };



  // ==========================================================================
  // SUA LOI CHONG CHAT SU KIEN
  // ==========================================================================
  const handleFixOverlappingEvents = async () => {
    const confirmed = await Swal.fire({
      title: 'Sửa Lỗi Chồng Chất Sự Kiện',
      html: `<div style="text-align:left;font-size:14px">
        <p>Chức năng này sẽ:</p>
        <ul style="margin:8px 0;padding-left:20px">
          <li>🔍 <b>Quét</b> tất cả sự kiện đang chồng lấn trên cùng phòng sản xuất</li>
          <li>↔️ <b>Tịnh tuyến</b> chúng vào vị trí trống gần nhất (trước hoặc sau)</li>
          <li>✅ <b>Đảm bảo</b> không vi phạm ngày nghỉ</li>
        </ul>
        <p style="color:#e67e22;margin-top:8px">⚠️ Chỉ di chuyển sự kiện <b>chưa hoàn thành</b>.</p>
      </div>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: '🔧 Chạy sửa lỗi',
      cancelButtonText: 'Hủy',
      confirmButtonColor: '#f39c12'
    });
    if (!confirmed.isConfirmed) return;

    Swal.fire({
      title: 'Đang quét và sửa lỗi...',
      html: 'Hệ thống đang tìm và tịnh tuyến các sự kiện chồng lấn...',
      allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false,
      didOpen: () => Swal.showLoading()
    });
    await new Promise(resolve => setTimeout(resolve, 80));

    const calendarApi = calendarRef.current?.getApi();
    if (!calendarApi) return;
    const allEvents = calendarApi.getEvents();
    const offRanges = offDays.map(d => {
      const start = new Date(`${d}T06:00:00`);
      const end = new Date(start.getTime() + 24 * 60 * 60 * 1000);
      return { start, end };
    }).sort((a, b) => a.start - b.start);

    let updates = [];
    let updatedTimesById = {};

    // Lock events da hoan thanh
    allEvents.forEach(e => {
      if (e.extendedProps?.finished == 1) {
        updatedTimesById[String(e.id)] = { start: new Date(e.start), end: new Date(e.end) };
      }
    });

    // Nhom MAIN events theo resource
    const eventsByResource = {};
    allEvents.forEach(e => {
      if (String(e.id).endsWith('-cleaning')) return;
      if (updatedTimesById[String(e.id)]) return;
      const rid = e.getResources()[0]?.id;
      if (!rid) return;
      if (!eventsByResource[rid]) eventsByResource[rid] = [];
      eventsByResource[rid].push(e);
    });

    for (const [resourceId, events] of Object.entries(eventsByResource)) {
      events.sort((a, b) => new Date(a.start) - new Date(b.start));
      for (const ev of events) {
        const evId = String(ev.id);
        const cleaningId = evId.replace('-main', '-cleaning');
        const cleaningEv = allEvents.find(e => String(e.id) === cleaningId);
        const evStart = new Date(ev.start);
        const duration = new Date(ev.end).getTime() - evStart.getTime();
        const cleaningDur = cleaningEv ? (new Date(cleaningEv.end).getTime() - new Date(cleaningEv.start).getTime()) : 0;

        const unprocessed = events.filter(e => !updatedTimesById[String(e.id)] && String(e.id) !== evId).map(e => String(e.id));
        const unprocessedCleaning = unprocessed.map(id => id.replace('-main', '-cleaning'));
        const ignoreIds = [...unprocessed, ...unprocessedCleaning, evId, cleaningId].filter(Boolean);

        const newSlot = findNextAvailableSlot(resourceId, duration, evStart, allEvents, offRanges, ignoreIds, updatedTimesById);
        const moved = Math.abs(newSlot.start.getTime() - evStart.getTime()) > 60000;

        updatedTimesById[evId] = { start: newSlot.start, end: newSlot.end };
        if (moved) updates.push({ id: evId, start: newSlot.start, end: newSlot.end, resourceId });

        if (cleaningEv) {
          const cStart = newSlot.end;
          const cEnd = new Date(cStart.getTime() + cleaningDur);
          updatedTimesById[cleaningId] = { start: cStart, end: cEnd };
          if (moved) updates.push({ id: cleaningId, start: cStart, end: cEnd, resourceId });
        }
      }
    }

    if (updates.length > 0) {
      let newPending = [...pendingChanges];
      calendarApi.batchRendering(() => {
        updates.forEach(u => {
          const ev = calendarApi.getEventById(u.id);
          if (ev && u.start && u.end) ev.setDates(u.start, u.end);
          if (ev) {
            const changeObj = { id: u.id, start: u.start, end: u.end, resourceId: u.resourceId || ev.getResources()[0]?.id, title: ev.title, submit: ev.extendedProps?.submit, C_end: ev.extendedProps?.C_end || false };
            const idx = newPending.findIndex(p => String(p.id) === String(u.id));
            if (idx >= 0) newPending[idx] = { ...newPending[idx], ...changeObj };
            else newPending.push(changeObj);
          }
        });
      });
      setPendingChanges(newPending);
      const mainMoved = updates.filter(u => !String(u.id).endsWith('-cleaning')).length;
      Swal.fire('Hoàn thành!', `Đã dịch chuyển <b>${mainMoved}</b> sự kiện khỏi vị trí chồng lấn.<br>Nhấn 💾 để lưu thay đổi.`, 'success');
    } else {
      Swal.fire('Thông báo', 'Không tìm thấy sự kiện nào bị chồng lấn. Lịch đang sạch!', 'info');
    }
  };

  // ==========================================================================
  // TỰ ĐỘNG SỬA LỖI ĐEN THEO PHA CHẾ
  // Cố định stage_code 3 & 4 (chưa xong, chưa bắt đầu) làm anchor
  // Backward cascade: kéo stage 1, 2 về sớm hơn (kết thúc trước stage 3)
  // Forward cascade: đẩy stage 5+ về sau (bắt đầu sau stage 4)
  // ==========================================================================
  const handleAutoFixByPhaChe = async () => {
    const confirmed = await Swal.fire({
      title: 'Tự Động Sửa Lỗi Đen Theo Pha Chế',
      html: `
        <div style="text-align:left;font-size:14px">
          <p>Chức năng này sẽ:</p>
          <ul style="margin:8px 0;padding-left:20px">
            <li>🔒 <b>Cố định</b> các công đoạn <b>Pha Chế</b> (giai đoạn 3 & 4) chưa bắt đầu</li>
            <li>⬅️ <b>Kéo lùi</b> các công đoạn trước (Cân, ...) để kết thúc đúng hạn</li>
            <li>➡️ <b>Đẩy tới</b> các công đoạn sau (Nén, Đóng gói...) để bắt đầu sau Pha Chế</li>
          </ul>
          <p style="color:#e74c3c;margin-top:8px">⚠️ Thao tác này ảnh hưởng đến <b>toàn bộ lịch hiện tại</b>.</p>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Chạy tự động',
      cancelButtonText: 'Hủy',
      confirmButtonColor: '#8e44ad'
    });

    if (!confirmed.isConfirmed) return;

    Swal.fire({
      title: 'Đang xử lý...',
      html: 'Vui lòng đợi trong khi hệ thống điều chỉnh lịch theo Pha Chế.',
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => Swal.showLoading()
    });

    await new Promise(resolve => setTimeout(resolve, 80));

    const calendarApi = calendarRef.current?.getApi();
    if (!calendarApi) return;
    const allEvents = calendarApi.getEvents();
    const now = new Date();

    // ----------------------------------------------------------------
    // BƯỚC 1: Xác định anchors
    // Stage 3 (chưa hoàn thành) → LUÔN là anchor (cố định)
    // Stage 4 (chưa hoàn thành) → chỉ là anchor nếu KHÔNG có stage 3 trong chuỗi predecessor
    //   (nếu sản phẩm có cả 3 và 4: stage 3 cố định, stage 4 được forward cascade đặt sau stage 3)
    // ----------------------------------------------------------------
    const stage3Anchors = allEvents.filter(e => {
      if (String(e.id).endsWith('-cleaning')) return false;
      if (Number(e.extendedProps?.stage_code) !== 3) return false;
      if (e.extendedProps?.finished == 1) return false;
      return true;
    });

    // Tập hợp code của tất cả stage 3 anchors để tra cứu nhanh
    const stage3AnchorCodes = new Set(stage3Anchors.map(a => String(a.extendedProps?.code)).filter(Boolean));

    // Helper: kiểm tra xem một event có stage 3 ancestor không (trace theo predecessor_code)
    const hasStage3Ancestor = (ev) => {
      let code = ev.extendedProps?.predecessor_code;
      let depth = 0;
      while (code && depth++ < 20) {
        if (stage3AnchorCodes.has(String(code))) return true;
        const predEv = allEvents.find(e =>
          String(e.extendedProps?.code) === String(code) &&
          !String(e.id).endsWith('-cleaning')
        );
        if (!predEv) break;
        code = predEv.extendedProps?.predecessor_code;
      }
      return false;
    };

    // Stage 4: chỉ anchor nếu không có stage 3 ancestor
    const stage4AnchorsList = allEvents.filter(e => {
      if (String(e.id).endsWith('-cleaning')) return false;
      if (Number(e.extendedProps?.stage_code) !== 4) return false;
      if (e.extendedProps?.finished == 1) return false;
      return !hasStage3Ancestor(e); // Không phải anchor nếu có stage 3 ở trước
    });

    const anchors = [...stage3Anchors, ...stage4AnchorsList];

    if (anchors.length === 0) {
      Swal.fire('Thông báo', 'Không tìm thấy công đoạn Pha Chế nào phù hợp (chưa hoàn thành).', 'info');
      return;
    }

    const anchorIds = new Set(anchors.map(a => String(a.id)));
    const offRanges = offDays.map(d => {
      const start = new Date(`${d}T06:00:00`);
      const end = new Date(start.getTime() + 24 * 60 * 60 * 1000);
      return { start, end };
    }).sort((a, b) => a.start - b.start);

    let updates = [];
    let updatedTimesById = {};

    // Đăng ký anchors + cleaning vào updatedTimesById (lock)
    anchors.forEach(a => {
      updatedTimesById[String(a.id)] = { start: new Date(a.start), end: new Date(a.end) };
      const cleaningA = allEvents.find(e => String(e.id) === String(a.id).replace('-main', '-cleaning'));
      if (cleaningA) {
        updatedTimesById[String(cleaningA.id)] = { start: new Date(cleaningA.start), end: new Date(cleaningA.end) };
      }
    });

    // Các event không di chuyển: chỉ finished=1 (đã hoàn thành)
    // Event trong quá khứ nhưng chưa hoàn thành VẪN được di chuyển
    allEvents.forEach(e => {
      const eId = String(e.id);
      if (updatedTimesById[eId]) return; // đã lock
      if (e.extendedProps?.finished == 1) {
        updatedTimesById[eId] = { start: new Date(e.start), end: new Date(e.end) };
      }
    });

    // ----------------------------------------------------------------
    // Helper: Build predecessor chain (ngược lên)
    // ----------------------------------------------------------------
    const buildPredChain = (startId) => {
      const chain = [];
      const visited = new Set();
      let queue = [startId];
      while (queue.length > 0) {
        const cId = queue.shift();
        if (visited.has(cId) || anchorIds.has(cId) || updatedTimesById[cId]) continue;
        visited.add(cId);
        const ev = allEvents.find(e => String(e.id) === cId && !String(e.id).endsWith('-cleaning'));
        if (!ev) continue;
        if (ev.extendedProps?.finished == 1) continue; // Không di chuyển event đã hoàn thành
        chain.push(ev);
        const pCode = ev.extendedProps.predecessor_code;
        if (pCode) {
          const pEv = allEvents.find(e =>
            String(e.extendedProps.code) === String(pCode) &&
            !String(e.id).endsWith('-cleaning') &&
            !anchorIds.has(String(e.id)) &&
            !visited.has(String(e.id))
          );
          if (pEv) queue.push(String(pEv.id));
        }
      }
      // Sắp xếp GIẢM dần theo stage_code (gần anchor nhất xử lý trước)
      return chain.sort((a, b) => (b.extendedProps.stage_code || 0) - (a.extendedProps.stage_code || 0));
    };

    // ----------------------------------------------------------------
    // Helper: Build successor chain (xuôi xuống)
    // ----------------------------------------------------------------
    const buildSuccChain = (startId) => {
      const chain = [];
      const visited = new Set();
      let queue = [startId];
      while (queue.length > 0) {
        const cId = queue.shift();
        if (visited.has(cId) || anchorIds.has(cId) || updatedTimesById[cId]) continue;
        visited.add(cId);
        const ev = allEvents.find(e => String(e.id) === cId && !String(e.id).endsWith('-cleaning'));
        if (!ev) continue;
        if (ev.extendedProps?.finished == 1) continue; // Không di chuyển event đã hoàn thành
        chain.push(ev);
        const myCode = ev.extendedProps.code;
        if (myCode) {
          allEvents.forEach(e => {
            const eId = String(e.id);
            if (String(e.extendedProps.predecessor_code) === String(myCode) &&
              !String(e.id).endsWith('-cleaning') &&
              !anchorIds.has(eId) &&
              !visited.has(eId)
            ) {
              queue.push(eId);
            }
          });
        }
      }
      // Sắp xếp TĂNG dần theo stage_code (gần anchor nhất xử lý trước)
      return chain.sort((a, b) => (a.extendedProps.stage_code || 0) - (b.extendedProps.stage_code || 0));
    };

    // ----------------------------------------------------------------
    // BƯỚC 2: BACKWARD CASCADE từ mỗi anchor stage_code = 3
    // Kéo predecessors (stage 1, 2) về sớm hơn để kết thúc trước anchor.start
    // ----------------------------------------------------------------
    for (const anchor of stage3Anchors) {
      const predCode = anchor.extendedProps.predecessor_code;
      if (!predCode) continue;

      const predEv = allEvents.find(e =>
        String(e.extendedProps.code) === String(predCode) &&
        !String(e.id).endsWith('-cleaning')
      );
      if (!predEv) continue;

      const predChain = buildPredChain(String(predEv.id));
      if (predChain.length === 0) continue;

      const chainIds = new Set(predChain.map(e => String(e.id)));

      for (const ev of predChain) {
        const evId = String(ev.id);
        const cleaningEv = allEvents.find(e => String(e.id) === evId.replace('-main', '-cleaning'));

        // latestEnd = start của successor (event có predecessor_code = ev.code)
        const myCode = ev.extendedProps.code;
        let latestEnd = null;
        if (myCode) {
          const succEv = allEvents.find(e =>
            String(e.extendedProps.predecessor_code) === String(myCode) &&
            !String(e.id).endsWith('-cleaning')
          );
          if (succEv) {
            const succId = String(succEv.id);
            latestEnd = updatedTimesById[succId]?.start || new Date(succEv.start);
          }
        }
        // Nếu không tìm được successor, dùng anchor.start
        if (!latestEnd) latestEnd = new Date(anchor.start);

        const duration = new Date(ev.end).getTime() - new Date(ev.start).getTime();
        const resourceId = ev.getResources()[0]?.id;

        // Cleaning phải đặt SAU main → trừ cleaning duration khỏi latestEnd
        const cleaningDur = cleaningEv
          ? (new Date(cleaningEv.end).getTime() - new Date(cleaningEv.start).getTime())
          : 0;
        const adjustedLatestEnd = new Date(latestEnd.getTime() - cleaningDur);

        // Kiểm tra xem đã hợp lệ chưa
        const currentEnd = updatedTimesById[evId]?.end || new Date(ev.end);
        if (currentEnd <= latestEnd) {
          updatedTimesById[evId] = { start: updatedTimesById[evId]?.start || new Date(ev.start), end: currentEnd };
          if (cleaningEv) {
            const cId = String(cleaningEv.id);
            if (!updatedTimesById[cId]) updatedTimesById[cId] = { start: new Date(cleaningEv.start), end: new Date(cleaningEv.end) };
          }
          continue;
        }

        // Ignore: chỉ bỏ qua các chain events CHƯА xử lý + event hiện tại
        // QUAN TRỌNG: KHAI anchorIds không nằm trong ignoreIds
        // (anchor đã trong updatedTimesById → findPreviousAvailableSlot tự động dùng vị trí làm obstacle)
        const unprocessed = [...chainIds].filter(id => id !== evId && !updatedTimesById[id]);
        const ignoreIds = [...unprocessed, evId, cleaningEv ? String(cleaningEv.id) : null].filter(Boolean);

        const newSlot = findPreviousAvailableSlot(resourceId, duration, adjustedLatestEnd, allEvents, offRanges, ignoreIds, updatedTimesById);

        updatedTimesById[evId] = { start: newSlot.start, end: newSlot.end };
        updates.push({ id: evId, start: newSlot.start, end: newSlot.end, resourceId, clearWarnings: true });

        if (cleaningEv) {
          const cId = String(cleaningEv.id);
          const cStart = newSlot.end;
          const cEnd = new Date(cStart.getTime() + cleaningDur);
          updatedTimesById[cId] = { start: cStart, end: cEnd };
          updates.push({ id: cId, start: cStart, end: cEnd, resourceId, clearWarnings: true });
        }
      }
    }

    // ----------------------------------------------------------------
    // BƯỚC 3: FORWARD CASCADE
    // Điểm xuất phát:
    //   - Nếu sản phẩm có stage 3 anchor và stage 4 (không phải anchor) → cascade từ stage 3
    //     (stage 4 sẽ được dịch chuyển ngay sau stage 3, rồi stage 5, 6, 7 theo sau)
    //   - Nếu chỉ có stage 4 anchor (không có stage 3) → cascade từ stage 4
    // ----------------------------------------------------------------
    // Tạo danh sách tất cả điểm xuất phát forward cascade
    // = stage3Anchors (có thể có stage 4 kế tiếp) + stage4AnchorsList (không có stage 3 ancestor)
    const forwardAnchors = [...stage3Anchors, ...stage4AnchorsList];

    for (const anchor of forwardAnchors) {
      const myCode = anchor.extendedProps.code;
      if (!myCode) continue;

      const succEv = allEvents.find(e =>
        String(e.extendedProps.predecessor_code) === String(myCode) &&
        !String(e.id).endsWith('-cleaning') &&
        !anchorIds.has(String(e.id))
      );
      if (!succEv) continue;
      if (updatedTimesById[String(succEv.id)]) continue; // đã xử lý

      const succChain = buildSuccChain(String(succEv.id));
      if (succChain.length === 0) continue;

      const chainIds = new Set(succChain.map(e => String(e.id)));

      for (const ev of succChain) {
        const evId = String(ev.id);
        if (updatedTimesById[evId]) continue;

        const cleaningEv = allEvents.find(e => String(e.id) === evId.replace('-main', '-cleaning'));

        // earliestStart = end của predecessor (+ cleaning nếu có)
        const predCode = ev.extendedProps.predecessor_code;
        let earliestStart = null;
        if (predCode) {
          const predEv = allEvents.find(e =>
            String(e.extendedProps.code) === String(predCode) &&
            !String(e.id).endsWith('-cleaning')
          );
          if (predEv) {
            const predId = String(predEv.id);
            earliestStart = updatedTimesById[predId]?.end || new Date(predEv.end);
            // Tính cả cleaning của predecessor
            const predCleaningId = predId.replace('-main', '-cleaning');
            if (updatedTimesById[predCleaningId]) {
              earliestStart = updatedTimesById[predCleaningId].end;
            } else {
              const predCleaning = allEvents.find(e => String(e.id) === predCleaningId);
              if (predCleaning) earliestStart = new Date(predCleaning.end);
            }
          }
        }

        const currentStart = updatedTimesById[evId]?.start || new Date(ev.start);
        if (!earliestStart || currentStart >= earliestStart) {
          updatedTimesById[evId] = { start: currentStart, end: updatedTimesById[evId]?.end || new Date(ev.end) };
          if (cleaningEv) {
            const cId = String(cleaningEv.id);
            if (!updatedTimesById[cId]) updatedTimesById[cId] = { start: new Date(cleaningEv.start), end: new Date(cleaningEv.end) };
          }
          continue;
        }

        const duration = new Date(ev.end).getTime() - new Date(ev.start).getTime();
        const resourceId = ev.getResources()[0]?.id;

        // Ignore: chỉ bỏ qua các chain events CHƯА xử lý + event hiện tại
        // QUAN TRỌNG: anchorIds không nằm trong ignoreIds
        // (anchor đã trong updatedTimesById → findNextAvailableSlot tự động dùng vị trí làm obstacle)
        const unprocessed = [...chainIds].filter(id => id !== evId && !updatedTimesById[id]);
        const unprocessedCleaning = unprocessed.map(id => id.replace('-main', '-cleaning')).filter(cid => !updatedTimesById[cid]);
        const ignoreIds = [...unprocessed, ...unprocessedCleaning, evId, cleaningEv ? String(cleaningEv.id) : null].filter(Boolean);

        const newSlot = findNextAvailableSlot(resourceId, duration, earliestStart, allEvents, offRanges, ignoreIds, updatedTimesById);

        updatedTimesById[evId] = { start: newSlot.start, end: newSlot.end };
        updates.push({ id: evId, start: newSlot.start, end: newSlot.end, resourceId, clearWarnings: true });

        if (cleaningEv) {
          const cId = String(cleaningEv.id);
          const cDur = new Date(cleaningEv.end).getTime() - new Date(cleaningEv.start).getTime();
          const cStart = newSlot.end;
          const cEnd = new Date(cStart.getTime() + cDur);
          updatedTimesById[cId] = { start: cStart, end: cEnd };
          updates.push({ id: cId, start: cStart, end: cEnd, resourceId, clearWarnings: true });
        }
      }
    }

    // ----------------------------------------------------------------
    // BƯỚC 4: Apply tất cả thay đổi
    // ----------------------------------------------------------------
    if (updates.length > 0) {
      let newPending = [...pendingChanges];
      calendarApi.batchRendering(() => {
        updates.forEach(u => {
          const ev = calendarApi.getEventById(u.id);
          if (ev) {
            if (u.start && u.end) ev.setDates(u.start, u.end);
            if (u.clearWarnings) {
              ev.setExtendedProp('warning_text', '');
              ev.setExtendedProp('violation_colors', []);
            }
          }
          if (u.start && u.end && ev) {
            const changeObj = {
              id: u.id,
              start: u.start,
              end: u.end,
              resourceId: u.resourceId || (ev.getResources ? ev.getResources()[0]?.id : ev.resourceId),
              title: ev.title,
              submit: ev.extendedProps?.submit,
              C_end: ev.extendedProps?.C_end || false
            };
            const existIdx = newPending.findIndex(p => String(p.id) === String(u.id));
            if (existIdx >= 0) newPending[existIdx] = { ...newPending[existIdx], ...changeObj };
            else newPending.push(changeObj);
          }
        });
      });
      setPendingChanges(newPending);
      Swal.fire(
        'Hoàn thành!',
        `Đã điều chỉnh <b>${updates.filter(u => u.start).length}</b> sự kiện theo Pha Chế.<br>Nhấn 💾 để lưu thay đổi.`,
        'success'
      );
    } else {
      Swal.fire('Thông báo', 'Tất cả các công đoạn đã hợp lệ, không cần điều chỉnh.', 'info');
    }
  };

  const handleFixAllWeighingViolations = async (targetEvent = null) => {

    Swal.fire({
      title: "Đang xử lý...",
      html: "Vui lòng đợi trong khi hệ thống điều chỉnh lỗi Cân Nguyên Liệu.",
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => Swal.showLoading()
    });

    await new Promise(resolve => setTimeout(resolve, 50));

    const calendarApi = calendarRef.current?.getApi();
    if (!calendarApi) return;
    const allEvents = calendarApi.getEvents();

    const offRanges = offDays.map(d => {
      const start = new Date(`${d}T06:00:00`);
      const end = new Date(start.getTime() + 24 * 60 * 60 * 1000);
      return { start, end };
    }).sort((a, b) => a.start - b.start);

    // Tìm các lô Cân bị lỗi đen
    let weighingViolations = allEvents.filter(e => {
      let isWeighing = false;
      let stageCode = String(e.extendedProps?.stage_code);
      if (stageCode === "1" || stageCode === "2" || stageCode === "0") {
        isWeighing = true;
      } else {
        const res = e.getResources ? e.getResources()[0] : null;
        if (res && res.title) {
          const rTitle = res.title.toLowerCase();
          if (rTitle.includes('dispensing') || rTitle.includes('cân') || rTitle.includes('weighing')) {
            isWeighing = true;
          }
        }
      }

      let hasViolation = (e.backgroundColor && e.backgroundColor.toLowerCase() === '#4d4b4bff') || (e.extendedProps?.violation_colors?.includes('#4d4b4bff'));

      // Nếu có targetEvent, chỉ sửa lỗi cân của plan_master_id đó
      if (targetEvent) {
        if (String(e.extendedProps?.plan_master_id) !== String(targetEvent.extendedProps?.plan_master_id)) return false;
      }

      return isWeighing && hasViolation;
    });

    if (weighingViolations.length === 0) {
      Swal.fire("Thông báo", targetEvent ? "Lô Cân của chuỗi này không có lỗi đen." : "Không có công đoạn Cân nào bị lỗi đen trên lịch hiện tại.", "info");
      return;
    }

    let updates = [];
    let updatedTimesById = {};

    weighingViolations.forEach(weighingEvent => {
      let pmId = weighingEvent.extendedProps?.plan_master_id;
      let successor = allEvents.find(e => e.extendedProps?.plan_master_id === pmId && String(e.extendedProps?.predecessor_code) === String(weighingEvent.extendedProps?.code));

      if (!successor) {
        let others = allEvents.filter(e => e.extendedProps?.plan_master_id === pmId && parseInt(e.extendedProps?.stage_code) > 1);
        others.sort((a, b) => a.start - b.start);
        if (others.length > 0) successor = others[0];
      }

      if (successor) {
        let successorStart = updatedTimesById[successor.id] ? updatedTimesById[successor.id].start : successor.start;
        let resourceId = weighingEvent.getResources()[0]?.id || weighingEvent.resourceId;
        let durationMs = new Date(weighingEvent.end).getTime() - new Date(weighingEvent.start).getTime();

        let cleaningEvent = allEvents.find(e => String(e.id) === String(weighingEvent.id).replace('-main', '-cleaning'));

        let ignoreIds = [weighingEvent.id];
        if (cleaningEvent) ignoreIds.push(cleaningEvent.id);

        let newSlot = findPreviousAvailableSlot(resourceId, durationMs, successorStart, allEvents, offRanges, ignoreIds, updatedTimesById);

        updatedTimesById[weighingEvent.id] = { start: newSlot.start, end: newSlot.end };

        if (cleaningEvent) {
          let cleaningDuration = new Date(cleaningEvent.end).getTime() - new Date(cleaningEvent.start).getTime();
          let newCleaningStart = newSlot.end;
          let newCleaningEnd = new Date(newCleaningStart.getTime() + cleaningDuration);
          updatedTimesById[cleaningEvent.id] = { start: newCleaningStart, end: newCleaningEnd };

          updates.push({
            id: cleaningEvent.id,
            start: newCleaningStart,
            end: newCleaningEnd,
            resourceId: resourceId,
            clearWarnings: true
          });
        }

        updates.push({
          id: weighingEvent.id,
          start: newSlot.start,
          end: newSlot.end,
          resourceId: resourceId,
          clearWarnings: true
        });
      }
    });

    if (updates.length > 0) {
      let newPending = [...pendingChanges];

      calendarApi.batchRendering(() => {
        updates.forEach(u => {
          const ev = calendarApi.getEventById(u.id);
          if (ev) {
            if (u.start && u.end) {
              ev.setDates(u.start, u.end);
            }
            if (u.clearWarnings) {
              ev.setExtendedProp('warning_text', '');
              ev.setExtendedProp('violation_colors', []);
            }
          }
          if (u.start && u.end && ev) {
            const changeObj = {
              id: u.id,
              start: u.start,
              end: u.end,
              resourceId: u.resourceId || (ev.getResources ? ev.getResources()[0]?.id : ev.resourceId),
              title: ev.title,
              submit: ev.extendedProps?.submit,
              C_end: ev.extendedProps?.C_end || false
            };
            const existIdx = newPending.findIndex(p => String(p.id) === String(u.id));
            if (existIdx >= 0) {
              newPending[existIdx] = { ...newPending[existIdx], ...changeObj };
            } else {
              newPending.push(changeObj);
            }
          }
        });
      });

      setPendingChanges(newPending);
      Swal.fire("Thành công", `Đã điều chỉnh ${updates.filter(u => u.start && !String(u.id).includes('-cleaning')).length} lô Cân bị lỗi đen.`, "success");
    } else {
      Swal.fire("Thông báo", "Không có công đoạn Cân nào cần điều chỉnh.", "info");
    }
  };

  const skipOffDays = (date, offRanges) => {
    let current = new Date(date);
    let crossed = true;
    while (crossed) {
      crossed = false;
      for (const off of offRanges) {
        if (current >= off.start && current < off.end) {
          current = new Date(off.end);
          crossed = true;
          break;
        }
        if (current < off.start) break;
      }
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

      // Hiển thị loader nếu ở chế độ Cascade vì tính toán có thể nặng
      if (isCascadeMode) {
        Swal.fire({
          title: 'Đang xử lý tịnh tuyến sự kiện...! Vui lòng chờ giây lát.',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
      }

      // Đưa logic vào setTimeout để UI kịp render loader và tránh block main thread quá lâu
      setTimeout(() => {
        selectedEvents.forEach(sel => {
          const event = calendarApi.getEventById(sel.id);

          /// kiểm tra lại định mức
          if (event) {
            const offset = delta.milliseconds + delta.days * 24 * 60 * 60 * 1000;
            const event_start = event.start.getTime()
            const newStart = new Date(event_start + offset);
            let newEnd = null;

            // Kiêm tra điều chinh đinh mức ngày chủ nhật
            if (!workingSunday) {
              const baseProcessCode = event._def.extendedProps.process_code.split('_').slice(0, 2).join('_');
              let lookupCode = baseProcessCode + "_" + event._def.resourceIds[0]
              let stage_code = event._def.extendedProps.stage_code
              let is_clearning = event._def.extendedProps.is_clearning
              let quota_event = quota.find(q =>
                q.process_code === lookupCode &&
                q.stage_code == stage_code
              );

              if (quota_event === undefined) {
                Swal.fire({
                  icon: 'warning',
                  title: 'Thiếu Định Mức',
                  timer: 1000,
                  showConfirmButton: false,
                });
                info.revert();
                return false;
              }

              let quota_event_m_time_seconds = timeToMilliseconds(quota_event.m_time)
              let quota_event_p_time_seconds = 0;

              if (event._def.extendedProps.first_in_campaign) {
                quota_event_p_time_seconds = timeToMilliseconds(quota_event.p_time)
              }


              if (is_clearning) {
                if (event._def.title == "VS-II") {
                  quota_event_m_time_seconds = timeToMilliseconds(quota_event.C2_time)
                } else {
                  quota_event_m_time_seconds = timeToMilliseconds(quota_event.C1_time)
                }

              }
              newEnd = new Date(event_start + offset + quota_event_m_time_seconds + quota_event_p_time_seconds);

              let safeEnd;
              do {
                safeEnd = newEnd;
                newEnd = skipOffDays(newEnd, offRanges);
              } while (newEnd.getTime() !== safeEnd.getTime());

            } else {
              newEnd = new Date(event.end.getTime() + offset);
            }


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

        // 🔹 Cascade Logic for Frontend (Group Move) - Smart Skip Off Days
        if (isCascadeMode) {
          const offset = delta.milliseconds + delta.days * 24 * 60 * 60 * 1000;
          const selectedIds = new Set(selectedEvents.map(s => s.id));

          const resourceMinStart = {};
          selectedEvents.forEach(sel => {
            const event = calendarApi.getEventById(sel.id);
            if (event) {
              // 🔹 Cập nhật cả ExtendedProps để mốc biên tịnh tuyến (boundary) chính xác cho các lô sau
              if (event.extendedProps.start_clearning && event.extendedProps.end_clearning) {
                const sc = new Date(new Date(event.extendedProps.start_clearning).getTime() + offset);
                const ec = new Date(new Date(event.extendedProps.end_clearning).getTime() + offset);
                event.setExtendedProp('start_clearning', sc.toISOString());
                event.setExtendedProp('end_clearning', ec.toISOString());
              }

              const resId = event.getResources()[0]?.id;
              if (resId) {
                const originalStart = new Date(event.start.getTime() - offset);
                if (!resourceMinStart[resId] || originalStart < resourceMinStart[resId]) {
                  resourceMinStart[resId] = originalStart;
                }
              }
            }
          });

          const sortedEvents = calendarApi.getEvents().sort((a, b) => a.start - b.start);
          const resourceLastEnd = {};
          selectedEvents.forEach(sel => {
            const ev = calendarApi.getEventById(sel.id);
            if (ev) {
              const rId = ev.getResources()[0]?.id;
              const boundary = ev.extendedProps.end_clearning ? new Date(ev.extendedProps.end_clearning) : ev.end;
              if (!resourceLastEnd[rId] || (boundary && boundary > resourceLastEnd[rId])) {
                resourceLastEnd[rId] = boundary;
              }
            }
          });

          sortedEvents.forEach(otherEv => {
            const resId = otherEv.getResources()[0]?.id;
            const minS = resourceMinStart[resId];
            if (
              minS &&
              !selectedIds.has(otherEv.id) &&
              otherEv.start >= minS &&
              !otherEv.extendedProps.finished &&
              !batchUpdates.some(b => b.id === otherEv.id)
            ) {
              // 1. Dự định vị trí: 
              // Nếu kéo sang phải (offset > 0): Giữ nguyên vị trí cũ (Push-only)
              // Nếu kéo sang trái (offset < 0): Tịnh tiến theo (Parallel Pull)
              let ns = new Date(otherEv.start.getTime());
              if (offset < 0) {
                ns = new Date(ns.getTime() + offset);
              }

              // 2. Ép biên (Collision/Sequence): Không cho phép lấn vào lô trước
              const boundary = resourceLastEnd[resId];
              if (boundary && ns < boundary) {
                ns = new Date(boundary.getTime());
              }

              ns = skipOffDays(ns, offRanges);

              const actualShift = ns.getTime() - otherEv.start.getTime();
              const ne = new Date((otherEv.end || otherEv.start).getTime() + actualShift);

              // 🔹 Chỉ lưu và vẽ lại nếu thực sự có thay đổi vị trí
              if (actualShift !== 0) {
                otherEv.setDates(ns, ne, { maintainDuration: true, skipRender: true });

                // 🔹 Quan trọng: Cập nhật cả extendedProps để chuỗi tịnh tiến sau đó dùng mốc biên chuẩn
                if (otherEv.extendedProps.start_clearning && otherEv.extendedProps.end_clearning) {
                  const sc_new = new Date(new Date(otherEv.extendedProps.start_clearning).getTime() + actualShift);
                  const ec_new = new Date(new Date(otherEv.extendedProps.end_clearning).getTime() + actualShift);
                  otherEv.setExtendedProp('start_clearning', sc_new.toISOString());
                  otherEv.setExtendedProp('end_clearning', ec_new.toISOString());
                }

                const newBoundary = otherEv.extendedProps.end_clearning
                  ? new Date(otherEv.extendedProps.end_clearning)
                  : ne;
                resourceLastEnd[resId] = newBoundary;

                batchUpdates.push({
                  id: otherEv.id,
                  start: ns.toISOString(),
                  end: ne.toISOString(),
                  resourceId: resId,
                  title: otherEv.title,
                  submit: otherEv.extendedProps.submit
                });
              } else {
                // Nếu không đổi vị trí, vẫn cập nhật biên cho các sự kiện phía sau
                resourceLastEnd[resId] = otherEv.extendedProps.end_clearning ? new Date(otherEv.extendedProps.end_clearning) : ne;
              }
            }
          });
        }

        setPendingChanges(prev => {
          const ids = new Set(batchUpdates.map(e => e.id));
          const filtered = prev.filter(e => !ids.has(e.id));
          return [...filtered, ...batchUpdates];
        });

        calendarApi.render();
        if (isCascadeMode) Swal.close();
      }, 50);
    } else {
      // Nếu không nằm trong selectedEvents thì xử lý đơn lẻ
      handleEventChange(info);
    }
  };

  ///
  const runCascadeLogic = (changedEvent, oldEvent) => {
    const deltaStart = changedEvent.start.getTime() - oldEvent.start.getTime();
    const deltaEnd = (changedEvent.end?.getTime() || 0) - (oldEvent.end?.getTime() || 0);
    const shift = Math.max(deltaStart, deltaEnd);

    let updates = [];

    if (shift !== 0) {
      const resourceId = changedEvent.getResources?.()[0]?.id;
      const originalStart = oldEvent.start;
      const calendarApi = calendarRef.current?.getApi();

      // 🔹 Cập nhật Cleaning Props cho chính sự kiện vừa thay đổi (PXV1)
      if (changedEvent.extendedProps.start_clearning && changedEvent.extendedProps.end_clearning) {
        const sc = new Date(changedEvent.end.getTime());
        const duration = new Date(changedEvent.extendedProps.end_clearning).getTime() - new Date(changedEvent.extendedProps.start_clearning).getTime();
        const ec = new Date(sc.getTime() + duration);
        changedEvent.setExtendedProp('start_clearning', sc.toISOString());
        changedEvent.setExtendedProp('end_clearning', ec.toISOString());
      }

      // Xuyên suốt cho resource hiện tại
      let lastEnd = changedEvent.extendedProps.end_clearning ? new Date(changedEvent.extendedProps.end_clearning) : changedEvent.end;
      const sortedEvents = calendarApi.getEvents().sort((a, b) => a.start - b.start);

      sortedEvents.forEach(otherEv => {
        if (
          otherEv.id !== changedEvent.id &&
          otherEv.getResources?.()[0]?.id === resourceId &&
          otherEv.start >= originalStart &&
          !otherEv.extendedProps.finished // Only shift unfinished events
        ) {
          // 1. Dự định: 
          // Nếu dịch chuyển/tăng size (shift > 0): Giữ nguyên (Push-only)
          // Nếu dịch chuyển sang trái (shift < 0): Tịnh tiến theo (Parallel Pull)
          let ns = new Date(otherEv.start.getTime());
          if (shift < 0) {
            ns = new Date(ns.getTime() + shift);
          }

          // 2. Đẩy nếu chồng lấn
          if (lastEnd && ns < lastEnd) {
            ns = new Date(lastEnd.getTime());
          }

          // 3. Né ngày nghỉ
          ns = skipOffDays(ns, offRanges);

          const actualShift = ns.getTime() - otherEv.start.getTime();
          const ne = new Date((otherEv.end || otherEv.start).getTime() + actualShift);

          // 🔹 Chỉ xử lý và lưu nếu thực sự có thay đổi
          if (actualShift !== 0) {
            otherEv.setDates(ns, ne, { maintainDuration: true, skipRender: true });

            // 4. Cập nhật boundary cho sự kiện tiếp theo
            // 🔹 Cập nhật props để mốc biên chuẩn
            if (otherEv.extendedProps.start_clearning && otherEv.extendedProps.end_clearning) {
              const sc_new = new Date(new Date(otherEv.extendedProps.start_clearning).getTime() + actualShift);
              const ec_new = new Date(new Date(otherEv.extendedProps.end_clearning).getTime() + actualShift);
              otherEv.setExtendedProp('start_clearning', sc_new.toISOString());
              otherEv.setExtendedProp('end_clearning', ec_new.toISOString());
            }

            lastEnd = otherEv.extendedProps.end_clearning
              ? new Date(otherEv.extendedProps.end_clearning)
              : ne;

            updates.push({
              id: otherEv.id,
              start: ns.toISOString(),
              end: ne.toISOString(),
              resourceId: resourceId,
              title: otherEv.title,
              submit: otherEv.extendedProps.submit
            });
          } else {
            // Nếu không đổi, vẫn cập nhật boundary cũ cho chuỗi
            lastEnd = otherEv.extendedProps.end_clearning ? new Date(otherEv.extendedProps.end_clearning) : ne;
          }
        }
      });
      calendarApi.render();
    }
    return updates;
  };

  const handleEventChange = (changeInfo) => {
    const changedEvent = changeInfo.event;
    const oldEvent = changeInfo.oldEvent || changedEvent;

    // Cập nhật process_code nếu resourceId thay đổi
    const newResourceId = changedEvent.getResources?.()[0]?.id ?? null;
    if (newResourceId && changedEvent.extendedProps.process_code) {
      const baseProcessCode = changedEvent.extendedProps.process_code.split('_').slice(0, 2).join('_');
      changedEvent.setExtendedProp('process_code', baseProcessCode + "_" + newResourceId);
    }

    // Create updates array starting with the changed event
    let updates = [{
      id: changedEvent.id,
      start: changedEvent.start.toISOString(),
      end: changedEvent.end.toISOString(),
      resourceId: newResourceId,
      title: changedEvent.title,
      submit: changedEvent.extendedProps.submit
    }];

    // 🔹 Cascade Logic for single move/resize - Smart Sequential Collision Skip
    if (isCascadeMode && changeInfo.oldEvent) {
      Swal.fire({
        title: 'Đang xử lý Cascade...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      setTimeout(() => {
        const cascadeUpdates = runCascadeLogic(changedEvent, oldEvent);
        updates = [...updates, ...cascadeUpdates];

        // Thêm hoặc cập nhật event vào pendingChanges
        setPendingChanges(prev => {
          const ids = new Set(updates.map(u => u.id));
          const filtered = prev.filter(e => !ids.has(e.id));
          return [...filtered, ...updates];
        });

        Swal.close();
      }, 50);
    } else {
      // Thêm hoặc cập nhật event vào pendingChanges (Trường hợp không Cascade)
      setPendingChanges(prev => {
        const ids = new Set(updates.map(u => u.id));
        const filtered = prev.filter(e => !ids.has(e.id));
        return [...filtered, ...updates];
      });
    }
  };

  const handleEventMouseEnter = (info) => {
    // 1. Xử lý Lịch sử (nếu đang bật)
    if (showHistoryHover) {
      const eventId = info.event.id;
      if (eventId) {
        if (hoverTimeoutRef.current) {
          clearTimeout(hoverTimeoutRef.current);
        }
        hoverTimeoutRef.current = setTimeout(() => {
          setLoadingHistory(true);
          axios.get('/Schedual/audit/history', { params: { id: eventId } })
            .then(res => {
              setHistoryData(res.data);
              setLoadingHistory(false);
              setShowHistoryDialog(true);
            })
            .catch(err => {
              console.error("Error fetching history:", err);
              setLoadingHistory(false);
            });
        }, 250);
      }
    }

    // 2. Xử lý Chi tiết lịch (nếu đang bật)
    if (showDetailHover) {
      if (detailHoverTimeoutRef.current) {
        clearTimeout(detailHoverTimeoutRef.current);
      }
      detailHoverTimeoutRef.current = setTimeout(() => {
        setHoverDetailData({
          event: info.event,
          props: info.event._def.extendedProps,
          x: info.jsEvent.clientX,
          y: info.jsEvent.clientY
        });
      }, 200);
    }
  };

  const handleEventMouseLeave = () => {
    // Hủy tiến trình tải history nếu người dùng rời đi sớm
    if (hoverTimeoutRef.current) {
      clearTimeout(hoverTimeoutRef.current);
      hoverTimeoutRef.current = null;
    }

    // Hủy chi tiết lịch
    if (detailHoverTimeoutRef.current) {
      clearTimeout(detailHoverTimeoutRef.current);
      detailHoverTimeoutRef.current = null;
    }
    setHoverDetailData(null);
  };

  const handleDiscardChanges = () => {
    Swal.fire({
      title: 'Hủy thay đổi?',
      text: "Tất cả các sự kiện sẽ được trả về vị trí ban đầu. Bạn có chắc chắn?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Đồng ý',
      cancelButtonText: 'Không',
      confirmButtonColor: '#d33'
    }).then((result) => {
      if (result.isConfirmed) {
        const calendarApi = calendarRef.current?.getApi();
        if (calendarApi) {
          calendarApi.batchRendering(() => {
            pendingChanges.forEach(p => {
              const ev = calendarApi.getEventById(p.id);
              if (ev) {
                const originalData = events.find(e => String(e.id) === String(p.id));
                if (originalData) {
                  ev.setDates(originalData.start, originalData.end);
                  if (originalData.resourceId) {
                    ev.setResources([originalData.resourceId]);
                  }
                  ev.setExtendedProp('warning_text', originalData.warning_text || '');
                  ev.setExtendedProp('violation_colors', originalData.violation_colors || []);
                }
              }
            });
          });
        }
        setPendingChanges([]);
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

    // 🟨 Kiểm tra xem có sự kiện nào có submit = 1 không
    const hasSubmittedEvent = pendingChanges.some(c => c.submit == 1);
    let reasonObj = {
      reason: "Cập nhật ngày",
      saveReason: false
    };

    if (hasSubmittedEvent) {
      // 🟨 Tạo datalist từ state "reasons"
      const htmlOptions = reasons
        .map(r => `<option value="${r}">`)
        .join("");

      // 🟨 Swal datalist (select hoặc nhập)
      const { value: swalResult } = await Swal.fire({
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
          const r = document.getElementById('reasonInput').value;
          const s = document.getElementById('saveReason');
          formValues.saveReason = s.checked;

          if (!r || r.trim() === '') {
            Swal.showValidationMessage('Bạn phải nhập hoặc chọn lý do!');
            return false;
          }
          formValues.reason = r;
          return formValues;
        }
      });

      // Nếu người dùng bấm “Hủy” thì dừng
      if (!swalResult) return;

      reasonObj = swalResult;
    }

    setSaving(true);

    // Hiện popup loading - block mọi thao tác trong khi đang lưu
    Swal.fire({
      title: 'Đang lưu...',
      html: `Đang xử lý <b>${pendingChanges.length}</b> thay đổi, vui lòng chờ...`,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => Swal.showLoading()
    });

    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
    let startDate = toLocalISOString(activeStart);
    let endDate = toLocalISOString(activeEnd);
    const theoryHidden = JSON.parse(sessionStorage.getItem('theoryHidden'));

    // ── Chia pendingChanges thành các batch nhỏ ──────────────────────────────
    const BATCH_SIZE = 50;
    const allChanges = pendingChanges.map(change => ({
      id: change.id,
      start: dayjs(change.start).format('YYYY-MM-DD HH:mm:ss'),
      end: dayjs(change.end).format('YYYY-MM-DD HH:mm:ss'),
      resourceId: change.resourceId,
      title: change.title,
      C_end: change.C_end || false
    }));

    const totalBatches = Math.ceil(allChanges.length / BATCH_SIZE);
    let lastData = null;

    try {
      for (let i = 0; i < totalBatches; i++) {
        const batchChanges = allChanges.slice(i * BATCH_SIZE, (i + 1) * BATCH_SIZE);
        const isLastBatch = i === totalBatches - 1;

        // Cập nhật loading với tiến độ
        Swal.update({
          title: `Đang lưu... (${i + 1}/${totalBatches})`,
          html: `Đang xử lý <b>${batchChanges.length}</b> thay đổi (đợt ${i + 1}/${totalBatches})...`,
        });

        const res = await axios.put('/Schedual/update', {
          reason: reasonObj,
          theory: theoryHidden,
          cascade: isCascadeMode,
          changes: batchChanges,
          // Chỉ gửi startDate/endDate ở đợt cuối để refresh calendar
          startDate: isLastBatch ? startDate : null,
          endDate: isLastBatch ? endDate : null,
        });

        let data = res.data;
        if (typeof data === 'string') {
          data = data.replace(/^<!--.*?-->/, '').trim();
          data = JSON.parse(data);
        }
        lastData = data;
      }

      // Sau tất cả batch thành công
      if (lastData) {
        setEvents(lastData.events);
        if (lastData.resources) setResources(lastData.resources);
        setSumBatchByStage(lastData.sumBatchByStage);
      }
      setPendingChanges([]);
      setSaving(false);

      Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: totalBatches > 1
          ? `Đã lưu ${allChanges.length} thay đổi trong ${totalBatches} đợt.`
          : 'Đã lưu tất cả thay đổi.',
        timer: 1500,
        showConfirmButton: false,
      });

      document.querySelectorAll('.fc-event[data-event-id]')
        .forEach(el => { el.style.border = 'none'; });

    } catch (err) {
      console.error('Lỗi khi lưu events:', err.response?.data || err.message);
      setSaving(false);
      Swal.fire({
        icon: 'error',
        title: 'Lỗi!',
        text: 'Không thể lưu thay đổi. Vui lòng thử lại.',
      });
    }
  };

  /// Xử lý Toggle sự kiện đang chọn: if đã chọn thì bỏ ra --> selectedEvents
  const selectedEventsRef = useRef([]);
  const toggleEventSelect = (event) => {
    setSelectedEvents((prevSelected) => {
      const exists = prevSelected.some(ev => ev.id === event.id);
      const newSelected = exists
        ? prevSelected.filter(ev => ev.id !== event.id)
        : [...prevSelected, { id: event.id, stage_code: event.extendedProps.stage_code, plan_master_id: event.extendedProps.plan_master_id }];

      // highlight DOM ngay lập tức
      const el = document.querySelector(`[data-event-id="${event.id}"]`);
      if (el) {
        el.style.border = exists ? 'none' : '5px solid yellow';
      }
      selectedEventsRef.current = newSelected;
      return newSelected;
    });
  };

  const handleEditEventClick = (event) => {
    if (!authorization) return;

    const props = event.extendedProps;
    const startStr = moment(event.start).format('YYYY-MM-DDTHH:mm');
    const endStr = moment(event.end).format('YYYY-MM-DDTHH:mm');
    const currentResourceId = event.getResources()[0]?.id;
    // Lấy danh sách phòng được phép dựa trên process_code, stage_code và định mức (quota)
    let allowedResources = [];
    if (props.process_code && quota && quota.length > 0) {
      const baseProcessCode = props.process_code.split('_').slice(0, 2).join('_');
      const allowedRoomIds = quota
        .filter(q => q.process_code.startsWith(baseProcessCode + "_") && q.stage_code === props.stage_code)
        .map(q => q.room_id);

      if (allowedRoomIds.length > 0) {
        allowedResources = resources.filter(res => allowedRoomIds.includes(res.id));
      }
    }


    // Nếu không tìm thấy theo process_code, fallback về permisson_room_filter hoặc stage_code
    if (allowedResources.length === 0) {
      const allowedCodes = props.permisson_room_filter
        ? props.permisson_room_filter.split(',').map(s => s.trim())
        : [];

      allowedResources = resources.filter(res => {
        if (allowedCodes.length > 0) {
          return allowedCodes.includes(res.code);
        }
        return res.stage_code === props.stage_code;
      });
    }

    const roomOptions = allowedResources.map(res =>
      `<option value="${res.id}" ${res.id == currentResourceId ? 'selected' : ''}>${res.title}</option>`
    ).join('');

    const baseProcessCode = props.process_code ? props.process_code.split('_').slice(0, 2).join('_') : '';
    const currentQuota = (quota && props.process_code)
      ? quota.find(q => q.process_code == baseProcessCode + "_" + currentResourceId)
      : null;

    let p_time = currentQuota ? currentQuota.p_time : 0;
    let m_time = currentQuota ? currentQuota.m_time : 0;

    // Lấy định mức thực tế từ duration của event (ưu tiên hơn quota mặc định)
    if (event.start && event.end) {
      const durationHours = moment(event.end).diff(moment(event.start), 'hours', true);
      if (props.first_in_campaign == 1 || props.title_clearning == "VS-II") {
        m_time = durationHours - p_time;
      } else {
        m_time = durationHours;
      }
      // Làm tròn 2 chữ số thập phân
      m_time = Math.round(m_time * 100) / 100;
      if (m_time < 0) m_time = 0;
    }

    // Chuẩn bị danh sách lô đã chọn để hiển thị trong modal
    const currentSelectedEvents = selectedEventsRef.current || [];
    const selectedBatchListHtml = currentSelectedEvents.length > 0
      ? currentSelectedEvents.map((sel, idx) => {
        const api = calendarRef.current?.getApi();
        const ev = api?.getEventById(sel.id);
        const evTitle = ev ? ev.title : sel.id;
        return `<div style="padding: 4px 8px; background: #f0f7ff; border-radius: 4px; margin-bottom: 4px; font-size: 13px; border-left: 3px solid #3085d6;">
            ${idx + 1}. ${evTitle}
          </div>`;
      }).join('')
      : '<div style="padding: 8px; color: #999; font-style: italic;">Chưa có lô nào được chọn</div>';

    const htmlOptions = reasons
      .map(r => `<option value="${r}">`)
      .join("");

    Swal.fire({
      title: 'Cập nhật lịch sản xuất',
      width: '550px',
      html: `
        <div style="text-align: left; padding: 10px;">
          <div style="display: flex; gap: 10px; margin-bottom: 15px;">
            <div style="flex: 1;">
              <label style="display: block; font-weight: bold; margin-bottom: 5px;">Định mức chuẩn bị (h):</label>
              <input type="number" id="swal-p-time" class="swal2-input" style="width: 100%; margin: 0; background: #f8f9fa;" value="${p_time}" readonly>
            </div>
            <div style="flex: 1;">
              <label style="display: block; font-weight: bold; margin-bottom: 5px;">Định mức SX (h):</label>
              <input type="number" id="swal-m-time" class="swal2-input" style="width: 100%; margin: 0;" value="${m_time}">
            </div>
          </div>

          <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Thời gian bắt đầu:</label>
            <input type="datetime-local" id="swal-start" class="swal2-input" style="width: 100%; margin: 0;" value="${startStr}">
          </div>
          
          <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Thời gian kết thúc:</label>
            <input type="datetime-local" id="swal-end" class="swal2-input" style="width: 100%; margin: 0;" value="${endStr}">
          </div>

          <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Phòng:</label>
            <select id="swal-resource" class="swal2-select" style="width: 100%; margin: 0; display: block;">
              ${roomOptions}
            </select>
          </div>

          <div id="swal-reason-container" style="margin-bottom: 15px; border: 1px solid #ffe066; border-radius: 8px; padding: 12px; background: #fffbe6; display: none;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #856404;">
              <i>⚠</i> Lý do Thay đổi <span style="color:red;">*</span>
            </label>
            <input list="reasonListDrop" id="swal-reason" name="reason" class="swal2-input"
              placeholder="Chọn hoặc nhập lý do thay đổi..."
              style="width: 100%; margin: 0; border: 1px solid #ffc107; border-radius: 6px; font-size: 13px;" />
            <datalist id="reasonListDrop">
              ${htmlOptions}
            </datalist>
          </div>

          <div style="margin-top: 10px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; background: #fafafa;">
            <label style="display: block; font-weight: bold; margin-bottom: 8px;">Chế độ di chuyển:</label>
            <div style="display: flex; flex-direction: column; gap: 8px;">
              <label style="display: flex; align-items: center; font-weight: 500; cursor: pointer; padding: 6px 8px; border-radius: 6px; transition: background 0.2s;" id="swal-label-campaign">
                <input type="radio" name="swal-move-mode" id="swal-move-campaign" value="campaign" style="margin-right: 10px; width: 18px; height: 18px;" checked>
                Di chuyển cả chiến dịch
              </label>
              <label style="display: flex; align-items: center; font-weight: 500; cursor: pointer; padding: 6px 8px; border-radius: 6px; transition: background 0.2s;" id="swal-label-selected">
                <input type="radio" name="swal-move-mode" id="swal-move-selected" value="selected" style="margin-right: 10px; width: 18px; height: 18px;">
                Di chuyển các lô được chọn
              </label>
            </div>

            <div id="swal-selected-batches-container" style="display: none; margin-top: 10px; padding: 8px; background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; max-height: 150px; overflow-y: auto;">
              <label style="display: block; font-weight: bold; margin-bottom: 6px; font-size: 13px; color: #555;">Danh sách lô đã chọn (${currentSelectedEvents.length}):</label>
              ${selectedBatchListHtml}
            </div>
          </div>
        </div>
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Lưu',
      cancelButtonText: 'Hủy',
      didOpen: () => {
        const startInput = document.getElementById('swal-start');
        const endInput = document.getElementById('swal-end');
        const mTimeInput = document.getElementById('swal-m-time');
        const pTimeInput = document.getElementById('swal-p-time');

        const calculateEnd = () => {
          const mTime = parseFloat(mTimeInput.value) || 0;
          const pTime = parseFloat(pTimeInput.value) || 0;
          if (startInput.value && (mTime > 0 || pTime > 0)) {
            const newStart = moment(startInput.value);
            const duration = (props.first_in_campaign == 1 || props.title_clearning == "VS-II") ? (mTime + pTime) : mTime;
            const newEnd = newStart.clone().add(duration, 'hours');
            endInput.value = newEnd.format('YYYY-MM-DDTHH:mm');
          }
        };

        startInput.addEventListener('input', calculateEnd);
        mTimeInput.addEventListener('input', calculateEnd);

        // Toggle hiển thị danh sách lô khi chuyển radio
        const radioCampaign = document.getElementById('swal-move-campaign');
        const radioSelected = document.getElementById('swal-move-selected');
        const batchesContainer = document.getElementById('swal-selected-batches-container');
        const labelCampaign = document.getElementById('swal-label-campaign');
        const labelSelected = document.getElementById('swal-label-selected');

        const checkReasonRequired = () => {
          const api = calendarRef.current?.getApi();
          const allEvents = api ? api.getEvents() : [];
          const isCampaign = radioCampaign.checked;

          let hasSubmit1 = false;
          if (isCampaign) {
            const campaignEvents = allEvents.filter(e =>
              e.extendedProps.campaign_code === props.campaign_code &&
              e.extendedProps.stage_code === props.stage_code
            );
            hasSubmit1 = campaignEvents.some(e => Number(e.extendedProps.submit) === 1);
          } else {
            const selEvents = selectedEventsRef.current || [];
            hasSubmit1 = selEvents.some(sel => {
              const ev = api?.getEventById(sel.id);
              return ev && Number(ev.extendedProps.submit) === 1;
            });
          }

          const reasonContainer = document.getElementById('swal-reason-container');
          if (reasonContainer) {
            if (hasSubmit1) {
              reasonContainer.style.display = 'block';
            } else {
              reasonContainer.style.display = 'none';
              const reasonTextarea = document.getElementById('swal-reason');
              if (reasonTextarea) {
                reasonTextarea.value = '';
              }
            }
          }
        };

        const updateRadioStyle = () => {
          if (radioCampaign.checked) {
            labelCampaign.style.background = '#e8f4fd';
            labelSelected.style.background = 'transparent';
            batchesContainer.style.display = 'none';
          } else {
            labelCampaign.style.background = 'transparent';
            labelSelected.style.background = '#e8f4fd';
            batchesContainer.style.display = 'block';
          }
          checkReasonRequired();
        };

        radioCampaign.addEventListener('change', updateRadioStyle);
        radioSelected.addEventListener('change', updateRadioStyle);
        updateRadioStyle();
      },
      preConfirm: () => {
        const start = document.getElementById('swal-start').value;
        const end = document.getElementById('swal-end').value;
        const resourceId = document.getElementById('swal-resource').value;
        const moveMode = document.querySelector('input[name="swal-move-mode"]:checked').value;
        const updateCampaign = moveMode === 'campaign';
        const moveSelectedBatches = moveMode === 'selected';

        const newMTime = parseFloat(document.getElementById('swal-m-time').value) || 0;
        const pTime = parseFloat(document.getElementById('swal-p-time').value) || 0;

        const reasonContainer = document.getElementById('swal-reason-container');
        const isReasonRequired = reasonContainer && reasonContainer.style.display !== 'none';
        const reason = document.getElementById('swal-reason')?.value?.trim();

        if (!start || !end || !resourceId) {
          Swal.showValidationMessage('Vui lòng nhập đầy đủ thông tin');
          return false;
        }

        if (isReasonRequired && !reason) {
          Swal.showValidationMessage('Vui lòng nhập Lý do Thay đổi!');
          document.getElementById('swal-reason').focus();
          return false;
        }

        // Nếu chọn "Di chuyển các lô được chọn", kiểm tra các lô có cùng chiến dịch không
        if (moveSelectedBatches) {
          const selEvents = selectedEventsRef.current || [];
          if (selEvents.length === 0) {
            Swal.showValidationMessage('Chưa có lô nào được chọn. Vui lòng chọn ít nhất 1 lô trước khi sử dụng chế độ này.');
            return false;
          }

          // Kiểm tra tất cả lô có cùng plan_master_id (cùng chiến dịch) không
          // Bỏ qua các sự kiện vệ sinh (id chứa "-cleaning") khi xét cùng chiến dịch
          const mainBatchEvents = selEvents.filter(ev => !String(ev.id).includes('-cleaning'));
          const uniqueCampaigns = [...new Set(mainBatchEvents.map(ev => ev.campaign_code))];
          if (uniqueCampaigns.length > 1) {
            Swal.showValidationMessage('Các lô được chọn không cùng chiến dịch! Trong 1 lần di chuyển, chỉ được chọn các lô thuộc cùng một chiến dịch.');
            return false;
          }
        }

        return { start, end, resourceId, updateCampaign, moveSelectedBatches, reason, newMTime, pTime };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const { start, end, resourceId, updateCampaign, moveSelectedBatches, reason, newMTime, pTime } = result.value;
        const api = calendarRef.current?.getApi();
        const eventToUpdate = api.getEventById(event.id);
        const { activeStart, activeEnd } = api.view;
        const theoryHidden = JSON.parse(sessionStorage.getItem('theoryHidden'));

        let changes = [];

        if (moveSelectedBatches) {
          // Chế độ di chuyển các lô được chọn:
          // Sắp xếp các sự kiện chính theo thời gian bắt đầu cũ và xếp chúng liên tục
          let currentStart = moment(start);
          const selEvents = selectedEventsRef.current || [];

          // Tách ra: sự kiện chính (-main)
          const mainIds = new Set();
          selEvents.forEach(sel => {
            const id = String(sel.id);
            if (!id.includes('-cleaning')) {
              mainIds.add(id);
            }
          });

          // Lấy danh sách các sự kiện chính và sắp xếp
          let sortedMainEvents = [];
          mainIds.forEach(mainId => {
            const ev = api.getEventById(mainId);
            if (ev) sortedMainEvents.push(ev);
          });
          sortedMainEvents.sort((a, b) => a.start - b.start);

          const processedIds = new Set();

          sortedMainEvents.forEach(ev => {
            let evNewStart = currentStart.clone();

            // Nếu người dùng thay đổi mTime, tính lại duration cho từng sự kiện được chọn
            let durationHours;
            if (newMTime > 0) {
              durationHours = (ev.extendedProps.first_in_campaign == 1 || ev.extendedProps.title_clearning == "VS-II") ? (newMTime + pTime) : newMTime;
            } else {
              durationHours = moment(ev.end).diff(moment(ev.start), 'hours', true);
            }

            let evNewEnd = evNewStart.clone().add(durationHours, 'hours');

            changes.push({
              id: ev.id,
              start: evNewStart.format('YYYY-MM-DD HH:mm:ss'),
              end: evNewEnd.format('YYYY-MM-DD HH:mm:ss'),
              resourceId: resourceId,
              title: ev.title,
              C_end: false
            });
            processedIds.add(ev.id);

            // Tự động di chuyển sự kiện vệ sinh tương ứng (dù người dùng có chọn hay không)
            const cleaningId = ev.id.replace('-main', '-cleaning');
            const cleanEv = api.getEventById(cleaningId);

            if (cleanEv && !processedIds.has(cleaningId)) {
              // Sự kiện vệ sinh nối liền sau sự kiện chính mới
              const cleanDurationMs = moment(cleanEv.end).diff(moment(cleanEv.start));
              const cleanNewStart = evNewEnd.clone();
              const cleanNewEnd = cleanNewStart.clone().add(cleanDurationMs, 'milliseconds');

              changes.push({
                id: cleanEv.id,
                start: cleanNewStart.format('YYYY-MM-DD HH:mm:ss'),
                end: cleanNewEnd.format('YYYY-MM-DD HH:mm:ss'),
                resourceId: resourceId,
                title: cleanEv.title,
                C_end: false
              });
              processedIds.add(cleaningId);

              currentStart = cleanNewEnd.clone();
            } else {
              currentStart = evNewEnd.clone();
            }
          });
        } else {
          // Chế độ mặc định: di chuyển sự kiện đơn (và cả chiến dịch nếu được chọn)
          changes = [{
            id: event.id,
            start: moment(start).format('YYYY-MM-DD HH:mm:ss'),
            end: moment(end).format('YYYY-MM-DD HH:mm:ss'),
            resourceId: resourceId,
            title: event.title,
            C_end: false
          }];
        }

        if (isCascadeMode && eventToUpdate && !moveSelectedBatches) {
          const oldEvent = {
            start: new Date(eventToUpdate.start),
            end: new Date(eventToUpdate.end)
          };

          // Cập nhật dates cho event object để runCascadeLogic hoạt động
          eventToUpdate.setDates(start, end, { maintainDuration: false });
          // Cập nhật resource nếu thay đổi
          if (eventToUpdate.getResources()[0]?.id !== resourceId) {
            // Lưu ý: FullCalendar không có setResource trực tiếp dễ dàng như setDates
            // Nhưng trong logic cascade, chúng ta chỉ quan tâm đến cùng resource.
          }

          const cascadeUpdates = runCascadeLogic(eventToUpdate, oldEvent);

          // Format lại cascadeUpdates cho API
          const formattedCascade = cascadeUpdates.map(u => ({
            ...u,
            start: moment(u.start).format('YYYY-MM-DD HH:mm:ss'),
            end: moment(u.end).format('YYYY-MM-DD HH:mm:ss'),
            C_end: false
          }));

          changes = [...changes, ...formattedCascade];
        }

        Swal.fire({
          title: 'Đang tải...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        axios.put('/Schedual/update', {
          theory: theoryHidden,
          cascade: isCascadeMode,
          update_campaign: updateCampaign,
          move_selected_batches: moveSelectedBatches || false,
          changes: changes,
          startDate: toLocalISOString(activeStart),
          endDate: toLocalISOString(activeEnd),
          reason: { reason: reason || 'Cập nhật qua modal' },
          newMTime: newMTime,
          pTime: pTime
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

  /// Xử lý chọn 1 sự kiện -> selectedEvents
  const handleEventClick = (clickInfo) => {
    // Nếu bấm trúng nút ✏️ thì mở modal sửa nhanh và ngắt các xử lý khác (chọn event)
    if (clickInfo.jsEvent.target.closest('.edit-single-event-btn')) {
      handleEditEventClick(clickInfo.event);
      return;
    }

    const theoryHidden = JSON.parse(sessionStorage.getItem('theoryHidden'));

    const event = clickInfo.event;

    if (event.extendedProps.stage_code == 8) {
      return false;
    }

    // ALT + CLICK ghép sự kiện vệ sinh ngay sau sự kiện chính
    if (clickInfo.jsEvent.altKey && theoryHidden != 2) {

      if (!authorization) {
        clickInfo.revert();
        return false;
      }

      if (selectedEvents.length === 0) {
        return;
      }
      // Lấy instance calendar
      const calendar = clickInfo.view.calendar;

      selectedEvents.forEach(sel => {
        const mainId = sel.id;                // "28217-main"
        const cleanId = mainId.replace("-main", "-cleaning");

        const mainEvent = calendar.getEventById(mainId);
        const cleanEvent = calendar.getEventById(cleanId);

        if (!mainEvent || !cleanEvent) return;

        // đặt event vệ sinh ngay sau event chính
        const newStart = new Date(mainEvent.end);
        const duration = cleanEvent.end - cleanEvent.start;
        const newEnd = new Date(newStart.getTime() + duration);

        cleanEvent.setStart(newStart);
        cleanEvent.setEnd(newEnd);

        // trigger pending changes
        handleEventChange({ event: cleanEvent });
      });

      return;
    }


    // ALT + CLICK ghép sự kiện vệ sinh ngay sau sự kiện chính
    if (clickInfo.jsEvent.altKey && theoryHidden == 2) {


      if (userID !== 1 && userID !== 5) {
        return;
      }

      if (event._def.ui.backgroundColor != "#002af9ff") {
        alert('Chỉ Chọn Sự Kiện Đã Hoàn Thành')
        return;
      }

      if (selectedEvents.length === 0) {
        return;
      }

      // Lấy instance calendar
      const calendar = clickInfo.view.calendar;

      selectedEvents.forEach(sel => {

        const mainId = sel.id;
        const theoryId = mainId.replace("-main", "-main-theory");
        const cleanId = mainId.replace("-main", "-cleaning");
        const theoryCleanId = mainId.replace("-main", "-cleaning-theory");

        const mainEvent = calendar.getEventById(mainId);
        const theoryEvent = calendar.getEventById(theoryId);
        const cleanEvent = calendar.getEventById(cleanId);
        const theoryCleanEvent = calendar.getEventById(theoryCleanId);

        if (!mainEvent || !theoryEvent || !cleanEvent || !theoryCleanEvent) return;


        const newStart = new Date(mainEvent.start.getTime() + 15 * 60000);
        const newEnd = new Date(mainEvent.end.getTime() + 15 * 60000);

        theoryEvent.setStart(newStart);
        theoryEvent.setEnd(newEnd);
        handleEventChange({ event: theoryEvent });



        const newClearningStart = new Date(cleanEvent.start.getTime() + 15 * 60000);
        const newClearningEnd = new Date(cleanEvent.end.getTime() + 15 * 60000);

        theoryCleanEvent.setStart(newClearningStart);
        theoryCleanEvent.setEnd(newClearningEnd);

        // trigger pending changes   
        handleEventChange({ event: theoryCleanEvent });

      });

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
    // if ( clickInfo.jsEvent.ctrlKey) {
    //   setSelectedEvents([{ id: event.id, stage_code: event.extendedProps.stage_code }]); // ghi đề toạn bọ các sự kiện chỉ giử lại sự kiện cuối
    // } else {

    // }

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
    selectedEventsRef.current = [];
    setActivePlanMasterIds([]);

    // Tùy: gọi hàm un-highlight
    handleEventUnHightLine?.();
  };

  const handleEventUnHightLine = () => {
    document.querySelectorAll('.fc-event').forEach(el => el.classList.remove('highlight-event', 'highlight-current-event'));
  };

  const hasAnyRoom = (filterStr, userRoomStr) => {
    if (!filterStr || !userRoomStr) return false;

    const filterArr = filterStr
      .split(',')
      .map(r => r.trim());

    return filterArr.includes(userRoomStr.trim());
  };
  //Number(p.stage_code) === 3 &&
  const handleShowLine = (room) => {

    return plan.filter(p =>
      (
        hasAnyRoom(p.permisson_room_filter, room) &&
        Object.values(p.permisson_room || {}).length === 1
      ) ||
      p.required_room_code === room
    )
      .map(p => p.id);
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

    let selectedDates = [...offDays];

    Swal.fire({
      title: 'Sắp Lịch Tự Động',
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
          <hr/>
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
        <hr/>

        <div style="text-align:center">
          <div class="sort-option">
            <label class="sort-card">
              <input type="radio" name="sortType" value="stage" checked>
              <span> Sắp Lich Theo Công Đoạn</span>
            </label>

            <label class="sort-card">
              <input type="radio" name="sortType" value="line">
              <span> Sắp Lich Theo Line</span>
            </label>


            
          </div>
        </div>


        <div id="stepper-container" style="margin-top: 15px;"></div>

        <div id="Stage_line" class="response-date-wrap text-center" style="display:none;">
          <label class="cfg-label">Chọn Line Sắp Lịch</label>
          <select id="lines" class="swal2-input response-date-input" name="lines">
            <option value="">-- Chọn Line --</option>
          </select>
        </div>

          ${hasEmptyPermission
          ? `<p style="color:red;font-weight:600;margin-top:10px;">
                ⚠️ Một hoặc nhiều sản phẩm chưa được định mức!<br>
                Bạn cần định mức đầy đủ trước khi chạy Auto Scheduler.
              </p>`
          : ''
        }
          <hr/>
          <div class="cfg-row">
            <label class="cfg-label" for="prev_orderBy">Thứ tự công đoạn từ ĐH -> ĐG theo :</label>
            <label class="switch">
              <input id="prev_orderBy" type="checkbox" checked>
              <span class="slider round"></span>
              <span class="switch-labels">
                <span class="off">KHCĐ</span>
                <span class="on">CĐT</span>
              </span>
            </label>
          </div>

        </div>

        <!-- Cột phải -->
        <div class="cfg-card cfg-right">
          <div class="cfg-row" style="display:none;">
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

          <hr/>

          <div class="cfg-row">
            <label class="cfg-label" for="calendar-container">Ngày Không Sắp Lịch:</label>
            <div id="calendar-container" style="margin-top: 15px;"></div>
          </div>

          <hr/>

          <div class="cfg-row">
            <label class="cfg-label" for="reason">Lý do chạy Auto Scheduler:</label>
            <input id="reason" type="text"
                  class="swal2-input cfg-input cfg-input--full"
                  name="reason"
                  placeholder="Nhập lý do..."
                  required>
          </div>

          <hr/>
          <div class="cfg-row">
            <label class="cfg-label">Phân Bổ Khuôn (Auto):</label>
            <div style="display: flex; gap: 8px; margin-top: 8px;">
              <button type="button" id="btn-allocate-missing" class="btn btn-info" style="flex: 1; font-size: 13px; color: white;">Bổ sung khuôn thiếu</button>
              <button type="button" id="btn-allocate-all" class="btn btn-warning" style="flex: 1; font-size: 13px;">Làm mới & Phân bổ</button>
            </div>
          </div>
          <hr/>

          
          <div class="cfg-row">
             

              <button type="button" id="btn-backup" class="btn btn-primary mx-2">Tạo bản sao lưu</button>
              <button type="button" id="btn-restore" class="btn btn-success mx-2">Khôi phục</button>

              <div class="response-date-wrap text-center" style="display:block;">
                <label class="cfg-label">Chọn Mã bản sao lưu </label>
                <select id="retoreList" class="swal2-input response-date-input" name="bkc_code">
                  <option value="">-- Chọn mã cần khôi phục --</option>
                </select>
              </div>
          </div>


        </div>

        <!-- Cột công cụ sửa lỗi -->
        <div class="cfg-card cfg-tools" style="min-width:220px;flex:0 0 220px;display:flex;flex-direction:column">
          <h5 style="color:#c0392b;font-weight:700;margin-bottom:14px;border-bottom:2px solid #e74c3c;padding-bottom:8px;font-size:14px">
            🔧 Sửa Lỗi Tự Động
          </h5>
          <div style="display:flex;flex-direction:column;gap:12px;flex:1">

            <button type="button" id="btn-fix-overlap"
              style="background:#e67e22;color:white;border:none;border-radius:6px;padding:12px 10px;text-align:left;cursor:pointer;font-size:12px;line-height:1.5;transition:opacity .2s"
              onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
              <div style="font-weight:700;font-size:13px">📦 Sửa Lỗi Chồng Chất</div>
              <div style="opacity:.9;margin-top:3px">Quét và tịnh tuyến sự kiện chồng lấn vào vị trí trống</div>
            </button>

            <button type="button" id="btn-fix-phache-modal"
              style="background:#8e44ad;color:white;border:none;border-radius:6px;padding:12px 10px;text-align:left;cursor:pointer;font-size:12px;line-height:1.5;transition:opacity .2s"
              onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
              <div style="font-weight:700;font-size:13px">🧬 Sửa Lỗi Đen Theo Pha Chế</div>
              <div style="opacity:.9;margin-top:3px">Cố định Pha Chế (3&4), sắp xếp công đoạn khác thoát đen</div>
            </button>

          </div>
          <div style="margin-top:14px;font-size:11px;color:#7f8c8d;border-top:1px solid #eee;padding-top:10px;line-height:1.5">
            ⚠️ Sau khi sửa lỗi, nhấn <b>Đóng</b> rồi nhấn 💾 để lưu.
          </div>
        </div>

      </div>
      `,

      width: '1440px',
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

          // convert string yyyy-mm-dd -> Date
          const parseToDate = (str) => {
            const [y, m, d] = str.split("-");
            return new Date(y, m - 1, d);
          };

          // convert Date -> yyyy-mm-dd
          const formatDate = (date) => {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, "0");
            const d = String(date.getDate()).padStart(2, "0");
            return `${y}-${m}-${d}`;
          };

          // state hiển thị cho Calendar (Date[])
          const [localDates, setLocalDates] = React.useState(
            offDays.map(parseToDate)
          );



          const handleChange = (e) => {
            const dates = e.value || [];
            const selected = dates.map(formatDate);
            setLocalDates(dates);
            setOffDays(selected);
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


                {(emptyPermission == null || emptyPermission.stage_code >= 7) && (
                  <StepperPanel header="CNL">
                    <div className="flex flex-column h-12rem">
                      <div
                        className={getClass("CNL")}
                      >
                        Cân NL ➡ Cân NL Khác
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

        const radios = document.querySelectorAll('input[name="sortType"]');
        const lineWrap = document.getElementById('Stage_line');
        const stageWrap = document.getElementById('stepper-container');

        radios.forEach(radio => {
          radio.addEventListener('change', () => {
            stageWrap.style.display =
              radio.value === 'line' && radio.checked
                ? 'none'
                : 'block';

            lineWrap.style.display =
              radio.value === 'line' && radio.checked
                ? 'block'
                : 'none';
          });
        });


        // ------------- Thêm soure cho Lines ------------- //
        const linesSelect = document.getElementById("lines");
        if (allLines && allLines?.length) {
          allLines.forEach(r => {
            const opt = document.createElement("option");
            opt.value = r.code;
            opt.textContent = r.code + " - " + r.name;
            linesSelect.appendChild(opt);
          });
        }

        // ------------- Thêm soure cho Phục hồi ------------- //
        const retoreList = document.getElementById("retoreList");
        if (bkcCode && bkcCode?.length) {
          bkcCode.forEach(r => {
            const opt = document.createElement("option");
            opt.value = r.bkc_code;
            opt.textContent = r.bkc_code;
            retoreList.appendChild(opt);
          });
        }

        // ================= Allocate Mold =================
        const btnAllocateMissing = document.getElementById('btn-allocate-missing');
        const btnAllocateAll = document.getElementById('btn-allocate-all');

        const handleAllocate = (type) => {
          Swal.fire({
            title: 'Đang kiểm tra dữ liệu...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
          });

          // 1. Kiểm tra xem có sản phẩm nào thiếu định mức khuôn không
          axios.post('/Schedual/checkMissingMoldQuotas', { type })
            .then(res => {
              const missing = res.data.missing || [];

              if (missing.length > 0) {
                // Hiển thị modal cảnh báo
                let htmlList = missing.map(m => `<li>${m}</li>`).join('');
                Swal.fire({
                  title: 'Cảnh báo: Thiếu Định Mức Khuôn!',
                  html: `
                    <div style="text-align: left; font-size: 14px; margin-bottom: 10px;">
                      Các sản phẩm sau chưa được thiết lập khuôn tương thích trong hệ thống. Nếu tiếp tục, chúng sẽ bị bỏ qua:
                    </div>
                    <ul style="text-align: left; font-size: 13px; max-height: 200px; overflow-y: auto; background: #f8d7da; color: #721c24; padding: 10px 20px; border-radius: 5px;">
                      ${htmlList}
                    </ul>
                  `,
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonText: 'Vẫn tiếp tục phân bổ',
                  cancelButtonText: 'Hủy',
                  confirmButtonColor: '#e0a800',
                  cancelButtonColor: '#6c757d',
                }).then((r) => {
                  if (r.isConfirmed) {
                    proceedAllocate(type);
                  }
                });
              } else {
                // Nếu không có lỗi, tiến hành phân bổ ngay
                proceedAllocate(type);
              }
            })
            .catch(err => {
              Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: err.response?.data?.message || 'Lỗi khi kiểm tra định mức khuôn.'
              });
            });
        };

        const proceedAllocate = (type) => {
          Swal.fire({
            title: 'Đang phân bổ khuôn...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
          });

          axios.post('/Schedual/autoAllocateMold', { type })
            .then(res => {
              Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: res.data.message,
                timer: 2000,
                showConfirmButton: false
              });
              setLoading(v => !v); // reload calendar
            })
            .catch(err => {
              Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: err.response?.data?.message || err.message
              });
            });
        };

        if (btnAllocateMissing) {
          btnAllocateMissing.addEventListener('click', () => handleAllocate('missing'));
        }
        if (btnAllocateAll) {
          btnAllocateAll.addEventListener('click', () => {
            Swal.fire({
              title: 'Xác nhận?',
              text: 'Thao tác này sẽ xóa toàn bộ phân bổ cũ của các lô chưa bắt đầu và phân bổ lại từ đầu!',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Đồng ý',
              cancelButtonText: 'Hủy'
            }).then(r => {
              if (r.isConfirmed) handleAllocate('all');
            });
          });
        }

        // ================= Backup =================
        const btnBackup = document.getElementById('btn-backup');
        const bkcSelect = document.getElementById('retoreList');

        if (btnBackup) {
          btnBackup.addEventListener('click', () => {

            Swal.fire({
              title: 'Đang tạo bản sao lưu...',
              allowOutsideClick: false,
              didOpen: () => Swal.showLoading()
            });

            axios.post('/Schedual/backup_schedualer')
              .then(res => {

                const opt = document.createElement('option');
                opt.value = res.data.bkcCode;
                opt.textContent = res.data.bkcCode;
                opt.selected = true;
                bkcSelect.appendChild(opt);

                Swal.fire({
                  icon: 'success',
                  title: 'Đã sao lưu',
                  timer: 1500,
                  showConfirmButton: false
                });
              })
              .catch(err => {
                Swal.fire({
                  icon: 'error',
                  title: 'Lỗi sao lưu',
                  text: err.response?.data?.message || err.message
                });
              });


          });
        }

        // ================= Restore =================
        const btnRestore = document.getElementById('btn-restore');

        if (btnRestore) {
          btnRestore.addEventListener('click', () => {
            const bkcCode = bkcSelect.value;

            if (!bkcCode) {
              Swal.fire('Vui lòng chọn mã sao lưu!');
              return;
            }

            Swal.fire({
              title: 'Xác nhận khôi phục?',
              text: `Khôi phục theo mã: ${bkcCode}`,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Khôi phục'
            }).then(r => {
              if (!r.isConfirmed) return;

              Swal.fire({
                title: 'Đang khôi phục...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
              });

              axios.post('/Schedual/restore_schedualer', { bkc_code: bkcCode })
                .then(() => {
                  Swal.fire({
                    icon: 'success',
                    title: 'Khôi phục thành công',
                    timer: 1500,
                    showConfirmButton: false
                  });

                  setLoading(v => !v); // reload calendar
                })
                .catch(err => {
                  Swal.fire({
                    icon: 'error',
                    title: 'Khôi phục thất bại',
                    text: err.response?.data?.message || err.message
                  });
                });

            });
          });
        }

        // ================= Sửa lỗi tự động =================
        const btnFixOverlap = document.getElementById('btn-fix-overlap');
        const btnFixPhaCheModal = document.getElementById('btn-fix-phache-modal');

        if (btnFixOverlap) {
          btnFixOverlap.addEventListener('click', () => {
            Swal.close();
            setTimeout(() => handleFixOverlappingEvents(), 200);
          });
        }
        if (btnFixPhaCheModal) {
          btnFixPhaCheModal.addEventListener('click', () => {
            Swal.close();
            setTimeout(() => handleAutoFixByPhaChe(), 200);
          });
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

        const prev_orderBy = document.getElementById('prev_orderBy');
        formValues.prev_orderBy = prev_orderBy.checked;

        const runType = document.querySelector('input[name="sortType"]:checked')?.value;
        formValues.runType = runType;

        formValues.selectedDates = selectedDates;
        formValues.selectedStep = activeStepText ?? "PC";



        if (!formValues.start_date) {
          Swal.showValidationMessage('Vui lòng chọn ngày!');
          return false;
        }
        return formValues;
      },
      willClose: () => {
        const workSunday = document.getElementById('work-sunday')?.checked ?? false;
        setWorkingSunday(workSunday);
      }

    })
      .then((result) => {
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

          const endpoint = result.value.runType === 'simulate' && result.value.selectedStep != 'CNL' ? '/Schedual/simulateScheduleAll' : '/Schedual/scheduleAll';

          axios.post(endpoint, {
            ...result.value,
            startDate: toLocalISOString(activeStart),
            endDate: toLocalISOString(activeEnd),
            stage_plan_ids: handleShowLine(result.value['lines']),
            room_code: result.value['lines']

          }, { timeout: 1200000 })
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
              setLoading(!loading)

            })
            .catch(err => {

              setLoading(!loading)
              console.error("ScheduleAll error:", err.response?.data || err.message);
            });
        }
      });
  };


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
                  style="width:90%; max-width:600px; padding:5px; margin:auto; display:block;">
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

                <StepperPanel header="CNL">
                  <div className="flex flex-column h-12rem">
                    <div className={getClass("CNL")}>Cân</div>
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
      } else if (currentView.includes("Quarter")) {
        const nextIndex = (prevIndex + 1) % slotViewQuarters.length;
        calendarApi.changeView(slotViewQuarters[nextIndex]);
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

    if (draggedEvent._def.ui.backgroundColor == "#002af9ff" || draggedEvent._def.extendedProps.stage_code == 8) {
      return false;
    }

    if (userID == 1 || userID == 5) {
      return true;
    }

    if (draggedEvent.extendedProps.finished) { return false; }
    return true;
  };

  const toggleNoteModal = () => {
    setShowNoteModal(!showNoteModal)
  }

  const handleSubmit = async (e) => {

    if (!authorization) return;
    e.stopPropagation();

    // 🔹 Lấy tháng/năm hiện tại từ calendar
    const api = calendarRef.current?.getApi();
    const activeStart = api?.view?.activeStart;
    const calYear  = activeStart ? new Date(activeStart).getFullYear() : new Date().getFullYear();
    const calMonth = activeStart ? new Date(activeStart).getMonth() + 1 : new Date().getMonth() + 1;

    // 🔹 1. BẮT ĐẦU CHẶN LỖI LỊCH NGHIÊM TRỌNG
    const allEvents = api?.getEvents() || [];
    const errorEvents = [];
    // Tạm thời comment phần chặn lỗi lịch nghiêm trọng sẽ mở lại sau
    /*
    allEvents.forEach(evt => {
      const bg = (evt.backgroundColor || '').toLowerCase();
      if (bg === '#920000ff' || bg === '#e54a4aff') {
        errorEvents.push(evt);
      }
    });
    */
    const hasEventErrors = errorEvents.length > 0;

    // 🔹 2. Gọi API kiểm tra sản lượng lý thuyết
    let checkResult = null;
    try {
      const res = await axios.post('/Schedual/yield_policy/check', { year: calYear, month: calMonth });
      checkResult = res.data;
    } catch (err) {
      console.warn('Không thể kiểm tra chính sách sản lượng:', err);
    }
    const hasYieldErrors = checkResult && checkResult.can_submit === false;

    // 🔹 3. Tổng hợp và hiển thị nếu có lỗi
    if (hasEventErrors || hasYieldErrors) {
      const isTwoCols = hasEventErrors && hasYieldErrors;
      let combinedHtml = `<div style="text-align:left; display: ${isTwoCols ? 'grid' : 'block'}; grid-template-columns: ${isTwoCols ? '1fr 1fr' : '1fr'}; gap: 20px;">`;

      if (hasEventErrors) {
        const rows = errorEvents.map(evt => {
          const bg = (evt.backgroundColor || '').toLowerCase();
          const reason = bg === '#920000ff' ? 'Cảnh Báo Ngày Đáp Ứng NL/BB' : 'Không Đáp Ứng Ngày Cần Hàng Theo Kế Hoạch';
          const dateStr = evt.start ? moment(evt.start).format('DD/MM/YYYY') : '';
          return `<tr>
            <td style="padding:8px;border-bottom:1px solid #f1f5f9;text-align:left;color:#334155;">${evt.title}</td>
            <td style="padding:8px;border-bottom:1px solid #f1f5f9;text-align:center;color:#64748b;">${dateStr}</td>
            <td style="padding:8px;border-bottom:1px solid #f1f5f9;color:${bg.substring(0, 7)};font-weight:600;text-align:left;">${reason}</td>
          </tr>`;
        }).join('');

        combinedHtml += `
          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:16px; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            <p style="color:#dc2626; margin-bottom:14px; font-size:1.1rem; border-bottom:1px solid #fee2e2; padding-bottom:10px;">
              <b><i class="fas fa-exclamation-triangle"></i> Lịch vi phạm quy tắc</b>
            </p>
            <div style="max-height:280px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;">
              <table style="width:100%; border-collapse:collapse; font-size:13px;">
                 <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                   <tr>
                     <th style="padding:10px 8px;text-align:left;border-bottom:1px solid #e2e8f0;color:#475569;font-weight:600;">Tên Kế Hoạch</th>
                     <th style="padding:10px 8px;text-align:center;border-bottom:1px solid #e2e8f0;color:#475569;font-weight:600;">Ngày Bắt Đầu</th>
                     <th style="padding:10px 8px;text-align:left;border-bottom:1px solid #e2e8f0;color:#475569;font-weight:600;">Lỗi Vi Phạm</th>
                   </tr>
                 </thead>
                 <tbody>${rows}</tbody>
              </table>
            </div>
          </div>
        `;
      }

      if (hasYieldErrors) {
        const minPct = checkResult.min_submit_pct ?? 100;
        const violationRows = (checkResult.violations || [])
          .map(v => `<tr>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;color:#334155;">${v.date}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:right;color:#64748b;">${Number(v.theory_dvl).toLocaleString()}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:right;color:#64748b;">${Number(v.target_dvl).toLocaleString()}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:center;color:#dc2626;font-weight:700;">${v.pct}%</td>
          </tr>`)
          .join('');

        const monthRow = checkResult.month_pct !== null && !checkResult.month_ok
          ? `<div style="margin-top:16px;padding:12px 14px;background:#fef2f2;border-radius:6px;border:1px solid #fecaca;font-size:.9rem;">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <i class="fas fa-calendar-alt" style="color:#dc2626;font-size:1.1rem;"></i>
                <strong style="color:#991b1b;">Target cả tháng chưa đạt 100%</strong>
              </div>
              <div style="color:#b91c1c;">
                Hiện tại: <b>${Number(checkResult.total_theory).toLocaleString()}</b> / Target: <b>${Number(checkResult.target_month).toLocaleString()}</b> ĐVL
                <strong style="color:#dc2626;float:right;font-size:1.05rem;">(${checkResult.month_pct}%)</strong>
              </div>
            </div>`
          : '';

        const dailySection = violationRows
          ? `<div>
              <p style="color:#475569;margin-bottom:10px;font-size:.9rem;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-calendar-day" style="color:#7c3aed;"></i>
                <span>Các ngày làm việc chưa đạt ngưỡng <strong style="color:#7c3aed;">${minPct}%</strong> target/ngày:</span>
              </p>
              <div style="max-height:240px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;">
                <table style="width:100%;border-collapse:collapse;font-size:.84rem;">
                  <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"><tr>
                    <th style="padding:10px;text-align:left;border-bottom:1px solid #e2e8f0;color:#475569;font-weight:600;">Ngày</th>
                    <th style="padding:10px;text-align:right;border-bottom:1px solid #e2e8f0;color:#475569;font-weight:600;">SL LT (ĐVL)</th>
                    <th style="padding:10px;text-align:right;border-bottom:1px solid #e2e8f0;color:#475569;font-weight:600;">Target (ĐVL)</th>
                    <th style="padding:10px;text-align:center;border-bottom:1px solid #e2e8f0;color:#475569;font-weight:600;">% Đạt</th>
                  </tr></thead>
                  <tbody>${violationRows}</tbody>
                </table>
              </div>
            </div>`
          : '';

        combinedHtml += `
          <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:16px; box-shadow:0 2px 4px rgba(0,0,0,0.02); display:flex; flex-direction:column;">
            <p style="color:#dc2626; margin-bottom:14px; font-size:1.1rem; border-bottom:1px solid #fee2e2; padding-bottom:10px;">
              <b><i class="fas fa-chart-line"></i> Vi phạm Chính sách sản lượng</b>
            </p>
            ${dailySection}
            ${monthRow}
          </div>
        `;
      }

      combinedHtml += '</div>';

      Swal.fire({
        title: `❌ Không thể Submit — Tháng ${calMonth}/${calYear}`,
        html: combinedHtml,
        icon: 'error',
        confirmButtonText: 'Đóng',
        confirmButtonColor: '#3085d6',
        width: '80%'
      });
      return;
    }

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
        axios.put('/Schedual/submit', { submit_type: 'production' })

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

            // Reload events after submit
            handleViewChange();

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

  const handleAcceptQuanrantine = (e) => {

    if (!authorization) { return };
    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
    if (!selectedEvents || selectedEvents.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Chọn Lịch Cần Chấp Nhận',
        showConfirmButton: false,
        timer: 1000
      });
      return; // Dừng hàm ở đây
    }
    e.stopPropagation();
    Swal.fire({
      title: 'Bạn có chắc muốn chấp nhận thời gian biệt trữ hiện tại?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Chấp Nhận',
      cancelButtonText: 'Hủy',
      confirmButtonColor: 'rgba(94, 221, 51, 1)',
      cancelButtonColor: '#3085d6',

    }).then((result) => {
      if (result.isConfirmed) {
        axios.put('/Schedual/accpectQuarantine', {
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

            Swal.fire({
              icon: 'success',
              title: 'Hoàn Thành',
              showConfirmButton: false,
              timer: 1500
            });
          })

          .catch((error) => {
            Swal.fire({
              icon: 'error',
              title: 'Chấp Nhận lịch thất bại',
              text: 'Vui lòng thử lại sau.',
            });
            console.error("API error:", error.response?.data || error.message);
          });
      }

    });
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

    if (props.is_personnel) {
      const startTime = moment(event.start).format('HH:mm');
      const endTime = moment(event.end).format('HH:mm');
      const html = `
        <div class="custom-personnel-event" style="
          padding: 2px 6px; 
          font-size: 11px; 
          font-weight: bold;
          line-height: 16px; 
          white-space: nowrap; 
          overflow: hidden; 
          text-overflow: ellipsis; 
          background-color: #dbeafe; 
          color: #1e40af; 
          border: 1px solid #bfdbfe; 
          border-radius: 4px;
          height: 20px;
          display: flex;
          align-items: center;
          width: 100%;
          box-sizing: border-box;
        ">
          <span>${event.title} (${startTime} - ${endTime})</span>
        </div>
      `;
      return { html };
    }

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

    let violationBars = '';
    if (props.violation_colors && props.violation_colors.length > 0) {
      violationBars = `
        <div style="position: absolute; top: 0; bottom: 0; right: 0; display: flex; flex-direction: row; opacity: 1; z-index: 1; overflow: hidden; border-top-right-radius: 3px; border-bottom-right-radius: 3px; box-shadow: -2px 0 5px rgba(0,0,0,0.5);">
          ${props.violation_colors.map(c => `<div style="width: 4px; background-color: ${c}; height: 100%;"></div>`).join('')}
        </div>
      `;
    }

    let html = `
        <div class="relative group custom-event-content" data-event-id="${event.id}" style="${tankStyle}; padding-right: ${props.violation_colors?.length > 1 ? '6px' : '0'}; max-height: 60px; overflow: hidden;">
          ${violationBars}
          <div style="font-size:${arg.eventFontSize || 12}px; ${isTank ? 'padding: 0px;' : ''} position: relative; z-index: 2;">
            
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

            ${!props.is_clearning && props.blister_mold_code && props.stage_code == 7 ? `
              <div style="margin-top: 4px; margin-bottom: 2px;">
                <span class="px-1.5 py-0.5 rounded shadow-sm bg-[#3b82f6] text-white" style="font-size: 11px; display: inline-block;">
                  <b>${props.blister_mold_code}</b>
                </span>
              </div>
            ` : ''}
        </div>
      `;

    if (!props.is_clearning && showRenderBadge && props.campaign_code) {
      html += `
                <div 
                  class="absolute bottom-[2px] right-[2px] px-1 rounded shadow-sm bg-white/90 text-red-600 z-[20]"
                  style="font-size: 10px; line-height: 1;"
                  title="Mã Chiến dịch"
                ><b>${props.campaign_code}</b></div>`;
    }



    if (!props.is_clearning && props.mold_warning) {
      html += `
                <div 
                  class="absolute top-[-10px] left-[2px] px-1 rounded shadow-sm bg-red-600 text-white z-[20]"
                  style="font-size: 10px; line-height: 1;"
                ><b>${props.mold_warning}</b></div>`;
    }


    if (!props.is_clearning && showRenderBadge) {
      const style = getStatusStyleString(props.status);

      html += `
              <div 
                class="absolute top-[2px] right-[2px] px-1 rounded shadow-sm z-[20]"
                style="${style}; font-size: 10px; line-height: 1.2;"
                title="Trạng Thái SX"
              >
                <b>${props.status ?? ''}</b>
              </div>
            `;
    }

    if (authorization && props.finished == 0 && props.tank == 0 && props.stage_code != 8) {
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
    const base = [
      ...events,                     // event sản xuất
      ...buildOffDayEvents(offDays), // background ngày nghỉ
    ];
    if (showPersonnel) {
      return [...base, ...personnelEvents];
    }
    return base;
  }, [events, offDays, personnelEvents, showPersonnel]);

  const displayResources = useMemo(() => {
    let baseRes = resources || [];
    if (!showPersonnel) {
      baseRes = baseRes.filter(r => !r.is_personnel_sub);
    }
    if (selectedStagesFilter && selectedStagesFilter.length > 0) {
      baseRes = baseRes.filter(r => selectedStagesFilter.includes(r.stage_name));
    }
    if (selectedRoomsFilter && selectedRoomsFilter.length > 0) {
      baseRes = baseRes.filter(r => selectedRoomsFilter.includes(r.title));
    }
    return baseRes;
  }, [resources, showPersonnel, selectedStagesFilter, selectedRoomsFilter]);

  const handleConfirmClearningValidation = (e) => {
    const ids = selectedEvents.map(row =>
      Number(row.id.split('-')[0])
    );

    const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
    axios.put('/Schedual/clearningValidation', {
      ids: ids,
      startDate: toLocalISOString(activeStart),
      endDate: toLocalISOString(activeEnd)
    })
      .then(res => {
        let data = res.data;
        if (typeof data === "string") {
          data = data.replace(/^<!--.*?-->/, "").trim();
          data = JSON.parse(data);
        }
        setEvents(data.events);
        setSelectedEvents([]);
        selectedEventsRef.current = [];
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

  const handleCleaninglevelchange = (e) => {


    const hasInvalidEvent = selectedEvents.some(row => {
      const type = row.id.split('-')[1];
      // Chỉ cho phép 'cleaning' hoặc 'cleaning-theory'. Nếu là 'main' hoặc không phải cleaning thì là invalid.
      const isCleaning = type && type.includes('cleaning');
      const isFinished = row.finished == 1; // Giả sử model có field finished. Nếu không, kiểm tra id. split('-')[2] == 'theory' có thể coi là OK? 
      // User nói "event chính hoặc event đã hoàn thành"
      return !isCleaning || isFinished;
    });

    if (hasInvalidEvent) {
      Swal.fire({
        icon: 'error',
        title: 'Lỗi chọn lịch',
        text: 'Chỉ được chọn các lịch Vệ sinh chưa hoàn thành. Vui lòng kiểm tra lại.',
        timer: 3000
      });
      return;
    }

    const ids = selectedEvents.map(row => Number(row.id.split('-')[0]));




    if (ids.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Thông báo',
        text: 'Vui lòng chọn ít nhất một lịch sản xuất để thay đổi cấp vệ sinh.',
        timer: 2000
      });
      return;
    }

    Swal.fire({
      title: 'Chọn cấp vệ sinh',
      input: 'select',
      inputOptions: {
        'VS-I': 'Vệ sinh cấp I (VS-I)',
        'VS-II': 'Vệ sinh cấp II (VS-II)'
      },
      inputPlaceholder: '--- Chọn cấp vệ sinh ---',
      showCancelButton: true,
      confirmButtonText: 'Xác nhận',
      cancelButtonText: 'Hủy',
      inputValidator: (value) => {
        if (!value) {
          return 'Bạn cần chọn một cấp vệ sinh!';
        }
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const clearning_type = result.value;
        const { activeStart, activeEnd } = calendarRef.current?.getApi().view;

        axios.put('/Schedual/cleaninglevelchange', {
          ids: ids,
          startDate: toLocalISOString(activeStart),
          endDate: toLocalISOString(activeEnd),
          clearning_type: clearning_type,
        })
          .then(res => {
            let data = res.data;
            if (typeof data === "string") {
              data = data.replace(/^<!--.*?-->/, "").trim();
              data = JSON.parse(data);
            }
            setEvents(data.events);
            setSelectedEvents([]);
            selectedEventsRef.current = [];
            Swal.fire({
              icon: 'success',
              title: 'Thành công',
              text: 'Đã cập nhật cấp vệ sinh và định mức thành công.',
              timer: 1500
            });
          })
          .catch(err => {
            Swal.fire({
              icon: 'error',
              title: 'Lỗi',
              text: 'Có lỗi xảy ra khi cập nhật cấp vệ sinh.',
              timer: 2000
            });
            console.error("API error:", err.response?.data || err.message);
          });
      }
    });
  }

  // Lọc Resource dựa trên danh sách sản phẩm được chọn
  const resourceFiltered = useMemo(() => {
    if (selectedRows.length === 0) return resources;

    const validRoomCodes = new Set();
    const validRoomIds = new Set();

    selectedRows.forEach(row => {
      const perms = row.permisson_room;
      if (!perms) return;

      if (Array.isArray(perms)) {
        perms.forEach(code => validRoomCodes.add(code));
      } else if (typeof perms === "object") {
        // Trường hợp backend trả object {id: code}
        Object.values(perms).forEach(code => validRoomCodes.add(code));
        Object.keys(perms).forEach(id => validRoomIds.add(String(id)));
      } else {
        // Trường hợp id đơn lẻ
        validRoomIds.add(String(perms));
      }
    });

    return (resources || []).filter(res => {
      return validRoomCodes.has(res.code) || validRoomIds.has(String(res.id));
    });
  }, [resources, selectedRows]);

  const unsubmittedCount = useMemo(() => {
    if (!events) return 0;
    return events.filter(e => e.submit === 0 && e.finished === 0 && !e.is_clearning && e.stage_code !== 8).length;
  }, [events]);

  return (

    <div className={`transition-all duration-300 ${showSidebar ? percentShow == "30%" ? 'w-[70%]' : 'w-[85%]' : 'w-full'} float-left pt-4 pl-2 pr-2 ${isCleaningHidden ? 'hide-cleaning-events' : ''}`}>
      <style>{`
        .hide-cleaning-events .cleaning-event {
          display: none !important;
        }
        .fc-event-pending {
          box-shadow: 0 0 12px 3px rgba(250, 204, 21, 0.9) !important; /* Yellow shadow */
          border: 2px solid #eab308 !important; /* Darker yellow border */
          z-index: 10 !important; /* Ensure it stays on top of others */
        }
      `}</style>

      {/* Visual Indicator for Selected Events and Pending Changes */}
      <div className="flex gap-4 mb-2 align-items-center justify-content-between" style={{ minHeight: '20px' }}>
        <div style={{ zIndex: 10, marginLeft: '20px', display: 'flex', gap: '10px' }}>
          <MultiSelect
            value={selectedStagesFilter || stageFilterOptions.map(o => o.value)}
            options={stageFilterOptions}
            onChange={(e) => setSelectedStagesFilter(e.value)}
            optionLabel="label"
            placeholder="Lọc công đoạn"
            display="chip"
            maxSelectedLabels={2}
            className="w-full md:w-16rem"
            filter
          />
          <MultiSelect
            value={selectedRoomsFilter || roomFilterOptions.map(o => o.value)}
            options={roomFilterOptions}
            onChange={(e) => setSelectedRoomsFilter(e.value)}
            optionLabel="label"
            placeholder="Lọc phòng sản xuất"
            display="chip"
            maxSelectedLabels={2}
            className="w-full md:w-20rem"
            filter
          />
        </div>
        <div className="flex gap-4 align-items-center justify-content-end">
          {blackViolationCount > 0 && (
            <div
              className="flex align-items-center gap-2 bg-purple-100 text-purple-800 px-3 py-1 border-round-2xl shadow-1 border-1 border-purple-200 cursor-pointer"
              onClick={scrollToFirstBlackViolation}
              title="Nhấn để di chuyển đến sự kiện có lỗi chuỗi"
              style={{ cursor: 'pointer', userSelect: 'none' }}
            >
              <i className="pi pi-bolt"></i>
              <span className="font-bold text-sm">{blackViolationCount} Sự Kiện Đen</span>
            </div>
          )}
          {blackViolationCount > 0 && (
            <div
              className="flex align-items-center gap-2 bg-blue-100 text-blue-800 px-3 py-1 border-round-2xl shadow-1 border-1 border-blue-200 cursor-pointer hover:bg-blue-200 transition-colors"
              onClick={() => handleFixAllWeighingViolations(null)}
              title="Click để tự động sửa tất cả lỗi đen của công đoạn Cân"
              style={{ cursor: 'pointer', userSelect: 'none' }}
            >
              <i className="pi pi-wrench"></i>
              <span className="font-bold text-sm">Sửa lỗi Cân</span>
            </div>
          )}
          {selectedEvents && selectedEvents.length > 0 && (
            <div
              className="flex align-items-center gap-2 bg-blue-100 text-blue-800 px-3 py-1 border-round-2xl shadow-1 border-1 border-blue-200 cursor-pointer"
              onClick={scrollToSelectedEvent}
              title="Nhấn để di chuyển đến sự kiện đang chọn"
              style={{ cursor: 'pointer', userSelect: 'none' }}
            >
              <i className="pi pi-map-marker"></i>
              <span className="font-bold text-sm">{selectedEvents.length} Lô đang chọn</span>
            </div>
          )}
          {unsubmittedCount > 0 && (
            <div className="flex align-items-center gap-2 bg-red-100 text-red-800 px-3 py-1 border-round-2xl shadow-1 border-1 border-red-200">
              <i className="pi pi-exclamation-circle"></i>
              <span className="font-bold text-sm">{unsubmittedCount} Lịch chưa submit</span>
            </div>
          )}
          {pendingChanges && pendingChanges.length > 0 && (
            <div className="flex align-items-center gap-2 bg-orange-100 text-orange-800 px-3 py-1 border-round-2xl shadow-1 border-1 border-orange-200">
              <i className="pi pi-exclamation-triangle"></i>
              <span className="font-bold text-sm">{pendingChanges.length} Thay đổi chưa lưu</span>
              <i
                className="pi pi-times cursor-pointer hover:text-red-600 transition-colors ml-1"
                title="Hủy tất cả thay đổi"
                onClick={handleDiscardChanges}
              ></i>
            </div>
          )}
          {moldWarningEvents.length > 0 && (
            <div
              className="flex align-items-center gap-2 bg-yellow-100 text-yellow-800 px-3 py-1 border-round-2xl shadow-1 border-1 border-yellow-300 cursor-pointer hover:bg-yellow-200 transition-colors"
              onClick={handleNextMoldWarning}
              title="Click để lọc các phòng/thiết bị bị trùng khuôn"
            >
              <i className="pi pi-exclamation-triangle"></i>
              <span className="font-bold text-sm">{moldWarningEvents.length} Lịch trùng khuôn</span>
            </div>
          )}
        </div>
      </div>

      <FullCalendar
        schedulerLicenseKey="GPL-My-Project-Is-Open-Source"
        ref={calendarRef}
        height="calc(100vh - 130px)"
        plugins={[dayGridPlugin, resourceTimelinePlugin, interactionPlugin]}
        initialView="resourceTimelineMonth1d"
        firstDay={1}
        events={calendarEvents}
        eventResourceEditable={true}
        eventClassNames={(arg) => {
          const classes = [];
          const isCleaning = arg.event.extendedProps.is_clearning;
          const pmId = arg.event.extendedProps.plan_master_id;

          // Hiding/Showing cleaning events locally
          if (isCleaning) {
            classes.push('cleaning-event');
          }

          // Hiệu ứng bóng mờ màu vàng cho các thay đổi chưa lưu
          if (pendingChanges && pendingChanges.some(p => String(p.id) === String(arg.event.id))) {
            classes.push('fc-event-pending');
          }

          // Active Plan Master IDs focusing (logic from line 2956)
          if (activePlanMasterIds.length > 0) {
            if (activePlanMasterIds.includes(pmId)) {
              classes.push('fc-event-focus');
            } else {
              classes.push('fc-event-hidden');
            }
          }

          return classes;
        }}
        resources={displayResources}
        resourceAreaHeaderContent="Phòng Sản Xuất"

        locale="vi"
        resourceAreaWidth="250px"
        expandRows={false}

        eventsSet={(events) => {
          const count = events.filter(e => (e.backgroundColor && e.backgroundColor.toLowerCase() === '#4d4b4bff') || (e.extendedProps?.violation_colors?.includes('#4d4b4bff'))).length;
          if (blackViolationCount !== count) {
            setBlackViolationCount(count);
          }
        }}

        editable={true}
        droppable={true}
        selectable={true}
        eventResizableFromStart={true}
        eventDurationEditable={true}


        eventClick={authorization ? handleEventClick : false}
        eventResize={authorization ? handleEventChange : false}
        eventDrop={authorization ? (info) => handleGroupEventDrop(info, selectedEvents, toggleEventSelect, handleEventChange) : false}
        eventReceive={authorization ? handleEventReceive : false}
        eventMouseEnter={(info) => handleEventMouseEnter(info)}
        eventMouseLeave={(info) => handleEventMouseLeave(info)}
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

          if (res.is_personnel_sub) {
            return {
              html: `
                <div style="font-size: 11px; font-weight: bold; padding-left: 15px; color: #475569; line-height: 20px; display: flex; align-items: center; height: 20px;">
                  ${arg.resource.title}
                </div>
              `
            };
          }

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


              ${authorization ? `
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
              ` : ""}


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

            axios.put('/Schedual/change_sheet', {
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

          resourceTimelineQuarter4h: { type: 'resourceTimelineQuarter', slotDuration: '04:00:00' },
          resourceTimelineQuarter1d: { type: 'resourceTimelineQuarter', slotDuration: { days: 1 } },
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
            text: 'KHSX',
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
          hiddenClearning: {
            text: isCleaningHidden ? '👀' : '🙈',
            click: toggleCleaningEvents,
            hint: 'Ẩn/ Hiện lịch vệ sinh'
          },
          historyToggle: {
            text: showHistoryHover ? '⏳' : '📜',
            click: toggleHistoryHover,
            hint: 'Bật/Tắt chế độ hiển thị lịch sử thay đổi lịch khi hover chuột vào sự kiện'
          },
          cascadeToggle: {
            text: isCascadeMode ? '⏳' : '🔀',
            click: toggleCascadeMode,
            hint: 'Bật/Tắt chế độ tịnh tiến tất cả các sự kiện phía sau trên cùng một Phòng sản xuất'
          },

          hiddenTheory: {
            text: '🧭',
            click: toggleTheoryEvents,
            hint: 'Hiển thị lịch lý thuyết đôi với các lịch đã hoàn thành'
          },

          autoSchedualer: {
            text: '🤖',
            click: handleAutoSchedualer,
            hint: 'Sắp lịch tự động'
          },
          deleteAllScheduale: {
            text: '🗑️',
            click: handleDeleteAllScheduale,
            hint: 'Xóa lịch theo CĐ hoặc Line: chọn ngày bắt đầu xóa, chọn chế độ xóa, bấm Lưu'
          },

          changeSchedualer: {
            text: '💾',
            click: handleSaveChanges,
            hint: 'Lưu thay đổi lịch: sau khi thay đổi lịch bấm 💾 hoặc Ctrl + S để lưu thay đổi'
          },
          unSelect: {
            text: '🚫',
            click: handleDeleteScheduale,
            hint: 'Xóa lịch được chọn: Chọn các lịch cần xóa, sau đó bấm 🚫'
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

          ShowBadge: {
            text: showRenderBadge ? '❌' : '👁️',
            click: () => setShowRenderBadge(!showRenderBadge),
            hint: 'Xem các thông tin thêm như: lý do đổi màu lịch, mã chiến dịch'
          },

          Submit: {
            text: '📤',
            click: handleSubmit,
            hint: 'Submit Lịch: Sau khi hoàn thành sắp lịch để các bộ phận khác có thể thấy bấm 📤'
          },

          AcceptQuarantine: {
            text: '✅',
            click: handleAcceptQuanrantine,
            hint: 'Chấp nhận lô quá hạn biệt trữ: Chọn lịch cần chấp nhận sau đó bám nút ✅'
          },

          clearningValidation: {
            text: '🚿',
            click: handleConfirmClearningValidation,
            hint: 'Xác Định Lịch Thẩm Định Vệ Sinh: Chọn lịch cần xác định sau đó bám nút 🚿'
          },

          Cleaninglevelchange: {
            text: '🆚',
            click: handleCleaninglevelchange,
            hint: 'Thay đổi cấp vệ sinh: Chọn các lịch cần thay đổi, bấm nút 🆚 hộp thoại chọn cấp vệ sinh xuất hiện, chọn cấp vệ sinh cần thay đổi. Bấm Lưu'
          },

          togglePersonnel: {
            text: showPersonnel ? '👥' : '👥',
            click: () => setShowPersonnel(prev => !prev),
            hint: 'Ẩn/Hiện phân công nhân sự tại từng phòng'
          },

          // autoFixPhaChe: {
          //   text: '🧬',
          //   click: handleAutoFixByPhaChe,
          //   hint: 'Tự Động Sửa Lỗi Đen Theo Pha Chế: Cố định giai đoạn Pha Chế (3 & 4), tự động kéo/đẩy các công đoạn khác để không còn lỗi đen'
          // },

          detailToggle: {
            text: showDetailHover ? '❌' : '🔍',
            click: () => setShowDetailHover(!showDetailHover),
            hint: 'Bật/tắt chế độ hiển thị chi tiết lịch khi di chuột'
          }
        }}

        headerToolbar={{
          left: 'customPre,myToday,customNext noteModal hiddenClearning hiddenTheory cascadeToggle historyToggle detailToggle autoSchedualer deleteAllScheduale changeSchedualer unSelect ShowBadge AcceptQuarantine clearningValidation Cleaninglevelchange togglePersonnel',
          center: 'title',
          right: 'Submit fontSizeBox searchBox slotDuration customDay,customWeek,customMonth,customQuarter customList' //customYear
        }}



        eventDidMount={(info) => {
          // Xử lý nút sửa nhanh đơn lẻ
          const editBtn = info.el.querySelector('.edit-single-event-btn');
          if (editBtn) {
            editBtn.addEventListener('click', (e) => {
              // e.stopPropagation(); // FullCalendar's eventClick might still trigger
              handleEditEventClick(info.event);
            });
          }

          // gắn data-event-id và data-plan_master_id để truy xuất nhanh
          info.el.setAttribute("data-event-id", info.event.id);
          info.el.setAttribute("data-stage_code", info.event.extendedProps.stage_code);
          info.el.setAttribute("data-plan_master_id", info.event.extendedProps.plan_master_id);

          // cho select evetn => pendingChanges
          const isPending = pendingChanges.some(e => e.id === info.event.id);
          if (isPending) {
            info.el.style.border = '2px dashed orange';
          }

          info.el.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            // Náº¿u chÆ°a select gÃ¬ thÃ¬ hÃ¬nh nhÆ° event mÃ¬nh click pháº£i dÃ¹ng lÃ m target
            setContextMenuInfo({
              visible: true,
              x: e.clientX,
              y: e.clientY,
              event: info.event
            });
          });

          info.el.addEventListener("dblclick", (e) => {
            e.stopPropagation();
            if (!e.ctrlKey) {
              handleEditEventClick(info.event);
              return;
            }

            const currentPm = info.event.extendedProps.plan_master_id;

            setActivePlanMasterIds(prev => {
              const currentSelected = selectedEventsRef.current || [];
              // Nếu đã chọn nhiều sự kiện, lấy ALL plan_master_id từ chúng
              if (currentSelected.length > 0) {
                const uniquePms = [...new Set(currentSelected.map(ev => ev.plan_master_id))];

                // Toggle: Nếu mảng hiện tại khớp hoàn toàn với uniquePms của nhóm vừa chọn -> reset
                const isCurrentGroup = prev.length === uniquePms.length && uniquePms.every(id => prev.includes(id));
                return isCurrentGroup ? [] : uniquePms;
              }

              // Nếu không có selection, xử lý toggle cho riêng dòng click (tương tự logic cũ)
              if (prev.length === 1 && prev[0] === currentPm) {
                return [];
              }
              return [currentPm];
            });
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
          production={production}
          currentPassword={currentPassword}
          lines={lines}
          multiStage={multiStage}
          setMultiStage={setMultiStage}
          excludeMaintenance={true}
        />)}

      {/* Modal / Overlay hiển thị lịch sử */}
      {/* Modal / Dialog hiển thị lịch sử thay đổi */}
      <Dialog
        visible={showHistoryDialog}
        onHide={() => setShowHistoryDialog(false)}
        style={{ width: 'min(1100px, 95vw)', maxHeight: '70vh' }}
        contentStyle={{ maxHeight: '55vh', overflowY: 'auto' }}
        className="history-dialog"
        header={
          <div className="d-flex align-items-center justify-content-between w-100 pr-4">
            <span className="d-flex align-items-center font-bold text-lg text-slate-800">
              <i className="pi pi-history mr-2 text-primary" style={{ fontSize: '1.2rem' }}></i>
              Lịch Sử Thay Đổi Lịch Sản Xuất
            </span>
            {loadingHistory && <i className="pi pi-spin pi-spinner ml-2 text-primary"></i>}
          </div>
        }
        modal={false}
        draggable={true}
        resizable={true}
        keepInViewport={true}
        focusOnShow={false}
        closeOnEscape={true}
      >
        <DataTable
          value={historyData}
          loading={loadingHistory}
          className="history-table p-datatable-sm"
          emptyMessage="Không có dữ liệu lịch sử thay đổi"
          stripedRows
        >
          <Column header="STT" body={(rowData, options) => <span className="font-semibold text-slate-500">{options.rowIndex + 1}</span>} style={{ width: '50px', textAlign: 'center' }} />
          <Column field="version" header="Bản" body={(row, options) => (
            <span className={`version-badge ${options.rowIndex === 0 ? 'version-badge-current' : 'version-badge-old'}`}>
              V{row.version} {options.rowIndex === 0 ? '★' : ''}
            </span>
          )} style={{ width: '110px' }} />
          <Column field="product_name" header="Sản phẩm" style={{ minWidth: '160px' }} />
          <Column field="batch" header="Số lô" style={{ width: '90px' }} />
          <Column field="start" header="Bắt đầu" body={(row) => <span className="text-slate-600 font-medium">{moment(row.start).format('DD/MM/YYYY HH:mm')}</span>} style={{ width: '140px' }} />
          <Column field="end" header="Kết thúc" body={(row) => <span className="text-slate-600 font-medium">{moment(row.end).format('DD/MM/YYYY HH:mm')}</span>} style={{ width: '140px' }} />
          <Column field="schedualed_at" header="Ngày tạo" body={(row) => <span className="text-slate-500">{row.schedualed_at ? moment(row.schedualed_at).format('DD/MM/YYYY HH:mm') : '-'}</span>} style={{ width: '140px' }} />
          <Column field="schedualed_by" header="Người tạo" body={(row) => <span className="font-semibold text-slate-700">{row.schedualed_by || '-'}</span>} style={{ width: '120px' }} />
          <Column field="type_of_change" header="Lý do thay đổi" body={(row) => row.type_of_change ? (
            <span className="change-reason-badge">{row.type_of_change}</span>
          ) : '-'} style={{ minWidth: '150px' }} />
        </DataTable>
      </Dialog>

      {/* Modal hiển thị chi tiết lịch khi hover (Tooltip) */}
      {hoverDetailData && (
        <div
          style={{
            position: 'fixed',
            top: hoverDetailData.y + 15 > window.innerHeight - 250 ? window.innerHeight - 250 : hoverDetailData.y + 15,
            left: hoverDetailData.x + 15 > window.innerWidth - 350 ? hoverDetailData.x - 365 : hoverDetailData.x + 15,
            zIndex: 9999,
            width: '350px',
            backgroundColor: '#ffffff',
            borderRadius: '8px',
            boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
            padding: '16px',
            border: '1px solid #e2e8f0',
            pointerEvents: 'none'
          }}
        >
          <div className="flex items-center mb-3 pb-2 border-b border-gray-100">
            <i className="pi pi-info-circle mr-2 text-blue-600" style={{ fontSize: '1.2rem' }}></i>
            <span className="font-bold text-slate-800 text-base">Chi Tiết Lịch</span>
          </div>
          <div className="flex flex-col gap-2 text-sm text-slate-700">
            <div><strong className="text-blue-700">Sản phẩm:</strong> {hoverDetailData.props.product_name || hoverDetailData.event.title}</div>
            {(hoverDetailData.props.batch_name || hoverDetailData.props.actual_batch) && (
              <div><strong className="text-blue-700">Lô:</strong> {hoverDetailData.props.actual_batch || hoverDetailData.props.batch_name}</div>
            )}
            <div><strong className="text-blue-700">Thời gian:</strong> {moment(hoverDetailData.event.start).format('DD/MM/YYYY HH:mm')} - {moment(hoverDetailData.event.end).format('DD/MM/YYYY HH:mm')}</div>
            {hoverDetailData.props.status && <div><strong className="text-blue-700">Trạng thái:</strong> {hoverDetailData.props.status}</div>}
            {hoverDetailData.props.campaign_code && <div><strong className="text-blue-700">Mã Chiến dịch:</strong> <span className="text-red-600 font-bold">{hoverDetailData.props.campaign_code}</span></div>}
            {hoverDetailData.props.blister_mold_code && <div><strong className="text-blue-700">Khuôn:</strong> {hoverDetailData.props.blister_mold_code}</div>}
            {hoverDetailData.props.mold_warning && <div className="text-red-600 font-bold mt-1">⚠️ {hoverDetailData.props.mold_warning}</div>}
            {hoverDetailData.props.subtitle && <div className="text-amber-600 font-semibold mt-1 whitespace-pre-line">{hoverDetailData.props.subtitle}</div>}
          </div>
        </div>
      )}


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
            const newlySelected = e.selected.map((el) => ({
              id: el.getAttribute("data-event-id"),
              stage_code: el.getAttribute("data-stage_code"),
              plan_master_id: el.getAttribute("data-plan_master_id"),
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

              selectedEventsRef.current = merged;
              return merged;
            });
          }}
        />
      )}

      {contextMenuInfo.visible && (
        <div
          style={{
            position: 'fixed',
            top: contextMenuInfo.y,
            left: contextMenuInfo.x,
            zIndex: 9999,
            background: 'white',
            border: '1px solid #ccc',
            boxShadow: '0 2px 5px rgba(0,0,0,0.2)',
            padding: '5px',
            borderRadius: '4px'
          }}
          onMouseLeave={() => setContextMenuInfo({ ...contextMenuInfo, visible: false })}
        >
          <div
            style={{ padding: '8px 12px', cursor: 'pointer', whiteSpace: 'nowrap' }}
            onClick={(e) => {
              e.stopPropagation();
              handleSmartRippleShift(contextMenuInfo.event);
              setContextMenuInfo({ ...contextMenuInfo, visible: false });
            }}
            onMouseEnter={(e) => e.target.style.background = '#f0f0f0'}
            onMouseLeave={(e) => e.target.style.background = 'white'}
          >
            Tự động điều chỉnh lỗi đen
          </div>

          {/* <div
            style={{ padding: '8px 12px', cursor: 'pointer', whiteSpace: 'nowrap', borderTop: '1px solid #eee' }}
            onClick={(e) => {
              e.stopPropagation();
              handleFixAllWeighingViolations(contextMenuInfo.event);
              setContextMenuInfo({ ...contextMenuInfo, visible: false });
            }}
            onMouseEnter={(e) => e.target.style.background = '#f0f0f0'}
            onMouseLeave={(e) => e.target.style.background = 'white'}
          >
            Sửa lỗi đen công đoạn Cân
          </div> */}

          <div
            style={{ padding: '8px 12px', cursor: 'pointer', whiteSpace: 'nowrap', borderTop: '1px solid #eee' }}
            onClick={(e) => {
              e.stopPropagation();
              handlePreviewChain(contextMenuInfo.event);
              setContextMenuInfo({ ...contextMenuInfo, visible: false });
            }}
            onMouseEnter={(e) => e.target.style.background = '#f0f0f0'}
            onMouseLeave={(e) => e.target.style.background = 'white'}
          >
            Xem trước chuỗi
          </div>
        </div>
      )}

    </div>


  );
};

export default ScheduleTest;
