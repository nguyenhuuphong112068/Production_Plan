<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaterialCheckController extends Controller
{
        /**
         * Tra cứu trên MMS các công thức hiện hành có sử dụng một mã NL/BB.
         * Công thức hiện hành = revision lớn nhất đã duyệt (BOMAp = 1) của từng sản phẩm.
         */
        public function index(Request $request)
        {
                session()->put(['title' => 'KIỂM TRA NL/BB']);

                $matId = trim((string) $request->input('mat_id', ''));

                $material = null;
                $datas = collect();
                $error = null;

                if ($matId !== '') {
                        try {
                                $material = $this->getMaterial($matId);
                                $datas = $this->getCurrentBoms($matId);
                        } catch (\Throwable $e) {
                                $error = 'Không lấy được dữ liệu từ MMS: ' . $e->getMessage();
                        }
                }

                return view('pages.category.material_check.list', [
                        'mat_id' => $matId,
                        'material' => $material,
                        'datas' => $datas,
                        'error' => $error,
                        'searched' => $matId !== '',
                ]);
        }

        /** Thông tin mã NL/BB trên MMS (để báo cho người dùng biết mã có tồn tại hay không) */
        private function getMaterial(string $matId)
        {
                return DB::connection('mms')
                        ->table('mstmaterial')
                        ->selectRaw("MatID, MatNM, MatUOM,
                                IIF(MatSTS = 0, 'Active', 'Not Active') AS MatSTS,
                                IIF(MatAP = 1, 'Approved', 'Not Approved') AS MatAppSTS")
                        ->where('MatID', $matId)
                        ->first();
        }

        /**
         * Danh sách BOM (max revision đã approved, chỉ lấy BOM còn hiệu lực - BOMSts = 0)
         * của mọi sản phẩm có dùng mã NL/BB này,
         * kèm mã BTP - mã TP tương ứng theo danh mục thành phẩm của PMS.
         */
        private function getCurrentBoms(string $matId): Collection
        {
                $sql = "
                        WITH maxrev AS (
                                SELECT PrdID, MAX(RevNo) AS MaxRevNo
                                FROM MstBOM
                                WHERE BOMAp = 1
                                GROUP BY PrdID
                        )
                        SELECT DISTINCT
                                m.MatID,
                                m.MatNM,
                                m.MatUOM,
                                b.PrdID,
                                p.MatNM                                             AS PrdName,
                                b.Revno1                                            AS BOMRevsion,
                                b.BatchQty,
                                b.BatchqtyUOM,
                                IIF(b.BOMSts = 0, 'BOM Active', 'BOM Not Active')    AS BOMSTS
                        FROM maxrev mr
                        INNER JOIN MstBOM     b  ON b.PrdID  = mr.PrdID AND b.RevNo  = mr.MaxRevNo
                        INNER JOIN MstBOMItem bi ON bi.PrdID = b.PrdID  AND bi.RevNo = b.RevNo
                        INNER JOIN mstmaterial m ON m.MatID  = bi.MatID
                        LEFT  JOIN mstmaterial p ON p.MatID  = b.PrdID
                        WHERE bi.MatID = ?
                          AND b.BOMSts = 0
                        ORDER BY b.PrdID
                ";

                $boms = collect(DB::connection('mms')->select($sql, [$matId]));

                if ($boms->isEmpty()) {
                        return $boms;
                }

                return $this->attachProductCodes($boms);
        }

        /**
         * Gắn cột Mã BTP / Mã TP cho từng BOM.
         * PrdID của BOM có thể là mã BTP hoặc mã TP, lấy mã còn lại từ danh mục thành phẩm.
         * Trường hợp chưa có trong danh mục thì suy ra theo quy ước mã của MMS (IS/IL/IE/IH... là BTP).
         */
        private function attachProductCodes(Collection $boms): Collection
        {
                $prdIds = $boms->pluck('PrdID')->filter()->unique()->values();

                $links = DB::table('finished_product_category')
                        ->select('finished_product_code', 'intermediate_code')
                        ->where('cancel', 0)
                        ->where(function ($query) use ($prdIds) {
                                $query->whereIn('finished_product_code', $prdIds)
                                        ->orWhereIn('intermediate_code', $prdIds);
                        })
                        ->get();

                $tpOfBtp = $links->groupBy('intermediate_code')
                        ->map(fn($rows) => $rows->pluck('finished_product_code')->unique()->sort()->values()->all());

                $btpOfTp = $links->groupBy('finished_product_code')
                        ->map(fn($rows) => $rows->pluck('intermediate_code')->unique()->sort()->values()->all());

                // Chỉ hiển thị những mã còn hiệu lực trong danh mục BTP / TP của PMS
                $activeBtp = DB::table('intermediate_category')
                        ->where('active', 1)
                        ->where('cancel', 0)
                        ->whereIn('intermediate_code', $links->pluck('intermediate_code')->merge($prdIds)->unique()->values())
                        ->pluck('intermediate_code')
                        ->flip();

                $tpRows = DB::table('finished_product_category')
                        ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                        ->select('finished_product_category.finished_product_code', 'market.code as market')
                        ->where('finished_product_category.active', 1)
                        ->where('finished_product_category.cancel', 0)
                        ->whereIn('finished_product_category.finished_product_code', $links->pluck('finished_product_code')->merge($prdIds)->unique()->values())
                        ->get();

                $activeTp = $tpRows->pluck('finished_product_code')->flip();

                // Thị trường của từng mã TP (một mã có thể thuộc nhiều thị trường)
                $marketOfTp = $tpRows->groupBy('finished_product_code')
                        ->map(fn($rows) => $rows->pluck('market')->filter()->unique()->sort()->implode(', '));

                foreach ($boms as $bom) {
                        $prdId = $bom->PrdID;

                        if (isset($tpOfBtp[$prdId])) {
                                $btpCodes = [$prdId];
                                $tpCodes = $tpOfBtp[$prdId];
                        } elseif (isset($btpOfTp[$prdId])) {
                                $btpCodes = $btpOfTp[$prdId];
                                $tpCodes = [$prdId];
                        } elseif (str_starts_with(strtoupper((string) $prdId), 'I')) {
                                $btpCodes = [$prdId];
                                $tpCodes = [];
                        } else {
                                $btpCodes = [];
                                $tpCodes = [$prdId];
                        }

                        $bom->btp_codes = array_values(array_filter($btpCodes, fn($code) => $activeBtp->has($code)));
                        $bom->tp_codes = array_values(array_filter($tpCodes, fn($code) => $activeTp->has($code)));
                }

                // Bỏ các BOM không còn mã BTP/TP nào đang hiệu lực trong danh mục của PMS
                $boms = $boms->filter(fn($bom) => $bom->btp_codes || $bom->tp_codes)->values();

                return $this->attachRevisions($boms, $marketOfTp);
        }

        /**
         * Gắn version (BOM revision) hiện hành của chính từng mã BTP / TP được hiển thị,
         * để mỗi mã mang badge version của riêng nó thay vì dùng chung revision của BOM đang xét.
         * Riêng mã TP kèm thêm thị trường lấy từ danh mục thành phẩm.
         */
        private function attachRevisions(Collection $boms, Collection $marketOfTp): Collection
        {
                $revisions = $this->currentRevisionMap();

                $withRevision = fn(array $codes) => array_map(fn($code) => [
                        'code' => $code,
                        'revision' => $revisions[$code] ?? null,
                ], $codes);

                $withMarket = fn(array $codes) => array_map(fn($code) => [
                        'code' => $code,
                        'revision' => $revisions[$code] ?? null,
                        'market' => $marketOfTp[$code] ?? '',
                ], $codes);

                foreach ($boms as $bom) {
                        $bom->btp_items = $withRevision($bom->btp_codes);
                        $bom->tp_items = $withMarket($bom->tp_codes);
                }

                return $boms;
        }

        /**
         * Version hiện hành của mọi sản phẩm trên MMS: revision lớn nhất đã duyệt và còn hiệu lực.
         * Lấy trọn bảng trong một lượt (khoảng 1.900 dòng) thay vì truyền danh sách mã vào mệnh đề IN,
         * vì driver sqlsrv ép các mã dạng số sang bigint làm hỏng phép so sánh với cột nvarchar.
         */
        private function currentRevisionMap(): Collection
        {
                return \App\Support\MmsBom::currentRevisionMap();
        }
}
