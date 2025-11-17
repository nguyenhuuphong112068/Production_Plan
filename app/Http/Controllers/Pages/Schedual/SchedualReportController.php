<?php

namespace App\Http\Controllers\Pages\Schedual;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedualReportController extends Controller
{
    
    



    public function list(Request $request){
               

                $fromDate = $request->from_date ?? Carbon::now()->toDateString();
                $toDate   = $request->to_date   ?? Carbon::now()->addMonth(2)->toDateString(); 
                $stage_code = $request->stage_code??3;
                $production = session('user')['production_code'];
      
                // $number_of_batch = DB::table('stage_plan')
                // ->select('stage_plan.*',
                //         'room.name as room_name',
                //         'room.code as room_code',
                //         'room.stage as stage',
                //         'plan_master.batch',
                //         'plan_master.expected_date',
                //         'plan_master.is_val',
                //         'finished_product_category.intermediate_code',
                //         'finished_product_category.finished_product_code',
                //         'finished_product_category.batch_qty',
                //         'finished_product_category.unit_batch_qty',
                //         'market.name as name'
                // )
                // ->whereBetween('stage_plan.start', [$fromDate, $toDate])
                // ->where('stage_plan.active', 1)->where ('stage_plan.stage_code', $stage_code)
                // ->where('stage_plan.deparment_code', $production)->where('stage_plan.finished', 0)->whereNotNull('stage_plan.start')
                // ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
                // ->leftJoin('plan_master', 'stage_plan.plan_master_id', 'plan_master.id')
                // ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
                // ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
                // ->leftJoin('market','finished_product_category.market_id','market.id')
                // ->get();

                $number_of_batch = DB::table('stage_plan')
                ->whereBetween('stage_plan.start', [$fromDate, $toDate])
                ->where('stage_plan.active', 1)->where('stage_plan.finished', 0)
                ->distinct('plan_master_id')
                ->count ('plan_master_id');
                $datas['Số Lượng Lô Chờ Sản Xuất'] = $number_of_batch;

                $stageNames = [
                    3 => 'Số Lượng Lô Pha Chế',
                    4 => 'Số Lượng Lô Trộn Hoàn Tất',
                    5 => 'Số Lượng Lô Dập Viên',
                    6 => 'Số Lượng Lô Bao Phim',
                    7 => 'Số Lượng Lô Đóng Gói',
                ];

                foreach ($stageNames as $stage => $label) {
                    $count = DB::table('stage_plan')
                        ->whereBetween('stage_plan.start', [$fromDate, $toDate])
                        ->where('stage_plan.active', 1)
                        ->where('stage_plan.finished', 0)
                        ->where('stage_code', $stage)
                        ->distinct('id')
                        ->count('id');
                    $datas[$label] = $count;
                }

                $number_of_batch_Not_reposible = DB::table('stage_plan')
                    ->whereBetween('stage_plan.start', [$fromDate, $toDate])
                    ->where('stage_plan.active', 1)
                    ->where('stage_plan.finished', 0)
                    ->where('stage_code', 7)
                    ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
                    ->whereRaw('stage_plan.end > DATE_SUB(plan_master.expected_date, INTERVAL 5 DAY)')
                    ->distinct('stage_plan.id')
                    ->count('stage_plan.id');

                $datas['Số Lượng Lô Trễ Ngày Dự Kiến KCS'] = $number_of_batch_Not_reposible;

                $stages = DB::table('stage_plan')
                ->select('stage_plan.stage_code', 'room.stage')
                ->where('stage_plan.active', 1)
                ->where('stage_plan.deparment_code', $production)
                ->where('stage_plan.finished', 0)
                ->whereNotNull('stage_plan.start')
                ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
                ->distinct()
                ->orderby ('stage_code')
                ->get();

                $stageCode = $request->input('stage_code', optional($stages->first())->stage_code);
               
                dd ($stages);
                session()->put(['title'=> 'BÁO CÁO']);
                return view('pages.Schedual.report.list',[

                        'datas' => $datas,
                        'stages' => $stages,
                        'stageCode' => $stageCode
                ]);
    }



   
}
