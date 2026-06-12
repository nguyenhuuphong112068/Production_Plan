import os
import re

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
    data_file = os.path.join(d, 'dataTable.blade.php')
    if os.path.exists(data_file):
        with open(data_file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # 1. Add Lịch Sử to the header
        # Find '<th>Edit</th>' or '<th style = "width: 15px">Edit</th>' or '<th>Thao Tác</th>'
        if '<th>Edit</th>' in content:
            content = content.replace('<th>Edit</th>', '<th>Edit</th>\n                        <th style="width: 15px">Lịch Sử</th>')
        elif '<th style = "width: 15px">Edit</th>' in content:
            content = content.replace('<th style = "width: 15px">Edit</th>', '<th style = "width: 15px">Edit</th>\n                        <th style="width: 15px">Lịch Sử</th>')
        elif '<th>Thao Tác</th>' in content:
            content = content.replace('<th>Thao Tác</th>', '<th>Thao Tác</th>\n                        <th style="width: 15px">Lịch Sử</th>')

        # 2. Extract the history button into its own <td>
        # The history button typically looks like:
        # <button class="btn btn-info btn-history...
        #     <i class="fas fa-history"></i>...
        # </button>
        # We find it and wrap it in a closing </td> and a new <td class="text-center align-middle">
        
        # We need a regex to match the history button
        btn_regex = r'(\s*<button class="btn btn-info btn-history[^>]*>[\s\S]*?</button>)'
        
        # When we find the button, it is currently inside the Thao Tác or Edit column.
        # We can just move it outside of its current <td>.
        # We replace:
        #   (the button)
        # with:
        #   \n</td>\n<td class="text-center align-middle">
        #   (the button)
        
        def replacement(match):
            btn = match.group(1)
            # Instead of closing the <td> here, wait, if we just replace it inside the td, 
            # it might be followed by a form (like DeActive) inside the same td?
            # Let's check:
            # In BlisterMold: btn-edit, btn-history, form-deActive are all in the same <td>.
            # If we do `</td><td>` it breaks the `form-deActive` into the `Lịch Sử` column!
            return '' # We'll do something smarter
            
        # Smarter approach:
        # Find the full <td> that contains btn-history
        td_regex = r'(<td class="text-center align-middle">\s*<button type="button" class="btn btn-warning btn-edit[\s\S]*?</button>\s*)(<button class="btn btn-info btn-history[\s\S]*?</button>\s*)([\s\S]*?</td>)'
        
        def td_replacement(match):
            part1_edit = match.group(1)
            part2_history = match.group(2)
            part3_rest = match.group(3)
            
            # The structure we want:
            # <td> {edit} {rest} </td>
            # <td> {history} </td>
            
            return f'{part1_edit}{part3_rest}\n                            <td class="text-center align-middle">\n                                {part2_history.strip()}\n                            </td>'

        content = re.sub(td_regex, td_replacement, content)
        
        with open(data_file, 'w', encoding='utf-8') as f:
            f.write(content)

print("Done updating dataTable files.")
