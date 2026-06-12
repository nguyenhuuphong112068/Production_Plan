import os
import re

directories = [
    r'C:\PMS\Production_Plan\resources\views\pages\category\intermediate',
    r'C:\PMS\Production_Plan\resources\views\pages\category\product'
]

history_th = '\n                        <th class="text-center align-middle">Lịch Sử</th>'
history_td = '''
                            <td class="text-center align-middle">
                                <button class="btn btn-info btn-history mb-1" data-id="{{ $data->id }}" title="Lịch sử thay đổi">
                                    <i class="fas fa-history"></i>
                                    @if(isset($historyCounts) && isset($historyCounts[$data->id]))
                                        <span class="badge badge-danger">{{ $historyCounts[$data->id]->total }}</span>
                                    @endif
                                </button>
                            </td>'''

for d in directories:
    # 1. Update history.blade.php
    history_file = os.path.join(d, 'history.blade.php')
    if os.path.exists(history_file):
        with open(history_file, 'r', encoding='utf-8') as f:
            content = f.read()

        # Update modal header to center and add logo
        content = re.sub(r'<h5 class="modal-title" id="historyModalLabel">(.*?)</h5>',
                         r'<h5 class="modal-title w-100 text-center font-weight-bold" id="historyModalLabel">\n                    <img src="{{ asset(\'dist/img/logo.png\') }}" style="width: 25px; margin-right: 10px; margin-bottom: 5px;">\n                    \1\n                </h5>', content)

        # Fix backslash if any
        content = content.replace(r"\'dist/img/logo.png\'", "'dist/img/logo.png'")

        # Update column headers
        content = content.replace('<th>Thời Gian</th>', '<th class="text-center align-middle">Ngày Sửa</th>')
        content = content.replace('<th>Người Cập Nhật</th>', '<th class="text-center align-middle">Người Sửa</th>')
        
        # Center all th
        content = re.sub(r'<th>(.*?)</th>', r'<th class="text-center align-middle">\1</th>', content)
        
        # Add footer with close button
        if 'modal-footer' not in content:
            footer = '''            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Đóng</button>
            </div>
        </div>
    </div>
</div>'''
            # Only append footer if not exist. The div structure might be slightly different.
            if '</div>\n        </div>\n    </div>\n</div>' in content:
                content = content.replace('            </div>\n        </div>\n    </div>\n</div>', footer)

        with open(history_file, 'w', encoding='utf-8') as f:
            f.write(content)

    # 2. Update dataTable.blade.php
    data_file = os.path.join(d, 'dataTable.blade.php')
    if os.path.exists(data_file):
        with open(data_file, 'r', encoding='utf-8') as f:
            content = f.read()

        # Add TH
        if '<th class="text-center align-middle">Lịch Sử</th>' not in content:
            content = re.sub(r'(</th>)\s*</tr>\s*</thead>', r'\1' + history_th + r'\n                    </tr>\n                </thead>', content)

        # Extract btn-history if it's already inside
        # Note: If it's already mixed, we should run the split logic. But wait, I'll just remove the old btn-history and append the new TD at the end.
        btn_regex = r'(\s*<button class="btn btn-info btn-history[^>]*>[\s\S]*?</button>)'
        content = re.sub(btn_regex, '', content)

        # Add TD
        if 'title="Lịch sử thay đổi"' not in content:
            content = re.sub(r'</td>\s*</tr>\s*@endforeach', r'</td>' + history_td + r'\n                        </tr>\n                    @endforeach', content)

        with open(data_file, 'w', encoding='utf-8') as f:
            f.write(content)

print("Done")
