<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ghi một phiên bản lịch vào stage_plan_history.
 *
 * Tách từ SchedualController::update() để các luồng đổi lịch khác
 * (tịnh tuyến theo hoàn thành, hoàn tác tịnh tuyến) ghi cùng một định dạng.
 */
class StagePlanHistory
{
    /**
     * @param  object  $row  Dòng stage_plan SAU khi đã cập nhật
     */
    public static function record(object $row, ?string $typeOfChange): void
    {
        try {
            DB::table('stage_plan_history')->insert([
                'stage_plan_id' => $row->id,
                'plan_list_id' => $row->plan_list_id,
                'plan_master_id' => $row->plan_master_id,
                'product_caterogy_id' => $row->product_caterogy_id,
                'campaign_code' => $row->campaign_code,
                'code' => $row->code,
                'order_by' => $row->order_by,
                'schedualed' => $row->schedualed,
                'stage_code' => $row->stage_code,
                'title' => $row->title,
                'start' => $row->start,
                'end' => $row->end,
                'resourceId' => $row->resourceId,
                'title_clearning' => $row->title_clearning,
                'start_clearning' => $row->start_clearning,
                'end_clearning' => $row->end_clearning,
                'tank' => $row->tank,
                'keep_dry' => $row->keep_dry,
                'AHU_group' => $row->AHU_group,
                'schedualed_by' => $row->schedualed_by,
                'schedualed_at' => $row->schedualed_at,
                'version' => (DB::table('stage_plan_history')->where('stage_plan_id', $row->id)->max('version') ?? 0) + 1,
                'note' => $row->note,
                'deparment_code' => session('user.production_code') ?? $row->deparment_code,
                'type_of_change' => $typeOfChange,
                'created_date' => now(),
                'created_by' => session('user')['fullName'] ?? 'System',
            ]);
        } catch (\Exception $e) {
            Log::error('[History Debug] INSERT FAILED for sid=' . $row->id, ['error' => $e->getMessage()]);
        }
    }
}
