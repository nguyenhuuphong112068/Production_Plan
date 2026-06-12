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
    r'C:\PMS\Production_Plan\resources\views\pages\materData\BlisterMold',
    r'C:\PMS\Production_Plan\resources\views\pages\category\intermediate',
    r'C:\PMS\Production_Plan\resources\views\pages\category\product'
]

for d in directories:
    # 1. Fix logo in history.blade.php
    history_file = os.path.join(d, 'history.blade.php')
    if os.path.exists(history_file):
        with open(history_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Replace incorrectly escaped backslashes and wrong path
        content = content.replace("'dist/img/logo.png'", "'img/logo/logo.png'")
        
        with open(history_file, 'w', encoding='utf-8') as f:
            f.write(content)

    # 2. Fix badge position in dataTable.blade.php
    data_file = os.path.join(d, 'dataTable.blade.php')
    if os.path.exists(data_file):
        with open(data_file, 'r', encoding='utf-8') as f:
            content = f.read()

        # Update button to have position-relative
        content = content.replace('class="btn btn-info btn-history mb-1"', 'class="btn btn-info btn-history mb-1 position-relative"')
        content = content.replace('class="btn btn-info btn-history"', 'class="btn btn-info btn-history position-relative"')
        
        # Avoid duplicating position-relative if script is run multiple times
        content = content.replace('position-relative position-relative', 'position-relative')

        # Update badge to have absolute positioning
        old_badge = '<span class="badge badge-danger">{{ $historyCounts[$data->id]->total }}</span>'
        new_badge = '<span class="badge badge-danger" style="position: absolute; top: -5px; right: -5px; padding: 4px 6px; border-radius: 50%; font-size: 10px;">{{ $historyCounts[$data->id]->total }}</span>'
        
        # Also handle badge without surrounding whitespace issues by matching exactly or updating what's there
        content = content.replace(old_badge, new_badge)

        with open(data_file, 'w', encoding='utf-8') as f:
            f.write(content)

print("Fixed logo and badge!")
