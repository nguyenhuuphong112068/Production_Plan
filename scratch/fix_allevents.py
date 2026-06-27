import re

with open('resources/js/Pages/FullCalender.jsx', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace allEvents initialization
old_init = "const allEvents = calendarApi.getEvents();"
new_init = "const allEvents = calendarApi.getEvents().filter(e => e.display !== 'background' && !e.extendedProps?.is_personnel && !String(e.id).startsWith('personnel-'));"

if old_init in content:
    content = content.replace(old_init, new_init)
    with open('resources/js/Pages/FullCalender.jsx', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed allEvents.")
else:
    print("old_init not found. It might have already been replaced.")
