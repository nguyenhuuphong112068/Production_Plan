<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tính tồn bán thành phẩm giữa các công đoạn và số ngày mà lượng tồn đó
 * đáp ứng được cho công đoạn kế tiếp, dựa trên lịch lý thuyết đã sắp.
 *
 * Lưu ý hiệu năng quan trọng: stage_plan.code và stage_plan.nextcessor_code
 * là quan hệ cha con nhưng phép tự nối bảng qua chúng rất tốn kém khi bảng lớn.
 * Vì vậy toàn bộ việc đi theo chuỗi công đoạn được làm trong PHP trên một
 * bảng tra dựng sẵn, không nối bảng trong SQL.
 */
class WipCoverageService
{
    /** Nhóm công đoạn giữ tồn, theo đúng thứ tự sản xuất */
    public const STAGE_GROUPS = [
        'PC' => [3, 4],   // Pha chế + Trộn hoàn tất, đầu ra lấy ở công đoạn lớn nhất có thật
        'DH' => [5],      // Định hình
        'BP' => [6],      // Bao phim
        'DG' => [7],      // Đóng gói, chỉ tiêu thụ chứ không sinh tồn
    ];

    /** Các nhóm thực sự sinh ra tồn bán thành phẩm */
    public const SOURCE_GROUPS = ['PC', 'DH', 'BP'];

    public const GROUP_NAMES = [
        'PC' => 'Pha chế',
        'DH' => 'Định hình',
        'BP' => 'Bao phim',
        'DG' => 'Đóng gói',
    ];

    public const DEFAULT_HORIZON_DAYS = 30;

    /** Giờ bắt đầu ngày công của nhà máy */
    public const DAY_START_HOUR = 6;

    /** Số ngày lịch sử dùng để đo nhịp chạy thực tế của mỗi công đoạn */
    public const CAPACITY_WINDOW_DAYS = 90;

    /**
     * @return array{
     *     production_code: string,
     *     snapshot_at: string,
     *     horizon_days: int,
     *     groups: array<int, array<string, mixed>>
     * }
     */
    public function compute(string $productionCode, Carbon $at, int $horizonDays = self::DEFAULT_HORIZON_DAYS): array
    {
        $at = $at->copy();
        $horizonDays = max(1, min(180, $horizonDays));

        $rows = $this->loadStagePlans($productionCode, $at, $horizonDays);
        $byCode = $this->buildCodeLookup($rows, $productionCode);
        $capacity = $this->loadCapacity($productionCode, $at);

        $days = $this->buildDayWindows($at, $horizonDays);

        // Gom các dòng theo lô một lần rồi dùng chung cho mọi nhóm
        $byPlanMaster = [];
        foreach ($rows as $row) {
            $byPlanMaster[$row->plan_master_id][] = $row;
        }

        // Bước 1: dựng sổ nhập xuất của tất cả các nhóm TRƯỚC khi tính số ngày.
        //
        // Phải làm hai lượt vì một công đoạn có thể được nhiều kho nuôi cùng lúc:
        // Đóng gói nhận hàng từ cả Bao phim, Định hình (hàng không bao phim) lẫn
        // Pha chế (hàng không định hình). Nếu tính riêng từng nhóm thì nhóm nào
        // cũng tưởng mình được dùng trọn công suất Đóng gói, ra số sai hẳn.
        $ledgers = [];
        $tallies = [];
        foreach (self::SOURCE_GROUPS as $groupCode) {
            $built = $this->buildLedger($groupCode, $byPlanMaster, $byCode, $capacity);

            // Phân xưởng không có công đoạn này thì không hiển thị thẻ nào cả
            if ($built === null) {
                continue;
            }

            $ledgers[$groupCode] = $built['ledger'];
            $tallies[$groupCode] = $built['next_tally'];
        }

        // Bước 2: chia nhịp chạy của từng công đoạn tiêu thụ cho các kho nuôi nó
        $series = $this->buildSeries($days, $ledgers, $capacity);

        // Con số trên thẻ phải đo tại ĐÚNG mốc chốt, không lấy điểm đầu của chuỗi.
        // Lệnh chạy nền chốt lúc 06:00 nên hai mốc trùng nhau, nhưng khi người dùng
        // bấm tính lại giữa ngày thì mốc chốt là bây giờ còn điểm đầu chuỗi vẫn là
        // 06:00 sáng nay, lấy nhầm sẽ ra thẻ ghi tồn bằng 0 mà vẫn đáp ứng mấy ngày.
        $coverNow = $this->coverByCapacity($ledgers, $at->format('Y-m-d H:i:s'), $capacity);

        $groups = [];
        foreach ($ledgers as $groupCode => $ledger) {
            $groups[] = $this->assembleGroup(
                $groupCode,
                $ledger,
                $tallies[$groupCode],
                $at,
                $days,
                $horizonDays,
                $capacity,
                $series[$groupCode],
                $coverNow[$groupCode]
            );
        }

        return [
            'production_code' => $productionCode,
            'snapshot_at'     => $at->format('Y-m-d H:i:s'),
            'horizon_days'    => $horizonDays,
            'groups'          => $groups,
        ];
    }

