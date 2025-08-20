import React, { useRef, useState, useEffect } from 'react';
import ReactDOM from 'react-dom/client';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';
import moment from 'moment';
import { usePage, router } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import ModalSidebar from '../Components/ModalSidebar';
import dayjs from 'dayjs';
import Swal from 'sweetalert2'; 
import './calendar.css';
import CalendarSearchBox from '../Components/CalendarSearchBox';
import EventFontSizeInput from '../Components/EventFontSizeInput';

import 'moment/locale/vi';
import '@fullcalendar/daygrid/index.js';
import '@fullcalendar/resource-timeline/index.js';

import 'primereact/resources/themes/lara-light-indigo/theme.css';
import 'primereact/resources/primereact.min.css';                
import 'primeicons/primeicons.css'; 



  const ScheduleTest = () => {
    const calendarRef = useRef(null);
    moment.locale('vi');

    const { events, resources, title, plan, quota } = usePage().props;
    const [showSidebar, setShowSidebar] = useState(false);
    const [selectedRow, setSelectedRow] = useState({});
    const [viewConfig, setViewConfig] = useState({timeView: 'resourceTimelineWeek', slotDuration: '00:15:00', is_clearning: true});
    const [cleaningHidden, setCleaningHidden] = useState(false);
    const [pendingChanges, setPendingChanges] = useState([]);
    const [saving, setSaving] = useState(false);
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [selectedEvents, setSelectedEvents] = useState([]);
    const [isHoveringSidebar, setIsHoveringSidebar] = useState(false);
    const [percentShow, setPercentShow] = useState("15%");
    const highlightedPMIdsRef = useRef(new Set());
    const searchResultsRef = useRef([]);
    const currentIndexRef = useRef(-1);
    const lastQueryRef = useRef("");
    const slotViews = ['resourceTimelineWeek15', 'resourceTimelineWeek30', 'resourceTimelineWeek60','resourceTimelineWeek4h']; //, 'resourceTimelineWeek8h', 'resourceTimelineWeek12h', 'resourceTimelineWeek24h'
    const [slotIndex, setSlotIndex] = useState(0);
    const [eventFontSize, setEventFontSize] = useState(14); // default 14px

    useEffect(() => {
    new Draggable(document.getElementById('external-events'), {
      itemSelector: '.fc-event',
        eventData: function (eventEl) {
        const isMulti = eventEl.hasAttribute('data-rows');
      
        

        if (isMulti) {

          const draggedData = JSON.parse(eventEl.getAttribute('data-rows') || '[]');
          if (!draggedData.length) return null;

          const { intermediate_code, stage_code } = draggedData[0];

          const matched = quota.find(item =>
            item.intermediate_code === intermediate_code &&
            parseInt(item.stage_code) === stage_code
          );

          if (!matched) {
            Swal.fire({
              icon: 'warning',
              title: 'Sản Phẩm Chưa Được Định Mức',
              text: 'Vui lòng định mức trước khi sắp lịch!',
            });
            return null;
          }


          setSelectedRow({
            stage_code: stage_code,
            quota: matched,
          });
        
          // ✅ Trường hợp nhiều mục được chọn
          
          return {
            title: 'Nhiều mục được chọn',
            extendedProps: {
              rows: draggedData
            }
          };

        } else {
          // ✅ Trường hợp kéo từng item
          const intermediate_code = eventEl.getAttribute('data-intermediate_code');
          const stage_code = parseInt(eventEl.getAttribute('data-stage_code'));

  
          const matched = quota.find(item =>
            item.intermediate_code === intermediate_code &&
            parseInt(item.stage_code) === stage_code
          );

          if (!matched) {
            Swal.fire({
              icon: 'warning',
              title: `Sản phẩm ${eventEl.getAttribute('data-title')} chưa được định mức.`,
              text: `Vui lòng định mức trước khi sắp lịch!`,
            });
            return null;
          }

          const duration = matched.PM;
      
          setSelectedRow({
            stage_code: stage_code,
            id: eventEl.getAttribute('data-id'),
            title: eventEl.getAttribute('data-title'),
            quota: matched
          });

          return {
            title: eventEl.getAttribute('data-title'),
            duration,
            extendedProps: {
            externalId: eventEl.getAttribute('data-id'),
            },
          };
        }
      }
    });
    }, [quota]);

    useEffect(() => {
      if (selectedEvents.length === 0) {
        setSidebarOpen(false); // Đóng nếu không còn gì/./

      }
    }, [selectedEvents]);

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
          {/* <EventFontSizeInput fontSize={eventFontSize} setFontSize={setEventFontSize} /> */}
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
      block: "nearest",
      inline: "center",
    });
  };


  const handleShowList = () => setShowSidebar(!showSidebar);

  const handleViewChange = (view) => {

    setViewConfig({is_clearning:false })
    calendarRef.current?.getApi()?.changeView(view);

  };

  const applyHighlights = () => {
      const api = calendarRef.current.getApi();
      api.batchRendering(() => {
        api.getEvents().forEach(ev => {
          const pm = ev.extendedProps.plan_master_id;
          const on = highlightedPMIdsRef.current.has(pm);
          ev.setExtendedProp('isHighlighted', on);
        });
      });
  };

  const handleEventHightLine = (event, isCtrlPressed = false) => {
      const pm = event.extendedProps.plan_master_id;

      if (!isCtrlPressed) highlightedPMIdsRef.current.clear();
      highlightedPMIdsRef.current.add(pm);

      applyHighlights();
  };

  const handleEventUnHightLine = async (info) => {
      document.querySelectorAll('.fc-event').forEach(el => {
      el.classList.remove('highlight-event');
    });
  };
 
  const handleEventReceive = async (info) => {
      const draggedRows = info.event.extendedProps?.rows || [];
      const resourceId = info.event.getResources?.()[0]?.id ?? null;
      const start = info.event.start;

      const matchedRow = quota.find(item =>item.room_id == resourceId);

      

      if (!matchedRow || matchedRow.stage_code == null || matchedRow.stage_code !== selectedRow.stage_code) {
        info.event.remove();  
        Swal.fire({
            icon: 'warning',
            title:'Sắp Lịch Sai Công Đoạn',
            timer: 1000,
            showConfirmButton: false,
          });
        return;
      }



      // ✅ Trường hợp 1: Kéo nhiều dòng (array draggedRows > 0)
      if (draggedRows.length > 0) {
          // Tính thời lượng mặc định (ví dụ: 1 giờ mỗi dòng)
          const startTime = dayjs(start).add(1 * 60, 'minute'); // dàn đều theo giờ
         
          router.put('/Schedual/multiStore', {
            numberofRow: draggedRows.length,
            draggedRows: draggedRows,
            extraData: selectedRow.draggedRows,
            start: startTime.format('YYYY-MM-DD HH:mm:ss'),
            resourceId,
            quota: selectedRow.quota
           
          }, {
            preserveScroll: true,
            onSuccess: () => console.log(`Đã tạo `),
            onError: (errors) => console.error(`Lỗi tạo `, errors),
          });
        

        info.event.remove(); // Loại bỏ event "gộp" ban đầu
        return;
      }

      // ✅ Trường hợp 2: Kéo 1 dòng
      if (selectedRow?.id) {
        const end = info.event.end;
        const [hours, minutes] = selectedRow.quota.C2_time?.split(':').map(Number) || [0, 0];
        const C_end = dayjs(end).add(hours, 'hour').add(minutes, 'minute').format('YYYY-MM-DD HH:mm:ss');
        router.put('/Schedual/store', {
          id: selectedRow.id,
          title: selectedRow.title,
          start: dayjs(start).format('YYYY-MM-DD HH:mm:ss'),
          end: dayjs(end).format('YYYY-MM-DD HH:mm:ss'),
          resourceId,
          C_end,
        }, {
          preserveScroll: true,
          onSuccess: () => console.log(`Đã tạo ${selectedRow.title}`),
          onError: (errors) => console.error('Lỗi tạo lịch', errors),
        });

        info.event.remove();
        setSelectedRow({});
      }
  };

  const toggleCleaningEvents = () => {
    const calendarApi = calendarRef.current?.getApi();
    if (!calendarApi) return;

    const view = calendarApi.view?.type;

    calendarApi.getEvents().forEach(event => {
      if (event.extendedProps.is_clearning) {
        const els = document.querySelectorAll(`[data-event-id="${event.id}"]`);
        els.forEach(el => {
          el.style.display = cleaningHidden ? '' : 'none';
        });
      }
    });

  setCleaningHidden(!cleaningHidden);
  };

  const handleGroupEventDrop = (info, selectedEvents, toggleEventSelect, handleEventChange) => {
      const draggedEvent = info.event;
      const delta = info.delta;
      const calendarApi = info.view.calendar;
     
      // Nếu chưa được chọn thì tự động chọn
      if (!selectedEvents.includes(draggedEvent.id)) {
        //toggleEventSelect(draggedEvent.id);
        toggleEventSelect(draggedEvent);
      }

      // Nếu là event đã được chọn, kéo theo nhóm
      if (selectedEvents.includes(draggedEvent.id)) {
        info.revert(); // Hoàn tác vì sẽ xử lý bằng tay

        selectedEvents.forEach(eventId => {
          const event = calendarApi.getEventById(eventId);
          if (event) {
            const newStart = new Date(event.start.getTime() + delta.milliseconds);
            const newEnd = new Date(event.end.getTime() + delta.milliseconds);

            event.setDates(newStart, newEnd);

            // Gửi vào handleEventChange
            handleEventChange({ event });
          }
        });
      } else {
        // Nếu không thuộc danh sách chọn, xử lý đơn
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
      router.put('/Schedual/update', {
        id: change.id,
        start: dayjs(change.start).format('YYYY-MM-DD HH:mm:ss'),
        end: dayjs(change.end).format('YYYY-MM-DD HH:mm:ss'),
        resourceId: change.resourceId,
        title: change.title,
        C_end: change.C_end || false,
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

  const toggleEventSelect = (event) => {
    
    setSelectedEvents((prevSelected) => {
      const exists = prevSelected.some(ev => ev.id === event.id);
      return exists
        ? prevSelected.filter(ev => ev.id !== event.id)
        : [...prevSelected, { id: event.id, stage_code: event.extendedProps.stage_code }];
    });
  };

  const handleEventClick = (clickInfo) => {
    const event = clickInfo.event;
    if (clickInfo.jsEvent.shiftKey || clickInfo.jsEvent.ctrlKey || clickInfo.jsEvent.metaKey) {
      setSelectedEvents([{ id: event.id, stage_code: event.extendedProps.stage_code }]);
    } else {
      toggleEventSelect(event);
    }
    
  };

  const handleRemove = (id) => {
    setSelectedEvents(prev => prev.filter(eid => eid !== id));
  };

  const handleClear = () => {
    setSelectedEvents([]);
  };

  const SelectedEventsSidebar = ({
        events,
        onRemove,
        onClear,
        onClose,
        pendingChanges,
        handleSaveChanges
      }) => {
        if (!events.length) return null;

        return (
          <div className="fixed right-0 top-0 h-full w-64 bg-white shadow-lg p-4 z-50 overflow-auto">
            <div className="flex justify-between items-center mb-2">
              <h2 className="text-lg font-semibold">Sản phẩm đã chọn</h2>
              
            </div>

            <div className="space-y-2 mb-4">
              <button
                onClick={handleSaveChanges}
                disabled={pendingChanges.length === 0}
                className={`w-full p-2 rounded ${
                  pendingChanges.length === 0
                    ? "bg-gray-300 cursor-not-allowed"
                    : "bg-green-500 hover:bg-green-600 text-white"
                }`}
              >
                Lưu thay đổi ({pendingChanges.length})
              </button>

              <button
                onClick={onClear}
                className="w-full p-2 rounded bg-red-500 hover:bg-red-600 text-white"
              >
                Bỏ chọn tất cả
              </button>
            </div>

            <ul>
              {events.map(ev => (
                <li key={ev.id} className="mb-2 border-b pb-1">
                  <div className="flex justify-between items-center">
                    <span>{ev.title}</span>
                    <button
                      onClick={() => onRemove(ev.id)}
                      className="text-sm text-red-500 hover:text-red-700"
                    >
                      ✕
                    </button>
                  </div>
                  <small>
                    {moment(ev.start).format("HH:mm")} - {moment(ev.end).format("HH:mm")}
                  </small>
                </li>
              ))}
            </ul>
          </div>
        );
  };

  // const handleAutoSchedualer = () => {
  //   Swal.fire({
  //     title: 'Bạn có chắc muốn chạy Auto Scheduler?',
  //     //text: "Hành động này sẽ tự động sắp xếp tất cả lịch.",
  //     icon: 'warning',
  //     showCancelButton: true,
  //     confirmButtonText: 'Chạy',
  //     cancelButtonText: 'Hủy',
  //     confirmButtonColor: '#3085d6',
  //     cancelButtonColor: '#d33'
  //   }).then((result) => {
  //     if (result.isConfirmed) {
  //       // Hiển thị loading
  //       Swal.fire({
  //         title: 'Đang chạy Auto Scheduler...',
  //         text: 'Vui lòng chờ trong giây lát',
  //         allowOutsideClick: false,
  //         didOpen: () => {
  //           Swal.showLoading();
  //         },
  //       });

  //       // Gọi API
  //       router.put('/Schedual/scheduleAll', {}, {
  //         preserveScroll: true,
  //         onSuccess: () => {
  //           Swal.fire({
  //             icon: 'success',
  //             title:'Hoàn Thành Sắp Lịch',
  //             timer: 1000,
  //             showConfirmButton: false,
  //           });
  //         },
  //         onError: (errors) => {
  //           Swal.fire({
  //             icon: 'error',
  //             title:'Lỗi',
  //             timer: 1000,
  //             showConfirmButton: false,
  //           });
  //         },
  //       });
  //     }
  //   });
  // };

  const handleAutoSchedualer = () => {
    Swal.fire({
      title: 'Chọn ngày chạy Auto Scheduler',
      html: `
        <input id="schedule-date" type="date" class="swal2-input" value="${new Date().toISOString().split('T')[0]}">
      `,
      showCancelButton: true,
      confirmButtonText: 'Chạy',
      cancelButtonText: 'Hủy',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      preConfirm: () => {
        const date = document.getElementById('schedule-date').value;
        if (!date) {
          Swal.showValidationMessage('Vui lòng chọn ngày!');
        }
        return date;
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
        router.put('/Schedual/scheduleAll', { date: result.value }, {
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

  const toggleSlotDuration = () => {
    setSlotIndex((prevIndex) => {
      const nextIndex = (prevIndex + 1) % slotViews.length;
      const calendarApi = calendarRef.current?.getApi();
      calendarApi.changeView(slotViews[nextIndex]);
      return nextIndex;
    });
  };

  return (
    <div className={`transition-all duration-300 ${showSidebar ? percentShow == "30%"? 'w-[70%]':'w-[85%]' : 'w-full'} float-left pt-4 pl-2 pr-2`}>
    
      <FullCalendar
        schedulerLicenseKey="GPL-My-Project-Is-Open-Source"
        ref={calendarRef}
        plugins={[dayGridPlugin, resourceTimelinePlugin, interactionPlugin]}
        initialView="resourceTimelineWeek"
        firstDay={1}
        
        eventResourceEditable ={true}
        resources={resources}
        resourceAreaHeaderContent="Phòng Sản Xuất"
        events={events}
        locale="vi"
        height="auto"
        resourceAreaWidth="8%"
        editable={true}
        droppable={true}
        selectable={true}
        eventResizableFromStart={true}
        resourceGroupField= "stage"
        slotDuration= "00:15:00"
        eventDurationEditable={true}
        resourceEditable={true}
        eventStartEditable={true} // <- phải có để kéo thay đổi start
      
        eventClick={handleEventClick}
        eventResize={handleEventChange} 
        eventDrop={(info) => handleGroupEventDrop(info, selectedEvents, toggleEventSelect, handleEventChange)}
        eventReceive={handleEventReceive}
        dateClick ={handleEventUnHightLine}
        
        views={{

          resourceTimelineDay: {
            slotDuration: '00:15:00',
            slotMinTime: '00:00:00',
            slotMaxTime: '24:00:00',
            buttonText: 'Ngày',
            titleFormat: { year: 'numeric', month: 'short', day: 'numeric' },
          },
          resourceTimelineWeek: {
            slotDuration: '00:30:00',
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
            slotDuration: { weeks: 1 },
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
          left: 'prev,myToday,next hiddenClearning autoSchedualer deleteAllScheduale',
          center: 'title',
          right: 'fontSizeBox searchBox slotDuration customDay,customWeek,customMonth,customYear customList'
        }}

        customButtons={{
          customList: {
            text: 'KHSX',
            click: handleShowList 
          },
          customDay: {
            text: 'Ngày',
            click: () => handleViewChange('resourceTimelineDay')
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
            text: 'Hôm nay',
            click: () => calendarRef.current.getApi().today()
          },
          hiddenClearning: {
            text: 'Ẩn Vệ Sịnh',
            click: toggleCleaningEvents
          },
          autoSchedualer: {
            text: 'Sắp lịch Tự Động',
            click: handleAutoSchedualer
          },
          deleteAllScheduale: {
            text: 'Xóa Toàn Bộ',
            click: handleDeleteAllScheduale
          },
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

          // cho select evetn => pendingChanges
          const isPending = pendingChanges.some(e => e.id === info.event.id);
          if (isPending) {
            info.el.style.border = '2px dashed orange';
          }


          // lắng nghe double click
          info.el.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            handleEventHightLine(info.event, e.ctrlKey || e.metaKey);
          });



        }}

        eventContent={(arg) => {
        const isSelected = selectedEvents.some(ev => ev.id === arg.event.id);
        return (
        <div className="relative group custom-event-content" data-event-id={arg.event.id} >
            
            <div style={{ fontSize: `${eventFontSize}px` }}>
              <b>{arg.event.title}</b><br/>
              <span >{moment(arg.event.start).format('HH:mm')} - {moment(arg.event.end).format('HH:mm')}</span>
            </div>

            {/* Nút xóa */}
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
            </button>

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
          </div>

        )}}    
      />
      
      <ModalSidebar
        visible={showSidebar}
        onClose={() => setShowSidebar(false)}
        events={plan}
        percentShow = {percentShow}
        setPercentShow={setPercentShow}
      />

      {/* Vùng hover */}
      <div
          className="fixed top-0 right-0 h-full w-10 z-40"
        onMouseEnter={() => {

          if (selectedEvents.length > 0) setSidebarOpen(true);
        }}
        onMouseLeave={() => {
         
          setTimeout(() => {
            if (!isHoveringSidebar) setSidebarOpen(false);
          }, 200); 
        }}
      />

        {sidebarOpen && selectedEvents.length > 0 && (
          <div
            onMouseEnter={() => setIsHoveringSidebar(true)}
            onMouseLeave={() => {
              setIsHoveringSidebar(false);
              setSidebarOpen(false);
            }}
          >
            <SelectedEventsSidebar
              events={selectedEvents.map(id => calendarRef.current?.getApi().getEventById(id)).filter(Boolean)}
              onRemove={handleRemove}
              onClear={handleClear}
              onClose={() => setSidebarOpen(false)}
              pendingChanges={pendingChanges}
              handleSaveChanges={handleSaveChanges}
            />
          </div>
        )}

    </div>
  );
};

export default ScheduleTest;

ScheduleTest.layout = (page) => (
  <AppLayout title={page.props.title} user={page.props.user}>
    {page}
  </AppLayout>
);


