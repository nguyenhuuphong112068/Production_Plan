<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use App\Models\PublicationTrackingDetail;
use App\Models\PublicationTrackingPeriod;
use App\Models\PublicationTrackingTask;
use App\Models\PublicationTrackingTaskHistory;
use App\Models\PublicationTrackingTaskItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicationTrackingController extends Controller
{
        /** Số kỳ gần nhất được tạo tự động khi mở trang danh sách */
        private const AUTO_CREATE_PERIODS = 12;

        /** Ngày bắt đầu của một chu kỳ (20 tháng này -> 19 tháng sau) */
        private const CYCLE_START_DAY = 20;

        /** Các phân xưởng được theo dõi lên ấn bản, theo thứ tự hiển thị */
        private const DEPARTMENTS = [
                'PXV1' => 'PX Viên 1',
                'PXV2' => 'PX Viên 2',
                'PXTN' => 'PX Thuốc Nước',
                'PXDN' => 'PX Dùng Ngoài',
                'PXVH' => 'PX Viên H',
        ];

        /**
         * Ngày bắt đầu của chu kỳ đang chạy tại thời điểm $date.
         * Trước ngày 20 thì vẫn thuộc chu kỳ mở từ ngày 20 tháng trước.
         */
        private function currentCycleStart(?Carbon $date = null): Carbon
        {
                $date = ($date ?? Carbon::now())->copy()->startOfDay();

                return $date->day >= self::CYCLE_START_DAY
                        ? $date->copy()->day(self::CYCLE_START_DAY)
                        : $date->copy()->subMonthNoOverflow()->day(self::CYCLE_START_DAY);
        }

        /**
         * Tạo sẵn các kỳ theo dõi của mọi phân xưởng cho tới chu kỳ đang chạy
         * để danh sách luôn có đủ tháng mà không cần thao tác tay.
         */
        private function ensurePeriods(): void
        {
                $cursor = $this->currentCycleStart();
                $cycles = [];

                for ($i = 0; $i < self::AUTO_CREATE_PERIODS; $i++) {
                        $cycles[] = $cursor->copy();
                        $cursor = $cursor->copy()->subMonthNoOverflow();
                }

                // Lấy 1 lần các kỳ đã có để không phải firstOrCreate từng phân xưởng x từng tháng
                $existing = DB::table('publication_tracking_period')
                        ->whereIn('deparment_code', array_keys(self::DEPARTMENTS))
                        ->get(['deparment_code', 'year', 'month'])
                        ->map(fn($row) => $row->deparment_code . '-' . $row->year . '-' . $row->month)
                        ->flip();

                $now = Carbon::now();
                $inserts = [];

                foreach (array_keys(self::DEPARTMENTS) as $departmentCode) {
                        foreach ($cycles as $cycle) {
                                if ($existing->has($departmentCode . '-' . $cycle->year . '-' . $cycle->month)) {
                                        continue;
                                }

                                $inserts[] = [
                                        'deparment_code' => $departmentCode,
                                        'year' => $cycle->year,
                                        'month' => $cycle->month,
                                        'start_date' => $cycle->toDateString(),
                                        'end_date' => $cycle->copy()->addMonthNoOverflow()->subDay()->toDateString(),
                                        'status' => 'Đang mở',
                                        'created_by' => 'System',
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                ];
                        }
                }

                if (empty($inserts)) {
                        return;
                }

                foreach (array_chunk($inserts, 200) as $chunk) {
                        DB::table('publication_tracking_period')->insert($chunk);
                }

                $this->carryIntoNewPeriods($inserts);
        }

        /**
         * Các kỳ vừa được mở tự động nhận toàn bộ nội dung theo dõi còn dang dở
         * của kỳ liền trước (xem carryPendingItems()).
         *
         * Duyệt theo thứ tự thời gian để nếu mở một lúc nhiều kỳ liên tiếp thì
         * nội dung vẫn chảy đúng dây chuyền từ kỳ cũ nhất tới kỳ mới nhất.
         *
         * @param array $created các dòng vừa insert vào publication_tracking_period
         */
        private function carryIntoNewPeriods(array $created): void
        {
                $periods = PublicationTrackingPeriod::where(function ($q) use ($created) {
                        foreach ($created as $row) {
                                $q->orWhere(fn($w) => $w->where('deparment_code', $row['deparment_code'])
                                        ->where('year', $row['year'])
                                        ->where('month', $row['month']));
                        }
                })
                        ->get()
                        ->sortBy(fn($period) => $period->start_date->toDateString());

                foreach ($periods as $period) {
                        $previousStart = $period->start_date->copy()->subMonthNoOverflow();

                        $previous = PublicationTrackingPeriod::where('deparment_code', $period->deparment_code)
                                ->where('year', $previousStart->year)
                                ->where('month', $previousStart->month)
                                ->first();

                        if ($previous) {
                                $this->carryPendingItems($previous, $period);
                        }
                }
        }

        public function index()
        {
                $this->ensurePeriods();

                $departments = self::DEPARTMENTS;
                $departmentOrder = array_flip(array_keys($departments));

                $periods = PublicationTrackingPeriod::whereIn('deparment_code', array_keys($departments))
                        ->get()
                        ->sortBy([
                                fn($a, $b) => $b->year <=> $a->year,
                                fn($a, $b) => $b->month <=> $a->month,
                                fn($a, $b) => ($departmentOrder[$a->deparment_code] ?? 99)
                                        <=> ($departmentOrder[$b->deparment_code] ?? 99),
                        ])
                        ->values();

                $counts = DB::table('publication_tracking_detail')
                        ->select('period_id', 'category_type', DB::raw('count(*) as total'))
                        ->whereIn('period_id', $periods->pluck('id'))
                        ->groupBy('period_id', 'category_type')
                        ->get()
                        ->groupBy('period_id');

                $currentStart = $this->currentCycleStart()->toDateString();

                session()->put(['title' => 'THEO DÕI LÊN ẤN BẢN']);

                return view('pages.category.publication_tracking.list', [
                        'periods' => $periods,
                        'counts' => $counts,
                        'currentStart' => $currentStart,
                        'departments' => $departments,
                        'userDepartment' => session('user')['production_code'],
                ]);
        }

        public function show($id)
        {
                // Xem được kỳ của mọi phân xưởng; chỉnh sửa vẫn giới hạn ở phân xưởng của mình
                $period = PublicationTrackingPeriod::findOrFail($id);

                $isOwnDepartment = $period->deparment_code === session('user')['production_code'];

                // Chỉ đồng bộ kỳ của chính mình: quyền xem mã giả định của người đang xem
                // không được phép quyết định danh sách mã của phân xưởng khác
                if ($isOwnDepartment) {
                        $this->syncDetails($period);
                }

                // withCount để badge biết nội dung đang dùng chung cho bao nhiêu mã,
                // cảnh báo trước khi sửa vì sửa là đổi cho tất cả
                $details = PublicationTrackingDetail::with([
                        'taskItems.task' => fn($q) => $q->withCount('items'),
                        'taskItems.movedToPeriod',
                ])
                        ->where('period_id', $period->id)
                        ->orderBy('sort_order')
                        ->orderBy('code')
                        ->get()
                        ->groupBy('category_type');

                $departmentName = self::DEPARTMENTS[$period->deparment_code] ?? $period->deparment_code;

                // Version công thức lấy trực tiếp từ MMS (bản Revno lớn nhất) chứ không
                // snapshot vào kỳ: người theo dõi cần biết mã đang ở version nào ngay lúc
                // xem, kể cả khi công thức vừa được nâng ấn bản sau lần đồng bộ gần nhất.
                $bomRevisions = mms_bom_revisions(
                        $details->flatten()->pluck('code')
                );

                // Số lô đã lên kế hoạch tháng của kỳ này, để biết mã nào sắp sản xuất
                $plannedBatches = $this->plannedBatches($period);

                session()->put(['title' => 'THEO DÕI LÊN ẤN BẢN - ' . $departmentName . ' - ' . $period->label]);

                // Nhãn kỳ kế tiếp để nút "chuyển kỳ sau" nói rõ nội dung sẽ đi đâu
                $nextLabel = PublicationTrackingPeriod::labelForCycleStart(
                        $period->start_date->copy()->addMonthNoOverflow()
                );

                return view('pages.category.publication_tracking.detail', [
                        'period' => $period,
                        'intermediates' => $details->get('BTP', collect()),
                        'products' => $details->get('TP', collect()),
                        'departmentName' => $departmentName,
                        'canEdit' => $isOwnDepartment,
                        'canCreateTask' => $this->canCreateTask(),
                        'canAddTask' => $this->canAddTask(),
                        'canDecide' => $this->canUpdateDecision(),
                        'nextPeriodLabel' => $nextLabel,
                        'bomRevisions' => $bomRevisions,
                        'plannedBatches' => $plannedBatches,
                        'hasMonthlyPlan' => $this->monthlyPlanLists($period)->isNotEmpty(),
                ]);
        }

        /**
         * Các bảng kế hoạch sản xuất tháng ứng với kỳ theo dõi này.
         *
         * Kỳ theo dõi mang tên tháng lên ấn bản, đi sau chu kỳ thu thập 2 tháng
         * (xem PublicationTrackingPeriod::labelForCycleStart), nên kỳ của chu kỳ
         * 20/07 - 19/08 phải soi kế hoạch tháng 09 của chính phân xưởng đó.
         *
         * Một tháng có thể có nhiều bảng kế hoạch (lập nhiều lần), lấy hết.
         * Kế hoạch chưa gửi vẫn tính: có tên trong bảng nháp cũng đã là dấu hiệu
         * mã sắp được sản xuất, đủ để dược sĩ cân nhắc khi ra quyết định.
         */
        private function monthlyPlanLists(PublicationTrackingPeriod $period)
        {
                $target = $period->start_date->copy()->addMonthsNoOverflow(2);

                return DB::table('plan_list')
                        ->where('deparment_code', $period->deparment_code)
                        ->where('type', 1) // 1 = kế hoạch sản xuất, 0 = bảo trì / hiệu chuẩn
                        ->where('active', 1)
                        ->where('year', $target->year)
                        ->where('month', $target->month)
                        ->pluck('id');
        }

        /**
         * Số lô đã lên kế hoạch tháng, quy về từng mã trong kỳ theo dõi.
         *
         * Mỗi dòng plan_master là 1 lô nên số lô đếm theo số dòng; cột batch là
         * SỐ HIỆU lô (varchar, ví dụ "010226") chứ không phải số lượng lô, chỉ
         * dùng để liệt kê trong chú thích.
         *
         * Kế hoạch lập theo mã TP; mã BTP nhận số lô của mọi mã TP dùng chung
         * bán thành phẩm đó, vì lên ấn bản BMR là lên cho cả nhóm TP ấy.
         *
         * Lô phát sinh so với kế hoạch dự kiến (plan_master.additional = 1) là tập con
         * của số lô trên, được đếm riêng để có nhãn và bộ lọc riêng.
         *
         * @return array ['TP-12' => ['count' => 3, 'lots' => [...],
         *                            'additional_count' => 1, 'additional_lots' => [...]], ...]
         *               mã không có kế hoạch thì vắng mặt
         */
        private function plannedBatches(PublicationTrackingPeriod $period): array
        {
                $planListIds = $this->monthlyPlanLists($period);

                if ($planListIds->isEmpty()) {
                        return [];
                }

                $rows = DB::table('plan_master')
                        ->select(
                                'finished_product_category.id as tp_id',
                                'intermediate_category.id as btp_id',
                                'plan_master.batch',
                                'plan_master.additional'
                        )
                        ->join(
                                'finished_product_category',
                                'plan_master.product_caterogy_id',
                                'finished_product_category.id'
                        )
                        ->leftJoin(
                                'intermediate_category',
                                'finished_product_category.intermediate_code',
                                'intermediate_category.intermediate_code'
                        )
                        ->whereIn('plan_master.plan_list_id', $planListIds)
                        ->where('plan_master.active', 1)
                        ->where('plan_master.cancel', 0)
                        ->get();

                $planned = [];

                foreach ($rows as $row) {
                        $keys = ['TP-' . $row->tp_id];

                        if ($row->btp_id) {
                                $keys[] = 'BTP-' . $row->btp_id;
                        }

                        foreach ($keys as $key) {
                                $planned[$key]['count'] = ($planned[$key]['count'] ?? 0) + 1;

                                if (filled($row->batch)) {
                                        $planned[$key]['lots'][] = $row->batch;
                                }

                                if (!$row->additional) {
                                        continue;
                                }

                                $planned[$key]['additional_count'] = ($planned[$key]['additional_count'] ?? 0) + 1;

                                if (filled($row->batch)) {
                                        $planned[$key]['additional_lots'][] = $row->batch;
                                }
                        }
                }

                return $planned;
        }

        /** Đồng bộ lại danh sách mã hiệu lực của kỳ theo yêu cầu của người dùng */
        public function sync(Request $request)
        {
                $departmentCode = session('user')['production_code'];

                $period = PublicationTrackingPeriod::where('deparment_code', $departmentCode)
                        ->findOrFail($request->period_id);

                if ($period->status === 'Đã chốt') {
                        return response()->json(['success' => false, 'message' => 'Kỳ đã chốt, không thể đồng bộ.']);
                }

                $this->syncDetails($period, true);

                return response()->json(['success' => true, 'message' => 'Đã đồng bộ danh sách mã hiệu lực.']);
        }

        /**
         * Ghi danh sách mã BTP / TP còn hiệu lực tới mốc xét của kỳ (xem effectiveUntil())
         * vào bảng chi tiết.
         * Kỳ đã chốt được giữ nguyên; kỳ đang mở thì thêm mã mới, cập nhật lại
         * snapshot và dọn các mã không còn hiệu lực mà chưa ai nhập nội dung.
         */
        private function syncDetails(PublicationTrackingPeriod $period, bool $force = false): void
        {
                if ($period->status === 'Đã chốt' && !$force) {
                        return;
                }

                $effective = $this->effectiveIntermediates($period)
                        ->concat($this->effectiveProducts($period));

                $existing = DB::table('publication_tracking_detail')
                        ->where('period_id', $period->id)
                        ->get()
                        ->keyBy(fn($row) => $row->category_type . '-' . $row->category_id);

                $updatedBy = session('user')['fullName'] ?? 'System';
                $now = Carbon::now();

                $inserts = [];
                $seen = [];

                foreach ($effective as $index => $item) {
                        $key = $item['category_type'] . '-' . $item['category_id'];
                        $seen[$key] = true;

                        $snapshot = [
                                'code' => $item['code'],
                                'process_code' => $item['process_code'],
                                'product_name' => $item['product_name'],
                                'batch_size' => $item['batch_size'],
                                'dosage_name' => $item['dosage_name'],
                                'market' => $item['market'],
                                'specification' => $item['specification'],
                                'pharmacist_id' => $item['pharmacist_id'],
                                'pharmacist_name' => $item['pharmacist_name'],
                                'sort_order' => $index,
                        ];

                        $current = $existing->get($key);

                        if (!$current) {
                                $inserts[] = $snapshot + [
                                        'period_id' => $period->id,
                                        'category_type' => $item['category_type'],
                                        'category_id' => $item['category_id'],
                                        'updated_by' => $updatedBy,
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                ];
                                continue;
                        }

                        // Chỉ ghi lại khi snapshot thực sự lệch so với danh mục hiện hành
                        $changed = collect($snapshot)->contains(
                                fn($value, $column) => (string) $value !== (string) $current->$column
                        );

                        if ($changed) {
                                DB::table('publication_tracking_detail')
                                        ->where('id', $current->id)
                                        ->update($snapshot + ['updated_at' => $now]);
                        }
                }

                // Mã không còn hiệu lực và chưa gắn công việc nào thì bỏ khỏi kỳ đang mở
                $candidateIds = $existing->reject(fn($row, $key) => isset($seen[$key]))->pluck('id');

                $usedIds = $candidateIds->isEmpty()
                        ? collect()
                        : DB::table('publication_tracking_task_item')
                                ->whereIn('detail_id', $candidateIds)
                                ->distinct()
                                ->pluck('detail_id');

                $obsoleteIds = $candidateIds->diff($usedIds);

                DB::transaction(function () use ($inserts, $obsoleteIds) {
                        foreach (array_chunk($inserts, 500) as $chunk) {
                                DB::table('publication_tracking_detail')->insert($chunk);
                        }

                        if ($obsoleteIds->isNotEmpty()) {
                                DB::table('publication_tracking_detail')->whereIn('id', $obsoleteIds)->delete();
                        }
                });
        }

        /**
         * Mốc thời gian xét mã còn hiệu lực của kỳ.
         *
         * Kỳ đã chốt giữ nguyên ngày kết thúc chu kỳ để tái hiện đúng danh sách mã
         * tại thời điểm chốt. Kỳ đang mở lấy tới hôm nay, vì kỳ vẫn đang quyết định
         * lên ấn bản nên mã mới thêm vào danh mục sau ngày kết thúc chu kỳ cũng
         * phải đồng bộ được vào kỳ. Kỳ đang mở mà chu kỳ còn ở tương lai thì giữ
         * ngày kết thúc để không thu hẹp phạm vi so với trước.
         */
        private function effectiveUntil(PublicationTrackingPeriod $period): string
        {
                if ($period->status === 'Đã chốt') {
                        return $period->end_date->toDateString();
                }

                return $period->end_date->max(Carbon::now())->toDateString();
        }

        /** Mã bán thành phẩm còn hiệu lực tính tới mốc xét của kỳ */
        private function effectiveIntermediates(PublicationTrackingPeriod $period)
        {
                $endDate = $this->effectiveUntil($period);

                return DB::table('intermediate_category')
                        ->select(
                                'intermediate_category.id',
                                'intermediate_category.intermediate_code as code',
                                'intermediate_category.batch_size',
                                'intermediate_category.unit_batch_size',
                                'intermediate_category.pharmacist_id',
                                'product_name.name as product_name',
                                'dosage.name as dosage_name',
                                'pharmacist.fullName as pharmacist_name'
                        )
                        ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                        ->leftJoin('dosage', 'intermediate_category.dosage_id', 'dosage.id')
                        ->leftJoin('user_management as pharmacist', 'intermediate_category.pharmacist_id', 'pharmacist.id')
                        ->where('intermediate_category.deparment_code', $period->deparment_code)
                        ->where('intermediate_category.active', 1)
                        ->where('intermediate_category.cancel', 0)
                        ->when(
                                !user_has_permission(session('user')['userId'], 'view_Hypothesis_category', 'boolean'),
                                fn($q) => $q->where('intermediate_category.IsHypothesis', 0)
                        )
                        ->where(function ($q) use ($endDate) {
                                $q->whereNull('intermediate_category.created_at')
                                        ->orWhereDate('intermediate_category.created_at', '<=', $endDate);
                        })
                        ->orderBy('product_name.name', 'asc')
                        ->orderBy('intermediate_category.intermediate_code', 'asc')
                        ->get()
                        ->map(fn($row) => [
                                'category_type' => 'BTP',
                                'category_id' => $row->id,
                                'code' => $row->code,
                                'process_code' => null,
                                'product_name' => $row->product_name,
                                'batch_size' => $this->formatBatchSize($row->batch_size, $row->unit_batch_size),
                                'dosage_name' => $row->dosage_name,
                                'market' => null, // mã BTP không gắn thị trường
                                'specification' => null, // qui cách chỉ có ở mã TP
                                'pharmacist_id' => $row->pharmacist_id,
                                'pharmacist_name' => $row->pharmacist_name,
                        ]);
        }

        /** Mã thành phẩm còn hiệu lực tính tới mốc xét của kỳ */
        private function effectiveProducts(PublicationTrackingPeriod $period)
        {
                $endDate = $this->effectiveUntil($period);

                // Dược sĩ phụ trách của mã TP (BPR) lấy theo mã BTP (BMR) tương ứng:
                // danh mục thành phẩm không còn tự xác định dược sĩ phụ trách nữa.
                return DB::table('finished_product_category')
                        ->select(
                                'finished_product_category.id',
                                'finished_product_category.finished_product_code as code',
                                'finished_product_category.process_code',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'intermediate_category.pharmacist_id',
                                'fp_name.name as product_name',
                                'dosage.name as dosage_name',
                                'market.code as market',
                                'specification.name as specification',
                                'pharmacist.fullName as pharmacist_name'
                        )
                        ->leftJoin('product_name as fp_name', 'finished_product_category.product_name_id', 'fp_name.id')
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
                        ->leftJoin('dosage', 'intermediate_category.dosage_id', 'dosage.id')
                        ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                        ->leftJoin('specification', 'finished_product_category.specification_id', 'specification.id')
                        ->leftJoin('user_management as pharmacist', 'intermediate_category.pharmacist_id', 'pharmacist.id')
                        ->where('finished_product_category.deparment_code', $period->deparment_code)
                        ->where('finished_product_category.active', 1)
                        ->where('finished_product_category.cancel', 0)
                        ->when(
                                !user_has_permission(session('user')['userId'], 'view_Hypothesis_category', 'boolean'),
                                fn($q) => $q->where('finished_product_category.IsHypothesis', 0)
                        )
                        ->where(function ($q) use ($endDate) {
                                $q->whereNull('finished_product_category.created_at')
                                        ->orWhereDate('finished_product_category.created_at', '<=', $endDate);
                        })
                        ->orderBy('fp_name.name', 'asc')
                        ->orderBy('finished_product_category.finished_product_code', 'asc')
                        ->get()
                        ->map(fn($row) => [
                                'category_type' => 'TP',
                                'category_id' => $row->id,
                                'code' => $row->code,
                                'process_code' => $row->process_code,
                                'product_name' => $row->product_name,
                                'batch_size' => $this->formatBatchSize($row->batch_qty, $row->unit_batch_qty),
                                'dosage_name' => $row->dosage_name,
                                'market' => $row->market,
                                'specification' => $row->specification,
                                'pharmacist_id' => $row->pharmacist_id,
                                'pharmacist_name' => $row->pharmacist_name,
                        ]);
        }

        private function formatBatchSize($qty, $unit): string
        {
                if ($qty === null || $qty === '') {
                        return '';
                }

                return trim(number_format((float) $qty, 0, ',', '.') . ' ' . ($unit ?? ''));
        }

        /**
         * Kỳ đang mở của phân xưởng người dùng, dùng chung cho các endpoint chỉnh sửa.
         * Trả về null kèm response lỗi nếu không được phép sửa.
         */
        private function editablePeriod($periodId, &$error)
        {
                $error = null;

                $period = PublicationTrackingPeriod::where('deparment_code', session('user')['production_code'])
                        ->find($periodId);

                if (!$period) {
                        $error = response()->json(['success' => false, 'message' => 'Không tìm thấy kỳ theo dõi.'], 404);
                        return null;
                }

                if ($period->status === 'Đã chốt') {
                        $error = response()->json(['success' => false, 'message' => 'Kỳ đã chốt, không thể chỉnh sửa.']);
                        return null;
                }

                return $period;
        }

        /** Quyền dùng nút "Tạo nội dung theo dõi": 1 nội dung gán cho nhiều mã cùng lúc */
        private function canCreateTask(): bool
        {
                return user_has_permission(session('user')['userId'], 'publication_tracking_task_create', 'boolean');
        }

        /**
         * Quyền dùng nút "Thêm nội dung" và các nút thao tác trên từng nội dung.
         * Quyền này chỉ có tác dụng trên mã do chính người đó phụ trách.
         */
        private function canAddTask(): bool
        {
                return user_has_permission(session('user')['userId'], 'publication_tracking_task_add', 'boolean');
        }

        /** Quyền bấm Có / Không thực hiện và nhập ngày dự kiến ở cột Quyết Định */
        private function canUpdateDecision(): bool
        {
                return user_has_permission(session('user')['userId'], 'publication_tracking_decision_update', 'boolean');
        }

        /**
         * Được phép thêm / sửa / xoá / chuyển kỳ nội dung theo dõi trên các mã này không.
         *
         * Quyền tạo hàng loạt thao tác được trên mọi mã; quyền thêm từng mã chỉ thao
         * tác được khi đúng là dược sĩ phụ trách của tất cả các mã liên quan — một nội
         * dung dùng chung cho nhiều mã nên sửa nó là đụng tới cả những mã đó.
         */
        private function canManageDetails($detailIds): bool
        {
                if ($this->canCreateTask()) {
                        return true;
                }

                $detailIds = collect($detailIds)->filter()->unique();

                if ($detailIds->isEmpty() || !$this->canAddTask()) {
                        return false;
                }

                // Mã chưa gán dược sĩ (pharmacist_id null) cũng không phải mã của mình
                return !DB::table('publication_tracking_detail')
                        ->whereIn('id', $detailIds)
                        ->where(function ($q) {
                                $q->whereNull('pharmacist_id')
                                        ->orWhere('pharmacist_id', '!=', session('user')['userId']);
                        })
                        ->exists();
        }

        /** Các mã đang gắn 1 nội dung theo dõi */
        private function taskDetailIds($taskId)
        {
                return DB::table('publication_tracking_task_item')
                        ->where('task_id', $taskId)
                        ->pluck('detail_id');
        }

        private function forbidden(?string $message = null)
        {
                return response()->json([
                        'success' => false,
                        'message' => $message ?? 'Bạn không có quyền thao tác nội dung theo dõi của mã này.',
                ], 403);
        }

        /** Ghi 1 dòng vào lịch sử thay đổi nội dung theo dõi */
        private function logTaskHistory(array $data): void
        {
                PublicationTrackingTaskHistory::create($data + [
                        'changed_by' => session('user')['fullName'] ?? 'System',
                        'created_at' => Carbon::now(),
                ]);
        }

        /**
         * Ghi nhiều dòng lịch sử cùng lúc. Chuyển kỳ hàng loạt sinh ra 1 dòng cho
         * mỗi mã nên insert từng dòng qua model là quá tốn truy vấn.
         *
         * Dòng nào tự khai changed_by / created_at thì giữ nguyên giá trị đó.
         */
        private function logTaskHistoryBatch(array $rows): void
        {
                if (empty($rows)) {
                        return;
                }

                $common = [
                        'changed_by' => session('user')['fullName'] ?? 'System',
                        'created_at' => Carbon::now(),
                ];

                foreach (array_chunk($rows, 500) as $chunk) {
                        DB::table('publication_tracking_task_history')
                                ->insert(array_map(fn($row) => $row + $common, $chunk));
                }
        }

        /**
         * Chuẩn hoá danh sách id mã được chọn.
         *
         * Client gửi dưới dạng chuỗi JSON trong 1 biến duy nhất: gửi mảng
         * detail_ids[] sẽ vượt max_input_vars của PHP (mặc định 1000) khi người
         * dùng chọn cả nghìn mã, PHP cắt bớt input và request hỏng giữa chừng.
         * Vẫn chấp nhận mảng thường để gọi trực tiếp từ code/test.
         */
        private function parseDetailIds($raw): array
        {
                if (is_string($raw)) {
                        $raw = json_decode($raw, true);
                }

                if (!is_array($raw)) {
                        return [];
                }

                return collect($raw)
                        ->filter(fn($v) => is_numeric($v))
                        ->map(fn($v) => (int) $v)
                        ->unique()
                        ->values()
                        ->all();
        }

        /**
         * Thêm 1 nội dung theo dõi và gắn cùng lúc cho nhiều mã BTP / TP trong kỳ.
         */
        public function storeTask(Request $request)
        {
                $validated = $request->validate([
                        'period_id' => 'required|integer',
                        'content' => 'required|string',
                ], [
                        'content.required' => 'Vui lòng nhập nội dung theo dõi.',
                ]);

                $selectedIds = $this->parseDetailIds($request->input('detail_ids'));

                if (empty($selectedIds)) {
                        return response()->json(['success' => false, 'message' => 'Vui lòng chọn ít nhất 1 mã BTP/TP.']);
                }

                // Gán cho nhiều mã cùng lúc là thao tác của nút "Tạo nội dung theo dõi",
                // nút "Thêm nội dung" trong ô chỉ gán cho đúng 1 mã
                if (count($selectedIds) > 1 && !$this->canCreateTask()) {
                        return $this->forbidden();
                }

                $period = $this->editablePeriod($validated['period_id'], $error);
                if (!$period) {
                        return $error;
                }

                // Chỉ nhận các mã thực sự thuộc kỳ này
                $detailIds = DB::table('publication_tracking_detail')
                        ->where('period_id', $period->id)
                        ->whereIn('id', $selectedIds)
                        ->pluck('id');

                if ($detailIds->isEmpty()) {
                        return response()->json(['success' => false, 'message' => 'Các mã đã chọn không thuộc kỳ này.']);
                }

                if (!$this->canManageDetails($detailIds)) {
                        return $this->forbidden();
                }

                $updatedBy = session('user')['fullName'] ?? 'System';
                $now = Carbon::now();

                $taskId = null;

                DB::transaction(function () use ($period, $validated, $detailIds, $updatedBy, $now, &$taskId) {
                        $taskId = DB::table('publication_tracking_task')->insertGetId([
                                'period_id' => $period->id,
                                'content' => $validated['content'],
                                'created_by' => $updatedBy,
                                'created_at' => $now,
                                'updated_at' => $now,
                        ]);

                        DB::table('publication_tracking_task_item')->insert(
                                $detailIds->map(fn($detailId) => [
                                        'task_id' => $taskId,
                                        'detail_id' => $detailId,
                                        'updated_by' => $updatedBy,
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                ])->all()
                        );
                });

                // Trả về id vừa tạo để client vẽ thẳng badge mới, khỏi tải lại cả trang
                $items = DB::table('publication_tracking_task_item')
                        ->where('task_id', $taskId)
                        ->get(['id', 'detail_id']);

                $this->logTaskHistory([
                        'task_id' => $taskId,
                        'period_id' => $period->id,
                        'action' => 'create',
                        'new_content' => $validated['content'],
                        'affected_count' => $items->count(),
                ]);

                return response()->json([
                        'success' => true,
                        'task_id' => $taskId,
                        'content' => $validated['content'],
                        'count' => $items->count(),
                        'items' => $items,
                        'updated_by' => $updatedBy,
                        'updated_at' => $now->format('d/m/Y H:i'),
                        'message' => 'Đã thêm nội dung theo dõi cho ' . $items->count() . ' mã.',
                ]);
        }

        /**
         * Kỳ kế tiếp của cùng phân xưởng, mở mới nếu chưa có.
         *
         * ensurePeriods() chỉ tạo sẵn các kỳ tới chu kỳ đang chạy, nên chuyển nội
         * dung sang kỳ sau thường phải mở kỳ đó ra trước.
         *
         * @return array{0: PublicationTrackingPeriod, 1: bool} kỳ kế tiếp và cờ vừa mới tạo
         */
        private function nextPeriod(PublicationTrackingPeriod $period): array
        {
                $start = $period->start_date->copy()->addMonthNoOverflow();

                $next = PublicationTrackingPeriod::where('deparment_code', $period->deparment_code)
                        ->where('year', $start->year)
                        ->where('month', $start->month)
                        ->first();

                if ($next) {
                        return [$next, false];
                }

                $next = PublicationTrackingPeriod::create([
                        'deparment_code' => $period->deparment_code,
                        'year' => $start->year,
                        'month' => $start->month,
                        'start_date' => $start->toDateString(),
                        'end_date' => $start->copy()->addMonthNoOverflow()->subDay()->toDateString(),
                        'status' => 'Đang mở',
                        'created_by' => session('user')['fullName'] ?? 'System',
                ]);

                // Mở kỳ mới là lúc các nội dung còn dang dở của kỳ này đi theo sang
                $this->carryPendingItems($period, $next);

                return [$next, true];
        }

        /**
         * Chuyển toàn bộ nội dung theo dõi còn dang dở của $from sang kỳ $to.
         *
         * "Còn dang dở" = nội dung chưa bị xoá, nằm trên mã chưa được quyết định
         * "Có thực hiện" và bản thân nội dung đó chưa từng được chuyển kỳ. Kỳ cũ
         * vẫn giữ nguyên nội dung để lưu vết, chỉ đánh dấu là đã chuyển đi.
         *
         * @return int số nội dung đã chuyển
         */
        private function carryPendingItems(PublicationTrackingPeriod $from, PublicationTrackingPeriod $to): int
        {
                if ($to->status === 'Đã chốt') {
                        return 0;
                }

                // Mã đã chốt "Có thực hiện" coi như đã xử lý xong trong kỳ, không mang sang
                $details = PublicationTrackingDetail::where('period_id', $from->id)
                        ->where(fn($q) => $q->whereNull('decision')->orWhere('decision', 0))
                        ->get()
                        ->keyBy('id');

                if ($details->isEmpty()) {
                        return 0;
                }

                $items = PublicationTrackingTaskItem::with('task')
                        ->whereIn('detail_id', $details->keys())
                        ->whereNull('moved_to_item_id')
                        ->orderBy('id')
                        ->get()
                        ->filter(fn($item) => $item->task); // nội dung đã bị xoá thì không còn gì để chuyển

                if ($items->isEmpty()) {
                        return 0;
                }

                // Đây là việc của hệ thống lúc mở kỳ mới, không phải thao tác của người
                // vừa mở trang, nên mọi dấu vết đều ghi tên System
                $userName = 'System';
                $now = Carbon::now();

                return DB::transaction(function () use ($items, $details, $from, $to, $userName, $now) {
                        // Mã và nội dung đã có sẵn ở kỳ đích thì dùng lại, giữ đúng cách
                        // 1 nội dung dùng chung cho nhiều mã như khi tạo mới
                        $targets = PublicationTrackingDetail::where('period_id', $to->id)
                                ->get()
                                ->keyBy(fn($row) => $row->category_type . '-' . $row->category_id);

                        $tasks = PublicationTrackingTask::where('period_id', $to->id)
                                ->get()
                                ->keyBy('content');

                        $history = [];

                        foreach ($items as $item) {
                                $detail = $details->get($item->detail_id);
                                $key = $detail->category_type . '-' . $detail->category_id;

                                $target = $targets->get($key);

                                if (!$target) {
                                        $target = $this->cloneDetailInto($detail, $to, $userName);
                                        $targets->put($key, $target);
                                }

                                $content = $item->task->content;
                                $task = $tasks->get($content);

                                if (!$task) {
                                        $task = PublicationTrackingTask::create([
                                                'period_id' => $to->id,
                                                'content' => $content,
                                                'created_by' => $userName,
                                        ]);
                                        $tasks->put($content, $task);
                                }

                                $newItem = PublicationTrackingTaskItem::firstOrCreate(
                                        ['task_id' => $task->id, 'detail_id' => $target->id],
                                        ['updated_by' => $userName]
                                );

                                $item->update([
                                        'moved_to_period_id' => $to->id,
                                        'moved_to_item_id' => $newItem->id,
                                        'moved_at' => $now,
                                        'moved_by' => $userName,
                                ]);

                                // Ghi vết ở kỳ cũ: nội dung nào của mã nào đã đi sang kỳ nào
                                $history[] = [
                                        'task_id' => $item->task_id,
                                        'period_id' => $from->id,
                                        'action' => 'carry',
                                        'old_content' => $content,
                                        'new_content' => $to->label,
                                        'detail_id' => $detail->id,
                                        'detail_code' => $detail->code,
                                        'changed_by' => $userName,
                                ];
                        }

                        $this->logTaskHistoryBatch($history);

                        return count($history);
                });
        }

        /** Dựng lại 1 mã ở kỳ khác từ snapshot của kỳ hiện tại */
        private function cloneDetailInto(
                PublicationTrackingDetail $detail,
                PublicationTrackingPeriod $period,
                string $userName
        ): PublicationTrackingDetail {
                return PublicationTrackingDetail::create([
                        'period_id' => $period->id,
                        'category_type' => $detail->category_type,
                        'category_id' => $detail->category_id,
                        'code' => $detail->code,
                        'process_code' => $detail->process_code,
                        'product_name' => $detail->product_name,
                        'batch_size' => $detail->batch_size,
                        'dosage_name' => $detail->dosage_name,
                        'market' => $detail->market,
                        'specification' => $detail->specification,
                        'pharmacist_id' => $detail->pharmacist_id,
                        'pharmacist_name' => $detail->pharmacist_name,
                        'sort_order' => $detail->sort_order,
                        'updated_by' => $userName,
                ]);
        }

        /**
         * Chuyển 1 nội dung theo dõi của 1 mã sang chu kỳ tiếp theo.
         *
         * Kỳ hiện tại vẫn giữ nguyên nội dung (để còn ghi nhận việc đã theo dõi
         * trong kỳ), chỉ đánh dấu là đã chuyển đi; kỳ sau nhận thêm 1 nội dung
         * cùng chữ gắn vào đúng mã đó.
         */
        public function carryTaskItem(Request $request)
        {
                $request->validate(['id' => 'required|integer']);

                $item = PublicationTrackingTaskItem::with(['task', 'detail', 'movedToPeriod'])
                        ->find($request->input('id'));

                if (!$item || !$item->detail) {
                        return response()->json(['success' => false, 'message' => 'Không tìm thấy dòng theo dõi.'], 404);
                }

                $period = $this->editablePeriod($item->task->period_id, $error);
                if (!$period) {
                        return $error;
                }

                if (!$this->canManageDetails([$item->detail_id])) {
                        return $this->forbidden();
                }

                if ($item->moved_to_item_id) {
                        return response()->json([
                                'success' => false,
                                'message' => 'Nội dung này đã được chuyển sang kỳ '
                                        . (optional($item->movedToPeriod)->label ?? 'sau') . '.',
                        ]);
                }

                [$next, $justCreated] = $this->nextPeriod($period);

                if ($next->status === 'Đã chốt') {
                        return response()->json([
                                'success' => false,
                                'message' => 'Kỳ ' . $next->label . ' đã chốt, không chuyển nội dung sang được.',
                        ]);
                }

                // Kỳ vừa mở chưa có mã nào: lấy danh sách mã hiệu lực về trước để
                // nội dung chuyển sang nằm đúng dòng của mã trong bảng kỳ sau
                if ($justCreated) {
                        $this->syncDetails($next);

                        // Mở kỳ mới đã kéo theo mọi nội dung chưa quyết định, trong đó
                        // thường có luôn nội dung vừa bấm chuyển
                        $item->refresh();

                        if ($item->moved_to_item_id) {
                                return response()->json([
                                        'success' => true,
                                        'moved_to_label' => $next->label,
                                        'moved_by' => $item->moved_by,
                                        'message' => 'Đã mở kỳ ' . $next->label
                                                . ' và chuyển các nội dung chưa quyết định sang kỳ đó.',
                                ]);
                        }
                }

                $detail = $item->detail;
                $content = $item->task->content;
                $userName = session('user')['fullName'] ?? 'System';

                [$task, $taskCreated] = DB::transaction(function () use ($item, $detail, $next, $content, $userName) {
                        // Mã hết hiệu lực ở kỳ sau thì vẫn dựng lại từ snapshot của kỳ này,
                        // nếu không nội dung theo dõi dở dang sẽ không còn chỗ để bám vào
                        $target = PublicationTrackingDetail::where('period_id', $next->id)
                                ->where('category_type', $detail->category_type)
                                ->where('category_id', $detail->category_id)
                                ->first();

                        if (!$target) {
                                $target = $this->cloneDetailInto($detail, $next, $userName);
                        }

                        // Chuyển nhiều mã cùng 1 nội dung thì gom chung 1 công việc ở kỳ sau,
                        // giữ đúng cách 1 nội dung dùng chung cho nhiều mã như khi tạo mới
                        $task = PublicationTrackingTask::where('period_id', $next->id)
                                ->where('content', $content)
                                ->first();

                        $taskCreated = false;

                        if (!$task) {
                                $task = PublicationTrackingTask::create([
                                        'period_id' => $next->id,
                                        'content' => $content,
                                        'created_by' => $userName,
                                ]);
                                $taskCreated = true;
                        }

                        $newItem = PublicationTrackingTaskItem::firstOrCreate(
                                ['task_id' => $task->id, 'detail_id' => $target->id],
                                ['updated_by' => $userName]
                        );

                        $item->update([
                                'moved_to_period_id' => $next->id,
                                'moved_to_item_id' => $newItem->id,
                                'moved_at' => Carbon::now(),
                                'moved_by' => $userName,
                        ]);

                        return [$task, $taskCreated];
                });

                // Ghi vết ở kỳ hiện tại: nội dung nào của mã nào đã chuyển đi đâu.
                // new_content giữ nhãn kỳ đích để bảng lịch sử nói rõ nơi chuyển tới.
                $this->logTaskHistory([
                        'task_id' => $item->task_id,
                        'period_id' => $period->id,
                        'action' => 'carry',
                        'old_content' => $content,
                        'new_content' => $next->label,
                        'detail_id' => $detail->id,
                        'detail_code' => $detail->code,
                ]);

                if ($taskCreated) {
                        $this->logTaskHistory([
                                'task_id' => $task->id,
                                'period_id' => $next->id,
                                'action' => 'create',
                                'new_content' => $content,
                                'affected_count' => 1,
                        ]);
                }

                return response()->json([
                        'success' => true,
                        'moved_to_label' => $next->label,
                        'moved_by' => $userName,
                        'message' => 'Đã chuyển nội dung sang kỳ ' . $next->label . '.',
                ]);
        }

        /** Sửa nội dung của 1 công việc (áp dụng cho mọi mã đang gắn công việc đó) */
        public function updateTask(Request $request)
        {
                $validated = $request->validate([
                        'task_id' => 'required|integer',
                        'content' => 'required|string',
                ]);

                $task = PublicationTrackingTask::find($validated['task_id']);
                if (!$task) {
                        return response()->json(['success' => false, 'message' => 'Không tìm thấy công việc.'], 404);
                }

                $period = $this->editablePeriod($task->period_id, $error);
                if (!$period) {
                        return $error;
                }

                if (!$this->canManageDetails($this->taskDetailIds($task->id))) {
                        return $this->forbidden();
                }

                $oldContent = $task->content;
                $updatedBy = session('user')['fullName'] ?? 'System';

                $task->update([
                        'content' => $validated['content'],
                        'updated_by' => $updatedBy,
                ]);

                $this->logTaskHistory([
                        'task_id' => $task->id,
                        'period_id' => $task->period_id,
                        'action' => 'update',
                        'old_content' => $oldContent,
                        'new_content' => $task->content,
                ]);

                return response()->json([
                        'success' => true,
                        'task_id' => $task->id,
                        'content' => $task->content,
                        'updated_by' => $updatedBy,
                        'updated_at' => $task->updated_at->format('d/m/Y H:i'),
                        'message' => 'Đã cập nhật nội dung theo dõi.',
                ]);
        }

        /** Chuẩn hoá các dòng lịch sử để client dựng bảng */
        private function historyRows($rows)
        {
                return $rows->map(fn($row) => [
                        'action' => $row->action,
                        'action_label' => $row->action_label,
                        'old_content' => $row->old_content,
                        'new_content' => $row->new_content,
                        'detail_code' => $row->detail_code,
                        'affected_count' => $row->affected_count,
                        'changed_by' => $row->changed_by,
                        'changed_at' => $row->created_at?->format('d/m/Y H:i'),
                ]);
        }

        /** Lịch sử thay đổi của 1 nội dung theo dõi */
        public function taskHistory(Request $request)
        {
                $request->validate(['task_id' => 'required|integer']);

                $rows = PublicationTrackingTaskHistory::where('task_id', $request->input('task_id'))
                        ->orderBy('id', 'desc')
                        ->get();

                return response()->json([
                        'success' => true,
                        'rows' => $this->historyRows($rows),
                ]);
        }

        /**
         * Lịch sử thay đổi của tất cả nội dung theo dõi trên 1 mã BTP / TP.
         * Gồm cả những nội dung đã bị gỡ khỏi mã hoặc xoá hẳn, vốn không còn
         * badge nào để mở lịch sử riêng.
         */
        public function detailHistory(Request $request)
        {
                $request->validate(['detail_id' => 'required|integer']);

                $detailId = (int) $request->input('detail_id');

                $detail = PublicationTrackingDetail::find($detailId);
                if (!$detail) {
                        return response()->json(['success' => false, 'message' => 'Không tìm thấy mã trong kỳ.'], 404);
                }

                // Nội dung đang gắn cho mã, cộng thêm những nội dung từng gắn rồi bị gỡ
                $taskIds = DB::table('publication_tracking_task_item')
                        ->where('detail_id', $detailId)
                        ->pluck('task_id')
                        ->merge(PublicationTrackingTaskHistory::where('detail_id', $detailId)->pluck('task_id'))
                        ->unique()
                        ->values();

                if ($taskIds->isEmpty()) {
                        return response()->json(['success' => true, 'code' => $detail->code, 'rows' => []]);
                }

                // Một nội dung có thể dùng chung cho cả nghìn mã: chỉ lấy các dòng
                // chung (tạo / sửa / xoá) và dòng gỡ của đúng mã này, bỏ qua dòng
                // gỡ thuộc về mã khác
                $rows = PublicationTrackingTaskHistory::whereIn('task_id', $taskIds)
                        ->where(fn($q) => $q->whereNull('detail_id')->orWhere('detail_id', $detailId))
                        ->orderBy('id', 'desc')
                        ->get();

                return response()->json([
                        'success' => true,
                        'code' => $detail->code,
                        'rows' => $this->historyRows($rows),
                ]);
        }

        /** Gỡ 1 công việc khỏi 1 mã, hoặc xoá hẳn công việc khỏi mọi mã trong kỳ */
        public function deleteTask(Request $request)
        {
                $validated = $request->validate([
                        'task_id' => 'required|integer',
                        'detail_id' => 'nullable|integer',
                ]);

                $task = PublicationTrackingTask::find($validated['task_id']);
                if (!$task) {
                        return response()->json(['success' => false, 'message' => 'Không tìm thấy công việc.'], 404);
                }

                $period = $this->editablePeriod($task->period_id, $error);
                if (!$period) {
                        return $error;
                }

                // Xoá hẳn nội dung là gỡ khỏi mọi mã đang gắn nó, nên phải kiểm cả danh sách đó
                $scope = empty($validated['detail_id'])
                        ? $this->taskDetailIds($task->id)
                        : [$validated['detail_id']];

                if (!$this->canManageDetails($scope)) {
                        return $this->forbidden();
                }

                if (empty($validated['detail_id'])) {
                        $this->logTaskHistory([
                                'task_id' => $task->id,
                                'period_id' => $task->period_id,
                                'action' => 'delete',
                                'old_content' => $task->content,
                        ]);

                        $task->delete(); // cascade xoá luôn task_item
                        return response()->json([
                                'success' => true,
                                'task_id' => $validated['task_id'],
                                'task_deleted' => true,
                                'remaining' => 0,
                                'message' => 'Đã xoá nội dung theo dõi.',
                        ]);
                }

                PublicationTrackingTaskItem::where('task_id', $task->id)
                        ->where('detail_id', $validated['detail_id'])
                        ->delete();

                $this->logTaskHistory([
                        'task_id' => $task->id,
                        'period_id' => $task->period_id,
                        'action' => 'detach',
                        'old_content' => $task->content,
                        'detail_id' => $validated['detail_id'],
                        'detail_code' => DB::table('publication_tracking_detail')
                                ->where('id', $validated['detail_id'])
                                ->value('code'),
                ]);

                // Công việc không còn gắn với mã nào thì bỏ luôn
                $remaining = PublicationTrackingTaskItem::where('task_id', $task->id)->count();
                if ($remaining === 0) {
                        $this->logTaskHistory([
                                'task_id' => $task->id,
                                'period_id' => $task->period_id,
                                'action' => 'delete',
                                'old_content' => $task->content,
                        ]);

                        $task->delete();
                }

                return response()->json([
                        'success' => true,
                        'task_id' => $validated['task_id'],
                        'detail_id' => (int) $validated['detail_id'],
                        'task_deleted' => $remaining === 0,
                        // Client dùng để cập nhật lại "đang gắn cho N mã" ở các badge còn lại
                        'remaining' => $remaining,
                        'message' => 'Đã gỡ nội dung theo dõi khỏi mã này.',
                ]);
        }

        /**
         * Lưu ý kiến DSPT, quyết định (Có/Không + ngày hoàn thành dự kiến) và kết quả
         * thực hiện (ngày hoàn thành + ghi chú) của 1 mã BTP/TP. Mỗi mã chỉ có 1 bộ
         * giá trị, không phụ thuộc số nội dung theo dõi đang gắn cho mã đó.
         *
         * Đây là lưu toàn bộ dòng chứ không phải sửa từng ô: client luôn gửi đủ
         * các giá trị đang hiển thị, ô nào không gửi sẽ bị ghi thành rỗng.
         */
        public function updateDetailDecision(Request $request)
        {
                $request->validate([
                        'id' => 'required|integer',
                        'decision' => 'nullable|in:0,1',
                        'due_date' => 'nullable|date',
                        'completed_date' => 'nullable|date',
                        'comment' => 'nullable|string|max:2000',
                        'pharmacist_opinion' => 'nullable|string|max:2000',
                        'ready' => 'nullable|in:0,1',
                ]);

                $detail = PublicationTrackingDetail::find($request->input('id'));
                if (!$detail) {
                        return response()->json(['success' => false, 'message' => 'Không tìm thấy mã trong kỳ.'], 404);
                }

                $period = $this->editablePeriod($detail->period_id, $error);
                if (!$period) {
                        return $error;
                }

                // Ô nào client không gửi thì coi như để trống, không được đọc thẳng từ mảng validated
                $decisionInput = $request->input('decision');
                $dueDate = $request->input('due_date');

                $decision = ($decisionInput === null || $decisionInput === '')
                        ? null
                        : (bool) $decisionInput;

                // Quyết định "Không" thì ngày hoàn thành dự kiến không còn ý nghĩa
                $newDueDate = $decision === true ? ($dueDate ?: null) : null;
                $newCompletedDate = $request->input('completed_date') ?: null;
                $newComment = $request->input('comment');
                $newOpinion = $request->input('pharmacist_opinion');
                $readyInput = $request->input('ready');
                $newReady = !($readyInput === null || $readyInput === '' || $readyInput === '0');

                // Các cột phân quyền riêng nhưng client gửi chung 1 request: cột nào không
                // có quyền thì giữ nguyên giá trị đang lưu thay vì nhận theo client.
                // Quyết Định có quyền riêng; Ý Kiến DSPT, Kết Quả và Hồ Sơ Sẵn Sàng theo
                // đúng luật của cột Nội Dung Theo Dõi (dược sĩ phụ trách của mã).
                $canDecide = $this->canUpdateDecision();
                $canSaveResult = $this->canManageDetails([$detail->id]);

                if (!$canDecide && !$canSaveResult) {
                        return $this->forbidden('Bạn không có quyền cập nhật mã này.');
                }

                if (!$canDecide) {
                        $decision = $detail->decision;
                        $newDueDate = optional($detail->due_date)->format('Y-m-d');
                }

                if (!$canSaveResult) {
                        $newCompletedDate = optional($detail->completed_date)->format('Y-m-d');
                        $newComment = $detail->comment;
                        $newOpinion = $detail->pharmacist_opinion;
                        $newReady = $detail->ready;
                }

                if ($decision === true && blank($newDueDate)) {
                        return response()->json(['success' => false, 'message' => 'Chọn "Có" thì phải nhập ngày hoàn thành.']);
                }

                // Client luôn gửi cả các ô nên phải so với giá trị cũ, nếu không mỗi lần
                // gõ ghi chú lại đóng dấu nhầm cho cả ô Quyết Định và ngược lại
                $decisionChanged = $detail->decision !== $decision
                        || optional($detail->due_date)->format('Y-m-d') !== $newDueDate;

                $resultChanged = optional($detail->completed_date)->format('Y-m-d') !== $newCompletedDate
                        || (string) $detail->comment !== (string) $newComment;

                $opinionChanged = (string) $detail->pharmacist_opinion !== (string) $newOpinion;

                $readyChanged = (bool) $detail->ready !== $newReady;

                $user = session('user')['fullName'] ?? 'System';
                $now = now();

                $data = [
                        'decision' => $decision,
                        'due_date' => $newDueDate,
                        'completed_date' => $newCompletedDate,
                        'comment' => $newComment,
                        'pharmacist_opinion' => $newOpinion,
                        'ready' => $newReady,
                        'updated_by' => $user,
                ];

                if ($decisionChanged) {
                        $data['decision_by'] = $user;
                        $data['decision_at'] = $now;
                }

                if ($resultChanged) {
                        $data['result_by'] = $user;
                        $data['result_at'] = $now;
                }

                if ($opinionChanged) {
                        $data['opinion_by'] = $user;
                        $data['opinion_at'] = $now;
                }

                if ($readyChanged) {
                        $data['ready_by'] = $user;
                        $data['ready_at'] = $now;
                }

                $detail->update($data);

                return response()->json([
                        'success' => true,
                        // Trả về nhãn đã format sẵn để client thay tại chỗ, khỏi tải lại trang
                        'decision_meta' => $this->actorMeta($detail->decision_by, $detail->decision_at),
                        'result_meta' => $this->actorMeta($detail->result_by, $detail->result_at),
                        'opinion_meta' => $this->actorMeta($detail->opinion_by, $detail->opinion_at),
                        'ready_meta' => $this->actorMeta($detail->ready_by, $detail->ready_at),
                        'message' => 'Đã lưu.',
                ]);
        }

        /** Nhãn "Người thực hiện · thời điểm" dùng chung cho 2 cột Quyết Định / Kết Quả */
        private function actorMeta(?string $by, $at): string
        {
                $at = $at ? $at->format('d/m/Y H:i') : '';

                if (!$by && !$at) {
                        return '';
                }

                return $by && $at ? $by . ' · ' . $at : ($by ?: $at);
        }

        /** Chốt / mở lại kỳ theo dõi */
        public function toggleStatus(Request $request)
        {
                $period = PublicationTrackingPeriod::where('deparment_code', session('user')['production_code'])
                        ->findOrFail($request->period_id);

                $period->update([
                        'status' => $period->status === 'Đã chốt' ? 'Đang mở' : 'Đã chốt',
                ]);

                return response()->json([
                        'success' => true,
                        'status' => $period->status,
                        'message' => $period->status === 'Đã chốt' ? 'Đã chốt kỳ theo dõi.' : 'Đã mở lại kỳ theo dõi.',
                ]);
        }
}
