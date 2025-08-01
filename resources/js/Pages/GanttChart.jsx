import React, { useEffect, useState } from "react";
import { Scheduler } from "@bitnoi.se/react-scheduler";
import "@bitnoi.se/react-scheduler/dist/style.css";
import dayjs from "dayjs";
import AppLayout from "../Layouts/AppLayout";


export default function GanttChart({ title, user, data: events, rows: devices }) {
  const [schedulerData, setSchedulerData] = useState([]);
  
  const range = {
    startDate: dayjs().hour(7).startOf("hour").toDate(),
    endDate: dayjs().hour(19).endOf("hour").toDate(),
  };

  useEffect(() => {
    const converted = convertToSchedulerData(events, devices);
    setSchedulerData(converted);
  }, [events, devices]);



  function convertToSchedulerData(events, devices) {
    const deviceMap = {};
    devices.forEach((d) => {
      deviceMap[d.id] = {
        id: d.id,
        label: {
          icon: "",
          title: d.label,
        },
        data: [],
      };
    });

    events.forEach((e) => {
      if (deviceMap[e.rowId]) {
        deviceMap[e.rowId].data.push({
          id: e.id,
          title: e.title,
          startDate: new Date(e.startDate),
          endDate: new Date(e.endDate),
          bgColor: "#fef08a",
        });
      }
    });

    return Object.values(deviceMap);
  }

  const handleItemDrop = (startDate) => {
      window.alert (startDate);
    // console.log("Item dropped:", item);
  };

    const handleClick = (item) => {
    
      window.alert (item);
  };


  return (

    <div className="p-4 mt-[80px]">
  
      <Scheduler
       
        data={schedulerData}
        range={range}
        zoom="hour"
        onTileClick={(item) => handleItemDrop (item)}
        onItemClick={(startDate) => handleClick (startDate)}
        
      />
     
    </div>
  );
}
GanttChart.layout = page => (
  <AppLayout title={page.props.title} user={page.props.user}>
    {page}
  </AppLayout>
);
