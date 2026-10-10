# Tài liệu Nguyên lý Sắp lịch tự động

Thư mục lưu các phiên bản tài liệu **"Nguyên lý Sắp lịch tự động và Kiểm soát tồn BTP"** dùng để trình bày với khách hàng. Thư mục được git theo dõi (ngoại lệ `!schedualer/**` trong `storage/app/.gitignore`), nên mọi thay đổi đều có lịch sử commit.

## Cấu trúc

```
schedualer/
├── README.md                     ← file này
├── CHANGELOG.md                  ← phiên bản nào thay đổi gì, vì sao
├── _tools/md_to_docx.py          ← chuyển bản .md sang Word (bìa, mục lục, header/footer, bảng)
├── _tools/docx_to_pdf.ps1        ← cập nhật mục lục + xuất PDF bằng Microsoft Word
└── nguyen-ly/
    └── v<MAJOR.MINOR>_<YYYY-MM-DD>/
        ├── Nguyen-ly-sap-lich-tu-dong-va-kiem-soat-ton-BTP.md    ← bản nguồn (sửa ở đây)
        ├── Nguyen-ly-sap-lich-tu-dong-va-kiem-soat-ton-BTP.docx  ← bản gửi khách hàng (Word)
        ├── Nguyen-ly-sap-lich-tu-dong-va-kiem-soat-ton-BTP.pdf   ← bản gửi khách hàng (PDF)
        └── so-do-dong-chay.png                                     ← sơ đồ dòng chảy công đoạn
```

Mỗi phiên bản là một thư mục riêng, **không sửa đè thư mục phiên bản cũ**.

## Đánh số phiên bản

- **MAJOR** (2.0, 3.0): đổi cách sắp lịch hoặc cách kiểm soát tồn (thêm/bỏ một bước, đổi thứ tự ưu tiên, đổi nguyên lý ngưng nguồn).
- **MINOR** (1.1, 1.2): bổ sung, làm rõ, sửa chỗ viết sai mà nguyên lý không đổi.

## Quy trình khi nguyên lý sắp lịch tự động thay đổi

1. Cập nhật Skills trong repo trước: `.agents/skills/schedual/SKILL.md` (lõi) và/hoặc `.agents/skills/wip-control/SKILL.md` (plugin Kiểm soát tồn BTP).
2. **Người dùng có thể đã sửa tay file `.docx` của bản mới nhất** (so ngày sửa file `.docx` với `.md`): trích chữ từ `.docx` (python-docx), so với `.md`, đưa đúng lời văn đã sửa vào bản mới (chỉ chữa lỗi gõ) và ghi rõ trong CHANGELOG.
3. Chép thư mục phiên bản mới nhất thành thư mục mới `nguyen-ly/v<số mới>_<ngày>/`, sửa file `.md` trong đó (dòng "Phiên bản … · ngày" ở đầu file cũng phải đổi). Viết cho khách hàng: dễ hiểu, không tên hàm, không tên bảng.
4. Sơ đồ đổi thì thay `so-do-dong-chay.png`.
5. Xuất Word rồi PDF (cần `pip install python-docx` và Microsoft Word trên máy):
   - `python storage/app/schedualer/_tools/md_to_docx.py "<đường dẫn file .md>"`
   - `powershell -ExecutionPolicy Bypass -File storage/app/schedualer/_tools/docx_to_pdf.ps1 "<đường dẫn file .docx>"` (cập nhật mục lục, số trang, lưu lại .docx và xuất .pdf cạnh đó)
   - Mở PDF xem lại: bìa, mục lục, bảng không bị cắt, khung ví dụ không tách trang.
   - Quy ước markdown công cụ hiểu: `#` tiêu đề (lên bìa), dòng `Phiên bản X.Y · dd/mm/yyyy`, `##` Heading 1 (`## Phần …` sang trang mới), `###` Heading 2 (đánh số `1.1.`), `####` Heading 3, bảng `| |`, danh sách `- ` / `1. `, ảnh `![chú thích](file.png)`, khung nổi bật `> …`.
6. Ghi một mục mới ở đầu `CHANGELOG.md`.
7. Nếu có bản trực tuyến (Claude Docs) thì cập nhật cho khớp; link bản trực tuyến ghi trong `CHANGELOG.md`.
