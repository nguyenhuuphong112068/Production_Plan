# Lịch sử phiên bản – Nguyên lý Sắp lịch tự động và Kiểm soát tồn BTP

Mục mới nhất ở trên cùng.

## v1.2 – 10/10/2026

Thư mục: `nguyen-ly/v1.2_2026-10-10/` (`.md`, `.docx`, `.pdf`, 9 trang)

Nguyên lý không đổi. Thay đổi:

- **Giữ lời văn người dùng đã sửa trực tiếp trong bản Word v1.1** (chỉ chữa lỗi gõ: "hang" → "hàng", "sếp" → "xếp", "lượng số lượng" → "số lượng", "phòng sx" → "phòng sản xuất"):
  - Giới thiệu: Sắp lịch tự động xếp sớm nhất "để khai thác tối đa năng lực phòng sản xuất".
  - 1.1: "các công đoạn của sản phẩm chưa có lịch"; bảng dữ liệu: "lô nào gấp hơn (theo ngày cần hàng)".
  - 1.2: chiến dịch gom lô "cùng sản phẩm (cùng mã BTP/TP)".
  - 1.3 nhóm 5, 6: "các sản phẩm cùng nhóm xếp theo ngày cần hàng".
  - 2.2 bảng, Chờ ĐH: "Trộn hoàn tất, hoặc Pha chế của lô không có THT".
  - 2.3: "Công đoạn tiêu thụ (nút cổ chai, có cài đặt số lượng BTP Max)".
- 2.3 thêm mục **Ví dụ minh hoạ theo từng trường hợp**: Chờ Định hình vượt Max, Chờ Bao phim vượt Max, Chờ Đóng gói vượt Max (3 nguồn), lô sắp tới hạn được ép chạy; thêm câu về thứ tự xử lý khi cài nhiều nhóm.
- Bản Word v1.1 trong thư mục `v1.1_2026-10-10/` là bản người dùng đã sửa tay (khác file `.md` cùng thư mục).

## v1.1 – 10/10/2026

Thư mục: `nguyen-ly/v1.1_2026-10-10/` (có `.docx` và `.pdf` để gửi khách hàng)

Nguyên lý không đổi so với v1.0. Thay đổi về trình bày:

- Thêm trang bìa, mục lục tự động, header/footer có số trang "Trang X / Y".
- Đánh số mục (1.1–1.5, 2.1–2.5); Phần 1 và Phần 2 bắt đầu trang mới.
- Thêm bảng **Thuật ngữ viết tắt** (PC, THT, ĐH, BP, ĐG, BTP, NL/BB, HH, BT-HC-TI, Chiến dịch, Max).
- Đoạn giới thiệu và ví dụ minh hoạ đặt trong khung nổi bật; hình được đánh số.
- Thêm bản PDF (có bookmark theo mục).

## v1.0 – 10/10/2026

Thư mục: `nguyen-ly/v1.0_2026-10-10/`
Bản trực tuyến: https://claude.ai/artifact/SZRvWgbQheSqVwHp41KuBw

Bản đầu tiên, mô tả hệ thống tại ngày 10/10/2026:

- Sắp lịch tự động: dữ liệu đầu vào, chiến dịch (xếp nguyên khối, không tách), thứ tự ưu tiên 6 nhóm, cách chọn phòng và giờ bắt đầu (hạn chế xuống khuôn 72 giờ, phòng đang nhận phòng bận tới giờ dự kiến kết thúc).
- Tự kiểm tra sau khi xếp: chen lịch theo Ngày HH NL chính / HH BB (tối đa 3 lần sắp lại); chiến dịch xếp xuyên BT-HC-TI chưa bắt đầu, BT-HC-TI bị đè được dời và kiểm tra hạn BT (HC 0 ngày, hàng tháng +7, loại khác +21).
- Kiểm soát tồn BTP: 3 nhóm chờ (ĐH, BP, ĐG), đo lúc 06:00 trong 30 ngày, nguyên lý ngưng nguồn, 7 ngày NL/BB luôn là hạn cứng, cơ chế an toàn theo vòng (tối đa 10 vòng / 15 phút), kết quả và giới hạn.
