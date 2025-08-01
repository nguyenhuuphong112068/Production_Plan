import React, { useRef } from 'react';
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

const ScheduleTest = () => {

  const calendarRef = useRef(null); // 🔧 ref để điều khiển view
  moment.locale('vi');
  

  const { events, resources, title } = usePage().props;

  

  const handleViewChange = (view) => {
    const calendarApi = calendarRef.current?.getApi();
    if (calendarApi) {
      calendarApi.changeView(view); // 🔧 đổi view tại đây
    }
  };

  const handleEventResize = async (info) => {
    const { id, start, end } = info.event;
   
    // try {
    //   await axios.put(`/api/schedules/${id}`, {
    //     start: start.toISOString(),
    //     end: end.toISOString(),
    //   });
    alert('Cập nhật thành công!');
    // } catch (error) {
    //   console.error('Resize error', error);
    //   alert('Cập nhật thất bại.');
    //   info.revert(); // hoàn tác nếu lỗi
    // }
  };
  const handleEventSelect = async (info) => {
  
  //  console.log (info.resource)
    // try {
    //   await axios.put(`/api/schedules/${id}`, {
    //     start: start.toISOString(),
    //     end: end.toISOString(),
    //   });
    alert('Cập nhật thành công!');
    // } catch (error) {
    //   console.error('Resize error', error);
    //   alert('Cập nhật thất bại.');
    //   info.revert(); // hoàn tác nếu lỗi
    // }
  };

 
  return (
    <div style={{ margin: '20px' }}>

      {/* <div  style={{ marginBottom: '10px' }}>
        <button className='btn btn-success' onClick={() => handleViewChange('resourceTimelineDay')}>Ngày</button>
        <button className='btn btn-success' onClick={() => handleViewChange('resourceTimelineWeek')}>Tuần</button>
        <button className='btn btn-success' onClick={() => handleViewChange('resourceTimelineMonth')}>Tháng</button>
        <button className='btn btn-success' onClick={() => handleViewChange('resourceTimelineYear')}>Năm</button>
        
      </div> */}
      

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
              left: 'prev,next myToday',
              center: 'title',
              right: 'customDay,customWeek,customMonth,customYear'
            }}
            customButtons={{
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
        events={events}
        locale="vi"
        height= "auto"
        resourceAreaWidth="10%" 
        editable={true} // select event
        eventResizableFromStart={true} // change time of event
        eventResize={handleEventResize} // handle change time of event

        selectable={true}
        dateClick= {handleEventSelect}

        eventContent={(arg) => (
          <div>
            <b>{arg.event.title}</b>
            <br />
            <small>
              {moment(arg.event.start).format('HH:mm')} - {moment(arg.event.end).format('HH:mm')}
            </small>
          </div>
        )}
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

