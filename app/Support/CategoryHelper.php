<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

if (! function_exists('pharmacist_options')) {
    /**
     * Danh sách người có thể chọn làm "Dược sĩ phụ trách" của một mã BTP / TP.
     * Chỉ nhân sự QA còn hoạt động và không bị khoá.
     * Kết quả cache trong 1 request vì nhiều modal cùng dùng chung danh sách này.
     */
    function pharmacist_options()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = DB::table('user_management')
            ->select('id', 'fullName', 'deparment')
            ->where('isActive', 1)
            ->where('isLocked', 0)
            ->where('deparment', 'QA')
            ->orderBy('fullName', 'asc')
            ->get();

        return $cache;
    }
}

if (! function_exists('mms_bom_revisions')) {
    /**
     * Ấn bản (Revno1) hiện hành của công thức trên MMS cho từng mã sản phẩm.
     * Ấn bản được lấy theo bản Revno lớn nhất - đúng bản mà màn hình xem công thức đang hiển thị.
     * Mã nào không có trong kết quả nghĩa là MMS chưa có công thức cho mã đó.
     *
     * @param  iterable  $codes  Danh sách mã BTP / TP
     * @return array [mã => ấn bản]
     */
    function mms_bom_revisions($codes)
    {
        $codes = collect($codes)
            ->map(fn($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return [];
        }

        $revisions = [];

        try {
            // SQL Server giới hạn số tham số trên mỗi câu lệnh nên chia nhỏ danh sách mã.
            foreach ($codes->chunk(500) as $chunk) {
                $chunk = $chunk->values()->all();
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));

                $rows = DB::connection('mms')->select(
                    "SELECT b.PrdID AS prd_id, MAX(b.Revno1) AS edition
                       FROM yfBOM_BOMItemHP b
                 INNER JOIN (
                            SELECT PrdID, MAX(Revno) AS max_rev
                              FROM yfBOM_BOMItemHP
                             WHERE PrdID IN ($placeholders)
                          GROUP BY PrdID
                       ) m ON m.PrdID = b.PrdID AND m.max_rev = b.Revno
                   GROUP BY b.PrdID",
                    $chunk
                );

                foreach ($rows as $row) {
                    $revisions[trim((string) $row->prd_id)] = (int) round((float) $row->edition);
                }
            }
        } catch (\Throwable $e) {
            // MMS là hệ thống ngoài, mất kết nối thì danh mục vẫn phải xem được.
            Log::error('Không lấy được ấn bản công thức từ MMS: ' . $e->getMessage());

            return [];
        }

        return $revisions;
    }
}

if (! function_exists('hypothesis_bom_counts')) {
    /**
     * Số dòng công thức giả định (bom_item) đang hiệu lực của từng danh mục.
     *
     * @param  int  $matParType  0 = nguyên liệu (BTP), 1 = bao bì đóng gói (TP)
     * @return array [product_caterogy_id => số dòng]
     */
    function hypothesis_bom_counts($matParType)
    {
        return DB::table('bom_item')
            ->select('product_caterogy_id', DB::raw('count(*) as total'))
            ->where('active', 1)
            ->where('mat_par_type', $matParType)
            ->groupBy('product_caterogy_id')
            ->pluck('total', 'product_caterogy_id')
            ->toArray();
    }
}
