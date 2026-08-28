<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WeeklyProductionScheduleController extends Controller
{
    /** Chức năng "Lead xác nhận lịch" chỉ áp dụng cho PX Viên 1 */
    private const LEAD_CONFIRM_PRODUCTION_CODE = 'PXV1';

    /** Nhóm user được phép bấm xác nhận */
    private const LEAD_CONFIRM_USER_GROUPS = ['Leader', 'Admin'];

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
                    ->where(function($q) {
                        $q->where('sp.submit', 1)
                          ->orWhere('sp.finished', 1);
                    })
                    ->where(function ($q) use ($startOfWeek, $endOfWeek) {
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
                'sp.title',
                'sp.title_clearning',
                'sp.start_clearning',
                'sp.end_clearning',
                'sp.actual_start_clearning',
                'sp.actual_end_clearning',
                'sp.comfirm_of_lead',
                'sp.comfirm_of_lead_by',
                'sp.comfirm_of_lead_at',
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
            if ($item->actual_start && $item->actual_end) {
                // Thực tế
                $startA = Carbon::parse($item->actual_start);
                $endA = Carbon::parse($item->actual_end);

                for ($i = 0; $i < 7; $i++) {
                    $dayStartBound = $startOfWeek->copy()->addDays($i);
                    $dayEndBound = $dayStartBound->copy()->addDays(1);

                    if ($startA->lt($dayEndBound) && $endA->gt($dayStartBound)) {
                        $newItemA = clone $item;
                        $newItemA->day_key = $dayStartBound->format('Y-m-d');
                        $slotStart = $startA->gt($dayStartBound) ? $startA : $dayStartBound;
                        $slotEnd = $endA->lt($dayEndBound) ? $endA : $dayEndBound;
                        $newItemA->slot_start = $slotStart->toDateTimeString();
                        $newItemA->slot_end   = $slotEnd->toDateTimeString();
                        $newItemA->display_title = $item->product_name ?? $item->title;
                        $newItemA->is_actual = true;
                        $newItemA->is_cleaning = false;
                        $newItemA->planned_start_val = $item->planned_start;
                        $newItemA->planned_end_val = $item->planned_end;
                        $expandedDatas->push($newItemA);
                    }
                }
            } elseif ($item->planned_start && $item->planned_end) {
                // Lý thuyết
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
                        $newItem->display_title = $item->product_name ?? $item->title;
                        $newItem->is_actual = false;
                        $newItem->is_cleaning = false;
                        $expandedDatas->push($newItem);
                    }
                }
            }

            // --- 2. Xử lý sự kiện VỆ SINH ---
            if ($item->actual_start_clearning && $item->actual_end_clearning) {
                // Thực tế
                $startCA = Carbon::parse($item->actual_start_clearning);
                $endCA = Carbon::parse($item->actual_end_clearning);

                for ($i = 0; $i < 7; $i++) {
                    $dayStartBound = $startOfWeek->copy()->addDays($i);
                    $dayEndBound = $dayStartBound->copy()->addDays(1);

                    if ($startCA->lt($dayEndBound) && $endCA->gt($dayStartBound)) {
                        $newItemCA = clone $item;
                        $newItemCA->day_key = $dayStartBound->format('Y-m-d');
                        $slotStart = $startCA->gt($dayStartBound) ? $startCA : $dayStartBound;
                        $slotEnd = $endCA->lt($dayEndBound) ? $endCA : $dayEndBound;
                        $newItemCA->slot_start = $slotStart->toDateTimeString();
                        $newItemCA->slot_end   = $slotEnd->toDateTimeString();
                        $cleanTitle = $item->title_clearning ?: 'VS';
                        $productPart = $item->product_name ?? $item->title;
                        $newItemCA->display_title = "($cleanTitle) " . $productPart;
                        $newItemCA->is_actual = true;
                        $newItemCA->is_cleaning = true;
                        $newItemCA->planned_start_val = $item->start_clearning;
                        $newItemCA->planned_end_val = $item->end_clearning;
                        $expandedDatas->push($newItemCA);
                    }
                }
            } elseif ($item->start_clearning && $item->end_clearning) {
                // Lý thuyết
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
                        $cleanTitle = $item->title_clearning ?: 'VS';
                        $productPart = $item->product_name ?? $item->title;
                        $newItemC->display_title = "($cleanTitle) " . $productPart;
                        $newItemC->is_actual = false;
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
            'displayWeek' => $displayWeek,
            // Xác nhận của Lead: chỉ áp dụng cho PX Viên 1
            'leadConfirmEnabled' => $this->isLeadConfirmScope($production_code),
            'canConfirmLead' => $this->canConfirmLead($production_code),
        ]);
    }

    /**
     * Chức năng xác nhận của Lead chỉ áp dụng cho PX Viên 1.
     */
    private function isLeadConfirmScope($production_code)
    {
        return $production_code === self::LEAD_CONFIRM_PRODUCTION_CODE;
    }

    /**
     * Chỉ Lead (và Admin) của PX Viên 1 mới được bấm xác nhận;
     * các user khác vẫn nhìn thấy dấu tick nhưng ở chế độ chỉ đọc.
     */
    private function canConfirmLead($production_code)
    {
        if (! $this->isLeadConfirmScope($production_code)) {
            return false;
        }

        return in_array(session('user')['userGroup'] ?? '', self::LEAD_CONFIRM_USER_GROUPS, true);
    }

    /**
     * Lead xác nhận / bỏ xác nhận sẽ thực hiện theo lịch do người sắp lịch đặt ra.
     * Nhận 1 hoặc nhiều stage_plan_id để hỗ trợ cả xác nhận lẻ và xác nhận hàng loạt.
     */
    public function confirmLead(Request $request)
    {
        $production_code = session('user')['production_code'] ?? null;

        if (! $this->canConfirmLead($production_code)) {
            return response()->json(['message' => 'Bạn không có quyền xác nhận lịch sản xuất.'], 403);
        }

        // Nhận mảng ids[] hoặc chuỗi "1,2,3" (xác nhận hàng loạt gửi dạng chuỗi
        // để không vướng giới hạn max_input_vars của PHP)
        $rawIds = $request->input('ids', []);
        if (is_string($rawIds)) {
            $rawIds = explode(',', $rawIds);
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) $rawIds)
        )));

        if (empty($ids)) {
            return response()->json(['message' => 'Không có lịch nào để xác nhận.'], 422);
        }

        $confirmed = $request->boolean('confirmed');

        // Chỉ cho phép thao tác trên lịch của chính xưởng đang đăng nhập và chưa hoàn thành
        $targetIds = DB::table('stage_plan')
            ->whereIn('id', $ids)
            ->where('deparment_code', $production_code)
            ->where('active', 1)
            ->where('finished', 0)
            ->pluck('id')
            ->all();

        if (empty($targetIds)) {
            return response()->json(['message' => 'Lịch không hợp lệ hoặc đã hoàn thành, không thể xác nhận.'], 422);
        }

        $fullName = session('user')['fullName'] ?? 'NA';
        $now = now();

        DB::table('stage_plan')
            ->whereIn('id', $targetIds)
            ->update([
                'comfirm_of_lead'    => $confirmed ? 1 : 0,
                'comfirm_of_lead_by' => $confirmed ? $fullName : null,
                'comfirm_of_lead_at' => $confirmed ? $now : null,
            ]);

        AuditTrialController::log(
            $confirmed ? 'Lead Xác Nhận Lịch Tuần' : 'Lead Bỏ Xác Nhận Lịch Tuần',
            'stage_plan',
            count($targetIds) === 1 ? $targetIds[0] : 0,
            'comfirm_of_lead=' . ($confirmed ? 0 : 1),
            'comfirm_of_lead=' . ($confirmed ? 1 : 0)
                . ' | ' . count($targetIds) . ' lịch'
                . ' | ids: ' . implode(',', $targetIds)
        );

        return response()->json([
            'ids'                => $targetIds,
            'confirmed'          => $confirmed,
            'comfirm_of_lead_by' => $confirmed ? $fullName : null,
            'comfirm_of_lead_at' => $confirmed ? $now->format('d/m/Y H:i') : null,
            'skipped'            => count($ids) - count($targetIds),
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
