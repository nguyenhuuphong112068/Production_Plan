import React, { useRef, useState, useEffect, useCallback, useMemo } from 'react';
import ReactDOM from 'react-dom/client';
import FullCalendar from '@fullcalendar/react';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';
import { useHotkeys } from "react-hotkeys-hook";
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import axios from "axios";
import moment from 'moment';
import 'moment/locale/vi';
import dayjs from 'dayjs';
import Swal from 'sweetalert2';

import './calendar.css';
import CalendarSearchBox from '../Components/CalendarSearchBox';
import EventFontSizeInput from '../Components/EventFontSizeInput';

const AssignmentChart = () => {
  const calendarRef = useRef(null);
  const draggableRef = useRef(null);
  moment.locale('vi');

  // URL parameters & states
  const queryParams = new URLSearchParams(window.location.search);
  const groupCode = queryParams.get('group_code') || '';
  const [reportedDate, setReportedDate] = useState(dayjs().format('YYYY-MM-DD'));
  
  const [resources, setResources] = useState([]);
  const [events, setEvents] = useState([]);
  const [personnel, setPersonnel] = useState([]);
  const [dbAssignments, setDbAssignments] = useState({});
  const [authorization, setAuthorization] = useState(false);
  const [productionCode, setProductionCode] = useState('PXV1');
  const [groupName, setGroupName] = useState('');

  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [pendingChanges, setPendingChanges] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterUnder8h, setFilterUnder8h] = useState(false);
  
  // Font-size state
  const [eventFontSize, setEventFontSize] = useState(14);

  // Edit Dialog States
  const [showEditDialog, setShowEditDialog] = useState(false);
  const [editingEvent, setEditingEvent] = useState(null);
  const [editJobDescription, setEditJobDescription] = useState('');
  const [editSheet, setEditSheet] = useState(1);
  const [editStart, setEditStart] = useState('');
  const [editEnd, setEditEnd] = useState('');
  const [editPersonnelList, setEditPersonnelList] = useState([]);

  const groupNamesMap = {
    '1': "Trung Tâm Cân",
    '3': "Pha Chế",
    '4': "Văn Phòng",
    '5': "Định Hình",
    '6': "Bao Phim",
    '7': "ĐGSC",
    '8': "ĐGTC",
    '9': "VSCN + Kho BTP"
  };

  useEffect(() => {
    setGroupName(groupNamesMap[groupCode] || 'Sản Xuất');
  }, [groupCode]);

  // Load calendar & personnel data
  const loadData = useCallback(() => {
    if (!calendarRef.current) return;
    
    setLoading(true);
    const api = calendarRef.current.getApi();
    const activeStart = dayjs(reportedDate).startOf('day').toDate();
    const activeEnd = dayjs(reportedDate).endOf('day').toDate();

    axios.post("/assignemnt/production/chart/view", {
      startDate: activeStart.toISOString(),
      endDate: activeEnd.toISOString(),
      group_code: groupCode
    })
    .then(res => {
      const data = res.data;
      setResources(data.resources || []);
      setEvents(data.events || []);
      setPersonnel(data.personnel || []);
      setDbAssignments(data.dbAssignments || {});
      setAuthorization(data.authorization === 'Admin' || data.authorization === 'Schedualer');
      setProductionCode(data.production || 'PXV1');
      setPendingChanges([]);
    })
    .catch(err => {
      console.error("Lỗi tải dữ liệu Gantt Chart:", err);
      Swal.fire('Lỗi', 'Không thể tải thông tin phân công.', 'error');
    })
    .finally(() => {
      setLoading(false);
    });
  }, [reportedDate, groupCode]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  // Setup external draggable sidebar items
  useEffect(() => {
    const sidebarEl = document.getElementById('sidebar-personnel-list');
    if (!sidebarEl) return;

    if (draggableRef.current) {
      draggableRef.current.destroy();
    }

    draggableRef.current = new Draggable(sidebarEl, {
      itemSelector: '.draggable-person-card',
      eventData: (eventEl) => {
        const personnelId = eventEl.getAttribute('data-id');
        const name = eventEl.getAttribute('data-name');
        return {
          title: '👥 ' + name,
          duration: '08:00', // Default duration 8 hours
          create: true,
          extendedProps: {
            is_assignment: true,
            personnel_list: [{ personnel_id: parseInt(personnelId), notification: '' }],
            sheet: 1,
            job_description: ''
          }
        };
      }
    });

    return () => {
      if (draggableRef.current) {
        draggableRef.current.destroy();
        draggableRef.current = null;
      }
    };
  }, [personnel]);

  // Calculate working hours per employee on the active day
  const employeeHours = useMemo(() => {
    const hours = {};
    // Seed all personnel with 0 hours
    personnel.forEach(p => {
      hours[p.id] = 0;
    });

    // Sum hours from existing visual events (excluding plans)
    events.forEach(ev => {
      if (ev.is_assignment && ev.extendedProps?.personnel_list) {
        const start = dayjs(ev.start);
        const end = dayjs(ev.end);
        const diffHours = end.diff(start, 'hour', true);
        ev.extendedProps.personnel_list.forEach(p => {
          if (hours[p.personnel_id] !== undefined) {
            hours[p.personnel_id] += diffHours;
          }
        });
      }
    });
    return hours;
  }, [events, personnel]);

  // Filtered personnel for sidebar
  const filteredPersonnel = useMemo(() => {
    return personnel.filter(p => {
      const matchQuery = p.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
                         p.code.toLowerCase().includes(searchQuery.toLowerCase());
      
      if (!matchQuery) return false;

      if (filterUnder8h) {
        const hr = employeeHours[p.id] || 0;
        return hr < 8;
      }

      return true;
    });
  }, [personnel, searchQuery, filterUnder8h, employeeHours]);

  // Handle Event dropped from external sidebar
  const handleEventReceive = (info) => {
    const calendarApi = calendarRef.current.getApi();
    const droppedEvent = info.event;
    const resource = droppedEvent.getResources()?.[0];
    
    // Remove temporary visual element created by FullCalendar
    droppedEvent.remove();

    if (!resource || !resource.id.startsWith('personnel-')) {
      Swal.fire('Cảnh báo', 'Vui lòng kéo nhân viên thả trực tiếp vào ô "Nhân sự trực".', 'warning');
      return;
    }

    const roomId = resource.id.replace('personnel-', '');
    const startTime = dayjs(info.event.start);
    const endTime = startTime.add(8, 'hour');

    // Auto-detect sheet based on drop hour
    const startHour = startTime.hour();
    let sheetCode = 1;
    if (startHour >= 6 && startHour < 14) sheetCode = 1;
    else if (startHour >= 14 && startHour < 22) sheetCode = 2;
    else if (startHour >= 22 || startHour < 6) sheetCode = 3;

    // Check employee skill qualification level for this room
    const targetRoomId = parseInt(roomId);
    const draggedPersonnelId = info.draggedEl.getAttribute('data-id');
    const draggedPersonnelName = info.draggedEl.getAttribute('data-name');
    const employee = personnel.find(p => p.id == draggedPersonnelId);
    
    // Validate if employee is authorized for this room
    let isAuthorized = false;
    if (employee && employee.allowed_rooms_with_levels) {
      const roomsLevel = employee.allowed_rooms_with_levels.split('|');
      isAuthorized = roomsLevel.some(rl => {
        const [rId, level] = rl.split(':');
        return parseInt(rId) === targetRoomId && parseInt(level) >= 1;
      });
    }

    if (!isAuthorized && groupCode !== '9') {
      Swal.fire('Không được phép', `Nhân viên ${draggedPersonnelName} chưa có định mức tay nghề tại phòng này.`, 'error');
      return;
    }

    // Check duplicate assignment overlaps locally before saving
    const hasOverlap = events.some(ev => {
      if (!ev.is_assignment) return false;
      const isSamePerson = ev.extendedProps?.personnel_list?.some(p => p.personnel_id == draggedPersonnelId);
      if (!isSamePerson) return false;

      // Overlap time check
      const evStart = dayjs(ev.start);
      const evEnd = dayjs(ev.end);
      return startTime.isBefore(evEnd) && endTime.isAfter(evStart);
    });

    if (hasOverlap) {
      Swal.fire('Lỗi trùng lịch', `Nhân viên ${draggedPersonnelName} đã được sắp lịch trong khoảng thời gian này.`, 'error');
      return;
    }

    // Create the visual event on client-side
    const newEventId = 'temp-' + Date.now();
    const newEventObj = {
      id: newEventId,
      resourceId: resource.id,
      start: startTime.format('YYYY-MM-DD HH:mm:ss'),
      end: endTime.format('YYYY-MM-DD HH:mm:ss'),
      title: '👥 ' + draggedPersonnelName,
      color: '#dbeafe',
      textColor: '#1e40af',
      borderColor: '#bfdbfe',
      editable: true,
      is_assignment: true,
      extendedProps: {
        assignment_id: null,
        room_id: targetRoomId,
        sheet: sheetCode,
        job_description: '',
        personnel_list: [{ personnel_id: parseInt(draggedPersonnelId), notification: '' }]
      }
    };

    setEvents(prev => [...prev, newEventObj]);
    trackPendingChange(newEventObj);
  };

  // Track client changes for saving
  const trackPendingChange = (eventObj) => {
    setPendingChanges(prev => {
      const filtered = prev.filter(p => p.id !== eventObj.id);
      return [...filtered, eventObj];
    });
  };

  // Handle visual resize or drag-move
  const handleEventChange = (info) => {
    const changedEvent = info.event;
    const resource = changedEvent.getResources()?.[0];
    
    if (!resource || !resource.id.startsWith('personnel-')) {
      info.revert();
      Swal.fire('Lỗi', 'Không thể di chuyển nhân sự ra khỏi hàng phân bổ.', 'error');
      return;
    }

    const roomId = resource.id.replace('personnel-', '');
    const startTime = dayjs(changedEvent.start);
    const endTime = dayjs(changedEvent.end);

    // Verify qualification on drag to another room
    const targetRoomId = parseInt(roomId);
    const personnelList = changedEvent.extendedProps?.personnel_list || [];
    
    for (let p of personnelList) {
      const employee = personnel.find(emp => emp.id == p.personnel_id);
      let isAuthorized = false;
      if (employee && employee.allowed_rooms_with_levels) {
        const roomsLevel = employee.allowed_rooms_with_levels.split('|');
        isAuthorized = roomsLevel.some(rl => {
          const [rId, level] = rl.split(':');
          return parseInt(rId) === targetRoomId && parseInt(level) >= 1;
        });
      }
      if (!isAuthorized && groupCode !== '9') {
        info.revert();
        Swal.fire('Không được phép', `Nhân sự ${employee?.name} không đạt định mức tay nghề tại phòng mới.`, 'error');
        return;
      }
    }

    // Update state
    setEvents(prev => prev.map(ev => {
      if (ev.id === changedEvent.id) {
        const updated = {
          ...ev,
          resourceId: resource.id,
          start: startTime.format('YYYY-MM-DD HH:mm:ss'),
          end: endTime.format('YYYY-MM-DD HH:mm:ss'),
          extendedProps: {
            ...ev.extendedProps,
            room_id: targetRoomId
          }
        };
        trackPendingChange(updated);
        return updated;
      }
      return ev;
    }));
  };

  // Open Edit modal on double-click
  const handleEventClick = (info) => {
    const event = info.event;
    if (!event.extendedProps.is_assignment) return; // Only edit assignments

    setEditingEvent(event);
    setEditJobDescription(event.extendedProps.job_description || '');
    setEditSheet(event.extendedProps.sheet || 1);
    setEditStart(dayjs(event.start).format('HH:mm'));
    setEditEnd(dayjs(event.end).format('HH:mm'));
    
    // Copy personnel list from props
    const list = (event.extendedProps.personnel_list || []).map(p => {
      const emp = personnel.find(e => e.id == p.personnel_id);
      return {
        id: p.personnel_id,
        name: emp ? emp.name : 'Unknown',
        notification: p.notification || ''
      };
    });
    setEditPersonnelList(list);
    setShowEditDialog(true);
  };

  // Save detailed edits from Dialog
  const handleSaveDialogDetails = () => {
    if (editPersonnelList.length === 0) {
      Swal.fire('Cảnh báo', 'Vui lòng chọn ít nhất một nhân sự.', 'warning');
      return;
    }

    const startDtStr = dayjs(reportedDate).format('YYYY-MM-DD') + ' ' + editStart;
    let endDtStr = dayjs(reportedDate).format('YYYY-MM-DD') + ' ' + editEnd;
    
    // Night shift check (crosses midnight)
    if (editEnd < editStart) {
      endDtStr = dayjs(reportedDate).add(1, 'day').format('YYYY-MM-DD') + ' ' + editEnd;
    }

    const names = editPersonnelList.map(p => p.name).join(', ');
    const updatedPersonnelProps = editPersonnelList.map(p => ({
      personnel_id: p.id,
      notification: p.notification
    }));

    setEvents(prev => prev.map(ev => {
      if (ev.id === editingEvent.id) {
        const updated = {
          ...ev,
          start: startDtStr,
          end: endDtStr,
          title: '👥 ' + names + (editJobDescription ? ' (' + editJobDescription + ')' : ''),
          extendedProps: {
            ...ev.extendedProps,
            sheet: editSheet,
            job_description: editJobDescription,
            personnel_list: updatedPersonnelProps
          }
        };
        trackPendingChange(updated);
        return updated;
      }
      return ev;
    }));

    setShowEditDialog(false);
    setEditingEvent(null);
  };

  // Delete assignment
  const handleDeleteAssignment = () => {
    Swal.fire({
      title: 'Xóa phân công này?',
      text: "Phân công của nhân sự tại phòng này sẽ bị xóa khỏi lịch.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy'
    }).then((result) => {
      if (result.isConfirmed) {
        setEvents(prev => prev.filter(ev => ev.id !== editingEvent.id));
        setPendingChanges(prev => {
          // If it was an existing DB entry, add it to pending deleted or just remove it from list
          const filtered = prev.filter(p => p.id !== editingEvent.id);
          // If it was already saved in DB (id doesn't start with temp-) we'll handle deleting on save
          return filtered;
        });

        // Trigger loading deletion via save if already in DB
        setShowEditDialog(false);
        setEditingEvent(null);
      }
    });
  };

  // Save all changes to the database
  const handleSaveChanges = () => {
    if (!authorization) {
      Swal.fire('Quyền hạn', 'Bạn không có quyền lưu phân công công tác.', 'warning');
      return;
    }

    setSaving(true);
    
    // Format all assignments on this day to save
    const payloadAssignments = events
      .filter(ev => ev.is_assignment)
      .map(ev => ({
        id: ev.extendedProps.assignment_id,
        room_id: ev.extendedProps.room_id,
        sheet: ev.extendedProps.sheet,
        start: ev.start,
        end: ev.end,
        job_description: ev.extendedProps.job_description,
        personnel_list: ev.extendedProps.personnel_list
      }));

    axios.put("/assignemnt/production/chart/store", {
      group_code: groupCode,
      reportedDate: reportedDate,
      assignments: payloadAssignments
    })
    .then(res => {
      Swal.fire('Thành công', 'Đã lưu toàn bộ lịch phân công sản xuất thành công.', 'success');
      loadData();
    })
    .catch(err => {
      console.error("Lỗi khi lưu phân công:", err);
      Swal.fire('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra khi lưu phân công.', 'error');
    })
    .finally(() => {
      setSaving(false);
    });
  };

  // Client-side Auto Assign Personnel (Round-Robin)
  const handleAutoAssign = () => {
    Swal.fire({
      title: 'Tự động phân công?',
      text: "Thuật toán sẽ tự động phân phối nhân sự dựa trên kế hoạch sản xuất trong ngày và bậc kỹ năng của nhân viên.",
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Đồng ý',
      cancelButtonText: 'Hủy'
    }).then(result => {
      if (!result.isConfirmed) return;

      Swal.fire({
        title: 'Đang xếp lịch tự động...',
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // 1. Identify which shifts/rooms have active production plans
      const activeRoomsWithPlans = new Set();
      const planShifts = []; // array of {roomId, sheet, start, end, label}

      const dayStart = dayjs(reportedDate).setTime(6, 0, 0);

      // We read plans and mapping them to shift times
      events.forEach(ev => {
        if (ev.is_plan) {
          const planStart = dayjs(ev.start);
          const planEnd = dayjs(ev.end);
          const roomId = parseInt(ev.resourceId);
          activeRoomsWithPlans.add(roomId);

          // Map to Shifts
          // Shift 1: 06:00 - 14:00
          // Shift 2: 14:00 - 22:00
          // Shift 3: 22:00 - 06:00 (next day)
          const s1S = dayStart.copy();
          const s1E = dayStart.copy().add(8, 'hour');
          if (planStart.isBefore(s1E) && planEnd.isAfter(s1S)) {
            planShifts.push({ roomId, sheet: 1, start: s1S, end: s1E, label: ev.title });
          }
          const s2S = s1E.copy();
          const s2E = s2S.copy().add(8, 'hour');
          if (planStart.isBefore(s2E) && planEnd.isAfter(s2S)) {
            planShifts.push({ roomId, sheet: 2, start: s2S, end: s2E, label: ev.title });
          }
          const s3S = s2E.copy();
          const s3E = s3S.copy().add(8, 'hour');
          if (planStart.isBefore(s3E) && planEnd.isAfter(s3S)) {
            planShifts.push({ roomId, sheet: 3, start: s3S, end: s3E, label: ev.title });
          }
        }
      });

      if (planShifts.length === 0) {
        Swal.fire('Thông báo', 'Không có lịch sản xuất nào trong ngày để sắp xếp.', 'info');
        return;
      }

      // 2. Build personnel qualification pools
      const qualifiedPool = {}; // roomId => array of employee_id
      personnel.forEach(emp => {
        if (!emp.allowed_rooms_with_levels) return;
        const roomLevels = emp.allowed_rooms_with_levels.split('|');
        roomLevels.forEach(rl => {
          const [rId, level] = rl.split(':');
          const roomIdInt = parseInt(rId);
          const levelInt = parseInt(level);
          if (levelInt >= 1) {
            if (!qualifiedPool[roomIdInt]) qualifiedPool[roomIdInt] = [];
            qualifiedPool[roomIdInt].push({
              empId: emp.id,
              name: emp.name,
              level: levelInt
            });
          }
        });
      });

      // Sort qualified pools by level descending to prioritize higher skilled employees
      Object.keys(qualifiedPool).forEach(rId => {
        qualifiedPool[rId].sort((a, b) => b.level - a.level);
      });

      // 3. Round-robin assignment loop
      const tempAssignments = [];
      const employeeSchedule = {}; // empId => array of {start, end}

      // Group planShifts by Sheet to allocate personnel ca-by-ca
      const shiftsGrouped = { 1: [], 2: [], 3: [] };
      planShifts.forEach(ps => {
        shiftsGrouped[ps.sheet].push(ps);
      });

      let assignedCount = 0;

      [1, 2, 3].forEach(sheetCode => {
        const shifts = shiftsGrouped[sheetCode];
        if (shifts.length === 0) return;

        // Track how many employees are currently assigned to each room's shift
        const roomShiftsAssigned = {};
        shifts.forEach(s => {
          roomShiftsAssigned[s.roomId] = [];
        });

        // Loop to assign 1st person, then 2nd person to each room, etc. (Round-Robin)
        let addedInRound = true;
        let round = 0;
        
        while (addedInRound && round < 3) {
          addedInRound = false;
          round++;

          for (let s of shifts) {
            const pool = qualifiedPool[s.roomId] || [];
            // Find first available employee in pool
            const candidate = pool.find(c => {
              // Not already assigned in this shift to this room
              if (roomShiftsAssigned[s.roomId].includes(c.empId)) return false;

              // Check if they are busy/assigned in this time frame on any other room
              const busySlots = employeeSchedule[c.empId] || [];
              const isOverlapping = busySlots.some(bs => {
                return s.start.isBefore(bs.end) && s.end.isAfter(bs.start);
              });

              return !isOverlapping;
            });

            if (candidate) {
              roomShiftsAssigned[s.roomId].push(candidate.empId);
              if (!employeeSchedule[candidate.empId]) employeeSchedule[candidate.empId] = [];
              employeeSchedule[candidate.empId].push({ start: s.start, end: s.end });

              // Record assignment
              tempAssignments.push({
                roomId: s.roomId,
                sheet: sheetCode,
                start: s.start,
                end: s.end,
                empId: candidate.empId,
                name: candidate.name,
                jobDesc: s.label.replace('📦 ', '')
              });

              addedInRound = true;
              assignedCount++;
            }
          }
        }
      });

      // 4. Merge individual employee assignments to single events per room-shift
      // Group tempAssignments by roomId + sheetCode
      const groupedAssignments = {};
      tempAssignments.forEach(ta => {
        const key = ta.roomId + '-' + ta.sheet;
        if (!groupedAssignments[key]) groupedAssignments[key] = {
          roomId: ta.roomId,
          sheet: ta.sheet,
          start: ta.start,
          end: ta.end,
          employees: [],
          jobDesc: ta.jobDesc
        };
        groupedAssignments[key].employees.push({ personnel_id: ta.empId, name: ta.name });
      });

      // 5. Build final event list and apply
      const autoEvents = Object.values(groupedAssignments).map((ga, idx) => {
        const names = ga.employees.map(e => e.name).join(', ');
        const eventId = 'temp-auto-' + idx + '-' + Date.now();
        
        const newEvent = {
          id: eventId,
          resourceId: 'personnel-' + ga.roomId,
          start: ga.start.format('YYYY-MM-DD HH:mm:ss'),
          end: ga.end.format('YYYY-MM-DD HH:mm:ss'),
          title: '👥 ' + names,
          color: '#dbeafe',
          textColor: '#1e40af',
          borderColor: '#bfdbfe',
          editable: true,
          is_assignment: true,
          extendedProps: {
            assignment_id: null,
            room_id: ga.roomId,
            sheet: ga.sheet,
            job_description: ga.jobDesc,
            personnel_list: ga.employees.map(e => ({ personnel_id: e.personnel_id, notification: '' }))
          }
        };
        trackPendingChange(newEvent);
        return newEvent;
      });

      // Keep production plans, discard old personnel assignments, and append new auto-assigned ones
      setEvents(prev => {
        const plans = prev.filter(ev => ev.is_plan);
        return [...plans, ...autoEvents];
      });

      Swal.close();
      Swal.fire('Thành công', `Đã tự động sắp xếp ${assignedCount} lượt phân công nhân viên. Vui lòng bấm "Lưu thay đổi" để đồng bộ cơ sở dữ liệu.`, 'success');
    });
  };

  // Add personnel inside Dialog
  const handleAddPersonnelToEditList = (e) => {
    const pId = e.value;
    if (!pId) return;

    if (editPersonnelList.some(p => p.id === pId)) {
      Swal.fire('Cảnh báo', 'Nhân sự này đã nằm trong danh sách chọn.', 'warning');
      return;
    }

    const emp = personnel.find(p => p.id === pId);
    if (emp) {
      setEditPersonnelList(prev => [...prev, {
        id: emp.id,
        name: emp.name,
        notification: ''
      }]);
    }
  };

  // Remove personnel inside Dialog
  const handleRemovePersonnelFromEditList = (id) => {
    setEditPersonnelList(prev => prev.filter(p => p.id !== id));
  };

  // Update notification inside Dialog
  const handleUpdatePersonnelNotif = (id, notif) => {
    setEditPersonnelList(prev => prev.map(p => {
      if (p.id === id) {
        return { ...p, notification: notif };
      }
      return p;
    }));
  };

  return (
    <div className="w-full float-left pt-4 pl-2 pr-2">
      {/* Top Header controls */}
      <div className="flex justify-between items-center bg-white p-3 border-round shadow-1 mb-3">
        <div className="flex items-center gap-3">
          <span className="font-bold text-lg text-primary">{groupName} - Gantt Chart</span>
          <span className="font-bold text-gray-500">|</span>
          <div className="flex items-center gap-2">
            <button className="p-button p-button-sm p-button-outlined" onClick={() => setReportedDate(dayjs(reportedDate).subtract(1, 'day').format('YYYY-MM-DD'))}>⏴</button>
            <input type="date" className="p-inputtext p-inputtext-sm font-bold text-center border-1 border-gray-300" style={{ height: '32px', borderRadius: '4px' }} value={reportedDate} onChange={e => setReportedDate(e.target.value)} />
            <button className="p-button p-button-sm p-button-outlined" onClick={() => setReportedDate(dayjs(reportedDate).add(1, 'day').format('YYYY-MM-DD'))}>⏵</button>
            <button className="p-button p-button-sm p-button-secondary" onClick={() => setReportedDate(dayjs().format('YYYY-MM-DD'))}>Hôm nay</button>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <div className="flex items-center gap-2">
            <input type="checkbox" id="filter-under-8h-checkbox" checked={filterUnder8h} onChange={e => setFilterUnder8h(e.target.checked)} />
            <label htmlFor="filter-under-8h-checkbox" className="font-bold text-sm select-none cursor-pointer">Lọc làm việc &lt; 8h</label>
          </div>

          {authorization && (
            <>
              <button className="p-button p-button-sm p-button-info" style={{ backgroundColor: '#17a2b8', border: 'none' }} onClick={handleAutoAssign}>
                <i className="pi pi-android mr-2"></i> Tự động phân công
              </button>
              
              <button className={`p-button p-button-sm ${pendingChanges.length > 0 ? 'p-button-warning shadow-2' : 'p-button-primary'}`} onClick={handleSaveChanges} disabled={saving}>
                <i className="pi pi-save mr-2"></i> {pendingChanges.length > 0 ? `Lưu thay đổi (${pendingChanges.length})` : 'Lưu toàn bộ lịch'}
              </button>
            </>
          )}
        </div>
      </div>

      <div className="flex gap-3 w-full" style={{ minHeight: 'calc(100vh - 200px)' }}>
        
        {/* FullCalendar Area */}
        <div className="flex-1 bg-white p-3 border-round shadow-1" style={{ overflow: 'hidden' }}>
          <FullCalendar
            schedulerLicenseKey="GPL-My-Project-Is-Open-Source"
            ref={calendarRef}
            height="calc(100vh - 230px)"
            plugins={[resourceTimelinePlugin, interactionPlugin]}
            initialView="resourceTimelineDay"
            initialDate={reportedDate}
            locale="vi"
            resourceAreaWidth="230px"
            resourceAreaHeaderContent="Phòng Sản Xuất"
            expandRows={false}
            editable={authorization}
            droppable={authorization}
            selectable={false}
            
            // Events & Resources bindings
            resources={resources}
            events={events}

            // Drag-drop & resize bindings
            eventReceive={handleEventReceive}
            eventDrop={handleEventChange}
            eventResize={handleEventChange}
            eventClick={handleEventClick}

            resourceGroupField="stage_name"
            resourceOrder="order_by"
            slotDuration="01:00:00"
            slotLabelFormat={{
              hour: '2-digit',
              minute: '2-digit',
              hour12: false
            }}

            // Label customization (sets sub-row label compact)
            resourceLabelContent={(arg) => {
              const res = arg.resource.extendedProps;
              if (res.is_personnel_sub) {
                return {
                  html: `
                    <div style="font-size: 11px; font-weight: bold; padding-left: 15px; color: #475569; line-height: 20px; display: flex; align-items: center; height: 20px;">
                      ${arg.resource.title}
                    </div>
                  `
                };
              }
              return {
                html: `
                  <div style="font-size: 13px; font-weight: bold; color: #0f172a; height: 40px; line-height: 40px; display: flex; align-items: center;">
                    ${arg.resource.title}
                  </div>
                `
              };
            }}
          />
        </div>

        {/* Personnel Draggable Sidebar */}
        <div className="w-80 bg-white p-3 border-round shadow-1 flex flex-col gap-3" style={{ width: '280px' }}>
          <div className="font-bold text-md text-primary border-bottom pb-2 flex items-center">
            <i className="pi pi-users mr-2"></i> Tình Hình Nhân Sự
          </div>
          
          <input type="text" className="p-inputtext p-inputtext-sm w-full border-1 border-gray-300 rounded p-2" placeholder="Tìm tên hoặc mã NV..." value={searchQuery} onChange={e => setSearchQuery(e.target.value)} />

          <div id="sidebar-personnel-list" className="flex-1 overflow-y-auto flex flex-col gap-2 pr-1" style={{ maxHeight: 'calc(100vh - 350px)' }}>
            {filteredPersonnel.length === 0 ? (
              <div className="text-gray-400 text-sm text-center italic mt-5">Không tìm thấy nhân sự</div>
            ) : (
              filteredPersonnel.map(p => {
                const hrs = employeeHours[p.id] || 0;
                let bgStyle = 'bg-gray-50 border-gray-200';
                
                // Color badges based on assigned hours
                if (hrs >= 8) bgStyle = 'bg-green-50 border-green-200 text-green-800';
                else if (hrs > 0) bgStyle = 'bg-yellow-50 border-yellow-200 text-yellow-800';

                return (
                  <div
                    key={p.id}
                    data-id={p.id}
                    data-name={p.name}
                    className={`draggable-person-card p-2 border-round border-1 shadow-sm flex justify-between items-center cursor-grab ${bgStyle}`}
                    style={{ userSelect: 'none' }}
                  >
                    <div className="flex flex-col">
                      <span className="font-bold text-sm text-slate-800">{p.name}</span>
                      <span className="text-xs text-gray-500">{p.code}</span>
                    </div>
                    <span className="text-xs font-bold bg-white px-2 py-1 border-round shadow-1">{hrs.toFixed(1)}h</span>
                  </div>
                );
              })
            )}
          </div>
        </div>

      </div>

      {/* Edit Details Dialog */}
      <Dialog
        header="Chi tiết Phân công Nhân sự"
        visible={showEditDialog}
        style={{ width: '500px' }}
        onHide={() => setShowEditDialog(false)}
        footer={
          <div className="flex justify-between items-center w-full">
            <button className="p-button p-button-sm p-button-danger p-button-text" onClick={handleDeleteAssignment}>
              <i className="pi pi-trash mr-1"></i> Xóa
            </button>
            <div className="flex gap-2">
              <button className="p-button p-button-sm p-button-secondary" onClick={() => setShowEditDialog(false)}>Hủy</button>
              <button className="p-button p-button-sm p-button-primary" onClick={handleSaveDialogDetails}>Xác nhận</button>
            </div>
          </div>
        }
      >
        <div className="flex flex-col gap-3 pt-2">
          {/* Shift & Time selectors */}
          <div className="grid grid-cols-2 gap-3" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
            <div className="flex flex-col gap-1">
              <label className="font-bold text-xs">Ca làm việc</label>
              <select className="p-inputtext p-2 border-round border-1 border-gray-300" value={editSheet} onChange={e => setEditSheet(parseInt(e.target.value))}>
                <option value={1}>Ca 1 (06h - 14h)</option>
                <option value={2}>Ca 2 (14h - 22h)</option>
                <option value={3}>Ca 3 (22h - 06h)</option>
                <option value={4}>Ca Hành chính (07h15 - 16h)</option>
                <option value={5}>Ca Khác</option>
              </select>
            </div>
            
            <div className="flex flex-col gap-1" style={{ display: 'flex', flexDirection: 'column' }}>
              <label className="font-bold text-xs">Thời gian bắt đầu - kết thúc</label>
              <div className="flex gap-1 items-center" style={{ display: 'flex', gap: '5px', alignItems: 'center' }}>
                <input type="time" className="p-inputtext p-2 border-round border-1 border-gray-300" value={editStart} onChange={e => setEditStart(e.target.value)} />
                <span>-</span>
                <input type="time" className="p-inputtext p-2 border-round border-1 border-gray-300" value={editEnd} onChange={e => setEditEnd(e.target.value)} />
              </div>
            </div>
          </div>

          {/* Job description notes */}
          <div className="flex flex-col gap-1">
            <label className="font-bold text-xs">Nội dung công việc / Lưu ý</label>
            <input type="text" className="p-inputtext p-2 border-round border-1 border-gray-300 w-full" value={editJobDescription} onChange={e => setEditJobDescription(e.target.value)} placeholder="Chi tiết việc phân công..." />
          </div>

          {/* Personnel Selection */}
          <div className="flex flex-col gap-2">
            <div className="flex justify-between items-center border-bottom pb-1">
              <label className="font-bold text-xs text-primary">Danh sách Nhân sự trực</label>
              <Dropdown
                options={personnel.map(p => ({ label: p.name + ' (' + p.code + ')', value: p.id }))}
                onChange={handleAddPersonnelToEditList}
                placeholder="Thêm nhân sự..."
                className="w-48 text-xs"
                style={{ height: '30px', fontSize: '12px' }}
              />
            </div>

            <div className="flex flex-col gap-2 overflow-y-auto" style={{ maxHeight: '150px' }}>
              {editPersonnelList.map((p, idx) => (
                <div key={p.id} className="flex justify-between items-center bg-gray-50 border-1 border-gray-200 p-2 border-round">
                  <div className="flex flex-col flex-1">
                    <span className="font-bold text-xs text-slate-800">{chr(65 + idx)}. {p.name}</span>
                    <input
                      type="text"
                      className="p-inputtext p-1 text-xs border-round border-1 border-gray-300 mt-1"
                      value={p.notification}
                      onChange={e => handleUpdatePersonnelNotif(p.id, e.target.value)}
                      placeholder="Lưu ý riêng..."
                    />
                  </div>
                  <button className="p-button p-button-sm p-button-text p-button-danger" style={{ height: '24px', padding: '0 5px' }} onClick={() => handleRemovePersonnelFromEditList(p.id)}>
                    <i className="pi pi-times"></i>
                  </button>
                </div>
              ))}
            </div>
          </div>

        </div>
      </Dialog>
    </div>
  );
};

// Helper function to render letters (A, B, C...)
const chr = (code) => String.fromCharCode(code);

export default AssignmentChart;
