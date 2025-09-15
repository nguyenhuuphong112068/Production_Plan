<?php
namespace App\Http\Controllers\Pages\Statistics;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticStageController extends Controller
{
    public function index(Request $request){

        
        $production = session('user')['production_code'];

        // ---- 1. Xác định khoảng thời gian người dùng chọn hoặc mặc định ----
        $fromDate = $request->from_date ?? Carbon::now()->subMonth(1)->toDateString(); 
        $toDate   = $request->to_date   ?? Carbon::now()->addMonth(1)->toDateString();


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
            $cycleDatas = DB::table('stage_plan as sp')
                ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                ->leftJoin('intermediate_category as ic', 'ic.intermediate_code', '=', 'fc.intermediate_code')
                ->select(
                    'sp.stage_code',
                    DB::raw('COUNT(DISTINCT sp.plan_master_id) as so_lo'),
                    DB::raw('SUM(TIMESTAMPDIFF(HOUR, sp.start_clearning, sp.end_clearning)) as tong_thoi_gian_vesinh'),
                    DB::raw('SUM(TIMESTAMPDIFF(HOUR, sp.start, sp.end)) as tong_thoi_gian_sanxuat'),
                    DB::raw('SUM(sp.yields) as san_luong_thuc_te'),
                    DB::raw('
                    SUM(
                        CASE 
                            WHEN sp.stage_code <= 4 THEN ic.batch_size
                            WHEN sp.stage_code <= 6 THEN ic.batch_qty
                            ELSE fc.batch_qty
                        END
                    ) as san_luong_ly_thuyet
                ')
                )
                ->whereBetween('sp.start', [$cycleFrom, $cycleTo])
                ->where('sp.active', 1)
                ->where('sp.deparment_code', $production)
                ->where('sp.finished', 1)
                ->groupBy('sp.stage_code')
                ->get();
           
            // --- Lưu chi tiết chu kỳ hiện tại ---
            if ($i === 0) {
                $datas = $cycleDatas;
            }

            // --- Lưu chi tiết tất cả stage theo chu kỳ ---
            foreach ($cycleDatas as $stageItem) {
                $allCycles[] = array_merge((array) $stageItem, [
                    'cycle_index' => -$i,
                    'label'       => $cycleFrom->format('d/m/Y') . ' _ ' . $cycleTo->format('d/m/Y'),
                    'from'        => $cycleFrom->toDateString(),
                    'to'          => $cycleTo->toDateString(),
                ]);
            }
        }
        // Chuyển $datas về stdClass
        $datas     = collect($datas)->map(fn($item) => (object) $item);
        $allCycles = collect($allCycles)->map(fn($item) => (object) $item);

        $groupedCycles = collect($allCycles)
        ->groupBy('stage_code')
        ->map(fn($group) => $group->map(fn($item) => (object) $item));
        
        // ---- 5. Trả về view ----
        session()->put(['title' => 'THỐNG KÊ THỜI GIAN HOẠT ĐỘNG THEO CÔNG ĐOẠN SẢN XUẤT']);
        //dd ($groupedCycles);
        return view('pages.statistics.stage.list', [
            'datas'     => $datas,
            'groupedCycles' => $groupedCycles,
            'totalHours'   => $totalHours,
        ]);
    }
    
 
}
