<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedualStepController extends Controller
{


        public function list(Request $request){
            //dd ($request->all());
            $fromDate = $request->from_date ?? Carbon::now()->subMonth()->toDateString();
            $toDate   = $request->to_date   ?? Carbon::now()->toDateString(); 
            
            // Lấy danh sách stage_name (danh mục stage)
            $stage_name = DB::table('room')
                ->distinct()
                ->select('stage_code', 'stage')
                ->get()
                ->keyBy('stage_code');

            // Lấy dữ liệu stage_plan + filter trước khi get()
            $datas = DB::table('stage_plan')
                ->leftJoin('room', 'stage_plan.resourceId' ,'room.id')
                ->leftJoin('plan_master',  'stage_plan.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id',  'finished_product_category.id')
                ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
                ->leftJoin('market','finished_product_category.market_id','market.id')
                ->select(
                    'stage_plan.plan_master_id as plan_id',
                    'stage_plan.stage_code',
                    'stage_plan.start',
                    'stage_plan.end',
                    'stage_plan.finished',
                    'stage_plan.yields',

                    'room.name as room_name',
                    'room.code as room_code',

                    'plan_master.batch',
                    'plan_master.expected_date',
                    'plan_master.after_weigth_date',
                    'plan_master.before_weigth_date',
                    'plan_master.after_parkaging_date',
                    'plan_master.before_parkaging_date',

                    'finished_product_category.batch_qty',
                    'finished_product_category.unit_batch_qty',
                    'market.name as market',
                    'product_name.name as product_name',
                    DB::raw("
                        CASE 
                            WHEN stage_plan.finished = 1 THEN 'finished'
                            WHEN stage_plan.end IS NOT NULL THEN 'scheduled'
                            ELSE 'pending'
                        END as status
                    ")
                )
                ->whereBetween('stage_plan.created_date', [$fromDate, $toDate])
                ->orderBy('stage_plan.plan_master_id')
                ->orderBy('stage_plan.stage_code')
                ->get()
                ->groupBy('plan_id');
            
            // Map thêm stage_name từ stage_code
            $datas = $datas->map(function ($plans) use ($stage_name) {
                return $plans->map(function ($item) use ($stage_name) {
                    $item->stage_name = $stage_name[$item->stage_code]->stage ?? null;
                    return $item;
                });
            });

            session()->put(['title'=> 'Tiến Trình Sản Xuất']);

            return view('pages.Schedual.step.list', [
                'datas' => $datas,
            ]);
        }


}
