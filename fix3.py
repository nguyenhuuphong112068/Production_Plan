import re

path = r'c:\PMS\Production_Plan\resources\js\Pages\FullCalender.jsx'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

content = content.replace("Hoï¿½n tï¿½c nhï¿½p", "Hoàn tác nháp")
content = content.replace("Khï¿½ng cï¿½ thao tï¿½c kï¿½o th? nï¿½o d? hoï¿½n tï¿½c.", "Không có thao tác kéo thả nào để hoàn tác.")

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
