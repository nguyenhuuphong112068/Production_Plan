import re

path = r'c:\PMS\Production_Plan\resources\js\Pages\FullCalender.jsx'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

content = re.sub(r'text: \\Nhp \(\$\{undoStack\.length\}\)\\,', 'text: Nháp (),', content)
content = re.sub(r"hint: 'Khi ph\?c thao tc ko th\? v\?a r\?i \(Ctrl\+Z\)'", "hint: 'Khôi phục thao tác kéo thả vừa rồi (Ctrl+Z)'", content)

# just in case the above didn't match perfectly, let's use a simpler match
content = re.sub(r'text: \\Nh.*?\)\\,', 'text: Nháp (),', content)
content = re.sub(r"hint: 'Kh.*?\+Z\)'", "hint: 'Khôi phục thao tác kéo thả vừa rồi (Ctrl+Z)'", content)


with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
