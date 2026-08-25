<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Thống kê tồn bán thành phẩm lý thuyết đang chờ để bước vào công đoạn kế
 * tiếp, dựa trên lịch đã sắp.
 *
 * Tồn được gom theo CÔNG ĐOẠN ĐÍCH — Định hình, Bao phim, Đóng gói — chứ
 * không theo công đoạn nguồn. Một lô đang chờ Đóng gói thì tính vào tổng
 * "chờ Đóng gói" bất kể nó xuất phát từ Pha chế, Định hình hay Bao phim,
 * vì đó chính là câu hỏi người dùng quan tâm: công đoạn tiếp theo còn bao
 * nhiêu để chạy.
 *
 * Đích của mỗi lô tự hiện ra từ dữ liệu qua bộ ba stage_plan.code,
 * predecessor_code và nextcessor_code, không khai cứng danh sách luồng.
 *
 * Lưu ý hiệu năng quan trọng: stage_plan.code và stage_plan.nextcessor_code
 * là quan hệ cha con nhưng phép tự nối bảng qua chúng rất tốn kém khi bảng lớn.
 * Vì vậy toàn bộ việc đi theo chuỗi công đoạn được làm trong PHP trên một
 * bảng tra dựng sẵn, không nối bảng trong SQL.
 */
class WipCoverageService
{
    /** Nhóm công đoạn, theo đúng thứ tự sản xuất */
    public const STAGE_GROUPS = [
        'PC' => [3, 4],   // Pha chế + Trộn hoàn tất, đầu ra lấy ở công đoạn lớn nhất có thật
        'DH' => [5],      // Định hình
        'BP' => [6],      // Bao phim
        'DG' => [7],      // Đóng gói, chỉ nhận hàng chứ không sinh tồn
    ];

    /** Các nhóm thực sự sinh ra tồn bán thành phẩm, đóng vai trò công đoạn nguồn */
    public const SOURCE_GROUPS = ['PC', 'DH', 'BP'];

    /**
     * Ba công đoạn đích mà người dùng quan tâm: tồn đang chờ để bước vào đâu.
     * Đây cũng là thứ tự hiển thị trên biểu đồ và bảng.
     */
    public const NEXT_GROUPS = ['DH', 'BP', 'DG'];

    public const GROUP_NAMES = [
        'PC' => 'Pha chế',
        'DH' => 'Định hình',
        'BP' => 'Bao phim',
        'DG' => 'Đóng gói',
    ];

    /** Mã nhóm cho lô chưa lần ra được công đoạn kế tiếp */
    public const NO_NEXT = 'NA';

    /** Mã giả cho nguồn vào Pha chế, dùng khi tra lô theo cột "Pha chế nhập vào" */
    public const SUPPLY = 'SUPPLY';

    /** Tên hiển thị theo mã công đoạn, dùng cho modal xem lô */
    private const STAGE_NAMES = [
        3 => 'Pha chế',
        4 => 'Trộn hoàn tất',
        5 => 'Định hình',
        6 => 'Bao phim',
        7 => 'Đóng gói',
    ];

    public const DEFAULT_HORIZON_DAYS = 30;

    /** Giờ bắt đầu ngày công của nhà máy */
    public const DAY_START_HOUR = 6;

    /**
     * @return array{
     *     production_code: string,
     *     snapshot_at: string,
     *     horizon_days: int,
     *     groups: array<int, array<string, mixed>>,
     *     supply: array<int, array<string, mixed>>
     * }
     */
    public function compute(string $productionCode, Carbon $at, int $horizonDays = self::DEFAULT_HORIZON_DAYS): array
    {
        $at = $at->copy();
        $horizonDays = max(1, min(180, $horizonDays));

        $rows = $this->loadStagePlans($productionCode, $at, $horizonDays);
        $lookup = $this->buildLookups($rows);

        $days = $this->buildDayWindows($at, $horizonDays);

        // Gom các dòng theo lô một lần rồi dùng chung cho mọi nhóm đích
        $byPlanMaster = [];
        foreach ($rows as $row) {
            $byPlanMaster[$row->plan_master_id][] = $row;
        }

        $ledgers = $this->buildLedgers($byPlanMaster, $lookup);
        $series  = $this->buildSeries($days, $ledgers);
        $supply  = $this->buildSupplySeries($days, $byPlanMaster);

        $groups = [];
        foreach ($ledgers as $groupCode => $lots) {
            $groups[] = $this->assembleGroup($groupCode, $lots, $at, $days, $horizonDays, $series[$groupCode]);
        }

        return [
            'production_code' => $productionCode,
            'snapshot_at'     => $at->format('Y-m-d H:i:s'),
            'horizon_days'    => $horizonDays,
            'groups'          => $groups,
            'supply'          => $supply,
        ];
    }

