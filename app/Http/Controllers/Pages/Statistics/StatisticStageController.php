<?php

namespace App\Http\Controllers\Pages\Statistics;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticStageController extends Controller
{
    public function index(Request $request){

        
        $production = session('user')['production'];

        // ---- 1. Xác định khoảng thời gian người dùng chọn hoặc mặc định ----
        $fromDate = $request->from_date ?? Carbon::now()->subMonth(1)->toDateString();
        $toDate   = $request->to_date   ?? Carbon::now()->toDateString();


        $fromDate = Carbon::parse($fromDate);
        $toDate   = Carbon::parse($toDate);

        $baseDate = Carbon::parse('2025-08-01');

        // ---- 2. Tính số chu kỳ ----
        $cycleLength = $fromDate->diffInDays($toDate) + 1;
        $totalDays   = $baseDate->diffInDays($toDate);
        $totalHours  = $totalDays * 24;
        $numCycles   = (int) floor($totalDays / $cycleLength);

        // ---- 3. Khởi tạo biến kết quả ----
        $allCycles = [];
        $datas     = [];

        // ---- 4. Lặp qua từng chu kỳ ----
        for ($i = 0; $i <= $numCycles; $i++) {
            $cycleFrom = $fromDate->copy()->subDays($i * $cycleLength);
            $cycleTo   = $toDate->copy()->subDays($i * $cycleLength);

            // --- 4.1 Thống kê thực tế theo stage_code ---
            $cycleDatas = DB::table('stage_plan')
                ->select(
                    'stage_plan.stage_code',
                    DB::raw('COUNT(DISTINCT stage_plan.plan_master_id) as so_lo'),
                    DB::raw('SUM(TIMESTAMPDIFF(HOUR, stage_plan.start_clearning, stage_plan.end_clearning)) as tong_thoi_gian_vesinh'),
                    DB::raw('SUM(TIMESTAMPDIFF(HOUR, stage_plan.start, stage_plan.end)) as tong_thoi_gian_sanxuat'),
                    DB::raw('SUM(stage_plan.yields) as san_luong_thuc_te')
                )
                ->whereBetween('stage_plan.start', [$cycleFrom, $cycleTo])
                ->where('stage_plan.active', 1)
                ->where('stage_plan.deparment_code', $production)
                ->where('stage_plan.finished', 1)
                ->groupBy('stage_plan.stage_code')
                ->get();

            // --- 4.2 Tính sản lượng lý thuyết ---
            $yieldData = $this->yield($cycleFrom, $cycleTo, 'stage_code');

            // --- Merge sản lượng lý thuyết ---
            $cycleDatasArray = $cycleDatas->map(function ($item) use ($yieldData) {
                $yieldItem = $yieldData->where('stage_code', $item->stage_code)->first();
                return array_merge((array)$item, [
                    'san_luong_ly_thuyet' => $yieldItem->total_qty ?? 0,
                ]);
            })->toArray();

            // --- Lưu chi tiết chu kỳ hiện tại ---
            if ($i === 0) {
                $datas = $cycleDatasArray;
            }

            // --- Lưu chi tiết tất cả stage theo chu kỳ ---
            foreach ($cycleDatasArray as $stageItem) {
                $allCycles[] = array_merge($stageItem, [
                    'cycle_index' => -$i,
                    'label' => $cycleFrom->format('d/m/Y') . ' _ ' . $cycleTo->format('d/m/Y'),
                    'from'        => $cycleFrom->toDateString(),
                    'to'          => $cycleTo->toDateString(),
                ]);
            }
        }
        // Chuyển $datas về stdClass
        $datas = array_map(fn($item) => (object) $item, $datas);

        // Chuyển $allCycles về stdClass
        $allCycles = array_map(fn($item) => (object) $item, $allCycles);

        // Nhóm theo stage_code (trở thành stdClass)
        $groupedCycles = collect($allCycles)
            ->groupBy('stage_code')
            ->map(function($group) {
                return $group->map(fn($item) => (object) $item);
            })
            ->toArray();
        // ---- 5. Trả về view ----
        session()->put(['title' => 'THỐNG KÊ THỜI GIAN HOẠT ĐỘNG THEO CÔNG ĐOẠN SẢN XUẤT']);
        //dd ($groupedCycles);
        return view('pages.statistics.stage.list', [
            'datas'     => $datas,
            'groupedCycles' => $groupedCycles,
            'totalHours'   => $totalHours,
        ]);
    }
    
    public function yield($startDate, $endDate, $group_By){
               return DB::table('stage_plan as sp')
                ->leftJoin('intermediate_category as ic', 'sp.product_caterogy_id', '=', 'ic.id')
                ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                ->whereBetween('sp.start', [$startDate, $endDate])
                ->whereNotNull('sp.start')
                ->select(
                    "sp.$group_By",
                    DB::raw('
                        SUM(
                            CASE 
                                WHEN sp.stage_code <= 4 THEN ic.batch_size
                                WHEN sp.stage_code <= 6 THEN ic.batch_qty
                                ELSE fc.batch_qty
                            END
                        ) as total_qty
                    '),
                    DB::raw('
                        CASE 
                            WHEN sp.stage_code <= 4 THEN "Kg"
                            ELSE "ĐVL"
                        END as unit
                    ')
                )
                ->groupBy("sp.$group_By", "unit")
                ->get();
    }
}
