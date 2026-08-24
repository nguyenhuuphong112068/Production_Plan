<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use App\Models\PlanMasterInforParkaging;
use App\Models\PlanMasterInforParkagingHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Thông Báo Đóng Gói.
 *
 * Dùng chung bảng plan_list làm cổng vào: trang đầu liệt kê các kế hoạch tháng,
 * mở một kế hoạch ra sẽ thấy lưới nhập chia làm ba tab "Sản Phẩm Châu Âu",
 * "Sản Phẩm Ngoài Châu Âu" và "Sản Phẩm Việt Nam" (phân loại theo cờ market.is_eu và
 * mã thị trường nội địa - xem open()). Mọi lô còn hiệu lực của kế hoạch đều lên lưới,
 * kể cả lô không chia lô (xem PlanMasterInforParkaging::eligibleQuery).
 *
 * Dòng dữ liệu được sinh sẵn khi gửi kế hoạch tháng (xem
 * PlanMasterInforParkaging::createForPlanList). Trang vẫn hiển thị được lô chưa có
 * dòng - ví dụ kế hoạch đã gửi từ trước khi có chức năng này - và tạo dòng ngay lúc
 * người dùng lưu ô đầu tiên. Nút "Tạo Thông Báo Khác" (thêm lô ngoài quy tắc tự động)
 * vẫn còn nhưng hiện luôn rỗng vì quy tắc tự động đã phủ mọi lô - xem
 * PlanMasterInforParkaging::manualCandidateQuery.
 *
 * Ba quyền tách riêng: PERMISSION_PO cho cột Số PO, PERMISSION_SAMPLING cho các cột
 * lấy mẫu và lý do, PERMISSION_LOCK cho việc khoá/mở khoá ba cột đó (xem lock()).
 * Quyền tạo/gỡ thông báo qua nút "Tạo Thông Báo Khác" đi chung nhóm với PERMISSION_PO
 * chứ không tách riêng.
 */
class PackagingNotificationController extends Controller
{
    /** Cổng vào: danh sách kế hoạch tháng của phân xưởng đang đăng nhập */
    public function index()
    {
        $production_code = session('user')['production_code'];

        $plans = DB::table('plan_list')
            ->where('active', 1)
            ->where('deparment_code', $production_code)
            ->where('type', 1)
            ->orderBy('id', 'desc')
            ->get();

        // Số lô của từng kế hoạch, chia theo 3 tab (Châu Âu / Ngoài Châu Âu / Việt Nam)
        // - tính trực tiếp trên plan_master, khớp đúng với con số hiển thị khi mở kế
        // hoạch ra (xem open()), không phụ thuộc đã có dòng lưu hay chưa.
        $tabCounts = PlanMasterInforParkaging::tabCountsForPlans($plans->pluck('id')->all());

        // Số lô đã xác nhận lấy mẫu (sampled_confirmed), dùng cho cột tiến độ
        $sampledCounts = DB::table('plan_master_infor_parkaging')
            ->whereIn('plan_list_id', $plans->pluck('id'))
            ->where('sampled_confirmed', 1)
            ->select('plan_list_id', DB::raw('COUNT(*) as total'))
            ->groupBy('plan_list_id')
            ->pluck('total', 'plan_list_id');

        session()->put(['title' => 'THÔNG BÁO ĐÓNG GÓI']);

        return view('pages.plan.packaging_notification.plan_list', [
            'plans' => $plans,
            'tabCounts' => $tabCounts,
            'sampledCounts' => $sampledCounts,
        ]);
    }

