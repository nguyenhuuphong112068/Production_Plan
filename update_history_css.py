import os
import glob
import re

css_style = """<style>
    .history-modal-dialog {
        max-width: 80% !important;
        width: 80% !important;
        margin: 1.75rem auto;
    }

    #historyModal .modal-content {
        background-color: #ffffff;
        border-radius: 10px;
        overflow: hidden;
    }

    #historyModal .modal-header {
        background-color: #ffffff;
        border-bottom: 2px solid #CDC717;
        padding: 14px 20px;
    }

    #historyModal .modal-title {
        color: #003A4F;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    #historyModal .modal-body {
        padding: 0;
        max-height: 75vh;
        overflow-y: auto;
        overflow-x: auto;
        background: #ffffff;
    }

    #historyModal .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }

    #data_table_history {
        font-size: 14px;
        margin-bottom: 0;
    }

    #data_table_history thead th {
        background-color: #f4f6f9 !important;
        color: #003A4F !important;
        font-weight: 700;
        white-space: nowrap;
        padding: 10px;
        position: sticky;
        top: 0;
        z-index: 10;
        text-align: center;
        border-bottom: 2px solid #dee2e6;
    }

    #data_table_history tbody td {
        padding: 8px 10px;
        vertical-align: middle;
        text-align: center;
    }
</style>

"""

for f in glob.glob(r'C:\PMS\Production_Plan\resources\views\pages\materData\**\history.blade.php', recursive=True):
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()

    # 1. Add style block if not present
    if '<style>' not in content:
        content = css_style + content
    else:
        # replace existing style block
        content = re.sub(r'<style>[\s\S]*?</style>\s*', css_style, content)

    # 2. Update modal-dialog class
    content = content.replace('modal-dialog modal-xl', 'modal-dialog history-modal-dialog')

    # 3. Update table id and class
    content = re.sub(r'<table class="table table-bordered table-striped[^"]*">', '<table id="data_table_history" class="table table-bordered table-striped w-100">', content)
    
    # 4. Update thead
    content = re.sub(r'<thead[^>]*>', '<thead id="data_table_history_head">', content)

    # 5. Update modal-header
    # Extract the title text first
    match = re.search(r'<h5[^>]*>[\s\S]*?(Lịch Sử Thay Đổi[^\n<]+)[\s\S]*?</h5>', content)
    title_text = "Lịch Sử Thay Đổi"
    if match:
        title_text = match.group(1).strip()
    
    new_header = f"""<div class="modal-header">
                <a href="{{{{ route('pages.general.home') }}}}" class="mr-3">
                    <img src="{{{{ asset('img/iconstella.svg') }}}}" style="opacity: 0.85; max-width: 42px;">
                </a>

                <h5 class="modal-title w-100 text-center" id="historyModalLabel">
                    {title_text}
                </h5>

                <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>"""
    
    content = re.sub(r'<div class="modal-header">[\s\S]*?</div>\s*<div class="modal-body">', new_header + '\n            <div class="modal-body">', content)

    with open(f, 'w', encoding='utf-8') as file:
        file.write(content)

print('Updated history modal CSS in materData')