    /** Tên hiển thị của một nhóm đích, kể cả nhóm chưa rõ công đoạn sau */
    public static function groupName(string $code): string
    {
        if ($code === self::NO_NEXT) {
            return 'Chưa rõ công đoạn sau';
        }

        return self::GROUP_NAMES[$code] ?? $code;
    }

    /**
     * Danh sách lô cấu thành một con số cụ thể trên bảng ngày: bấm vào tồn,
     * nhập, xuất của một nhóm, hoặc sản lượng Pha chế nhập vào của một ngày,
     * là xem được ngay lô nào gộp lại thành số đó.
     *
     * @param string $groupCode DH | BP | DG | NA | SUPPLY
     * @param string $kind      stock | in | out — bỏ qua khi groupCode = SUPPLY
     * @return array{rows: array, total_dvl: float, total_kg: ?float}
     */
    public function dayLots(
        string $productionCode,
        Carbon $at,
        int $horizonDays,
        string $groupCode,
        string $date,
        string $kind
    ): array {
        $at = $at->copy();
        $horizonDays = max(1, min(180, $horizonDays));

        $rows = $this->loadStagePlans($productionCode, $at, $horizonDays);
        $lookup = $this->buildLookups($rows);

        $byPlanMaster = [];
        foreach ($rows as $row) {
            $byPlanMaster[$row->plan_master_id][] = $row;
        }

        $dayStart = Carbon::parse($date)->setTime(self::DAY_START_HOUR, 0, 0);
        $from = $dayStart->format('Y-m-d H:i:s');
        $to   = $dayStart->copy()->addDay()->format('Y-m-d H:i:s');

        if ($groupCode === self::SUPPLY) {
            return $this->supplyLotsOfDay($byPlanMaster, $from, $to);
        }

        $ledgers = $this->buildLedgers($byPlanMaster, $lookup);

        return $this->lotsOfDay($ledgers[$groupCode] ?? [], $from, $to, $kind);
    }

    /** Lô cấu thành tồn / nhập / xuất của MỘT nhóm trong MỘT ngày */
    private function lotsOfDay(array $lots, string $from, string $to, string $kind): array
    {
        $rows = [];
        $total = 0.0;

        foreach ($lots as $lot) {
            if ($kind === 'stock') {
                $qty = $this->lotStockAt($lot, $from);
                if ($qty <= 0) {
                    continue;
                }
                $rows[] = $this->lotRow($lot, $qty, $this->nextMomentOf($lot, $from));
                $total += $qty;
                continue;
            }

            if ($kind === 'in') {
                // Tồn chỉ tăng đúng lúc lô nhập kho, không bao giờ tăng lại sau đó,
                // nên chênh lệch dương giữa hai đầu ngày chính là lô mới nhập trong
                // ngày đó — không cần tra riêng cột entry.
                $delta = $this->lotStockAt($lot, $to) - $this->lotStockAt($lot, $from);
                if ($delta <= 1e-9) {
                    continue;
                }
                $rows[] = $this->lotRow($lot, $delta, $this->nextMomentOf($lot, $to));
                $total += $delta;
                continue;
            }

            if ($kind === 'out') {
                // Xuất theo TỪNG đợt rút, không gộp: một lô đóng gói một phần có thể
                // xuất cho nhiều lô con ở nhiều mốc khác nhau trong cùng một ngày.
                foreach ($lot['exits'] as $exit) {
                    if ($exit['start'] < $from || $exit['start'] >= $to) {
                        continue;
                    }
                    $qty = $lot['qty_dvl'] * $exit['weight'];
                    if ($qty <= 1e-9) {
                        continue;
                    }
                    $rows[] = $this->lotRow($lot, $qty, $exit['start']);
                    $total += $qty;
                }
            }
        }

        usort($rows, fn($a, $b) => $b['qty_dvl'] <=> $a['qty_dvl']);

        return ['rows' => $rows, 'total_dvl' => round($total, 2), 'total_kg' => null];
    }