    /**
     * Một truy vấn phẳng duy nhất. Không nối stage_plan với chính nó.
     *
     * Phạm vi thời gian: lấy rộng về quá khứ để không bỏ sót lô đã bắt đầu từ lâu
     * mà công đoạn sau vẫn chưa chạy, và lấy tới hết khoảng dự báo cho phía cầu.
     */
    private function loadStagePlans(string $productionCode, Carbon $at, int $horizonDays)
    {
        $from = $at->copy()->subDays(365)->format('Y-m-d H:i:s');
        $to   = $at->copy()->addDays($horizonDays + 1)->format('Y-m-d H:i:s');

        return DB::table('stage_plan as sp')
            ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
            ->leftJoin('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
            ->leftJoin('intermediate_category as ic', 'fpc.intermediate_code', '=', 'ic.intermediate_code')
            ->leftJoin('product_name as pn', 'ic.product_name_id', '=', 'pn.id')
            ->where('sp.active', 1)
            ->where('sp.stage_code', '<', 8)          // loại Bảo trì, Hiệu chuẩn, Tiện ích
            ->where('pm.active', 1)
            ->where('pm.cancel', 0)
            ->where('pl.type', 1)                      // chỉ kế hoạch sản xuất
            ->where('sp.deparment_code', $productionCode)
            ->where(function ($q) use ($from, $to) {
                $q->whereNull('sp.start')
                  ->orWhereBetween('sp.start', [$from, $to]);
            })
            ->select(
                'sp.id',
                'sp.code',
                'sp.plan_master_id',
                'sp.stage_code',
                'sp.predecessor_code',
                'sp.nextcessor_code',
                'sp.start',
                'sp.end',
                'sp.finished',
                'sp.Theoretical_yields as theoretical_yields',
                'sp.yields',
                'pm.batch',
                'pm.percent_parkaging',
                'fpc.intermediate_code',
                'fpc.finished_product_code',
                'ic.batch_size',
                'ic.batch_qty',
                'ic.unit_batch_qty',
                'pn.name as product_name'
            )
            ->get();
    }

    /**
     * Bảng tra code -> DANH SÁCH dòng.
     *
     * stage_plan.code KHÔNG duy nhất: lô đóng gói một phần sinh nhiều dòng công đoạn 7
     * dùng chung một code nhưng khác plan_master_id, mỗi dòng mang percent_parkaging
     * riêng (xem ProductionPlanController quanh dòng 2261). Nếu tra bằng keyBy('code')
     * thì chỉ giữ được một dòng và tính thiếu nhu cầu.
     *
     * Công đoạn kế tiếp cũng có thể nằm ở phân xưởng khác nên phải nạp bù các code
     * còn thiếu mà không lọc theo deparment_code.
     */
    private function buildCodeLookup($rows, string $productionCode): array
    {
        $byCode = [];
        foreach ($rows as $row) {
            if ($row->code !== null && $row->code !== '') {
                $byCode[$row->code][] = $row;
            }
        }

        $missing = [];
        foreach ($rows as $row) {
            $next = $row->nextcessor_code;
            if ($next !== null && $next !== '' && ! isset($byCode[$next])) {
                $missing[$next] = true;
            }
        }

        if ($missing === []) {
            return $byCode;
        }

        foreach (array_chunk(array_keys($missing), 1000) as $chunk) {
            $extra = DB::table('stage_plan as sp')
                ->whereIn('sp.code', $chunk)
                ->where('sp.active', 1)
                ->where('sp.stage_code', '<', 8)
                ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                ->leftJoin('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                ->select(
                    'sp.id',
                    'sp.code',
                    'sp.plan_master_id',
                    'sp.stage_code',
                    'sp.predecessor_code',
                    'sp.nextcessor_code',
                    'sp.start',
                    'sp.end',
                    'sp.finished',
                    'sp.Theoretical_yields as theoretical_yields',
                    'sp.yields',
                    'pm.percent_parkaging',
                    'fpc.intermediate_code',
                    'fpc.finished_product_code'
                )
                ->get();

            foreach ($extra as $row) {
                if (! isset($byCode[$row->code])) {
                    $byCode[$row->code] = [];
                }
                $byCode[$row->code][] = $row;
            }
        }

        return $byCode;
    }

    /**
     * Đổi chuỗi giờ dạng "HH:MM" của bảng quota về số giờ thập phân.
     * Cột p_time/m_time là varchar chứ không phải số, so sánh số học trực tiếp
     * sẽ ra 0 cho mọi giá trị dạng "02:00".
     */
    private static function hoursOf(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (strpos($value, ':') !== false) {
            $parts = explode(':', $value);
            $hours = (float) $parts[0] + ((float) ($parts[1] ?? 0)) / 60;
        } else {
            $hours = (float) $value;
        }

        return $hours > 0 ? $hours : null;
    }

    /**
     * Định mức giờ chạy mỗi lô và công suất thực tế của từng công đoạn.
     *
     * Tử số là định mức: quota.m_time cho biết một lô chiếm bao nhiêu giờ máy ở
     * công đoạn đó. Mẫu số là công suất: đo nhịp chạy thật của cả công đoạn trong
     * CAPACITY_WINDOW_DAYS ngày gần nhất, tính bằng giờ máy mỗi ngày.
     *
     * Không lấy "số phòng × 24 giờ" làm công suất vì không phòng nào chạy liên tục
     * suốt ngày; con số đó sẽ báo động giả. Nhịp chạy đo được phản ánh đúng tốc độ
     * mà công đoạn sau thực sự rút hàng ra khỏi kho.
     *
     * @return array{hours: array, median: array, rate: array, rooms: array, days: int}
     */
    private function loadCapacity(string $productionCode, Carbon $at): array
    {
        $stages = [3, 4, 5, 6, 7];

        // Định mức giờ máy mỗi lô. Công đoạn 7 tra theo mã thành phẩm, còn lại
        // tra theo mã bán thành phẩm.
        $hours = [];
        $pool = [];

        $quotas = DB::table('quota')
            ->where('deparment_code', $productionCode)
            ->where('active', 1)
            ->whereIn('stage_code', $stages)
            ->get(['stage_code', 'intermediate_code', 'finished_product_code', 'm_time']);

        foreach ($quotas as $quota) {
            $value = self::hoursOf($quota->m_time);
            if ($value === null) {
                continue;
            }

            $stage = (int) $quota->stage_code;
            $key = $stage >= 7 ? $quota->finished_product_code : $quota->intermediate_code;

            if ($key === null || $key === '' || $key === 'NA') {
                continue;
            }

            $hours[$stage][$key][] = $value;
            $pool[$stage][] = $value;
        }

        // Một mã có thể khai nhiều phòng với giờ khác nhau, lấy trung vị
        foreach ($hours as $stage => $byKey) {
            foreach ($byKey as $key => $values) {
                $hours[$stage][$key] = self::median($values);
            }
        }

        $median = [];
        foreach ($pool as $stage => $values) {
            $median[$stage] = self::median($values);
        }

        // Nhịp chạy thật: tổng giờ máy đã xếp chia cho số ngày của cửa sổ đo
        $windowDays = self::CAPACITY_WINDOW_DAYS;
        $from = $at->copy()->subDays($windowDays)->format('Y-m-d H:i:s');
        $to   = $at->copy()->format('Y-m-d H:i:s');

        $usage = DB::table('stage_plan as sp')
            ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
            ->where('sp.active', 1)
            ->where('pm.active', 1)
            ->where('pm.cancel', 0)
            ->where('pl.type', 1)
            ->where('sp.deparment_code', $productionCode)
            ->whereIn('sp.stage_code', $stages)
            ->whereBetween('sp.start', [$from, $to])
            ->selectRaw('sp.stage_code, SUM(TIMESTAMPDIFF(MINUTE, sp.start, sp.end)) / 60 as hours, COUNT(*) as lots')
            ->groupBy('sp.stage_code')
            ->get();

        $rate = [];
        $lots = [];
        foreach ($usage as $row) {
            $stage = (int) $row->stage_code;
            $rate[$stage] = round(((float) $row->hours) / $windowDays, 2);
            $lots[$stage] = (int) $row->lots;
        }

        $rooms = DB::table('room')
            ->where('deparment_code', $productionCode)
            ->where('active', 1)
            ->where('only_maintenance', 0)
            ->whereIn('stage_code', $stages)
            ->selectRaw('stage_code, COUNT(*) as n')
            ->groupBy('stage_code')
            ->pluck('n', 'stage_code')
            ->all();

        return [
            'hours'  => $hours,
            'median' => $median,
            'rate'   => $rate,
            'lots'   => $lots,
            'rooms'  => $rooms,
            'days'   => $windowDays,
        ];
    }

    private static function median(array $values): float
    {
        sort($values);
        $n = count($values);
        if ($n === 0) {
            return 0.0;
        }

        $mid = intdiv($n, 2);

        return $n % 2 === 1 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }

    /** Các mốc ngày công 06:00 -> 06:00 kể từ mốc chốt */
    private function buildDayWindows(Carbon $at, int $horizonDays): array
    {
        $first = $at->copy();
        if ($first->hour < self::DAY_START_HOUR) {
            $first->subDay();
        }
        $first->setTime(self::DAY_START_HOUR, 0, 0);

        $days = [];
        for ($i = 0; $i < $horizonDays; $i++) {
            $start = $first->copy()->addDays($i);
            $days[] = [
                'index' => $i,
                'date'  => $start->format('Y-m-d'),
                'start' => $start,
                'end'   => $start->copy()->addDay(),
            ];
        }

        return $days;
    }

    /**
     * Sổ nhập xuất của một nhóm công đoạn: mỗi lô vào kho lúc nào, bị rút ra lúc
     * nào và chiếm bao nhiêu giờ máy ở công đoạn sau.
     *
     * Trả về null nếu phân xưởng không có công đoạn nào thuộc nhóm này.
     *
     * @return array{ledger: array, next_tally: array}|null
     */
    private function buildLedger(
        string $groupCode,
        array $byPlanMaster,
        array $byCode,
        array $capacity
    ): ?array {
        $stageCodes = self::STAGE_GROUPS[$groupCode];

        $groupExists = false;
        $nextGroupTally = [];

        // Hồ sơ nhập/xuất của từng lô, dùng để tính lại tồn tại BẤT KỲ mốc thời gian nào
        $ledger = [];

        foreach ($byPlanMaster as $planRows) {
            $outRow = $this->outputRowOfGroup($planRows, $stageCodes);
            if ($outRow === null) {
                continue;
            }

            $groupExists = true;

            $successors = $this->nextGroupRows($outRow, $byCode, $groupCode);
            $qtyDvl = $this->toDvl($outRow);

            if ($qtyDvl <= 0) {
                continue;
            }

            // Thời điểm lô này nhập kho, và các thời điểm từng phần bị rút ra
            $exits = [];
            foreach ($successors as $successor) {
                if ($successor->start === null) {
                    continue;
                }
                $exits[] = [
                    'start'  => $successor->start,
                    'weight' => $this->successorWeight($successors, $successor),
                ];
            }

            $ledger[] = [
                'plan_master_id'    => $outRow->plan_master_id,
                'batch'             => $outRow->batch ?? null,
                'intermediate_code' => $outRow->intermediate_code ?? null,
                'product_name'      => $outRow->product_name ?? null,
                'unit'              => $outRow->unit_batch_qty ?? null,
                'stage_code'        => (int) $outRow->stage_code,
                'qty_dvl'          => $qtyDvl,
                'entry'            => $outRow->start,   // null nghĩa là chưa sắp lịch, không bao giờ nhập kho
                'exits'            => $exits,
                'orphan'           => $successors === [],
                // Số giờ máy mà lô này sẽ chiếm ở công đoạn sau, tách theo công đoạn.
                // Tính cho cả phần chưa sắp lịch, vì công việc vẫn phải làm.
                'load'             => $this->consumerLoad($successors, $capacity),
            ];

            if ($successors !== []) {
                $nextGroup = $this->groupOfStage((int) $successors[0]->stage_code);
                $nextGroupTally[$nextGroup] = ($nextGroupTally[$nextGroup] ?? 0) + 1;
            }
        }

        if (! $groupExists) {
            return null;
        }

        return ['ledger' => $ledger, 'next_tally' => $nextGroupTally];
    }

    /** Ráp kết quả của một nhóm từ sổ nhập xuất và chuỗi biến thiên đã tính sẵn */
    private function assembleGroup(
        string $groupCode,
        array $ledger,
        array $nextGroupTally,
        Carbon $at,
        array $days,
        int $horizonDays,
        array $capacity,
        array $series,
        array $coverNow
    ): array {
        $atStr = $at->format('Y-m-d H:i:s');

        // Tồn ngay tại mốc chốt
        $now = $this->stockAt($ledger, $atStr);
        $stock = $now['lots'];
        $stockTotal = $now['total'];
        $orphanLots = $now['orphans'];

        $cover = $this->coverFromSeries($series);

        // Nhịp mà nhóm này thực sự được hưởng ở từng công đoạn tiêu thụ, tại mốc chốt
        $allocated = $coverNow['rate_by_stage'];
        $groupRate = (float) array_sum($allocated);

        $details = $this->buildDetails($stock, $ledger, $days, $atStr, $groupRate);

        // Nhịp đã chia chỉ cần ở mức nhóm, bỏ khỏi từng điểm cho nhẹ đường truyền
        foreach ($series as $i => $point) {
            unset($series[$i]['rate_by_stage']);
        }

        $topProduct = $details[0] ?? null;

        arsort($nextGroupTally);
        $nextGroupCode = $nextGroupTally === [] ? null : array_key_first($nextGroupTally);

        return [
            'stage_group_code'      => $groupCode,
            'stage_group_name'      => self::GROUP_NAMES[$groupCode],
            'next_stage_group_code' => $nextGroupCode,
            'next_stage_group_name' => $nextGroupCode ? self::GROUP_NAMES[$nextGroupCode] : null,
            'stock_dvl'             => round($stockTotal, 2),
            'stock_lots'            => count($stock),
            'orphan_lots'           => $orphanLots,
            'days_of_cover'         => $coverNow['days'],
            'first_shortage_date'   => $cover['shortage_date'],
            'lowest_stock_dvl'      => $cover['lowest'],
            'lowest_stock_date'     => $cover['lowest_date'],
            'demand_total_dvl'      => $cover['demand_total'],
            // Có công đoạn sau để nuôi hay không. Khác với "đã sắp lịch cho công
            // đoạn sau", vì tải vẫn tính cho cả phần chưa sắp lịch.
            'has_demand'            => $coverNow['hours'] > 0,
            // Nhóm có trong quy trình của phân xưởng nhưng hiện không có gì để cảnh báo
            'is_empty'              => $stockTotal <= 0 && $coverNow['hours'] <= 0,
            // Phần tồn đã có lịch tiêu thụ cho công đoạn sau. Thấp nghĩa là lịch còn
            // trống chứ không phải hàng đứng yên, dùng để nhắc sắp lịch xa hơn.
            'scheduled_demand_pct'  => $stockTotal > 0
                ? round($cover['demand_total'] / $stockTotal * 100, 1)
                : null,
            // Cơ sở tính số ngày đáp ứng, đưa lên giao diện để đối chiếu được
            'load_hours'            => $coverNow['hours'],
            'capacity_basis'        => $this->capacityBasis($capacity, $allocated),
            'max_product_days'      => $topProduct['days_of_cover'] ?? null,
            'max_product_code'      => $topProduct['intermediate_code'] ?? null,
            'horizon_days'          => $horizonDays,
            'daily_series'          => $series,
            'details'               => $details,
        ];
    }

    /** Công đoạn cuối cùng thuộc nhóm mà lô này thực sự có */
    private function outputRowOfGroup(array $planRows, array $stageCodes)
    {
        $best = null;
        foreach ($planRows as $row) {
            if (! in_array((int) $row->stage_code, $stageCodes, true)) {
                continue;
            }
            if ($best === null || (int) $row->stage_code > (int) $best->stage_code) {
                $best = $row;
            }
        }

        return $best;
    }

    /**
     * Đi theo nextcessor_code cho tới khi ra khỏi nhóm hiện tại.
     * Sản phẩm không bao phim thì từ Định hình sẽ nhảy thẳng sang Đóng gói.
     *
     * Trả về MẢNG vì một code có thể ứng với nhiều dòng: lô đóng gói một phần
     * tách thành nhiều lô con dùng chung code, mỗi lô con có khung giờ và
     * percent_parkaging riêng.
     *
     * @return array danh sách dòng công đoạn kế tiếp, rỗng nếu không tìm được
     */
    private function nextGroupRows($row, array $byCode, string $groupCode): array
    {
        $stageCodes = self::STAGE_GROUPS[$groupCode];
        $cursor = $row;
        $guard = 0;

        while ($guard++ < 12) {
            $nextCode = $cursor->nextcessor_code ?? null;
            if ($nextCode === null || $nextCode === '' || empty($byCode[$nextCode])) {
                return [];
            }

            $candidates = $byCode[$nextCode];

            if (! in_array((int) $candidates[0]->stage_code, $stageCodes, true)) {
                return $candidates;
            }

            // Còn trong cùng nhóm thì đi tiếp. Các dòng cùng code luôn cùng công đoạn
            // nên lấy dòng đầu làm con trỏ là đủ.
            $cursor = $candidates[0];
        }

        return [];
    }

    /**
     * Tỉ lệ đầu ra của công đoạn trước mà mỗi lô con phía sau sẽ tiêu thụ.
     * Chỉ một lô con thì lấy trọn; nhiều lô con thì chia theo percent_parkaging.
     */
    private function successorWeight(array $successors, $successor): float
    {
        if (count($successors) <= 1) {
            return 1.0;
        }

        $pct = $successor->percent_parkaging ?? null;
        if ($pct !== null && (float) $pct > 0) {
            return (float) $pct;
        }

        return 1.0 / count($successors);
    }

    /**
     * Số giờ máy mà một lô sẽ chiếm ở công đoạn sau, tách theo công đoạn.
     *
     * Lô đóng gói một phần tách thành nhiều lô con, mỗi lô con chiếm giờ máy theo
     * đúng tỉ lệ của nó. Mã nào chưa khai định mức thì lấy trung vị của công đoạn
     * đó thay vì bỏ qua, để không tính thiếu tải.
     *
     * @return array<int, float> [stage_code => giờ máy]
     */
    private function consumerLoad(array $successors, array $capacity): array
    {
        $load = [];

        foreach ($successors as $successor) {
            $stage = (int) $successor->stage_code;

            $key = $stage >= 7
                ? ($successor->finished_product_code ?? null)
                : ($successor->intermediate_code ?? null);

            $hours = $capacity['hours'][$stage][$key] ?? null;
            if ($hours === null) {
                $hours = $capacity['median'][$stage] ?? null;
            }
            if ($hours === null || $hours <= 0) {
                continue;
            }

            $load[$stage] = ($load[$stage] ?? 0.0)
                + $hours * $this->successorWeight($successors, $successor);
        }

        return $load;
    }

    /**
     * Giờ máy mà phần tồn còn lại của một sổ đặt lên từng công đoạn tiêu thụ.
     *
     * @return array<int, float> [stage_code => giờ máy]
     */
    private function loadHoursAt(array $ledger, string $at): array
    {
        $hoursByStage = [];

        foreach ($ledger as $lot) {
            if ($lot['load'] === [] || $lot['qty_dvl'] <= 0) {
                continue;
            }

            $remaining = $this->lotStockAt($lot, $at);
            if ($remaining <= 0) {
                continue;
            }

            $fraction = $remaining / $lot['qty_dvl'];

            foreach ($lot['load'] as $stage => $hours) {
                $hoursByStage[$stage] = ($hoursByStage[$stage] ?? 0.0) + $hours * $fraction;
            }
        }

        return $hoursByStage;
    }

    /**
     * Số ngày đáp ứng của TẤT CẢ các nhóm tại một mốc thời gian.
     *
     * Một công đoạn thường được nhiều kho nuôi cùng lúc: Đóng gói nhận hàng từ Bao
     * phim, từ Định hình với sản phẩm không bao phim, và từ Pha chế với sản phẩm
     * không định hình. Ba kho đó cùng chia nhau một dây chuyền Đóng gói, nên nhịp
     * chạy của công đoạn được chia cho các kho theo đúng tỉ lệ giờ máy mà mỗi kho
     * đang gửi tới. Nếu bỏ qua bước chia này, kho nào cũng tưởng mình dùng trọn
     * công suất và nhánh phụ nhỏ xíu sẽ kéo con số xuống vô lý.
     *
     * @param array<string, array> $ledgers
     * @return array<string, array{days: ?float, hours: float, by_stage: array, rate_by_stage: array}>
     */
    private function coverByCapacity(array $ledgers, string $at, array $capacity): array
    {
        $hoursByGroup = [];
        $totalByStage = [];

        foreach ($ledgers as $groupCode => $ledger) {
            $hoursByGroup[$groupCode] = $this->loadHoursAt($ledger, $at);

            foreach ($hoursByGroup[$groupCode] as $stage => $hours) {
                $totalByStage[$stage] = ($totalByStage[$stage] ?? 0.0) + $hours;
            }
        }

        $result = [];

        foreach ($hoursByGroup as $groupCode => $hoursByStage) {
            $totalHours = 0.0;
            $totalRate = 0.0;
            $byStage = [];
            $rateByStage = [];
            $unknownRate = false;

            foreach ($hoursByStage as $stage => $hours) {
                $totalHours += $hours;

                $rate = $capacity['rate'][$stage] ?? 0.0;
                if ($rate <= 0 || ($totalByStage[$stage] ?? 0) <= 0) {
                    // Công đoạn chưa từng chạy trong cửa sổ đo thì không suy ra nhịp được
                    $byStage[$stage] = ['hours' => round($hours, 1), 'rate' => null, 'days' => null];
                    $unknownRate = true;
                    continue;
                }

                $share = $rate * $hours / $totalByStage[$stage];
                $totalRate += $share;
                $rateByStage[$stage] = round($share, 2);

                $byStage[$stage] = [
                    'hours' => round($hours, 1),
                    'rate'  => round($share, 2),
                    'days'  => round($hours / $share, 2),
                ];
            }

            // Kho rút cạn qua nhiều công đoạn cùng lúc nên cộng nhịp của chúng lại
            $result[$groupCode] = [
                'days'          => ($totalRate > 0 && ! $unknownRate) ? round($totalHours / $totalRate, 2) : null,
                'hours'         => round($totalHours, 1),
                'by_stage'      => $byStage,
                'rate_by_stage' => $rateByStage,
            ];
        }

        return $result;
    }

    /** Nhịp chạy và số phòng của các công đoạn tiêu thụ mà nhóm này nuôi */
    private function capacityBasis(array $capacity, array $allocated): array
    {
        $basis = [];

        foreach ($allocated as $stage => $share) {
            $basis[] = [
                'stage_code'     => $stage,
                'stage_group'    => $this->groupOfStage($stage),
                'rooms'          => $capacity['rooms'][$stage] ?? 0,
                // Nhịp của cả công đoạn, và phần mà riêng kho này được hưởng
                'hours_per_day'  => $capacity['rate'][$stage] ?? null,
                'share_per_day'  => $share,
                'lots_measured'  => $capacity['lots'][$stage] ?? 0,
                'window_days'    => $capacity['days'],
            ];
        }

        usort($basis, fn($a, $b) => $a['stage_code'] <=> $b['stage_code']);

        return $basis;
    }

    private function groupOfStage(int $stageCode): ?string
    {
        foreach (self::STAGE_GROUPS as $code => $stages) {
            if (in_array($stageCode, $stages, true)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Quy sản lượng về đơn vị liều.
     * Công đoạn tới Trộn hoàn tất tính bằng Kg nên phải nhân hệ số batch_qty/batch_size;
     * từ Định hình trở đi vốn đã là đơn vị liều.
     */
    private function toDvl($row): float
    {
        $raw = (float) ($row->theoretical_yields ?? 0);

        if ((int) ($row->finished ?? 0) === 1 && (float) ($row->yields ?? 0) > 0) {
            $raw = (float) $row->yields;
        }

        if ($raw <= 0) {
            return 0.0;
        }

        if ((int) $row->stage_code > 4) {
            return $raw;
        }

        $batchSize = (float) ($row->batch_size ?? 0);
        $batchQty  = (float) ($row->batch_qty ?? 0);

        if ($batchSize <= 0 || $batchQty <= 0) {
            return $raw;
        }

        return $raw * ($batchQty / $batchSize);
    }

    /**
     * Tồn tại một mốc thời gian bất kỳ.
     *
     * Một lô nằm trong kho khi công đoạn nguồn đã bắt đầu trước mốc đó, trừ đi
     * phần đã bị các lô con phía sau rút ra. Nhờ xét lại cả hai chiều nên hàm này
     * phản ánh đúng cả hàng mới nhập lẫn hàng đã xuất, chứ không chỉ trừ dần.
     *
     * @return array{total: float, count: int, orphans: int, lots: array}
     */
    private function stockAt(array $ledger, string $at): array
    {
        $total = 0.0;
        $count = 0;
        $orphans = 0;
        $lots = [];

        foreach ($ledger as $lot) {
            $qty = $this->lotStockAt($lot, $at);
            if ($qty <= 0) {
                continue;
            }

            $total += $qty;
            $count++;

            if ($lot['orphan']) {
                $orphans++;
            }

            $lots[] = [
                'plan_master_id'    => $lot['plan_master_id'],
                'batch'             => $lot['batch'],
                'intermediate_code' => $lot['intermediate_code'],
                'product_name'      => $lot['product_name'],
                'unit'              => $lot['unit'],
                'stage_code'        => $lot['stage_code'],
                'start'             => $lot['entry'],
                'qty_dvl'           => $qty,
            ];
        }

        return ['total' => $total, 'count' => $count, 'orphans' => $orphans, 'lots' => $lots];
    }

    /**
     * Lượng của MỘT lô còn nằm trong kho tại một mốc thời gian.
     *
     * Đây là hàm gốc duy nhất định nghĩa tồn. Cột nhập và xuất từng ngày cũng
     * suy ra từ chênh lệch của chính hàm này, nhờ vậy đường tồn và hai cột luôn
     * khớp nhau tuyệt đối, kể cả với dữ liệu lỗi như công đoạn sau được xếp
     * trước công đoạn trước, hay tổng tỉ lệ các lô con vượt quá 100%.
     */
    private function lotStockAt(array $lot, string $at): float
    {
        // Chưa sắp lịch hoặc chưa tới lượt chạy thì chưa có hàng trong kho
        if ($lot['entry'] === null || $lot['entry'] > $at) {
            return 0.0;
        }

        $consumed = 0.0;
        foreach ($lot['exits'] as $exit) {
            if ($exit['start'] <= $at) {
                $consumed += $exit['weight'];
            }
        }

        return $lot['qty_dvl'] * max(0.0, 1.0 - $consumed);
    }

    /**
     * Biến thiên tồn kho: tính LẠI tồn tại 06:00 của từng ngày theo lịch đã sắp,
     * kèm lượng nhập và xuất trong ngày đó, và số ngày mà lượng tồn tại chính mốc
     * đó còn đáp ứng được cho công đoạn sau.
     */
    private function buildSeries(array $days, array $ledgers, array $capacity): array
    {
        $series = array_fill_keys(array_keys($ledgers), []);

        foreach ($days as $day) {
            $from = $day['start']->format('Y-m-d H:i:s');
            $to   = $day['end']->format('Y-m-d H:i:s');

            // Đứng ở đúng mốc đó, tồn còn nuôi công đoạn sau được bao lâu.
            //
            // Mẫu số là công suất công đoạn sau chứ không phải lịch đã sắp. Nếu lấy
            // lịch làm mẫu số thì càng về cuối khoảng dự báo càng ít ngày để trừ,
            // ngày nào cũng ra "còn dư" bất kể tồn bao nhiêu; mà lịch thường mới sắp
            // được hai tuần nên phần sau gần như không có nhu cầu nào để trừ.
            $cover = $this->coverByCapacity($ledgers, $from, $capacity);

            foreach ($ledgers as $groupCode => $ledger) {
                // Nhập và xuất phải tính theo ĐÚNG mốc mà tồn thay đổi, tức thời điểm
                // công đoạn bắt đầu, chứ không chia đều theo khung giờ chạy. Nếu chia
                // đều thì cột nhập trừ xuất sẽ không giải thích được bước nhảy của
                // đường tồn, có ngày còn ngược dấu, nhìn vào tưởng sai số liệu.
                $in = 0.0;
                $out = 0.0;

                foreach ($ledger as $lot) {
                    $delta = $this->lotStockAt($lot, $to) - $this->lotStockAt($lot, $from);

                    if ($delta > 0) {
                        $in += $delta;
                    } else {
                        $out -= $delta;
                    }
                }

                $snapshot = $this->stockAt($ledger, $from);

                $series[$groupCode][] = [
                    'date'          => $day['date'],
                    'stock_dvl'     => round($snapshot['total'], 2),
                    'stock_lots'    => $snapshot['count'],
                    'in_dvl'        => round($in, 2),
                    'out_dvl'       => round($out, 2),
                    'days_of_cover' => $cover[$groupCode]['days'],
                    'load_hours'    => $cover[$groupCode]['hours'],
                    'rate_by_stage' => $cover[$groupCode]['rate_by_stage'],
                ];
            }
        }

        return $series;
    }

    /**
     * Tổng hợp từ chuỗi biến thiên: tổng lượng đã sắp lịch xuất, ngày tồn chạm
     * đáy, và ngày đầu tiên tồn cạn hẳn trong khoảng dự báo.
     */
    private function coverFromSeries(array $series): array
    {
        if ($series === []) {
            return [
                'shortage_date' => null, 'demand_total' => 0.0,
                'lowest' => null, 'lowest_date' => null,
            ];
        }

        $demandTotal = 0.0;
        foreach ($series as $point) {
            $demandTotal += (float) $point['out_dvl'];
        }

        // Ngày mà tồn xuống thấp nhất, và ngày đầu tiên tồn cạn hẳn
        $lowest = null;
        $lowestDate = null;
        $shortageDate = null;

        foreach ($series as $point) {
            $value = (float) $point['stock_dvl'];

            if ($lowest === null || $value < $lowest) {
                $lowest = $value;
                $lowestDate = $point['date'];
            }

            if ($shortageDate === null && $value <= 0) {
                $shortageDate = $point['date'];
            }
        }

        return [
            'shortage_date' => $shortageDate,
            'demand_total'  => round($demandTotal, 2),
            'lowest'        => $lowest === null ? null : round($lowest, 2),
            'lowest_date'   => $lowestDate,
        ];
    }

    /**
     * Lặp lại phép tính nhưng gom theo mã bán thành phẩm.
     *
     * Ở cấp mã, con số ngày mang nghĩa khác cấp nhóm: nó là phần TẢI mà riêng mã
     * này đặt lên công đoạn sau, quy ra ngày. Cộng tải của mọi mã thì đúng bằng số
     * ngày đáp ứng của cả nhóm, nên mã nào đứng đầu bảng là mã đang chiếm chỗ nhiều
     * nhất chứ không phải mã sắp cạn.
     */
    private function buildDetails(array $stock, array $ledger, array $days, string $at, float $groupRate): array
    {
        $stockByCode = [];
        foreach ($stock as $lot) {
            $code = $lot['intermediate_code'] ?? '(không rõ)';
            if (! isset($stockByCode[$code])) {
                $stockByCode[$code] = [
                    'intermediate_code' => $code,
                    'product_name'      => $lot['product_name'],
                    'unit'              => $lot['unit'],
                    'stock_dvl'         => 0.0,
                    'stock_lots'        => 0,
                    'batches'           => [],
                ];
            }

            $stockByCode[$code]['stock_dvl'] += $lot['qty_dvl'];
            $stockByCode[$code]['stock_lots']++;
            $stockByCode[$code]['batches'][] = [
                'plan_master_id' => $lot['plan_master_id'],
                'batch'          => $lot['batch'],
                'stage_code'     => $lot['stage_code'],
                'start'          => $lot['start'],
                'qty_dvl'        => round($lot['qty_dvl'], 2),
            ];
        }

        // Lượng xuất kho theo từng mã, tính cùng quy ước với chuỗi tổng: theo đúng
        // mốc công đoạn sau bắt đầu
        $ledgerByCode = [];
        foreach ($ledger as $lot) {
            $ledgerByCode[$lot['intermediate_code'] ?? '(không rõ)'][] = $lot;
        }

        // Ngày mà mã này xuất kho lần cuối trong khoảng dự báo, để biết còn lịch
        // tiêu thụ tới đâu
        $details = [];
        foreach ($stockByCode as $code => $entry) {
            $lastOutDate = null;
            foreach ($days as $day) {
                $from = $day['start']->format('Y-m-d H:i:s');
                $to   = $day['end']->format('Y-m-d H:i:s');

                foreach ($ledgerByCode[$code] ?? [] as $lot) {
                    if ($this->lotStockAt($lot, $to) - $this->lotStockAt($lot, $from) < 0) {
                        $lastOutDate = $day['date'];
                        break;
                    }
                }
            }

            // Chia cho nhịp của CẢ nhóm chứ không phải nhịp riêng từng công đoạn,
            // nhờ vậy cộng số ngày của mọi mã lại thì đúng bằng số ngày đáp ứng của
            // cả nhóm, đọc được là "mã này chiếm bao nhiêu ngày trong số đó"
            $hours = array_sum($this->loadHoursAt($ledgerByCode[$code] ?? [], $at));

            $details[] = [
                'intermediate_code'   => $code,
                'product_name'        => $entry['product_name'],
                'unit'                => $entry['unit'],
                'stock_dvl'           => round($entry['stock_dvl'], 2),
                'stock_lots'          => $entry['stock_lots'],
                'load_hours'          => round($hours, 1),
                'days_of_cover'       => $groupRate > 0 ? round($hours / $groupRate, 2) : null,
                'last_out_date'       => $lastOutDate,
                'batches'             => $entry['batches'],
            ];
        }

        // Mã chiếm nhiều giờ máy của công đoạn sau nhất lên đầu
        usort($details, fn($a, $b) => $b['load_hours'] <=> $a['load_hours']);

        return $details;
    }

    /**
     * Đối chiếu số ngày đáp ứng với ngưỡng đã cấu hình.
     *
     * $daysOfCover rỗng nghĩa là không quy ra ngày được: hoặc lô không có công đoạn
     * sau nào, hoặc công đoạn sau chưa từng chạy trong cửa sổ đo nên không suy ra
     * được nhịp. Cả hai đều là chuyện dữ liệu, không phải chuyện tồn kho, nên báo
     * no_demand để người dùng đi kiểm tra chứ không tô màu xanh cho yên tâm hão.
     */
    public static function resolveStatus(?float $daysOfCover, ?object $threshold, bool $hasDemand = true): string
    {
        if ($daysOfCover === null) {
            return $hasDemand ? 'ok' : 'no_demand';
        }

        $critical = $threshold->critical_days ?? 1;
        $warn     = $threshold->warn_days ?? 3;

        if ($critical !== null && $daysOfCover < (float) $critical) {
            return 'critical';
        }

        if ($warn !== null && $daysOfCover < (float) $warn) {
            return 'warn';
        }

        return 'ok';
    }

    /** Ngưỡng của một phân xưởng, tự bù giá trị mặc định cho nhóm chưa cấu hình */
    public static function thresholdsFor(string $productionCode): array
    {
        $rows = DB::table('wip_coverage_thresholds')
            ->where('production_code', $productionCode)
            ->get()
            ->keyBy('stage_group_code');

        $result = [];
        foreach (self::SOURCE_GROUPS as $groupCode) {
            $result[$groupCode] = $rows[$groupCode] ?? (object) [
                'production_code'  => $productionCode,
                'stage_group_code' => $groupCode,
                'critical_days'    => 1,
                'warn_days'        => 3,
                'horizon_days'     => self::DEFAULT_HORIZON_DAYS,
                'is_active'        => 1,
            ];
        }

        return $result;
    }
}