    /** Mở một kế hoạch tháng: lưới nhập chia ba tab Châu Âu / Ngoài Châu Âu / Việt Nam */
    public function open(Request $request)
    {
        $planListId = (int) $request->query('plan_list_id');

        $plan = DB::table('plan_list')->where('id', $planListId)->first();

        if (!$plan) {
            return redirect()
                ->route('pages.plan.packaging_notification.list')
                ->with('error', 'Không tìm thấy kế hoạch.');
        }

        // Bộ lọc theo từng cột thay vì một ô tìm kiếm gộp: mỗi ô lọc đúng một cột, kết hợp
        // AND với nhau. Thị Trường dùng đúng danh sách thị trường của kế hoạch (marketOptions)
        // nên so khớp tuyệt đối, các cột còn lại so khớp gần đúng (LIKE).
        $filters = [
            'product' => trim((string) $request->query('product', '')),
            'batch' => trim((string) $request->query('batch', '')),
            'finishedCode' => trim((string) $request->query('finished_code', '')),
            'intermediateCode' => trim((string) $request->query('intermediate_code', '')),
            'market' => trim((string) $request->query('market', '')),
        ];

        // Danh sách thị trường đổ vào <select> lọc - lấy trên toàn bộ lô của kế hoạch,
        // KHÔNG áp các bộ lọc khác, để người dùng luôn thấy đủ lựa chọn dù đang lọc dở
        $marketOptions = PlanMasterInforParkaging::visibleQuery($planListId)
            ->whereNotNull('mk.name')
            ->distinct()
            ->orderBy('mk.name')
            ->pluck('mk.name');

        $datas = PlanMasterInforParkaging::visibleQuery($planListId)
            ->leftJoin('product_name as fp_name', 'fpc.product_name_id', '=', 'fp_name.id')
            ->leftJoin('specification as spec', 'fpc.specification_id', '=', 'spec.id')
            ->when($filters['product'] !== '', fn($q) => $q->where('fp_name.name', 'like', "%{$filters['product']}%"))
            ->when($filters['batch'] !== '', fn($q) => $q->where('pm.batch', 'like', "%{$filters['batch']}%"))
            ->when(
                $filters['finishedCode'] !== '',
                fn($q) => $q->where('fpc.finished_product_code', 'like', "%{$filters['finishedCode']}%")
            )
            ->when(
                $filters['intermediateCode'] !== '',
                fn($q) => $q->where('fpc.intermediate_code', 'like', "%{$filters['intermediateCode']}%")
            )
            ->when($filters['market'] !== '', fn($q) => $q->where('mk.name', $filters['market']))
            ->select(
                'pm.id',
                'pm.batch',
                'pm.expected_date',
                'pm.level',
                'pm.only_parkaging',
                'pm.percent_parkaging',
                'pm.main_parkaging_id',
                'fpc.finished_product_code',
                'fpc.intermediate_code',
                'fpc.batch_qty',
                'fpc.unit_batch_qty',
                'fp_name.name as finished_product_name',
                'spec.name as specification',
                'mk.name as market',
                'mk.code as market_code',
                DB::raw('COALESCE(mk.is_eu, 0) as is_eu')
            )
            // Thứ tự nền trước khi xếp lại theo lịch đóng gói - dùng làm tiêu chí phân
            // định khi nhiều lô cùng mã BTP có cùng thời gian đóng gói (hoặc đều chưa
            // có lịch), xem sortForDisplay()
            ->orderBy('pm.expected_date', 'asc')
            ->orderBy('pm.main_parkaging_id', 'asc')
            ->orderByRaw('pm.batch + 0 ASC')
            ->orderBy('pm.level', 'asc')
            ->get();

        $stageInfo = PlanMasterInforParkaging::stageInfoFor($datas);

        // Ưu tiên lô có thời gian đóng gói dự kiến sớm nhất lên trước, đồng thời gom các
        // lô cùng mã BTP nằm cạnh nhau (xem PlanMasterInforParkaging::sortForDisplay)
        $datas = PlanMasterInforParkaging::sortForDisplay($datas, $stageInfo);

        $records = PlanMasterInforParkaging::whereIn('plan_master_id', $datas->pluck('id'))
            ->get()
            ->keyBy('plan_master_id');

        $userId = session('user')['userId'];
        $canUpdatePo = user_has_permission($userId, PlanMasterInforParkaging::PERMISSION_PO, 'boolean');
        $canUpdateSampling = user_has_permission($userId, PlanMasterInforParkaging::PERMISSION_SAMPLING, 'boolean');
        $canLock = user_has_permission($userId, PlanMasterInforParkaging::PERMISSION_LOCK, 'boolean');

        session()->put(['title' => $plan->name . ' - THÔNG BÁO ĐÓNG GÓI']);

        return view('pages.plan.packaging_notification.list', [
            'plan' => $plan,
            // Ba tab là ba lát cắt của cùng một tập lô: Châu Âu theo cờ market.is_eu,
            // Việt Nam theo mã thị trường nội địa, còn lại (nước ngoài không thuộc EU,
            // kể cả thị trường chưa xác định) rơi vào tab Ngoài Châu Âu.
            'euDatas' => $datas->where('is_eu', 1)->values(),
            'vnDatas' => $datas->where('is_eu', 0)
                ->where('market_code', PlanMasterInforParkaging::DOMESTIC_MARKET_CODE)
                ->values(),
            'nonEuDatas' => $datas->where('is_eu', 0)
                ->where('market_code', '<>', PlanMasterInforParkaging::DOMESTIC_MARKET_CODE)
                ->values(),
            'records' => $records,
            'stageInfo' => $stageInfo,
            'filters' => $filters,
            'marketOptions' => $marketOptions,
            'canUpdatePo' => $canUpdatePo,
            'canUpdateSampling' => $canUpdateSampling,
            'canLock' => $canLock,
            // Quyền tạo/gỡ lô ngoài quy tắc đi chung nhóm với quyền nhập Số PO
            'canAdd' => $canUpdatePo,
        ]);
    }

