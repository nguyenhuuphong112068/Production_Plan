<?php

namespace App\Console\Commands;

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
    protected $description = 'Chốt tồn bán thành phẩm lý thuyết đang chờ từng công đoạn đích lúc 6h sáng';

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
        $horizon = WipCoverageService::DEFAULT_HORIZON_DAYS;

        $result = $service->compute($productionCode, $at, $horizon);

        // Nhóm nào biến mất khỏi lịch thì bản chốt cũ của ngày hôm nay cũng phải
        // biến mất theo, nếu không trang sẽ còn hiện một nhóm không còn tồn tại
        $keptGroups = [];

        foreach ($result['groups'] as $group) {
            $keptGroups[] = $group['group_code'];

            $snapshotId = $this->writeSnapshot($productionCode, $at, $group, $horizon, $result['supply']);
            $this->writeDetails($snapshotId, $group);
        }

        DB::table('wip_coverage_snapshots')
            ->where('production_code', $productionCode)
            ->where('snapshot_date', $at->format('Y-m-d'))
            ->whereNotIn('next_stage_group_code', $keptGroups ?: [''])
            ->delete();

        $this->line("[{$productionCode}] " . count($result['groups']) . ' nhóm công đoạn đích.');
    }

    /**
     * $supply là chuỗi sản lượng Pha chế của CẢ phân xưởng, không riêng nhóm nào.
     * Ghi lặp trên mọi dòng của cùng ngày chốt để lúc đọc bản chốt không phải nối
     * thêm bảng; chuỗi 30 ngày rất nhẹ nên phần lặp không đáng kể.
     */
    private function writeSnapshot(string $productionCode, Carbon $at, array $group, int $horizon, array $supply): int
    {
        $key = [
            'snapshot_date'          => $at->format('Y-m-d'),
            'production_code'        => $productionCode,
            'next_stage_group_code'  => $group['group_code'],
        ];

        DB::table('wip_coverage_snapshots')->updateOrInsert($key, [
            'snapshot_at'         => $at->format('Y-m-d H:i:s'),
            'stock_dvl'           => $group['stock_dvl'],
            'stock_lots'          => $group['stock_lots'],
            'first_shortage_date' => $group['first_shortage_date'],
            'lowest_stock_dvl'    => $group['lowest_stock_dvl'],
            'lowest_stock_date'   => $group['lowest_stock_date'],
            'highest_stock_dvl'   => $group['highest_stock_dvl'],
            'highest_stock_date'  => $group['highest_stock_date'],
            'avg_stock_dvl'       => $group['avg_stock_dvl'],
            'top_product_code'    => $group['top_product_code'],
            'top_product_dvl'     => $group['top_product_dvl'],
            'horizon_days'        => $horizon,
            'daily_series'        => json_encode($group['daily_series'], JSON_UNESCAPED_UNICODE),
            'supply_series'       => json_encode($supply, JSON_UNESCAPED_UNICODE),
            'computed_at'         => now(),
        ]);

        return (int) DB::table('wip_coverage_snapshots')->where($key)->value('id');
    }

    private function writeDetails(int $snapshotId, array $group): void
    {
        DB::table('wip_coverage_snapshot_details')->where('snapshot_id', $snapshotId)->delete();

        $payload = [];
        foreach ($group['details'] as $detail) {
            $payload[] = [
                'snapshot_id'       => $snapshotId,
                'intermediate_code' => $detail['intermediate_code'],
                'product_name'      => $detail['product_name'],
                'unit'              => $detail['unit'],
                'stock_dvl'         => $detail['stock_dvl'],
                'stock_lots'        => $detail['stock_lots'],
                'share_pct'         => $detail['share_pct'],
                'last_out_date'     => $detail['last_out_date'],
                'batches'           => json_encode($detail['batches'], JSON_UNESCAPED_UNICODE),
            ];
        }

        foreach (array_chunk($payload, 500) as $chunk) {
            DB::table('wip_coverage_snapshot_details')->insert($chunk);
        }
    }
}
