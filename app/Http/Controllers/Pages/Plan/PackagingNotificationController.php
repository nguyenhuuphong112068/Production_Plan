<?php

namespace App\Http\Controllers\Pages\Plan;

use App\Http\Controllers\Controller;
use App\Models\PlanMasterInforParkaging;
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
 * người dùng lưu ô đầu tiên.
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

        $datas = PlanMasterInforParkaging::eligibleQuery($planListId)
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
            ->orderBy('pm.expected_date', 'asc')
            ->orderBy('pm.main_parkaging_id', 'asc')
            ->orderByRaw('pm.batch + 0 ASC')
            ->orderBy('pm.level', 'asc')
            ->get();

        $records = PlanMasterInforParkaging::whereIn('plan_master_id', $datas->pluck('id'))
            ->get()
            ->keyBy('plan_master_id');

        session()->put(['title' => $plan->name . ' - THÔNG BÁO ĐÓNG GÓI']);

        return view('pages.plan.packaging_notification.list', [
            'plan' => $plan,
            // Hai tab là hai lát cắt của cùng một tập lô, chia theo cờ market.is_eu
            'euDatas' => $datas->where('is_eu', 1)->values(),
            'nonEuDatas' => $datas->where('is_eu', 0)->values(),
            'records' => $records,
            'keyword' => $keyword,
            'canUpdate' => user_has_permission(
                session('user')['userId'],
                'packaging_notification_update',
                'boolean'
            ),
        ]);
    }

    /**
     * Lưu một dòng thông báo đóng gói.
     *
     * Dùng updateOrCreate để lô chưa có dòng (kế hoạch gửi trước khi có chức năng này)
     * vẫn nhập được, mà không cần chạy backfill cho toàn bộ dữ liệu cũ.
     */
    public function save(Request $request)
    {
        if (!user_has_permission(session('user')['userId'], 'packaging_notification_update', 'boolean')) {
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
            'Sampling_specifications' => 'nullable|string|max:100',
            'Sampling_times' => 'nullable|string|max:100',
            'Sampling_amount' => 'nullable|numeric',
            'sampling_uint' => 'nullable|string|max:50',
            'Reason' => 'nullable|string|max:255',
        ], [
            // Thông báo mặc định của Laravel là tiếng Anh và tự tách tên cột thành
            // "p o no"; toàn bộ giao diện đang tiếng Việt nên đặt lại cho khớp.
            'max' => ':attribute không được dài quá :max ký tự.',
            'Sampling_amount.numeric' => 'Số Lượng Lấy Mẫu phải là số.',
        ], PlanMasterInforParkaging::INPUT_LABELS);

        // Ô để trống lưu thành NULL thay vì chuỗi rỗng, để bộ đếm "đã nhập" ở trang
        // danh sách kế hoạch không tính nhầm dòng người dùng xoá trắng lại.
        $values = [];

        foreach (PlanMasterInforParkaging::inputFields() as $field) {
            $value = $validated[$field] ?? null;
            $values[$field] = ($value === '' || $value === null) ? null : $value;
        }

        $values['plan_list_id'] = $planMaster->plan_list_id;
        $values['updated_by'] = session('user')['fullName'];

        PlanMasterInforParkaging::updateOrCreate(
            ['plan_master_id' => $planMasterId],
            $values
        );

        return response()->json(['success' => true]);
    }
}
