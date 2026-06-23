import re

file = 'c:/PMS/Production_Plan/resources/js/Pages/FullCalender.jsx'
with open(file, 'r', encoding='utf-8') as f:
    content = f.read()

button_html = """
            <button type="button" id="btn-fix-pass2"
              style="background:#27ae60;color:white;border:none;border-radius:6px;padding:12px 10px;text-align:left;cursor:pointer;font-size:12px;line-height:1.5;transition:opacity .2s"
              onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
              <div style="font-weight:700;font-size:13px">🚀 Tối Ưu Hóa (Pass 2)</div>
              <div style="opacity:.9;margin-top:3px">Xếp lại lịch ưu tiên tuyệt đối cho các chiến dịch quá hạn biệt trữ</div>
            </button>
"""

target = """            <button type="button" id="btn-fix-phache-modal"
              style="background:#8e44ad;color:white;border:none;border-radius:6px;padding:12px 10px;text-align:left;cursor:pointer;font-size:12px;line-height:1.5;transition:opacity .2s"
              onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
              <div style="font-weight:700;font-size:13px">🧬 Sửa Lỗi Đen Theo Pha Chế</div>
              <div style="opacity:.9;margin-top:3px">Cố định Pha Chế (3&4), sắp xếp công đoạn khác thoát đen</div>
            </button>"""

replacement = target + button_html

if target in content:
    content = content.replace(target, replacement)
    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Added button html to FullCalender.jsx")
else:
    print("Target HTML not found")
