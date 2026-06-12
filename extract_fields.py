import os
import re

base_dir = r'C:\PMS\Production_Plan\resources\views\pages\materData'
for d in os.listdir(base_dir):
    p = os.path.join(base_dir, d, 'dataTable.blade.php')
    if os.path.exists(p):
        content = open(p, 'r', encoding='utf-8').read()
        inputs = re.findall(r'modal\.find\(\'input\[name="(.*?)"\]\'\)', content)
        selects = re.findall(r'modal\.find\(\'select\[name="(.*?)"\]\'\)', content)
        print(d, inputs + selects)

for d in [r'C:\PMS\Production_Plan\resources\views\pages\category\intermediate', r'C:\PMS\Production_Plan\resources\views\pages\category\product']:
    p = os.path.join(d, 'dataTable.blade.php')
    if os.path.exists(p):
        content = open(p, 'r', encoding='utf-8').read()
        inputs = re.findall(r'modal\.find\(\'input\[name="(.*?)"\]\'\)', content)
        selects = re.findall(r'modal\.find\(\'select\[name="(.*?)"\]\'\)', content)
        print(os.path.basename(d), inputs + selects)

