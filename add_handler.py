import re

file = 'c:/PMS/Production_Plan/resources/js/Pages/FullCalender.jsx'
with open(file, 'r', encoding='utf-8') as f:
    content = f.read()

target_regex = r"if\s*\(btnFixPhaCheModal\)\s*\{\s*btnFixPhaCheModal\.addEventListener\('click',\s*\(\)\s*=>\s*\{\s*Swal\.close\(\);\s*setTimeout\(\(\)\s*=>\s*handleAutoFixByPhaChe\(\),\s*200\);\s*\}\);\s*\}"

replacement = """if (btnFixPhaCheModal) {
            btnFixPhaCheModal.addEventListener('click', () => {
              Swal.close();
              setTimeout(() => handleAutoFixByPhaChe(), 200);
            });
          }

          const btnFixPass2 = document.getElementById('btn-fix-pass2');
          if (btnFixPass2) {
            btnFixPass2.addEventListener('click', () => {
              Swal.close();
              setTimeout(() => {
                Swal.fire({
                  title: 'Đang chạy Pass 2...',
                  text: 'Vui lòng chờ trong giây lát',
                  allowOutsideClick: false,
                  didOpen: () => Swal.showLoading(),
                });
                
                const { activeStart, activeEnd } = calendarRef.current?.getApi().view;
                
                axios.post('/Schedual/scheduleAllPass2', {
                  startDate: toLocalISOString(activeStart),
                  endDate: toLocalISOString(activeEnd),
                }, { timeout: 1200000 }).then(res => {
                  let data = res.data;
                  if (data && data.success === false) {
                     Swal.fire('Thông báo', data.message, 'info');
                     setLoading(!loading);
                     return;
                  }
                  Swal.fire({
                    icon: 'success',
                    title: 'Hoàn Thành Pass 2',
                    timer: 1000,
                    showConfirmButton: false,
                  });
                  setLoading(!loading);
                }).catch(err => {
                  Swal.fire('Lỗi Pass 2', err.message, 'error');
                  setLoading(!loading);
                });
              }, 200);
            });
          }"""

new_content = re.sub(target_regex, replacement, content)

if new_content != content:
    with open(file, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print("Added click handler for Pass 2")
else:
    print("Could not find target regex")

