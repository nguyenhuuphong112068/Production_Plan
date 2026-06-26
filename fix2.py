import re

path = r'c:\PMS\Production_Plan\resources\js\Pages\FullCalender.jsx'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = re.sub(r'text: Nháp \(\),', 'text: Nháp (),', content)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
