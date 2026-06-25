const fs = require('fs');

const chart = `graph TD
    %% Khởi tạo
    Start([Bắt đầu: Khởi tạo thông số]) --> Init[Tiếp nhận start_date, work_sunday, Wait Times]
    Init --> Queue[Đưa vào Hàng đợi Ưu tiên]

    %% Luồng ưu tiên
    Queue --> P1[Ưu tiên 1: Giải phóng WIP<br/>Backward Scheduling]
    Queue --> P2[Ưu tiên 2: Nguy cơ quá hạn NVL<br/>EDF]
    Queue --> P3[Ưu tiên 3: Sản phẩm cách ly<br/>Forward Scheduling]
    Queue --> P4[Ưu tiên 4: Kế hoạch đại trà<br/>Forward Scheduling]

    %% Hợp luồng
    P1 --> SelectOrder[Chọn Lệnh sản xuất]
    P2 --> SelectOrder
    P3 --> SelectOrder
    P4 --> SelectOrder

    %% Xử lý thiết bị
    SelectOrder --> EarliestTime[1. Xác định Thời gian khả thi sớm nhất]
    EarliestTime --> FilterEquip[2. Sàng lọc Thiết bị tương thích]
    FilterEquip --> ScanSlot[3. Dò tìm Khoảng trống<br/>Time-Slot Scanning]
    
    %% Tối ưu
    ScanSlot --> CheckCampaign{4. Cùng Chiến dịch?}
    CheckCampaign -->|Có| OptCampaign[Gom mẻ, áp dụng vệ sinh nhẹ C1]
    CheckCampaign -->|Không| NormalClean[Áp dụng vệ sinh toàn diện C2]
    
    OptCampaign --> Assign[Phân bổ Thiết bị & Thời gian]
    NormalClean --> Assign

    %% Ràng buộc Immediately
    Assign --> CheckImmediate{5. Yêu cầu làm ngay<br/>immediately?}
    CheckImmediate -->|Có| NextStage[Cấp phát lịch <br/>công đoạn tiếp theo ngay]
    CheckImmediate -->|Không| FinishStage[Hoàn tất phân bổ <br/>công đoạn hiện tại]

    NextStage --> FinishStage
    FinishStage --> Loop{Còn Lệnh sản xuất?}
    Loop -->|Có| SelectOrder
    Loop -->|Không| End([Kết thúc Lập lịch])

    classDef process fill:#e1f5fe,stroke:#01579b,stroke-width:2px;
    classDef decision fill:#fff3e0,stroke:#e65100,stroke-width:2px;
    classDef startend fill:#e8f5e9,stroke:#1b5e20,stroke-width:2px;
    classDef priority fill:#fce4ec,stroke:#880e4f,stroke-width:2px;

    class Start,End startend;
    class Init,SelectOrder,EarliestTime,FilterEquip,ScanSlot,Assign,FinishStage,NextStage process;
    class CheckCampaign,CheckImmediate,Loop decision;
    class P1,P2,P3,P4 priority;
`;

fetch('https://kroki.io/mermaid/png', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ diagram_source: chart })
})
.then(res => {
  if (!res.ok) throw new Error('Status: ' + res.status);
  return res.arrayBuffer();
})
.then(buffer => {
  fs.writeFileSync('Sodo_Lich_Tu_Dong.png', Buffer.from(buffer));
  console.log('Image saved successfully!');
})
.catch(err => console.error(err));
