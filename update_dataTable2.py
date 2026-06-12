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
    data_file = os.path.join(d, 'dataTable.blade.php')
    if os.path.exists(data_file):
        with open(data_file, 'r', encoding='utf-8') as f:
            content = f.read()

        # 1. Insert history TH before the closing </tr> of <thead>
        content = re.sub(r'(</th>)\s*</tr>\s*</thead>', r'\1' + history_th + r'\n                    </tr>\n                </thead>', content)

        # 2. Insert history TD before the closing </tr> of the loop
        content = re.sub(r'</td>\s*</tr>\s*@endforeach', r'</td>' + history_td + r'\n                        </tr>\n                    @endforeach', content)

        with open(data_file, 'w', encoding='utf-8') as f:
            f.write(content)

print('Done')