    /**
     * Lưu một dòng thông báo đóng gói.
     *
     * Dùng updateOrCreate để lô chưa có dòng (kế hoạch gửi trước khi có chức năng này)
     * vẫn nhập được, mà không cần chạy backfill cho toàn bộ dữ liệu cũ. Chỉ ghi những
     * cột người dùng có quyền: form gửi lên cả dòng nên nếu không lọc, người chỉ có
     * quyền lấy mẫu sẽ ghi đè cả Số PO.
     */
    public function save(Request $request)
    {
        $userId = session('user')['userId'];
        $canUpdatePo = user_has_permission($userId, PlanMasterInforParkaging::PERMISSION_PO, 'boolean');
        $canUpdateSampling = user_has_permission($userId, PlanMasterInforParkaging::PERMISSION_SAMPLING, 'boolean');

        $editableFields = PlanMasterInforParkaging::editableFields($canUpdatePo, $canUpdateSampling);

        if (empty($editableFields)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật Thông Báo Đóng Gói.',
            ], 403);
        }

        $planMasterId = (int) $request->input('plan_master_id');

        $planMaster = DB::table('plan_master')->where('id', $planMasterId)->first();

        if (!$planMaster) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lô sản xuất.',
            ], 404);
        }

        $validated = $request->validate([
            'PO_no' => 'nullable|string|max:50',
            'primary_sample' => 'nullable|string|max:2000',
            'secondary_sample' => 'nullable|string|max:2000',
            'Reason' => 'nullable|string|max:255',
            'sampled_confirmed' => 'nullable|boolean',
        ], [
            // Thông báo mặc định của Laravel là tiếng Anh và tự tách tên cột thành
            // "p o no"; toàn bộ giao diện đang tiếng Việt nên đặt lại cho khớp.
            'max' => ':attribute không được dài quá :max ký tự.',
        ], PlanMasterInforParkaging::INPUT_LABELS);

        // Ô để trống lưu thành NULL thay vì chuỗi rỗng, để bộ đếm "đã nhập" ở trang
        // danh sách kế hoạch không tính nhầm dòng người dùng xoá trắng lại.
        $values = [];

        foreach ($editableFields as $field) {
            // Checkbox không có trong dữ liệu gửi lên nghĩa là "chưa tick" (false), không
            // phải "chưa biết" (null) - cột NOT NULL default 0 nên phải quy về false, nếu
            // không request thiếu field này sẽ làm insert/update vỡ ràng buộc NOT NULL.
            if ($field === 'sampled_confirmed') {
                $values[$field] = $request->boolean('sampled_confirmed');
                continue;
            }

            $value = $validated[$field] ?? null;
            $values[$field] = ($value === '' || $value === null) ? null : $value;
        }

        $values['plan_list_id'] = $planMaster->plan_list_id;
        $values['updated_by'] = session('user')['fullName'];

        $record = PlanMasterInforParkaging::firstOrNew(['plan_master_id' => $planMasterId]);
        $isNew = !$record->exists;

        // Mẫu Sơ Cấp/Thứ Cấp/Lý Do đang bị khoá thì bỏ qua thay đổi ở các cột này, kể cả
        // khi client vẫn gửi lên (giao diện đã disable, nhưng phòng trường hợp gọi API
        // trực tiếp) - phải mở khoá trước mới sửa lại được.
        if ($record->exists && $record->is_locked) {
            $values = array_diff_key($values, array_flip(PlanMasterInforParkaging::LOCKED_FIELDS));
        }

        // Chỉ được tích "Xác Nhận Đã Lấy Mẫu" khi đã nhập đủ Mẫu Sơ Cấp/Thứ Cấp/Lý Do VÀ
        // dòng đang ở trạng thái khoá (dữ liệu đã chốt, không sửa được nữa). Ba cột này
        // luôn bị lọc khỏi $values khi đã khoá (xem trên) nên trong trường hợp đó luôn rơi
        // vào nhánh lấy từ $record - đúng giá trị đang lưu, không đổi được lúc khoá.
        if (($values['sampled_confirmed'] ?? false) === true) {
            $effectivePrimary = array_key_exists('primary_sample', $values) ? $values['primary_sample'] : $record->primary_sample;
            $effectiveSecondary = array_key_exists('secondary_sample', $values) ? $values['secondary_sample'] : $record->secondary_sample;
            $effectiveReason = array_key_exists('Reason', $values) ? $values['Reason'] : $record->Reason;

            if (empty($effectivePrimary) || empty($effectiveSecondary) || empty($effectiveReason)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cần nhập đủ Mẫu Đóng Gói Sơ Cấp, Thứ Cấp và Lý Do trước khi xác nhận đã lấy mẫu.',
                ], 422);
            }

            if (!$record->is_locked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ xác nhận đã lấy mẫu được khi dòng dữ liệu đang ở trạng thái khoá.',
                ], 422);
            }
        }

        // Chụp giá trị cũ trước khi ghép thay đổi để so ra đúng những ô thật sự đổi
        $before = $this->snapshot($record);

        $record->fill($values);
        $record->save();

        $this->recordHistory($record, $before, $isNew);

        return response()->json(['success' => true]);
    }

    /**
     * Khoá hoặc mở khoá Mẫu Đóng Gói Sơ Cấp, Thứ Cấp và Lý Do của một lô.
     *
     * Khoá lần đầu tiên đánh dấu ever_locked = true vĩnh viễn - mốc này quyết định thời
     * điểm bắt đầu ghi lịch sử nhập liệu (xem recordHistory). Mở khoá luôn bắt buộc nhập
     * lý do, kể cả sau đó khoá lại rồi mở khoá tiếp.
     */
    public function lock(Request $request)
    {
        $userId = session('user')['userId'];

        if (!user_has_permission($userId, PlanMasterInforParkaging::PERMISSION_LOCK, 'boolean')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền khoá/mở khoá Thông Báo Đóng Gói.',
            ], 403);
        }

        $planMasterId = (int) $request->input('plan_master_id');
        $action = $request->input('action');

        $record = PlanMasterInforParkaging::where('plan_master_id', $planMasterId)->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Lô chưa có dữ liệu để khoá. Vui lòng nhập thông tin trước.',
            ], 404);
        }

        $fullName = session('user')['fullName'];
        $now = now();

        if ($action === 'lock') {
            if ($record->is_locked) {
                return response()->json(['success' => true, 'is_locked' => true]);
            }

            $record->is_locked = true;
            $record->ever_locked = true;
            $record->locked_by = $fullName;
            $record->locked_at = $now;
            $record->save();

            PlanMasterInforParkagingHistory::insert([
                'plan_master_id' => $record->plan_master_id,
                'infor_parkaging_id' => $record->id,
                'action' => 'lock',
                'field' => PlanMasterInforParkaging::LOCK_FIELD,
                'old_value' => 'Mở khoá',
                'new_value' => 'Đã khoá',
                'changed_by' => $fullName,
                'created_at' => $now,
            ]);

            return response()->json(['success' => true, 'is_locked' => true]);
        }

        if ($action === 'unlock') {
            if (!$record->is_locked) {
                return response()->json(['success' => true, 'is_locked' => false]);
            }

            $validated = $request->validate([
                'reason' => 'required|string|max:255',
            ], [
                'reason.required' => 'Vui lòng nhập lý do mở khoá.',
            ]);

            $record->is_locked = false;
            $record->save();

            PlanMasterInforParkagingHistory::insert([
                'plan_master_id' => $record->plan_master_id,
                'infor_parkaging_id' => $record->id,
                'action' => 'unlock',
                'field' => PlanMasterInforParkaging::LOCK_FIELD,
                'old_value' => 'Đã khoá',
                'new_value' => 'Mở khoá - Lý do: ' . $validated['reason'],
                'changed_by' => $fullName,
                'created_at' => $now,
            ]);

            return response()->json(['success' => true, 'is_locked' => false]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Hành động không hợp lệ.',
        ], 422);
    }

    /** Lịch sử nhập liệu của một lô, hiển thị trong modal */
    public function history(Request $request)
    {
        $request->validate(['plan_master_id' => 'required|integer']);

        $histories = PlanMasterInforParkagingHistory::where('plan_master_id', $request->plan_master_id)
            ->orderBy('id', 'desc')
            ->get();

        $html = view('pages.plan.packaging_notification.history', compact('histories'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Giá trị hiện tại của các cột người dùng nhập, chuẩn hoá về chuỗi để so sánh
     * và ghi vết.
     *
     * @return array<string, string|null>
     */
    private function snapshot(PlanMasterInforParkaging $record): array
    {
        $snapshot = [];

        foreach (PlanMasterInforParkaging::inputFields() as $field) {
            $value = $record->{$field};

            if ($value instanceof Carbon) {
                $value = $value->toDateString();
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            $snapshot[$field] = ($value === null || $value === '') ? null : (string) $value;
        }

        return $snapshot;
    }

    /**
     * Ghi vết những ô thật sự thay đổi. Lần lưu đầu tiên của một lô được đánh dấu
     * "create"; các lần sau là "update". Không có ô nào đổi thì không ghi gì.
     *
     * Chỉ ghi vết SAU KHI lô đã được khoá lần đầu (xem lock()) - trước đó coi là giai
     * đoạn nháp, người nhập có thể chỉnh sửa thoải mái mà không cần lưu vết từng lần sửa.
     *
     * @param  array<string, string|null>  $before
     */
    private function recordHistory(PlanMasterInforParkaging $record, array $before, bool $isNew): void
    {
        if (!$record->ever_locked) {
            return;
        }

        $after = $this->snapshot($record);
        $now = now();
        $changedBy = session('user')['fullName'];
        $rows = [];

        foreach ($after as $field => $newValue) {
            $oldValue = $before[$field] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $rows[] = [
                'plan_master_id' => $record->plan_master_id,
                'infor_parkaging_id' => $record->id,
                'action' => $isNew ? 'create' : 'update',
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_by' => $changedBy,
                'created_at' => $now,
            ];
        }

        if ($rows) {
            PlanMasterInforParkagingHistory::insert($rows);
        }
    }

    /**
     * Danh sách lô có thể thêm tay vào thông báo đóng gói của một kế hoạch tháng.
     *
     * Chỉ trả về phần nằm ngoài quy tắc tự động và chưa có dòng, để người dùng không
     * chọn nhầm lô đã có sẵn trên lưới.
     */
    public function candidates(Request $request)
    {
        $planListId = (int) $request->query('plan_list_id');
        $keyword = trim((string) $request->query('keyword', ''));

        $datas = PlanMasterInforParkaging::manualCandidateQuery($planListId)
            ->leftJoin('product_name as fp_name', 'fpc.product_name_id', '=', 'fp_name.id')
            ->leftJoin('specification as spec', 'fpc.specification_id', '=', 'spec.id')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('pm.batch', 'like', "%{$keyword}%")
                        ->orWhere('fpc.finished_product_code', 'like', "%{$keyword}%")
                        ->orWhere('fpc.intermediate_code', 'like', "%{$keyword}%")
                        ->orWhere('fp_name.name', 'like', "%{$keyword}%");
                });
            })
            ->select(
                'pm.id',
                'pm.batch',
                'pm.expected_date',
                'fpc.finished_product_code',
                'fp_name.name as finished_product_name',
                'spec.name as specification',
                'mk.name as market'
            )
            ->orderBy('pm.expected_date', 'asc')
            ->orderByRaw('pm.batch + 0 ASC')
            ->limit(300)
            ->get();

        return response()->json([
            'success' => true,
            'datas' => $datas,
        ]);
    }

    /**
     * Tạo thông báo đóng gói cho các lô người dùng chủ động chọn.
     *
     * Đánh dấu is_manual = 1 để lưới giữ lại những lô này dù không thoả quy tắc tự động,
     * và để chỉ chúng mới được gỡ ra.
     */
    public function store(Request $request)
    {
        $userId = session('user')['userId'];

        $canAdd = user_has_permission($userId, PlanMasterInforParkaging::PERMISSION_PO, 'boolean');

        if (!$canAdd) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tạo Thông Báo Đóng Gói.',
            ], 403);
        }

        $validated = $request->validate([
            'plan_list_id' => 'required|integer',
            'plan_master_ids' => 'required|array|min:1',
            'plan_master_ids.*' => 'integer',
        ], [
            'plan_master_ids.required' => 'Chưa chọn lô nào.',
        ]);

        $planListId = (int) $validated['plan_list_id'];

        // Lọc lại theo đúng danh sách được phép thêm: id gửi lên từ trình duyệt nên
        // không tin được, và giữa lúc mở modal với lúc bấm lưu dữ liệu có thể đã đổi.
        $allowedIds = PlanMasterInforParkaging::manualCandidateQuery($planListId)
            ->whereIn('pm.id', $validated['plan_master_ids'])
            ->pluck('pm.id');

        if ($allowedIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Các lô đã chọn không hợp lệ hoặc đã có thông báo đóng gói.',
            ], 422);
        }

        $now = now();
        $fullName = session('user')['fullName'];

        $rows = $allowedIds->map(fn($id) => [
            'plan_master_id' => $id,
            'plan_list_id' => $planListId,
            'is_manual' => 1,
            'created_by' => $fullName,
            'updated_by' => $fullName,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        $created = DB::table('plan_master_infor_parkaging')->insertOrIgnore($rows);

        return response()->json([
            'success' => true,
            'created' => $created,
            'message' => 'Đã tạo thông báo đóng gói cho ' . $created . ' lô.',
        ]);
    }

    /**
     * Gỡ một dòng thông báo đóng gói đã thêm tay.
     *
     * Chỉ xoá được dòng is_manual = 1: dòng sinh tự động theo quy tắc thì gỡ xong lần
     * gửi kế hoạch kế tiếp cũng tạo lại, và xoá nhầm là mất dữ liệu đã nhập.
     */
    public function destroy(Request $request)
    {
        $userId = session('user')['userId'];

        $canAdd = user_has_permission($userId, PlanMasterInforParkaging::PERMISSION_PO, 'boolean');

        if (!$canAdd) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xoá Thông Báo Đóng Gói.',
            ], 403);
        }

        $planMasterId = (int) $request->input('plan_master_id');

        $deleted = PlanMasterInforParkaging::where('plan_master_id', $planMasterId)
            ->where('is_manual', 1)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ gỡ được thông báo do người dùng tự tạo thêm.',
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
