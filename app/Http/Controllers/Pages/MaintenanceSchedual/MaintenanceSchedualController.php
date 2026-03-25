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

    public function getPlanWaiting($production, $order_by_type = false)
    {
        $plans = DB::table("stage_plan as sp")
            ->whereNull('sp.start')
            ->where('sp.active', 1)
            ->where('sp.finished', 0)
            ->where('sp.deparment_code', $production)
            ->where('sp.stage_code', 8)
            ->leftJoin('quota_maintenance', 'sp.product_caterogy_id', '=', 'quota_maintenance.id')
            ->leftJoin('room', 'quota_maintenance.room_id', '=', 'room.id')
            ->select(
                'sp.*',
                'quota_maintenance.inst_id as name',
                'quota_maintenance.inst_id as instrument_code',
                'quota_maintenance.is_HVAC',
                'quota_maintenance.exe_time',
                'room.code as room_code',
                'room.name as room_name'
            )
            ->get();

        $instIds = $plans->pluck('instrument_code')->filter()->unique()->toArray();
        $instruments = collect();

        if (!empty($instIds)) {
            $connections = ['cal1', 'cal2'];
            $suffixes = [1, 2, 3];
            foreach ($connections as $conn) {
                foreach ($suffixes as $suffix) {
                    try {
                        $result = DB::connection($conn)
                            ->table("Inst_Master_{$suffix} as Ins")
                            ->leftJoin("Eqp_mst_{$suffix} as Eqp", 'Eqp.Eqp_ID', '=', 'Ins.Parent_Equip_id')
                            ->whereIn('Ins.Inst_id', $instIds)
                            ->select('Ins.Inst_id', 'Ins.Inst_Name', 'Ins.Parent_Equip_id', 'Eqp.Eqp_name')
                            ->get()
                            ->keyBy('Inst_id');
                        $instruments = $instruments->merge($result);
                    } catch (\Exception $e) {
                        Log::error("Error fetching instrument from {$conn}-{$suffix}: " . $e->getMessage());
                    }
                }
            }
        }

        return $plans->map(function ($item) use ($instruments) {
            $inst = $instruments[$item->instrument_code] ?? null;
            $item->Inst_Name = $inst->Inst_Name ?? $item->instrument_code;
            $item->Parent_Equip_id = $inst->Parent_Equip_id ?? '';
            $item->Eqp_name = $inst->Eqp_name ?? '';

            // Tính tổng thời gian thực hiện
            $item->PM = $item->exe_time ?? '00:00';

            return $item;
        });
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
                $item->PM = $item->exe_time ?? '00:00';
                return $item;
            });

        Log::info($result);
        return $result;
    }


    protected function toMinutes($time)
    {
        [$hours, $minutes] = explode(':', $time);
        return ((int)$hours) * 60 + (int)$minutes;
    }

    public function store(Request $request)
    {
        log::info($request->all());

        DB::beginTransaction();
        try {

            $products = collect($request->products)->sortBy('batch')->values();

            $start = Carbon::parse($request->start);

            // 🔥 KIỂM TRA NGAY TỪ ĐẦU NẾU current_start NẰM TRONG OFFDATE
            foreach ($products as $product) {

                $end = $start->addMinutes($this->toMinutes($product['PM']));
                DB::table('stage_plan')
                    ->where('id', $product['id'])
                    ->update([
                        'start'           => $start,
                        'end'             => $end,
                        // 'start_clearning' => $end_man,
                        // 'end_clearning'   => $end_clearning,
                        'resourceId'      => $request->room_id,
                        'title'           => $product['Parent_Equip_id'] . ' - ' . $product['Eqp_name'] . ' - ' . $product['Inst_Name'] . ' - ' . $product['instrument_code'],
                        'schedualed_by'   => session('user')['fullName'],
                        'schedualed_at'   => now(),

                    ]);

                $submit = DB::table('stage_plan')->where('id', $product['id'])->value('submit');

                if ($submit == 1) {
                    $last_version = DB::table('stage_plan_history')
                        ->where('stage_plan_id', $product['id'])
                        ->max('version') ?? 0;

                    DB::table('stage_plan_history')->insert([
                        'stage_plan_id'  => $product['id'],
                        'version'        => $last_version + 1,
                        'start'           => $start,
                        'end'             => $end,
                        'resourceId'     => $request->room_id,
                        'title'           => $product['Parent_Equip_id'] . ' - ' . $product['Eqp_name'] . ' - ' . $product['Inst_Name'] . ' - ' . $product['instrument_code'],
                        'schedualed_by'   => session('user')['fullName'],
                        'schedualed_at'  => now(),
                        'deparment_code' => session('user')['production_code'],
                        'type_of_change' => $request->reason ?? "Lập Lịch Thủ Công",
                    ]);
                }

                $start = $end;
            }
            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

        /*
                |--------------------------------------------------------------------------
                | TRẢ KẾT QUẢ
                |--------------------------------------------------------------------------
                */
        $production = session('user')['production_code'];
        $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
        $plan_waiting = $this->getPlanWaiting($production);
        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

        return response()->json([
            'events' => $events,
            'plan' => $plan_waiting,
            'sumBatchByStage' => $sumBatchByStage,
        ]);
    }
}
