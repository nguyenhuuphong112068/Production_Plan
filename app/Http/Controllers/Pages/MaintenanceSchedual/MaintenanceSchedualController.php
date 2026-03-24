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


        $startDate = $request->startDate ?? Carbon::now();
        $endDate = $request->endDate ?? Carbon::now()->addDays(7);
        $viewtype = $request->viewtype ?? "resourceTimelineWeek";
        $this->theory = (int)$request->theory ?? 0;

        try {
            $production = session('user')['production_code'];
            $department = DB::table('user_management')->where('userName', session('user')['userName'])->value('deparment');

            $clearing = $request->clearning ?? true;

            if ($viewtype == "resourceTimelineQuarter") {
                $clearing = false;
            }

            if (user_has_permission(session('user')['userId'], 'loading_plan_waiting', 'boolean')) {
                $plan_waiting = $this->getPlanWaiting($production);
                $bkc_code = DB::table('stage_plan_bkc')->where('deparment_code', session('user')['production_code'])->select('bkc_code')->distinct()->orderByDesc('bkc_code')->get();
                $reason = DB::table('reason')->where('deparment_code', $production)->pluck('name');
                $quota = parent::getQuota($production);
            }


            $stageMap = DB::table('room')->where('deparment_code', $production)->pluck('stage_code', 'stage')->toArray();

            $events = parent::getEvents($production, $startDate, $endDate, $clearing, $this->theory);

            $sumBatchByStage = parent::yield($startDate, $endDate, "stage_code");

            $resources = parent::getResources($production, $startDate, $endDate);





            $type = true;

            $Lines = DB::table('room')
                ->select('stage_code', 'name', 'code')
                ->where('deparment_code', $production)
                ->whereIn('stage_code', [3, 4, 5, 6, 7])
                ->where('active', 1)
                ->orderBy('order_by')
                ->get()
                ->groupBy('stage_code')
                ->map(function ($items) {
                    return $items->map(function ($room) {
                        return [
                            'name'      => $room->code,
                            'name_code' => $room->code . ' - ' . $room->name,
                        ];
                    })->values();
                });

            $allLines = DB::table('room')
                ->select('stage_code', 'name', 'code')
                ->where('deparment_code', $production)
                ->whereIn('stage_code', [3, 4, 5, 6, 7])
                ->where('active', 1)
                ->orderBy('order_by')
                ->get();





            $authorization = session('user')['userGroup'];
            $UesrID =  session('user')['userId'];

            return response()->json([
                //'title' => $title,
                'events' => $events,
                'plan' => $plan_waiting ?? [],
                'quota' => $quota ?? [],
                'stageMap' => $stageMap ?? [],
                'resources' => $resources ?? [],
                'sumBatchByStage' =>  $sumBatchByStage ?? [],
                'reason' => $reason ?? [],
                'type' => $type,
                'authorization' => $authorization,
                'production' => $production,
                'department' => $department,
                'currentPassword' => session('user')['passWord'] ?? '',
                'Lines'       => $Lines ?? [],
                'allLines' => $allLines ?? [],
                'off_days' => DB::table('off_days')->where('off_date', '>=', now())->get()->pluck('off_date') ?? [],
                'bkc_code' => $bkc_code ?? [],
                'UesrID' => $UesrID
            ]);
        } catch (\Throwable $e) {
            // Ghi log chi tiết lỗi
            Log::error('Error in view(): ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ghi đè phương thức store để xử lý lưu lịch bảo trì.
     * Tận dụng parent::store_maintenance() đã có sẵn ở SchedualController.
     */
    public function store(Request $request)
    {
        return $this->store_maintenance($request);
    }
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
     * Ở SchedualController cha, getQuota có thể đang lấy quota sản xuất.
     * Ta ghi đè để lấy dữ liệu liên quan bảo trì.
     */
    protected function getQuota($production)
    {
        $result = DB::table('quota_maintenance as q ')
            ->leftJoin('room', 'q.room_id', '=', 'room.id')
            //->where('q.active', 1)
            ->where('q.deparment_code', $production)
            ->get()
            ->map(function ($item) {
                $toSeconds = fn($time) => (($h = (int)explode(':', $time)[0]) * 3600) + ((int)explode(':', $time)[1] * 60);
                $toTime = fn($seconds) => sprintf('%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60));
                $item->PM = $toTime($toSeconds($item->p_time) + $toSeconds($item->m_time));
                return $item;
            });

        Log::info($result);
        return $result;
    }
}
