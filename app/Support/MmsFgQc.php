<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Ngày KCS và số phiếu COATP lấy từ MMS (bảng fgqc).
 *
 * Mỗi lần nhập kho thành phẩm sinh một GRN, và fgqc ghi hai dòng cho GRN đó:
 * step='Sampling' lúc lấy mẫu, step='QA Approval' lúc KCS duyệt. Ngày KCS là
 * fgqc.cron của dòng 'QA Approval' - đối chiếu với 76 lô người dùng đã tự nhập tay
 * trên trang Theo dõi hồ sơ KCS thì khớp 76/76.
 *
 * fgqc không có cột nào nối thẳng về kế hoạch được, phải đi vòng qua FGGRN:
 *
 *     fgqc.grnno -> FGGRN.GRNNO -> FGGRN.Mfgbatchno (số lô) + FGGRN.MatID (mã TP)
 *
 * Cố ý KHÔNG nối bằng số lệnh: PMS lưu "2545/6R1S" trong khi MMS lưu "2545/6P2S",
 * hậu tố khác hẳn nhau (MMS còn có biến thể "4653A/5P2S") nên thử nối kiểu này
 * không khớp được lô nào trong 3.082 lô.
 *
 * Cũng KHÔNG nối bằng số lô đơn thuần: quá nửa số lô (1.639/3.262) được dùng lại
 * cho nhiều mã TP khác nhau, nối như vậy sẽ gán nhầm ngày KCS của lô khác.
 *
 * Toàn bộ lớp này chỉ SELECT trên MMS, không có lệnh nào làm thay đổi dữ liệu.
 */
class MmsFgQc
{
    /** Kết quả đã tra của từng mốc thời gian, cache trong 1 request */
    private static array $cache = [];

    /**
     * Các lần KCS duyệt tính từ $since trở đi.
     *
     * Cố ý nhận mốc thời gian thay vì lấy trọn bảng như MmsBom: fgqc có 56.700 dòng
     * 'QA Approval' và kéo hết mất thêm ~1,7 giây mỗi lần mở trang, trong khi lưới
     * chỉ hiển thị vài tháng kế hoạch.
     *
     * @return array{
     *     approvals: array<string, array{kcs_date: string, coatp_number: string}>,
     *     orders: array<string, array{code: string, batch: string}|false>
     * } approvals khoá theo "mã TP|số lô"; orders khoá theo số lệnh đã chuẩn hoá và chỉ
     *   dùng để dò lô lệch mã TP (xem normalizeOrderNumber), giá trị false nghĩa là số
     *   lệnh đó ứng với nhiều lô nên không kết luận được.
     */
    public static function approvalsSince(DateTimeInterface $since): array
    {
        $key = $since->format('Y-m-d');

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // Chỉ lấy dòng 'QA Approval': dòng 'Sampling' của cùng GRN là ngày lấy mẫu,
        // không phải ngày KCS.
        //
        // Cố ý không lọc thêm qcsts = 'Approved': lô từng bị Reject rồi duyệt lại vẫn
        // phải có ngày, và đối chiếu cho thấy bỏ lọc này không làm đổi ngày của lô nào
        // (do đã lấy lần duyệt sớm nhất), chỉ thêm được 3 lô trước đó bị bỏ sót.
        $sql = "
            SELECT g.MatID, g.Mfgbatchno, g.prdorderno, q.coano, q.cron
            FROM FGGRN g
            INNER JOIN fgqc q ON q.grnno = g.GRNNO
            WHERE q.step = N'QA Approval' AND q.cron >= ?
        ";

        $approvals = [];
        $orders = [];

        foreach (DB::connection('mms')->select($sql, [$key]) as $row) {
            $code = trim((string) $row->MatID);
            $batch = trim((string) $row->Mfgbatchno);

            if ($code === '' || $batch === '') {
                continue;
            }

            $date = substr((string) $row->cron, 0, 10);
            $lookup = $code . '|' . $batch;

            // Lô được duyệt nhiều lần (tái kiểm / duyệt lại) thì lấy lần duyệt đầu tiên
            if (!isset($approvals[$lookup]) || $date < $approvals[$lookup]['kcs_date']) {
                $approvals[$lookup] = [
                    'kcs_date' => $date,
                    'coatp_number' => trim((string) $row->coano),
                ];
            }

            $order = self::normalizeOrderNumber($row->prdorderno);

            if ($order === null) {
                continue;
            }

            // Số lệnh sau chuẩn hoá có thể đụng nhau (VD lệnh đóng gói lại dùng dãy số
            // riêng). Đánh dấu false để bên dùng bỏ qua thay vì kết luận nhầm.
            if (!isset($orders[$order])) {
                $orders[$order] = ['code' => $code, 'batch' => $batch];
            } elseif ($orders[$order] !== false
                && ($orders[$order]['code'] !== $code || $orders[$order]['batch'] !== $batch)) {
                $orders[$order] = false;
            }
        }

        return self::$cache[$key] = ['approvals' => $approvals, 'orders' => $orders];
    }

    /**
     * Số lệnh rút về phần so sánh được giữa hai hệ thống: "2648/6P2S" (MMS) và
     * "2648/6RS" (PMS) đều thành "2648/6".
     *
     * Hai bên đánh hậu tố khác hẳn nhau - PMS dùng R/R1/R2, MMS dùng P2 và còn chèn
     * chữ cái vào phần số khi cấp lại lệnh ("4653A/5P2S") - nên nối bằng số lệnh
     * nguyên văn không khớp được lô nào. Chỉ phần số thứ tự và chữ số năm là chung.
     *
     * Cố ý KHÔNG dùng làm khoá lấy ngày KCS, chỉ dùng để đối chứng mã TP: đối chiếu
     * cho thấy 2.416 lô khớp / 5 lô lệch so với khoá "mã TP|số lô", đủ tin cậy để
     * cảnh báo nhưng khoá chính vẫn nên là cặp mã TP + số lô.
     */
    public static function normalizeOrderNumber(?string $orderNumber): ?string
    {
        if (!preg_match('~^\s*(\d+)[A-Za-z]*\s*/\s*(\d)~', (string) $orderNumber, $matches)) {
            return null;
        }

        return ltrim($matches[1], '0') . '/' . $matches[2];
    }

    /**
     * Lần KCS duyệt sớm nhất của đúng một lô.
     *
     * Dùng lúc lưu một dòng theo dõi, nơi chỉ cần tra một lô: truy vấn lẻ mất ~8ms, rẻ
     * hơn nhiều so với kéo cả bảng như approvalsSince(). Đã đối chiếu 150 lô, khớp
     * hoàn toàn với kết quả tính từ truy vấn hàng loạt.
     *
     * @return array{kcs_date: string, coatp_number: string}|null
     */
    public static function approvalFor(?string $code, ?string $batch): ?array
    {
        $code = trim((string) $code);
        $batch = trim((string) $batch);

        if ($code === '' || $batch === '') {
            return null;
        }

        // TOP 1 + ORDER BY cron = lần duyệt đầu tiên, cùng quy tắc với approvalsSince()
        $sql = "
            SELECT TOP 1 q.cron, q.coano
            FROM FGGRN g
            INNER JOIN fgqc q ON q.grnno = g.GRNNO
            WHERE q.step = N'QA Approval' AND g.MatID = ? AND g.Mfgbatchno = ?
            ORDER BY q.cron
        ";

        $row = DB::connection('mms')->select($sql, [$code, $batch])[0] ?? null;

        return $row === null ? null : [
            'kcs_date' => substr((string) $row->cron, 0, 10),
            'coatp_number' => trim((string) $row->coano),
        ];
    }
}
