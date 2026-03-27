<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WeeklyProductionScheduleController extends Controller
{
    public function index(Request $request)
    {
        $production_code = session('user')['production_code'];
        
        // Xử lý ô chọn tuần (format: 2026-W13) hoặc ngày (format: Y-m-d)
        $selectedDate = $request->reportedDate;
        if ($selectedDate && str_contains($selectedDate, '-W')) {
            $parts = explode('-W', $selectedDate);
            $startOfWeek = Carbon::now()->setISODate($parts[0], $parts[1])->startOfWeek(Carbon::MONDAY)->setTime(6, 0, 0);
        } else {
            $selectedDate = $selectedDate ?? Carbon::now()->format('Y-m-d');
            $startOfWeek = Carbon::parse($selectedDate)->startOfWeek(Carbon::MONDAY)->setTime(6, 0, 0);
        }
        $endOfWeek = $startOfWeek->copy()->addDays(7);
        $selectedDate = $startOfWeek->format('o-\WW'); 

        // Tạo mảng 7 ngày để hiển thị header
        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $weekDays[] = [
                'date' => $day->format('Y-m-d'),
                'display' => $day->format('d/m'),
                'label' => $this->getDayLabel($day->dayOfWeek)
            ];
        }

        // Lấy dữ liệu sản xuất (stage_code != 8)
        $datas = DB::table('room as r')
            ->leftJoin('stage_plan as sp', function($join) use ($startOfWeek, $endOfWeek) {
                $join->on('r.id', '=', 'sp.resourceId')
                     ->where('sp.stage_code', '!=', 8) // NOT maintenance
                     ->whereBetween('sp.start', [$startOfWeek, $endOfWeek]);
            })
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('product_name as pn', 'fpc.product_name_id', '=', 'pn.id')
            ->where('r.deparment_code', $production_code)
            ->select(
                'r.id as room_id',
                'r.name as room_name',
                'r.code as room_code',
                'r.stage',
                'r.order_by',
                'sp.id as sp_id',
                'sp.start as planned_start',
                'sp.end as planned_end',
                'sp.stage_code',
                'pn.name as product_name',
                'pm.batch',
                'pm.actual_batch'
            )
            ->orderBy('r.order_by')
            ->orderBy('r.code')
            ->orderBy('sp.start')
            ->get();

        // Xử lý day_key ( shifts starting at 06:00 AM)
        $datas = $datas->map(function($item) {
            if ($item->sp_id) {
                $item->day_key = Carbon::parse($item->planned_start)->isBefore(Carbon::parse($item->planned_start)->setTime(6,0,0)) 
                                ? Carbon::parse($item->planned_start)->subDay()->format('Y-m-d')
                                : Carbon::parse($item->planned_start)->format('Y-m-d');
            }
            return $item;
        });

        $groupedByRoom = $datas->groupBy('room_id');
        
        $displayWeek = "Tuần từ " . $startOfWeek->format('d/m/Y') . " đến " . $startOfWeek->copy()->addDays(6)->format('d/m/Y');
        session()->put(['title' => "LỊCH SẢN XUẤT TUẦN"]);

        return view('pages.MaintenanceSchedual.production_weekly.list', [
            'groupedByRoom' => $groupedByRoom,
            'weekDays' => $weekDays,
            'selectedDate' => $selectedDate,
            'displayWeek' => $displayWeek
        ]);
    }

    private function getDayLabel($dayOfWeek)
    {
        $labels = [
            Carbon::MONDAY => 'Thứ 2',
            Carbon::TUESDAY => 'Thứ 3',
            Carbon::WEDNESDAY => 'Thứ 4',
            Carbon::THURSDAY => 'Thứ 5',
            Carbon::FRIDAY => 'Thứ 6',
            Carbon::SATURDAY => 'Thứ 7',
            Carbon::SUNDAY => 'Chủ Nhật',
        ];
        return $labels[$dayOfWeek] ?? '';
    }
}
