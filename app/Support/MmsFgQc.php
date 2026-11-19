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
     *     approvals: array<string, list<array{kcs_date: string, coatp_number: string, qty: float}>>,
     *     orders: array<string, array{code: string, batch: string}|false>
     * } approvals khoá theo "mã TP|số lô", mỗi phần tử là một phiếu nhập kho (GRN) đã
     *   được duyệt - dùng earliest() hoặc matchSplit() để ra ngày của từng dòng kế hoạch;
     *   orders khoá theo số lệnh đã chuẩn hoá và chỉ dùng để dò lô lệch mã TP (xem
     *   normalizeOrderNumber), giá trị false nghĩa là số lệnh đó ứng với nhiều lô nên
     *   không kết luận được.
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
            SELECT g.GRNNO, g.MatID, g.Mfgbatchno, g.prdorderno, g.totalqty, q.coano, q.cron
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

            self::addReceipt($approvals[$code . '|' . $batch], $row);

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

        $approvals = array_map([self::class, 'sortReceipts'], $approvals);

        return self::$cache[$key] = ['approvals' => $approvals, 'orders' => $orders];
    }

    /**
     * Ngày KCS của lô KHÔNG chia: lần duyệt sớm nhất trong các phiếu nhập kho.
     *
     * Lô được duyệt nhiều lần (tái kiểm / duyệt lại) hay nhập kho nhiều đợt thì vẫn lấy
     * lần duyệt đầu tiên - quy tắc đã đối chiếu khớp 76/76 lô nhập tay.
     *
     * @param  list<array{kcs_date: string, coatp_number: string, qty: float}>  $receipts
     * @return array{kcs_date: string, coatp_number: string}|null
     */
    public static function earliest(array $receipts): ?array
    {
        $first = self::sortReceipts($receipts)[0] ?? null;

        return $first === null ? null : self::approval($first);
    }

    /**
     * Ngày KCS của từng dòng kế hoạch trong một lô CHIA (nhiều dòng kế hoạch cùng mã TP
     * + số lô, có phần "chỉ đóng gói").
     *
     * MMS không ghi phiếu nhập kho thuộc phần nào của lô: lô 640926 có hai phiếu cùng số
     * lệnh 3220A/6P2S, cùng số COATP, chỉ khác số lượng (152.400 / 547.700) và ngày duyệt
     * (05/11 / 06/11). Trước đây cả hai dòng kế hoạch đều nhận lần duyệt sớm nhất, nên
     * phần nhận hồ sơ ngày 06/11 lại có ngày KCS 05/11. Vì vậy ghép từng phiếu với dòng kế
     * hoạch có số lượng gần nhất (plan_master.number_parkaging), mỗi phiếu dùng cho đúng
     * một dòng. Dòng không ghép được phiếu nào nghĩa là phần đó chưa được KCS: để trống
     * thay vì mượn ngày của phần kia.
     *
     * Phiếu cũ trên MMS không có số lượng (totalqty = 0) thì không ghép được, quay về quy
     * tắc lần duyệt sớm nhất cho cả lô như trước.
     *
     * @param  list<array{kcs_date: string, coatp_number: string, qty: float}>  $receipts
     * @param  array<int, float>  $plans  [plan_master_id => số lượng của phần lô đó]
     * @return array<int, array{kcs_date: string, coatp_number: string}|null>
     */
    public static function matchSplit(array $receipts, array $plans): array
    {
        $receipts = self::sortReceipts($receipts);

        if ($receipts === []) {
            return array_fill_keys(array_keys($plans), null);
        }

        $paired = self::pairByQty($receipts, $plans);

        if ($paired === null) {
            return array_fill_keys(array_keys($plans), self::earliest($receipts));
        }

        return array_map(fn($receipt) => $receipt === null ? null : self::approval($receipt), $paired);
    }

    /**
     * Ghép từng phiếu nhập kho với dòng kế hoạch có số lượng gần nhất, mỗi phiếu dùng cho
     * đúng một dòng (quy tắc lô chia, xem matchSplit()). Dùng chung cho Ngày KCS và Ngày
     * nhập kho để hai cột luôn chỉ về cùng một phiếu.
     *
     * @param  list<array{qty: float}>  $receipts  đã xếp theo thời gian, phiếu sớm nhất trước
     * @param  array<int, float>  $plans  [plan_master_id => số lượng của phần lô đó]
     * @return array<int, array|null>|null  [plan_master_id => phiếu ghép được hoặc null];
     *                                      null khi thiếu số lượng (phiếu cũ totalqty = 0)
     *                                      nên không ghép được
     */
    private static function pairByQty(array $receipts, array $plans): ?array
    {
        foreach ($receipts as $receipt) {
            if ($receipt['qty'] <= 0) {
                return null;
            }
        }

        foreach ($plans as $qty) {
            if ((float) $qty <= 0) {
                return null;
            }
        }

        $result = array_fill_keys(array_keys($plans), null);
        $pairs = [];

        foreach ($plans as $planId => $planQty) {
            foreach ($receipts as $index => $receipt) {
                $pairs[] = [abs((float) $planQty - $receipt['qty']), $index, $planId];
            }
        }

        // Lệch số lượng ít nhất ghép trước; bằng nhau thì phiếu sớm hơn (index nhỏ)
        // đi với dòng kế hoạch tạo trước (id nhỏ) để kết quả luôn ổn định.
        usort($pairs, fn($a, $b) => $a <=> $b);

        $usedReceipts = [];

        foreach ($pairs as [, $index, $planId]) {
            if ($result[$planId] !== null || isset($usedReceipts[$index])) {
                continue;
            }

            $result[$planId] = $receipts[$index];
            $usedReceipts[$index] = true;
        }

        return $result;
    }

    /** Kết quả đã tra của stockInsSince() theo từng mốc thời gian, cache trong 1 request */
    private static array $stockInCache = [];

    /**
     * Các phiếu nhập kho thành phẩm (FGGRN) tạo từ $since trở đi, KHÔNG cần đã được KCS
     * duyệt: lô đã nhập kho nhưng đang chờ KCS vẫn phải thấy ngày nhập kho.
     *
     * Ngày nhập kho = FGGRN.CRON (lúc kho tạo phiếu, có đủ 100% phiếu). Không dùng GRNAP:
     * phần giờ lệch 12 tiếng (CRON 16:07 -> GRNAP 04:07), và thiếu ở vài phiếu.
     *
     * @return array<string, list<array{grn: string, date: string, qty: float}>>
     *         khoá "mã TP|số lô", mỗi lô xếp theo ngày nhập, phiếu đầu tiên trước
     */
    public static function stockInsSince(DateTimeInterface $since): array
    {
        $key = $since->format('Y-m-d');

        if (isset(self::$stockInCache[$key])) {
            return self::$stockInCache[$key];
        }

        $sql = "
            SELECT GRNNO, MatID, Mfgbatchno, totalqty, CRON
            FROM FGGRN
            WHERE CRON >= ?
        ";

        $lots = [];

        foreach (DB::connection('mms')->select($sql, [$key]) as $row) {
            $code = trim((string) $row->MatID);
            $batch = trim((string) $row->Mfgbatchno);

            if ($code === '' || $batch === '' || !$row->CRON) {
                continue;
            }

            $lots[$code . '|' . $batch][] = [
                'grn' => trim((string) $row->GRNNO),
                'date' => substr((string) $row->CRON, 0, 10),
                'qty' => (float) $row->totalqty,
            ];
        }

        foreach ($lots as &$receipts) {
            usort($receipts, fn($a, $b) => [$a['date'], $a['grn']] <=> [$b['date'], $b['grn']]);
        }
        unset($receipts);

        return self::$stockInCache[$key] = $lots;
    }

    /**
     * Ngày nhập kho của một dòng kế hoạch từ các phiếu nhập kho của lô.
     *
     * Lô không chia: phiếu nhập đầu tiên. Lô chia: phiếu có số lượng gần nhất với phần
     * lô đó (cùng quy tắc với Ngày KCS); phần chưa ghép được phiếu nào là chưa nhập kho.
     *
     * @param  list<array{grn: string, date: string, qty: float}>  $receipts  từ stockInsSince()
     * @param  array<int, float>|null  $plans  anh em của lô chia (splitFamilies), null nếu không chia
     * @return array{date: string, grn: string, qty: float, count: int}|null
     */
    public static function stockInFor(array $receipts, ?array $plans, int $planId): ?array
    {
        if ($receipts === []) {
            return null;
        }

        $receipt = $receipts[0];

        if ($plans) {
            $paired = self::pairByQty($receipts, $plans);

            if ($paired !== null) {
                $receipt = $paired[$planId] ?? null;
            }
        }

        if ($receipt === null) {
            return null;
        }

        return [
            'date' => $receipt['date'],
            'grn' => $receipt['grn'],
            'qty' => $receipt['qty'],
            'count' => count($receipts),
        ];
    }

    /** Gộp một dòng duyệt vào danh sách phiếu nhập kho, mỗi phiếu giữ lần duyệt sớm nhất */
    private static function addReceipt(?array &$receipts, object $row): void
    {
        $grn = trim((string) $row->GRNNO);
        $date = substr((string) $row->cron, 0, 10);

        if (isset($receipts[$grn]) && $receipts[$grn]['kcs_date'] <= $date) {
            return;
        }

        $receipts[$grn] = [
            'kcs_date' => $date,
            'coatp_number' => trim((string) $row->coano),
            // totalqty luôn có giá trị; Atotalqty bằng 0 ở phần lớn phiếu cũ
            'qty' => (float) $row->totalqty,
        ];
    }

    /** Danh sách phiếu xếp theo ngày duyệt, phiếu đầu tiên là lần KCS sớm nhất */
    private static function sortReceipts(array $receipts): array
    {
        $receipts = array_values($receipts);
        usort($receipts, fn($a, $b) => $a['kcs_date'] <=> $b['kcs_date']);

        return $receipts;
    }

    /** @return array{kcs_date: string, coatp_number: string} */
    private static function approval(array $receipt): array
    {
        return ['kcs_date' => $receipt['kcs_date'], 'coatp_number' => $receipt['coatp_number']];
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
     * Các phiếu nhập kho đã được KCS duyệt của đúng một lô (mã TP + số lô).
     *
     * Dùng lúc lưu một dòng theo dõi, nơi chỉ cần tra một lô: truy vấn lẻ mất ~8ms, rẻ
     * hơn nhiều so với kéo cả bảng như approvalsSince(). Kết quả cùng dạng với từng phần
     * tử approvals của approvalsSince(), đưa qua earliest() / matchSplit() như nhau.
     *
     * @return list<array{kcs_date: string, coatp_number: string, qty: float}>
     */
    public static function receiptsFor(?string $code, ?string $batch): array
    {
        $code = trim((string) $code);
        $batch = trim((string) $batch);

        if ($code === '' || $batch === '') {
            return [];
        }

        $sql = "
            SELECT g.GRNNO, g.totalqty, q.cron, q.coano
            FROM FGGRN g
            INNER JOIN fgqc q ON q.grnno = g.GRNNO
            WHERE q.step = N'QA Approval' AND g.MatID = ? AND g.Mfgbatchno = ?
        ";

        $receipts = null;

        foreach (DB::connection('mms')->select($sql, [$code, $batch]) as $row) {
            self::addReceipt($receipts, $row);
        }

        return self::sortReceipts($receipts ?? []);
    }
}
