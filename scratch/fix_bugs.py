import re

with open('scratch/handleOptimizeSchedule.js', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Inject helpers right after 'const allEvents = calendarApi.getEvents();'
helpers = """
    const getDurationForEvent = (ev, roomId) => {
        let q = quota.find(item => String(item.room_id) === String(roomId) && Number(item.stage_code) === Number(ev.extendedProps?.stage_code) && (
            (ev.extendedProps?.process_code && String(item.process_code).startsWith(String(ev.extendedProps?.process_code))) ||
            (ev.extendedProps?.intermediate_code && String(item.intermediate_code) === String(ev.extendedProps?.intermediate_code)) ||
            (ev.extendedProps?.finished_product_code && String(item.finished_product_code) === String(ev.extendedProps?.finished_product_code))
        ));
        
        if (q) {
             let pTime = parseFloat(q.p_time) || 0;
             let mTime = parseFloat(q.m_time) || 0;
             return (pTime + mTime) * 3600000;
        }
        let currentDur = new Date(ev.end).getTime() - new Date(ev.start).getTime();
        let start = new Date(ev.start);
        let end = new Date(ev.end);
        for (let off of offRanges) {
            if (off.start < end && off.end > start) {
                let overlapStart = new Date(Math.max(start.getTime(), off.start.getTime()));
                let overlapEnd = new Date(Math.min(end.getTime(), off.end.getTime()));
                if (overlapEnd > overlapStart) {
                    currentDur -= (overlapEnd.getTime() - overlapStart.getTime());
                }
            }
        }
        return currentDur;
    };

    const getAllowedRooms = (ev) => {
        let allowed = new Set();
        const currentRoomId = ev.getResources()[0]?.id || ev.extendedProps?.resourceId || ev.resourceId;
        if (currentRoomId) allowed.add(String(currentRoomId));

        let matches = quota.filter(item => Number(item.stage_code) === Number(ev.extendedProps?.stage_code) && (
            (ev.extendedProps?.process_code && String(item.process_code).startsWith(String(ev.extendedProps?.process_code))) ||
            (ev.extendedProps?.intermediate_code && String(item.intermediate_code) === String(ev.extendedProps?.intermediate_code)) ||
            (ev.extendedProps?.finished_product_code && String(item.finished_product_code) === String(ev.extendedProps?.finished_product_code))
        ));
        for (let m of matches) {
            allowed.add(String(m.room_id));
        }
        return Array.from(allowed);
    };

    const getCompatibleMolds = (ev, roomId) => {
        let catId = ev.extendedProps?.product_caterogy_id;
        if (!catId) return [];
        let room = allEvents.find(e => e.getResources && String(e.getResources()[0]?.id) === String(roomId))?.getResources()[0] || resources.find(r => String(r.id) === String(roomId));
        let rType = room?.extendedProps?.blister_type_code || room?.blister_type_code;
        
        let moldIds = finishedProductMolds.filter(f => String(f.finished_product_category_id) === String(catId)).map(f => String(f.blister_mold_id));
        let compatible = blisterMolds.filter(m => moldIds.includes(String(m.id)));
        if (rType) {
            compatible = compatible.filter(m => m.blister_type_code === rType || (m.blister_type_code && m.blister_type_code.includes(rType)) || !m.blister_type_code);
        }
        return compatible;
    };

    const isMoldAvailable = (moldId, tempStart, tempEnd, evId, currentUpdates, updatedTimes) => {
        let mold = blisterMolds.find(m => String(m.id) === String(moldId));
        if (!mold) return true; 
        let amount = Number(mold.amount) || 0;
        
        let concurrent = 0;
        for (let e of allEvents) {
            if (String(e.id) === String(evId) || String(e.id).endsWith('-cleaning')) continue;
            if (Number(e.extendedProps?.stage_code) === 7) {
                 let eStart, eEnd;
                 if (updatedTimes[e.id]) {
                     eStart = new Date(updatedTimes[e.id].start);
                     eEnd = new Date(updatedTimes[e.id].end);
                 } else {
                     eStart = new Date(e.start);
                     eEnd = new Date(e.end);
                 }
                 let eMoldId = currentUpdates.find(u => String(u.id) === String(e.id))?.blister_mold_id || e.extendedProps?.blister_mold_id;
                 if (String(eMoldId) === String(moldId)) {
                     if (tempStart < eEnd && tempEnd > eStart) {
                         concurrent++;
                     }
                 }
            }
        }
        return concurrent < amount;
    };

    const campaignRoomMap = {}; // key: campaign_code_stage_code -> roomId
"""

content = content.replace('const allEvents = calendarApi.getEvents();', 'const allEvents = calendarApi.getEvents();\n' + helpers)

# 2. Modify BACKWARD CASCADE logic
# Find: const duration = new Date(ev.end).getTime() - new Date(ev.start).getTime();
# And the findPreviousAvailableSlot call.
backward_find = """        const duration = new Date(ev.end).getTime() - new Date(ev.start).getTime();
        const resourceId = ev.getResources()[0]?.id;

        const newSlot = findPreviousAvailableSlot(resourceId, duration, latestEnd, allEvents, offRanges, ignoreIds, updatedTimesById);"""

backward_replace = """        const duration = getDurationForEvent(ev, ev.getResources()[0]?.id);
        const originalResourceId = ev.getResources()[0]?.id;
        
        let allowedRooms = getAllowedRooms(ev);
        let campaignKey = `${ev.extendedProps?.campaign_code}_${ev.extendedProps?.stage_code}`;
        if (campaignRoomMap[campaignKey]) {
            allowedRooms = [campaignRoomMap[campaignKey]];
        }

        let bestSlot = null;
        let bestRoomId = originalResourceId;

        for (const roomId of allowedRooms) {
             const tempSlot = findPreviousAvailableSlot(roomId, duration, latestEnd, allEvents, offRanges, ignoreIds, updatedTimesById);
             if (!bestSlot || tempSlot.end.getTime() > bestSlot.end.getTime()) {
                 bestSlot = tempSlot;
                 bestRoomId = roomId;
             }
        }
        
        if (bestRoomId !== originalResourceId) campaignRoomMap[campaignKey] = bestRoomId;
        const newSlot = bestSlot;
        const resourceId = bestRoomId;"""

content = content.replace(backward_find, backward_replace)


# 3. Modify FORWARD CASCADE logic
forward_find = """        const duration = new Date(ev.end).getTime() - new Date(ev.start).getTime();
        const resourceId = ev.getResources()[0]?.id;

        // TÃ¬m chá»— trá»‘ng tá»« earliestStart trá»Ÿ Ä‘i
        const newSlot = findNextAvailableSlot(resourceId, duration, earliestStart, allEvents, offRanges, ignoreIds, updatedTimesById);

        updatedTimesById[evId] = { start: newSlot.start, end: newSlot.end };
        updates.push({ id: evId, start: newSlot.start, end: newSlot.end, resourceId, clearWarnings: true });"""

forward_replace = """        const originalResourceId = ev.getResources()[0]?.id;
        let allowedRooms = getAllowedRooms(ev);
        let campaignKey = `${ev.extendedProps?.campaign_code}_${ev.extendedProps?.stage_code}`;
        if (campaignRoomMap[campaignKey]) {
            allowedRooms = [campaignRoomMap[campaignKey]];
        }

        let bestSlot = null;
        let bestRoomId = originalResourceId;
        let bestMoldId = ev.extendedProps?.blister_mold_id;

        for (const roomId of allowedRooms) {
             const duration = getDurationForEvent(ev, roomId);
             let tempSlot = findNextAvailableSlot(roomId, duration, earliestStart, allEvents, offRanges, ignoreIds, updatedTimesById);
             let moldId = ev.extendedProps?.blister_mold_id;
             
             if (Number(ev.extendedProps?.stage_code) === 7) {
                 let compatibleMolds = getCompatibleMolds(ev, roomId);
                 let foundMold = false;
                 
                 while (!foundMold && tempSlot.start.getTime() < new Date("2050-01-01").getTime()) {
                     for (let m of compatibleMolds) {
                         if (isMoldAvailable(m.id, tempSlot.start, tempSlot.end, evId, updates, updatedTimesById)) {
                             moldId = m.id;
                             foundMold = true;
                             break;
                         }
                     }
                     if (foundMold) break;
                     // push start time by 1 hour and try again
                     tempSlot = findNextAvailableSlot(roomId, duration, new Date(tempSlot.start.getTime() + 3600000), allEvents, offRanges, ignoreIds, updatedTimesById);
                 }
             }

             if (!bestSlot || tempSlot.start.getTime() < bestSlot.start.getTime()) {
                 bestSlot = tempSlot;
                 bestRoomId = roomId;
                 bestMoldId = moldId;
             }
        }
        
        if (bestRoomId !== originalResourceId) campaignRoomMap[campaignKey] = bestRoomId;
        const newSlot = bestSlot;
        const resourceId = bestRoomId;

        updatedTimesById[evId] = { start: newSlot.start, end: newSlot.end };
        updates.push({ id: evId, start: newSlot.start, end: newSlot.end, resourceId, blister_mold_id: bestMoldId, clearWarnings: true });"""

content = content.replace(forward_find, forward_replace)

# 4. Also update the update push loop to set extendedProps for mold
update_loop_find = """              id: u.id,
              start: u.start,
              end: u.end,"""
update_loop_replace = """              id: u.id,
              start: u.start,
              end: u.end,
              blister_mold_id: u.blister_mold_id !== undefined ? u.blister_mold_id : ev.extendedProps?.blister_mold_id,"""
content = content.replace(update_loop_find, update_loop_replace)

# Add setter for blister_mold_id to ev
update_ev_find = """              ev.setExtendedProp('warning_text', '');"""
update_ev_replace = """              ev.setExtendedProp('warning_text', '');
              if (u.blister_mold_id) ev.setExtendedProp('blister_mold_id', u.blister_mold_id);"""
content = content.replace(update_ev_find, update_ev_replace)


with open('scratch/handleOptimizeSchedule_new.js', 'w', encoding='utf-8') as f:
    f.write(content)

print("Done generating new function")
