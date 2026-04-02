<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceivePackagingController extends Controller
{
    public function list(Request $request)
    {
        //dd ($request->all());

        $fromDate = $request->from_date ?? Carbon::now()->toDateString();
        $toDate   = $request->to_date   ?? Carbon::now()->addDays(7)->toDateString();

        $production = session('user')['production_code'];

        $datas = DB::table('stage_plan')
            ->select(
                'stage_plan.*',
                'room.name as room_name',
                'room.code as room_code',
                'room.stage as stage',
                DB::raw("COALESCE(plan_master.actual_batch, plan_master.batch) AS batch"),
                'plan_master.expected_date',
                'plan_master.is_val',
                'finished_product_category.intermediate_code',
                'finished_product_category.finished_product_code',
                'finished_product_category.batch_qty',
                'finished_product_category.unit_batch_qty',
                'market.name as market',
                'market.code as market_code',
                'product_name.name as product_name',
                'specification.name as specification',

                //DB::raw("DATE(DATE_SUB(stage_plan.start, INTERVAL 2 DAY)) as receive_date")
            )
            ->whereBetween('stage_plan.start', [$fromDate, $toDate])
            ->where('stage_plan.active', 1)
            ->where('stage_plan.stage_code', 7)
            ->where('stage_plan.deparment_code', $production)->where('stage_plan.finished', 0)->whereNotNull('stage_plan.start')
            ->when(!in_array(session('user')['userGroup'], ['Schedualer', 'Admin', 'Leader']), fn($query) => $query->where('submit', 1))
            ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
            ->leftJoin('plan_master', 'stage_plan.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
            ->leftJoin('specification', 'finished_product_category.specification_id', '=', 'specification.id')
            ->orderBy('receive_packaging_date', 'asc')
            ->orderBy('received', 'asc')
            ->get();

        $planMasterIds = $datas->pluck('plan_master_id')->unique();

        $comments = DB::table('receive_packaging_comments as c')
            ->leftJoin('user_management as u', 'c.user_id', '=', 'u.id')
            ->whereIn('c.plan_master_id', $planMasterIds)
            ->select(
                'c.plan_master_id',
                'c.message',
                'c.created_at',
                'u.fullName as user_name',
                'u.deparment as deparment'
            )
            ->orderBy('c.created_at', 'desc')
            ->get()
            ->groupBy('plan_master_id');

        foreach ($datas as $data) {
            $data->comments = $comments[$data->plan_master_id] ?? collect();
        }

        session()->put(['title' => 'LỊCH NHẬN BAO BÌ']);
        return view('pages.Schedual.receive_packaging.list', [

            'datas' => $datas,
        ]);
    }

    public function store(Request $request)
    {

        Log::info($request->all());
        $message = $request->message;
        $id = DB::table('receive_packaging_comments')->insertGetId([
            'plan_master_id'     => $request->plan_master_id,
            'user_id'    => session('user')['userId'],
            'message'    => $message,
            'created_at' => now(),
        ]);

        // --- XỬ LÝ GỬI THÔNG BÁO CHO NGƯỜI ĐƯỢC TAG ---
        // Tìm định dạng @Tên[userId]
        preg_match_all('/@.*?\[(\d+)\]/', $message, $matches);
        if (!empty($matches[1])) {
            $taggedUserIds = array_unique($matches[1]);

            // Lấy thông tin Lô để nội dung thông báo rõ ràng hơn
            $planInfo = DB::table('plan_master')->where('id', $request->plan_master_id)->first();
            $batchStr = $planInfo ? " (Lô: $planInfo->batch)" : "";

            \App\Http\Controllers\General\NotificationController::sendNotification(
                session('user')['fullName'] . " đã nhắc lời bạn trong trao đổi sản xuất" . $batchStr . ": " . $message,
                'Nhắc tên',
                $request->plan_master_id,
                $taggedUserIds,
                [],
                route('pages.Schedual.receive_packaging.list') . "?plan_master_id=" . $request->plan_master_id
            );
        }

        return response()->json([
            'user_name' => session('user')['fullName'],
            'message'   => $request->message,
            'time'      => now()->format('d/m H:i'),
            'department' => session('user')['department']
        ]);
    }


    public function updateInput(Request $request)
    {
        $id    = $request->stage_plan_id;
        $name  = $request->name;
        $value = $request->updateValue;

        DB::table('stage_plan')
            ->where('id', $id)
            ->update([$name => $value]);

        if (in_array($name, ['receive_packaging_date', 'receive_second_packaging_date'])) {
            $type = ($name == 'receive_packaging_date') ? 0 : 1;
            $this->syncPackagingDate($id, $value, $type);
        }

        return response()->json(['updateValue' => $value]);
    }

    protected function syncPackagingDate($stagePlanId, $date, $type)
    {
        $latest = DB::table('packaging_issuance_date')
            ->where('stage_plane_id', $stagePlanId)
            ->where('type_packaging', $type)
            ->orderBy('ver', 'desc')
            ->first();

        if (!$latest || $latest->receive_packaging_date != $date) {
            DB::table('packaging_issuance_date')->insert([
                'stage_plane_id'         => $stagePlanId,
                'type_packaging'         => $type,
                'receive_packaging_date' => $date,
                'ver'                    => ($latest->ver ?? 0) + 1,
                'created_at'             => now(),
                'created_by'             => session('user')['fullName'] ?? 'System'
            ]);
        }
    }

    public function received(Request $request)
    {
        DB::table('stage_plan')
            ->where('id', $request->plan_master_id)
            ->update([
                $request->btn => 1,
                $request->confirm_by => session('user')['fullName'],
                $request->confirm_date => now(),
            ]);
        return response()->json([
            'confirm_by' =>  session('user')['fullName'],
            'confirm_date' => now()->format('d/m/Y'),
        ]);
    }

    public function getHistory(Request $request)
    {
        $history = DB::table('packaging_issuance_date')
            ->where('stage_plane_id', $request->stage_plan_id)
            ->where('type_packaging', $request->type_packaging)
            ->orderBy('ver', 'desc')
            ->get();

        return response()->json($history);
    }
}
