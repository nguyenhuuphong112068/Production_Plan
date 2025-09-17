import React, { useRef, useState, useEffect, useCallback } from 'react';
import ReactDOM from 'react-dom/client';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';
import moment from 'moment';
import { usePage, router } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import ModalSidebar from '../Components/ModalSidebar';
import NoteModal from '../Components/NoteModal';
import dayjs from 'dayjs';
import Swal from 'sweetalert2'; 
import './calendar.css';
import CalendarSearchBox from '../Components/CalendarSearchBox';
import EventFontSizeInput from '../Components/EventFontSizeInput';
import axios from "axios";
import 'moment/locale/vi';
import '@fullcalendar/daygrid/index.js';
import '@fullcalendar/resource-timeline/index.js';
import Selecto from "react-selecto";

  const ScheduleTest = () => {
    
    const calendarRef = useRef(null);
    moment.locale('vi');

    const { events, resources, sumBatchByStage, plan, quota, stageMap } = usePage().props;
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
    const slotViews = ['resourceTimelineWeek15', 'resourceTimelineWeek30', 'resourceTimelineWeek60','resourceTimelineWeek4h']; //, 'resourceTimelineWeek8h', 'resourceTimelineWeek12h', 'resourceTimelineWeek24h'
    const [slotIndex, setSlotIndex] = useState(0);
    const [eventFontSize, setEventFontSize] = useState(14); // default 14px
    const [selectedRows, setSelectedRows] = useState([]);
    const [showNoteModal, setShowNoteModal] = useState(false);

    //Get dư liệu row được chọn 
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
    }, [selectedRows]);

    // UseEffect cho render nut search
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

    // --- Highlight tất cả sự kiện ---
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

    // --- Xoá highlight ---
    const clearHighlights = () => {
      document.querySelectorAll(".highlight-event, .highlight-current-event").forEach(el => {
        el.classList.remove("highlight-event", "highlight-current-event");
      });
    };

    // --- Scroll sự kiện hiện tại vào view ---
    const scrollToEvent = (el) => {
      if (!el) return;
      el.scrollIntoView({
        behavior: "smooth",
        block: "center",
        inline: "center",
      });
    };

    // show sidebar
    const handleShowList = () => {
    
      setShowSidebar(true);
    }

    //  Thay đôi khung thời gian
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
      
      router.put(`/Schedual/view`,
        { start: activeStart.toISOString(), end: activeEnd.toISOString() },
        {
          preserveState: true,
          replace: true,
          only: ['resources'],
          onSuccess: (page) => {
                  setTimeout(() => {
                      Swal.close();
                    }, 500);
          }
        }
      );

      // Chờ FullCalendar render xong rồi tắt loading
    // bạn chỉnh thời gian tuỳ theo tốc độ render
    };

    // Tô màu các event trùng khớp
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

    // Bỏ tô màu các event trùng khớp
    const handleEventUnHightLine = async (info) => {
        document.querySelectorAll('.fc-event').forEach(el => {
        el.classList.remove('highlight-event');
      });
    };
 
    // Nhân Dữ liệu để tạo mới event
    const handleEventReceive = async (info) => {
     
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

      if (selectedRows[0].stage_code !== 8){
          router.put('/Schedual/store', {
              room_id: resourceId,
              stage_code: selectedRows[0].stage_codes,
              start: moment(start).format("YYYY-MM-DD HH:mm:ss"),
              products: selectedRows,
              }, {
                preserveScroll: true,
                onSuccess: () => {
                  setSelectedRows([]);
                  },
                onError: (errors) => console.error('Lỗi tạo lịch', errors),
          });
      }else if (selectedRows[0].stage_code == 8){
            router.put('/Schedual/store_maintenance', {
              stage_code: 8,
              start: moment(start).format("YYYY-MM-DD HH:mm:ss"),
              products: selectedRows,
              is_HVAC: selectedRows[0].is_HVAC
              }, {
                preserveScroll: true,
                onSuccess: () => {
                  setSelectedRows([]);
                  },
                onError: (errors) => console.error('Lỗi tạo lịch', errors),
          });
      }

    };

    // Ẩn hiện sự kiện vệ sinh
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

    // 3 Ham sử lý thay đôi sự kiện
    const handleGroupEventDrop = (info, selectedEvents, toggleEventSelect, handleEventChange) => {
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

    const handleSaveChanges = async () => {
      

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

      setSaving(true);

      for (const change of pendingChanges) {

        router.put('/Schedual/update',   
        {
          changes: pendingChanges.map(change => ({
              id: change.id,
              start: dayjs(change.start).format('YYYY-MM-DD HH:mm:ss'),
              end: dayjs(change.end).format('YYYY-MM-DD HH:mm:ss'),
              resourceId: change.resourceId,
              title: change.title,
              //C_end: change.C_end || false,
        })),
        }, {
          preserveScroll: true,
          onSuccess: () => console.log(`Đã lưu event ${change.id}`),
          onError: (errors) => console.error(`Lỗi khi lưu event ${change.id}`, errors),
        });
      }

      setSaving(false);
      setPendingChanges([]);

      Swal.fire({
          icon: 'success',
          title: 'Thành công!',
          text: 'Đã lưu tất cả thay đổi.',
          timer: 1000,
          showConfirmButton: false,
      });

    };
 
    // Xử lý Toggle sự kiện đang chọn: if đã chọn thì bỏ ra --> selectedEvents
    const toggleEventSelect = (event) => {
      setSelectedEvents((prevSelected) => {
        const exists = prevSelected.some(ev => ev.id === event.id);
        return exists
          ? prevSelected.filter(ev => ev.id !== event.id)
          : [...prevSelected, { id: event.id, stage_code: event.extendedProps.stage_code }];
      });
    };

    // Xử lý chọn 1 sự kiện -> selectedEvents
    const handleEventClick = (clickInfo) => {
      const event = clickInfo.event;
      if (clickInfo.jsEvent.shiftKey || clickInfo.jsEvent.ctrlKey || clickInfo.jsEvent.metaKey) {
        setSelectedEvents([{ id: event.id, stage_code: event.extendedProps.stage_code }]); // ghi đề toạn bọ các sự kiện chỉ giử lại sự kiện cuối
      } else {
        toggleEventSelect(event);
      }
      
    };

    // bỏ chọn tất cả sự kiện đã chọn ở select sidebar -->  selectedEvents
    const handleClear = () => {setSelectedEvents([]);};

    // Xử lý Chạy Lịch Tư Động
    const handleAutoSchedualer = () => {
      Swal.fire({
        title: 'Cấu Hình Chung Sắp Lịch',
        html: `
          <div class="cfg-wrapper">
            <div class="cfg-card">
              <!-- Hàng Ngày chạy -->
              <div class="cfg-row">
                <label class="cfg-label" for="schedule-date">Ngày chạy bắt đầu sắp lịch:</label>
                <input id="schedule-date" type="date" 
                      class="swal2-input cfg-input cfg-input--half"  name = "start_date"
                      value="${new Date().toISOString().split('T')[0]}">
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

          // Gọi API với ngày
          router.put('/Schedual/scheduleAll', result.value , {
            preserveScroll: true,
            onSuccess: () => {
              Swal.fire({
                icon: 'success',
                title: 'Hoàn Thành Sắp Lịch',
                timer: 1000,
                showConfirmButton: false,
              });
            },
            onError: () => {
              Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                timer: 1000,
                showConfirmButton: false,
              });
            },
          });
        }
      });
    };

    // Xử lý Xóa Toàn Bộ Lịch
    const handleDeleteAllScheduale = () => {
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
        if (result.isConfirmed) {
          router.put(`/Schedual/deActiveAll`, {
            onSuccess: () => {
              Swal.fire({
                icon: 'success',
                title: 'Đã xóa lịch thành công',
                showConfirmButton: false,
                timer: 1500
              });
            },
            onError: () => {
              Swal.fire({
                icon: 'error',
                title: 'Xóa lịch thất bại',
                text: 'Vui lòng thử lại sau.',
                timer: 1500
              });
            }
          });
        }
      });
    };

    // Xử lý độ chia thời gian nhỏ nhất 
    const toggleSlotDuration = () => {
      setSlotIndex((prevIndex) => {
        const nextIndex = (prevIndex + 1) % slotViews.length;
        const calendarApi = calendarRef.current?.getApi();
        calendarApi.changeView(slotViews[nextIndex]);
        return nextIndex;
      });
    };

    // Xử lý format số thập phân
    const formatNumberWithComma = (x) => {
      if (x == null) return "0";
      return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Xử lý hoản thành lô
    const handleFinished = (event) => {
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
          router.put('/Schedual/finished', result.value , {
            preserveScroll: true,
            onSuccess: () => {
              Swal.fire({
                icon: 'success',
                title: 'Hoàn Thành',
                timer: 500,
                showConfirmButton: false,
              });
            },
            onError: () => {
              Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                timer: 500,
                showConfirmButton: false,
              });
            },
          });
        }
      });
    };

    // Ngăn xụ thay đổi lô Sau khi hoàn thành
    const finisedEvent = (dropInfo, draggedEvent) =>{
          if (draggedEvent.extendedProps.finished) {return false;}
          return true;
    };

    const handleConfirmSource = (event) => {
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
              router.put('/Schedual/confirm_source', result.value, {
                preserveScroll: true,
                onSuccess: () => {
                  Swal.fire({
                    icon: 'success',
                    title: 'Hoàn Thành',
                    timer: 500,
                    showConfirmButton: false,
                  });
                },
                onError: () => {
                  Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    timer: 500,
                    showConfirmButton: false,
                  });
                },
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
        height="auto"
        resourceAreaWidth="8%"
   

        editable={true}
        droppable={true}
        selectable={true}
        eventResizableFromStart={true}
        
        slotDuration= "00:15:00"
        eventDurationEditable={true}
        resourceEditable={true}
        eventStartEditable={true} 
      
        eventClick={handleEventClick}
        eventResize={handleEventChange} 
        eventDrop={(info) => handleGroupEventDrop(info, selectedEvents, toggleEventSelect, handleEventChange)}
        eventReceive={handleEventReceive}
        dateClick ={ handleEventUnHightLine}
        eventAllow = {finisedEvent}

 
        datesSet={(info) => {
    
          const { start, end } = info; 
          Swal.fire({
            title: "Đang tải...",
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            },
          });

          if (!start || !end) {
            return;
          }

          router.put(`/Schedual/view`,
            { start: start.toISOString(), end: end.toISOString() },
            {
              preserveState: true,
              preserveScroll: true,
              replace: false,
              only: ['resources', 'sumBatchByStage'],
              onSuccess: () => {
                setTimeout(() => Swal.close(), 500);
              }
            }
          );

         
        }}


        resourceGroupField="stage"
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

        resourceLabelContent={(arg) => {
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
                    fontWeight: "bold",
                    marginBottom: "2px",
                    width: "8%",
                    position: "relative",
                    top: "-26px", // dịch lên trên 6px
                  }}
                >
                  {arg.resource.title}
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
        }}
        
        headerToolbar={{
          left: 'prev,myToday,next noteModal hiddenClearning autoSchedualer deleteAllScheduale changeSchedualer unSelect',
          center: 'title',
          right: 'fontSizeBox searchBox slotDuration customDay,customWeek,customMonth customList' //customYear
        }}

        customButtons={{
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
        <div className="relative group custom-event-content" data-event-id={arg.event.id} >
            
            <div style={{ fontSize: `${eventFontSize}px` }}>
              {/* {viewConfig.timeView != 'resourceTimelineMonth' ? (<b >{arg.event.title}</b>):(<b>{arg.event.extendedProps.name ? arg.event.extendedProps.name.split(" ")[0] : ""}-{arg.event.extendedProps.batch}</b>)} */}
              <b>{arg.event.title}</b>
              <br/>
              {viewConfig.timeView != 'resourceTimelineMonth' ? (<span >{moment(arg.event.start).format('HH:mm')} - {moment(arg.event.end).format('HH:mm')}</span>):""}
              {/* <span >{moment(arg.event.start).format('HH:mm')} - {moment(arg.event.end).format('HH:mm')}</span> */}
            </div>

            {/* Nút xóa */}
            {arg.event.extendedProps.finished !== 1 && (
              <button
              onClick={(e) => {

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
                    arg.event.remove();
                    router.put(`/Schedual/deActive`,
                      { 
                        ids: selectedEvents.map(ev => ev),
                        //stage_code: selectedEvents.map(ev => ev.stage_code)
                       }
                      , {
                      onSuccess: () => {
                        Swal.fire({
                          icon: 'success',
                          title: 'Đã xóa lịch thành công',
                          showConfirmButton: false,
                          timer: 1500
                        });
                      },
                      onError: () => {
                        Swal.fire({
                          icon: 'error',
                          title: 'Xóa lịch thất bại',
                          text: 'Vui lòng thử lại sau.',
                        });
                      }
                    });
                  }
                  setSelectedEvents([]);
                });
              }}
              className="absolute top-0 right-0 hidden group-hover:block text-red-500 text-sm bg-white px-1 rounded shadow"
              title="Xóa lịch"
            >
              ×
            </button>)}

            {/* Nút Sửa/Nội dung */}
            <button
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
            </button>

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

            {/* 🎯 Nút Xác nhận Hoàn thành && arg.event._instance.range.end <= now */} 
            {arg.event.extendedProps.finished === 0  && (
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  handleFinished(arg.event);
                }}
                className="absolute bottom-0 left-0 hidden group-hover:block text-blue-500 text-sm bg-white px-1 rounded shadow"
                title='Xác Nhận Hoàn Thành Lô Sản Xuất'
              >
                🎯
            </button>)}

            {/* 📦 Nút Xác nhận nguồn NL Và Phòng Sản Xuất */} 
            {arg.event.extendedProps.room_source === false  && (
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
      />
      
      <ModalSidebar
          visible={showSidebar}
          onClose={setShowSidebar}
          events={plan}
          percentShow = {percentShow}
          setPercentShow={setPercentShow}
          selectedRows = {selectedRows}
          setSelectedRows = {setSelectedRows}
          quota = {quota}
          resources = {resources}
      />

        <NoteModal show={showNoteModal} setShow={setShowNoteModal} />

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

ScheduleTest.layout = (page) => (
  <AppLayout title={page.props.title} user={page.props.user}>
    {page}
  </AppLayout>
);


