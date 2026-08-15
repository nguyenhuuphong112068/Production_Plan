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
     * Dữ liệu cho cả bảng tóm tắt trong Lịch sản xuất lẫn trang chi tiết.
     * Mặc định đọc bản chốt gần nhất cho nhanh; truyền live=1 để tính lại tại chỗ.
     */
    public function view(Request $request)
    {
        if (! $this->canView()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xem chức năng này.'], 403);
        }

        $productionCode = session('user')['production_code'];
        $thresholds = WipCoverageService::thresholdsFor($productionCode);

        if ($request->boolean('live')) {
            $payload = $this->computeLive($productionCode, $thresholds);
        } else {
            $payload = $this->readSnapshot($productionCode);

            // Chưa từng chạy lệnh chốt thì tính tại chỗ để trang không trống trơn
            if ($payload === null) {
                $payload = $this->computeLive($productionCode, $thresholds);
            }
        }

        return response()->json([
            'success'         => true,
            'production_code' => $productionCode,
            'group_names'     => WipCoverageService::GROUP_NAMES,
            'thresholds'      => array_values(array_map(fn($t) => (array) $t, $thresholds)),
        ] + $payload);
    }

    /** Danh sách mã bán thành phẩm của một nhóm công đoạn */
    public function detail(Request $request)
    {
        if (! $this->canView()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xem chức năng này.'], 403);
        }

        $request->validate([
            'stage_group_code' => 'required|string|max:4',
        ]);

        $productionCode = session('user')['production_code'];
        $groupCode = $request->stage_group_code;

        $snapshot = DB::table('wip_coverage_snapshots')
            ->where('production_code', $productionCode)
            ->where('stage_group_code', $groupCode)
            ->orderByDesc('snapshot_date')
            ->first();

        if (! $snapshot) {
            return response()->json(['success' => true, 'details' => []]);
        }

        // Mã chiếm nhiều giờ máy của công đoạn sau nhất lên đầu
        $details = DB::table('wip_coverage_snapshot_details')
            ->where('snapshot_id', $snapshot->id)
            ->orderByRaw('load_hours IS NULL, load_hours DESC')
            ->get()
            ->map(function ($row) {
                $row->batches = json_decode($row->batches ?? '[]', true) ?: [];
                $row->stock_dvl = (float) $row->stock_dvl;
                $row->load_hours = $row->load_hours === null ? null : (float) $row->load_hours;
                $row->days_of_cover = $row->days_of_cover === null ? null : (float) $row->days_of_cover;
                return $row;
            });

        return response()->json([
            'success'       => true,
            'snapshot_date' => $snapshot->snapshot_date,
            'details'       => $details,
        ]);
    }

    /** Xu hướng số ngày đáp ứng của 30 bản chốt gần nhất */
    public function history(Request $request)
    {
        if (! $this->canView()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xem chức năng này.'], 403);
        }

        $productionCode = session('user')['production_code'];

        $rows = DB::table('wip_coverage_snapshots')
            ->where('production_code', $productionCode)
            ->when($request->filled('stage_group_code'), fn($q) => $q->where('stage_group_code', $request->stage_group_code))
            ->orderByDesc('snapshot_date')
            ->limit(30 * count(WipCoverageService::SOURCE_GROUPS))
            ->get(['snapshot_date', 'stage_group_code', 'days_of_cover', 'stock_dvl', 'status']);

        // Gom theo ngày để vẽ nhiều đường trên cùng một trục thời gian
        $byDate = [];
        foreach ($rows->sortBy('snapshot_date') as $row) {
            $date = $row->snapshot_date;
            $byDate[$date]['date'] = $date;
            $byDate[$date][$row->stage_group_code] = $row->days_of_cover === null ? null : (float) $row->days_of_cover;
        }

        return response()->json([
            'success' => true,
            'history' => array_values($byDate),
        ]);
    }

    /** Tính lại tại thời điểm hiện tại, không ghi vào bảng chốt */
    private function computeLive(string $productionCode, array $thresholds): array
    {
        $horizon = collect($thresholds)
            ->map(fn($t) => (int) ($t->horizon_days ?? WipCoverageService::DEFAULT_HORIZON_DAYS))
            ->max() ?: WipCoverageService::DEFAULT_HORIZON_DAYS;

        $result = $this->service->compute($productionCode, Carbon::now(), $horizon);

        $groups = [];
        foreach ($result['groups'] as $group) {
            $group['status'] = WipCoverageService::resolveStatus(
                $group['days_of_cover'],
                $thresholds[$group['stage_group_code']] ?? null,
                $group['has_demand']
            );

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
        ];
    }

    /** Đọc bản chốt gần nhất. Trạng thái đã được lệnh chạy nền gán sẵn theo ngưỡng. */
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

        // Giữ đúng thứ tự công đoạn thay vì thứ tự chữ cái
        $order = array_flip(WipCoverageService::SOURCE_GROUPS);

        $groups = $rows
            ->sortBy(fn($row) => $order[$row->stage_group_code] ?? 99)
            ->map(function ($row) {
                $series = json_decode($row->daily_series ?? '[]', true) ?: [];
                $demandTotal = array_sum(array_column($series, 'out_dvl'));
                $stock = (float) $row->stock_dvl;
                $loadHours = (float) ($row->load_hours ?? 0);

                return [
                    'stage_group_code'      => $row->stage_group_code,
                    'stage_group_name'      => WipCoverageService::GROUP_NAMES[$row->stage_group_code] ?? $row->stage_group_code,
                    'next_stage_group_code' => $row->next_stage_group_code,
                    'next_stage_group_name' => WipCoverageService::GROUP_NAMES[$row->next_stage_group_code] ?? null,
                    'stock_dvl'             => $stock,
                    'stock_lots'            => (int) $row->stock_lots,
                    'orphan_lots'           => (int) $row->orphan_lots,
                    'days_of_cover'         => $row->days_of_cover === null ? null : (float) $row->days_of_cover,
                    'first_shortage_date'   => $row->first_shortage_date,
                    'lowest_stock_dvl'      => $row->lowest_stock_dvl === null ? null : (float) $row->lowest_stock_dvl,
                    'lowest_stock_date'     => $row->lowest_stock_date,
                    'max_product_days'      => $row->top_product_days === null ? null : (float) $row->top_product_days,
                    'max_product_code'      => $row->top_product_code,
                    'status'                => $row->status,
                    'horizon_days'          => (int) $row->horizon_days,
                    'demand_total_dvl'      => round($demandTotal, 2),
                    'load_hours'            => $loadHours,
                    'capacity_basis'        => json_decode($row->capacity_basis ?? '[]', true) ?: [],
                    'has_demand'            => $loadHours > 0,
                    'is_empty'              => $stock <= 0 && $loadHours <= 0,
                    'scheduled_demand_pct'  => $stock > 0 ? round($demandTotal / $stock * 100, 1) : null,
                    'daily_series'          => $series,
                    'details'               => [],
                ];
            })
            ->values()
            ->all();

        return [
            'source'       => 'snapshot',
            'snapshot_at'  => $rows->first()->snapshot_at,
            'horizon_days' => (int) $rows->first()->horizon_days,
            'groups'       => $groups,
        ];
    }

    private function canView(): bool
    {
        return user_has_permission(session('user')['userId'], 'layout_wip_coverage', 'boolean');
    }
}
