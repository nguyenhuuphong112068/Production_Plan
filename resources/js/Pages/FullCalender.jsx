import React, { useRef, useState } from 'react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';
import moment from 'moment';
import 'moment/locale/vi';
import { usePage } from '@inertiajs/react';

import '@fullcalendar/daygrid/index.js';
import '@fullcalendar/resource-timeline/index.js';
import AppLayout from '../Layouts/AppLayout';
import dayjs from 'dayjs'
import { router } from '@inertiajs/react';
import ModalSidebar from '../Components/ModalSidebar';

import 'primereact/resources/themes/lara-light-indigo/theme.css';
import 'primereact/resources/primereact.min.css';                
import 'primeicons/primeicons.css'; 

const ScheduleTest = () => {

  const calendarRef = useRef(null); // 🔧 ref để điều khiển view
  moment.locale('vi');
  
  const { events, resources, title } = usePage().props;

  const [showSidebar, setShowSidebar] = useState(false);

  // Dữ liệu cứng để test
  const testEvents = [
    { id: 1, title: 'Sự kiện A', start: '2025-08-02 08:00', end: '2025-08-02 09:00', resourceId: 'P1' },
    { id: 2, title: 'Sự kiện B', start: '2025-08-02 10:00', end: '2025-08-02 11:00', resourceId: 'P2' },
    { id: 3, title: 'Sự kiện C', start: '2025-08-02 13:00', end: '2025-08-02 14:30', resourceId: 'P3' }
  ];
  
    const handleViewChange = (view) => {
      const calendarApi = calendarRef.current?.getApi();
      if (calendarApi) {
        calendarApi.changeView(view); // 🔧 đổi view tại đây
      }
    };
    // 1. Xử lý sự kiện tăng giảm thời gian
    const handleEventResize = async (info) => {
      window.alert ("sa")
      const { id, start, end } = info.event
      const resourceId = info.event.getResources?.()[0]?.id ?? null

      // Convert to local time string
      const formattedStart = dayjs(start).format('YYYY-MM-DD HH:mm:ss')
      const formattedEnd = dayjs(end).format('YYYY-MM-DD HH:mm:ss')


      router.put('/Schedual/update', {
        id: id,
        start: formattedStart,
        end: formattedEnd,
        resourceId: resourceId,
      }, {
        preserveScroll: true,
        onSuccess: () => console.log('Updated'),
        onError: (errors) => console.error('Update failed', errors),
      })
    }
    // 2. Xử lý Sự kiện Kéo
    const handleEventDrop = async (info) => {
      const { id, start, end } = info.event
      const resourceId = info.event.getResources?.()[0]?.id ?? null

      // Convert to local time string
      const formattedStart = dayjs(start).format('YYYY-MM-DD HH:mm:ss')
      const formattedEnd = dayjs(end).format('YYYY-MM-DD HH:mm:ss')


      router.put('/Schedual/update', {
        id: id,
        start: formattedStart,
        end: formattedEnd,
        resourceId: resourceId,
      }, {
        preserveScroll: true,
        onSuccess: () => console.log('Updated'),
        onError: (errors) => console.error('Update failed', errors),
      })
    };

    const handleEventSelect = async (info) => {
       
       
    };

    const handleShowList = async () => {
      setShowSidebar(true);
    };


    
  return (

<div
  id="Calender"
  className={`transition-all duration-300 ${showSidebar ? 'w-[70%]' : 'w-full'} float-right pt-3 pl-2 pr-2`}
>

      <FullCalendar
          schedulerLicenseKey="GPL-My-Project-Is-Open-Source"

          views={{
            resourceTimelineDay: {
              titleFormat: { year: 'numeric', month: 'short', day: 'numeric' }
            },
            resourceTimelineWeek: {
              titleFormat: { year: 'numeric', month: 'short', day: 'numeric' }
            }
          }}

          headerToolbar={{
              left: 'customList prev,next myToday',
              center: 'title',
              right: 'customDay,customWeek,customMonth,customYear'
            }}
            customButtons={{
              customList: {
                text: 'Danh sách',
                click: () => handleShowList()
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


        ref={calendarRef}
        plugins={[dayGridPlugin, resourceTimelinePlugin, interactionPlugin]}
        initialView="resourceTimelineWeek"
        slotDuration="00:30:00"

        slotMinTime="00:00:00" // Lịch bắt đầu từ 7 giờ sáng
        slotMaxTime="23:59:00" // Lịch kết thúc lúc 6 giờ chiều

        resources={resources}
        resourceAreaHeaderContent = 'Phòng Sản Xuất'

        events={events}
        locale="vi"
        height= "auto"
        resourceAreaWidth="10%" 
        editable={true} // Cho phép kéo thả
        eventResizableFromStart={true} // change time of event
        eventResize={handleEventResize} // handle change time of event

        selectable={true}
        eventClick= {handleEventSelect}

        droppable={true} // Cho phép kéo từ bên ngoài (nếu có)
        eventDrop={handleEventDrop} // Gọi khi kéo thả xong

        // eventContent={(arg) => (
        //   <div>
        //     <b>{arg.event.title}</b>
        //     <br />
        //     <small>
        //       {moment(arg.event.start).format('HH:mm')} - {moment(arg.event.end).format('HH:mm')}
        //     </small>
        //   </div>
        // )}

        eventContent={(arg) => {
          return (
            <div className="relative group">
              <b>{arg.event.title}</b>
              <br />
              <small>
                {moment(arg.event.start).format('HH:mm')} - {moment(arg.event.end).format('HH:mm')}
              </small>

              {/* Nút X hiện khi hover */}
              <button
                onClick={(e) => {
                  e.stopPropagation(); // tránh trigger eventClick
                  if (confirm('Bạn có chắc muốn xóa lịch này?')) {
                    arg.event.remove(); // xóa khỏi giao diện
                    // Nếu cần gọi API thì gọi thêm tại đây
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
          );
        }}



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

ScheduleTest.layout = page => (
  <AppLayout title={page.props.title} user={page.props.user}>
    {page}
  </AppLayout>
);

