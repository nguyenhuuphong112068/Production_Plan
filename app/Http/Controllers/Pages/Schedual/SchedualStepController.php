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
            $toDate   = $request->to_date   ?? Carbon::now()->addMonth(); 
            $production = session ('user')['production_code'];
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
                    'stage_plan.id',
                    'stage_plan.plan_master_id',
                    'stage_plan.stage_code',
                    'stage_plan.start',
                    'stage_plan.end',
                    'stage_plan.start_clearning',
                    'stage_plan.end_clearning',
                    'stage_plan.finished',
                    'stage_plan.yields',
                    DB::raw("CONCAT(room.name,'-', room.code) as room_name"),
                    //'room.name as room_name',
                    //'room.code as room_code',
                    'plan_master.batch',
                    'plan_master.expected_date',
                    'plan_master.after_weigth_date',
                    'plan_master.before_weigth_date',
                    'plan_master.after_parkaging_date',
                    'plan_master.before_parkaging_date',
                    'plan_master.only_parkaging',
                    'plan_master.main_parkaging_id',
                    'plan_master.percent_parkaging',
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
                ->where ('stage_plan.active', 1)
                ->where ('stage_plan.deparment_code', $production)
                ->where ('plan_master.only_parkaging',  0)
                ->orderBy('stage_plan.plan_master_id')
                ->orderBy('stage_plan.stage_code')
                ->get()
                ->groupBy('plan_master_id');
 
            $datas_only_parkaging = DB::table('stage_plan')
                ->leftJoin('room', 'stage_plan.resourceId' ,'room.id')
                ->leftJoin('plan_master',  'stage_plan.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id',  'finished_product_category.id')
                ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
                ->leftJoin('market','finished_product_category.market_id','market.id')
                ->select(
                    'stage_plan.id',
                    'stage_plan.plan_master_id',
                    'stage_plan.stage_code',
                    'stage_plan.start',
                    'stage_plan.end',
                    'stage_plan.start_clearning',
                    'stage_plan.end_clearning',
                    'stage_plan.finished',
                    'stage_plan.yields',
                    DB::raw("CONCAT(room.name,'-', room.code) as room_name"),
                    // 'room.name as room_name',
                    // 'room.code as room_code',
                    'plan_master.batch',
                    'plan_master.expected_date',
                    'plan_master.after_weigth_date',
                    'plan_master.before_weigth_date',
                    'plan_master.after_parkaging_date',
                    'plan_master.before_parkaging_date',
                    'plan_master.only_parkaging',
                    'plan_master.main_parkaging_id',
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
                ->where ('stage_plan.active', 1)
                ->where ('stage_plan.deparment_code', $production)
                ->where ('plan_master.only_parkaging',  1)
                ->orderBy('stage_plan.plan_master_id')
                ->orderBy('stage_plan.stage_code')
                ->get();
                

                    // --- 3. Map thêm stage_name + ghép dữ liệu phụ ---
            $datas = $datas->map(function ($plans) use ($stage_name, $datas_only_parkaging) {

                $plans = $plans->map(function ($item) use ($stage_name) {
                    $item->stage_name = $stage_name[$item->stage_code]->stage ?? null;
                    return $item;
                });
                
                // Lấy plan_master đầu tiên để kiểm tra percent_parkaging
                $main = $plans->first();
                
                // Nếu percent_parkaging < 1 → lấy các stage đóng gói phụ tương ứng (main_parkaging_id)
                if ($main && $main->percent_parkaging < 1) {
                    
                    $extraStages = $datas_only_parkaging
                        ->where('main_parkaging_id', $main->plan_master_id)
                    ->values();
                    

                    if ($extraStages->isNotEmpty()) {
                        // map thêm stage_name
                        $extraStages = $extraStages->map(function ($item) use ($stage_name) {
                            $item->stage_name = $stage_name[$item->stage_code]->stage ?? null;
                            return $item;
                        });

                        // Gộp thêm vào cuối
                        $plans = $plans->merge($extraStages);
                        
                    }
                }

                return $plans->values();
            });


            
            //dd ($datas);

            session()->put(['title'=> 'Tiến Trình Sản Xuất']);
            //dd ($datas);
            return view('pages.Schedual.step.list', [
                'datas' => $datas,
            ]);
        }


}
