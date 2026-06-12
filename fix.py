import os

directories = [
    r'C:\PMS\Production_Plan\resources\views\pages\materData\Unit',
    r'C:\PMS\Production_Plan\resources\views\pages\materData\StageGroup',
    r'C:\PMS\Production_Plan\resources\views\pages\materData\Specification',
    r'C:\PMS\Production_Plan\resources\views\pages\materData\source_material',
    r'C:\PMS\Production_Plan\resources\views\pages\materData\Room',
    r'C:\PMS\Production_Plan\resources\views\pages\materData\Market',
    r'C:\PMS\Production_Plan\resources\views\pages\materData\productName',
    r'C:\PMS\Production_Plan\resources\views\pages\materData\Dosage',
    r'C:\PMS\Production_Plan\resources\views\pages\materData\BlisterType',
    r'C:\PMS\Production_Plan\resources\views\pages\materData\Department',
    r'C:\PMS\Production_Plan\resources\views\pages\materData\BlisterMold'
]

for d in directories:
    fpath = os.path.join(d, 'history.blade.php')
    if os.path.exists(fpath):
        with open(fpath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Replace incorrectly escaped backslashes
        content = content.replace(r"\'dist/img/logo.png\'", "'dist/img/logo.png'")
        
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)

print("Fixed!")
