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
    # Update history.blade.php
    history_file = os.path.join(d, 'history.blade.php')
    if os.path.exists(history_file):
        with open(history_file, 'r', encoding='utf-8') as f:
            content = f.read()

        # Update modal header to center and add logo
        content = re.sub(r'<h5 class="modal-title" id="historyModalLabel">(.*?)</h5>',
                         r'<h5 class="modal-title w-100 text-center font-weight-bold" id="historyModalLabel">\n                    <img src="{{ asset(\'dist/img/logo.png\') }}" style="width: 25px; margin-right: 10px; margin-bottom: 5px;">\n                    \1\n                </h5>', content)

        # Update column headers: 'Thời Gian' -> 'Ngày Sửa', 'Người Cập Nhật' -> 'Người Sửa'
        content = content.replace('<th>Thời Gian</th>', '<th class="text-center align-middle">Ngày Sửa</th>')
        content = content.replace('<th>Người Cập Nhật</th>', '<th class="text-center align-middle">Người Sửa</th>')
        
        # Center all th
        content = re.sub(r'<th>(.*?)</th>', r'<th class="text-center align-middle">\1</th>', content)
        
        # Center cells logic is in dataTable.blade.php, let's update that next.
        
        # Add footer with close button
        if 'modal-footer' not in content:
            footer = '''            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Đóng</button>
            </div>
        </div>
    </div>
</div>'''
            content = re.sub(r'            </div>\s*</div>\s*</div>\s*</div>$', footer, content, flags=re.MULTILINE)

        with open(history_file, 'w', encoding='utf-8') as f:
            f.write(content)
            
    # Update dataTable.blade.php to separate column and add text-center
    data_file = os.path.join(d, 'dataTable.blade.php')
    if os.path.exists(data_file):
        with open(data_file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # Add Lịch Sử column header in the table (before DeActive or after Edit/Thao Tác)
        # Usually it's `<th>Thao Tác</th>` or `<th style = "width: 15px"> DeActive</th>`
        # Let's see:
        if '<th style = "width: 15px"> DeActive</th>' in content:
             content = content.replace('<th style = "width: 15px"> DeActive</th>', '<th style="width: 15px">Lịch Sử</th>\n                        <th style = "width: 15px"> DeActive</th>')
        elif '<th>Thao Tác</th>' in content:
             content = content.replace('<th>Thao Tác</th>', '<th>Thao Tác</th>\n                        <th>Lịch Sử</th>')
             
        # Now replace the <button class="btn btn-info btn-history" ...
        # Right now it's in the SAME <td> as Edit button.
        # We want to extract it and put it in a new <td>
        history_btn_regex = r'(<button class="btn btn-info btn-history"[\s\S]*?</button>)'
        
        # Find it and remove it from current position
        matches = re.findall(history_btn_regex, content)
        if matches:
            history_btn = matches[0]
            content = content.replace(history_btn, '')
            # Now we add a new td after the <td> that contained it
            # The current TD usually ends with </button>\s*</td> or </form>\s*</td>
            # Wait, if we use the marker `btn-history`, let's just do a string replacement on the <tr>.
            # But the row structure differs.
            pass
            
        with open(data_file, 'w', encoding='utf-8') as f:
            f.write(content)
