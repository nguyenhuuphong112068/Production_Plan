import re

replacement = """              if (data && data.overdueCampaigns && data.overdueCampaigns.length > 0) {
                Swal.fire({
                    title: `Phát hiện ${data.overdueCampaigns.length} chiến dịch quá hạn!`,
                    text: "Bạn có muốn hệ thống chạy Tối ưu hóa (Pass 2) đẩy độ ưu tiên tuyệt đối để triệt tiêu lỗi quá hạn này không?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Có, Tối Ưu',
                    cancelButtonText: 'Không',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                }).then((pass2Result) => {
                    if (pass2Result.isConfirmed) {
                        Swal.fire({
                            title: 'Đang chạy Pass 2...',
                            text: 'Vui lòng chờ trong giây lát',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading(),
                        });
                        axios.post('/Schedual/scheduleAllPass2', {
                            ...result.value,
                            startDate: toLocalISOString(activeStart),
                            endDate: toLocalISOString(activeEnd),
                            stage_plan_ids: handleShowLine(result.value['lines']),
                            room_code: result.value['lines'],
                            overdueCampaigns: data.overdueCampaigns
                        }, { timeout: 1200000 }).then(res2 => {
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
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Hoàn Thành Sắp Lịch',
                            timer: 1000,
                            showConfirmButton: false,
                        });
                        setLoading(!loading);
                    }
                });
              } else {
                  Swal.fire({
                    icon: 'success',
                    title: 'Hoàn Thành Sắp Lịch',
                    timer: 1000,
                    showConfirmButton: false,
                  });
                  setLoading(!loading);
              }"""

files = ['c:/PMS/Production_Plan/resources/js/Pages/FullCalender.jsx', 'c:/PMS/Production_Plan/resources/js/Pages/FullCalender copy.jsx']

for file in files:
    try:
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        new_content = re.sub(
            r"Swal\.fire\(\{\s*icon:\s*'success',\s*title:\s*'Hoàn Thành Sắp Lịch',\s*timer:\s*1000,\s*showConfirmButton:\s*false,?\s*\}\);\s*setLoading\(!loading\)",
            replacement,
            content
        )
        
        if new_content != content:
            with open(file, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {file}")
        else:
            print(f"No changes made to {file}")
    except Exception as e:
        print(f"Error {file}: {e}")

