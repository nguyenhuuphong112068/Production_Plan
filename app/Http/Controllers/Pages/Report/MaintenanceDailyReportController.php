<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MaintenanceDailyReportController extends Controller
{
    public function index(Request $request)
    {
        $production_code = session('user')['production_code'];
        $reportedDateInput = $request->reportedDate ?? Carbon::now()->format('Y-m-d');

        // 1. Tự động đồng bộ hóa dữ liệu từ bên ngoài
        $this->autoSyncMaintenance($production_code);

        $startDate = Carbon::parse($reportedDateInput)->setTime(6, 0, 0);
        $endDate = $startDate->copy()->addDays(1);

        // 2. Lấy TOÀN BỘ phòng thuộc phân xưởng hiện tại và Join với dữ liệu bảo trì (stage_code = 8)
        $datas = DB::table('room as r')
            ->leftJoin('stage_plan as sp', function ($join) use ($startDate, $endDate) {
                $join->on('r.id', '=', 'sp.resourceId')
                    ->where('sp.stage_code', '=', 8)
                    ->whereBetween('sp.start', [$startDate, $endDate]);
            })
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
            ->where('r.deparment_code', $production_code)
            ->select(
                'r.id as room_id',
                'r.name as room_name',
                'r.code as room_code',
                'sp.id as sp_id',
                'sp.start as planned_start',
                'sp.end as planned_end',
                'sp.actual_start',
                'sp.actual_end',
                'sp.yields',
                'sp.finished',
                'sp.finished_by',
                'qm.inst_id',
                'qm.inst_name',
                'qm.parent_eqp_id',
                'qm.Eqp_name',
                'qm.block',
                'pm.expected_date as plan_date',
                'pm.batch as sch_ids'
            )
            ->orderBy('r.order_by')
            ->orderBy('r.code')
            ->orderBy('sp.actual_start')
            ->get();

        $displayDate = Carbon::parse($reportedDateInput)->format('d/m/Y');
        session()->put(['title' => "BÁO CÁO BẢO TRÌ NGÀY $displayDate"]);

        // Map yields và giải mã loại hoạt động
        $datas = $datas->map(function ($item) {
            $statusText = '—';
            $badgeColor = 'light';

            if ($item->sp_id) {
                if ($item->yields == 1) {
                    $statusText = 'Pass';
                    $badgeColor = 'success';
                } elseif ($item->yields == 0 && $item->yields !== null) {
                    $statusText = 'Fail';
                    $badgeColor = 'danger';
                } elseif ($item->yields == 2) {
                    $statusText = 'Skip';
                    $badgeColor = 'warning';
                }
            }

            // Giải mã loại hoạt động từ block
            $typeCode = explode('-', $item->block ?? '')[0];
            $item->type_name = match ($typeCode) {
                'HC' => 'Hiệu chuẩn',
                'BT' => 'Bảo trì',
                'TI' => 'Tiện ích',
                default => 'Hoạt động'
            };

            $item->status_text = $statusText;
            $item->badge_color = $badgeColor;
            return $item;
        });

        // Nhóm dữ liệu theo phòng để hiển thị chi tiết (giống DailyReport)
        $groupedDatas = $datas->groupBy('room_id');

        return view('pages.report.maintenance_daily_report.list', [
            'groupedDatas' => $groupedDatas,
            'reportedDate' => $displayDate,
        ]);
    }

    private function autoSyncMaintenance($production)
    {
        // 1. Xác định connection dựa trên phân xưởng
        $conn = null;
        if (in_array($production, ['PXV1', 'PXTN'])) {
            $conn = 'cal1';
        } elseif (in_array($production, ['PXV2', 'PXDN', 'PXVH'])) {
            $conn = 'cal2';
        }

        if (!$conn) return;

        // 2. Tìm các lệnh bảo trì chưa xong và lấy thông tin loại (HC, BT, TI)
        $pendingPlans = DB::table('stage_plan as sp')
            ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
            ->where('sp.stage_code', 8)
            ->where('sp.finished', 0)
            ->where('sp.deparment_code', $production)
            ->whereNotNull('pm.batch')
            ->select('sp.id as sp_id', 'pm.batch as sch_ids', 'qm.block')
            ->get();

        if ($pendingPlans->isEmpty()) return;

        // 3. Nhóm các SCH_ID theo loại bảng (Suffix 1, 2, 3)
        $suffixGroups = [
            1 => [], // HC
            2 => [], // BT
            3 => [], // TI
        ];

        $schIdToSpIds = [];

        foreach ($pendingPlans as $p) {
            $typeCode = explode('-', $p->block ?? '')[0];
            $suffix = match ($typeCode) {
                'HC' => 1,
                'BT' => 2,
                'TI' => 3,
                default => 1 // Mặc định là HC nếu không rõ
            };

            foreach (explode(',', $p->sch_ids) as $id) {
                $id = trim($id);
                if (!$id) continue;
                $suffixGroups[$suffix][] = $id;
                $schIdToSpIds[$suffix][$id][] = $p->sp_id;
            }
        }

        // 4. Truy vấn từng bảng remote trên connection duy nhất
        foreach ($suffixGroups as $suffix => $allSchIds) {
            if (empty($allSchIds)) continue;

            try {
                $remoteResults = DB::connection($conn)
                    ->table("Schedule_Master_{$suffix}")
                    ->whereIn('SCH_ID', array_unique($allSchIds))
                    ->where('sch_ap_sts', 1)
                    ->get();

                foreach ($remoteResults as $res) {
                    $schId = (string)$res->SCH_ID;
                    if (isset($schIdToSpIds[$suffix][$schId])) {
                        foreach ($schIdToSpIds[$suffix][$schId] as $spId) {
                            $yield = 1;
                            $statusRaw = trim($res->Sch_Result_Status ?? 'Pass');
                            if ($statusRaw == 'Fail') $yield = 0;
                            elseif ($statusRaw == 'Skip') $yield = 2;

                            DB::table('stage_plan')->where('id', $spId)
                                ->where('finished', 0)
                                ->update([
                                    'actual_start' => $res->Sch_caldone_to,
                                    'actual_end'   => $res->Sch_CalDone_On,
                                    'finished_by'  => $res->Sch_Cal_Done_by ?? null,
                                    'finished'     => 1,
                                    'yields'       => $yield,
                                    'finished_date' => now()
                                ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Maintenance Sync Error ({$conn} - Master {$suffix}): " . $e->getMessage());
            }
        }
    }
}
