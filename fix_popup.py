import re

with open('c:/PMS/Production_Plan/resources/js/Components/AutoSchedulerPopup.jsx', 'r', encoding='utf-8') as f:
    content = f.read()

target = """          Swal.fire({
            icon: "success",
            title: "Hoàn Thành Sắp Lịch",
            timer: 1000,
            showConfirmButton: false,
          });

          setEvents(data.events);
          setSumBatchByStage(data.sumBatchByStage);
          setPlan(data.plan);"""

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
                            startDate: activeStart.toISOString(),
                            endDate: activeEnd.toISOString(),
                            overdueCampaigns: data.overdueCampaigns
                        }).then(res2 => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Hoàn Thành Pass 2',
                                timer: 1000,
                                showConfirmButton: false,
                            });
                            
                            let data2 = res2.data;
                            if (typeof data2 === "string") {
                                data2 = data2.replace(/^<!--.*?-->/, "").trim();
                                data2 = JSON.parse(data2);
                            }
                            setEvents(data2.events);
                            setSumBatchByStage(data2.sumBatchByStage);
                            if(data2.plan) setPlan(data2.plan);
                        }).catch(err => {
                            Swal.fire('Lỗi Pass 2', err.message, 'error');
                        });
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Hoàn Thành Sắp Lịch',
                            timer: 1000,
                            showConfirmButton: false,
                        });
                        setEvents(data.events);
                        setSumBatchByStage(data.sumBatchByStage);
                        if(data.plan) setPlan(data.plan);
                    }
                });
              } else {
                  Swal.fire({
                    icon: 'success',
                    title: 'Hoàn Thành Sắp Lịch',
                    timer: 1000,
                    showConfirmButton: false,
                  });
                  setEvents(data.events);
                  setSumBatchByStage(data.sumBatchByStage);
                  if(data.plan) setPlan(data.plan);
              }"""

if target in content:
    content = content.replace(target, replacement)
    with open('c:/PMS/Production_Plan/resources/js/Components/AutoSchedulerPopup.jsx', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Updated AutoSchedulerPopup")
else:
    print("Target not found in AutoSchedulerPopup")

