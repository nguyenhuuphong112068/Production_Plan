import React, { useRef, useState, useEffect } from 'react';
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

import 'moment/locale/vi';
import '@fullcalendar/daygrid/index.js';
import '@fullcalendar/resource-timeline/index.js';

import 'primereact/resources/themes/lara-light-indigo/theme.css';
import 'primereact/resources/primereact.min.css';                
import 'primeicons/primeicons.css'; 
import { Button, Col, Form, InputGroup, Row } from 'react-bootstrap';

  const ScheduleTest = () => {
  const calendarRef = useRef(null);
  moment.locale('vi');

  //0. Get data
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
  const [searchText, setSearchText] = useState("");
  const [percentShow, setPercentShow] = useState("15%");


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
        const stage_code = parseInt(eventEl.getAttribute('data-stage'));
        
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

  const handleShowList = () => setShowSidebar(!showSidebar);

  const handleViewChange = (view) => {

    setViewConfig({is_clearning:false })
    calendarRef.current?.getApi()?.changeView(view);

  }

  const handleEventHightLine = async (info) => {
    const calendarApi = calendarRef.current.getApi();
    const allEvents = calendarApi.getEvents();
    const clickedId = info.event.extendedProps.plan_master_id;


    // Bỏ class cũ nếu có
    document.querySelectorAll('.fc-event').forEach(el => {
      el.classList.remove('highlight-event');
    });

    // Tìm và highlight các event có cùng plan_master_id
    allEvents.forEach(event => {
      if (event.extendedProps.plan_master_id === clickedId) {

        const el = event._def.ui?.el; // Không chắc chắn hỗ trợ
        const dom = document.querySelector(`[data-event-id="${event.id}"]`);

        // Cách đáng tin cậy: dùng custom attribute
        const allRenderedEls = document.querySelectorAll('.fc-event');

        allRenderedEls.forEach(el => {
          if (el.innerText.includes(event.title)) {
            el.classList.add('highlight-event');
          }
        });
      }
    });

  };

  const handleEventUnHightLine = async (info) => {
      document.querySelectorAll('.fc-event').forEach(el => {
      el.classList.remove('highlight-event');
    });
  }
 
  const handleEventReceive = async (info) => {
      const draggedRows = info.event.extendedProps?.rows || [];
      const resourceId = info.event.getResources?.()[0]?.id ?? null;
      const start = info.event.start;
      const matchedRow = quota.find(item =>item.instrument_id == resourceId);

      
      if (matchedRow.stage_code != selectedRow.stage_code){
        info.event.remove();  
        Swal.fire({
            icon: 'warning',
            title:'Sắp Lịch Sai Công Đoạn',
            //text: 'Bạn Đang Sắp Lịch ' + selectedRow.title + ,
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
        toggleEventSelect(draggedEvent.id);
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

  const toggleEventSelect = (eventId) => {
     
      setSelectedEvents((prevSelected) =>
        prevSelected.includes(eventId)
          ? prevSelected.filter((id) => id !== eventId)
          : [...prevSelected, eventId]
      );
     
  };

  const handleEventClick = (clickInfo) => {
    const eventId = clickInfo.event.id;
    
    if (clickInfo.jsEvent.shiftKey || clickInfo.jsEvent.ctrlKey || clickInfo.jsEvent.metaKey) {
      // Toggle nếu đang giữ Shift/Ctrl
      setSelectedEvents([eventId]);
    } else {
      // Nếu không giữ gì thì chọn riêng lẻ
      toggleEventSelect(eventId);
    }

    console.log (selectedEvents);
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

  const handleSearch = () => {

        const calendarApi = calendarRef.current?.getApi();
        if (!calendarApi) return;

        const targetEvent = calendarApi.getEvents().find(ev =>
          ev.title.toLowerCase().includes(searchText.toLowerCase())
        );

        if (targetEvent) {
          const el = document.querySelector(`[data-event-id="${targetEvent._def.publicId}"]`);
          if (el) {
            el.scrollIntoView({ behavior: "smooth", block: "center" });
            el.classList.add("highlight-event");
            setTimeout(() => {
              el.classList.remove("highlight-event");
            }, 3000);
          }
        } else {
          Swal.fire({
              icon: 'info',
              title: 'Không tìm thấy',
              text: 'Không có sự kiện nào khớp với từ khóa tìm kiếm.',
              confirmButtonText: 'OK'
            });
        }
  };

  return (
    <div className={`transition-all duration-300 ${showSidebar ? percentShow == "30%"? 'w-[70%]':'w-[85%]' : 'w-full'} float-left pt-4 pl-2 pr-2`}>
      
      <Row className='ps-2 pe-2'>

        <Col md={10} sm={6}>

        </Col>
        <Col md={2} sm={6}>
            <InputGroup className="mb-3">
            <Form.Control
              aria-label="Recipient's username"
              aria-describedby="basic-addon2"
              type="text"
              placeholder="Tìm sản phẩm..."
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
            />
            <Button variant="outline-secondary" id="button-addon2" onClick={handleSearch}>
              <i className="fas fa-search"></i>
            </Button>
          </InputGroup>
        </Col>
      </Row>



      <FullCalendar
        schedulerLicenseKey="GPL-My-Project-Is-Open-Source"
        ref={calendarRef}
        plugins={[dayGridPlugin, resourceTimelinePlugin, interactionPlugin]}
        initialView="resourceTimelineWeek"
        firstDay={1} 

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
            slotDuration: { weeks: 1 },
            slotMinTime: '00:00:00',
            slotMaxTime: '24:00:00',
            buttonText: 'Năm',
            titleFormat: { year: 'numeric' }
          }
        }}
        
        headerToolbar={{
          left: 'prev,myToday,next hiddenClearning',
          center: 'title',
          right: 'customDay,customWeek,customMonth,customYear customList'
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
          }

        }}


        eventDidMount={(info) => {
          const isPending = pendingChanges.some(e => e.id === info.event.id);
          if (isPending) {
            info.el.style.border = '2px dashed orange';
          }

          info.el.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            handleEventHightLine(info); 

          });

          
        }}

        eventContent={(arg) => {
        const isSelected = selectedEvents.includes(arg.event.id);
          

        return (
        <div className="relative group custom-event-content" data-event-id={arg.event.id} >
            <b>{arg.event.title}</b><br />
            <small>{moment(arg.event.start).format('HH:mm')} - {moment(arg.event.end).format('HH:mm')}</small>
           
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
                      { ids: selectedEvents.map(ev => ev) }
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
                  toggleEventSelect(arg.event.id);
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
