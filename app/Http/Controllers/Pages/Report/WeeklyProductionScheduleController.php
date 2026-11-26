<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use App\Services\RoomOccupancyService;
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

        // Nhận / Trả phòng ở trang Thực Thi Sản Xuất, cùng quy tắc với thanh "▶ đang diễn ra" / "⏳ Chờ XN" của Gantt:
        // - running : đã Nhận phòng, chưa Trả phòng → từ Nhận phòng tới dự kiến trả phòng
        //             (Nhận phòng + thời lượng giữ phòng theo lịch = KT vệ sinh − BĐ; quá lịch thì tới hiện tại).
        // - released: đã Trả phòng, chưa xác nhận hoàn thành ✓✓ → từ Nhận phòng tới Trả phòng.
        // Hai loại này thay cho khối lý thuyết (sản xuất + vệ sinh) của lô.
        $occupancy = app(RoomOccupancyService::class);
        $now = Carbon::now();
        $occupied = []; // sp_id => ['state', 'from', 'to', 'planned_to']
        foreach ($occupancy->runningRooms($production_code) as $plans) {
            foreach ($plans as $p) {
                if ($p->maintenance) {
                    continue;
                }
                $from = Carbon::parse($p->actual_start);
                $plannedEnd = $p->end_clearning ?? $p->end;
                $sec = $p->start && $plannedEnd
                    ? max(0, Carbon::parse($p->start)->diffInSeconds(Carbon::parse($plannedEnd), false)) : 0;
                $plannedTo = $from->copy()->addSeconds((int) $sec);
                $occupied[$p->id] = [
                    'state' => 'running',
                    'from' => $from,
                    'to' => $plannedTo->copy()->max($now),
                    'planned_to' => $plannedTo,
                ];
            }
        }
        foreach ($occupancy->awaitingConfirmation($production_code, $startOfWeek, $endOfWeek) as $p) {
            if ($p->maintenance) {
                continue;
            }
            $occupied[$p->id] = [
                'state' => 'released',
                'from' => Carbon::parse($p->actual_start),
                'to' => Carbon::parse($p->released_at),
                'planned_to' => null,
            ];
        }
        $occupiedIds = array_keys($occupied);

        // Lấy dữ liệu sản xuất (stage_code != 8)
        $datas = DB::table('room as r')
            ->leftJoin('stage_plan as sp', function ($join) use ($startOfWeek, $endOfWeek, $occupiedIds) {
                $join->on('r.id', '=', 'sp.resourceId')
                    ->where('sp.stage_code', '!=', 8) // NOT maintenance
                    ->where('sp.active', 1)
                    ->where(function ($q0) use ($startOfWeek, $endOfWeek, $occupiedIds) {
                        $q0->where(function ($qq) use ($startOfWeek, $endOfWeek) {
                            $qq->where(function ($q) {
                                $q->where('sp.submit', 1)
                                    ->orWhere('sp.finished', 1);
                            })->where(function ($q) use ($startOfWeek, $endOfWeek) {
                                $q->where(function ($q1) use ($startOfWeek, $endOfWeek) {
                                    $q1->where('sp.start', '<', $endOfWeek)
                                        ->where('sp.end', '>=', $startOfWeek);
                                })->orWhere(function ($q2) use ($startOfWeek, $endOfWeek) {
                                    $q2->whereNotNull('sp.start_clearning')
                                        ->where('sp.start_clearning', '<', $endOfWeek)
                                        ->where('sp.end_clearning', '>=', $startOfWeek);
                                })->orWhere(function ($q3) use ($startOfWeek, $endOfWeek) {
                                    $q3->whereNotNull('sp.actual_start')
                                        ->where('sp.actual_start', '<', $endOfWeek)
                                        ->where('sp.actual_end', '>=', $startOfWeek);
                                })->orWhere(function ($q4) use ($startOfWeek, $endOfWeek) {
                                    $q4->whereNotNull('sp.actual_start_clearning')
                                        ->where('sp.actual_start_clearning', '<', $endOfWeek)
                                        ->where('sp.actual_end_clearning', '>=', $startOfWeek);
                                });
                            });
                        });
                        // Lô đang giữ phòng / chờ xác nhận: lấy cả khi lịch lý thuyết nằm ngoài tuần
                        // (khoảng thực tế được cắt theo ngày của tuần ở dưới)
                        if ($occupiedIds) {
                            $q0->orWhereIn('sp.id', $occupiedIds);
                        }
                    });
            })
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('product_name as pn', 'fpc.product_name_id', '=', 'pn.id')
            ->leftJoinSub(
                DB::table('yields')->select('stage_plan_id', DB::raw('SUM(yield) as actual_yield'))->groupBy('stage_plan_id'),
                'y',
                'sp.id',
                '=',
                'y.stage_plan_id'
            )
            ->where('r.deparment_code', $production_code)
            ->where('r.stage_code', '!=', 8)
            ->select(
                'r.id as room_id',
                'r.name as room_name',
                'r.code as room_code',
                'r.stage',
                'r.stage_code as room_stage_code',
                DB::raw("CASE WHEN r.stage_code IN (3, 4) THEN 'Pha chế' ELSE r.stage END as stage_name"),
                'r.order_by',
                'sp.id as sp_id',
                'sp.start as planned_start',
                'sp.end as planned_end',
                'sp.actual_start',
                'sp.actual_end',
                'sp.finished',
                'y.actual_yield as yields',
                'sp.yields_batch_qty',
                'sp.stage_code',
                'sp.nextcessor_code',
                'sp.title',
                'sp.title_clearning',
                'sp.start_clearning',
                'sp.end_clearning',
                'sp.actual_start_clearning',
                'sp.actual_end_clearning',
                'pn.name as product_name',
                'pm.batch',
                'pm.actual_batch'
            )
            ->orderBy('r.order_by')
            ->orderBy('r.code')
            ->orderBy('sp.start')
            ->get();

        $expandedDatas = collect();

        // Cắt khoảng [$from, $to] theo các ngày của tuần (ngày bắt đầu 06:00): mỗi ngày giao nhau là 1 thẻ
        $split = function ($item, Carbon $from, Carbon $to, array $extra) use ($startOfWeek, $expandedDatas) {
            for ($i = 0; $i < 7; $i++) {
                $dayStartBound = $startOfWeek->copy()->addDays($i);
                $dayEndBound = $dayStartBound->copy()->addDays(1);

                if ($from->lt($dayEndBound) && $to->gt($dayStartBound)) {
                    $newItem = clone $item;
                    $newItem->day_key = $dayStartBound->format('Y-m-d');
                    $newItem->slot_start = ($from->gt($dayStartBound) ? $from : $dayStartBound)->toDateTimeString();
                    $newItem->slot_end = ($to->lt($dayEndBound) ? $to : $dayEndBound)->toDateTimeString();
                    foreach ($extra as $key => $value) {
                        $newItem->$key = $value;
                    }
                    $expandedDatas->push($newItem);
                }
            }
        };

        foreach ($datas as $item) {
            if (!$item->sp_id) {
                $expandedDatas->push($item);
                continue;
            }

            $productPart = $item->product_name ?? $item->title;
            $cleanTitle = $item->title_clearning ?: 'VS';
            // Loại nguyên liệu đang cân (chỉ lịch cân, thẻ vệ sinh không gắn — xem $split bên dưới)
            $item->weigh_kind = self::weighKind($item);

            // --- 0. Lô đang giữ phòng / đã Trả phòng chờ xác nhận (trang Thực Thi Sản Xuất) ---
            if (isset($occupied[$item->sp_id])) {
                $occ = $occupied[$item->sp_id];
                $split($item, $occ['from'], $occ['to'], [
                    'display_title' => $productPart,
                    'is_actual' => true,
                    'is_cleaning' => false,
                    'occupancy' => $occ['state'],
                    'received_at' => $occ['from']->toDateTimeString(),
                    'released_at' => $occ['state'] === 'released' ? $occ['to']->toDateTimeString() : null,
                    'expected_release' => $occ['planned_to'] ? $occ['planned_to']->toDateTimeString() : null,
                    'planned_start_val' => $item->planned_start,
                    'planned_end_val' => $item->end_clearning ?? $item->planned_end,
                ]);
                continue;
            }

            // --- 1. Xử lý sự kiện SẢN XUẤT ---
            if ($item->actual_start && $item->actual_end) {
                // Thực tế
                $split($item, Carbon::parse($item->actual_start), Carbon::parse($item->actual_end), [
                    'display_title' => $productPart,
                    'is_actual' => true,
                    'is_cleaning' => false,
                    'planned_start_val' => $item->planned_start,
                    'planned_end_val' => $item->planned_end,
                ]);
            } elseif ($item->planned_start && $item->planned_end) {
                // Lý thuyết
                $split($item, Carbon::parse($item->planned_start), Carbon::parse($item->planned_end), [
                    'display_title' => $productPart,
                    'is_actual' => false,
                    'is_cleaning' => false,
                ]);
            }

            // --- 2. Xử lý sự kiện VỆ SINH ---
            if ($item->actual_start_clearning && $item->actual_end_clearning) {
                // Thực tế
                $split($item, Carbon::parse($item->actual_start_clearning), Carbon::parse($item->actual_end_clearning), [
                    'display_title' => "($cleanTitle) " . $productPart,
                    'is_actual' => true,
                    'is_cleaning' => true,
                    'planned_start_val' => $item->start_clearning,
                    'planned_end_val' => $item->end_clearning,
                ]);
            } elseif ($item->start_clearning && $item->end_clearning) {
                // Lý thuyết
                $split($item, Carbon::parse($item->start_clearning), Carbon::parse($item->end_clearning), [
                    'display_title' => "($cleanTitle) " . $productPart,
                    'is_actual' => false,
                    'is_cleaning' => true,
                ]);
            }
        }

        // Phòng cân (stage 1, 2): các lô cân chung (Nhận phòng / Trả phòng cùng lúc, hoặc lịch lý thuyết ghép cân chung
        // cùng giờ) gộp thành 1 thẻ, số lô nối bằng dấu phẩy. Khoá gộp gồm mọi thứ thẻ hiển thị nên không mất thông tin.
        $expandedDatas = $expandedDatas
            ->groupBy(function ($e) {
                if (!$e->sp_id || !in_array((int) $e->stage_code, [1, 2], true)) {
                    return 'single-' . spl_object_id($e);
                }
                return implode('|', [
                    $e->room_id, $e->day_key, $e->slot_start, $e->slot_end, $e->display_title,
                    $e->occupancy ?? '', $e->received_at ?? '', $e->released_at ?? '', $e->expected_release ?? '',
                    (int) $e->is_actual, (int) $e->is_cleaning, (int) $e->finished,
                    $e->weigh_kind['key'] ?? '',
                ]);
            })
            ->map(function ($group) {
                $card = clone $group->first();
                $card->lot_count = $group->count();
                if ($group->count() > 1) {
                    $card->actual_batch = $group->map(fn($e) => $e->actual_batch ?? $e->batch)->unique()->sort(SORT_NATURAL)->implode(', ');
                    // Giờ lý thuyết (nút Hiện/Ẩn Lý Thuyết) của thẻ gộp: sớm nhất → muộn nhất của các lô
                    $card->planned_start_val = $group->pluck('planned_start_val')->filter()->min();
                    $card->planned_end_val = $group->pluck('planned_end_val')->filter()->max();
                }
                return $card;
            })
            ->values();

        $groupedByRoom = $expandedDatas->groupBy('room_id');

        $displayWeek = "Tuần từ " . $startOfWeek->format('d/m/Y') . " đến " . $startOfWeek->copy()->addDays(6)->format('d/m/Y');
        session()->put(['title' => "LỊCH SẢN XUẤT TUẦN"]);

        return view('pages.MaintenanceSchedual.production_weekly.list', [
            'groupedByRoom' => $groupedByRoom,
            'weekDays' => $weekDays,
            'selectedDate' => $selectedDate,
            'displayWeek' => $displayWeek,
        ]);
    }

    /**
     * Loại nguyên liệu của lịch cân (stage_code 1, 2), cùng quy tắc nhãn phía trên thanh lịch cân trên Gantt
     * (weighKindClass trong resources/js/Plugins/WeighAssist — sửa thì sửa cả 2): theo công đoạn nhận = hậu tố
     * nextcessor_code; thiếu thì theo hậu tố tên lịch "-Nang Rỗng" / "-Tá dược BP".
     *
     * @return array{key: string, label: string}|null
     */
    private static function weighKind(object $item): ?array
    {
        $stage = (int) $item->stage_code;
        if (!in_array($stage, [1, 2], true)) {
            return null;
        }
        $parts = explode('_', (string) ($item->nextcessor_code ?? ''));
        $next = (int) end($parts) ?: null;

        $key = null;
        if ($stage === 1) {
            $key = $next === 4 ? 'tht' : 'pc';
        } elseif ($next === 5) {
            $key = 'nang';
        } elseif ($next === 6) {
            $key = 'bp';
        } elseif ($next === 7) {
            $key = 'dg';
        } elseif (preg_match('/Nang Rỗng$/iu', (string) $item->title)) {
            $key = 'nang';
        } elseif (preg_match('/Tá dược BP$/iu', (string) $item->title)) {
            $key = 'bp';
        }

        $labels = [
            'pc' => '🧪 NL PC',
            'tht' => '🧪 NL THT',
            'nang' => '💊 Nang rỗng',
            'bp' => '🖌️ NL BP',
            'dg' => '📦 NL ĐG',
        ];
        return $key ? ['key' => $key, 'label' => $labels[$key]] : null;
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
