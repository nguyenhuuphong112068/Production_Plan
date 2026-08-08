<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use App\Models\PlanMasterKcs;
use App\Models\PlanMasterKcsHistory;
use App\Support\MmsBom;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Theo dõi hồ sơ KCS: mỗi lô sản xuất là một dòng, người dùng nhập các mốc hồ sơ
 * (ngày nhận hồ sơ, người đọc, ngày KCS, COATP, các ngày approval...) và hệ thống
 * chấm "Đáp Ứng / Không Đáp Ứng" theo số ngày hoàn thành, kèm bảng tổng kết tỉ lệ.
 */
class KcsTrackingController extends Controller
{
    /** Các phân xưởng có theo dõi hồ sơ KCS, theo thứ tự hiển thị */
    private const DEPARTMENTS = [
        'PXV1' => 'PX Viên 1',
        'PXV2' => 'PX Viên 2',
        'PXTN' => 'PX Thuốc Nước',
        'PXDN' => 'PX Dùng Ngoài',
        'PXVH' => 'PX Viên H',
    ];

    /**
     * Các mốc đã có sẵn trên plan_master được điền trước vào ô nhập để đỡ gõ lại.
     *
     * Chỉ là gợi ý trên giao diện: dòng theo dõi chỉ được tạo khi người dùng thật sự
     * bấm lưu. Cố ý không tự sinh dòng và tự chấm kết quả từ dữ liệu này, vì
     * plan_master không có "ngày đọc xong hồ sơ" - mốc thường quyết định ngày đủ
     * điều kiện - nên tự chấm sẽ cho tỉ lệ đúng hạn thấp hơn thực tế.
     */
    public const PREFILL_FROM_PLAN_MASTER = [
        'record_received_date' => 'actual_record_date',
        'coatp_received_date' => 'actual_CoA_date',
        'kcs_date' => 'actual_KCS',
    ];

    public function index(Request $request)
    {
        // Mặc định chỉ lấy tháng kế hoạch hiện tại: lưới có tới 15 ô nhập mỗi lô nên
        // mở rộng cả năm sẽ nặng trang. Người dùng tự nới khoảng khi cần xem lại.
        $department = $request->query('department', session('user')['production_code'] ?? '');
        $fromMonth = $request->query('from_month') ?: Carbon::now()->format('Y-m');
        $toMonth = $request->query('to_month') ?: Carbon::now()->format('Y-m');
        $keyword = trim((string) $request->query('keyword', ''));
        $summaryYear = (int) ($request->query('summary_year') ?: Carbon::now()->year);

        $datas = $this->batches($department, $fromMonth, $toMonth, $keyword);

        $records = PlanMasterKcs::whereIn('plan_master_id', $datas->pluck('id'))
            ->get()
            ->keyBy('plan_master_id');

        $bomVersions = $this->captureBomVersions($datas);

        session()->put(['title' => 'THEO DÕI HỒ SƠ KCS']);

        return view('pages.plan.kcs_tracking.list', [
            'datas' => $datas,
            'records' => $records,
            'bomVersions' => $bomVersions,
            'summary' => $this->buildSummary($department, $summaryYear),
            'summaryYear' => $summaryYear,
            'summaryYears' => $this->summaryYears(),
            'departments' => self::DEPARTMENTS,
            'department' => $department,
            'fromMonth' => $fromMonth,
            'toMonth' => $toMonth,
            'keyword' => $keyword,
            'canUpdate' => user_has_permission(session('user')['userId'], 'kcs_tracking_update', 'boolean'),
        ]);
    }

    /**
     * Lưu một dòng theo dõi. Các cột dẫn xuất được tính lại từ toàn bộ dữ liệu
     * sau khi ghép thay đổi, nên sửa lẻ một ô vẫn cho kết quả đúng.
     */
    public function save(Request $request)
    {
        if (!user_has_permission(session('user')['userId'], 'kcs_tracking_update', 'boolean')) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền cập nhật theo dõi hồ sơ KCS'], 403);
        }

