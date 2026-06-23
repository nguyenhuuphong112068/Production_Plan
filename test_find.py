import re

with open('c:/PMS/Production_Plan/resources/js/Pages/FullCalender.jsx', 'r', encoding='utf-8') as f:
    content = f.read()

target = """              Swal.fire({
                icon: 'success',
                title: 'Hoàn Thành Sắp Lịch',
                timer: 1000,
                showConfirmButton: false,
              });
              setLoading(!loading)"""

if target in content:
    print("Found exact match in FullCalender.jsx")
else:
    print("Not found EXACT match")
    # Let's search using regex
    match = re.search(r"Swal\.fire\(\{\s*icon:\s*'success',\s*title:\s*'Hoàn Thành Sắp Lịch',\s*timer:\s*1000,\s*showConfirmButton:\s*false,\s*\}\);\s*setLoading\(!loading\)", content)
    if match:
        print("Found with regex!")
    else:
        print("Not found with regex either")
