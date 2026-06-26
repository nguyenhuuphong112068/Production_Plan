import re

path = r'c:\PMS\Production_Plan\resources\js\Pages\FullCalender.jsx'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

old_logic = '''      calendarRef.current.getApi().batchRendering(() => {
          lastState.events.forEach(item => {
              const ev = calendarRef.current.getApi().getEventById(item.id);
              if (ev) {
                  if (item.start && item.end) ev.setDates(new Date(item.start), new Date(item.end), { maintainDuration: false });
                  if (item.resourceId) ev.setResources([item.resourceId]);
                  ev.setExtendedProp('start_clearning', item.start_clearning);
                  ev.setExtendedProp('end_clearning', item.end_clearning);
              }
          });
      });'''

new_logic = '''      const calendarApi = calendarRef.current.getApi();
      const currentEvents = calendarApi.getEvents();
      const currentEventsMap = new Map();
      currentEvents.forEach(ev => currentEventsMap.set(String(ev.id), ev));

      calendarApi.batchRendering(() => {
          lastState.events.forEach(item => {
              const ev = currentEventsMap.get(String(item.id));
              if (ev) {
                  const currentStart = ev.start ? ev.start.toISOString() : null;
                  const currentEnd = ev.end ? ev.end.toISOString() : null;
                  const currentRes = ev.getResources()[0]?.id || null;
                  
                  if (currentStart !== item.start || currentEnd !== item.end) {
                      if (item.start && item.end) {
                          ev.setDates(new Date(item.start), new Date(item.end), { maintainDuration: false });
                      }
                  }
                  
                  if (currentRes !== item.resourceId && item.resourceId) {
                      ev.setResources([item.resourceId]);
                  }
                  
                  if (ev.extendedProps.start_clearning !== item.start_clearning) {
                      ev.setExtendedProp('start_clearning', item.start_clearning);
                  }
                  if (ev.extendedProps.end_clearning !== item.end_clearning) {
                      ev.setExtendedProp('end_clearning', item.end_clearning);
                  }
              }
          });
      });'''

content = content.replace(old_logic, new_logic)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
