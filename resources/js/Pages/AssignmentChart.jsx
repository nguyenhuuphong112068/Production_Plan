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

// ─── Week calculation helpers ─────────────────────────────────────────────────
/** Returns 'YYYY-MM-DD' of the Monday of the ISO week containing dateStr */
const getWeekMonday = (dateStr) => {
  const d = dayjs(dateStr);
  const dayOfWeek = d.day(); // 0=Sun, 1=Mon,...,6=Sat
  const daysFromMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
  return d.subtract(daysFromMonday, 'day').format('YYYY-MM-DD');
};

/** Returns ISO week number for a given date string */
const getISOWeekNumber = (dateStr) => {
  const d = new Date(dateStr);
  d.setHours(0, 0, 0, 0);
  d.setDate(d.getDate() + 4 - (d.getDay() || 7));
  const yearStart = new Date(d.getFullYear(), 0, 1);
  return Math.ceil(((d - yearStart) / 86400000 + 1) / 7);
};

/** Converts a date string to the format for <input type="week"> (e.g. "2026-W22") */
const dateToWeekInputValue = (dateStr) => {
  const monday = getWeekMonday(dateStr);
  const year = dayjs(monday).year();
  const week = getISOWeekNumber(monday);
  return `${year}-W${String(week).padStart(2, '0')}`;
};

/** Converts a week input value (e.g. "2026-W22") to the Monday date string */
const weekInputToMonday = (weekStr) => {
  const [yearStr, weekPart] = weekStr.split('-W');
  const year = parseInt(yearStr);
  const week = parseInt(weekPart);
  const jan4 = new Date(year, 0, 4);
  const jan4Day = jan4.getDay() || 7;
  const mondayOfWeek1 = new Date(jan4.getTime() - (jan4Day - 1) * 86400000);
  const targetMonday = new Date(mondayOfWeek1.getTime() + (week - 1) * 7 * 86400000);
  return dayjs(targetMonday).format('YYYY-MM-DD');
};
// ─────────────────────────────────────────────────────────────────────────────

