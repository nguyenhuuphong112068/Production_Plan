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

import 'moment/locale/vi';
import '@fullcalendar/daygrid/index.js';
import '@fullcalendar/resource-timeline/index.js';

import 'primereact/resources/themes/lara-light-indigo/theme.css';
import 'primereact/resources/primereact.min.css';                
import 'primeicons/primeicons.css'; 

const ScheduleTest = () => {
  const calendarRef = useRef(null);
  moment.locale('vi');

  const { events, resources, title } = usePage().props;
  const [showSidebar, setShowSidebar] = useState(false);

  const testEvents = [
    { id: 1, title: 'Paracetamol EG 100 mg - 010125', duration: '10:00' , resourceIdGroup: 'E02, E03' , plan_stage_code: 1 , expertedDate: '2025-08-30' },
    { id: 1, title: 'Paracetamol EG 100 mg - 010125', duration: '15:00' , resourceIdGroup: 'E02, E03' , plan_stage_code: 4 , expertedDate: '2025-08-30' },
    { id: 1, title: 'Paracetamol EG 100 mg - 010125', duration: '11:00' , resourceIdGroup: 'E02, E03' , plan_stage_code: 5 , expertedDate: '2025-08-30' },
  ];

  useEffect(() => {
    new Draggable(document.getElementById('external-events'), {
      itemSelector: '.fc-event',
      eventData: function (eventEl) {
        return {
          title: eventEl.getAttribute('data-title'),
          duration: eventEl.getAttribute('data-duration'),
          extendedProps: {
          externalId: eventEl.getAttribute('data-id'),
          }
        };
      }
    });
  }, []);

  const handleViewChange = (view) => {
    calendarRef.current?.getApi()?.changeView(view);
  };

  const handleEventSelect = async (info) => {
    // Event click logic
  };

  const handleEventResize = async (info) => {
    const { id, start, end } = info.event;
    const resourceId = info.event.getResources?.()[0]?.id ?? null;

    router.put('/Schedual/update', {
      id,
      start: dayjs(start).format('YYYY-MM-DD HH:mm:ss'),
      end: dayjs(end).format('YYYY-MM-DD HH:mm:ss'),
      resourceId,
    }, {
      preserveScroll: true,
      onSuccess: () => console.log('Updated'),
      onError: (errors) => console.error('Update failed', errors),
    });
  };

  const handleEventDrop = async (info) => {
    const { id, start, end } = info.event;
    const resourceId = info.event.getResources?.()[0]?.id ?? null;

    router.put('/Schedual/update', {
      id,
      start: dayjs(start).format('YYYY-MM-DD HH:mm:ss'),
      end: dayjs(end).format('YYYY-MM-DD HH:mm:ss'),
      resourceId,
    }, {
      preserveScroll: true,
      onSuccess: () => console.log('Updated'),
      onError: (errors) => console.error('Update failed', errors),
    });
  };

  const handleEventReceive = async (info) => {
    const { title, start, end } = info.event;
    const resourceId = info.event.getResources?.()[0]?.id ?? null;

    console.log("Sự kiện được thả từ bên ngoài:", {
      title,
      start,
      end,
      resourceId,
    });

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
        slotDuration="00:30:00"
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
            <button
              onClick={(e) => {
                e.stopPropagation();
                if (confirm('Bạn có chắc muốn xóa lịch này?')) {
                  arg.event.remove();
                  router.delete(`/Schedual/${arg.event.id}`, {
                    onSuccess: () => console.log('Đã xóa lịch thành công'),
                    onError: () => console.error('Xóa lịch thất bại')
                  });
                }
              }}
              className="absolute top-0 right-0 hidden group-hover:block text-red-500 text-sm bg-white px-1 rounded shadow"
              title="Xóa lịch"
            >
              ×
            </button>
          </div>
        )}
      />

      <ModalSidebar
        visible={showSidebar}
        onClose={() => setShowSidebar(false)}
        events={testEvents}
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