    /** Lô Pha chế xuất trong MỘT ngày, không quy về nhóm đích nào — đó là nguồn chung */
    private function supplyLotsOfDay(array $byPlanMaster, string $from, string $to): array
    {
        $stageCodes = self::STAGE_GROUPS['PC'];

        $rows = [];
        $totalDvl = 0.0;
        $totalKg = 0.0;

        foreach ($byPlanMaster as $planRows) {
            $outRow = $this->outputRowOfGroup($planRows, $stageCodes);
            if ($outRow === null) {
                continue;
            }

            $moment = self::stageMoment($outRow);
            if ($moment === null || $moment < $from || $moment >= $to) {
                continue;
            }

            $dvl = $this->toDvl($outRow);
            if ($dvl <= 0) {
                continue;
            }

            $kg = $this->rawYield($outRow);

            $rows[] = [
                'intermediate_code' => $outRow->intermediate_code ?? null,
                'product_name'      => $outRow->product_name ?? null,
                'batch'             => $outRow->batch ?? null,
                'stage_code'        => (int) $outRow->stage_code,
                'stage_name'        => self::STAGE_NAMES[(int) $outRow->stage_code] ?? null,
                'prev_moment'       => $moment,
                // Một mẻ Pha chế có thể tách đi nhiều hướng khác nhau (có lô lên
                // Định hình, có lô đi thẳng Đóng gói), không quy về một mốc duy nhất
                'next_moment'       => null,
                'qty_dvl'           => round($dvl, 2),
                'qty_kg'            => round($kg, 2),
                'unit'              => $outRow->unit_batch_qty ?? null,
            ];

            $totalDvl += $dvl;
            $totalKg  += $kg;
        }

        usort($rows, fn($a, $b) => $b['qty_dvl'] <=> $a['qty_dvl']);

        return ['rows' => $rows, 'total_dvl' => round($totalDvl, 2), 'total_kg' => round($totalKg, 2)];
    }

    /** Dòng hiển thị chuẩn hoá cho modal xem lô, dùng chung cho tồn/nhập/xuất */
    private function lotRow(array $lot, float $qty, ?string $nextMoment): array
    {
        return [
            'intermediate_code' => $lot['intermediate_code'],
            'product_name'      => $lot['product_name'],
            'batch'             => $lot['batch'],
            'stage_code'        => $lot['stage_code'],
            'stage_name'        => self::STAGE_NAMES[$lot['stage_code']] ?? null,
            'prev_moment'       => $lot['entry'],
            'next_moment'       => $nextMoment,
            'qty_dvl'           => round($qty, 2),
            'qty_kg'            => null,
            'unit'              => $lot['unit'],
        ];
    }

    /** Mốc rút hàng gần nhất kể từ một thời điểm, để biết lô này còn hẹn công đoạn sau lúc nào */
    private function nextMomentOf(array $lot, string $from): ?string
    {
        $next = null;
        foreach ($lot['exits'] as $exit) {
            if ($exit['start'] < $from) {
                continue;   // đã xảy ra trước mốc này rồi, không còn là "sắp tới"
            }
            if ($next === null || $exit['start'] < $next) {
                $next = $exit['start'];
            }
        }

        return $next;
    }

    /**
     * Một truy vấn phẳng duy nhất. Không nối stage_plan với chính nó.
     *
     * Phạm vi thời gian: lấy rộng về quá khứ để không bỏ sót lô đã bắt đầu từ lâu
     * mà công đoạn sau vẫn chưa chạy, và lấy tới hết khoảng dự báo cho phía sau.
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
                // Mốc chạy thật, dùng để vá các dòng đã hoàn thành mà mất lịch
                'sp.actual_start',
                'sp.actual_end',
                'sp.finished_date',
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
     * Hai bảng tra dùng để đi từ một công đoạn sang công đoạn kế tiếp.
     *
     * by_code: code -> DANH SÁCH dòng. stage_plan.code KHÔNG duy nhất: lô đóng gói
     * một phần sinh nhiều dòng công đoạn 7 dùng chung một code nhưng khác
     * plan_master_id, mỗi dòng mang percent_parkaging riêng (xem
     * ProductionPlanController quanh dòng 2261). Nếu tra bằng keyBy('code') thì chỉ
     * giữ được một dòng và tính thiếu lượng xuất.
     *
     * by_predecessor: predecessor_code -> DANH SÁCH dòng, dùng khi nextcessor_code
     * bỏ trống. Hai cột này khai cùng một quan hệ theo hai chiều, dòng nào thiếu
     * chiều xuôi thì vẫn còn chiều ngược để lần ra công đoạn sau.
     *
     * Công đoạn kế tiếp cũng có thể nằm ở phân xưởng khác nên phải nạp bù các code
     * còn thiếu mà không lọc theo deparment_code.
     *
     * @return array{by_code: array, by_predecessor: array}
     */
    private function buildLookups($rows): array
    {
        $byCode = [];
        $all = [];

        foreach ($rows as $row) {
            $all[] = $row;
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
                    'sp.actual_start',
                    'sp.actual_end',
                    'sp.finished_date',
                    'sp.Theoretical_yields as theoretical_yields',
                    'sp.yields',
                    'pm.percent_parkaging',
                    'fpc.intermediate_code',
                    'fpc.finished_product_code'
                )
                ->get();

            foreach ($extra as $row) {
                $all[] = $row;
                $byCode[$row->code][] = $row;
            }
        }

