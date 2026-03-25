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
  const slotViewWeeks = ['resourceTimelineWeek1day', 'resourceTimelineWeek4h', 'resourceTimelineWeek1h', 'resourceTimelineWeek15'];
  const slotViewMonths = ['resourceTimelineMonth1d', 'resourceTimelineMonth4h', 'resourceTimelineMonth1h',];
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
  const [resources, setResources] = useState([]);
  const [sumBatchByStage, setSumBatchByStage] = useState([]);
  const [plan, setPlan] = useState([]);
  const [quota, setQuota] = useState([]);
  const [stageMap, setStageMap] = useState({});
  const [type, setType] = useState(true);
  const [loading, setLoading] = useState(false);
  const [authorization, setAuthorization] = useState(false);
  const [heightResource, setHeightResource] = useState("1px");
  const [reasons, setReasons] = useState([]);
  const [bkcCode, setBkcCode] = useState([]);
  const [lines, setLines] = useState(['S16']);
  const [allLines, setAllLines] = useState([]);
  const [currentPassword, setCurrentPassword] = useState(null);
  const [userID, setUserID] = useState(null);

  const [activePlanMasterId, setActivePlanMasterId] = useState(null);
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
        //console.log (data.events)
        setEvents(data.events);
        setResources(data.resources);
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
        clearning: cleaningHidden,
        theory: theoryHidden,
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
    } finally {

    }
  }, []);

  const toggleCleaningEvents = () => {
    const current = JSON.parse(sessionStorage.getItem('cleaningHidden'));
    const newHidden = !current;
    sessionStorage.setItem('cleaningHidden', JSON.stringify(newHidden));

    handleViewChange(null, null);
  };

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

    console.log(slotDuration)
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
          if (data.resources) setResources(data.resources);
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
          let newEnd = null;

          // Kiêm tra điều chinh đinh mức ngày chủ nhật
          if (!workingSunday) {
            let process_code = event._def.extendedProps.process_code + "_" + event._def.resourceIds[0]
            let stage_code = event._def.extendedProps.stage_code
            let is_clearning = event._def.extendedProps.is_clearning
            let quota_event = quota.find(q =>
              q.process_code.startsWith(process_code) &&
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

            // if (isInSundayToMondayWindow (newEnd)){
            //   newEnd = new Date(event_start + offset + quota_event_m_time_seconds + 86400000)
            // }

          } else {
            newEnd = new Date(event.end.getTime() + offset);
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
    axios.put('/Schedual/update', {
      reason, // 🟢 gửi thêm lý do
      theory: theoryHidden,
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
  const selectedEventsRef = useRef([]);
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
      selectedEventsRef.current = newSelected;
      return newSelected;
    });
  };

  /// Xử lý chọn 1 sự kiện -> selectedEvents
  const handleEventClick = (clickInfo) => {

    const theoryHidden = JSON.parse(sessionStorage.getItem('theoryHidden'));

    const event = clickInfo.event;

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
      title: 'Cấu Hình Chung Sắp Lịch',
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
              <input id="prev_orderBy" type="checkbox">
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

          
          <div class="cfg-row">
             

              <button id="btn-backup" class="btn btn-primary mx-2">Tạo bản sao lưu</button>
              <button id="btn-restore" class="btn btn-success mx-2">Khôi phục</button>

              <div class="response-date-wrap text-center" style="display:block;">
                <label class="cfg-label">Chọn Mã bản sao lưu </label>
                <select id="retoreList" class="swal2-input response-date-input" name="bkc_code">
                  <option value="">-- Chọn mã cần khôi phục --</option>
                </select>
              </div>
          </div>



        </div>
      </div>
      `,

      width: '1200px',
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

          axios.post('/Schedual/scheduleAll', {
            ...result.value,
            startDate: toLocalISOString(activeStart),
            endDate: toLocalISOString(activeEnd),
            stage_plan_ids: handleShowLine(result.value['lines']),
            room_code: result.value['lines']

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
              setLoading(!loading)

            })
            .catch(err => {

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
      }
    });
  };

  /// Xử lý format số thập phân
  const formatNumberWithComma = (x) => {
    if (x == null) return "0";
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  const finisedEvent = (dropInfo, draggedEvent) => {

    if (draggedEvent._def.ui.backgroundColor == "#002af9ff") {
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
    const isTimelineMonth = arg.view.type === 'resourceTimelineMonth';

    if (event.title == undefined) {
      console.log(event)
    }

    let html = `
      <div class="relative group custom-event-content" data-event-id="${event.id}">
        <div style="font-size:${arg.eventFontSize || 12}px;">
          
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
      ...events,                     // event sản xuất
      ...buildOffDayEvents(offDays), // background ngày nghỉ
    ];
  }, [events, offDays]);

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

  return (

    <div className={`transition-all duration-300 ${showSidebar ? percentShow == "30%" ? 'w-[70%]' : 'w-[85%]' : 'w-full'} float-left pt-4 pl-2 pr-2`}>
      <FullCalendar
        schedulerLicenseKey="GPL-My-Project-Is-Open-Source"
        ref={calendarRef}
        plugins={[dayGridPlugin, resourceTimelinePlugin, interactionPlugin]}
        initialView="resourceTimelineMonth1d"
        firstDay={1}
        events={calendarEvents}
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

        //slotDuration="01:00:00"
        eventDurationEditable={true}


        eventClick={authorization ? handleEventClick : false}
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

        headerToolbar={{
          left: 'customPre,myToday,customNext noteModal hiddenClearning hiddenTheory autoSchedualer deleteAllScheduale changeSchedualer unSelect ShowBadge AcceptQuarantine clearningValidation Cleaninglevelchange',
          center: 'title',
          right: 'Submit fontSizeBox searchBox slotDuration customDay,customWeek,customMonth,customQuarter customList' //customYear
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
            text: '🙈',
            click: toggleCleaningEvents,
            hint: 'Ẩn/ Hiện lịch vệ sinh'
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
            text: '♻️',
            click: handleSaveChanges,
            hint: 'Lưu thay đổi lịch: sau khi thay đổi lịch bấm ♻️ hoặc Ctrl + S để lưu thay đổi'
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
            text: '👁️',
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


        }}

        //eventClassNames={(arg) => arg.event.extendedProps.isHighlighted ? ['highlight-event'] : []}
        eventClassNames={(arg) => {

          const pm = arg.event.extendedProps.plan_master_id;

          if (!activePlanMasterId) return [];

          if (pm === activePlanMasterId) {
            return ['fc-event-focus'];
          }

          return ['fc-event-hidden'];
        }}


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
            if (!e.ctrlKey) return;
            //handleEventHighlightGroup(info.event, e.ctrlKey || e.metaKey);
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
          currentPassword={currentPassword}
          lines={lines}
          multiStage={multiStage}
          setMultiStage={setMultiStage}
          excludeMaintenance={true}
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

              return merged;
            });
          }}
        />
      )}
    </div>


  );
};

export default ScheduleTest;

