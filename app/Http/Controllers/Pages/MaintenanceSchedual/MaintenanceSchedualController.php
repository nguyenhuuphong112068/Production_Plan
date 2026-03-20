<?php

namespace App\Http\Controllers\Pages\MaintenanceSchedual;

use App\Http\Controllers\Pages\Schedual\SchedualController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MaintenanceSchedualController extends SchedualController
{
    public function index()
    {
        session()->put(['title' => 'LỊCH BẢO TRÌ - HIỆU CHUẨN']);
        return view('app');
    }

    /**
     * Ghi đè phương thức view để chuyên biệt hóa cho Bảo trì.
     */
    public function view(Request $request)
    {
        return parent::view($request);
    }

    /**
     * Ghi đè phương thức store để xử lý lưu lịch bảo trì.
     * Tận dụng parent::store_maintenance() đã có sẵn ở SchedualController.
     */
    public function getPlanWaiting($production, $order_by_type = false)
    {
        return DB::table("stage_plan as sp")
            ->whereNull('sp.start')
            ->where('sp.active', 1)
            ->where('sp.finished', 0)
            ->where('sp.deparment_code', $production)
            ->where('sp.stage_code', 8)
            ->leftJoin('quota_maintenance', 'sp.product_caterogy_id', '=', 'quota_maintenance.id')
            ->select(
                'sp.*',
                'quota_maintenance.inst_id as name',
                'quota_maintenance.inst_id as instrument_code',
                'quota_maintenance.is_HVAC'
            )
            ->get();
    }

    /**
     * Ghi đè getEvents để tập trung vào dữ liệu bảo trì.
     */
    protected function getEvents($production, $startDate, $endDate, $clearning, int $theory)
    {
        $startDate = Carbon::parse($startDate)->toDateTimeString();
        $endDate   = Carbon::parse($endDate)->toDateTimeString();

        $event_plans = DB::table("stage_plan as sp")
            ->where('sp.active', 1)
            ->where('sp.deparment_code', $production)
            ->where('sp.stage_code', 8)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereRaw('(sp.start <= ? AND sp.end >= ?)', [$endDate, $startDate])
                    ->orWhereRaw('(sp.actual_start <= ? AND sp.actual_end >= ?)', [$endDate, $startDate]);
            })
            ->select('sp.*')
            ->get();

        $events = collect();
        foreach ($event_plans as $plan) {
            $events->push([
                'plan_id' => $plan->id,
                'id' => "{$plan->id}-main",
                'title' => $plan->title,
                'start' => $plan->actual_start ?? $plan->start,
                'end' => $plan->actual_end ?? $plan->end,
                'resourceId' => $plan->resourceId,
                'color' => $plan->finished == 1 ? '#002af9ff' : '#4CAF50',
                'textColor' => '#FFFFFF',
                'stage_code' => $plan->stage_code,
                'is_clearning' => false,
                'finished' => $plan->finished,
            ]);
        }
        return $events;
    }

    /**
     * Ở SchedualController cha, getQuota có thể đang lấy quota sản xuất.
     * Ta ghi đè để lấy dữ liệu liên quan bảo trì.
     */
    protected function getQuota($production)
    {
        return DB::table('maintenance_category')->get();
    }
}
