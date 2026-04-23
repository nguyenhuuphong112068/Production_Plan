<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceWeeklyReportController extends Controller
{
    public function index(Request $request)
    {
        $production_code = session('user')['production_code'];

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

        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $weekDays[] = [
                'date' => $day->format('Y-m-d'),
                'display' => $day->format('d/m'),
                'label' => $this->getDayLabel($day->dayOfWeek)
            ];
        }

        // 1. Lấy tất cả sự kiện bảo trì trong tuần (bao gồm cả PX)
        $maintenanceEvents = DB::table('stage_plan as sp')
            ->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
            ->where('sp.stage_code', 8)
            ->whereBetween('sp.start', [$startOfWeek, $endOfWeek])
            ->where(function ($q) use ($production_code) {
                $q->where('sp.deparment_code', $production_code)
                  ->orWhereExists(function ($query) use ($production_code) {
                      $query->select(DB::raw(1))
                            ->from('room')
                            ->whereColumn('room.id', 'sp.resourceId')
                            ->where('room.deparment_code', $production_code);
                  });
            })
            ->select(
                'sp.resourceId as room_id',
                'sp.id as sp_id',
                'sp.start as planned_start',
                'sp.end as planned_end',
                'sp.finished',
                'qm.inst_id',
                'qm.inst_name',
                'qm.Eqp_name',
                'qm.parent_eqp_id',
                'qm.block',
                DB::raw("CASE WHEN qm.block LIKE 'TI-%' THEN 'Tiện ích' 
                              WHEN qm.block LIKE 'HC-%' THEN 'Hiệu chuẩn' 
                              ELSE 'Bảo Trì' END as type_name")
            )
            ->get();

        // 2. Lấy danh sách phòng
        $rooms = DB::table('room as r')
            ->where('r.deparment_code', $production_code)
            ->select('id as room_id', 'name as room_name', 'code as room_code', 'stage', 'order_by')
            ->get();

        // 3. Xử lý gộp dữ liệu
        $datas = collect();

        // Thêm phòng và map sự kiện
        foreach ($rooms as $room) {
            $roomEvents = $maintenanceEvents->where('room_id', $room->room_id);
            if ($roomEvents->isEmpty()) {
                // Thêm dòng trống nếu phòng không có bảo trì
                $datas->push((object) array_merge((array)$room, [
                    'sp_id' => null,
                    'type_name' => null
                ]));
            } else {
                foreach ($roomEvents as $ev) {
                    $datas->push((object) array_merge((array)$room, (array)$ev));
                }
            }
        }

        // 4. Xử lý riêng trường hợp PX (resourceId = 0)
        $pxEvents = $maintenanceEvents->where('room_id', 0);
        if ($pxEvents->isNotEmpty()) {
            $pxRoom = (object)[
                'room_id' => 0,
                'room_code' => 'PX',
                'room_name' => 'TOÀN PHÂN XƯỞNG (PX)',
                'stage' => 'Bảo trì chung',
                'order_by' => -1 // Cho lên đầu
            ];
            foreach ($pxEvents as $ev) {
                $datas->push((object) array_merge((array)$pxRoom, (array)$ev));
            }
        }

        $datas = $datas->map(function ($item) {
            if (isset($item->sp_id) && $item->sp_id) {
                $item->day_key = Carbon::parse($item->planned_start)->isBefore(Carbon::parse($item->planned_start)->setTime(6, 0, 0))
                    ? Carbon::parse($item->planned_start)->subDay()->format('Y-m-d')
                    : Carbon::parse($item->planned_start)->format('Y-m-d');
            }
            return $item;
        });

        $groupedByRoom = $datas->groupBy('room_id');

        $displayWeek = "Tuần từ " . $startOfWeek->format('d/m/Y') . " đến " . $startOfWeek->copy()->addDays(6)->format('d/m/Y');
        session()->put(['title' => "LỊCH BẢO TRÌ TUẦN"]);

        return view('pages.MaintenanceSchedual.maintenance_weekly.list', [
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
