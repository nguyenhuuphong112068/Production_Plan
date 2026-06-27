import re

with open('resources/js/Pages/FullCalender.jsx', 'r', encoding='utf-8') as f:
    content = f.read()

loop_find = r"""        if \(Number\(ev\.extendedProps\?\.stage_code\) === 7\) \{
            let compatibleMolds = getCompatibleMolds\(ev, resourceId\);
            let foundMold = false;
            
            while \(!foundMold && newSlot\.start\.getTime\(\) < new Date\("2050-01-01"\)\.getTime\(\)\) \{
                for \(let m of compatibleMolds\) \{
                    if \(isMoldAvailable\(m\.id, newSlot\.start, newSlot\.end, evId, updates, updatedTimesById\)\) \{
                        moldId = m\.id;
                        foundMold = true;
                        break;
                    \}
                \}
                if \(foundMold\) break;
                // push start time by 1 hour and try again
                newSlot = findNextAvailableSlot\(resourceId, duration, new Date\(newSlot\.start\.getTime\(\) \+ 3600000\), allEvents, offRanges, ignoreIds, updatedTimesById\);
            \}
        \}"""

loop_replace = """        if (Number(ev.extendedProps?.stage_code) === 7) {
            let compatibleMolds = getCompatibleMolds(ev, resourceId);
            let foundMold = false;
            
            if (compatibleMolds && compatibleMolds.length > 0) {
                let tries = 0;
                while (!foundMold && tries < 200 && newSlot.start.getTime() < new Date("2050-01-01").getTime()) {
                    for (let m of compatibleMolds) {
                        if (isMoldAvailable(m.id, newSlot.start, newSlot.end, evId, updates, updatedTimesById)) {
                            moldId = m.id;
                            foundMold = true;
                            break;
                        }
                    }
                    if (foundMold) break;
                    // jump ahead by 12 hours to speed up search and prevent freeze
                    newSlot = findNextAvailableSlot(resourceId, duration, new Date(newSlot.start.getTime() + 12 * 3600000), allEvents, offRanges, ignoreIds, updatedTimesById);
                    tries++;
                }
            }
        }"""

content = re.sub(loop_find, loop_replace, content)

with open('resources/js/Pages/FullCalender.jsx', 'w', encoding='utf-8') as f:
    f.write(content)
