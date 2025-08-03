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

  //01. Get data from row of Model Table
  useEffect(() => {
    new Draggable(document.getElementById('external-events'), {
      itemSelector: '.fc-event',
      eventData: function (eventEl) {
        // Lấy giá trị từ data-attributes
        const intermediate_code = eventEl.getAttribute('data-intermediate_code');
        const stage_code = parseInt(eventEl.getAttribute('data-stage'));     

        // Tìm bản ghi phù hợp trong quota
        const matched = quota.find(item =>
          item.intermediate_code === intermediate_code &&
          parseInt(item.stage_code) === stage_code
        );

        if (!matched) {
          Swal.fire({
            icon: 'warning',
            title: `Sản phẩm  ${eventEl.getAttribute('data-title')} chưa được định mức.`,
            text: `Vui Lòng Định Mức trước khi sắp lịch!`,
          });
          return false; 
        }
      
        const duration = matched.PMC2;
        const P_time = matched.P_time;
        const  M_time = matched.M_time;
        const  C1_time = matched.C1_time;
        const  C2_time = matched.C2_time;

        setSelectedRow({
            id: eventEl.getAttribute('data-id'),
            title: eventEl.getAttribute('data-title'),
            duration: duration,
            intermediate_code: intermediate_code,
            stage_code: stage_code,
            P_time: P_time,
            M_time: M_time,
            C1_time: C1_time,
            C2_time: C2_time,
        });

        return {
          title:  eventEl.getAttribute('data-title') ,
          duration: duration,
          extendedProps: {
          externalId: eventEl.getAttribute('data-id'),

          },
        };
      }
    });
  }, [quota]);



  const handleViewChange = (view) => {
    calendarRef.current?.getApi()?.changeView(view);
  };

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

  const handleEventDrop = async (info) => {
    const { id, start, end , title} = info.event;
    const resourceId = info.event.getResources?.()[0]?.id ?? null;

    console.log (info.event);

    router.put('/Schedual/update', {
      id,
      start: dayjs(start).format('YYYY-MM-DD HH:mm:ss'),
      end: dayjs(end).format('YYYY-MM-DD HH:mm:ss'),
      resourceId,
      title
    }, {
      preserveScroll: true,
      onSuccess: () => console.log('Updated'),
      onError: (errors) => console.error('Update failed', errors),
    });
  };


  const handleEventResize = async (info) => {
    const { id, start, end , title} = info.event;
    const resourceId = info.event.getResources?.()[0]?.id ?? null;

    router.put('/Schedual/update', {
      id,
      start: dayjs(start).format('YYYY-MM-DD HH:mm:ss'),
      end: dayjs(end).format('YYYY-MM-DD HH:mm:ss'),
      resourceId,
      title
      
    }, {
      preserveScroll: true,
      onSuccess: () => console.log('Updated'),
      onError: (errors) => console.error('Update failed', errors),
    });
  };



  const handleEventReceive = async (info) => {

    const { id, start, end } = info.event;
    const resourceId = info.event.getResources?.()[0]?.id ?? null;
    const [hours, minutes] = selectedRow.C2_time.split(':').map(Number);
    const C_end = dayjs(end).add(hours, 'hour').add(minutes, 'minute').format('YYYY-MM-DD HH:mm:ss');

    router.put('/Schedual/store', {
      id: selectedRow.id,
      start: dayjs(start).format('YYYY-MM-DD HH:mm:ss'),
      end: dayjs(end).format('YYYY-MM-DD HH:mm:ss'),
      resourceId,
      title: selectedRow.title,
      C_end: C_end

    }, {
      preserveScroll: true,
      onSuccess: () => console.log('create'),
      onError: (errors) => console.error('create failed', errors),
    });
    setSelectedRow ({});
    // Optional: gửi API để tạo lịch mới từ dữ liệu bên ngoài
  };

  const handleShowList = () => {
    setShowSidebar(true);
  };



  return (
    <div className={`transition-all duration-300 ${showSidebar ? 'w-[70%]' : 'w-full'} float-right pt-3 pl-2 pr-2`}>

      <FullCalendar
        schedulerLicenseKey="GPL-My-Project-Is-Open-Source"
        ref={calendarRef}
        plugins={[dayGridPlugin, resourceTimelinePlugin, interactionPlugin]}
        initialView="resourceTimelineWeek"
        slotDuration="00:15:00"
        slotMinTime="00:00:00"
        slotMaxTime="23:59:00"
        resources={resources}
        resourceAreaHeaderContent="Phòng Sản Xuất"
        events={events}
        locale="vi"
        height="auto"
        resourceAreaWidth="10%"
        editable={true}
        droppable={true}
        selectable={true}
        eventResizableFromStart={true}
        resourceGroupField="stage"


        eventClick={handleEventSelect}
        eventResize={handleEventResize}
        eventDrop={handleEventDrop}
        eventReceive={handleEventReceive}


        views={{
          resourceTimelineDay: { titleFormat: { year: 'numeric', month: 'short', day: 'numeric' } },
          resourceTimelineWeek: { titleFormat: { year: 'numeric', month: 'short', day: 'numeric' } }
        }}
        headerToolbar={{
          left: 'customList prev,next myToday',
          center: 'title',
          right: 'customDay,customWeek,customMonth,customYear'
        }}

        customButtons={{
          customList: {
            text: 'Danh sách',
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
          }
        }}

        eventContent={(arg) => (
          <div className="relative group">
            <b>{arg.event.title}</b><br />
            <small>{moment(arg.event.start).format('HH:mm')} - {moment(arg.event.end).format('HH:mm')}</small>

            <button onClick={(e) => {
                e.stopPropagation();
                if (confirm('Bạn có chắc muốn xóa lịch này?')) {
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
              }}
              className="absolute top-0 right-0 hidden group-hover:block text-red-500 text-sm bg-white px-1 rounded shadow"
              title="Xóa lịch">×</button>
          </div>
        )}
      />
      
      <ModalSidebar
        visible={showSidebar}
        onClose={() => setShowSidebar(false)}
        events={plan}
        
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
