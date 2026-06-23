import re

file = 'c:/PMS/Production_Plan/resources/js/Pages/FullCalender.jsx'
with open(file, 'r', encoding='utf-8') as f:
    content = f.read()

# We need to find the block after JSON.parse(data)
start_marker = """              if (typeof data === "string") {
                data = data.replace(/^<!--.*?-->/, "").trim();
                data = JSON.parse(data);
              }"""

end_marker = """            })
            .catch(err => {"""

start_idx = content.find(start_marker)
end_idx = content.find(end_marker, start_idx)

if start_idx != -1 and end_idx != -1:
    new_block = start_marker + """
              
              Swal.fire({
                icon: 'success',
                title: 'Hoàn Thành Sắp Lịch',
                timer: 1000,
                showConfirmButton: false,
              });
              setLoading(!loading);
"""
    content = content[:start_idx] + new_block + content[end_idx:]
    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Cleaned up the duplicate blocks in FullCalender.jsx")
else:
    print("Could not find markers to clean up")