        $byPredecessor = [];
        foreach ($all as $row) {
            $prev = $row->predecessor_code ?? null;
            if ($prev !== null && $prev !== '') {
                $byPredecessor[$prev][] = $row;
            }
        }

        return ['by_code' => $byCode, 'by_predecessor' => $byPredecessor];
    }

    /**
     * Mốc mà một công đoạn chiếm chỗ trên trục thời gian.
     *
     * Đây là màn tồn LÝ THUYẾT nên lịch đã sắp vẫn là căn cứ chính. Nhưng rất
     * nhiều dòng chạy xong lại bị xoá mất `start`: riêng Đóng gói của PXV1 có
     * 340 dòng như vậy. Nếu chỉ nhìn `start` thì công đoạn sau coi như chưa bao
     * giờ rút hàng, lô nằm lại trong kho vĩnh viễn — biểu đồ đang hiện cả tồn
     * từ tháng 11 năm ngoái, gần 60% con số là hàng đã đóng gói xong từ lâu.
     *
     * Vì vậy dòng đã hoàn thành thì lấy mốc chạy thật: đó là sự thật chắc chắn
     * hơn lịch, và cũng xử lý luôn vài dòng finished mà `start` còn nằm ở tương
     * lai. Dòng chưa hoàn thành thì vẫn theo đúng lịch, chưa sắp lịch nghĩa là
     * chưa diễn ra.
     */
    private static function stageMoment($row): ?string
    {
        if ((int) ($row->finished ?? 0) === 1) {
            foreach (['actual_start', 'start', 'finished_date', 'actual_end'] as $field) {
                if (! empty($row->$field)) {
                    return $row->$field;
                }
            }

            return null;
        }

        return empty($row->start) ? null : $row->start;
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
     * Sổ nhập xuất của TỪNG NHÓM ĐÍCH: mỗi lô vào kho lúc nào và bị công đoạn
     * sau rút ra lúc nào.
     *
     * Với mỗi lô, đi theo chuỗi công đoạn cho tới khi ra khỏi nhóm hiện tại;
     * nhóm chạm tới đầu tiên chính là công đoạn mà lô này đang CHỜ để bước vào,
     * và lô được cộng thẳng vào sổ của nhóm đó — bất kể nó xuất phát từ Pha chế,
     * Định hình hay Bao phim. Nhờ vậy "chờ Đóng gói" luôn là MỘT con số duy
     * nhất, gộp cả hàng đi đủ tuần tự lẫn hàng bỏ qua Bao phim hoặc Định hình.
     *
     * @return array<string, array> [group_code => danh sách lô]
     */
    private function buildLedgers(array $byPlanMaster, array $lookup): array
    {
        $ledgers = [];

        foreach (self::SOURCE_GROUPS as $sourceCode) {
            $stageCodes = self::STAGE_GROUPS[$sourceCode];

            foreach ($byPlanMaster as $planRows) {
                $outRow = $this->outputRowOfGroup($planRows, $stageCodes);
                if ($outRow === null) {
                    continue;
                }

                $qtyDvl = $this->toDvl($outRow);
                if ($qtyDvl <= 0) {
                    continue;
                }

                $successors = $this->nextGroupRows($outRow, $lookup, $sourceCode);

                $nextGroup = $successors === []
                    ? null
                    : $this->groupOfStage((int) $successors[0]->stage_code);

                $entry = self::stageMoment($outRow);

                // Thời điểm lô này nhập kho, và các thời điểm từng phần bị rút ra
                $exits = [];
                foreach ($successors as $successor) {
                    $moment = self::stageMoment($successor);

                    // Đã chạy xong nhưng không còn mốc nào để lần ra thời điểm:
                    // hàng chắc chắn đã bị rút, chỉ là không biết lúc nào. Cho rút
                    // ngay khi nhập để nó khỏi đọng lại thành tồn ma, thay vì bỏ
                    // qua và tính lô này còn nguyên trong kho mãi mãi.
                    if ($moment === null && (int) ($successor->finished ?? 0) === 1) {
                        $moment = $entry;
                    }

                    if ($moment === null) {
                        continue;   // chưa sắp lịch và chưa chạy, hàng vẫn còn chờ
                    }

                    $exits[] = [
                        'start'  => $moment,
                        'weight' => $this->successorWeight($successors, $successor),
                    ];
                }

                // Mọi lô con phía sau đều đã chạy xong thì lô này chắc chắn không
                // còn gì để chờ. Nhưng percent_parkaging làm tròn 4 chữ số nên
                // cộng lại thường ra 0,9999 chứ không tròn 1; để nguyên thì mỗi lô
                // đóng gói một phần đọng lại một mẩu tí xíu, gom cả trăm lô lại
                // thành một dãy tồn ma kéo dài từ năm ngoái. Đã biết chắc hàng đi
                // hết thì chuẩn hoá cho tổng đúng bằng 1.
                $allConsumed = $successors !== []
                    && count($exits) === count($successors)
                    && ! array_filter($successors, fn($s) => (int) ($s->finished ?? 0) !== 1);

                if ($allConsumed) {
                    $sum = array_sum(array_column($exits, 'weight'));
                    if ($sum > 0) {
                        foreach ($exits as $i => $exit) {
                            $exits[$i]['weight'] = $exit['weight'] / $sum;
                        }
                    }
                }

                $groupCode = $nextGroup ?? self::NO_NEXT;

                $ledgers[$groupCode][] = [
                    'plan_master_id'    => $outRow->plan_master_id,
                    'batch'             => $outRow->batch ?? null,
                    'intermediate_code' => $outRow->intermediate_code ?? null,
                    'product_name'      => $outRow->product_name ?? null,
                    'unit'              => $outRow->unit_batch_qty ?? null,
                    'stage_code'        => (int) $outRow->stage_code,   // công đoạn hiện đang giữ lô, tức nguồn
                    'qty_dvl'           => $qtyDvl,
                    'entry'             => $entry,   // null nghĩa là chưa sắp lịch và chưa chạy, không bao giờ nhập kho
                    'exits'             => $exits,
                ];
            }
        }

        return $this->sortGroups($ledgers);
    }

    /**
     * Sản lượng Pha chế nhập vào dây chuyền mỗi ngày.
     *
     * Pha chế là nguồn duy nhất cấp bán thành phẩm cho toàn bộ công đoạn sau,
     * nên đặt cạnh đường tồn mới đọc được quan hệ nhân quả: đổ vào nhiều mà tồn
     * vẫn tụt nghĩa là phía sau chạy vượt nguồn, còn tồn dâng lên đều đặn là dấu
     * hiệu nghẽn ở phía sau chứ không phải Pha chế làm ít.
     *
     * Trả cả Kg lẫn đơn vị liều: Pha chế cân theo Kg còn cả trang tính theo viên,
     * thiếu một trong hai thì hoặc không đối chiếu được với phiếu cân, hoặc không
     * so được với các cột tồn bên cạnh.
     *
     * Mốc tính là lúc công đoạn Pha chế bắt đầu, đúng bằng lúc lô nhập kho ở các
     * nhóm phía sau, nên cột này và cột "Nhập" luôn kể cùng một câu chuyện.
     */
    private function buildSupplySeries(array $days, array $byPlanMaster): array
    {
        $stageCodes = self::STAGE_GROUPS['PC'];

        $lots = [];
        foreach ($byPlanMaster as $planRows) {
            $outRow = $this->outputRowOfGroup($planRows, $stageCodes);
            if ($outRow === null) {
                continue;
            }

            $moment = self::stageMoment($outRow);
            if ($moment === null) {
                continue;   // chưa sắp lịch và chưa chạy
            }

            $dvl = $this->toDvl($outRow);
            if ($dvl <= 0) {
                continue;
            }

            $lots[] = [
                'at'  => $moment,
                'kg'  => $this->rawYield($outRow),
                'dvl' => $dvl,
            ];
        }

        $series = [];
        foreach ($days as $day) {
            $from = $day['start']->format('Y-m-d H:i:s');
            $to   = $day['end']->format('Y-m-d H:i:s');

            $kg = 0.0;
            $dvl = 0.0;
            $count = 0;

            foreach ($lots as $lot) {
                if ($lot['at'] >= $from && $lot['at'] < $to) {
                    $kg  += $lot['kg'];
                    $dvl += $lot['dvl'];
                    $count++;
                }
            }

            $series[] = [
                'date'       => $day['date'],
                'output_kg'  => round($kg, 2),
                'output_dvl' => round($dvl, 2),
                'lots'       => $count,
            ];
        }

        return $series;
    }

    /**
     * Giới hạn trên/dưới của mức tồn từng công đoạn, cấu hình ở trang Chính sách
     * sản lượng. Công đoạn chưa cấu hình thì trả về hai đầu rỗng, nghĩa là không
     * đối chiếu chứ không phải giới hạn bằng 0.
     *
     * @return array<string, object>
     */
    public static function stockLimitsFor(string $productionCode): array
    {
        $rows = DB::table('wip_stock_limits')
            ->where('production_code', $productionCode)
            ->where('is_active', 1)
            ->get()
            ->keyBy('stage_group_code');

        $result = [];
        foreach (self::NEXT_GROUPS as $groupCode) {
            $row = $rows[$groupCode] ?? null;

            $result[$groupCode] = (object) [
                'production_code'  => $productionCode,
                'stage_group_code' => $groupCode,
                'min_stock_dvl'    => $row && $row->min_stock_dvl !== null ? (float) $row->min_stock_dvl : null,
                'max_stock_dvl'    => $row && $row->max_stock_dvl !== null ? (float) $row->max_stock_dvl : null,
            ];
        }

        return $result;
    }

    /** Xếp DH, BP, DG theo đúng thứ tự dây chuyền; nhóm chưa rõ công đoạn sau xuống cuối */
    private function sortGroups(array $ledgers): array
    {
        $order = array_flip(self::NEXT_GROUPS);

        uksort($ledgers, fn($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));

        return $ledgers;
    }

    /** Ráp kết quả của một nhóm từ sổ nhập xuất và chuỗi biến thiên đã tính sẵn */
    private function assembleGroup(
        string $groupCode,
        array $lots,
        Carbon $at,
        array $days,
        int $horizonDays,
        array $series
    ): array {
        $atStr = $at->format('Y-m-d H:i:s');

        // Tồn ngay tại mốc chốt
        $now = $this->stockAt($lots, $atStr);
        $stock = $now['lots'];
        $stockTotal = $now['total'];

        $stats = $this->statsFromSeries($series);
        $details = $this->buildDetails($stock, $lots, $days, $stockTotal);

        $topProduct = $details[0] ?? null;

        return [
            'group_code'           => $groupCode,
            'group_name'           => self::groupName($groupCode),
            'stock_dvl'            => round($stockTotal, 2),
            'stock_lots'           => count($stock),
            'first_shortage_date'  => $stats['shortage_date'],
            'lowest_stock_dvl'     => $stats['lowest'],
            'lowest_stock_date'    => $stats['lowest_date'],
            'highest_stock_dvl'    => $stats['highest'],
            'highest_stock_date'   => $stats['highest_date'],
            'avg_stock_dvl'        => $stats['average'],
            'in_total_dvl'         => $stats['in_total'],
            'out_total_dvl'        => $stats['out_total'],
            // Nhóm có trong quy trình nhưng hiện không giữ hàng và cũng không có
            // đợt nhập xuất nào trong kỳ
            'is_empty'             => $stockTotal <= 0 && $stats['in_total'] <= 0 && $stats['out_total'] <= 0,
            'top_product_code'     => $topProduct['intermediate_code'] ?? null,
            'top_product_dvl'      => $topProduct['stock_dvl'] ?? null,
            'horizon_days'         => $horizonDays,
            'daily_series'         => $series,
            'details'              => $details,
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
     * Đi theo chuỗi công đoạn cho tới khi ra khỏi nhóm hiện tại.
     * Sản phẩm không bao phim thì từ Định hình sẽ nhảy thẳng sang Đóng gói, đó
     * chính là lúc lô này được tính vào "chờ Đóng gói" thay vì "chờ Bao phim".
     *
     * Trả về MẢNG vì một code có thể ứng với nhiều dòng: lô đóng gói một phần
     * tách thành nhiều lô con dùng chung code, mỗi lô con có khung giờ và
     * percent_parkaging riêng.
     *
     * @return array danh sách dòng công đoạn kế tiếp, rỗng nếu không tìm được
     */
    private function nextGroupRows($row, array $lookup, string $groupCode): array
    {
        $stageCodes = self::STAGE_GROUPS[$groupCode];
        $cursor = $row;
        $seen = [];

        // Chuỗi dài nhất có thật chỉ 5 công đoạn, 12 vòng là dư sức; chặn ở đây
        // để dữ liệu khai vòng tròn không treo cả trang
        for ($guard = 0; $guard < 12; $guard++) {
            $candidates = $this->childRowsOf($cursor, $lookup);
            if ($candidates === []) {
                return [];
            }

            // Các dòng cùng một bước luôn cùng công đoạn nên xét dòng đầu là đủ
            if (! in_array((int) $candidates[0]->stage_code, $stageCodes, true)) {
                return $candidates;
            }

            $key = $candidates[0]->id ?? spl_object_hash($candidates[0]);
            if (isset($seen[$key])) {
                return [];
            }
            $seen[$key] = true;

            $cursor = $candidates[0];
        }

        return [];
    }

    /**
     * Các dòng công đoạn ngay sau một dòng cho trước.
     *
     * Ưu tiên nextcessor_code vì đó là chiều khai chính. Dòng nào bỏ trống cột
     * này thì tra ngược bằng predecessor_code, và chỉ nhận công đoạn gần nhất về
     * phía sau để không nhảy cóc qua một công đoạn khi lô khai nhiều cấp.
     */
    private function childRowsOf($row, array $lookup): array
    {
        $nextCode = $row->nextcessor_code ?? null;
        if ($nextCode !== null && $nextCode !== '' && ! empty($lookup['by_code'][$nextCode])) {
            return $lookup['by_code'][$nextCode];
        }

        $code = $row->code ?? null;
        if ($code === null || $code === '' || empty($lookup['by_predecessor'][$code])) {
            return [];
        }

        $stage = (int) $row->stage_code;
        $nearest = null;
        foreach ($lookup['by_predecessor'][$code] as $child) {
            $childStage = (int) $child->stage_code;
            if ($childStage <= $stage) {
                continue;   // dòng tự trỏ về mình hoặc khai ngược chiều
            }
            if ($nearest === null || $childStage < $nearest) {
                $nearest = $childStage;
            }
        }

        if ($nearest === null) {
            return [];
        }

        return array_values(array_filter(
            $lookup['by_predecessor'][$code],
            fn($child) => (int) $child->stage_code === $nearest
        ));
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
     * Sản lượng thô của một dòng, theo đúng đơn vị gốc của công đoạn đó.
     * Chạy xong rồi thì lấy số thực tế, chưa chạy thì lấy số lý thuyết.
     */
    private function rawYield($row): float
    {
        $raw = (float) ($row->theoretical_yields ?? 0);

        if ((int) ($row->finished ?? 0) === 1 && (float) ($row->yields ?? 0) > 0) {
            $raw = (float) $row->yields;
        }

        return $raw > 0 ? $raw : 0.0;
    }

    /**
     * Quy sản lượng về đơn vị liều.
     * Công đoạn tới Trộn hoàn tất tính bằng Kg nên phải nhân hệ số batch_qty/batch_size;
     * từ Định hình trở đi vốn đã là đơn vị liều.
     */
    private function toDvl($row): float
    {
        $raw = $this->rawYield($row);

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
     * @return array{total: float, count: int, lots: array}
     */
    private function stockAt(array $lots, string $at): array
    {
        $total = 0.0;
        $count = 0;
        $result = [];

        foreach ($lots as $lot) {
            $qty = $this->lotStockAt($lot, $at);
            if ($qty <= 0) {
                continue;
            }

            $total += $qty;
            $count++;

            $result[] = [
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

        return ['total' => $total, 'count' => $count, 'lots' => $result];
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

        $remaining = 1.0 - $consumed;

        // Cộng dồn số thực không bao giờ ra đúng 1,0 nên lô đã rút hết vẫn còn
        // sót một phần cỡ 1e-16; nhân với lô cả triệu liều thì phần sót đó vẫn
        // lớn hơn 0 và lô tiếp tục bị đếm là đang chờ. Ngưỡng dưới đây thấp hơn
        // mọi tỉ lệ chia thật (nhỏ nhất trong dữ liệu là 1,35%) nên không thể
        // nuốt nhầm một phần tồn có ý nghĩa.
        if ($remaining < 1e-9) {
            return 0.0;
        }

        return $lot['qty_dvl'] * $remaining;
    }

    /**
     * Biến thiên tồn kho: tính LẠI tồn tại 06:00 của từng ngày theo lịch đã sắp,
     * kèm lượng nhập và xuất trong ngày đó.
     */
    private function buildSeries(array $days, array $ledgers): array
    {
        $series = array_fill_keys(array_keys($ledgers), []);

        foreach ($days as $day) {
            $from = $day['start']->format('Y-m-d H:i:s');
            $to   = $day['end']->format('Y-m-d H:i:s');

            foreach ($ledgers as $groupCode => $lots) {
                // Nhập và xuất phải tính theo ĐÚNG mốc mà tồn thay đổi, tức thời điểm
                // công đoạn bắt đầu, chứ không chia đều theo khung giờ chạy. Nếu chia
                // đều thì cột nhập trừ xuất sẽ không giải thích được bước nhảy của
                // đường tồn, có ngày còn ngược dấu, nhìn vào tưởng sai số liệu.
                $in = 0.0;
                $out = 0.0;

                foreach ($lots as $lot) {
                    $delta = $this->lotStockAt($lot, $to) - $this->lotStockAt($lot, $from);

                    if ($delta > 0) {
                        $in += $delta;
                    } else {
                        $out -= $delta;
                    }
                }

                $snapshot = $this->stockAt($lots, $from);

                $series[$groupCode][] = [
                    'date'       => $day['date'],
                    'stock_dvl'  => round($snapshot['total'], 2),
                    'stock_lots' => $snapshot['count'],
                    'in_dvl'     => round($in, 2),
                    'out_dvl'    => round($out, 2),
                ];
            }
        }

        return $series;
    }

    /**
     * Tổng hợp từ chuỗi biến thiên: tổng nhập, tổng xuất, mức tồn cao nhất,
     * thấp nhất, trung bình, và ngày đầu tiên tồn cạn hẳn trong khoảng dự báo.
     */
    private function statsFromSeries(array $series): array
    {
        $empty = [
            'shortage_date' => null,
            'in_total'      => 0.0,
            'out_total'     => 0.0,
            'lowest'        => null,
            'lowest_date'   => null,
            'highest'       => null,
            'highest_date'  => null,
            'average'       => null,
        ];

        if ($series === []) {
            return $empty;
        }

        $inTotal = 0.0;
        $outTotal = 0.0;
        $sum = 0.0;

        $lowest = null;
        $lowestDate = null;
        $highest = null;
        $highestDate = null;
        $shortageDate = null;

        foreach ($series as $point) {
            $value = (float) $point['stock_dvl'];

            $inTotal  += (float) $point['in_dvl'];
            $outTotal += (float) $point['out_dvl'];
            $sum      += $value;

            if ($lowest === null || $value < $lowest) {
                $lowest = $value;
                $lowestDate = $point['date'];
            }

            if ($highest === null || $value > $highest) {
                $highest = $value;
                $highestDate = $point['date'];
            }

            if ($shortageDate === null && $value <= 0) {
                $shortageDate = $point['date'];
            }
        }

        return [
            'shortage_date' => $shortageDate,
            'in_total'      => round($inTotal, 2),
            'out_total'     => round($outTotal, 2),
            'lowest'        => $lowest === null ? null : round($lowest, 2),
            'lowest_date'   => $lowestDate,
            'highest'       => $highest === null ? null : round($highest, 2),
            'highest_date'  => $highestDate,
            'average'       => round($sum / count($series), 2),
        ];
    }

    /**
     * Lặp lại phép tính nhưng gom theo mã bán thành phẩm, để biết mã nào đang
     * chiếm chỗ trong kho và còn lịch xuất tới đâu. Cột "Công đoạn" trong bảng
     * lô con (stage_code) vẫn cho biết lô đó xuất phát từ đâu, dù tổng nhóm đã
     * gộp mọi nguồn lại theo đích chung.
     */
    private function buildDetails(array $stock, array $lots, array $days, float $groupTotal): array
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
        $lotsByCode = [];
        foreach ($lots as $lot) {
            $lotsByCode[$lot['intermediate_code'] ?? '(không rõ)'][] = $lot;
        }

        // Ngày mà mã này xuất kho lần cuối trong khoảng dự báo, để biết còn lịch
        // tiêu thụ tới đâu
        $details = [];
        foreach ($stockByCode as $code => $entry) {
            $lastOutDate = null;
            foreach ($days as $day) {
                $from = $day['start']->format('Y-m-d H:i:s');
                $to   = $day['end']->format('Y-m-d H:i:s');

                foreach ($lotsByCode[$code] ?? [] as $lot) {
                    if ($this->lotStockAt($lot, $to) - $this->lotStockAt($lot, $from) < 0) {
                        $lastOutDate = $day['date'];
                        break;
                    }
                }
            }

            $details[] = [
                'intermediate_code' => $code,
                'product_name'      => $entry['product_name'],
                'unit'              => $entry['unit'],
                'stock_dvl'         => round($entry['stock_dvl'], 2),
                'stock_lots'        => $entry['stock_lots'],
                'share_pct'         => $groupTotal > 0 ? round($entry['stock_dvl'] / $groupTotal * 100, 1) : null,
                'last_out_date'     => $lastOutDate,
                'batches'           => $entry['batches'],
            ];
        }

        // Mã đang giữ nhiều hàng nhất lên đầu
        usort($details, fn($a, $b) => $b['stock_dvl'] <=> $a['stock_dvl']);

        return $details;
    }
}
