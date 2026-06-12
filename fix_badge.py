import os
import glob

for f in glob.glob(r'C:\PMS\Production_Plan\resources\views\pages\**\dataTable.blade.php', recursive=True):
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    if 'btn-history' in content:
        content = content.replace('btn-history mb-1"', 'btn-history mb-1 position-relative"')
        content = content.replace('<span class="badge badge-danger">', '<span class="badge badge-danger" style="position: absolute; top: -5px; right: -5px; padding: 4px 6px; border-radius: 50%; font-size: 10px;">')
        with open(f, 'w', encoding='utf-8') as file:
            file.write(content)
print('Fixed badge CSS')