        $request->validate([
            'plan_master_id' => 'required|integer|exists:plan_master,id',
            'record_received_date' => 'nullable|date',
            'reader' => 'nullable|string|max:100',
            'record_done_date' => 'nullable|date',
            'stock_in_qty' => 'nullable|integer|min:0',
            'kcs_date' => 'nullable|date',
            'coatp_number' => 'nullable|string|max:50',
            'coatp_received_date' => 'nullable|date',
            'dr_ir' => 'nullable|string|max:100',
            'dr_ir_approval_date' => 'nullable|date',
            'oos' => 'nullable|string|max:100',
            'oos_approval_date' => 'nullable|date',
            'dr_ir_kcq_approval_date' => 'nullable|date',
            'opv_pvr_approval_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        try {
            $changes = DB::transaction(function () use ($request) {
                $record = PlanMasterKcs::firstOrNew(['plan_master_id' => $request->plan_master_id]);
                $isNew = !$record->exists;

                // Chụp giá trị cũ trước khi ghép thay đổi để so ra đúng những ô thật sự đổi
                $before = $this->inputSnapshot($record);

                foreach (PlanMasterKcs::inputFields() as $field) {
                    if ($request->has($field)) {
                        $value = $request->input($field);
                        $record->{$field} = ($value === '' ? null : $value);
                    }
                }

                $record->fill(PlanMasterKcs::derive($this->inputSnapshot($record)));
                $record->updated_by = session('user')['userName'] ?? 'System';
                $record->save();

                $this->recordHistory($record, $before, $isNew);

                return $record;
            });

            return response()->json([
                'success' => true,
                'message' => 'Đã lưu',
                'derived' => [
                    'eligible_date' => optional($changes->eligible_date)->format('d/m/Y'),
                    'completion_days' => $changes->completion_days,
                    'bottleneck' => $changes->bottleneck,
                    'kcs_pending' => $changes->kcs_pending,
                    'kcs_month' => $changes->kcs_month,
                    'result' => $changes->result,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /** Lịch sử chỉnh sửa của một lô, hiển thị trong modal */
    public function history(Request $request)
    {
        $request->validate(['plan_master_id' => 'required|integer']);

        $histories = PlanMasterKcsHistory::where('plan_master_id', $request->plan_master_id)
            ->orderBy('id', 'desc')
            ->get();

        $html = view('pages.plan.kcs_tracking.history', compact('histories'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Giá trị hiện tại của các cột nhập, đã chuẩn hoá về chuỗi để so sánh và ghi vết.
     *
     * @return array<string, string|null>
     */
    private function inputSnapshot(PlanMasterKcs $record): array
    {
        $snapshot = [];

        foreach (PlanMasterKcs::inputFields() as $field) {
            $value = $record->{$field};

            if ($value instanceof Carbon) {
                $value = $value->toDateString();
            }

            $snapshot[$field] = ($value === null || $value === '') ? null : (string) $value;
        }

        return $snapshot;
    }

    /**
     * Ghi vết những ô thật sự thay đổi. Lần lưu đầu tiên của một lô được đánh dấu
     * "create"; các lần sau là "update". Không có ô nào đổi thì không ghi gì.
     *
     * @param  array<string, string|null>  $before
     */
    private function recordHistory(PlanMasterKcs $record, array $before, bool $isNew): void
    {
        $after = $this->inputSnapshot($record);
        $now = Carbon::now();
        $changedBy = session('user')['userName'] ?? 'System';
        $rows = [];

        foreach ($after as $field => $newValue) {
            $oldValue = $before[$field] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $rows[] = [
                'plan_master_id' => $record->plan_master_id,
                'kcs_id' => $record->id,
                'action' => $isNew ? 'create' : 'update',
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_by' => $changedBy,
                'created_at' => $now,
            ];
        }

        if ($rows) {
            PlanMasterKcsHistory::insert($rows);
        }
    }

    /** Bảng tổng kết tỉ lệ (dùng khi người dùng đổi phân xưởng / năm mà không tải lại trang) */
    public function summary(Request $request)
    {
        $department = $request->query('department', '');
        $year = (int) ($request->query('year') ?: Carbon::now()->year);

        $html = view('pages.plan.kcs_tracking.summary', [
            'summary' => $this->buildSummary($department, $year),
            'summaryYear' => $year,
            'summaryYears' => $this->summaryYears(),
            'departments' => self::DEPARTMENTS,
            'department' => $department,
        ])->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Chốt version BOM (mã BTP / TP) cho những lô đã có số lệnh nhưng chưa được chốt,
     * rồi trả về bản đã chốt theo plan_master_id để lưới hiển thị.
     *
     * Chỉ chốt một lần: lô đã có bản ghi thì giữ nguyên version cũ dù MMS đã nâng
     * revision, vì hồ sơ KCS phải phản ánh version tại thời điểm cấp số lệnh.
     * Lô chưa có số lệnh thì chưa chốt, để version không bị lấy sớm hơn thực tế.
     */
    private function captureBomVersions($datas)
    {
        $existing = DB::table('plan_master_bom_version')
            ->whereIn('plan_master_id', $datas->pluck('id'))
            ->get()
            ->keyBy('plan_master_id');

        $pending = $datas->filter(fn($data) => !empty($data->order_number_R1)
            && !$existing->has($data->id));

        if ($pending->isEmpty()) {
            return $existing;
        }

        // MMS không kết nối được thì bỏ qua lần chốt này, trang vẫn dùng bình thường
        if (!MmsBom::isReachable()) {
            return $existing;
        }

        $now = Carbon::now();
        $inserts = [];

        foreach ($pending as $data) {
            // Tra version tại đúng ngày cấp số lệnh, không lấy version hiện tại: BOM được
            // nâng revision theo thời gian nên lô cũ phải giữ version dùng lúc sản xuất.
            $orderedAt = $data->create_at_order_number ?: $now;

            $inserts[] = [
                'plan_master_id' => $data->id,
                'order_number' => $data->order_number_R1,
                'btp_code' => $data->intermediate_code,
                'btp_version' => MmsBom::revisionAsOf($data->intermediate_code, $orderedAt),
                'tp_code' => $data->finished_product_code,
                'tp_version' => MmsBom::revisionAsOf($data->finished_product_code, $orderedAt),
                'captured_at' => $now,
            ];
        }

        foreach (array_chunk($inserts, 200) as $chunk) {
            // insertOrIgnore: hai người mở trang cùng lúc thì bản chốt trước thắng
            DB::table('plan_master_bom_version')->insertOrIgnore($chunk);
        }

        return DB::table('plan_master_bom_version')
            ->whereIn('plan_master_id', $datas->pluck('id'))
            ->get()
            ->keyBy('plan_master_id');
    }

    /** Danh sách lô sản xuất cần theo dõi hồ sơ KCS trong khoảng tháng kế hoạch */
    private function batches(string $department, string $fromMonth, string $toMonth, string $keyword)
    {
        $query = DB::table('plan_master')
            ->join('plan_list as pl', 'plan_master.plan_list_id', '=', 'pl.id')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
            ->leftJoin('specification', 'finished_product_category.specification_id', '=', 'specification.id')
            ->select(
                'plan_master.id',
                'plan_master.batch',
                'plan_master.actual_batch',
                'plan_master.order_number_R1',
                'plan_master.order_number_R2',
                'plan_master.create_at_order_number',
                'plan_master.expected_date',
                'plan_master.is_val',
                'plan_master.code_val',
                'plan_master.deparment_code',
                'plan_master.actual_record_date',
                'plan_master.actual_CoA_date',
                'plan_master.actual_KCS',
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
            ->where('plan_master.active', 1)
            ->where('plan_master.cancel', 0)
            ->where('pl.type', 1);

        if ($department) {
            $query->where('plan_master.deparment_code', $department);
        }

        if ($fromMonth) {
            $query->whereRaw("STR_TO_DATE(CONCAT('01-', pl.month, '-', pl.year), '%d-%c-%Y') >= ?", [$fromMonth . '-01']);
        }

        if ($toMonth) {
            $query->whereRaw("STR_TO_DATE(CONCAT('01-', pl.month, '-', pl.year), '%d-%c-%Y') <= ?", [$toMonth . '-01']);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('finished_product_category.finished_product_code', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('finished_product_category.intermediate_code', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('product_name.name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('plan_master.batch', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('plan_master.actual_batch', 'LIKE', '%' . $keyword . '%');
            });
        }

        return $query->orderBy('pl.year', 'desc')
            ->orderBy('pl.month', 'desc')
            ->orderBy('plan_master.expected_date', 'asc')
            ->orderBy('plan_master.batch', 'asc')
            ->get();
    }

    /**
     * Tổng kết tỉ lệ lô KCS đúng hạn theo từng tháng và trung bình theo quý.
     *
     * @return array{months: array<int, array>, quarters: array<int, array>, total: array}
     */
    private function buildSummary(string $department, int $year): array
    {
        $rows = PlanMasterKcs::query()
            ->join('plan_master', 'plan_master_KCS.plan_master_id', '=', 'plan_master.id')
            ->where('plan_master_KCS.kcs_year', $year)
            ->when($department, fn($q) => $q->where('plan_master.deparment_code', $department))
            ->groupBy('plan_master_KCS.kcs_month')
            ->select(
                'plan_master_KCS.kcs_month',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN plan_master_KCS.result = '" . PlanMasterKcs::RESULT_MET . "' THEN 1 ELSE 0 END) as on_time")
            )
            ->get()
            ->keyBy('kcs_month');

        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $total = (int) ($rows[$month]->total ?? 0);
            $onTime = (int) ($rows[$month]->on_time ?? 0);

            $months[$month] = [
                'month' => $month,
                'total' => $total,
                'on_time' => $onTime,
                'late' => $total - $onTime,
                'rate' => $total > 0 ? (int) round($onTime / $total * 100) : null,
            ];
        }

        $quarters = [];

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $inQuarter = array_slice($months, ($quarter - 1) * 3, 3);
            $rates = array_filter(array_column($inQuarter, 'rate'), fn($rate) => $rate !== null);

            $quarters[$quarter] = [
                'quarter' => $quarter,
                'total' => array_sum(array_column($inQuarter, 'total')),
                'on_time' => array_sum(array_column($inQuarter, 'on_time')),
                // Trung bình các tỉ lệ tháng, đúng như công thức AVERAGE của file gốc
                'rate' => count($rates) > 0 ? (int) round(array_sum($rates) / count($rates)) : null,
            ];
        }

        $total = array_sum(array_column($months, 'total'));
        $onTime = array_sum(array_column($months, 'on_time'));

        return [
            'months' => $months,
            'quarters' => $quarters,
            'total' => [
                'total' => $total,
                'on_time' => $onTime,
                'late' => $total - $onTime,
                'rate' => $total > 0 ? (int) round($onTime / $total * 100) : null,
            ],
        ];
    }

    /** Các năm có dữ liệu KCS, luôn kèm năm hiện tại để bảng tổng kết không rỗng */
    private function summaryYears(): array
    {
        $years = PlanMasterKcs::whereNotNull('kcs_year')
            ->distinct()
            ->pluck('kcs_year')
            ->map(fn($year) => (int) $year)
            ->all();

        $years[] = Carbon::now()->year;
        $years = array_unique($years);
        rsort($years);

        return $years;
    }
}
