<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\General\NotificationController;
use Carbon\Carbon;

class NotifyValidationSampling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:validation-sampling';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gửi thông báo nhắc nhở lấy mẫu thẩm định đóng gói vào ngày mai';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tomorrowStart = Carbon::tomorrow()->startOfDay();
        $tomorrowEnd = Carbon::tomorrow()->endOfDay();

        // 1. Tìm các lô có is_validation_tracking = 1 và chuẩn bị đóng gói vào ngày mai
        $batches = DB::table('plan_master as pm')
            ->join('stage_plan as sp', function ($join) {
                $join->on('pm.main_parkaging_id', '=', 'sp.plan_master_id')
                     ->where('sp.stage_code', 7) // Công đoạn Đóng gói
                     ->where('sp.active', 1)
                     ->where('sp.finished', 0);
            })
            ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('product_name as pn', 'fpc.product_name_id', '=', 'pn.id')
            ->where('pm.active', 1)
            ->where('pm.cancel', 0)
            ->where('pm.is_validation_tracking', 1)
            ->whereBetween('sp.start', [$tomorrowStart, $tomorrowEnd])
            ->select(
                'pm.id as plan_master_id',
                'pm.batch',
                'pm.actual_batch',
                'fpc.finished_product_code',
                'fpc.intermediate_code',
                'pn.name as product_name',
                'pm.deparment_code',
                'sp.start'
            )
            ->get();

        if ($batches->isEmpty()) {
            $this->info('Không có lô thẩm định nào bắt đầu đóng gói vào ngày mai.');
            return;
        }

        // 2. Lấy danh sách ID user thuộc phòng QC, QA, PL
        $targetUsers = DB::table('user_management')
            ->where('isActive', 1)
            ->whereIn('deparment', ['QC', 'QA', 'PL'])
            ->pluck('id')
            ->toArray();

        if (empty($targetUsers)) {
            $this->warn('Không tìm thấy người dùng nào thuộc phòng QC, QA, PL.');
            return;
        }

        // 3. Tạo nội dung thông báo
        $count = $batches->count();
        $dateStr = $tomorrowStart->format('d/m/Y');
        $message = "Có {$count} lô sản phẩm thẩm định sẽ bắt đầu đóng gói vào ngày mai ({$dateStr}). Vui lòng chuẩn bị kế hoạch lấy mẫu.";

        // Tạo nội dung HTML cho modal_content_extend
        $html = '<table class="table table-bordered table-sm text-center" style="font-size: 13px; vertical-align: middle;">';
        $html .= '<thead class="bg-primary text-white"><tr><th>STT</th><th>Mã sản phẩm</th><th>Tên sản phẩm</th><th>Số lô</th><th>Phân xưởng</th><th>TG Đóng gói dự kiến</th></tr></thead><tbody>';
        
        $stt = 1;
        foreach ($batches as $batch) {
            $maSP = $batch->finished_product_code;
            $tenSP = $batch->product_name ?: '-';
            $soLo = $batch->actual_batch ?: $batch->batch;
            $timeStart = Carbon::parse($batch->start)->format('H:i d/m/Y');
            
            $html .= "<tr><td>{$stt}</td><td class='font-weight-bold text-success'>{$maSP}</td><td>{$tenSP}</td><td class='font-weight-bold'>{$soLo}</td><td>{$batch->deparment_code}</td><td>{$timeStart}</td></tr>";
            $stt++;
        }
        $html .= '</tbody></table>';

        // 4. Gửi thông báo hàng loạt
        NotificationController::sendNotification(
            $message,
            "Nhắc nhở Lấy mẫu Thẩm định",
            null, // referenceId
            $targetUsers,
            [], // targetUserGroups
            '/plan/validation_tracking', // url
            $html,
            true // forceSystemSender
        );

        $this->info("Đã gửi thông báo lấy mẫu thẩm định thành công cho " . count($targetUsers) . " người dùng.");
    }
}
