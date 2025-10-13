import React, { useRef, useState, useEffect } from 'react';

import '@fullcalendar/daygrid/index.js';
import '@fullcalendar/resource-timeline/index.js';
import ReactDOM from 'react-dom/client';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';

import axios from "axios";
import 'moment/locale/vi';

import moment from 'moment';
import Selecto from "react-selecto";
import Swal from 'sweetalert2';

import './calendar.css';
import CalendarSearchBox from '../Components/CalendarSearchBox';
import EventFontSizeInput from '../Components/EventFontSizeInput';
import ModalSidebar from '../Components/ModalSidebar';
import NoteModal from '../Components/NoteModal';
import History from '../Components/History';
import {CheckAuthorization} from '../Components/CheckAuthorization';
import dayjs from 'dayjs';

  const ScheduleTest = () => {

    const calendarRef = useRef(null);
    moment.locale('vi');
    const [showSidebar, setShowSidebar] = useState(false);
    const [viewConfig, setViewConfig] = useState({timeView: 'resourceTimelineWeek', slotDuration: '00:15:00', is_clearning: true});
    const [cleaningHidden, setCleaningHidden] = useState(false);
    const [pendingChanges, setPendingChanges] = useState([]);
    const [saving, setSaving] = useState(false);
    const [selectedEvents, setSelectedEvents] = useState([]);
    const [percentShow, setPercentShow] = useState("100%");
    const searchResultsRef = useRef([]);
    const currentIndexRef = useRef(-1);
    const lastQueryRef = useRef("");
    const slotViewWeeks = ['resourceTimelineWeek15', 'resourceTimelineWeek30', 'resourceTimelineWeek60','resourceTimelineWeek4h'];
    const slotViewMonths = ['resourceTimelineMonth1h', 'resourceTimelineMonth4h', 'resourceTimelineMonth1d'];
    const [slotIndex, setSlotIndex] = useState(0);
    const [eventFontSize, setEventFontSize] = useState(14); // default 14px
    const [selectedRows, setSelectedRows] = useState([]);
    const [showNoteModal, setShowNoteModal] = useState(false);
    const [showHistoryModal, setShowHistoryModal] = useState(false);
    const [viewName, setViewName] = useState("resourceTimelineWeek");

    const [events, setEvents] = useState([]);
    const [resources, setResources] = useState([]);
    const [sumBatchByStage, setSumBatchByStage] = useState([]);
    const [plan, setPlan] = useState([]);
    const [quota, setQuota] = useState([]);
    const [stageMap, setStageMap] = useState({});
    const [historyData, setHistoryData] = useState([]);
    const [type, setType] = useState(true);
    const [loading, setLoading] = useState(false);
    const [authorization, setAuthorization] = useState(false);
   

    /// Get dữ liệu ban đầu
    useEffect(() => {
      // const calendarApi = calendarRef.current?.getApi();
      // if (!calendarApi) return;

      Swal.fire({
        title: "Đang tải...",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
      //console.log (activeStart.toISOString(), activeEnd.toISOString())
      axios.post("/Schedual/view", {
          startDate: activeStart.toISOString(),
          endDate: activeEnd.toISOString(),
          viewtype: viewName,
      })
        .then(res => {
          
          let data = res.data;

          if (typeof data === "string") {
            data = data.replace(/^<!--.*?-->/, "").trim();
            data = JSON.parse(data);
          }
          setEvents(data.events);
          setResources(data.resources);
          setType (data.type)
          setAuthorization (data.authorization)
          setPlan(data.plan);
          setQuota(data.quota);
          setStageMap(data.stageMap);
          setSumBatchByStage(data.sumBatchByStage);
     

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

      new Draggable(document.getElementById('external-events'), {

        itemSelector: '.fc-event',
        eventData: (eventEl) => {

          // Lấy selectedRows mới nhất từ state
          const draggedData = selectedRows.length ? selectedRows : [];
          //console.log (draggedData);
          return {
            title: draggedData.length > 1 ? `(${draggedData.length}) sản phẩm` : draggedData[0]?.product_code || 'Trống',
            extendedProps: { rows: draggedData },
          };
        },
      });
    }, []);

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
      if (!CheckAuthorization(authorization, ['Admin', 'Schedualer'])) return;
      setShowSidebar(true);
    }

    ///  Thay đôi khung thời gian
    const handleViewChange = (view) => {
      
      Swal.fire({
        title: "Đang tải...",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });
      

      setViewConfig({ is_clearning: false, timeView: view });
      calendarRef.current?.getApi()?.changeView(view)
      const { activeStart, activeEnd } = calendarRef.current?.getApi().view;

      setViewName (view)
      axios.post(`/Schedual/view`, {
          startDate: activeStart.toISOString(),
          endDate: activeEnd.toISOString() ,
          viewtype: view
        })
        .then(res => {
          let data = res.data;
          // Trường hợp response trả về có HTML thừa (ví dụ: <!-- -->)
          if (typeof data === "string") {
            data = data.replace(/^<!--.*?-->/, "").trim();
            data = JSON.parse(data);
          }
          // Chỉ update các state cần thiết (giống `only: ['resources','sumBatchByStage']`)
          setEvents(data.events);
          setResources(data.resources);
          

          setTimeout(() => {
            Swal.close();
          }, 500);

        })
        .catch(err => {
          console.error("API error:", err.response?.data || err.message);
          Swal.fire({
            icon: 'error',
            title: 'Có lỗi xảy ra',
            text: 'Vui lòng thử lại sau.',
          });
        });
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

    /// Bỏ tô màu các event trùng khớp
    const handleEventUnHightLine = async (info) => {
        document.querySelectorAll('.fc-event').forEach(el => {
        el.classList.remove('highlight-event');
      });
    };

    // Nhân Dữ liệu để tạo mới event
    const handleEventReceive = (info) => {
      // chưa chọn row
      const start = info.event.start;
      const now = new Date();
      const resourceId = info.event.getResources?.()[0]?.id ?? null;
      info.event.remove();

      if (selectedRows.length === 0 ){
          Swal.fire({
            icon: 'warning',
            title:'Vui Lòng Chọn Sản Phẩm Muốn Sắp Lịch',
              timer: 1000,
              showConfirmButton: false,
            });
          return false
      }
      // chưa định mức
      if (selectedRows[0].permisson_room.length == 0 && selectedRows[0].stage_code !== 9){
          Swal.fire({
            icon: 'warning',
            title:'Sản Phẩm Chưa Được Định Mức',
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

      if (start <= now){
        Swal.fire({
          icon: "warning",
          title: "Thời gian bắt đầu nhỏ hơn thời gian hiện tại!",
          timer: 1000,
          showConfirmButton: false,
        });
          return false;
      }

      const { activeStart, activeEnd } = calendarRef.current?.getApi().view;

      if (selectedRows[0].stage_code !== 8){
          axios.put('/Schedual/store', {
              room_id: resourceId,
              stage_code: selectedRows[0].stage_codes,
              start: moment(start).format("YYYY-MM-DD HH:mm:ss"),
              products: selectedRows,
              startDate: activeStart.toISOString(),
              endDate: activeEnd.toISOString()
          })
          .then(res => {
              let data = res.data;
              if (typeof data === "string") {
                data = data.replace(/^<!--.*?-->/, "").trim();
                data = JSON.parse(data);
              }
              console.log (data);
              setEvents(data.events);
              setResources(data.resources);
              setSumBatchByStage(data.sumBatchByStage);
              setPlan(data.plan);

              setSelectedRows([]);
          })
          .catch(err => {
              console.error("Lỗi tạo lịch:", err.response?.data || err.message);
          });
      }else if (selectedRows[0].stage_code == 8){

            axios.put('/Schedual/store_maintenance', {
                stage_code: 8,
                start: moment(start).format("YYYY-MM-DD HH:mm:ss"),
                products: selectedRows,
                is_HVAC: selectedRows[0]?.is_HVAC ?? false,
                startDate: activeStart.toISOString(),
                endDate: activeEnd.toISOString()
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

    /// Ẩn hiện sự kiện vệ sinh
    const toggleCleaningEvents = () => {
      const calendarApi = calendarRef.current?.getApi();
      if (!calendarApi) return;

      Swal.fire({
        title: cleaningHidden ? "Hiển thị sự kiện vệ sinh..." : "Ẩn sự kiện vệ sinh...",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      setTimeout(() => {
        const view = calendarApi.view?.type;

        calendarApi.getEvents().forEach(event => {
          if (event.extendedProps.is_clearning) {
            const els = document.querySelectorAll(`[data-event-id="${event.id}"]`);
            els.forEach(el => {
              el.style.display = cleaningHidden ? "" : "none";
            });
          }
        });

        setCleaningHidden(!cleaningHidden);

        Swal.close();
      }, 300); // delay 300ms để thấy loading
    };

    /// 3 Ham sử lý thay đôi sự kiện
    const handleGroupEventDrop = (info, selectedEvents, toggleEventSelect, handleEventChange) => {

      if (!CheckAuthorization(authorization, ['Admin', 'Schedualer'])) {
        info.revert();
        return false
      } ;

      const draggedEvent = info.event;
      const delta = info.delta;
      const calendarApi = info.view.calendar;

      // Nếu chưa được chọn thì tự động chọn
      if (!selectedEvents.some(ev => ev.id === draggedEvent.id)) {
        toggleEventSelect(draggedEvent);
      }

      // Nếu đã chọn thì xử lý nhóm
      if (selectedEvents.some(ev => ev.id === draggedEvent.id)) {
        info.revert();

        selectedEvents.forEach(sel => {
          const event = calendarApi.getEventById(sel.id);
          if (event) {


            const newStart = new Date(
                event.start.getTime() +
                delta.milliseconds +
                delta.days * 24 * 60 * 60 * 1000
              );

              const newEnd = new Date(
                event.end.getTime() +
                delta.milliseconds +
                delta.days * 24 * 60 * 60 * 1000
              );
            event.setDates(newStart, newEnd);

            handleEventChange({ event });
          }
        });
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

      if (!CheckAuthorization(authorization, ['Admin', 'Schedualer'])) {
        info.revert();
        return false
      } ;

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
      const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
      setSaving(true);

      axios.put('/Schedual/update', {
          changes: pendingChanges.map(change => ({
              id: change.id,
              start: dayjs(change.start).format('YYYY-MM-DD HH:mm:ss'),
              end: dayjs(change.end).format('YYYY-MM-DD HH:mm:ss'),
              resourceId: change.resourceId,
              title: change.title,
              C_end: change.C_end || false
          })),
          startDate: activeStart.toISOString(),
          endDate: activeEnd.toISOString()
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
          Swal.fire({
              icon: 'success',
              title: 'Thành công!',
              text: 'Đã lưu tất cả thay đổi.',
              timer: 1000,
              showConfirmButton: false,
          });
          setSaving(false);
          setPendingChanges([]);
      })
      .catch(err => {
       console.error("Lỗi khi lưu events:", err.response?.data || err.message);
      });
    };

    /// Xử lý Toggle sự kiện đang chọn: if đã chọn thì bỏ ra --> selectedEvents
    const toggleEventSelect = (event) => {
      setSelectedEvents((prevSelected) => {
        const exists = prevSelected.some(ev => ev.id === event.id);
        return exists
          ? prevSelected.filter(ev => ev.id !== event.id)
          : [...prevSelected, { id: event.id, stage_code: event.extendedProps.stage_code }];
      });
    };

    /// Xử lý chọn 1 sự kiện -> selectedEvents
    const handleEventClick = (clickInfo) => {
      const event = clickInfo.event;
      if (clickInfo.jsEvent.shiftKey || clickInfo.jsEvent.ctrlKey || clickInfo.jsEvent.metaKey) {
        setSelectedEvents([{ id: event.id, stage_code: event.extendedProps.stage_code }]); // ghi đề toạn bọ các sự kiện chỉ giử lại sự kiện cuối
      } else {
        toggleEventSelect(event);
      }

    };

    /// bỏ chọn tất cả sự kiện đã chọn ở select sidebar -->  selectedEvents
    const handleClear = () => {setSelectedEvents([]);};

    /// Xử lý Chạy Lịch Tư Động
    const handleAutoSchedualer = () => {

      if (!CheckAuthorization(authorization, ['Admin', 'Schedualer'])) return;

      Swal.fire({
        title: 'Cấu Hình Chung Sắp Lịch',
        html: `
          <div class="cfg-wrapper">
            <div class="cfg-card">
              <!-- Hàng Ngày chạy -->
              <div class="cfg-row cfg-grid-2">
               <div class="cfg-col">
                <label class="cfg-label" for="schedule-date">Ngày chạy bắt đầu sắp lịch:</label>
                <input id="schedule-date" type="date"
                      class="swal2-input cfg-input cfg-input--half"  name = "start_date"
                      value="${new Date().toISOString().split('T')[0]}">
                </div>

                <div class="cfg-col">
                <label class="cfg-label" for="schedule-date">Thời Gian Đệm (ngày):</label>
                <input id="buffer_date" type="number"  class="swal2-input cfg-input cfg-input--full" min = "0" value = "3" name = "buffer_date">
                </div>

              </div>

              <!-- Hàng 2 cột -->
              <label class="cfg-label" >Thời Gian Chờ Kết Quả Kiểm Nghiệm (ngày)</label>
              <div class="cfg-row cfg-grid-2">
                <div class="cfg-col">
                  <label class="cfg-label" for="wt_bleding">Trộn Hoàn Tất Lô Thẩm Định</label>
                  <input id="wt_bleding" type="number" class="swal2-input cfg-input cfg-input--full" min = "0" value = "5" name = "wt_bleding_val">
                  <label class="cfg-label" for="wt_forming">Định Hình Lô Thẩm Định</label>
                  <input id="wt_forming" type="number" class="swal2-input cfg-input cfg-input--full" min = "0" value = "5" name = "wt_forming_val">
                  <label class="cfg-label" for="wt_coating">Bao Phim Lô Thẩm Định</label>
                  <input id="wt_coating" type="number" class="swal2-input cfg-input cfg-input--full" min = "0" value = "5" name = "wt_coating_val">
                  <label class="cfg-label" for="wt_blitering">Đóng Gói Lô Thẩm Định</label>
                  <input id="wt_blitering" type="number" class="swal2-input cfg-input cfg-input--full" min = "0" value = "10" name = "wt_blitering_val">
                </div>
                <div class="cfg-col">
                  <label class="cfg-label" for="wt_bleding_val">Trộn Hoàn Tất Lô Thương Mại</label>
                  <input id="wt_bleding_val" type="number" class="swal2-input cfg-input cfg-input--full" min = "0" value = "1" name = "wt_bledingl">
                  <label class="cfg-label" for="wt_forming_val">Định Hình Lô Thương Mại</label>
                  <input id="wt_forming_val" type="number" class="swal2-input cfg-input cfg-input--full" min = "0" value = "1" name = "wt_forming">
                  <label class="cfg-label" for="wt_coating_val">Bao Phim Lô Thương Mại</label>
                  <input id="wt_coating_val" type="number" class="swal2-input cfg-input cfg-input--full" min = "0" value = "1" name = "wt_coating">
                  <label class="cfg-label" for="wt_blitering_val">Đóng Gói Lô Thương Mại</label>
                  <input id="wt_blitering_val" type="number" class="swal2-input cfg-input cfg-input--full" min = "0" value = "3" name = "wt_blitering">
                </div>
              </div>

              <div class="cfg-row">
                <label class="cfg-label" for="work-sunday">Làm Chủ Nhật:</label>
                <label class="switch">
                  <input id="work-sunday" type="checkbox" checked>
                  <span class="slider round"></span>
                  <span class="switch-labels">
                    <span class="off">No</span>
                    <span class="on">Yes</span>
                  </span>
                </label>
              </div>

            </div>
          </div>
        `,
        width: 700,
        customClass: { htmlContainer: 'cfg-html-left' , title: 'my-swal-title'},
        showCancelButton: true,
        confirmButtonText: 'Chạy',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
        ,
        preConfirm: () => {
          const formValues = {};
          // Lấy tất cả input trong Swal
          document.querySelectorAll('.swal2-input').forEach(input => {
            formValues[input.name] = input.value;
          });

          const workSunday = document.getElementById('work-sunday');
          formValues.work_sunday = workSunday.checked;

          if (!formValues.start_date) {
            Swal.showValidationMessage('Vui lòng chọn ngày!');
            return false;
          }

          return formValues;
        }
      }).then((result) => {
        if (result.isConfirmed) {
          // Hiển thị loading
          Swal.fire({
            title: 'Đang chạy Auto Scheduler...',
            text: 'Vui lòng chờ trong giây lát',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            },
          });
        const { activeStart, activeEnd } = calendarRef.current?.getApi().view;

        // Gọi API với ngày
        axios.post('/Schedual/scheduleAll', {
            ...result.value,
            startDate: activeStart.toISOString(),
            endDate: activeEnd.toISOString()
          })
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

            setEvents(data.events);
            setSumBatchByStage(data.sumBatchByStage);
            setPlan(data.plan);


          })
        .catch(err => {
            Swal.fire({
              icon: 'error',
              title: 'Lỗi',
              timer: 1000,
              showConfirmButton: false,
            });
            console.error("ScheduleAll error:", err.response?.data || err.message);
          });
        }});
    };

    /// Xử lý Xóa Toàn Bộ Lịch
    const handleDeleteAllScheduale = () => {

      if (!CheckAuthorization(authorization, ['Admin', 'Schedualer'])) return;

      const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
      Swal.fire({
        title: 'Bạn có chắc muốn xóa toàn bộ lịch?',
        text: "Hành động này sẽ xóa toàn bộ lịch không thể phục hồi!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6'
      }).then((result) => {
        Swal.fire({
          title: "Đang tải...",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          },
        });

        if (result.isConfirmed) {
          axios.put('/Schedual/deActiveAll',  { startDate: activeStart.toISOString(), endDate: activeEnd.toISOString()})
            .then(res => {
              let data = res.data;
              if (typeof data === "string") {
                data = data.replace(/^<!--.*?-->/, "").trim();
                data = JSON.parse(data);
              }
              setEvents(data.events);
              setSumBatchByStage(data.sumBatchByStage);
              setPlan(data.plan);

              setTimeout(() => {
                Swal.close();
              }, 100);

              Swal.fire({
                icon: 'success',
                title: 'Đã xóa lịch thành công',
                showConfirmButton: false,
                timer: 1500
              });

            })
            .catch(err => {

              setTimeout(() => {
                Swal.close();
              }, 100);

              Swal.fire({
                icon: 'error',
                title: 'Xóa lịch thất bại',
                text: 'Vui lòng thử lại sau.',
                timer: 1500
              });
              console.error("API error:", err.response?.data || err.message);
          });
        }
        setTimeout(() => {
                Swal.close();
              }, 100);

        });
    };

    /// Xử lý xoa các lịch được chọn
    const handleDeleteScheduale = (e) => {
        if (!CheckAuthorization(authorization, ['Admin', 'Schedualer'])) {return};
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
                        startDate: activeStart.toISOString(),
                        endDate: activeEnd.toISOString()
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
        if (currentView.includes("Week")){
          console.log (currentView)
            const nextIndex = (prevIndex + 1) % slotViewWeeks.length;
            calendarApi.changeView(slotViewWeeks[nextIndex]);
             return nextIndex;
        }else if (currentView.includes("Month")) {
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

    /// Xử lý hoản thành lô
    const handleFinished = (event) => {
      if (!CheckAuthorization(authorization, ['Admin', 'Schedualer'])) {return};
      let unit = event._def.extendedProps.stage_code <= 4 ? "Kg": "ĐVL"
      let id = event._def.publicId

      Swal.fire({

        title: 'Hoàn Thành Sản Xuất',
        html: `
          <div class="cfg-wrapper">
            <div class="cfg-card">
              <!-- Hàng 2 cột -->
              <div class="cfg-row cfg-grid-2">
                <div class="cfg-col">
                  <label class="cfg-label" for="wt_bleding">Sản Lượng Thực Tế</label>
                  <input id="yields" type="number" class="swal2-input cfg-input cfg-input--full" min = "0"  name = "wt_bleding">
                </div>
                <div class="cfg-col">
                  <label class="cfg-label" for="wt_bleding_val">Đơn Vị</label>
                  <input id="unit" type="text" class="swal2-input cfg-input cfg-input--full"  readonly >
                  <input id="stag_plan_id" type="hidden" >
                </div>
              </div>

            </div>
          </div>
        `,
        didOpen: () => {
            document.getElementById('unit').value = unit;
            document.getElementById('stag_plan_id').value = id; // set value thủ công
        },
        width: 700,
        customClass: { htmlContainer: 'cfg-html-left' , title: 'my-swal-title'},
        showCancelButton: true,
        confirmButtonText: 'Lưu',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
        ,
        preConfirm: () => {
          const yields_input = document.getElementById('yields');
          const stag_plan_id = document.getElementById('stag_plan_id').value;
          const yields = yields_input ? yields_input.value.trim() : "";

          if (!yields) {
            Swal.showValidationMessage('Vui lòng nhập sản lượng thực tế');
            return false;
          }

        return { yields, id: stag_plan_id };
        }
      }).then((result) => {
        if (result.isConfirmed) {


          // Gọi API với ngày
        axios.put('/Schedual/finished', result.value)
        .then(res => {
            let data = res.data;
            if (typeof data === "string") {
              data = data.replace(/^<!--.*?-->/, "").trim();
              data = JSON.parse(data);
            }
            setEvents(data.events);

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

      }
      });
    };

    /// Ngăn xụ thay đổi lô Sau khi hoàn thành
    const finisedEvent = (dropInfo, draggedEvent) =>{
          if (draggedEvent.extendedProps.finished) {return false;}
          return true;
    };

    const handleConfirmSource = (event) => {
      if (!CheckAuthorization(authorization, ['Admin', 'Schedualer'])) {return} ;

      let room_id = event._def.resourceIds[0];
      let plan_master_id = event._def.extendedProps.plan_master_id;
      let resource = resources.filter (i => i.id == room_id)[0].title;

      axios.put('/Schedual/getInforSoure', { plan_master_id })
        .then(res => {
          const source_infor = res.data.sourceInfo;
          Swal.fire({
            title: 'Xác Nhận Nguồn Nguyên Liệu Đã Thẩm Định Trên Thiết Bị',
            html: `
              <div class="cfg-wrapper">
                <div class="cfg-card">

                    <div class="cfg-col">
                      <label class="cfg-label" for="intermediate_code">Mã BTP</label>
                      <input id="intermediate_code" type="text"
                            class="swal2-input cfg-input cfg-input--full" readonly>
                    </div>
                    <div class="cfg-col">
                      <label class="cfg-label" for="name">Sản Phẩm</label>
                      <textarea id="name" rows="2"
                                class="swal2-textarea cfg-input cfg-input--full" readonly></textarea>
                    </div>

                    <div class="cfg-col">
                      <label class="cfg-label" for="room">Phòng Sản Xuất</label>
                      <input id="room" type="text"
                            class="swal2-input cfg-input cfg-input--full" readonly>
                    </div>

                    <div class="cfg-col">
                      <label class="cfg-label" for="material_source_id">Nguồn Nguyên Liệu</label>
                      <textarea id="material_source_id" rows="2"
                                class="swal2-textarea cfg-input cfg-input--full" readonly></textarea>
                    </div>
                </div>
              </div>
            `,
            didOpen: () => {
              document.getElementById('intermediate_code').value = source_infor.intermediate_code ?? '';
              document.getElementById('name').value = source_infor.product_name ?? '';
              document.getElementById('room').value = resource ?? '';
              document.getElementById('material_source_id').value = source_infor.name ?? '';


            },
            width: 700,
            customClass: { htmlContainer: 'cfg-html-left', title: 'my-swal-title' },
            showCancelButton: true,
            confirmButtonText: 'Xác Nhận',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            preConfirm: () => {
              const intermediate_code = document.getElementById('intermediate_code');

              if (!intermediate_code) {
                Swal.showValidationMessage('Lỗi: dữ liệu trống');
                return false;
              }

              // Trả dữ liệu về để .then(result) nhận được
              return {
                source_id: source_infor.material_source_id,
                room_id,
                intermediate_code: source_infor.intermediate_code,
              };
            }
          }).then((result) => {
            if (result.isConfirmed) {
            axios.put('/Schedual/confirm_source', result.value)
              .then(res => {
                // Nếu Laravel trả về JSON
                let data = res.data;
                if (typeof data === "string") {
                  data = data.replace(/^<!--.*?-->/, "").trim();
                  data = JSON.parse(data);
                }

                Swal.fire({
                  icon: 'success',
                  title: 'Hoàn Thành',
                  timer: 500,
                  showConfirmButton: false,
                });

                // Nếu có dữ liệu mới trả về thì cập nhật state
                if (data.events) setEvents(data.events);
                })
              .catch(err => {
                Swal.fire({
                  icon: 'error',
                  title: 'Lỗi',
                  timer: 500,
                  showConfirmButton: false,
                });
                console.error("Confirm_source error:", err.response?.data || err.message);
              });
            }
          });
        })
        .catch(() => {
          Swal.fire({
            icon: 'error',
            title: 'Lỗi tải dữ liệu',
            timer: 500,
            showConfirmButton: false
          });
        });
    };

    const toggleNoteModal = () => {
          setShowNoteModal (!showNoteModal)
    }

    const handleShowHistory = (event) => {
      let stage_code_id = event._def.extendedProps.plan_id;

      axios.put('/Schedual/history', {stage_code_id: stage_code_id})
              .then(res => {
                // Nếu Laravel trả về JSON
                let data = res.data;
                if (typeof data === "string") {
                  data = data.replace(/^<!--.*?-->/, "").trim();
                  data = JSON.parse(data);
                }
                setHistoryData (data.history_data);


                })
              .catch(err => {
                Swal.fire({
                  icon: 'error',
                  title: 'Lỗi',
                  timer: 500,
                  showConfirmButton: false,
                });
                console.error("Confirm_source error:", err.response?.data || err.message);
      });


      setShowHistoryModal (true)
    }

  return (

    <div className={`transition-all duration-300 ${showSidebar ? percentShow == "30%"? 'w-[70%]':'w-[85%]' : 'w-full'} float-left pt-4 pl-2 pr-2`}>
      <FullCalendar
        schedulerLicenseKey="GPL-My-Project-Is-Open-Source"
        ref={calendarRef}
        plugins={[dayGridPlugin, resourceTimelinePlugin, interactionPlugin]}
        initialView="resourceTimelineWeek"
        firstDay={1}
        events={events}
        eventResourceEditable ={true}
        resources={resources}
        resourceAreaHeaderContent="Phòng Sản Xuất"

        locale="vi"
        resourceAreaWidth="250px"
        expandRows = {false}

        editable={true}
        droppable={true}
        selectable={true}
        eventResizableFromStart={true}

        slotDuration= "00:15:00"
        eventDurationEditable={true}
        eventStartEditable={true}

        eventClick={handleEventClick}
        eventResize={handleEventChange}
        eventDrop={(info) => handleGroupEventDrop(info, selectedEvents, toggleEventSelect, handleEventChange)}
        eventReceive={handleEventReceive}
        dateClick ={ handleEventUnHightLine}
        eventAllow = {finisedEvent}

        resourceGroupField="stage"

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
           //console.log (arg.resource)
          const res = arg.resource.extendedProps;
          const busy = parseFloat(res.busy_hours) || 0;
          const yields = parseFloat(res.yield)  || 0;
          const unit = res.unit || null;
          const total = parseFloat(res.total_hours) || 1;
          const efficiency = ((busy / total) * 100).toFixed(1);


          const highlight = selectedRows.some(row => {
            if (!row.permisson_room) return false;

            if (Array.isArray(row.permisson_room)) {
              // nếu backend đổi thành array thì vẫn chạy
              return row.permisson_room.includes(arg.resource.extendedProps.code);
            } else if (typeof row.permisson_room === "object") {
              // trường hợp {id_room: code}
              return Object.values(row.permisson_room).includes(arg.resource.extendedProps.code);
            } else {
              // fallback: string / id
              return row.permisson_room == arg.resource.id;
            }
          });

          return (
            <div
                style={{
                  backgroundColor: highlight ? "#c6f7d0" : "transparent",
                  padding: "0px",
                  borderRadius: "6px",
                  marginTop: "0px",
                  position: "relative",
                  height:"1px" // cần để con có thể dịch lên
                }}
              >
                <div
                  style={{
                    fontSize: "14px",
                    fontWeight: "bold",
                    marginBottom: "2px",
                    width: "8%",
                    position: "relative",
                    top: "-26px", // dịch lên trên 6px
                  }}
                >
                  {arg.resource.title}-{arg.resource.extendedProps.main_equiment_name}
                </div>

                <div
                  className="resource-bar"
                  style={{
                    position: "relative",
                    top: "-26px", // dịch luôn cả progress bar lên
                    height: "15px",
                    background: "#eeeeeeff",
                    borderRadius: "20px",
                    overflow: "hidden",
                    display: "flex",
                    alignItems: "center",
                  }}
                >
                  <div
                    className="busy"
                    style={{
                      width: `${(busy / total) * 100}%`,
                      background: "red",
                      height: "100%",
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "center",
                    }}
                  />
                  <b
                    style={{
                      position: "absolute",
                      top: "50%",
                      left: "50%",
                      transform: "translate(-50%, -50%)",
                      fontSize: "70%",
                      color: "#060606ff",
                    }}
                  >
                    {efficiency}% - {formatNumberWithComma(yields)} {unit}
                  </b>
                </div>
              </div>

          );
        }}

        headerToolbar={{
          left: 'customPre,myToday,customNext noteModal hiddenClearning autoSchedualer deleteAllScheduale changeSchedualer unSelect',
          center: 'title',
          right: 'fontSizeBox searchBox slotDuration customDay,customWeek,customMonth,customQuarter customList' //customYear
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
              duration: { months: 4 },      
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
          resourceTimelineMonth1d: { type: 'resourceTimelineMonth', slotDuration: { days: 1 }},
        }}

        customButtons={{
          customNext: {
            text: '⏵',
            click: () => {
              let api = calendarRef.current.getApi();
              api.next();  // gọi hành vi gốc
              setLoading (!loading);
            }
          },
          customPre: {
            text: '⏴',
            click: () => {
              let api = calendarRef.current.getApi();
              api.prev(); // gọi hành vi gốc
              setLoading (!loading);
            }
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
            text: '4 Tháng',
            click: () => handleViewChange('resourceTimelineQuarter')
          },
          customYear: {
            text: 'Năm',
            click: () => handleViewChange('resourceTimelineYear')
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
            click: handleClear
          },
          dateRange : {text: ''},
          searchBox: {text: ''},
          fontSizeBox: {text: ''},

          slotDuration: {
            text: 'Slot',
            click: toggleSlotDuration
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

        eventContent={(arg) => {

        const isSelected = selectedEvents.some(ev => ev.id === arg.event.id);
        const now = new Date();
        return (
        <div className="relative  group custom-event-content" data-event-id={arg.event.id} >

            <div style={{fontSize: `${eventFontSize}px`}}>
              <b>{arg.event.title}</b>
              <br/>
              {viewConfig.timeView != 'resourceTimelineMonth' ? (<span >{moment(arg.event.start).format('HH:mm')} - {moment(arg.event.end).format('HH:mm')}</span>):""}
            </div>

            {/* Nút xóa */}
            {arg.event.extendedProps.finished !== 1 && (
              <button onClick={(e) => {
                //alert ("sa");
                handleDeleteScheduale(e);
              }}
              className="absolute top-0 right-0 hidden group-hover:block text-red-500 text-sm bg-white px-1 rounded shadow"
              title="Xóa lịch"
            >
              ×
            </button>)}

            {/* Nút Sửa/Nội dung */}
            {/* <button
              onClick={(e) => {
                console.log (arg.event)
                e.stopPropagation();
                Swal.fire({
                  title: 'Thêm nội dung cho lịch',
                  input: 'textarea',
                  //inputLabel: 'Ghi chú',
                  inputPlaceholder: 'Nhập nội dung tại đây...',
                  showCancelButton: true,
                  confirmButtonText: 'Lưu',
                  cancelButtonText: 'Hủy',
                  preConfirm: (value) => {
                    if (!value) return Swal.showValidationMessage('Nội dung không được để trống');
                    // Cập nhật nội dung hoặc gửi server
                    arg.event.setExtendedProp('note', value);
                    router.put(`/Schedual/addEventContent/${arg.event.id}`, { note: value});
                  }
                });
              }}
              className="absolute top-0 right-6 hidden group-hover:block text-blue-500 text-sm bg-white px-1 rounded shadow"
              title="Thêm nội dung"
            >
              📝
            </button> */}

            {/* ✅ Nút Select thêm vào đây */}
            <button
                onClick={(e) => {
                  e.stopPropagation();
                  toggleEventSelect(arg.event);
                }}
                className={`absolute top-0 left-0 text-xs px-1 rounded shadow
                  ${isSelected ? 'block' : 'hidden group-hover:block'}
                  ${isSelected ? 'bg-blue-500 text-white' : 'bg-white text-blue-500 border border-blue-500'}
                `}
                title={isSelected ? 'Bỏ chọn' : 'Chọn sự kiện'}
              >
                {isSelected ? '✓' : '+'}
            </button>

            {/* H Xem History */}
            {type && viewName == "resourceTimelineWeek"  && (
            <button
                onClick={(e) => { 
                  e.stopPropagation();
                  handleShowHistory(arg.event);}}
                className={`absolute top-[-15px] left-2 text-xs px-1 rounded shadow bg-red-500 text-white`}
                title={'Xem Lịch Sử Thay Đổi'}
              >
                {arg.event._def.extendedProps.number_of_history}
            </button>)}

            {arg.event._def.extendedProps.experted_date && (
            <div
                className={`
                  absolute top-[-15px] left-[50px] text-xs px-1 rounded shadow text-white
                  ${arg.event._def.extendedProps.level == '1' ? 'bg-red-500' : ''}
                  ${arg.event._def.extendedProps.level == '2' ? 'bg-orange-500' : ''}
                  ${arg.event._def.extendedProps.level == '3' ? 'bg-blue-500' : ''}
                  ${arg.event._def.extendedProps.level == '3' ? 'bg-green-500' : ''}
                  ${!['low','medium','high'].includes(arg.event._def.extendedProps.level) ? 'bg-blue-500' : ''}
                `}
                title={'Ngày Cần Hàng'}
              >
                {arg.event._def.extendedProps.experted_date}
            </div>)}

            {arg.event._def.extendedProps.tank == true &&  viewName == "resourceTimelineWeek"  && (
            <div
                className={`absolute top-[-15px] left-[170px] text-xs px-1 rounded shadow bg-red-500 text-white`}
                title={'Bồn Trộn Lập Phương'}
              >
                ⚗️
            </div>)}

            {arg.event._def.extendedProps.keep_dry == true &&  viewName == "resourceTimelineWeek"  && (
            <div
                className={`absolute top-[-15px] left-[170px] text-xs px-1 rounded shadow bg-red-500 text-white`}
                title={'Đóng Gói Độ Ẩm Thấp'}
              >
                🌡
            </div>)}


              {/* H Xem History */}
            {!arg.event._def.extendedProps.is_clearning &&  (
            <button
                className={`absolute top-[-15px] right-5 text-15 px-1 rounded shadow bg-white-500 text-red`}
                title={'Xem Lịch Sử Thay Đổi'}
              >
            {arg.event._def.extendedProps.direction ? '➡' : '⬅'} <b>{arg.event._def.extendedProps.order_by}</b>
            </button>)}

            {/* 🎯 Nút Xác nhận Hoàn thành && arg.event._instance.range.end <= now */}
            {arg.event.extendedProps.finished == 0  && type && (
              <button onClick={(e) => { e.stopPropagation(); handleFinished(arg.event);}}
                className="absolute bottom-0 left-0 hidden group-hover:block text-blue-500 text-sm bg-white px-1 rounded shadow"
                title='Xác Nhận Hoàn Thành Lô Sản Xuất'
              >
                🎯
            </button>)}

            {/* 📦 Nút Xác nhận nguồn NL Và Phòng Sản Xuất */}
            {arg.event.extendedProps.room_source === false  && type && (
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  handleConfirmSource(arg.event);
                }}
                className="absolute bottom-0 left-0 hidden group-hover:block text-blue-500 text-sm bg-white px-1 rounded shadow"
                title='Khai báo nguồn nguyên liệu trên thiết bị sản xuất'
              >
                📦
            </button>)}

        </div>

        )}}

        slotLaneDidMount={(info) => {
          if (info.date < new Date()) {
            info.el.style.backgroundColor = "rgba(0,0,0,0.05)";
          }
        }}


      />
      {/* <div className="modal-sidebar"> */}
        <ModalSidebar

            visible={showSidebar}
            onClose={setShowSidebar}
            waitPlan={plan}
            setPlan={setPlan}
            percentShow = {percentShow}
            setPercentShow={setPercentShow}
            selectedRows = {selectedRows}
            setSelectedRows = {setSelectedRows}
            quota = {quota}
            resources = {resources}
            type = {type}
           
        />
      {/* </div> */}


        <NoteModal show={showNoteModal} setShow={setShowNoteModal} />
        <History show={showHistoryModal} setShow={setShowHistoryModal} historyData={historyData}/>

        {/* Selecto cho phép quét chọn nhiều .fc-event */}
        <Selecto
          onDragStart={(e) => {
              // Nếu không nhấn shift thì dừng Selecto => để FullCalendar drag hoạt động
              if (!e.inputEvent.shiftKey) {
                e.stop();
              }
            }}
            container=".calendar-wrapper"
            selectableTargets={[".fc-event"]}
            hitRate={100}
            selectByClick={false}   // tắt click select (chỉ dùng drag + Shift)
            selectFromInside={true}
            toggleContinueSelect={["shift"]}

            onSelectEnd={(e) => {
              const selected = e.selected.map((el) => {
                const id = el.getAttribute("data-event-id");
                const stageCode = el.getAttribute("data-stage_code");
                return { id, stage_code: stageCode };
              });
              setSelectedEvents(selected);
              console.log (selectedEvents);
            }}
        />



    </div>


  );
};

export default ScheduleTest;

