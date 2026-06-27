import re

with open('resources/js/Pages/FullCalender.jsx', 'r', encoding='utf-8') as f:
    content = f.read()

get_dur_find = r"""    const getDurationForEvent = \(ev, roomId\) => \{
        let q = quota\.find\(item => String\(item\.room_id\) === String\(roomId\) && Number\(item\.stage_code\) === Number\(ev\.extendedProps\?\.stage_code\) && \(
            \(ev\.extendedProps\?\.process_code && String\(item\.process_code\)\.startsWith\(String\(ev\.extendedProps\?\.process_code\)\)\) \|\|
            \(ev\.extendedProps\?\.intermediate_code && String\(item\.intermediate_code\) === String\(ev\.extendedProps\?\.intermediate_code\)\) \|\|
            \(ev\.extendedProps\?\.finished_product_code && String\(item\.finished_product_code\) === String\(ev\.extendedProps\?\.finished_product_code\)\)
        \)\);
        
        if \(q\) \{
             let pTime = parseFloat\(q\.p_time\) \|\| 0;
             let mTime = parseFloat\(q\.m_time\) \|\| 0;
             return \(pTime \+ mTime\) \* 3600000;
        \}"""

get_dur_replace = """    const getDurationForEvent = (ev, roomId) => {
        let q = quota.find(item => String(item.room_id) === String(roomId) && Number(item.stage_code) === Number(ev.extendedProps?.stage_code) && (
            (ev.extendedProps?.process_code && String(item.process_code).startsWith(String(ev.extendedProps?.process_code))) ||
            (ev.extendedProps?.intermediate_code && String(item.intermediate_code) === String(ev.extendedProps?.intermediate_code)) ||
            (ev.extendedProps?.finished_product_code && String(item.finished_product_code) === String(ev.extendedProps?.finished_product_code))
        ));
        
        if (q) {
             let pTime = parseFloat(q.p_time) || 0;
             let mTime = parseFloat(q.m_time) || 0;
             
             let ratio = 1;
             if (ev.extendedProps?.percent_parkaging && (Number(ev.extendedProps?.stage_code) === 7 || Number(ev.extendedProps?.stage_code) === 1)) {
                 ratio = parseFloat(ev.extendedProps.percent_parkaging);
             }
             pTime = pTime * ratio;
             mTime = mTime * ratio;

             let isFirst = ev.extendedProps?.first_in_campaign === 1 || ev.extendedProps?.title_clearning === "VS-II";
             let durationHours = isFirst ? (pTime + mTime) : mTime;
             return durationHours * 3600000;
        }"""

content = re.sub(get_dur_find, get_dur_replace, content)

# 3. Modify BACKWARD CASCADE logic
backward_find = r"""        const duration = new Date\(ev\.end\)\.getTime\(\) - new Date\(ev\.start\)\.getTime\(\);
        const resourceId = ev\.getResources\(\)\[0\]\?\.id;

        const cleaningDur = cleaningEv \? \(new Date\(cleaningEv\.end\)\.getTime\(\) - new Date\(cleaningEv\.start\)\.getTime\(\)\) : 0;"""

backward_replace = """        const duration = getDurationForEvent(ev, ev.getResources()[0]?.id);
        const resourceId = ev.getResources()[0]?.id;

        const cleaningDur = cleaningEv ? (new Date(cleaningEv.end).getTime() - new Date(cleaningEv.start).getTime()) : 0;"""

content = re.sub(backward_find, backward_replace, content)

# 4. Modify FORWARD CASCADE duration and mold
forward_find = r"""        const duration = new Date\(ev\.end\)\.getTime\(\) - new Date\(ev\.start\)\.getTime\(\);
        const resourceId = ev\.getResources\(\)\[0\]\?\.id;

        // Tương tự, coi các event chưa khóa khác như "không tồn tại" để lấp vào chỗ của chúng
        const allUnprocessed = allEvents\.filter\(e => !updatedTimesById\[String\(e\.id\)\] && String\(e\.id\) !== evId && \(!cleaningEv \|\| String\(e\.id\) !== String\(cleaningEv\.id\)\)\)\.map\(e => String\(e\.id\)\);
        const ignoreIds = allUnprocessed;

        const newSlot = findNextAvailableSlot\(resourceId, duration, earliestStart, allEvents, offRanges, ignoreIds, updatedTimesById\);

        updatedTimesById\[evId\] = \{ start: newSlot\.start, end: newSlot\.end \};
        updates\.push\(\{ id: evId, start: newSlot\.start, end: newSlot\.end, resourceId, clearWarnings: true \}\);"""

forward_replace = """        const resourceId = ev.getResources()[0]?.id;
        const duration = getDurationForEvent(ev, resourceId);

        // Tương tự, coi các event chưa khóa khác như "không tồn tại" để lấp vào chỗ của chúng
        const allUnprocessed = allEvents.filter(e => !updatedTimesById[String(e.id)] && String(e.id) !== evId && (!cleaningEv || String(e.id) !== String(cleaningEv.id))).map(e => String(e.id));
        const ignoreIds = allUnprocessed;

        let newSlot = findNextAvailableSlot(resourceId, duration, earliestStart, allEvents, offRanges, ignoreIds, updatedTimesById);
        let moldId = ev.extendedProps?.blister_mold_id;
             
        if (Number(ev.extendedProps?.stage_code) === 7) {
            let compatibleMolds = getCompatibleMolds(ev, resourceId);
            let foundMold = false;
            
            while (!foundMold && newSlot.start.getTime() < new Date("2050-01-01").getTime()) {
                for (let m of compatibleMolds) {
                    if (isMoldAvailable(m.id, newSlot.start, newSlot.end, evId, updates, updatedTimesById)) {
                        moldId = m.id;
                        foundMold = true;
                        break;
                    }
                }
                if (foundMold) break;
                // push start time by 1 hour and try again
                newSlot = findNextAvailableSlot(resourceId, duration, new Date(newSlot.start.getTime() + 3600000), allEvents, offRanges, ignoreIds, updatedTimesById);
            }
        }

        updatedTimesById[evId] = { start: newSlot.start, end: newSlot.end };
        updates.push({ id: evId, start: newSlot.start, end: newSlot.end, resourceId, blister_mold_id: moldId, clearWarnings: true });"""

content = re.sub(forward_find, forward_replace, content)

with open('resources/js/Pages/FullCalender.jsx', 'w', encoding='utf-8') as f:
    f.write(content)
