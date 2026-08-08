<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Truy vấn version BOM trên MMS.
 *
 * Tách riêng vì cả trang Kiểm tra nguyên liệu lẫn trang Theo dõi hồ sơ KCS đều cần
 * version của một mã, mà các truy vấn này có vài điểm dễ làm sai (xem chú thích
 * từng hàm bên dưới).
 */
class MmsBom
{
    /**
     * Version hiện hành của mọi sản phẩm trên MMS: revision lớn nhất đã duyệt (BOMAp = 1)
     * và BOM còn hiệu lực (BOMSts = 0), trả về dạng [PrdID => Revision].
     *
     * Lấy trọn bảng trong một lượt (khoảng 1.900 dòng) thay vì truyền danh sách mã vào
     * mệnh đề IN, vì driver sqlsrv ép các mã dạng số sang bigint làm hỏng phép so sánh
     * với cột nvarchar.
     *
     * Kết quả cache trong 1 request vì một trang có thể tra hàng trăm mã.
     */
    public static function currentRevisionMap(): Collection
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $sql = "
            SELECT b.PrdID, b.Revno1 AS Revision
            FROM MstBOM b
            INNER JOIN (
                SELECT PrdID, MAX(RevNo) AS MaxRevNo
                FROM MstBOM
                WHERE BOMAp = 1
                GROUP BY PrdID
            ) mr ON mr.PrdID = b.PrdID AND mr.MaxRevNo = b.RevNo
            WHERE b.BOMSts = 0
        ";

        return $cache = collect(DB::connection('mms')->select($sql))->pluck('Revision', 'PrdID');
    }

    /**
     * Toàn bộ lịch sử revision đã duyệt, dạng [PrdID => [ ['rev','label','at'], ... ]]
     * sắp xếp tăng dần theo RevNo. Dùng để tra "version tại một thời điểm trong quá khứ".
     *
     * Cố ý KHÔNG lọc BOMSts = 0: cột này là trạng thái *hiện tại*, các revision cũ đều đã
     * chuyển sang BOMSts = 1 nên lọc theo nó sẽ xoá sạch lịch sử cần tra.
     *
     * Khoảng 18.800 dòng, lấy một lượt rồi tra trong PHP vì mỗi lô có một mốc thời gian
     * riêng, không thể gộp thành một câu SQL chung.
     */
    public static function approvedRevisionHistory(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $sql = "
            SELECT PrdID, RevNo, Revno1, bomApon
            FROM MstBOM
            WHERE BOMAp = 1 AND bomApon IS NOT NULL
        ";

        $history = [];

        foreach (DB::connection('mms')->select($sql) as $row) {
            $history[trim((string) $row->PrdID)][] = [
                'rev' => (float) $row->RevNo,
                'label' => $row->Revno1,
                'at' => strtotime((string) $row->bomApon),
            ];
        }

        foreach ($history as &$rows) {
            usort($rows, fn($a, $b) => $a['rev'] <=> $b['rev']);
        }

        return $cache = $history;
    }

    /**
     * Version của một mã tại thời điểm $at: revision đã duyệt mới nhất tính đến lúc đó.
     * Trả về null nếu mã chưa có BOM nào được duyệt trước thời điểm này.
     */
    public static function revisionAsOf(?string $code, $at): ?string
    {
        if ($code === null || trim($code) === '' || $at === null) {
            return null;
        }

        $history = self::approvedRevisionHistory();
        $code = trim($code);

        if (!isset($history[$code])) {
            return null;
        }

        $timestamp = $at instanceof \DateTimeInterface ? $at->getTimestamp() : strtotime((string) $at);

        if ($timestamp === false) {
            return null;
        }

        $found = null;

        // Danh sách đã sắp tăng dần theo RevNo nên bản cuối cùng thoả điều kiện là bản cần tìm
        foreach ($history[$code] as $row) {
            if ($row['at'] <= $timestamp) {
                $found = $row;
            }
        }

        return $found ? self::formatRevision($found['label']) : null;
    }

    /** Như approvedRevisionHistory() nhưng không làm gãy trang khi MMS không kết nối được */
    public static function isReachable(): bool
    {
        try {
            self::approvedRevisionHistory();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Chuẩn hoá version để hiển thị: MMS trả "12.0" trong khi người dùng quen đọc "12".
     * Giữ nguyên phần thập phân nếu thật sự khác 0 (VD "12.5").
     */
    public static function formatRevision($revision): ?string
    {
        if ($revision === null || trim((string) $revision) === '') {
            return null;
        }

        $revision = trim((string) $revision);

        return str_contains($revision, '.')
            ? rtrim(rtrim($revision, '0'), '.')
            : $revision;
    }
}
