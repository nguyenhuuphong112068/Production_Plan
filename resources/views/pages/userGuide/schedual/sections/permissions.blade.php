@include('pages.userGuide.shared.permissionSection')

<h3>Ai sửa được Lịch Sản Xuất Chart</h3>
<p>
    Mọi tài khoản thấy menu <b>Lịch Sản Xuất</b> đều xem được biểu đồ. Sửa lịch (kéo thả, xoá, lưu, sắp lịch tự động, thả
    lô từ danh sách KHSX, Submit) không dùng quyền tick ở Nhóm Phân Quyền mà xét <b>nhóm quyền chính</b> và phân xưởng:
</p>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 260px">Tài khoản</th><th>Được làm gì trên Lịch Sản Xuất Chart</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Nhóm quyền chính <b>Admin</b> hoặc <b>Schedualer</b></td>
                <td>Sửa lịch, sắp lịch tự động và <a href="#sap-lich-can">gợi ý lịch cân</a>, <b>chỉ khi phân xưởng đang chọn trùng phòng ban của tài khoản</b>.
                    Chuyển sang phân xưởng khác (menu chọn phân xưởng PXV1, PXV2...) thì chỉ xem.</td>
            </tr>
            <tr>
                <td>Nhóm quyền chính <b>Weighing Scheduler</b> (Người Lập Lịch Cân - sắp lịch trung tâm cân)</td>
                <td>Chỉ sắp lịch <b>công đoạn cân</b>, cùng điều kiện phân xưởng như Schedualer:
                    <ul style="margin: 4px 0 0 18px; padding: 0">
                        <li>Thả lô cân từ danh sách KHSX, kéo thả / kéo dài, sửa nhanh <span class="ug-emoji">✏️</span>, xoá <span class="ug-emoji">🚫</span>,
                            lưu <span class="ug-emoji">💾</span> và <a href="#sap-lich-can">gợi ý lịch cân</a> - chỉ với lịch cân, chỉ vào phòng cân.
                            Lịch các công đoạn khác không kéo được; chọn lẫn lịch khác rồi kéo / xoá thì phần mềm báo và không làm.</li>
                        <li>Bấm <span class="ug-emoji">📤</span> <b>Submit</b> chỉ submit <b>lịch cân</b>; lịch các công đoạn khác giữ nguyên chưa submit,
                            chờ Schedualer submit. Phần kiểm tra trước khi Submit chỉ xét lịch cân, không xét chính sách sản lượng.</li>
                        <li>Không có nút tịnh tiến <span class="ug-emoji">🔀</span> (dời cả công đoạn sau), sắp lịch tự động <span class="ug-emoji">🤖</span>,
                            xoá hàng loạt <span class="ug-emoji">🗑️</span>, chấp nhận biệt trữ <span class="ug-emoji">✅</span>, thẩm định / cấp vệ sinh
                            <span class="ug-emoji">🚿 🆚</span>. Các thao tác khác của danh sách KHSX (thứ tự, chiến dịch, không sắp...) bị từ chối.</li>
                    </ul>
                </td>
            </tr>
            <tr>
                <td>Tài khoản thuộc phòng ban <b>BOD</b></td>
                <td>Sửa lịch ở mọi phân xưởng.</td>
            </tr>
            <tr>
                <td>Các tài khoản khác</td>
                <td>Chỉ xem. Các nút sửa lịch (<span class="ug-emoji">🤖 🗑️ 💾 🚫</span>...) bấm vào không có tác dụng.
                    Vẫn bật được nút <b>⚖ Lô cần cân</b> để xem lô cần chuẩn bị cân; nút <b>Gợi ý lịch cân</b> không hiện.</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="ug-callout is-warn">
    <i class="fas fa-lock"></i>
    <div>
        <b>Một phân xưởng chỉ một người sắp lịch tự động tại một thời điểm.</b> Nếu người khác đang chạy, phần mềm báo
        <i>Đang sắp lịch</i> và không chạy lần thứ hai. Khi sắp lịch thủ công, phần mềm còn tự bật <b>Khóa TTSX</b>
        (khoá Nhận / Trả phòng của trang Thực Thi Sản Xuất; hiện áp dụng cho PXV1) - xem <a href="#chinh-lich-tay">Chỉnh lịch trên Gantt</a>.
    </div>
</div>
