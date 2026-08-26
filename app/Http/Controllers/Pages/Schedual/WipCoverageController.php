<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use App\Services\WipCoverageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WipCoverageController extends Controller
{
    public function __construct(private WipCoverageService $service)
    {
    }

    /** Trang riêng, do React đảm nhiệm phần hiển thị */
    public function index()
    {
        session()->put(['title' => 'TỒN KHO LÝ THUYẾT THEO CÔNG ĐOẠN']);

        return view('app');
    }

    /**
     * Thống kê tồn theo từng công đoạn đích (Định hình, Bao phim, Đóng gói).
     * Mặc định đọc bản chốt gần nhất cho nhanh; truyền live=1 để tính lại tại chỗ.
     */
    public function view(Request $request)
    {
        if (! $this->canView()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xem chức năng này.'], 403);
        }

        $productionCode = session('user')['production_code'];

        if ($request->boolean('live')) {
            $payload = $this->computeLive($productionCode);
        } else {
            $payload = $this->readSnapshot($productionCode);

            // Chưa từng chạy lệnh chốt thì tính tại chỗ để trang không trống trơn
            if ($payload === null) {
                $payload = $this->computeLive($productionCode);
            }
        }

        return response()->json([
            'success'         => true,
            'production_code' => $productionCode,
            'group_names'     => WipCoverageService::GROUP_NAMES,
            // Giới hạn trên/dưới cấu hình ở trang Chính sách sản lượng, để trang
            // này tô được ngày nào vượt ngưỡng
            'stock_limits'    => array_values(array_map(
                fn($limit) => (array) $limit,
                WipCoverageService::stockLimitsFor($productionCode)
            )),
        ] + $payload);
    }

    /**
     * Danh sách lô cấu thành một con số cụ thể trên bảng ngày: tồn, nhập, xuất
     * của một nhóm, hoặc sản lượng Pha chế nhập vào — luôn tính trực tiếp chứ
     * không đọc bản chốt, vì bản chốt chỉ lưu số tổng hợp từng ngày chứ không
     * lưu tới mức từng lô.
     */
    public function dayDetail(Request $request)
    {
        if (! $this->canView()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xem chức năng này.'], 403);
        }

        $request->validate([
            'date'       => 'required|date_format:Y-m-d',
            'group_code' => 'required|string|max:8',
            'kind'       => 'required_unless:group_code,SUPPLY|nullable|string|in:stock,in,out',
        ]);

        $productionCode = session('user')['production_code'];
        $kind = $request->group_code === WipCoverageService::SUPPLY ? 'supply' : $request->kind;

        $result = $this->service->dayLots(
            $productionCode,
            Carbon::now(),
            WipCoverageService::DEFAULT_HORIZON_DAYS,
            $request->group_code,
            $request->date,
            $request->kind ?? 'stock'
        );

        return response()->json([
            'success'    => true,
            'date'       => $request->date,
            'group_code' => $request->group_code,
            'kind'       => $kind,
        ] + $result);
    }

    /** Danh sách mã bán thành phẩm đang tồn của một nhóm đích */
    public function detail(Request $request)
    {
        if (! $this->canView()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xem chức năng này.'], 403);
        }

        $request->validate([
            'group_code' => 'required|string|max:4',
        ]);

        $productionCode = session('user')['production_code'];

        $snapshot = DB::table('wip_coverage_snapshots')
            ->where('production_code', $productionCode)
            ->where('next_stage_group_code', $request->group_code)
            ->orderByDesc('snapshot_date')
            ->first();

        // Chưa từng chạy lệnh chốt, hoặc vừa đổi cấu trúc nên bản chốt cũ đã bị
        // xoá, thì tính tại chỗ đúng như view() vẫn làm. Thiếu nhánh này thì bảng
        // tổng có số (vì view() tự tính) mà bảng chi tiết lại trống trơn, người
        // dùng nhìn vào tưởng mất dữ liệu.
        if (! $snapshot) {
            return response()->json([
                'success'       => true,
                'snapshot_date' => null,
                'details'       => $this->liveDetails($productionCode, $request->group_code),
            ]);
        }

        // Mã đang giữ nhiều hàng nhất lên đầu
        $details = DB::table('wip_coverage_snapshot_details')
            ->where('snapshot_id', $snapshot->id)
            ->orderByDesc('stock_dvl')
            ->get()
            ->map(function ($row) {
                $row->batches = json_decode($row->batches ?? '[]', true) ?: [];
                $row->stock_dvl = (float) $row->stock_dvl;
                $row->share_pct = $row->share_pct === null ? null : (float) $row->share_pct;
                return $row;
            });

        return response()->json([
            'success'       => true,
            'snapshot_date' => $snapshot->snapshot_date,
            'details'       => $details,
        ]);
    }

    /**
     * Danh sách mã của một nhóm, tính tại chỗ khi chưa có bản chốt.
     * Gọi thẳng compute() chứ không qua computeLive() vì bảng chi tiết cần cả
     * danh sách lô trong 'batches', thứ mà computeLive() cắt bớt cho nhẹ.
     */
    private function liveDetails(string $productionCode, string $groupCode): array
    {
        $result = $this->service->compute(
            $productionCode,
            Carbon::now(),
            WipCoverageService::DEFAULT_HORIZON_DAYS
        );

        foreach ($result['groups'] as $group) {
            if ($group['group_code'] === $groupCode) {
                return $group['details'];
            }
        }

        return [];
    }

    /** Tính lại tại thời điểm hiện tại, không ghi vào bảng chốt */
    private function computeLive(string $productionCode): array
    {
        $result = $this->service->compute(
            $productionCode,
            Carbon::now(),
            WipCoverageService::DEFAULT_HORIZON_DAYS
        );

        $groups = [];
        foreach ($result['groups'] as $group) {
            // Bảng tóm tắt không cần danh sách lô, cắt bớt cho nhẹ đường truyền
            $group['details'] = array_slice($group['details'], 0, 20);
            foreach ($group['details'] as &$detail) {
                unset($detail['batches']);
            }
            unset($detail);

            $groups[] = $group;
        }

        return [
            'source'       => 'live',
            'snapshot_at'  => $result['snapshot_at'],
            'horizon_days' => $result['horizon_days'],
            'groups'       => $groups,
            'supply'       => $result['supply'],
        ];
    }

    /** Đọc bản chốt gần nhất và dựng lại đúng hình dạng payload của bản tính trực tiếp */
    private function readSnapshot(string $productionCode): ?array
    {
        $latestDate = DB::table('wip_coverage_snapshots')
            ->where('production_code', $productionCode)
            ->max('snapshot_date');

        if (! $latestDate) {
            return null;
        }

        $rows = DB::table('wip_coverage_snapshots')
            ->where('production_code', $productionCode)
            ->where('snapshot_date', $latestDate)
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        // Giữ đúng thứ tự Định hình -> Bao phim -> Đóng gói, nhóm chưa rõ công
        // đoạn sau xuống cuối
        $order = array_flip(WipCoverageService::NEXT_GROUPS);

        $groups = $rows
            ->sortBy(fn($row) => $order[$row->next_stage_group_code] ?? 99)
            ->map(function ($row) {
                $series = json_decode($row->daily_series ?? '[]', true) ?: [];

                $inTotal  = array_sum(array_column($series, 'in_dvl'));
                $outTotal = array_sum(array_column($series, 'out_dvl'));
                $stock    = (float) $row->stock_dvl;

                return [
                    'group_code'          => $row->next_stage_group_code,
                    'group_name'          => WipCoverageService::groupName($row->next_stage_group_code),
                    'stock_dvl'           => $stock,
                    'stock_lots'          => (int) $row->stock_lots,
                    'first_shortage_date' => $row->first_shortage_date,
                    'lowest_stock_dvl'    => $row->lowest_stock_dvl === null ? null : (float) $row->lowest_stock_dvl,
                    'lowest_stock_date'   => $row->lowest_stock_date,
                    'highest_stock_dvl'   => $row->highest_stock_dvl === null ? null : (float) $row->highest_stock_dvl,
                    'highest_stock_date'  => $row->highest_stock_date,
                    'avg_stock_dvl'       => $row->avg_stock_dvl === null ? null : (float) $row->avg_stock_dvl,
                    'in_total_dvl'        => round($inTotal, 2),
                    'out_total_dvl'       => round($outTotal, 2),
                    'is_empty'            => $stock <= 0 && $inTotal <= 0 && $outTotal <= 0,
                    'top_product_code'    => $row->top_product_code,
                    'top_product_dvl'     => $row->top_product_dvl === null ? null : (float) $row->top_product_dvl,
                    'horizon_days'        => (int) $row->horizon_days,
                    'daily_series'        => $series,
                    'details'             => [],
                ];
            })
            ->values()
            ->all();

        return [
            'source'       => 'snapshot',
            'snapshot_at'  => $rows->first()->snapshot_at,
            'horizon_days' => (int) $rows->first()->horizon_days,
            'groups'       => $groups,
            // Chuỗi Pha chế giống nhau trên mọi dòng của cùng ngày chốt
            'supply'       => json_decode($rows->first()->supply_series ?? '[]', true) ?: [],
        ];
    }

    private function canView(): bool
    {
        return user_has_permission(session('user')['userId'], 'layout_wip_coverage', 'boolean');
    }
}
