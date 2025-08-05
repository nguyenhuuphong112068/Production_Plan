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
  const [slot, setSlot] = useState('00:15:00');


  useEffect(() => {
  new Draggable(document.getElementById('external-events'), {
    itemSelector: '.fc-event',
    eventData: function (eventEl) {
      const isMulti = eventEl.hasAttribute('data-rows');

      if (isMulti) {

        const draggedData = JSON.parse(eventEl.getAttribute('data-rows') || '[]');
        console.log (draggedData)

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



  const handleShowList = () => setShowSidebar(!showSidebar);

  const handleViewChange = (view) => {

    setViewConfig({is_clearning:false })
    calendarRef.current?.getApi()?.changeView(view);

  }

  const handleEventSelect = async (info) => {
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


  const handleSlotChange = (changeInfo) =>{
        setSlot (changeInfo);
        calendarRef.current?.getApi()?.changeView(view);
  }


  return (
    <div className={`transition-all duration-300 ${showSidebar ? 'w-[70%]' : 'w-full'} float-left pt-3 pl-2 pr-2`}>
        <button onClick={handleSaveChanges} disabled={pendingChanges.length === 0} className='btn btn-success p-2'>
           Lưu thay đổi ({pendingChanges.length})
         </button>


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
        resourceAreaWidth="7%"
        editable={true}
        droppable={true}
        selectable={true}
        eventResizableFromStart={true}
        resourceGroupField="stage"
        slotDuration= {slot}

        

        eventClick={handleEventSelect}
        eventResize={handleEventChange} //{handleEventResize}
        eventDrop= {handleEventChange} //{handleEventDrop}
        eventReceive={handleEventReceive}
        dateClick ={handleEventUnHightLine}
        
        views={{

          resourceTimelineDay: {
            slotDuration: '00:30:00',
            slotMinTime: '06:00:00',
            slotMaxTime: '24:00:00',
            buttonText: 'Ngày',
            titleFormat: { year: 'numeric', month: 'short', day: 'numeric' },
          },
          resourceTimelineWeek: {
            slotDuration: '00:15:00',
            slotMinTime: '06:00:00',
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
          left: 'prev,next myToday' ,
          center: 'title',
          right: 'customDay,customWeek,customMonth,customYear customList slotDuration15m,slotDuration1H,slotDuration4H'
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

          slotDuration15m: {
            text: '15M',
            click: () => handleSlotChange('00:15:00')
          },
          slotDuration1H: {
            text: '1H',
            click: () => handleSlotChange('01:00:00')
          },
          slotDuration4H: {
            text: '4H',
            click: () => handleSlotChange('04:00:00')
          },


          myToday: {
            text: 'Hôm nay',
            click: () => calendarRef.current.getApi().today()
          }
        }}



        eventDidMount={(info) => {
          const isPending = pendingChanges.some(e => e.id === info.event.id);
          if (isPending) {
            info.el.style.border = '2px dashed orange';
          }
        }}

        eventContent={(arg) => (
          <div className="relative group" data-event-id={arg.event.id}>
            <b>{arg.event.title}</b><br />
            <small>{moment(arg.event.start).format('HH:mm')} - {moment(arg.event.end).format('HH:mm')}</small>

           <button
              onClick={(e) => {
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
                    router.put(`/Schedual/deActive/${arg.event.id}`, {
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


          </div>
        )}
      />
      
      <ModalSidebar
        visible={showSidebar}
        onClose={() => setShowSidebar(false)}
        events={plan}
        
      />

      <button onClick={toggleCleaningEvents}>
        Ẩn các lịch vệ sinh
      </button>

    </div>
  );
};

export default ScheduleTest;

ScheduleTest.layout = (page) => (
  <AppLayout title={page.props.title} user={page.props.user}>
    {page}
  </AppLayout>
);