const AssignmentChart = () => {
  const calendarRef = useRef(null);
  const draggableRef = useRef(null);
  const dateInputDomRef = useRef(null);
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

  // Personnel sidebar toggle (like FullCalender.jsx ModalSidebar)
  const [showPersonnelSidebar, setShowPersonnelSidebar] = useState(true);

  // View mode: 'day' (single day) | 'week' (ISO week Mon-Sun)
  const [viewMode, setViewMode] = useState('day');
  const viewModeRef = useRef('day');
  // Keep viewModeRef in sync (used inside customButton click closures)
  useEffect(() => { viewModeRef.current = viewMode; }, [viewMode]);

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

    // Calculate date range based on viewMode
    let startDate, endDate;
    if (viewMode === 'week') {
      const monday = getWeekMonday(reportedDate);
      startDate = dayjs(monday).startOf('day').toDate();
      endDate   = dayjs(monday).add(6, 'day').endOf('day').toDate();
    } else {
      startDate = dayjs(reportedDate).startOf('day').toDate();
      endDate   = dayjs(reportedDate).endOf('day').toDate();
    }

    axios.post("/assignemnt/production/chart/view", {
      startDate: startDate.toISOString(),
      endDate:   endDate.toISOString(),
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
  }, [reportedDate, groupCode, viewMode]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  // Navigate calendar to correct date when reportedDate changes
  useEffect(() => {
    if (calendarRef.current) {
      const target = viewMode === 'week' ? getWeekMonday(reportedDate) : reportedDate;
      calendarRef.current.getApi().gotoDate(target);
    }
  }, [reportedDate, viewMode]);

  // Switch FullCalendar view (day ↔ week) when viewMode changes
  useEffect(() => {
    if (!calendarRef.current) return;
    const api = calendarRef.current.getApi();
    if (viewMode === 'week') {
      api.changeView('resourceTimelineWeek');
      api.gotoDate(getWeekMonday(reportedDate));
    } else {
      api.changeView('resourceTimelineDay');
      api.gotoDate(reportedDate);
    }
  }, [viewMode]); // eslint-disable-line react-hooks/exhaustive-deps

  // Highlight active view-mode button in toolbar
  useEffect(() => {
    const timer = setTimeout(() => {
      const dayBtn  = document.querySelector('.fc-viewDay-button');
      const weekBtn = document.querySelector('.fc-viewWeek-button');
      if (!dayBtn || !weekBtn) return;
      const active   = 'background:#3b82f6!important;border-color:#2563eb!important;color:white!important;font-weight:700!important;';
      const inactive = '';
      dayBtn.style.cssText  = viewMode === 'day'  ? active : inactive;
      weekBtn.style.cssText = viewMode === 'week' ? active : inactive;
    }, 150);
    return () => clearTimeout(timer);
  }, [viewMode]);

  // Re-inject date/week input into toolbar when viewMode changes
  useEffect(() => {
    const timeout = setTimeout(() => {
      const btn = document.querySelector('.fc-dateInput-button');
      if (!btn) return;
      btn.innerHTML = '';
      const isWeek = viewMode === 'week';
      const input  = document.createElement('input');
      input.type   = isWeek ? 'week' : 'date';
      input.value  = isWeek ? dateToWeekInputValue(reportedDate) : reportedDate;
      input.title  = isWeek ? 'Chọn tuần' : 'Chọn ngày';
      input.style.cssText = [
        'height: 28px',
        'padding: 2px 8px',
        'border: 1px solid #d1d5db',
        'border-radius: 6px',
        'font-weight: 600',
        'font-size: 13px',
        'color: #1e293b',
        'cursor: pointer',
        'outline: none'
      ].join(';');
      input.addEventListener('change', (e) => {
        if (viewMode === 'week') {
          setReportedDate(weekInputToMonday(e.target.value));
        } else {
          setReportedDate(e.target.value);
        }
      });
      btn.appendChild(input);
      dateInputDomRef.current = input;
    }, 200);
    return () => clearTimeout(timeout);
  }, [viewMode]); // eslint-disable-line react-hooks/exhaustive-deps

  // Keep toolbar date input value in sync with reportedDate state
  useEffect(() => {
    if (dateInputDomRef.current) {
      dateInputDomRef.current.value = viewMode === 'week'
        ? dateToWeekInputValue(reportedDate)
        : reportedDate;
    }
  }, [reportedDate, viewMode]);

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
        const threshold = viewMode === 'week' ? 40 : 8;
        return hr < threshold;
      }

      return true;
    });
  }, [personnel, searchQuery, filterUnder8h, employeeHours, viewMode]);

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
    // In week mode (slotDuration=1 day), dropped time is 00:00 → snap to 06:00
    let startTime = dayjs(info.event.start);
    if (viewModeRef.current === 'week') {
      startTime = startTime.startOf('day').hour(6).minute(0).second(0);
    }
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

    // Use the event's actual date (important for week mode where event may not be on reportedDate)
    const eventDate = editingEvent
      ? dayjs(editingEvent.start).format('YYYY-MM-DD')
      : dayjs(reportedDate).format('YYYY-MM-DD');
    const startDtStr = eventDate + ' ' + editStart;
    let endDtStr = eventDate + ' ' + editEnd;
    
    // Night shift check (crosses midnight)
    if (editEnd < editStart) {
      endDtStr = dayjs(eventDate).add(1, 'day').format('YYYY-MM-DD') + ' ' + editEnd;
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
    const allAssignments = events.filter(ev => ev.is_assignment);

    if (viewMode === 'week') {
      // ── Week mode: group by date and save each of the 7 days in parallel ──
      const monday    = getWeekMonday(reportedDate);
      const weekDates = Array.from({ length: 7 }, (_, i) =>
        dayjs(monday).add(i, 'day').format('YYYY-MM-DD')
      );

      // Initialise empty arrays for every day of the week
      const grouped = {};
      weekDates.forEach(d => { grouped[d] = []; });

      allAssignments.forEach(ev => {
        const evDate = dayjs(ev.start).format('YYYY-MM-DD');
        if (grouped[evDate] !== undefined) {
          grouped[evDate].push({
            id: ev.extendedProps.assignment_id,
            room_id: ev.extendedProps.room_id,
            sheet: ev.extendedProps.sheet,
            start: ev.start,
            end: ev.end,
            job_description: ev.extendedProps.job_description,
            personnel_list: ev.extendedProps.personnel_list
          });
        }
      });

      Promise.all(
        weekDates.map(date =>
          axios.put("/assignemnt/production/chart/store", {
            group_code:   groupCode,
            reportedDate: date,
            assignments:  grouped[date]
          })
        )
      )
      .then(() => {
        Swal.fire('Thành công', 'Đã lưu toàn bộ lịch phân công cả tuần thành công.', 'success');
        loadData();
      })
      .catch(err => {
        console.error("Lỗi khi lưu phân công tuần:", err);
        Swal.fire('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra khi lưu phân công.', 'error');
      })
      .finally(() => setSaving(false));

    } else {
      // ── Day mode: single save call ──
      const payloadAssignments = allAssignments.map(ev => ({
        id: ev.extendedProps.assignment_id,
        room_id: ev.extendedProps.room_id,
        sheet: ev.extendedProps.sheet,
        start: ev.start,
        end: ev.end,
        job_description: ev.extendedProps.job_description,
        personnel_list: ev.extendedProps.personnel_list
      }));

      axios.put("/assignemnt/production/chart/store", {
        group_code:   groupCode,
        reportedDate: reportedDate,
        assignments:  payloadAssignments
      })
      .then(() => {
        Swal.fire('Thành công', 'Đã lưu toàn bộ lịch phân công sản xuất thành công.', 'success');
        loadData();
      })
      .catch(err => {
        console.error("Lỗi khi lưu phân công:", err);
        Swal.fire('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra khi lưu phân công.', 'error');
      })
      .finally(() => setSaving(false));
    }
  };

  // Client-side Auto Assign Personnel (Round-Robin)
  const handleAutoAssign = () => {
    Swal.fire({
      title: viewModeRef.current === 'week' ? '🤖 Tự động phân công cả tuần?' : '🤖 Tự động phân công?',
      text: viewModeRef.current === 'week'
        ? "Thuật toán sẽ tự động phân phối nhân sự cho toàn bộ 7 ngày trong tuần dựa trên kế hoạch sản xuất và bậc kỹ năng. Kết quả sẽ được áp dụng cho từng ngày có lịch sản xuất."
        : "Thuật toán sẽ tự động phân phối nhân sự dựa trên kế hoạch sản xuất trong ngày và bậc kỹ năng của nhân viên.",
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
      <style>{`
        /* FullCalendar date input button - strip native button styling */
        .fc-dateInput-button {
          background: none !important;
          border: none !important;
          box-shadow: none !important;
          padding: 2px 4px !important;
          cursor: default !important;
        }
        .fc-dateInput-button:hover,
        .fc-dateInput-button:active,
        .fc-dateInput-button:focus {
          background: none !important;
          box-shadow: none !important;
        }
        /* Pulse animation for pending badge */
        .ac-pending-badge {
          animation: ac-pulse-warn 1.5s ease-in-out infinite;
        }
        @keyframes ac-pulse-warn {
          0%, 100% { box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.3); }
          50% { box-shadow: 0 0 0 6px rgba(234, 88, 12, 0); }
        }
        /* Hover lift effect on person cards */
        .draggable-person-card:hover {
          box-shadow: 0 4px 14px rgba(0,0,0,0.13) !important;
          transform: translateY(-1px);
        }
      `}</style>

      {/* Visual Indicators - same pattern as FullCalender.jsx */}
      <div className="flex gap-4 mb-2 align-items-center justify-content-end" style={{ minHeight: '32px' }}>
        {/* View mode badge */}
        <div
          className="flex align-items-center gap-2 px-3 py-1 border-round-2xl border-1"
          style={{
            background:   viewMode === 'week' ? '#eff6ff' : '#f0fdf4',
            borderColor:  viewMode === 'week' ? '#bfdbfe' : '#bbf7d0',
            color:        viewMode === 'week' ? '#1e40af' : '#166534',
          }}
        >
          <i className={`pi ${viewMode === 'week' ? 'pi-calendar' : 'pi-clock'}`}></i>
          <span className="font-bold text-sm">
            {viewMode === 'week' ? '📅 Xem theo tuần' : '🗓️ Xem theo ngày'}
          </span>
        </div>
        {loading && (
          <div className="flex align-items-center gap-2 bg-blue-100 text-blue-800 px-3 py-1 border-round-2xl shadow-1 border-1 border-blue-200">
            <i className="pi pi-spin pi-spinner"></i>
            <span className="font-bold text-sm">Đang tải dữ liệu...</span>
          </div>
        )}
        {pendingChanges && pendingChanges.length > 0 && (
          <div className="ac-pending-badge flex align-items-center gap-2 bg-orange-100 text-orange-800 px-3 py-1 border-round-2xl shadow-1 border-1 border-orange-200">
            <i className="pi pi-exclamation-triangle"></i>
            <span className="font-bold text-sm">{pendingChanges.length} Thay đổi chưa lưu</span>
          </div>
        )}
      </div>

      {/* Main layout: Calendar + Personnel Sidebar (flex, matches FullCalender.jsx pattern) */}
      <div style={{ display: 'flex', gap: '12px', alignItems: 'flex-start' }}>

        {/* FullCalendar Area - flex:1 takes remaining space */}
        <div style={{ flex: 1, minWidth: 0 }}>
          <FullCalendar
            schedulerLicenseKey="GPL-My-Project-Is-Open-Source"
            ref={calendarRef}
            height="calc(100vh - 130px)"
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

            resources={resources}
            events={events}

            eventReceive={handleEventReceive}
            eventDrop={handleEventChange}
            eventResize={handleEventChange}
            eventClick={handleEventClick}

            resourceGroupField="stage_name"
            resourceOrder="order_by"
            slotDuration={viewMode === 'week' ? { days: 1 } : '01:00:00'}
            slotLabelFormat={viewMode === 'week'
              ? [{ weekday: 'short', day: '2-digit', month: '2-digit' }]
              : { hour: '2-digit', minute: '2-digit', hour12: false }}

            headerToolbar={{
              left: 'viewDay viewWeek customPre,myToday,customNext dateInput',
              center: 'title',
              right: authorization
                ? 'autoAssign saveChanges togglePersonnel'
                : 'togglePersonnel'
            }}

            customButtons={{
              viewDay: {
                text: '🗓️ Ngày',
                click: () => setViewMode('day'),
                hint: 'Chế độ xem theo ngày'
              },
              viewWeek: {
                text: '📅 Tuần',
                click: () => setViewMode('week'),
                hint: 'Chế độ xem theo tuần (7 ngày)'
              },
              customPre: {
                text: '⏴',
                click: () => setReportedDate(prev => {
                  const step = viewModeRef.current === 'week' ? 7 : 1;
                  return dayjs(prev).subtract(step, 'day').format('YYYY-MM-DD');
                }),
                hint: 'Kỳ trước'
              },
              myToday: {
                text: 'Hôm nay',
                click: () => setReportedDate(dayjs().format('YYYY-MM-DD')),
                hint: 'Trở về hôm nay / tuần hiện tại'
              },
              customNext: {
                text: '⏵',
                click: () => setReportedDate(prev => {
                  const step = viewModeRef.current === 'week' ? 7 : 1;
                  return dayjs(prev).add(step, 'day').format('YYYY-MM-DD');
                }),
                hint: 'Kỳ tiếp theo'
              },
              dateInput: {
                text: '',
                hint: 'Chọn ngày / tuần'
              },
              autoAssign: {
                text: '🤖 Tự động phân công',
                click: handleAutoAssign,
                hint: 'Tự động phân công nhân sự dựa trên kế hoạch sản xuất'
              },
              saveChanges: {
                text: '💾 Lưu lịch phân công',
                click: handleSaveChanges,
                hint: 'Lưu toàn bộ thay đổi phân công'
              },
              togglePersonnel: {
                text: '👥',
                click: () => setShowPersonnelSidebar(prev => !prev),
                hint: 'Ẩn/Hiện bảng tình hình nhân sự'
              }
            }}

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

        {/* Personnel Sidebar - slides in/out with CSS transition */}
        <div
          style={{
            width: showPersonnelSidebar ? '260px' : '0',
            minWidth: showPersonnelSidebar ? '260px' : '0',
            transition: 'width 0.3s ease, min-width 0.3s ease',
            overflow: 'hidden',
            flexShrink: 0
          }}
        >
          <div
            className="bg-white border-round-xl shadow-2 flex flex-column"
            style={{ height: 'calc(100vh - 130px)', width: '260px', overflow: 'hidden' }}
          >
            {/* Sidebar Header */}
            <div
              className="px-3 py-3 flex align-items-center gap-2 border-bottom-1 border-gray-100"
              style={{ background: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)', flexShrink: 0 }}
            >
              <i className="pi pi-users text-primary" style={{ fontSize: '1.2rem' }}></i>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div className="font-bold text-sm text-slate-800">Tình Hình Nhân Sự</div>
                <div className="text-xs text-slate-500">{groupName}</div>
              </div>
              <div
                className="text-xs font-bold text-white px-2 py-1 border-round-xl"
                style={{ background: '#3b82f6', flexShrink: 0 }}
              >
                {filteredPersonnel.length}
              </div>
            </div>

            {/* Search & Filter */}
            <div
              className="px-3 py-2 flex flex-column gap-2 border-bottom-1 border-gray-100"
              style={{ flexShrink: 0 }}
            >
              <input
                type="text"
                className="p-inputtext p-inputtext-sm w-full"
                placeholder="🔍 Tìm tên hoặc mã NV..."
                value={searchQuery}
                onChange={e => setSearchQuery(e.target.value)}
                style={{ fontSize: '12px' }}
              />
              <div className="flex align-items-center gap-2">
                <input
                  type="checkbox"
                  id="filter-under-8h-ac"
                  checked={filterUnder8h}
                  onChange={e => setFilterUnder8h(e.target.checked)}
                />
                <label
                  htmlFor="filter-under-8h-ac"
                  className="cursor-pointer select-none"
                  style={{ fontSize: '12px', color: '#475569', fontWeight: 500 }}
                >
                  {viewMode === 'week' ? 'Lọc < 40h/tuần' : 'Lọc < 8h/ngày'}
                </label>
              </div>
            </div>

            {/* Draggable Personnel List */}
            <div
              id="sidebar-personnel-list"
              className="flex-1 overflow-y-auto px-2 py-2 flex flex-column gap-1"
            >
              {filteredPersonnel.length === 0 ? (
                <div className="flex flex-column align-items-center justify-content-center h-full text-slate-400 gap-2">
                  <i className="pi pi-search" style={{ fontSize: '2rem' }}></i>
                  <span className="text-sm italic">Không tìm thấy nhân sự</span>
                </div>
              ) : (
                filteredPersonnel.map(p => {
                  const hrs = employeeHours[p.id] || 0;
                  const fullThreshold = viewMode === 'week' ? 40 : 8;
                  const isFullDay  = hrs >= fullThreshold;
                  const isPartial  = hrs > 0 && hrs < fullThreshold;

                  return (
                    <div
                      key={p.id}
                      data-id={p.id}
                      data-name={p.name}
                      className="draggable-person-card p-2 border-round-lg border-1 flex justify-content-between align-items-center cursor-grab"
                      style={{
                        userSelect: 'none',
                        backgroundColor: isFullDay ? '#f0fdf4' : isPartial ? '#fefce8' : '#f8fafc',
                        borderColor: isFullDay ? '#86efac' : isPartial ? '#fde047' : '#e2e8f0',
                        transition: 'box-shadow 0.15s ease, transform 0.1s ease'
                      }}
                    >
                      <div className="flex flex-column gap-0" style={{ flex: 1, minWidth: 0 }}>
                        <span
                          className="font-bold"
                          style={{
                            fontSize: '12px',
                            color: isFullDay ? '#15803d' : isPartial ? '#854d0e' : '#334155',
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis'
                          }}
                        >
                          {p.name}
                        </span>
                        <span style={{ fontSize: '11px', color: '#94a3b8' }}>{p.code}</span>
                      </div>
                      <span
                        className="font-bold px-2 py-1 border-round-lg flex-shrink-0 ml-1 text-white"
                        style={{
                          fontSize: '11px',
                          background: isFullDay ? '#22c55e' : isPartial ? '#eab308' : '#94a3b8',
                          minWidth: '38px',
                          textAlign: 'center'
                        }}
                      >
                        {hrs.toFixed(1)}h
                      </span>
                    </div>
                  );
                })
              )}
            </div>
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
          <div className="flex justify-content-between align-items-center w-full">
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
        <div className="flex flex-column gap-3 pt-2">
          {/* Shift & Time selectors */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
            <div className="flex flex-column gap-1">
              <label className="font-bold text-xs">Ca làm việc</label>
              <select className="p-inputtext p-2 border-round border-1 border-gray-300" value={editSheet} onChange={e => setEditSheet(parseInt(e.target.value))}>
                <option value={1}>Ca 1 (06h - 14h)</option>
                <option value={2}>Ca 2 (14h - 22h)</option>
                <option value={3}>Ca 3 (22h - 06h)</option>
                <option value={4}>Ca Hành chính (07h15 - 16h)</option>
                <option value={5}>Ca Khác</option>
              </select>
            </div>

            <div className="flex flex-column gap-1">
              <label className="font-bold text-xs">Thời gian bắt đầu - kết thúc</label>
              <div className="flex gap-1 align-items-center">
                <input type="time" className="p-inputtext p-2 border-round border-1 border-gray-300" value={editStart} onChange={e => setEditStart(e.target.value)} />
                <span>-</span>
                <input type="time" className="p-inputtext p-2 border-round border-1 border-gray-300" value={editEnd} onChange={e => setEditEnd(e.target.value)} />
              </div>
            </div>
          </div>

          {/* Job description notes */}
          <div className="flex flex-column gap-1">
            <label className="font-bold text-xs">Nội dung công việc / Lưu ý</label>
            <input type="text" className="p-inputtext p-2 border-round border-1 border-gray-300 w-full" value={editJobDescription} onChange={e => setEditJobDescription(e.target.value)} placeholder="Chi tiết việc phân công..." />
          </div>

          {/* Personnel Selection */}
          <div className="flex flex-column gap-2">
            <div className="flex justify-content-between align-items-center border-bottom-1 pb-1">
              <label className="font-bold text-xs text-primary">Danh sách Nhân sự trực</label>
              <Dropdown
                options={personnel.map(p => ({ label: p.name + ' (' + p.code + ')', value: p.id }))}
                onChange={handleAddPersonnelToEditList}
                placeholder="Thêm nhân sự..."
                className="w-48 text-xs"
                style={{ height: '30px', fontSize: '12px' }}
              />
            </div>

            <div className="flex flex-column gap-2 overflow-y-auto" style={{ maxHeight: '150px' }}>
              {editPersonnelList.map((p, idx) => (
                <div key={p.id} className="flex justify-content-between align-items-center bg-gray-50 border-1 border-gray-200 p-2 border-round">
                  <div className="flex flex-column flex-1">
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
