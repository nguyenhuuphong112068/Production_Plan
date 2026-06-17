import sys

with open('resources/js/Pages/FullCalender.jsx', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Move updates declaration up
content = content.replace('    let updates = [];\n    let updatedTimesById = {};\n', '')
content = content.replace('    const pmIds = anchors.map(a => String(a.extendedProps.plan_master_id));\n', '    let updates = [];\n    let updatedTimesById = {};\n\n    const pmIds = anchors.map(a => String(a.extendedProps.plan_master_id));\n')

# 2. Anchors loop
anchors_old = '''    // Xóa màu cảnh báo cho chính các anchors vì chuỗi sẽ được tự động sửa
    anchors.forEach(a => {
        a.setExtendedProp('warning_text', '');
        a.setExtendedProp('violation_colors', []);
        let cleaningEvent = allEvents.find(e => String(e.id) === String(a.id).replace('-main', '-cleaning'));
        if (cleaningEvent) {
            cleaningEvent.setExtendedProp('warning_text', '');
            cleaningEvent.setExtendedProp('violation_colors', []);
        }
    });'''

anchors_new = '''    // Xóa màu cảnh báo cho chính các anchors vì chuỗi sẽ được tự động sửa
    anchors.forEach(a => {
        updates.push({ id: a.id, clearWarnings: true });
        let cleaningEvent = allEvents.find(e => String(e.id) === String(a.id).replace('-main', '-cleaning'));
        if (cleaningEvent) {
            updates.push({ id: cleaningEvent.id, clearWarnings: true });
        }
    });'''
content = content.replace(anchors_old, anchors_new)

# 3. Backward loop
backward_old_1 = '''        if (evData.end <= latestEnd) {
            updatedTimesById[evData.id] = { start: evData.start, end: evData.end };
            evData.event.setExtendedProp('warning_text', '');
            evData.event.setExtendedProp('violation_colors', []);
            if (cleaningEvent) {
                updatedTimesById[cleaningEvent.id] = { start: evData.event.end, end: cleaningEvent.end };
                cleaningEvent.setExtendedProp('warning_text', '');
                cleaningEvent.setExtendedProp('violation_colors', []);
            }
            if (successorData && successorData.event) {
                successorData.event.setExtendedProp('warning_text', '');
                successorData.event.setExtendedProp('violation_colors', []);
            }
            return;
        }'''

backward_new_1 = '''        if (evData.end <= latestEnd) {
            updatedTimesById[evData.id] = { start: evData.start, end: evData.end };
            updates.push({ id: evData.id, clearWarnings: true });
            if (cleaningEvent) {
                updatedTimesById[cleaningEvent.id] = { start: evData.event.end, end: cleaningEvent.end };
                updates.push({ id: cleaningEvent.id, clearWarnings: true });
            }
            if (successorData && successorData.event) {
                updates.push({ id: successorData.event.id, clearWarnings: true });
            }
            return;
        }'''
content = content.replace(backward_old_1, backward_new_1)

backward_old_2 = '''        evData.start = newSlot.start;
        evData.end = newSlot.end;
        evData.event.setDates(newSlot.start, newSlot.end);
        
        if (cleaningEvent) {
            let cleaningDuration = new Date(cleaningEvent.end).getTime() - new Date(cleaningEvent.start).getTime();
            let newCleaningStart = newSlot.end;
            let newCleaningEnd = new Date(newCleaningStart.getTime() + cleaningDuration);
            cleaningEvent.setDates(newCleaningStart, newCleaningEnd);
            
            cleaningEvent.setExtendedProp('warning_text', '');
            cleaningEvent.setExtendedProp('violation_colors', []);
            updatedTimesById[cleaningEvent.id] = { start: newCleaningStart, end: newCleaningEnd };
        }
        
        updatedTimesById[evData.id] = { start: newSlot.start, end: newSlot.end };
        
        evData.event.setExtendedProp('warning_text', '');
        evData.event.setExtendedProp('violation_colors', []);
        
        if (successorData && successorData.event) {
            successorData.event.setExtendedProp('warning_text', '');
            successorData.event.setExtendedProp('violation_colors', []);
        }
        
        updates.push({
            id: evData.id,
            start: evData.start,
            end: evData.end,
            resourceId: evData.resourceId
        });'''

backward_new_2 = '''        evData.start = newSlot.start;
        evData.end = newSlot.end;
        
        if (cleaningEvent) {
            let cleaningDuration = new Date(cleaningEvent.end).getTime() - new Date(cleaningEvent.start).getTime();
            let newCleaningStart = newSlot.end;
            let newCleaningEnd = new Date(newCleaningStart.getTime() + cleaningDuration);
            updatedTimesById[cleaningEvent.id] = { start: newCleaningStart, end: newCleaningEnd };
            updates.push({
                id: cleaningEvent.id,
                start: newCleaningStart,
                end: newCleaningEnd,
                resourceId: evData.resourceId,
                clearWarnings: true
            });
        }
        
        updatedTimesById[evData.id] = { start: newSlot.start, end: newSlot.end };
        
        if (successorData && successorData.event) {
            updates.push({ id: successorData.event.id, clearWarnings: true });
        }
        
        updates.push({
            id: evData.id,
            start: evData.start,
            end: evData.end,
            resourceId: evData.resourceId,
            clearWarnings: true
        });'''
content = content.replace(backward_old_2, backward_new_2)

# 4. Forward loop
forward_old_1 = '''        if (evData.start >= earliestStart) {
            updatedTimesById[evData.id] = { start: evData.start, end: evData.end };
            evData.event.setExtendedProp('warning_text', '');
            evData.event.setExtendedProp('violation_colors', []);
            if (cleaningEvent) {
                updatedTimesById[cleaningEvent.id] = { start: evData.event.end, end: cleaningEvent.end };
                cleaningEvent.setExtendedProp('warning_text', '');
                cleaningEvent.setExtendedProp('violation_colors', []);
            }
            if (predData && predData.event) {
                predData.event.setExtendedProp('warning_text', '');
                predData.event.setExtendedProp('violation_colors', []);
            }
            return;
        }'''

forward_new_1 = '''        if (evData.start >= earliestStart) {
            updatedTimesById[evData.id] = { start: evData.start, end: evData.end };
            updates.push({ id: evData.id, clearWarnings: true });
            if (cleaningEvent) {
                updatedTimesById[cleaningEvent.id] = { start: evData.event.end, end: cleaningEvent.end };
                updates.push({ id: cleaningEvent.id, clearWarnings: true });
            }
            if (predData && predData.event) {
                updates.push({ id: predData.event.id, clearWarnings: true });
            }
            return;
        }'''
content = content.replace(forward_old_1, forward_new_1)

forward_old_2 = '''        evData.start = newSlot.start;
        evData.end = newSlot.end;
        evData.event.setDates(newSlot.start, newSlot.end);
        
        if (cleaningEvent) {
            let cleaningDuration = new Date(cleaningEvent.end).getTime() - new Date(cleaningEvent.start).getTime();
            let newCleaningStart = newSlot.end;
            let newCleaningEnd = new Date(newCleaningStart.getTime() + cleaningDuration);
            cleaningEvent.setDates(newCleaningStart, newCleaningEnd);
            
            cleaningEvent.setExtendedProp('warning_text', '');
            cleaningEvent.setExtendedProp('violation_colors', []);
            updatedTimesById[cleaningEvent.id] = { start: newCleaningStart, end: newCleaningEnd };
        }
        
        updatedTimesById[evData.id] = { start: newSlot.start, end: newSlot.end };
        
        evData.event.setExtendedProp('warning_text', '');
        evData.event.setExtendedProp('violation_colors', []);
        
        if (predData && predData.event) {
            predData.event.setExtendedProp('warning_text', '');
            predData.event.setExtendedProp('violation_colors', []);
        }
        
        updates.push({
            id: evData.id,
            start: evData.start,
            end: evData.end,
            resourceId: evData.resourceId
        });'''

forward_new_2 = '''        evData.start = newSlot.start;
        evData.end = newSlot.end;
        
        if (cleaningEvent) {
            let cleaningDuration = new Date(cleaningEvent.end).getTime() - new Date(cleaningEvent.start).getTime();
            let newCleaningStart = newSlot.end;
            let newCleaningEnd = new Date(newCleaningStart.getTime() + cleaningDuration);
            updatedTimesById[cleaningEvent.id] = { start: newCleaningStart, end: newCleaningEnd };
            updates.push({
                id: cleaningEvent.id,
                start: newCleaningStart,
                end: newCleaningEnd,
                resourceId: evData.resourceId,
                clearWarnings: true
            });
        }
        
        updatedTimesById[evData.id] = { start: newSlot.start, end: newSlot.end };
        
        if (predData && predData.event) {
            updates.push({ id: predData.event.id, clearWarnings: true });
        }
        
        updates.push({
            id: evData.id,
            start: evData.start,
            end: evData.end,
            resourceId: evData.resourceId,
            clearWarnings: true
        });'''
content = content.replace(forward_old_2, forward_new_2)

# 5. Wrapping updates block in batchRendering
updates_block_old = '''    if (updates.length > 0) {
        let newPending = [...pendingChanges];
        updates.forEach(u => {
            const ev = calendarApi.getEventById(u.id);
            if (ev) {
                ev.setDates(u.start, u.end);
            }
            const existIdx = newPending.findIndex(p => String(p.id) === String(u.id));
            if (existIdx >= 0) {
                newPending[existIdx] = { ...newPending[existIdx], ...u };
            } else {
                newPending.push(u);
            }
        });
        setPendingChanges(newPending);
        Swal.fire("Thành công", `Đã điều chỉnh ${updates.length} sự kiện con.`, "success");
    } else {
        Swal.fire("Thông báo", "Không có sự kiện nào cần điều chỉnh (hoặc các sự kiện đã được tối ưu).", "info");
    }'''

updates_block_new = '''    if (updates.length > 0) {
        let newPending = [...pendingChanges];
        
        // SỬ DỤNG BATCH RENDERING ĐỂ TỐI ƯU TỐC ĐỘ, CHỈ VẼ LẠI UI 1 LẦN!
        calendarApi.batchRendering(() => {
            updates.forEach(u => {
                const ev = calendarApi.getEventById(u.id);
                if (ev) {
                    if (u.start && u.end) {
                        ev.setDates(u.start, u.end);
                    }
                    if (u.clearWarnings) {
                        ev.setExtendedProp('warning_text', '');
                        ev.setExtendedProp('violation_colors', []);
                    }
                }
                const existIdx = newPending.findIndex(p => String(p.id) === String(u.id));
                if (existIdx >= 0) {
                    newPending[existIdx] = { ...newPending[existIdx], ...u };
                } else {
                    newPending.push(u);
                }
            });
        });
        
        setPendingChanges(newPending);
        Swal.fire("Thành công", `Đã điều chỉnh ${updates.filter(u => u.start).length} sự kiện con.`, "success");
    } else {
        Swal.fire("Thông báo", "Không có sự kiện nào cần điều chỉnh (hoặc các sự kiện đã được tối ưu).", "info");
    }'''
content = content.replace(updates_block_old, updates_block_new)

with open('resources/js/Pages/FullCalender.jsx', 'w', encoding='utf-8') as f:
    f.write(content)
