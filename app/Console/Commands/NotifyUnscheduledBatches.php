<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\General\NotificationController;

class NotifyUnscheduledBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:unscheduled-batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gửi thông báo nhắc nhở lịch chưa sắp xếp cho Scheduler các phân xưởng';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        if ($now->day <= 25) {
            $currentMonthStr = $now->copy()->startOfMonth()->format('Y-m-d');
        } else {
            $currentMonthStr = $now->copy()->addMonth()->startOfMonth()->format('Y-m-d');
        }

        // Lấy tất cả các batch (plan_master_id) có ít nhất 1 stage chưa sắp trong tháng, group theo department
        $unscheduledBatches = DB::table('stage_plan as sp')
            ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
            ->join('product_name as pn', 'fpc.product_name_id', '=', 'pn.id')
            ->whereNull('sp.start')
            ->where('sp.finished', 0)
            ->where('sp.active', 1)
            ->where('pm.active', 1)
            ->where('pm.expected_date', '<', $currentMonthStr)
            ->select(
                'sp.deparment_code', 
                'pm.id as plan_master_id',
                'pm.batch',
                'pm.expected_date',
                'fpc.finished_product_code',
                'fpc.intermediate_code',
                'pn.name as product_name'
            )
            ->distinct()
            ->get();

        $planMasterIds = $unscheduledBatches->pluck('plan_master_id')->unique()->toArray();
        $groupedStages = [];
        if (!empty($planMasterIds)) {
            $allStages = DB::table('stage_plan as sp')
                ->leftJoin('stages as st', 'sp.stage_code', '=', 'st.code')
                ->whereIn('sp.plan_master_id', $planMasterIds)
                ->where('sp.active', 1)
                ->where('sp.finished', 0)
                ->select('sp.plan_master_id', 'sp.deparment_code', 'st.name as stage_name', 'sp.start')
                ->orderBy('sp.order_by_line')
                ->get();
            
            foreach ($allStages as $stage) {
                $key = $stage->plan_master_id . '_' . $stage->deparment_code;
                if (!isset($groupedStages[$key])) {
                    $groupedStages[$key] = [];
                }
                $groupedStages[$key][] = $stage;
            }
        }

        $groupedBatches = $unscheduledBatches->groupBy('deparment_code');

        foreach ($groupedBatches as $department => $batches) {
            $count = $batches->count();

            if ($count > 0) {
                // Find schedualers in this department OR any user in COMP or BOD
                $schedulers = DB::table('user_management')
                    ->where('isActive', 1)
                    ->where(function($query) use ($department) {
                        $query->where(function($q) use ($department) {
                            $q->where('userGroup', 'Schedualer')
                              ->where('deparment', $department);
                        })
                        ->orWhereIn('deparment', ['COMP', 'BOD']);
                    })
                    ->pluck('id')
                    ->toArray();

                if (!empty($schedulers)) {
                    $monthDisplay = date('m/Y', strtotime($currentMonthStr));
                    $message = "Phân xưởng {$department} có {$count} lô chưa được sắp lịch (Kế hoạch giao trước tháng {$monthDisplay}). Vui lòng sắp xếp.";
                    
                    // Tạo nội dung HTML cho modal_content_extend
                    $html = '<table class="table table-bordered table-sm" style="font-size: 13px; vertical-align: middle;">';
                    $html .= '<thead><tr><th class="text-center">STT</th><th>Mã SP (BTP - TP)</th><th>Tên Sản phẩm</th><th>Số Lô</th><th>Ngày dự kiến KCS</th><th>Tình trạng sắp lịch</th></tr></thead><tbody>';
                    $stt = 1;
                    foreach ($batches as $batch) {
                        $date = \Carbon\Carbon::parse($batch->expected_date)->format('d/m/Y');
                        $maSP = $batch->intermediate_code . ' - ' . $batch->finished_product_code;
                        $tenSP = $batch->product_name ?: '-';
                        $soLo = $batch->batch ?: '-';

                        $key = $batch->plan_master_id . '_' . $department;
                        $stagesHtml = '<div style="display: flex; gap: 4px; flex-wrap: wrap;">';
                        if (isset($groupedStages[$key])) {
                            foreach ($groupedStages[$key] as $stg) {
                                $stageName = $stg->stage_name ?: 'Công đoạn';
                                if ($stg->start) {
                                    $stagesHtml .= '<span class="badge bg-success" style="font-weight: normal;"><i class="fas fa-check"></i> ' . $stageName . '</span>';
                                } else {
                                    $stagesHtml .= '<span class="badge bg-secondary" style="font-weight: normal;"><i class="fas fa-times"></i> ' . $stageName . '</span>';
                                }
                            }
                        }
                        $stagesHtml .= '</div>';

                        $html .= "<tr><td class='text-center'>{$stt}</td><td>{$maSP}</td><td>{$tenSP}</td><td>{$soLo}</td><td>{$date}</td><td>{$stagesHtml}</td></tr>";
                        $stt++;
                    }
                    $html .= '</tbody></table>';

                    // Gửi thông báo với url = null và có modal_content_extend
                    NotificationController::sendNotification(
                        $message,
                        "Nhắc nhở lịch chưa sắp",
                        null,
                        $schedulers,
                        [],
                        null,
                        $html
                    );
                }
            }
        }

        $this->info('Notifications sent successfully.');
    }
}
