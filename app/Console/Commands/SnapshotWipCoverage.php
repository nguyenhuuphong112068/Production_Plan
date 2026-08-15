<?php

namespace App\Console\Commands;

use App\Http\Controllers\General\NotificationController;
use App\Services\WipCoverageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SnapshotWipCoverage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wip:snapshot-coverage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chốt tồn bán thành phẩm theo công đoạn lúc 6h sáng và cảnh báo khi không đủ ngày đáp ứng';

    public function handle(WipCoverageService $service)
    {
        $at = Carbon::today()->setTime(WipCoverageService::DAY_START_HOUR, 0, 0);

        $productionCodes = DB::table('stage_plan')
            ->where('active', 1)
            ->where('stage_code', '<', 8)
            ->whereNotNull('deparment_code')
            ->distinct()
            ->pluck('deparment_code')
            ->filter()
            ->values();

        if ($productionCodes->isEmpty()) {
            $this->warn('Không tìm thấy phân xưởng nào có lịch sản xuất.');
            return self::SUCCESS;
        }

        foreach ($productionCodes as $productionCode) {
            try {
                $this->handleProduction($service, $productionCode, $at);
            } catch (\Exception $e) {
                // Một phân xưởng lỗi không được làm hỏng các phân xưởng còn lại
                $this->error("[{$productionCode}] Lỗi: " . $e->getMessage());
            }
        }

        $this->info('Đã chốt tồn bán thành phẩm cho ' . $productionCodes->count() . ' phân xưởng.');

        return self::SUCCESS;
    }

    private function handleProduction(WipCoverageService $service, string $productionCode, Carbon $at): void
    {
        $thresholds = WipCoverageService::thresholdsFor($productionCode);

        // Khoảng dự báo lấy theo cấu hình rộng nhất của phân xưởng
        $horizon = collect($thresholds)
            ->map(fn($t) => (int) ($t->horizon_days ?? WipCoverageService::DEFAULT_HORIZON_DAYS))
            ->max() ?: WipCoverageService::DEFAULT_HORIZON_DAYS;

        $result = $service->compute($productionCode, $at, $horizon);

        $alerts = [];

        foreach ($result['groups'] as $group) {
            $groupCode = $group['stage_group_code'];
            $threshold = $thresholds[$groupCode] ?? null;

            $status = WipCoverageService::resolveStatus(
                $group['days_of_cover'],
                $threshold,
                $group['has_demand']
            );

            $snapshotId = $this->writeSnapshot($productionCode, $at, $group, $status, $horizon);
            $this->writeDetails($snapshotId, $group);

            if (in_array($status, ['warn', 'critical'], true)) {
                $alerts[] = ['group' => $group, 'status' => $status, 'threshold' => $threshold];
            }
        }

        $this->line("[{$productionCode}] " . count($result['groups']) . ' nhóm công đoạn, ' . count($alerts) . ' cảnh báo.');

        if ($alerts !== []) {
            $this->sendAlert($productionCode, $at, $alerts);
        }
    }

    private function writeSnapshot(string $productionCode, Carbon $at, array $group, string $status, int $horizon): int
    {
        $key = [
            'snapshot_date'    => $at->format('Y-m-d'),
            'production_code'  => $productionCode,
            'stage_group_code' => $group['stage_group_code'],
        ];

        DB::table('wip_coverage_snapshots')->updateOrInsert($key, [
            'snapshot_at'               => $at->format('Y-m-d H:i:s'),
            'next_stage_group_code'     => $group['next_stage_group_code'],
            'stock_dvl'                 => $group['stock_dvl'],
            'stock_lots'                => $group['stock_lots'],
            'orphan_lots'               => $group['orphan_lots'],
            'load_hours'                => $group['load_hours'],
            'days_of_cover'             => $group['days_of_cover'],
            'first_shortage_date'       => $group['first_shortage_date'],
            'lowest_stock_dvl'          => $group['lowest_stock_dvl'],
            'lowest_stock_date'         => $group['lowest_stock_date'],
            'top_product_days'          => $group['max_product_days'],
            'top_product_code'          => $group['max_product_code'],
            'status'                    => $status,
            'horizon_days'              => $horizon,
            'capacity_basis'            => json_encode($group['capacity_basis'], JSON_UNESCAPED_UNICODE),
            'daily_series'              => json_encode($group['daily_series'], JSON_UNESCAPED_UNICODE),
            'computed_at'               => now(),
        ]);

        return (int) DB::table('wip_coverage_snapshots')->where($key)->value('id');
    }

    private function writeDetails(int $snapshotId, array $group): void
    {
        DB::table('wip_coverage_snapshot_details')->where('snapshot_id', $snapshotId)->delete();

        $payload = [];
        foreach ($group['details'] as $detail) {
            $payload[] = [
                'snapshot_id'         => $snapshotId,
                'intermediate_code'   => $detail['intermediate_code'],
                'product_name'        => $detail['product_name'],
                'unit'                => $detail['unit'],
                'stock_dvl'           => $detail['stock_dvl'],
                'stock_lots'          => $detail['stock_lots'],
                'load_hours'          => $detail['load_hours'],
                'days_of_cover'       => $detail['days_of_cover'],
                'last_out_date'       => $detail['last_out_date'],
                // Ở cấp mã, số ngày là PHẦN TẢI mã đó chiếm trong tổng số ngày đáp
                // ứng của cả nhóm, không phải số ngày riêng mã đó trụ được. Đối
                // chiếu với ngưỡng ở đây sẽ ra sai, nên để trạng thái trung tính.
                'status'              => 'ok',
                'batches'             => json_encode($detail['batches'], JSON_UNESCAPED_UNICODE),
            ];
        }

        foreach (array_chunk($payload, 500) as $chunk) {
            DB::table('wip_coverage_snapshot_details')->insert($chunk);
        }
    }

    private function sendAlert(string $productionCode, Carbon $at, array $alerts): void
    {
        $recipients = DB::table('user_management')
            ->where('isActive', 1)
            ->where(function ($query) use ($productionCode) {
                $query->where(function ($q) use ($productionCode) {
                    $q->where('userGroup', 'Schedualer')
                      ->where('deparment', $productionCode);
                })
                ->orWhereIn('deparment', ['COMP', 'BOD']);
            })
            ->pluck('id')
            ->toArray();

        if ($recipients === []) {
            return;
        }

        $critical = collect($alerts)->where('status', 'critical')->count();
        $names = collect($alerts)
            ->map(fn($a) => WipCoverageService::GROUP_NAMES[$a['group']['stage_group_code']])
            ->implode(', ');

        $message = "Phân xưởng {$productionCode}: tồn bán thành phẩm sau {$names} không đủ đáp ứng công đoạn sau"
            . ($critical > 0 ? " ({$critical} nhóm ở mức nguy cấp)." : '.')
            . ' Vui lòng xem lại lịch sản xuất.';

        $html = '<table class="table table-bordered table-sm" style="font-size: 13px; vertical-align: middle;">';
        $html .= '<thead><tr><th class="text-center">STT</th><th>Công đoạn giữ tồn</th><th>Nuôi công đoạn</th>'
            . '<th class="text-right">Tồn hiện tại (ĐVL)</th><th class="text-center">Số lô</th>'
            . '<th class="text-right">Giờ máy</th><th class="text-center">Đáp ứng</th>'
            . '<th class="text-right">Tồn thấp nhất</th><th class="text-center">Rơi vào ngày</th>'
            . '<th>Mã BTP chiếm nhiều nhất</th></tr></thead><tbody>';

        $stt = 1;
        foreach ($alerts as $alert) {
            $g = $alert['group'];
            $color = $alert['status'] === 'critical' ? '#dc3545' : '#fd7e14';
            $label = $alert['status'] === 'critical' ? 'Nguy cấp' : 'Cảnh báo';

            $lowestDate = $g['lowest_stock_date']
                ? Carbon::parse($g['lowest_stock_date'])->format('d/m/Y')
                : '-';

            $topProduct = $g['max_product_code']
                ? $g['max_product_code'] . ' (' . number_format((float) $g['max_product_days'], 1) . ' ngày)'
                : '-';

            $html .= '<tr>'
                . "<td class='text-center'>{$stt}</td>"
                . '<td>' . WipCoverageService::GROUP_NAMES[$g['stage_group_code']] . '</td>'
                . '<td>' . (WipCoverageService::GROUP_NAMES[$g['next_stage_group_code']] ?? '-') . '</td>'
                . "<td class='text-right'>" . number_format((float) $g['stock_dvl'], 0) . '</td>'
                . "<td class='text-center'>" . $g['stock_lots'] . '</td>'
                . "<td class='text-right'>" . number_format((float) $g['load_hours'], 0) . '</td>'
                . "<td class='text-center'><span class='badge' style='background-color: {$color}; color: #fff;'>"
                . number_format((float) $g['days_of_cover'], 1) . " ngày - {$label}</span></td>"
                . "<td class='text-right'>" . number_format((float) ($g['lowest_stock_dvl'] ?? 0), 0) . '</td>'
                . "<td class='text-center'>{$lowestDate}</td>"
                . "<td>{$topProduct}</td>"
                . '</tr>';
            $stt++;
        }

        $html .= '</tbody></table>';
        $html .= "<p style='margin-top:8px; font-size:12px; color:#6c757d;'>Số liệu chốt lúc "
            . $at->format('H:i d/m/Y')
            . '. Tồn được tính lại tại 06:00 từng ngày theo lịch đã sắp. Cột "Giờ máy" là số giờ mà lượng tồn'
            . ' này chiếm ở công đoạn sau theo định mức, cột "Đáp ứng" là số giờ đó chia cho nhịp chạy thực tế'
            . ' của công đoạn sau, tức số ngày tồn còn nuôi được nếu công đoạn trước ngừng cấp hàng.</p>';

        NotificationController::sendNotification(
            $message,
            'Cảnh báo tồn bán thành phẩm',
            null,
            $recipients,
            [],
            '/Schedual/wip_coverage',
            $html,
            true
        );
    }
}
