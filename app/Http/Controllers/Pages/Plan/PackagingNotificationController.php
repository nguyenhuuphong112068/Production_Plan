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
 * mở một kế hoạch ra sẽ thấy lưới nhập chia làm hai tab "Sản Phẩm Châu Âu" và
 * "Sản Phẩm Ngoài Châu Âu" (phân loại theo cờ market.is_eu).
 *
 * Dòng dữ liệu được sinh sẵn khi gửi kế hoạch tháng (xem
 * PlanMasterInforParkaging::createForPlanList). Trang vẫn hiển thị được lô chưa có
 * dòng - ví dụ kế hoạch đã gửi từ trước khi có chức năng này - và tạo dòng ngay lúc
 * người dùng lưu ô đầu tiên. Lô nằm ngoài quy tắc tự động thì thêm bằng nút
 * "Tạo Thông Báo Khác".
 *
 * Hai quyền nhập tách riêng: PERMISSION_PO cho cột Số PO, PERMISSION_SAMPLING cho
 * các cột lấy mẫu và lý do. Quyền tạo/gỡ thông báo cho lô ngoài quy tắc (nút
 * "Tạo Thông Báo Khác") đi chung nhóm với PERMISSION_PO chứ không tách riêng.
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

        // Số lô đã có thông báo đóng gói của từng kế hoạch, để người dùng biết kế hoạch
        // nào đã được gửi và có việc để làm trước khi mở ra.
        $rowCounts = DB::table('plan_master_infor_parkaging')
            ->whereIn('plan_list_id', $plans->pluck('id'))
            ->select('plan_list_id', DB::raw('COUNT(*) as total'))
            ->groupBy('plan_list_id')
            ->pluck('total', 'plan_list_id');

        // Số lô đã nhập ít nhất một thông tin, dùng cho cột tiến độ
        $filledCounts = DB::table('plan_master_infor_parkaging')
            ->whereIn('plan_list_id', $plans->pluck('id'))
            ->where(function ($q) {
                foreach (PlanMasterInforParkaging::inputFields() as $field) {
                    $q->orWhere(function ($sub) use ($field) {
                        $sub->whereNotNull($field)->where($field, '<>', '');
                    });
                }
            })
            ->select('plan_list_id', DB::raw('COUNT(*) as total'))
            ->groupBy('plan_list_id')
            ->pluck('total', 'plan_list_id');

        session()->put(['title' => 'THÔNG BÁO ĐÓNG GÓI']);

        return view('pages.plan.packaging_notification.plan_list', [
            'plans' => $plans,
            'rowCounts' => $rowCounts,
            'filledCounts' => $filledCounts,
        ]);
    }

    /** Mở một kế hoạch tháng: lưới nhập chia hai tab Châu Âu / Ngoài Châu Âu */
    public function open(Request $request)
    {
        $planListId = (int) $request->query('plan_list_id');

        $plan = DB::table('plan_list')->where('id', $planListId)->first();

        if (!$plan) {
            return redirect()
                ->route('pages.plan.packaging_notification.list')
                ->with('error', 'Không tìm thấy kế hoạch.');
        }

        $keyword = trim((string) $request->query('keyword', ''));

        $datas = PlanMasterInforParkaging::visibleQuery($planListId)
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

        session()->put(['title' => $plan->name . ' - THÔNG BÁO ĐÓNG GÓI']);

        return view('pages.plan.packaging_notification.list', [
            'plan' => $plan,
            // Hai tab là hai lát cắt của cùng một tập lô, chia theo cờ market.is_eu
            'euDatas' => $datas->where('is_eu', 1)->values(),
            'nonEuDatas' => $datas->where('is_eu', 0)->values(),
            'records' => $records,
            'stageInfo' => $stageInfo,
            'keyword' => $keyword,
            'canUpdatePo' => $canUpdatePo,
            'canUpdateSampling' => $canUpdateSampling,
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

        // Chỉ được tích "Xác Nhận Đã Lấy Mẫu" khi lô đã có Số PO. PO_no có thể không nằm
        // trong request này (người xác nhận thường không có quyền Số PO), nên lấy giá trị
        // SẼ CÓ sau khi lưu: ưu tiên giá trị mới nếu request này có sửa PO_no, không thì
        // lấy giá trị đang lưu sẵn trên dòng.
        if (($values['sampled_confirmed'] ?? false) === true) {
            $effectivePoNo = array_key_exists('PO_no', $values) ? $values['PO_no'] : $record->PO_no;

            if (empty($effectivePoNo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lô chưa có Số PO nên chưa xác nhận đã lấy mẫu được.',
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
     * @param  array<string, string|null>  $before
     */
    private function recordHistory(PlanMasterInforParkaging $record, array $before, bool $isNew): void
    {
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
