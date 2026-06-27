import re

with open('resources/js/Pages/FullCalender.jsx', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update ratio logic in handleGroupEventDrop (line ~2884)
content = re.sub(
    r"if \(\s*event\._def\.extendedProps\.stage_code\s*==\s*7\s*&&\s*event\._def\.extendedProps\.percent_parkaging\s*\)\s*\{",
    r"if ((event._def.extendedProps.stage_code == 7 || event._def.extendedProps.stage_code == 1) && event._def.extendedProps.percent_parkaging) {",
    content
)

# 2. Update mold logic in handleGroupEventDrop
mold_find = r"""          if \(!workingSunday\) \{
            newStart = addWorkingTime\(event_start, currentWorkingShiftMs, offRanges\);
            newStart = skipOffDays\(newStart, offRanges\);
            newEnd = addWorkingTime\(newStart, conf\.trueDurMs, offRanges\);
          \} else \{
            newStart = new Date\(event_start\.getTime\(\) \+ currentOffset\);
            newEnd = new Date\(isDragged \? \(info\.oldEvent\.end \? info\.oldEvent\.end\.getTime\(\) \+ currentOffset : event_start\.getTime\(\) \+ currentOffset\) : \(conf\.event\.end \? conf\.event\.end\.getTime\(\) \+ currentOffset : event_start\.getTime\(\) \+ currentOffset\)\);
          \}

          return \{
            id: conf\.event\.id,"""

mold_replace = """          if (!workingSunday) {
            newStart = addWorkingTime(event_start, currentWorkingShiftMs, offRanges);
            newStart = skipOffDays(newStart, offRanges);
            newEnd = addWorkingTime(newStart, conf.trueDurMs, offRanges);
          } else {
            newStart = new Date(event_start.getTime() + currentOffset);
            newEnd = new Date(isDragged ? (info.oldEvent.end ? info.oldEvent.end.getTime() + currentOffset : event_start.getTime() + currentOffset) : (conf.event.end ? conf.event.end.getTime() + currentOffset : event_start.getTime() + currentOffset));
          }

          let moldId = conf.event._def.extendedProps.blister_mold_id;
          if (Number(conf.event._def.extendedProps.stage_code) === 7) {
              let catId = conf.event._def.extendedProps.product_caterogy_id;
              if (catId) {
                  let rType = resources.find(r => String(r.id) === String(conf.resId))?.blister_type_code;
                  let mIds = finishedProductMolds.filter(f => String(f.finished_product_category_id) === String(catId)).map(f => String(f.blister_mold_id));
                  let compatible = blisterMolds.filter(m => mIds.includes(String(m.id)));
                  if (rType) {
                      compatible = compatible.filter(m => m.blister_type_code === rType || (m.blister_type_code && m.blister_type_code.includes(rType)) || !m.blister_type_code);
                  }
                  
                  let concurrentCount = (mId) => {
                       let c = 0;
                       for (let e of calendarApi.getEvents()) {
                           if (String(e.id) === String(conf.event.id) || String(e.id).endsWith('-cleaning')) continue;
                           if (Number(e.extendedProps?.stage_code) === 7 && String(e.extendedProps?.blister_mold_id) === String(mId)) {
                               if (newStart < e.end && newEnd > e.start) c++;
                           }
                       }
                       return c;
                  };

                  if (moldId) {
                       let mold = blisterMolds.find(m => String(m.id) === String(moldId));
                       if (mold && concurrentCount(moldId) >= (Number(mold.amount) || 0)) {
                           for (let m of compatible) {
                               if (concurrentCount(m.id) < (Number(m.amount) || 0)) {
                                   moldId = m.id;
                                   break;
                               }
                           }
                       }
                  }
              }
          }

          return {
            id: conf.event.id,
            blister_mold_id: moldId,"""

content = re.sub(mold_find, mold_replace, content)

with open('resources/js/Pages/FullCalender.jsx', 'w', encoding='utf-8') as f:
    f.write(content)
