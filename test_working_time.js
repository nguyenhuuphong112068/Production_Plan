const offRanges = [
    { start: new Date('2026-06-28T06:00:00+07:00'), end: new Date('2026-06-29T06:00:00+07:00') }
];

const skipOffDays = (date, offRanges) => {
    let current = new Date(date);
    let crossed = true;
    while (crossed) {
      crossed = false;
      for (const off of offRanges) {
        if (current >= off.start && current < off.end) {
          current = new Date(off.end);
          crossed = true;
          break;
        }
        if (current < off.start) break;
      }
    }
    return current;
};

const getWorkingTimeBetween = (start, end, offRanges) => {
    let s = new Date(start).getTime();
    let e = new Date(end).getTime();
    let isNegative = false;
    if (s > e) {
        let temp = s; s = e; e = temp;
        isNegative = true;
    }
    let total = e - s;
    for (const off of offRanges) {
        const offS = off.start.getTime();
        const offE = off.end.getTime();
        const overlapS = Math.max(s, offS);
        const overlapE = Math.min(e, offE);
        if (overlapS < overlapE) {
            total -= (overlapE - overlapS);
        }
    }
    return isNegative ? -total : total;
};

const addWorkingTime = (start, workingMs, offRanges) => {
    // Nếu đi tới (workingMs > 0), trước tiên bỏ qua ngày nghỉ ngay tại vị trí xuất phát
    let current = workingMs > 0 ? skipOffDays(new Date(start), offRanges).getTime() : new Date(start).getTime();
    if (workingMs === 0) return new Date(current);
    
    let remaining = Math.abs(workingMs);
    const direction = workingMs > 0 ? 1 : -1;

    while (remaining > 0) {
        let nextOff = null;
        for (const off of offRanges) {
            if (direction > 0) {
                if (off.start.getTime() >= current && (!nextOff || off.start.getTime() < nextOff.start.getTime())) {
                    nextOff = off;
                }
            } else {
                if (off.end.getTime() <= current && (!nextOff || off.end.getTime() > nextOff.end.getTime())) {
                    nextOff = off;
                }
            }
        }

        if (nextOff) {
            let timeUntilOff = direction > 0 ? (nextOff.start.getTime() - current) : (current - nextOff.end.getTime());
            if (timeUntilOff >= remaining) {
                current += direction * remaining;
                remaining = 0;
            } else {
                current = direction > 0 ? nextOff.end.getTime() : nextOff.start.getTime();
                remaining -= timeUntilOff;
            }
        } else {
            current += direction * remaining;
            remaining = 0;
        }
    }
    return direction > 0 ? skipOffDays(new Date(current), offRanges) : new Date(current);
};

let origStart = new Date('2026-06-27T06:00:00+07:00'); // Saturday
let newStart = new Date('2026-06-29T06:00:00+07:00'); // Monday
let shift = getWorkingTimeBetween(origStart, newStart, offRanges);
console.log("Shift: " + (shift / 3600000) + " hours"); // Should be 24h

let eventBStart = new Date('2026-06-27T14:00:00+07:00'); // Saturday 14:00
let newEventBStart = addWorkingTime(eventBStart, shift, offRanges);
console.log("New Event B Start: " + newEventBStart); // Should be Monday 14:00

let eventCStart = new Date('2026-06-27T22:00:00+07:00'); // Saturday 22:00
let newEventCStart = addWorkingTime(eventCStart, shift, offRanges);
console.log("New Event C Start: " + newEventCStart); // Should be Monday 22:00
