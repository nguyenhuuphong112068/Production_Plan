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
            ->leftJoin('stage_plan as sp', function ($join) use ($startOfWeek, $endOfWeek) {
                $join->on('r.id', '=', 'sp.resourceId')
                    ->where('sp.stage_code', '!=', 8) // NOT maintenance
                    ->where('sp.active', 1)
                    ->where(function ($q) use ($startOfWeek, $endOfWeek) {
                        $q->where(function ($q1) use ($startOfWeek, $endOfWeek) {
                            $q1->where('sp.start', '<', $endOfWeek)
                                ->where('sp.end', '>=', $startOfWeek);
                        })->orWhere(function ($q2) use ($startOfWeek, $endOfWeek) {
                            $q2->whereNotNull('sp.start_clearning')
                                ->where('sp.start_clearning', '<', $endOfWeek)
                                ->where('sp.end_clearning', '>=', $startOfWeek);
                        });
                    });
            })
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('product_name as pn', 'fpc.product_name_id', '=', 'pn.id')
            ->where('r.deparment_code', $production_code)
            ->where('r.stage_code', '!=', 8)
            ->select(
                'r.id as room_id',
                'r.name as room_name',
                'r.code as room_code',
                'r.stage',
                DB::raw("CASE WHEN r.stage_code IN (3, 4) THEN 'Pha chế' ELSE r.stage END as stage_name"),
                'r.order_by',
                'sp.id as sp_id',
                'sp.start as planned_start',
                'sp.end as planned_end',
                'sp.stage_code',
                'sp.title',
                'sp.title_clearning',
                'sp.start_clearning',
                'sp.end_clearning',
                'pn.name as product_name',
                'pm.batch',
                'pm.actual_batch'
            )
            ->orderBy('r.order_by')
            ->orderBy('r.code')
            ->orderBy('sp.start')
            ->get();

        $expandedDatas = collect();
        foreach ($datas as $item) {
            if (!$item->sp_id) {
                $expandedDatas->push($item);
                continue;
            }

            // --- 1. Xử lý sự kiện SẢN XUẤT ---
            if ($item->planned_start && $item->planned_end) {
                $start = Carbon::parse($item->planned_start);
                $end = Carbon::parse($item->planned_end);

                for ($i = 0; $i < 7; $i++) {
                    $dayStartBound = $startOfWeek->copy()->addDays($i);
                    $dayEndBound = $dayStartBound->copy()->addDays(1);

                    if ($start->lt($dayEndBound) && $end->gt($dayStartBound)) {
                        $newItem = clone $item;
                        $newItem->day_key = $dayStartBound->format('Y-m-d');
                        $slotStart = $start->gt($dayStartBound) ? $start : $dayStartBound;
                        $slotEnd = $end->lt($dayEndBound) ? $end : $dayEndBound;
                        $newItem->slot_start = $slotStart->toDateTimeString();
                        $newItem->slot_end   = $slotEnd->toDateTimeString();

                        // Giữ nguyên title hoặc product name
                        $newItem->display_title = $item->product_name ?? $item->title;

                        $expandedDatas->push($newItem);
                    }
                }
            }

            // --- 2. Xử lý sự kiện VỆ SINH ---
            if ($item->start_clearning && $item->end_clearning) {
                $startC = Carbon::parse($item->start_clearning);
                $endC = Carbon::parse($item->end_clearning);

                for ($i = 0; $i < 7; $i++) {
                    $dayStartBound = $startOfWeek->copy()->addDays($i);
                    $dayEndBound = $dayStartBound->copy()->addDays(1);

                    if ($startC->lt($dayEndBound) && $endC->gt($dayStartBound)) {
                        $newItemC = clone $item;
                        $newItemC->day_key = $dayStartBound->format('Y-m-d');
                        $slotStart = $startC->gt($dayStartBound) ? $startC : $dayStartBound;
                        $slotEnd = $endC->lt($dayEndBound) ? $endC : $dayEndBound;
                        $newItemC->slot_start = $slotStart->toDateTimeString();
                        $newItemC->slot_end   = $slotEnd->toDateTimeString();

                        // Thêm prefix (VS)
                        $cleanTitle = $item->title_clearning ?: 'VS';
                        $productPart = $item->product_name ?? $item->title;
                        $newItemC->display_title = "($cleanTitle) " . $productPart;

                        // Đánh dấu là sự kiện vệ sinh để view có thể tô màu nếu cần (tùy chọn)
                        $newItemC->is_cleaning = true;

                        $expandedDatas->push($newItemC);
                    }
                }
            }
        }

        $groupedByRoom = $expandedDatas->groupBy('room_id');

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
