<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use App\Support\MmsFgQc;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Theo dõi xuất kho EU: các lô sản xuất có thị trường Châu Âu (market.is_eu = 1), cùng cột
 * thông tin sản phẩm như trang Theo Dõi Hồ Sơ KCS, kèm Ngày xuất kho và Số lượng xuất
 * kho lấy từ MMS. Trang chỉ xem, không ghi gì vào PMS lẫn MMS.
 */
class CocTrackingController extends Controller
{
    public function index(Request $request)
    {
        $department = $request->query('department', session('user')['production_code'] ?? '');
        $fromMonth = $request->query('from_month') ?: Carbon::now()->format('Y-m');
        $toMonth = $request->query('to_month') ?: Carbon::now()->format('Y-m');
        $keyword = trim((string) $request->query('keyword', ''));

        if ($fromMonth > $toMonth) {
            [$fromMonth, $toMonth] = [$toMonth, $fromMonth];
        }

        // Khoảng Ngày xuất kho: ô lọc gửi dd/mm/yyyy, đổi về 'Y-m-d' ('' nếu trống / sai)
        $outFrom = KcsTrackingController::toIsoDate($request->query('out_from', '')) ?? '';
        $outTo = KcsTrackingController::toIsoDate($request->query('out_to', '')) ?? '';

        if ($outFrom !== '' && $outTo !== '' && $outFrom > $outTo) {
            [$outFrom, $outTo] = [$outTo, $outFrom];
        }

        $hasOut = $outFrom !== '' || $outTo !== '';

        // Ngày xuất kho là một trục thời gian riêng: lô kế hoạch tháng 6 có thể xuất kho
        // tháng 11. Nên khi lọc theo ngày xuất kho thì bỏ khoảng tháng kế hoạch (giống Tháng
        // KCS ở trang KCS), chỉ giữ cửa sổ lùi 12 tháng để không quét cả bảng.
        $planFrom = $fromMonth;
        $planTo = $toMonth;

        if ($hasOut) {
            // Chỉ nhập "từ ngày" thì lọc mở tới hôm nay
            $end = $outTo !== '' ? $outTo : max($outFrom, Carbon::now()->toDateString());
            $planFrom = Carbon::parse($outFrom ?: $outTo)->subMonths(12)->format('Y-m');
            $planTo = Carbon::parse($end)->addMonth()->format('Y-m');
        }

        $datas = $this->batches($department, $planFrom, $planTo, $keyword);
        $mms = $this->mmsData($datas, $planFrom);
        $stockOuts = $mms['stock_outs'];

        if ($hasOut) {
            // Giữ lô có ít nhất một phiếu xuất kho trong khoảng đang lọc
            $datas = $datas->filter(function ($data) use ($stockOuts, $outFrom, $outTo) {
                foreach ($stockOuts->get($data->id)['issues'] ?? [] as $issue) {
                    if (($outFrom === '' || $issue['date'] >= $outFrom) && ($outTo === '' || $issue['date'] <= $outTo)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        // Version BOM chỉ đọc lại bản đã chốt bởi trang Theo Dõi Hồ Sơ KCS, không tự chốt ở đây
        $bomVersions = DB::table('plan_master_bom_version')
            ->whereIn('plan_master_id', $datas->pluck('id'))
            ->get()
            ->keyBy('plan_master_id');

        session()->put(['title' => 'THEO DÕI XUẤT KHO EU']);

        return view('pages.plan.coc_tracking.list', [
            'datas' => $datas,
            'bomVersions' => $bomVersions,
            'stockOuts' => $stockOuts,
            'stockIns' => $mms['stock_ins'],
            'kcs' => $mms['kcs'],
            'cocRecords' => DB::table('plan_master_coc')
                ->whereIn('plan_master_id', $datas->pluck('id'))
                ->get()
                ->keyBy('plan_master_id'),
            'canUpdate' => user_has_permission(session('user')['userId'], 'coc_tracking_update', 'boolean'),
            'mmsAvailable' => $mms['available'],
            'departments' => KcsTrackingController::DEPARTMENTS,
            'department' => $department,
            'fromMonth' => $fromMonth,
            'toMonth' => $toMonth,
            'outFrom' => $outFrom,
            'outTo' => $outTo,
            'keyword' => $keyword,
        ]);
    }

    /**
     * Lưu Ngày lập phiếu COC của một lô (ô nhập trên lưới, tự lưu khi đổi giá trị).
     * Mỗi lần đổi ghi một dòng vết vào plan_master_coc_history (giá trị cũ / mới, người khai
     * báo, thời điểm khai báo).
     *
     * Chỉ nhận ngày khi lô ĐÃ có phiếu xuất kho (ngày + số lượng) trên MMS: ô trên lưới đã khoá
     * với lô chưa xuất kho, kiểm tra lại ở đây để request sửa tay cũng không vượt qua được.
     * Xoá trắng ngày thì luôn cho phép.
     */
    public function save(Request $request)
    {
        if (!user_has_permission(session('user')['userId'], 'coc_tracking_update', 'boolean')) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền cập nhật Theo Dõi Xuất Kho EU'], 403);
        }

        $request->validate(['plan_master_id' => 'required|integer|exists:plan_master,id']);

        // Lưới gửi dd/mm/yyyy; không dùng rule 'date' vì nó hiểu 05/11/2026 kiểu Mỹ
        $raw = trim((string) $request->input('coc_date', ''));
        $cocDate = $raw === '' ? null : KcsTrackingController::toIsoDate($raw);

        if ($raw !== '' && $cocDate === null) {
            return response()->json([
                'success' => false,
                'message' => 'Ngày "' . $raw . '" không hợp lệ, nhập theo dạng dd/mm/yyyy',
            ], 422);
        }

        if ($cocDate !== null) {
            $stockOut = $this->stockOutOf((int) $request->plan_master_id);

            if ($stockOut === false) {
                return response()->json(['success' => false, 'message' => 'Không kết nối được MMS để kiểm tra xuất kho, thử lại sau'], 503);
            }

            if (!$stockOut || !$stockOut['first_date'] || $stockOut['qty'] <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lô chưa có ngày và số lượng xuất kho trên MMS nên chưa nhập được Ngày Lập Phiếu COC',
                ], 422);
            }
        }

        $user = session('user')['fullName'] ?? (session('user')['userName'] ?? 'System');
        $now = Carbon::now();

        DB::transaction(function () use ($request, $cocDate, $user, $now) {
            $planId = (int) $request->plan_master_id;
            $old = DB::table('plan_master_coc')->where('plan_master_id', $planId)->lockForUpdate()->first();
            $oldDate = $old?->coc_date ? substr((string) $old->coc_date, 0, 10) : null;

            if ($oldDate === $cocDate) {
                return;
            }

            if ($old) {
                DB::table('plan_master_coc')->where('id', $old->id)
                    ->update(['coc_date' => $cocDate, 'updated_by' => $user, 'updated_at' => $now]);
            } else {
                DB::table('plan_master_coc')->insert([
                    'plan_master_id' => $planId,
                    'coc_date' => $cocDate,
                    'updated_by' => $user,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('plan_master_coc_history')->insert([
                'plan_master_id' => $planId,
                'field' => 'coc_date',
                'old_value' => $oldDate,
                'new_value' => $cocDate,
                'changed_by' => $user,
                'created_at' => $now,
            ]);
        });

        return response()->json([
            'success' => true,
            'declared_by' => $user,
            'declared_at' => $now->format('d/m/Y H:i'),
        ]);
    }

    /** Lịch sử khai báo Ngày Lập Phiếu COC của một lô, hiển thị trong modal */
    public function history(Request $request)
    {
        $request->validate(['plan_master_id' => 'required|integer']);

        $histories = DB::table('plan_master_coc_history')
            ->where('plan_master_id', $request->plan_master_id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'html' => view('pages.plan.coc_tracking.history', compact('histories'))->render(),
        ]);
    }

    /**
     * Phiếu xuất kho của đúng một lô EU (dùng khi lưu), cùng cách tính với lưới.
     *
     * @return array|null|false  null = lô chưa xuất kho / không phải lô EU; false = lỗi MMS
     */
    private function stockOutOf(int $planMasterId)
    {
        $lots = $this->baseQuery()->where('plan_master.id', $planMasterId)->get();

        if ($lots->isEmpty()) {
            return null;
        }

        $lot = $lots->first();
        $planMonth = sprintf('%04d-%02d', $lot->plan_year, $lot->plan_month);
        $mms = $this->mmsData($lots, $planMonth);

        if (!$mms['available']) {
            return false;
        }

        return $mms['stock_outs']->get($planMasterId);
    }

    /**
     * Nguyên liệu và bao bì đã xuất cho một lô, hiển thị trong modal.
     *
     * Nguồn là view MMS yfIssueHistory (phiếu xuất kho NL/BB, loại "7. Against Prd Order"),
     * khoá prdid + batchno:
     *  - Nguyên liệu: prdid = mã BTP (công đoạn Formulation, Coating...)
     *  - Bao bì: prdid = mã TP (Primary / Secondary packaging)
     * Lô NL/BB của nhà sản xuất (GRN.Mfgbatchno) nối qua GRNNO, đơn vị lấy từ mstmaterial.
     * Chỉ SELECT trên MMS.
     */
    public function materials(Request $request)
    {
        $request->validate(['plan_master_id' => 'required|integer']);

        $lot = DB::table('plan_master as pm')
            ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('product_name as pn', 'fpc.product_name_id', '=', 'pn.id')
            ->where('pm.id', $request->plan_master_id)
            ->select('pm.id', 'pm.batch', 'pm.actual_batch', 'fpc.intermediate_code',
                'fpc.finished_product_code', 'pn.name as product_name')
            ->first();

        if (!$lot) {
            return response()->json(['message' => 'Không tìm thấy lô'], 404);
        }

        $batch = trim((string) ($lot->actual_batch ?: $lot->batch));

        try {
            $materials = $this->issuedMaterials(trim((string) $lot->intermediate_code), $batch);
            $packagings = $this->issuedMaterials(trim((string) $lot->finished_product_code), $batch);
        } catch (\Throwable) {
            return response()->json(['message' => 'Không kết nối được MMS'], 503);
        }

        $html = view('pages.plan.coc_tracking.materials', compact('lot', 'batch', 'materials', 'packagings'))->render();

        return response()->json([
            'html' => $html,
            'counts' => ['materials' => count($materials), 'packagings' => count($packagings)],
        ]);
    }

    /**
     * Các NL/BB đã xuất cho một mã (BTP hoặc TP) + số lô, gộp theo công đoạn + mã + lô
     * NL/BB (view gốc có một dòng cho mỗi bao / mỗi lần cân).
     *
     * @return list<array{stage: string, code: string, name: string, lot: string, mfg_batch: string,
     *                    coa: string, qty: float, uom: string, first_date: string, issues: list<string>}>
     */
    private function issuedMaterials(string $productCode, string $batch): array
    {
        if ($productCode === '' || $batch === '') {
            return [];
        }

        $sql = "
            SELECT h.QCSTS, h.matid, h.MatNM, h.lotno, h.coano, h.totalqty, h.cron, h.Issueno,
                   g.Mfgbatchno, m.MatUOM
            FROM yfIssueHistory h
            LEFT JOIN GRN g ON g.GRNNO = h.GRNNO
            LEFT JOIN mstmaterial m ON m.MatID = h.matid
            WHERE h.prdid = ? AND h.batchno = ? AND h.issuetype LIKE N'7.%'
        ";

        $rows = [];

        foreach (DB::connection('mms')->select($sql, [$productCode, $batch]) as $row) {
            $stage = trim(preg_replace('/^\s*\d+\s*-\s*/', '', (string) $row->QCSTS));
            $code = trim((string) $row->matid);
            $lotNo = trim((string) $row->lotno);
            $key = $stage . '|' . $code . '|' . $lotNo;
            $date = substr((string) $row->cron, 0, 10);
            $issue = trim((string) $row->Issueno);

            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'stage_order' => (int) $row->QCSTS,
                    'stage' => $stage,
                    'code' => $code,
                    'name' => trim((string) $row->MatNM),
                    'lot' => $lotNo,
                    'mfg_batch' => trim((string) $row->Mfgbatchno),
                    'coa' => trim((string) $row->coano),
                    'qty' => 0.0,
                    'uom' => trim((string) $row->MatUOM),
                    'first_date' => $date,
                    'issues' => [],
                ];
            }

            $rows[$key]['qty'] += (float) $row->totalqty;
            $rows[$key]['first_date'] = min($rows[$key]['first_date'], $date);
            $rows[$key]['issues'][$issue] = $issue;
        }

        $rows = array_values($rows);
        usort($rows, fn($a, $b) => [$a['stage_order'], $a['code'], $a['first_date'], $a['lot']]
            <=> [$b['stage_order'], $b['code'], $b['first_date'], $b['lot']]);

        return array_map(function ($row) {
            $row['issues'] = array_values($row['issues']);

            return $row;
        }, $rows);
    }

    /**
     * Dữ liệu MMS của từng lô (khoá plan_master_id):
     *  - stock_outs: phiếu xuất kho ['first_date', 'last_date', 'qty', 'issues']
     *  - stock_ins: phiếu nhập kho ['date', 'qty', 'uom', 'mfg_date', 'exp_date', 'receipts'] (FGGRN, ngày =
     *    CRON, SL = totalqty; ngày sản xuất / hạn dùng lấy ở phiếu nhập đầu tiên của lô)
     *  - kcs: ['kcs_date', 'coatp_number'] - ngày KCS / số CoA (fgqc bước QA Approval), cùng
     *    quy tắc với trang Theo Dõi Hồ Sơ KCS: lô thường lấy lần duyệt sớm nhất, lô chia ghép
     *    phiếu theo số lượng. MMS chưa có thì dùng giá trị đã lưu ở plan_master_KCS.
     *
     * Đi vòng qua phiếu nhập kho: lô -> các GRN nhập kho (FGGRN, khoá mã TP + số lô thực
     * tế, như cột Ngày Nhập Kho của trang KCS) -> các phiếu xuất của những GRN đó.
     * Lô chia (có phần "chỉ đóng gói") chỉ lấy phiếu xuất của GRN đã ghép đúng phần lô đó
     * theo số lượng (MmsFgQc::stockInFor), để hai phần không cùng nhận một lượng xuất.
     *
     * @return array{stock_outs: \Illuminate\Support\Collection, stock_ins: \Illuminate\Support\Collection,
     *               kcs: \Illuminate\Support\Collection, available: bool}
     */
    private function mmsData($datas, string $fromMonth): array
    {
        $empty = fn(bool $available) => [
            'stock_outs' => collect(), 'stock_ins' => collect(), 'kcs' => $this->savedKcs($datas, []),
            'available' => $available,
        ];

        if ($datas->isEmpty()) {
            return $empty(true);
        }

        try {
            // Lùi 4 tháng như trang KCS (3 tháng KCS sớm + 1 tháng nhập kho trước KCS)
            $since = Carbon::parse($fromMonth . '-01')->subMonths(4);
            $stockIns = MmsFgQc::stockInsSince($since);
            $approvals = MmsFgQc::approvalsSince($since->copy()->addMonth())['approvals'];
        } catch (\Throwable) {
            return $empty(false);
        }

        $families = KcsTrackingController::splitFamilies($datas);
        $grnsByPlan = [];
        $stockInResult = [];
        $kcsResult = [];

        foreach ($datas as $data) {
            $batch = trim((string) ($data->actual_batch ?: $data->batch));
            $code = trim((string) $data->finished_product_code);

            if ($batch === '' || $code === '') {
                continue;
            }

            $approved = $approvals[$code . '|' . $batch] ?? [];

            if ($approved) {
                $hit = isset($families[$data->id])
                    ? (MmsFgQc::matchSplit($approved, $families[$data->id])[$data->id] ?? null)
                    : MmsFgQc::earliest($approved);

                if ($hit) {
                    $kcsResult[$data->id] = $hit;
                }
            }

            $receipts = $stockIns[$code . '|' . $batch] ?? [];

            if ($receipts === []) {
                continue;
            }

            if (isset($families[$data->id])) {
                // Lô chia: chỉ phiếu nhập đã ghép đúng phần lô này theo số lượng
                $stock = MmsFgQc::stockInFor($receipts, $families[$data->id], $data->id);
                $own = $stock ? array_values(array_filter($receipts, fn($r) => $r['grn'] === $stock['grn'])) : [];
            } else {
                $own = $receipts;
            }

            $grnsByPlan[$data->id] = array_column($own, 'grn');

            if ($own) {
                // Ngày nhập kho = phiếu đầu tiên (như trang KCS), số lượng = tổng các phiếu của lô
                $stockInResult[$data->id] = [
                    'date' => $own[0]['date'],
                    'qty' => array_sum(array_column($own, 'qty')),
                    'uom' => $own[0]['uom'],
                    'mfg_date' => $own[0]['mfg_date'],
                    'exp_date' => $own[0]['exp_date'],
                    'receipts' => $own,
                ];
            }
        }

        try {
            $byGrn = MmsFgQc::stockOutsForGrns(array_merge([], ...array_values($grnsByPlan)));
        } catch (\Throwable) {
            return $empty(false);
        }

        $result = [];

        foreach ($grnsByPlan as $planId => $grns) {
            // Một phiếu xuất có thể lấy hàng từ nhiều GRN của cùng lô: gộp theo số phiếu xuất
            $issues = [];

            foreach ($grns as $grn) {
                foreach ($byGrn[$grn] ?? [] as $no => $issue) {
                    if (isset($issues[$no])) {
                        $issues[$no]['qty'] += $issue['qty'];
                    } else {
                        $issues[$no] = $issue;
                    }
                }
            }

            if ($issues === []) {
                continue;
            }

            $issues = array_values($issues);
            usort($issues, fn($a, $b) => [$a['date'], $a['issue']] <=> [$b['date'], $b['issue']]);

            $result[$planId] = [
                'first_date' => $issues[0]['date'],
                'last_date' => end($issues)['date'],
                'qty' => array_sum(array_column($issues, 'qty')),
                'issues' => $issues,
            ];
        }

        return [
            'stock_outs' => collect($result),
            'stock_ins' => collect($stockInResult),
            'kcs' => $this->savedKcs($datas, $kcsResult),
            'available' => true,
        ];
    }

    /**
     * Ghép Ngày KCS / số CoA từ MMS với giá trị đã lưu ở plan_master_KCS (dùng khi MMS chưa
     * có, VD lô lệch mã TP được nhập tay trên trang KCS). MMS luôn được ưu tiên.
     */
    private function savedKcs($datas, array $fromMms)
    {
        $saved = DB::table('plan_master_KCS')
            ->whereIn('plan_master_id', $datas->pluck('id'))
            ->get(['plan_master_id', 'kcs_date', 'coatp_number'])
            ->keyBy('plan_master_id');

        $result = [];

        foreach ($datas as $data) {
            $mms = $fromMms[$data->id] ?? [];
            $row = $saved->get($data->id);
            $kcsDate = ($mms['kcs_date'] ?? '') ?: substr((string) ($row->kcs_date ?? ''), 0, 10);
            $coa = ($mms['coatp_number'] ?? '') ?: (string) ($row->coatp_number ?? '');

            if ($kcsDate !== '' || $coa !== '') {
                $result[$data->id] = ['kcs_date' => $kcsDate, 'coatp_number' => $coa];
            }
        }

        return collect($result);
    }

    /** Lô sản xuất thị trường Châu Âu trong khoảng tháng kế hoạch, cùng bộ lọc nền với trang KCS */
    private function batches(string $department, string $fromMonth, string $toMonth, string $keyword)
    {
        $query = $this->baseQuery()
            ->whereRaw("STR_TO_DATE(CONCAT('01-', pl.month, '-', pl.year), '%d-%c-%Y') >= ?", [$fromMonth . '-01'])
            ->whereRaw("STR_TO_DATE(CONCAT('01-', pl.month, '-', pl.year), '%d-%c-%Y') <= ?", [$toMonth . '-01']);

        if ($department) {
            $query->where('plan_master.deparment_code', $department);
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('finished_product_category.finished_product_code', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('finished_product_category.intermediate_code', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('product_name.name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('plan_master.batch', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('plan_master.actual_batch', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('market.name', 'LIKE', '%' . $keyword . '%');
            });
        }

        return $query->orderBy('pl.year', 'desc')
            ->orderBy('pl.month', 'desc')
            ->orderBy('plan_master.batch')
            ->get();
    }

    /** Câu truy vấn nền: lô EU đang hiệu lực, cùng bộ lọc nền với trang KCS */
    private function baseQuery()
    {
        return DB::table('plan_master')
            ->join('plan_list as pl', 'plan_master.plan_list_id', '=', 'pl.id')
            ->join('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->join('market', 'finished_product_category.market_id', '=', 'market.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->leftJoin('specification', 'finished_product_category.specification_id', '=', 'specification.id')
            ->select(
                'plan_master.id',
                'plan_master.batch',
                'plan_master.actual_batch',
                'plan_master.order_number_R1',
                'plan_master.order_number_R2',
                'finished_product_category.intermediate_code',
                'finished_product_category.finished_product_code',
                'finished_product_category.batch_qty',
                'finished_product_category.unit_batch_qty',
                'product_name.name as product_name',
                'market.name as market',
                'specification.name as specification',
                'pl.month as plan_month',
                'pl.year as plan_year'
            )
            ->where('market.is_eu', 1)
            ->where('plan_master.active', 1)
            ->where('plan_master.cancel', 0)
            ->where('pl.type', 1);
    }
}
