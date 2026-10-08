<?php

namespace App\Plugins\WipControl\Services;

use App\Services\WipCoverageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lùi đầu nguồn để tồn bán thành phẩm không vượt Max.
 *
 * Vòng đầu: ngưng nguồn theo ngưỡng cho từng nhóm có cài Max (gateSteps, xem ở đó).
 * Mỗi vòng sau đó (và cho Max chờ ĐH / chờ ĐG):
 *   1. Đo tồn chờ ĐH / BP / ĐG từng ngày (06:00) theo lịch hiện tại bằng
 *      WipCoverageService, lấy các ngày vượt Max.
 *   2. Với mỗi ngày vượt, chọn các lô đang nằm trong kho hôm đó, ưu tiên lô có
 *      ngày cần hàng (expected_date) muộn nhất, đủ bù phần vượt.
 *   3. Lô được chọn lùi các công đoạn TẠO RA tồn (từ công đoạn đầu, thường là
 *      Pha chế, tới ngay trước công đoạn tiêu thụ), để lô vào kho vừa kịp lúc
 *      công đoạn sau cần. Công đoạn tiêu thụ trở về sau GIỮ NGUYÊN lịch nên ngày
 *      xong của lô không đổi.
 *   4. Lô cùng campaign / cùng nhóm lô con đóng gói đi theo nhau.
 *   5. Xoá lịch phần đầu nguồn rồi sắp lại bằng chính bộ sắp lịch tự động, kèm
 *      mốc "không sớm hơn" cho từng lô. Lô nào sắp lại mà không kịp trước công
 *      đoạn tiêu thụ thì trả về đúng lịch cũ.
 * Dừng khi hết vi phạm, khi tổng lượng vượt không giảm sau vài vòng (không khả
 * thi, thường do năng lực công đoạn sau thấp hơn đầu nguồn hoặc tồn nằm ở lô đã
 * chạy), hết số vòng hoặc hết thời gian.
 */
class WipThrottleService
{
    private const STAGE_NAMES = [3 => 'PC', 4 => 'THT', 5 => 'ĐH', 6 => 'BP', 7 => 'ĐG'];

    /** Công đoạn tiêu thụ tồn của từng nhóm đích */
    public const CONSUMER_STAGE = ['DH' => 5, 'BP' => 6, 'DG' => 7];

    /** Nhóm tồn theo công đoạn tiêu thụ */
    private const GROUP_OF_STAGE = [5 => 'DH', 6 => 'BP', 7 => 'DG'];

    /** Hạn bắt đầu luôn giữ (06:00 như scanOverdueTasks), ngoài các ngày NL/BB tuỳ chọn bên dưới */
    private const STAGE_DEADLINES = [
        7 => ['parkaging_before_date'],
    ];

    /**
     * Ngày NL/BB người dùng chọn "không vi phạm" (modal): khoá => [cột plan_master, công đoạn, kiểu].
     * max: công đoạn phải bắt đầu trong hoặc trước ngày đó; min: không được bắt đầu trước ngày đó.
     * So theo ngày như cảnh báo trên lịch (colorEvent / SchedualWarningController). Ngày được
     * cho phép vi phạm thì plugin bỏ qua hẳn.
     */
    public const DATE_RULES = [
        'allow_weight'     => ['allow_weight_before_date', 3, 'min'],
        'expired_material' => ['expired_material_date', 3, 'max'],
        'expired_packing'  => ['expired_packing_date', 7, 'max'],
        'preperation'      => ['preperation_before_date', 3, 'max'],
        'blending'         => ['blending_before_date', 4, 'max'],
        'forming'          => ['forming_before_date', 5, 'max'],
        'coating'          => ['coating_before_date', 6, 'max'],
    ];

    /** Các cột lịch được chụp lại trước khi xoá để trả về nguyên trạng khi cần */
    private const SNAPSHOT_FIELDS = [
        'start', 'end', 'start_clearning', 'end_clearning', 'resourceId', 'title', 'title_clearning',
        'accept_quarantine', 'schedualed', 'blister_mold_id', 'schedualed_by', 'schedualed_at', 'submit',
        'comfirm_of_lead', 'comfirm_of_lead_by', 'comfirm_of_lead_at', 'first_in_campaign', 'AHU_group',
        'scheduling_direction', 'overlap', 'receive_packaging_date', 'receive_second_packaging_date',
    ];

    private WipCoverageService $coverage;

    private string $productionCode;
    private Request $request;
    private int $selectedStep;
    private Carbon $startDate;
    private Carbon $at;
    private array $limits;
    private bool $lockValidation;

    private ?string $gateError = null;

    /** @var array<int, string> các khoá DATE_RULES không được vi phạm */
    private array $hardRules = [];

    /** @var array<int, true> lô bị ép chạy đúng hạn ở bước ngưng nguồn (ngưng thì vi phạm ngày NL/BB) */
    private array $gateForced = [];

    /** @var array<int, true> lô đã ép chạy đúng hạn mà vẫn vi phạm (phòng kẹt): giữ nguyên giờ cũ ở bước ngưng nguồn */
    private array $gatePinned = [];

    /** @var array<int, string> trong một lần thử ngưng nguồn: lô gốc gây vi phạm ngày NL/BB => mô tả */
    private array $hardHits = [];

    /** @var array<int, int> dòng bị đẩy lùi lan => lô gốc gây ra */
    private array $pushOrigin = [];

    /** @var array<int, string> vi phạm ngày NL/BB làm huỷ cả vòng (lưới an toàn cuối) */
    private array $hardBlocked = [];

    /** @var array<int, string> dòng có dữ liệu bất thường gặp khi ngưng nguồn: id => mô tả */
    private array $gateWarnings = [];

    /** @var array<int, array> dòng công đoạn sau bị đẩy lùi theo (chế độ ngưng nguồn): id => [...] */
    private array $bpShifted = [];

    /** Bước ngưng nguồn đang chạy: công đoạn nguồn lớn nhất (dòng sau nó bị lùi là "lùi theo") và nhóm ghi nhận */
    private int $gateStage = 5;
    private string $gateGroup = 'BP';
    private int $pullBufferHours = 24;
    private array $waits;
    private array $overdueCampaigns = [];

    /** @var array<int, array> plan_master_id => thông tin lô, nạp dần */
    private array $info = [];

    /** @var array<int, Carbon> plan_master_id => mốc sớm nhất, cộng dồn qua các vòng */
    private array $hints = [];

    /** @var array<int, array> lô đã bị lùi: plan_master_id => [old_head, old_last_end, round, ...] */
    private array $moved = [];

    /** @var array<int, string> lô không lùi được: plan_master_id => lý do */
    private array $skipped = [];

    /** Nhật ký thao tác khi bật wip_control.debug */
    private array $trace = [];

    /** @var array<int, array> lô được kéo lên sớm khi đổi chỗ: plan_master_id => [stage, old_start] */
    private array $pulled = [];

    /** @var array<int, int> số lần lô sắp lại không kịp và phải trả về lịch cũ */
    private array $failures = [];

    /** Sau bấy nhiêu lần trả về lịch cũ thì thôi không thử lùi lô đó nữa */
    private const MAX_FAILURES = 3;

    public function __construct(WipCoverageService $coverage)
    {
        $this->coverage = $coverage;
    }

    /**
     * @param array{
     *     production_code: string,
     *     limits: array<string, float|null>,
     *     iterations: int,
     *     lock_validation: bool,
     *     selected_step: int,
     *     start_date: Carbon,
     *     request: Request
     * } $opt
     */
    public function run(array $opt): array
    {
        $began = microtime(true);

        $this->productionCode = $opt['production_code'];
        $this->request = $opt['request'];
        $this->selectedStep = $opt['selected_step'];
        $this->startDate = $opt['start_date'];
        $this->lockValidation = $opt['lock_validation'];
        RoomSequencer::resetHistoryCache();
        // Các ngày NL/BB luôn không được vi phạm (đã bỏ tuỳ chọn cho phép vi phạm trên modal)
        $this->hardRules = array_keys(self::DATE_RULES);
        $this->pullBufferHours = (int) ($opt['pull_buffer_hours'] ?? config('wip_control.safety_buffer_hours', 24));
        $this->waits = WipAwareScheduler::waitTimes($this->request);

        $now = Carbon::now();
        $this->at = $this->startDate->gt($now) ? $this->startDate->copy() : $now;

        // Chỉ xét nhóm có cài Max và có công đoạn tiêu thụ nằm trong phạm vi sắp lịch;
        // nhóm ngoài phạm vi chưa có lịch rút hàng nên tồn của nó vô nghĩa
        $this->limits = [];
        $outOfScope = [];
        foreach (self::CONSUMER_STAGE as $group => $stage) {
            $max = $opt['limits'][$group] ?? null;
            if ($max === null) {
                continue;
            }
            if ($stage > $this->selectedStep) {
                $outOfScope[] = $group;
                continue;
            }
            $this->limits[$group] = (float) $max;
        }

        if ($this->limits === []) {
            return $this->finish('skipped', [], null, null, $began, null, ['out_of_scope' => $outOfScope]);
        }

        $maxIterations = max(1, min((int) config('wip_control.max_iterations', 10), $opt['iterations']));
        $stagnantLimit = (int) config('wip_control.stagnant_rounds', 2);
        $budget = (int) config('wip_control.time_budget_seconds', 900);

        $this->overdueCampaigns = $this->freshScheduler()->overdueCampaignCodes();
        $overdueBefore = $this->overdueCampaigns;

        $rounds = [];
        $before = null;
        $last = null;
        $undoCode = null;
        $prevExcess = null;
        $stagnant = 0;
        $status = 'max_iterations';

        for ($round = 1; ; $round++) {
            $measure = $this->measure();
            $last = $this->summary($measure);
            $before ??= $last;

            if ($measure['violations'] === []) {
                $status = 'ok';
                break;
            }

            if ($round > $maxIterations) {
                $status = 'max_iterations';
                break;
            }

            if (microtime(true) - $began > $budget) {
                $status = 'timeout';
                break;
            }

            if ($prevExcess !== null && $measure['total_excess'] >= $prevExcess * 0.999) {
                $stagnant++;
                if ($stagnant >= $stagnantLimit) {
                    $status = 'infeasible';
                    break;
                }
            } else {
                $stagnant = 0;
            }
            $prevExcess = $measure['total_excess'];

            $stats = [
                'round'          => $round,
                'violation_days' => count($measure['violations']),
                'total_excess'   => round($measure['total_excess'], 2),
            ];

            $undoCode ??= $this->createUndoPoint();

            // Lưới an toàn ngày NL/BB: giờ bắt đầu trước vòng, và báo cáo để trả lại nếu huỷ vòng
            $startsBefore = $this->startsSnapshot();
            $stateBefore = [$this->moved, $this->pulled, $this->bpShifted, $this->gateWarnings];

            // Cả vòng trong một transaction: lỗi giữa chừng (kể cả PHP chết) thì vòng
            // này tự huỷ, lịch quay về đúng như trước vòng, không để dòng nào bị khoá treo
            DB::beginTransaction();
            try {
                // Bước 0 (chỉ vòng đầu): ngưng nguồn theo ngưỡng cho từng nhóm có cài Max
                $mix = $round === 1 ? $this->gateSteps($round) : ['delayed' => 0, 'pulled' => 0, 'reverted' => 0];
                if ($mix['delayed'] + $mix['pulled'] > 0) {
                    // Lịch vừa đổi nhiều: đo lại trước khi đổi chỗ / giãn
                    unset($measure);
                    $measure = $this->measure();
                }

                // Bước 1 + 2: đổi chỗ rồi giãn lịch ngay trong phòng của công đoạn nguồn
                $room = $this->roomStep($measure, $round);
                $movedNow = [];
                $reverted = [];

                // Bước 3: phòng không dời được gì thì mới sắp lại từng lô (lùi cả chuỗi từ PC)
                if ($room['swap'] + $room['stretch'] + $mix['delayed'] + $mix['pulled'] === 0) {
                    $picks = $this->pickLots($measure);
                    $hintsBefore = $this->hints;
                    [$movedNow, $groupOf] = $this->expandAndHint($picks, $round);
                    unset($measure, $picks);

                    if ($movedNow !== []) {
                        $snapshot = $this->unschedule($movedNow);
                        $this->reschedule($movedNow, $snapshot);
                        $reverted = $this->validateRound($movedNow, $groupOf, $snapshot, $hintsBefore, $round);
                    }
                }

                // Còn dòng nào vừa bị dời mà phạm ngày NL/BB không được vi phạm: huỷ cả vòng
                $this->hardBlocked = $this->hardNet($startsBefore);
                if ($this->hardBlocked !== []) {
                    DB::rollBack();
                    RoomSequencer::resetHistoryCache();
                    [$this->moved, $this->pulled, $this->bpShifted, $this->gateWarnings] = $stateBefore;
                    $this->info = [];
                    $this->debug('hard_date_round', $round, $this->hardBlocked);
                    $status = 'hard_date';
                    break;
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            unset($measure);

            $rounds[] = $stats + [
                'mix_delayed'   => $mix['delayed'],
                'mix_pulled'    => $mix['pulled'],
                'swapped'       => $room['swap'],
                'stretched'     => $room['stretch'],
                'moved_lots'    => count($movedNow) - count($reverted),
                'reverted_lots' => count($reverted),
            ];

            if ($room['swap'] + $room['stretch'] + $mix['delayed'] + $mix['pulled'] === 0 && count($movedNow) - count($reverted) <= 0) {
                $status = 'infeasible';
                break;
            }
        }

        $overdueAfter = $this->freshScheduler()->overdueCampaignCodes();

        // Lô đã xếp xuyên qua BT-HC-TI chưa bắt đầu: dời các lịch đó ra khe trống kế tiếp trong phòng
        $maintenanceShifted = $rounds === [] ? [] : app(\App\Services\MaintenanceShiftService::class)
            ->shiftOverlapping($this->productionCode, 'Dời BT-HC-TI sau lịch sản xuất (kiểm soát tồn BTP)');

        return $this->finish($status, $rounds, $before, $last, $began, $undoCode, [
            'out_of_scope' => $outOfScope,
            'new_overdue'  => array_values(array_diff($overdueAfter, $overdueBefore)),
            'maintenance_shifted' => $maintenanceShifted,
        ]);
    }

    // ------------------------------------------------------------------
    // Đo tồn
    // ------------------------------------------------------------------

    private function measure(): array
    {
        $horizon = (int) config('wip_control.horizon_days', 30);
        $data = $this->coverage->ledgers($this->productionCode, $this->at, $horizon);

        $violations = [];
        $total = 0.0;

        foreach ($this->limits as $group => $max) {
            foreach ($data['series'][$group] ?? [] as $i => $point) {
                $stock = (float) $point['stock_dvl'];
                if ($stock <= $max) {
                    continue;
                }

                $violations[] = [
                    'group'     => $group,
                    'index'     => $i,
                    'date'      => $point['date'],
                    'day_start' => $data['days'][$i]['start']->format('Y-m-d H:i:s'),
                    'stock'     => $stock,
                    'max'       => $max,
                    'excess'    => $stock - $max,
                ];
                $total += $stock - $max;
            }
        }

        return $data + ['violations' => $violations, 'total_excess' => $total];
    }

    /** Tóm tắt một lần đo để trả về giao diện */
    private function summary(array $measure): array
    {
        $groups = [];
        foreach ($this->limits as $group => $max) {
            $peak = 0.0;
            $peakDate = null;
            $peakIndex = null;
            foreach ($measure['series'][$group] ?? [] as $i => $point) {
                if ((float) $point['stock_dvl'] > $peak) {
                    $peak = (float) $point['stock_dvl'];
                    $peakDate = $point['date'];
                    $peakIndex = $i;
                }
            }

            // Phần tồn ngày đỉnh là hàng đã vào kho trước ngày sắp lịch: lùi gì cũng không bớt
            $stuck = 0.0;
            if ($peakIndex !== null) {
                $moment = $measure['days'][$peakIndex]['start']->format('Y-m-d H:i:s');
                foreach ($measure['ledgers'][$group] ?? [] as $lot) {
                    if (! empty($lot['entry']) && strtotime($lot['entry']) < $this->at->getTimestamp()) {
                        $stuck += $this->coverage->lotStockAtMoment($lot, $moment);
                    }
                }
            }

            $days = array_values(array_filter($measure['violations'], fn($v) => $v['group'] === $group));

            $groups[] = [
                'group'          => $group,
                'name'           => WipCoverageService::groupName($group),
                'max'            => $max,
                'peak'           => round($peak, 2),
                'peak_date'      => $peakDate,
                'peak_stuck'     => round($stuck, 2),
                'violation_days' => count($days),
                'excess'         => round(array_sum(array_column($days, 'excess')), 2),
                'first_date'     => $days[0]['date'] ?? null,
                'last_date'      => $days !== [] ? end($days)['date'] : null,
            ];
        }

        return [
            'total_excess'   => round($measure['total_excess'], 2),
            'violation_days' => count($measure['violations']),
            'groups'         => $groups,
        ];
    }

    // ------------------------------------------------------------------
    // Chọn lô
    // ------------------------------------------------------------------

    /**
     * Chọn lô cần lùi cho mọi ngày vượt Max của vòng này.
     *
     * @return array<int, array{delta: int, group: string, date: string, top: int}>
     *         plan_master_id => số giây cần lùi, top = công đoạn cuối được lùi
     */
    private function pickLots(array $measure): array
    {
        $minShift = (int) config('wip_control.min_shift_minutes', 60) * 60;
        $horizonEnd = end($measure['days'])['end']->getTimestamp();

        // Chỉ nạp thông tin các lô thật sự nằm trong kho vào một ngày vượt Max
        $pmIds = [];
        foreach ($measure['violations'] as $v) {
            foreach ($measure['ledgers'][$v['group']] ?? [] as $lot) {
                if ($this->coverage->lotStockAtMoment($lot, $v['day_start']) > 0) {
                    $pmIds[(int) $lot['plan_master_id']] = true;
                }
            }
        }
        $this->loadInfo(array_keys($pmIds));

        $picked = [];

        foreach (array_keys($this->limits) as $group) {
            $ledger = $measure['ledgers'][$group] ?? [];
            $days = array_values(array_filter($measure['violations'], fn($v) => $v['group'] === $group));
            $consumer = self::CONSUMER_STAGE[$group];
            $top = $consumer - 1;

            foreach ($days as $v) {
                $at = $v['day_start'];
                $atTs = strtotime($at);

                $excess = $v['excess'];
                $candidates = [];

                foreach ($ledger as $lot) {
                    $qty = $this->coverage->lotStockAtMoment($lot, $at);
                    if ($qty <= 0) {
                        continue;
                    }

                    $pm = (int) $lot['plan_master_id'];
                    $entryTs = strtotime($lot['entry']);

                    // Lô đã chọn ở ngày trước mà cũng rời khỏi ngày này thì trừ luôn phần vượt
                    if (isset($picked[$pm])) {
                        if ($entryTs + $picked[$pm]['delta'] > $atTs) {
                            $excess -= $qty;
                        }
                        continue;
                    }

                    if (! isset($this->info[$pm])) {
                        continue;
                    }

                    $lock = $this->lockOf($pm, $top);
                    if ($lock !== null) {
                        $this->skip($pm, $lock);
                        continue;
                    }

                    $wanted = $this->shiftFor($lot, $pm, $consumer, $at, $v, $measure, $horizonEnd);

                    // Lần trước sắp lại không kịp thì lần này chỉ lùi một nửa
                    $wanted = (int) ($wanted / (2 ** ($this->failures[$pm] ?? 0)));

                    $delta = min($wanted, $this->maxShift($pm, $top), $this->dueShift($pm, $lot, $at));

                    if ($delta < $minShift || $entryTs + $delta <= $atTs) {
                        if ($delta < $wanted && $entryTs + $wanted > $atTs) {
                            $this->skip($pm, 'Lùi sẽ quá hạn công đoạn hoặc ngày cần hàng');
                        }
                        continue;   // lùi tới đâu cũng vẫn còn nằm trong kho ngày này
                    }

                    $candidates[] = [
                        'pm'    => $pm,
                        'qty'   => $qty,
                        'delta' => $delta,
                        'need'  => $this->info[$pm]['expected_date'],
                    ];
                }

                if ($excess <= 0) {
                    continue;
                }

                // Ngày cần hàng muộn nhất lùi trước; lô chưa khai ngày cần hàng coi là ít gấp nhất
                usort($candidates, function ($a, $b) {
                    if ($a['need'] !== $b['need']) {
                        if ($a['need'] === null) {
                            return -1;
                        }
                        if ($b['need'] === null) {
                            return 1;
                        }
                        return strcmp($b['need'], $a['need']);
                    }
                    return $b['qty'] <=> $a['qty'];
                });

                foreach ($candidates as $c) {
                    if ($excess <= 0) {
                        break;
                    }
                    $picked[$c['pm']] = ['delta' => $c['delta'], 'group' => $group, 'date' => $v['date'], 'top' => $top];
                    $excess -= $c['qty'];
                }
            }
        }

        return $picked;
    }

    /**
     * Bước 1 + 2 trong phòng của công đoạn nguồn (xem RoomSequencer).
     *
     * Với mỗi ngày vượt Max, xét các lô đang nằm trong kho hôm đó, ngày cần hàng
     * muộn nhất trước. Cho từng lô:
     *   1. Đổi chỗ: tìm khối lô phía sau CÙNG PHÒNG mà hàng của nó đi sang nhóm khác
     *      KHÔNG cài Max (vd lô không bao phim đi thẳng ĐG khi đang xét chờ BP), có
     *      ngày cần hàng sớm nhất, đưa lên đúng chỗ khối đang gây tồn. Được kéo sớm
     *      hơn hẳn ngày cần hàng của nó.
     *   2. Giãn lịch: không đổi chỗ được thì dời khối đó và các lô sau trong phòng
     *      trễ đi, tới mốc "vừa kịp" công đoạn sau.
     * Chỉ nhận phương án làm lô rời khỏi kho ngày đó.
     *
     * @return array{swap: int, stretch: int}
     */
    private function roomStep(array $measure, int $round): array
    {
        $count = ['swap' => 0, 'stretch' => 0];
        $horizonEnd = end($measure['days'])['end']->getTimestamp();

        // Nhóm đích của từng (lô, công đoạn nguồn), để biết khối nào đi sang nhóm khác
        $nextGroup = [];
        foreach ($measure['ledgers'] as $g => $lots) {
            foreach ($lots as $lot) {
                $nextGroup[(int) $lot['plan_master_id']][(int) $lot['stage_code']] = $g;
            }
        }

        $info = function (int $pm) {
            $this->loadInfo([$pm]);
            return $this->info[$pm];
        };

        $sequencer = new RoomSequencer(
            $this->productionCode,
            $this->at->getTimestamp(),
            $horizonEnd + 30 * 86400,
            fn(int $pm) => $info($pm)['lock'],
            fn(int $pm, int $stage) => $info($pm)['deadlines'][$stage] ?? null,
            function (int $stage, int $pm) use ($info) {
                $info($pm);
                return $this->waitSeconds($stage, $pm);
            },
            $this->freshScheduler()->offRanges(),
            fn(int $pm, int $stage) => $info($pm)['earliest'][$stage] ?? null
        );

        $shifted = [];   // pm => [stage => giây đã dời trong vòng]
        $label = 'Kiểm soát tồn BTP';

        foreach (array_keys($this->limits) as $group) {
            $ledger = $measure['ledgers'][$group] ?? [];
            $consumer = self::CONSUMER_STAGE[$group];
            $days = array_values(array_filter($measure['violations'], fn($v) => $v['group'] === $group));

            foreach ($days as $v) {
                $at = $v['day_start'];
                $atTs = strtotime($at);
                $excess = $v['excess'];
                $candidates = [];

                foreach ($ledger as $lot) {
                    $qty = $this->coverage->lotStockAtMoment($lot, $at);
                    if ($qty <= 0) {
                        continue;
                    }
                    $pm = (int) $lot['plan_master_id'];
                    $stage = (int) $lot['stage_code'];
                    $entryTs = strtotime($lot['entry']);

                    if (isset($shifted[$pm][$stage])) {
                        if ($entryTs + $shifted[$pm][$stage] > $atTs) {
                            $excess -= $qty;
                        }
                        continue;
                    }

                    $candidates[] = ['pm' => $pm, 'stage' => $stage, 'qty' => $qty, 'lot' => $lot,
                        'need' => $info($pm)['expected_date']];
                }

                if ($excess <= 0) {
                    continue;
                }

                usort($candidates, function ($a, $b) {
                    if ($a['need'] !== $b['need']) {
                        if ($a['need'] === null) {
                            return -1;
                        }
                        if ($b['need'] === null) {
                            return 1;
                        }
                        return strcmp($b['need'], $a['need']);
                    }
                    return $b['qty'] <=> $a['qty'];
                });

                foreach ($candidates as $cand) {
                    if ($excess <= 0) {
                        break;
                    }
                    $pm = $cand['pm'];
                    $stage = $cand['stage'];
                    if (isset($shifted[$pm][$stage])) {
                        continue;   // đã dời theo khối của lô khác ở trên
                    }

                    $roomId = null;
                    foreach ($info($pm)['rows'] as $row) {
                        if ($row->stage_code === $stage) {
                            $roomId = $row->resourceId;
                        }
                    }
                    if ($roomId === null) {
                        continue;
                    }

                    $seq = $sequencer->room($roomId);
                    $i = $sequencer->indexOf($seq, $pm, $stage);
                    if ($i === null) {
                        continue;
                    }
                    if ($seq[$i]['lock'] !== null) {
                        $this->skip($pm, $seq[$i]['lock']);
                        continue;
                    }

                    [$c, $cEnd] = $sequencer->block($seq, $i);
                    $needShift = $atTs - $seq[$i]['start'] + 60;   // tối thiểu để rời khỏi kho ngày này

                    // ---- 1. Đổi chỗ
                    $plan = null;
                    $method = null;
                    $bestNeed = null;
                    $scanned = 0;
                    for ($j = $cEnd + 1; $j < count($seq) && $scanned < 40; $scanned++) {
                        [$n1, $n2] = $sequencer->block($seq, $j);
                        $j = $n2 + 1;

                        $other = $nextGroup[$seq[$n1]['pm']][$seq[$n1]['stage']] ?? null;
                        if ($other === null || $other === $group || $other === WipCoverageService::NO_NEXT
                            || isset($this->limits[$other]) || $seq[$n1]['lock'] !== null) {
                            continue;
                        }

                        $option = $sequencer->swapPlan($seq, $c, $n1, $n2);
                        if ($option === null || ($option[$seq[$i]['id']] ?? 0) < $needShift) {
                            continue;
                        }

                        $optNeed = $info($seq[$n1]['pm'])['expected_date'] ?? '9999-12-31';
                        if ($plan === null || $optNeed < $bestNeed) {
                            $plan = $option;
                            $bestNeed = $optNeed;
                            $method = 'swap';
                        }
                    }

                    // ---- 2. Giãn lịch
                    if ($plan === null) {
                        $wanted = $this->shiftFor($cand['lot'], $pm, $consumer, $at, $v, $measure, $horizonEnd);
                        if ($wanted >= $needShift) {
                            $delta = $sequencer->maxShift($seq, $c, $wanted);
                            $option = $delta > 0 ? $sequencer->shiftPlan($seq, $c, $delta) : null;
                            if ($option !== null && ($option[$seq[$i]['id']] ?? 0) >= $needShift) {
                                $plan = $option;
                                $method = 'stretch';
                            }
                        }
                    }

                    if ($plan === null) {
                        $this->skip($pm, 'Phòng kín lịch: không đổi chỗ / giãn được mà không trễ công đoạn sau');
                        continue;
                    }

                    $sequencer->apply($plan, $label . ($method === 'swap' ? ' (đổi chỗ)' : ' (giãn lịch)'));
                    $this->debug('room_apply', $method, $roomId, $plan);
                    $sequencer->forget($roomId);
                    $count[$method]++;

                    $byId = [];
                    foreach ($seq as $row) {
                        $byId[$row['id']] = $row;
                    }
                    foreach ($plan as $id => $d) {
                        $row = $byId[$id];
                        $shifted[$row['pm']][$row['stage']] = ($shifted[$row['pm']][$row['stage']] ?? 0) + $d;
                        $this->recordRoomMove($row, $d, $method, $group, $v['date'], $round, $id === $seq[$i]['id']);
                        unset($this->info[$row['pm']]);   // lịch đổi, nạp lại khi cần
                    }

                    if (($shifted[$pm][$stage] ?? 0) > 0 && $cand['lot']['entry'] !== null
                        && strtotime($cand['lot']['entry']) + $shifted[$pm][$stage] > $atTs) {
                        $excess -= $cand['qty'];
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Chế độ "Ngưng nguồn theo ngưỡng" cho từng nhóm có cài Max, xét từ cuối dây chuyền
     * về đầu nguồn để bước sau biết giờ tiêu thụ mới của bước trước:
     *  1. Max chờ ĐG: phòng BP (lô vào kho chờ ĐG khi BP bắt đầu);
     *  2. Max chờ BP và / hoặc chờ ĐG: phòng ĐH (lô bao phim vào chờ BP, lô không bao phim
     *     vào chờ ĐG khi ĐH bắt đầu);
     *  3. Max chờ ĐH: phòng THT (hoặc PC với lô không có THT), lô vào chờ ĐH khi bắt đầu.
     * Mỗi bước trong một savepoint riêng: lỗi thì huỷ riêng bước đó.
     *
     * @return array{delayed: int, pulled: int, reverted: int}
     */
    private function gateSteps(int $round): array
    {
        $steps = [];
        if (isset($this->limits['DG'])) {
            $steps[] = [6];
        }
        if (isset($this->limits['BP']) || isset($this->limits['DG'])) {
            $steps[] = [5];
        }
        if (isset($this->limits['DH'])) {
            $steps[] = [3, 4];
        }
        foreach ($steps as $stages) {
            $this->gateStep($round, $stages);
        }

        $result = ['delayed' => 0, 'pulled' => 0, 'reverted' => 0];
        foreach ($this->moved as $m) {
            if (($m['method'] ?? null) === 'gate') {
                $result['delayed']++;
            }
        }
        foreach ($this->pulled as $p) {
            if (($p['method'] ?? null) === 'gate') {
                $result['pulled']++;
            }
        }

        return $result;
    }

    /**
     * Một bước ngưng nguồn: xếp lại giờ các lô chưa chạy trong khung ở phòng của công đoạn
     * nguồn $stages bằng mô phỏng WipGate. Lô vào nhóm có Max chỉ bắt đầu khi tồn nhóm đó
     * cộng lượng của lô ≤ Max; lúc ngưng, phòng chạy lô đi nhóm không cài Max đã sẵn sàng
     * (công đoạn trước xong), không có thì để trống. Lô giữ phòng và thời lượng; không bắt
     * đầu trong ngày nghỉ; lô đi nhóm không cài Max luôn kịp công đoạn sau.
     * Lô có Max chỉ chạy khi tồn cho phép, kể cả khi vì thế xong trễ giờ công đoạn sau: khi đó
     * phòng công đoạn sau chạy trước lô khác đã có hàng, không được thì công đoạn sau của lô
     * đó (và các lô sau cùng phòng...) bị đẩy lùi theo.
     * Sau đó lùi các công đoạn phía trước của lô có Max về sát giờ mới (xếp theo hạn, dồn sát hạn).
     * Lỗi giữa chừng thì huỷ cả bước, lịch giữ nguyên.
     *
     * @param array<int, int> $stages [6] = BP, [5] = ĐH, [3, 4] = PC/THT (dòng ra của nhóm Pha chế)
     */
    private function gateStep(int $round, array $stages, int $attempt = 0): void
    {
        $this->hardHits = [];
        $this->pushOrigin = [];
        $atTs = $this->at->getTimestamp();
        $horizonDays = (int) config('wip_control.horizon_days', 30);
        $horizonEnd = $this->at->copy()->addDays($horizonDays)->getTimestamp();
        $buffer = $this->pullBufferHours * 3600;
        $stageName = implode('/', array_map(fn($s) => self::STAGE_NAMES[$s], $stages));
        $label = 'Kiểm soát tồn BTP (ngưng nguồn ' . $stageName . ')';
        $offRanges = $this->freshScheduler()->offRanges();
        $this->gateStage = max($stages);
        $this->info = [];   // bước trước vừa đổi lịch

        // Mỗi lô vào kho nhóm nào ở công đoạn nào, bao nhiêu viên (theo sổ tồn)
        $ledgers = $this->coverage->ledgers($this->productionCode, $this->at, $horizonDays)['ledgers'];
        $groupOf = [];   // pm => [stage => [nhóm, lượng]]
        foreach (array_keys(self::CONSUMER_STAGE) as $g) {
            foreach ($ledgers[$g] ?? [] as $l) {
                $groupOf[(int) $l['plan_master_id']][(int) $l['stage_code']] = [$g, (float) $l['qty_dvl']];
            }
        }

        // Lô chưa chạy trong khung (thêm 15 ngày để lô cuối khung còn chỗ dời)
        $rows = DB::table('stage_plan')
            ->where('deparment_code', $this->productionCode)->whereIn('stage_code', $stages)->where('active', 1)
            ->where('finished', 0)->whereNull('actual_start')->whereNotNull('start')->whereNotNull('resourceId')
            ->where('overlap', 0)
            ->where('start', '>=', date('Y-m-d H:i:s', $atTs))->where('start', '<', date('Y-m-d H:i:s', $horizonEnd + 15 * 86400))
            ->get(['id', 'plan_master_id', 'stage_code', 'resourceId', 'start', 'end', 'end_clearning', 'code', 'predecessor_code']);

        // Công đoạn sau và công đoạn trước của từng dòng
        $succ = [];   // code => [[stage, pm, start]]
        $succStage = [];   // code => công đoạn sau gần nhất (kể cả chưa có lịch)
        $succStarted = [];
        foreach (array_chunk($rows->pluck('code')->filter()->all(), 1000) as $chunk) {
            foreach (DB::table('stage_plan')->whereIn('predecessor_code', $chunk)->where('active', 1)
                ->get(['predecessor_code', 'stage_code', 'plan_master_id', 'start', 'finished', 'actual_start']) as $r) {
                $succStage[$r->predecessor_code] = min($succStage[$r->predecessor_code] ?? 99, (int) $r->stage_code);
                if ($r->start === null) {
                    continue;
                }
                $succ[$r->predecessor_code][] = [(int) $r->stage_code, (int) $r->plan_master_id, strtotime($r->start)];
                if ((int) $r->finished === 1 || ! empty($r->actual_start)) {
                    $succStarted[$r->predecessor_code] = true;
                }
            }
        }

        // PC của lô có THT không sinh tồn (tồn tính từ THT): để nguyên làm khối cố định, lùi theo ở bước dồn sát hạn.
        // Lô ép chạy đúng hạn mà vẫn vi phạm ngày NL/BB: cũng đứng yên ở giờ cũ
        $rows = $rows->filter(fn($r) => ((int) $r->stage_code !== 3 || ($succStage[$r->code] ?? 99) !== 4)
            && ! isset($this->gatePinned[(int) $r->plan_master_id]))->values();

        $this->loadInfo($rows->pluck('plan_master_id')->map(fn($pm) => (int) $pm)->unique()->values()->all());
        $rows = $rows->filter(function ($r) use ($succStarted) {
            $pm = (int) $r->plan_master_id;
            $lock = $this->info[$pm]['lock'] ?? null;
            if ($lock !== null) {
                $this->skip($pm, $lock);
                return false;
            }
            // Công đoạn sau đã nhận phòng / đã xong mà công đoạn này chưa chạy là dữ liệu bất thường:
            // giữ nguyên, không dời (dời thì phải đẩy cả lô đang chạy)
            if (isset($succStarted[$r->code])) {
                $name = self::STAGE_NAMES[(int) $r->stage_code];
                $this->gateWarnings[(int) $r->id] = 'Lô ' . ($this->info[$pm]['batch'] ?? $pm)
                    . ': công đoạn sau đã nhận phòng / đã xong nhưng ' . $name . ' chưa chạy, giữ nguyên lịch ' . $name;
                $this->skip($pm, 'Công đoạn sau đã chạy trước ' . $name . ' (dữ liệu bất thường)');
                return false;
            }
            return true;
        })->values();
        if ($rows->isEmpty()) {
            return;
        }

        $predEnd = [];
        foreach (array_chunk($rows->pluck('predecessor_code')->filter()->all(), 1000) as $chunk) {
            foreach (DB::table('stage_plan')->whereIn('code', $chunk)->where('active', 1)
                ->get(['code', 'end', 'actual_end', 'finished']) as $r) {
                $end = (int) $r->finished === 1 && $r->actual_end ? $r->actual_end : $r->end;
                if ($end) {
                    $predEnd[$r->code] = max($predEnd[$r->code] ?? 0, strtotime($end));
                }
            }
        }

        // Nhóm tồn của từng dòng; chỉ nhóm có cài Max mới là cổng
        $gateOfRow = [];   // id => [nhóm|null, lượng]
        $simKey = [];      // pm => [stage => true] dòng đang được xếp lại
        foreach ($rows as $r) {
            $pm = (int) $r->plan_master_id;
            $stage = (int) $r->stage_code;
            [$g, $q] = $groupOf[$pm][$stage] ?? [self::GROUP_OF_STAGE[$succStage[$r->code] ?? 0] ?? null, 0.0];
            $gateOfRow[(int) $r->id] = [$g !== null && isset($this->limits[$g]) ? $g : null, $q];
            $simKey[$pm][$stage] = true;
        }

        // Tồn cố định của các nhóm có Max: mọi lô trong sổ trừ dòng đang được xếp lại
        $events = [];
        foreach (array_keys($this->limits) as $g) {
            $events[$g] = [];
            foreach ($ledgers[$g] ?? [] as $l) {
                if (isset($simKey[(int) $l['plan_master_id']][(int) $l['stage_code']]) || $l['entry'] === null) {
                    continue;
                }
                $events[$g][] = [strtotime($l['entry']), (float) $l['qty_dvl']];
                foreach ($l['exits'] as $e) {
                    $events[$g][] = [strtotime($e['start']), -(float) $l['qty_dvl'] * $e['weight']];
                }
            }
        }
        unset($ledgers, $groupOf);

        // Khối cố định trong các phòng: mọi dòng khác không được xếp lại (trừ BT-HC-TI chưa bắt đầu, cuối lượt dời ra sau)
        $ids = $rows->pluck('id')->flip()->all();
        $blocks = [];
        foreach (DB::table('stage_plan')->whereIn('resourceId', $rows->pluck('resourceId')->unique()->all())
            ->where('active', 1)->where('finished', 0)->where('overlap', 0)->whereNotNull('start')
            ->where(fn($q) => $q->where('stage_code', '!=', 8)->orWhereNotNull('actual_start'))
            ->whereRaw('COALESCE(end_clearning, end) > ?', [date('Y-m-d H:i:s', $atTs)])
            ->get(['id', 'resourceId', 'start', 'end', 'end_clearning']) as $b) {
            if (! isset($ids[$b->id])) {
                $blocks[(int) $b->resourceId][] = [strtotime($b->start), strtotime($b->end_clearning ?: $b->end)];
            }
        }
        // Lô đang giữ phòng: bận tới dự kiến trả phòng (như thanh trên Gantt), không theo giờ kế hoạch cũ
        foreach (app(\App\Services\RoomOccupancyService::class)->heldUntil($rows->pluck('resourceId')->unique()->values()->all()) as $roomId => [$from, $to]) {
            $blocks[$roomId][] = [$from->getTimestamp(), $to->getTimestamp()];
        }

        $lots = [];
        $byId = [];
        $gatedPm = [];   // pm => nhóm, lô có cổng: lùi công đoạn phía trước về sát giờ mới
        foreach ($rows as $r) {
            $pm = (int) $r->plan_master_id;
            $stage = (int) $r->stage_code;
            $start = strtotime($r->start);
            $dur = strtotime($r->end) - $start;
            [$gate, $q] = $gateOfRow[(int) $r->id];
            if (isset($this->gateForced[$pm])) {
                $gate = null;   // ngưng lô này thì vi phạm ngày NL/BB: chạy đúng hạn như lô chạy thay
            }
            $consumer = $gate !== null ? self::CONSUMER_STAGE[$gate] : null;

            $limit = null;
            $exit = null;
            $hard = $this->info[$pm]['deadlines'][$stage] ?? PHP_INT_MAX;
            foreach ($succ[$r->code] ?? [] as [$sStage, $spm, $sStart]) {
                $this->loadInfo([$spm]);
                if (($sd = $this->info[$spm]['deadlines'][$sStage] ?? null) !== null) {
                    $hard = min($hard, $sd - $this->waitSeconds($sStage, $spm) - $dur);
                }
                $l = $sStart - $this->waitSeconds($sStage, $spm) - ($sStage === $consumer ? $buffer : 0);
                $limit = $limit === null ? $l : min($limit, $l);
                if ($sStage === $consumer) {
                    $exit = $exit === null ? $sStart : min($exit, $sStart);
                }
            }
            $due = $limit === null ? PHP_INT_MAX : $limit - $dur;
            if (($deadline = $this->info[$pm]['deadlines'][$stage] ?? null) !== null) {
                $due = min($due, $deadline);
            }

            $pe = $r->predecessor_code ? ($predEnd[$r->predecessor_code] ?? null) : null;
            $ready = $pe === null ? $start
                : min($start, max($atTs, $pe + $this->waitSeconds($stage, $pm), $this->info[$pm]['earliest'][$stage] ?? 0));

            $lots[] = [
                'id' => (int) $r->id, 'pm' => $pm, 'room' => (int) $r->resourceId, 'old' => $start,
                'occ' => strtotime($r->end_clearning ?: $r->end) - $start, 'dur' => $dur, 'gate' => $gate,
                'ready' => $ready, 'due' => max($start, $due), 'qty' => $gate !== null ? $q : 0.0,
                'exit' => $exit, 'lag' => $consumer !== null ? $dur + $this->waitSeconds($consumer, $pm) : 0,
                'hard' => max($start, $hard),
            ];
            $byId[(int) $r->id] = $r;
            if ($gate !== null) {
                $gatedPm[$pm] = $gate;
            }
        }
        if ($gatedPm === []) {
            return;   // không lô nào vào nhóm có Max: không có gì để ngưng
        }

        try {
            $plan = WipGate::plan($lots, $events, $blocks, $offRanges, $this->limits, $atTs, $horizonEnd);
        } catch (\RuntimeException $e) {
            $this->gateFailed($stageName, $e->getMessage());
            return;
        }
        $this->debug('gate', $stageName, count($lots), count($plan));
        unset($events, $blocks);

        $info = function (int $pm) {
            $this->loadInfo([$pm]);
            return $this->info[$pm];
        };
        $sequencer = new RoomSequencer(
            $this->productionCode,
            $atTs,
            $horizonEnd + 30 * 86400,
            fn(int $pm) => $info($pm)['lock'],
            fn(int $pm, int $stage) => $info($pm)['deadlines'][$stage] ?? null,
            function (int $stage, int $pm) use ($info) {
                $info($pm);
                return $this->waitSeconds($stage, $pm);
            },
            $offRanges,
            fn(int $pm, int $stage) => $info($pm)['earliest'][$stage] ?? null
        );

        // Lỗi thì trả lại đúng kết quả các bước trước
        $saved = [$this->moved, $this->pulled, $this->bpShifted, $this->gateWarnings];
        DB::beginTransaction();   // savepoint: lỗi thì huỷ riêng bước này
        try {
            $changes = [];
            foreach ($plan as $id => $newStart) {
                $d = $newStart - strtotime($byId[$id]->start);
                if ($d !== 0) {
                    $changes[$id] = $d;
                }
            }
            $sequencer->apply($changes, $label);

            $push = [];
            foreach ($changes as $id => $d) {
                $r = $byId[$id];
                $pm = (int) $r->plan_master_id;
                if ($v = $this->hardViolation($pm, (int) $r->stage_code, strtotime($r->start) + $d, strtotime($r->start))) {
                    $this->hardHits[$pm] = $v;
                }
                $group = $gateOfRow[$id][0] ?? array_key_first($this->limits);
                $this->recordRoomMove(['pm' => $pm, 'stage' => (int) $r->stage_code, 'start' => strtotime($r->start)], $d, 'gate', $group,
                    substr($r->start, 0, 10), $round, true);
                if ($d > 0) {
                    // Xong trễ hơn lúc công đoạn sau bắt đầu (cộng thời gian chờ) thì đẩy công đoạn sau lùi theo
                    $newEnd = strtotime($r->end) + $d;
                    foreach (DB::table('stage_plan')->where('predecessor_code', $r->code)->where('active', 1)->whereNotNull('start')
                        ->get(['id', 'stage_code', 'plan_master_id', 'start']) as $sx) {
                        $need = $newEnd + $this->waitSeconds((int) $sx->stage_code, (int) $sx->plan_master_id);
                        if (strtotime($sx->start) < $need) {
                            $push[(int) $sx->id] = max($push[(int) $sx->id] ?? 0, $need);
                            $this->pushOrigin[(int) $sx->id] ??= $pm;
                        }
                    }
                }
            }
            $this->gateGroup = array_key_first($this->limits);
            $this->repairSuccessors($push, $label, $offRanges, $atTs, $horizonEnd);

            // Lùi các công đoạn phía trước của lô có cổng về sát giờ mới: ĐH → THT, PC; THT → PC.
            // BP không lùi ĐH ở đây: ĐH do bước sau xếp lại (hoặc nhóm chờ BP không cài Max)
            $blockIs = function (array $seq, int $a, int $b, int $stage, bool $wantGated) use ($gatedPm): bool {
                for ($k = $a; $k <= $b; $k++) {
                    if ($seq[$k]['stage'] !== $stage || isset($gatedPm[$seq[$k]['pm']]) !== $wantGated) {
                        return false;
                    }
                }
                return true;
            };
            $upstream = min($stages) === 5 ? [4, 3] : (in_array(4, $stages, true) ? [3] : []);
            foreach ($upstream as $stage) {
                $rooms = DB::table('stage_plan')
                    ->where('deparment_code', $this->productionCode)->where('stage_code', $stage)->where('active', 1)
                    ->where('finished', 0)->whereNull('actual_start')->whereNotNull('resourceId')
                    ->whereBetween('start', [date('Y-m-d H:i:s', $atTs), date('Y-m-d H:i:s', $horizonEnd)])
                    ->distinct()->pluck('resourceId')->map(fn($id) => (int) $id)->all();
                foreach ($rooms as $roomId) {
                    $this->pullOrderAndJustify($sequencer, $roomId, $stage, $gatedPm, $blockIs, $atTs, $horizonEnd, $round, $label, 'gate');
                }
            }
            if ($this->hardHits !== []) {
                throw new \RuntimeException('vi phạm ngày NL/BB');
            }
            DB::commit();
        } catch (\RuntimeException $e) {
            DB::rollBack();
            RoomSequencer::resetHistoryCache();
            [$this->moved, $this->pulled, $this->bpShifted, $this->gateWarnings] = $saved;
            $this->info = [];
            if ($this->hardHits === []) {
                $this->gateFailed($stageName, $e->getMessage());
                return;
            }

            // Ngưng các lô này thì vi phạm ngày NL/BB (của chính nó hoặc lô bị đẩy lùi theo): lần sau cho chạy đúng hạn
            $this->debug('gate_hard_retry', $stageName, $attempt, $this->hardHits);
            $hits = $this->hardHits;
            foreach ($hits as $pm => $_) {
                if (isset($this->gateForced[$pm])) {
                    $this->gatePinned[$pm] = true;   // ép rồi vẫn vi phạm: lần sau giữ nguyên giờ cũ
                }
                $this->gateForced[$pm] = true;
            }
            if ($attempt >= (int) config('wip_control.hard_date_retries', 8)) {
                $this->gateFailed($stageName, 'không ngưng nguồn được mà vẫn giữ ngày NL/BB (' . implode('; ', array_slice($hits, 0, 3)) . ')');
                return;
            }
            $this->gateStep($round, $stages, $attempt + 1);
        }
    }

    private function gateFailed(string $stageName, string $message): void
    {
        $this->debug('gate_failed', $stageName, $message);
        $this->gateError = ($this->gateError !== null ? $this->gateError . '; ' : '') . $stageName . ': ' . $message;
    }

    /**
     * Đẩy lùi các dòng tới ít nhất giờ yêu cầu, lan theo phòng (lô sau bị đè thì lùi theo)
     * và theo công đoạn sau (cộng thời gian chờ), tới khi khoảng trống hấp thụ hết. Không
     * bắt đầu trong ngày nghỉ (được chạy xuyên qua); nhảy qua bảo trì / công đoạn khác / lô
     * đang chạy trong phòng. Phải đẩy lô đang chạy / đã xong thì báo lỗi.
     *
     * @param array<int, int> $req id dòng => giờ bắt đầu tối thiểu
     */
    private function pushLater(array $req, RoomSequencer $sequencer, string $label, array $offRanges): void
    {
        $skipOff = function (int $t) use ($offRanges): int {
            do {
                $moved = false;
                foreach ($offRanges as [$a, $b]) {
                    if ($t >= $a && $t < $b) {
                        $t = $b;
                        $moved = true;
                    }
                }
            } while ($moved);
            return $t;
        };
        $fmt = fn(int $t) => date('Y-m-d H:i:s', $t);
        $cols = ['id', 'plan_master_id', 'stage_code', 'code', 'resourceId', 'start', 'end', 'end_clearning', 'finished', 'actual_start', 'overlap'];
        $queue = $req;
        $steps = 0;

        // Dời một dòng tới ít nhất $min (nhảy ngày nghỉ, nhảy khối không dời được), ghi công đoạn sau cần lùi
        $move = function ($r, int $min) use (&$queue, &$steps, $skipOff, $fmt, $sequencer, $label): array {
            if (++$steps > 5000) {
                throw new \RuntimeException('Đẩy lùi lan quá rộng (> 5000 dòng)');
            }
            $stage = (int) $r->stage_code;
            if ((int) $r->finished === 1 || ! empty($r->actual_start) || $stage < 3 || $stage > 7) {
                // Công đoạn đã nhận phòng / đã xong mà công đoạn trước còn chưa xong: dữ liệu bất thường,
                // không đẩy được; giữ nguyên và báo lại thay vì huỷ cả bước
                $this->loadInfo([(int) $r->plan_master_id]);
                $this->gateWarnings[(int) $r->id] = 'Lô ' . ($this->info[(int) $r->plan_master_id]['batch'] ?? $r->plan_master_id)
                    . ' (' . (self::STAGE_NAMES[$stage] ?? 'CĐ ' . $stage) . '): đã nhận phòng / đã xong trước khi công đoạn trước xong, không đẩy lùi được';
                return [strtotime($r->start), strtotime($r->end_clearning ?: $r->end)];
            }
            $start = strtotime($r->start);
            $occ = strtotime($r->end_clearning ?: $r->end) - $start;
            $new = $skipOff($min);
            for ($k = 0; $r->resourceId && ! $r->overlap && $k < 50; $k++) {
                $wallEnd = DB::table('stage_plan')->where('resourceId', $r->resourceId)->where('id', '!=', $r->id)
                    ->where('active', 1)->where('overlap', 0)->where('finished', 0)->whereNotNull('start')
                    ->where(fn($q) => $q->whereNotNull('actual_start')->orWhereNotIn('stage_code', [3, 4, 5, 6, 7, 8]))
                    ->where('start', '<', $fmt($new + $occ))
                    ->whereRaw('COALESCE(end_clearning, end) > ?', [$fmt($new)])
                    ->max(DB::raw('COALESCE(end_clearning, end)'));
                if ($wallEnd === null) {
                    break;
                }
                $new = $skipOff(strtotime($wallEnd));
            }

            $d = $new - $start;
            $sequencer->apply([(int) $r->id => $d], $label);
            $pm = (int) $r->plan_master_id;
            $this->loadInfo([$pm]);
            if ($v = $this->hardViolation($pm, $stage, $new, $start)) {
                $this->hardHits[$this->pushOrigin[(int) $r->id] ?? $pm] = $v;
            }
            if ($stage > $this->gateStage) {
                $this->bpShifted[(int) $r->id] ??= ['pm' => $pm, 'stage' => $stage, 'old' => $r->start];
                $this->bpShifted[(int) $r->id]['new'] = $fmt($new);
            } else {
                $this->recordRoomMove(['pm' => $pm, 'stage' => $stage, 'start' => $start], $d, 'gate', $this->gateGroup, substr($r->start, 0, 10), 1, false);
            }

            $newEnd = strtotime($r->end) + $d;
            if ($r->code) {
                foreach (DB::table('stage_plan')->where('predecessor_code', $r->code)->where('active', 1)->whereNotNull('start')
                    ->get(['id', 'stage_code', 'plan_master_id', 'start']) as $sx) {
                    $this->loadInfo([(int) $sx->plan_master_id]);
                    $need = $newEnd + $this->waitSeconds((int) $sx->stage_code, (int) $sx->plan_master_id);
                    if (strtotime($sx->start) < $need) {
                        $queue[(int) $sx->id] = max($queue[(int) $sx->id] ?? 0, $need);
                        $this->pushOrigin[(int) $sx->id] ??= $this->pushOrigin[(int) $r->id] ?? $pm;
                    }
                }
            }

            return [$new, $start + $occ + $d];
        };

        while ($queue !== []) {
            asort($queue);
            $id = array_key_first($queue);
            $min = $queue[$id];
            unset($queue[$id]);

            $r = DB::table('stage_plan')->where('id', $id)->first($cols);
            if ($r === null || empty($r->start) || strtotime($r->start) >= $min) {
                continue;
            }
            [, $cursor] = $move($r, $min);

            // Các lô sau trong phòng, theo thứ tự cũ: bị đè thì lùi ra sau lô trước, gặp khoảng trống thì dừng
            if (! $r->resourceId || $r->overlap) {
                continue;
            }
            $followers = DB::table('stage_plan')->where('resourceId', $r->resourceId)->where('id', '!=', $id)
                ->where('active', 1)->where('overlap', 0)->where('finished', 0)->whereNull('actual_start')
                ->whereBetween('stage_code', [3, 7])->whereNotNull('start')
                ->where('start', '>=', $r->start)->orderBy('start')->limit(400)->get($cols);
            foreach ($followers as $f) {
                if (strtotime($f->start) >= $cursor) {
                    break;
                }
                // Lô này có thể đang chờ lùi xa hơn vì công đoạn trước của nó: lấy mốc lớn hơn
                $this->pushOrigin[(int) $f->id] ??= $this->pushOrigin[$id] ?? (int) $r->plan_master_id;
                [, $cursor] = $move($f, max($cursor, $queue[(int) $f->id] ?? 0));
                unset($queue[(int) $f->id]);
            }
        }
    }

    /**
     * Công đoạn sau (BP, ĐG) của lô vừa xếp lại ĐH chưa có hàng đúng giờ: thay vì để phòng
     * chờ (BP là nút cổ chai, cả phòng sẽ lùi theo), đổi chỗ với khối lô ngay phía sau trong
     * phòng đã có hàng; lặp tới khi kịp. Không đổi được thì mới đẩy lùi lan truyền.
     *
     * @param array<int, int> $need id dòng => giờ bắt đầu tối thiểu
     */
    private function repairSuccessors(array $need, string $label, array $offRanges, int $atTs, int $horizonEnd): void
    {
        if ($need === []) {
            return;
        }

        $info = function (int $pm) {
            $this->loadInfo([$pm]);
            return $this->info[$pm];
        };
        $sequencer = new RoomSequencer(
            $this->productionCode,
            $atTs,
            $horizonEnd + 30 * 86400,
            fn(int $pm) => $info($pm)['lock'],
            fn(int $pm, int $stage) => $info($pm)['deadlines'][$stage] ?? null,
            function (int $stage, int $pm) use ($info) {
                $info($pm);
                return $this->waitSeconds($stage, $pm);
            },
            $offRanges,
            fn(int $pm, int $stage) => $info($pm)['earliest'][$stage] ?? null
        );

        $rows = DB::table('stage_plan')->whereIn('id', array_keys($need))->get(['id', 'resourceId', 'overlap'])->keyBy('id');
        $push = [];
        foreach ($need as $id => $min) {
            $roomId = (int) ($rows[$id]->resourceId ?? 0);
            if ($roomId === 0 || (int) ($rows[$id]->overlap ?? 0) === 1) {
                $push[$id] = $min;
                continue;
            }

            for ($guard = 0; $guard < 30; $guard++) {
                $seq = $sequencer->room($roomId);
                $i = null;
                foreach ($seq as $k => $row) {
                    if ($row['id'] === $id) {
                        $i = $k;
                    }
                }
                if ($i === null || $seq[$i]['start'] >= $min) {
                    break;
                }

                // Khối phía sau gần nhất đã có hàng, lên đúng chỗ dòng này
                $plan = null;
                for ($j = $i + 1, $scanned = 0; $j < count($seq) && $scanned < 15; $scanned++) {
                    [$n1, $n2] = $sequencer->block($seq, $j);
                    $j = $n2 + 1;
                    if ($seq[$n1]['stage'] !== $seq[$i]['stage'] || $seq[$n1]['lock'] !== null) {
                        continue;
                    }
                    if (($plan = $sequencer->swapPlan($seq, $i, $n1, $n2)) !== null) {
                        break;
                    }
                }
                if ($plan === null) {
                    break;
                }
                $sequencer->apply($plan, $label);
                $sequencer->forget($roomId);
                $this->debug('gate_swap', $roomId, $plan);
                foreach ($seq as $row) {
                    if (isset($plan[$row['id']]) && $row['stage'] > $this->gateStage) {
                        $this->bpShifted[$row['id']] ??= ['pm' => $row['pm'], 'stage' => $row['stage'], 'old' => date('Y-m-d H:i:s', $row['start'])];
                        $this->bpShifted[$row['id']]['new'] = date('Y-m-d H:i:s', $row['start'] + $plan[$row['id']]);
                    }
                }
            }

            $start = DB::table('stage_plan')->where('id', $id)->value('start');
            if ($start && strtotime($start) < $min) {
                $push[$id] = $min;
            }
        }

        $this->debug('gate_push', count($push));
        $this->pushLater($push, $sequencer, $label, $offRanges);
    }

    /** Dòng công đoạn sau bị đẩy lùi theo ở chế độ ngưng nguồn */
    private function bpShiftedReport(): array
    {
        $report = [];
        foreach ($this->bpShifted as $id => $s) {
            $pm = $s['pm'];
            $this->loadInfo([$pm]);
            $lastEnd = DB::table('stage_plan')->where('plan_master_id', $pm)->where('active', 1)->max('end');
            $due = $this->info[$pm]['expected_date'] ?? null;
            $report[] = [
                'plan_master_id' => $pm,
                'batch'          => $this->info[$pm]['batch'] ?? (string) $pm,
                'product_name'   => $this->info[$pm]['product_name'] ?? null,
                'stage'          => $s['stage'],
                'old_start'      => $s['old'],
                'new_start'      => $s['new'],
                'last_end'       => $lastEnd,
                'expected_date'  => $due,
                'late'           => $due !== null && $lastEnd !== null && Carbon::parse($lastEnd)->gt(Carbon::parse($due)->endOfDay()),
            ];
        }
        usort($report, fn($a, $b) => [$b['late'], $a['old_start']] <=> [$a['late'], $b['old_start']]);

        return $report;
    }

    /**
     * Ngưng nguồn, sau khi xếp lại công đoạn nguồn: trong một phòng công đoạn phía trước,
     * với các lô trong $coated (lô có cổng, pm => nhóm):
     *  1. xếp các khối lô theo hạn (giờ bắt đầu muộn nhất còn kịp công đoạn sau):
     *     khối hạn xa đang chạy trước khối hạn gần thì đổi chỗ, để khối hạn xa còn đường lùi;
     *  2. dồn từng khối lô về sát hạn, xét từ khối cuối phòng ngược lên (lấp khoảng
     *     trống phía sau, không đẩy được lô đã sát hạn).
     *
     * @return int số lần dời
     */
    private function pullOrderAndJustify(RoomSequencer $sequencer, int $roomId, int $stage, array $coated, callable $blockIs,
        int $atTs, int $horizonEnd, int $round, string $label, string $method = 'pull'): int
    {
        $moves = 0;
        $minShift = (int) config('wip_control.min_shift_minutes', 60) * 60;
        $scanLimit = 20;

        $isHead = function (array $seq, int $i) use ($stage, $coated, $blockIs, $atTs, $horizonEnd, $sequencer): ?array {
            $row = $seq[$i];
            if ($row['stage'] !== $stage || ! isset($coated[$row['pm']]) || $row['lock'] !== null
                || $row['start'] < $atTs || $row['start'] > $horizonEnd) {
                return null;
            }
            [$a, $b] = $sequencer->block($seq, $i);
            return $a === $i && $blockIs($seq, $a, $b, $stage, true) ? [$a, $b] : null;
        };
        $due = function (array $seq, int $a, int $b): int {
            $d = PHP_INT_MAX;
            for ($k = $a; $k <= $b; $k++) {
                $d = min($d, $seq[$k]['bound'] === PHP_INT_MAX ? PHP_INT_MAX : $seq[$k]['start'] + $seq[$k]['bound']);
            }
            return $d;
        };
        $record = function (array $seq, array $plan, int $seedId) use ($round, $method, $coated) {
            $byId = [];
            foreach ($seq as $row) {
                $byId[$row['id']] = $row;
            }
            foreach ($plan as $id => $d) {
                $row = $byId[$id];
                $this->recordRoomMove($row, $d, $method, is_string($coated[$row['pm']] ?? null) ? $coated[$row['pm']] : 'BP',
                    date('Y-m-d', $row['start']), $round, $id === $seedId);
                unset($this->info[$row['pm']]);
            }
        };

        // 1. Hạn gần chạy trước
        for ($guard = 0; $guard < 200; $guard++) {
            $seq = $sequencer->room($roomId);
            $plan = null;
            $seedId = null;
            for ($i = 0; $i < count($seq) && $plan === null; $i++) {
                if (($c = $isHead($seq, $i)) === null) {
                    continue;
                }
                $dueC = $due($seq, $c[0], $c[1]);
                for ($j = $c[1] + 1, $scanned = 0; $j < count($seq) && $scanned < $scanLimit; $scanned++) {
                    $n = $isHead($seq, $j);
                    $j = $sequencer->block($seq, $j)[1] + 1;
                    if ($n === null || $due($seq, $n[0], $n[1]) >= $dueC) {
                        continue;
                    }
                    if (($option = $sequencer->swapPlan($seq, $c[0], $n[0], $n[1])) !== null) {
                        $plan = $option;
                        $seedId = $seq[$c[0]]['id'];
                        break;
                    }
                }
            }
            if ($plan === null) {
                break;
            }
            $sequencer->apply($plan, $label);
            $this->debug('pull_order', $stage, $roomId, $plan);
            $sequencer->forget($roomId);
            $record($seq, $plan, $seedId);
            $moves++;
        }

        // 2. Dồn về sát hạn, từ cuối phòng ngược lên
        $seq = $sequencer->room($roomId);
        for ($i = count($seq) - 1; $i >= 0; $i--) {
            if ($isHead($seq, $i) === null) {
                continue;
            }
            $delta = $sequencer->maxShift($seq, $i, $horizonEnd - $seq[$i]['start']);
            if ($delta < $minShift || ($plan = $sequencer->shiftPlan($seq, $i, $delta)) === null) {
                continue;
            }
            $sequencer->apply($plan, $label);
            $this->debug('pull_justify', $stage, $roomId, $plan);
            $sequencer->forget($roomId);
            $record($seq, $plan, $seq[$i]['id']);
            $moves++;
            $seq = $sequencer->room($roomId);   // thứ tự dòng không đổi, chỉ giờ
        }

        return $moves;
    }

    private function recordRoomMove(array $row, int $delta, string $method, string $group, string $date, int $round, bool $seed): void
    {
        $pm = $row['pm'];

        if ($delta < 0) {
            $this->pulled[$pm] ??= ['stage' => $row['stage'], 'old_start' => date('Y-m-d H:i:s', $row['start']), 'method' => $method];
            return;
        }

        $this->moved[$pm] ??= [
            'old_head'     => date('Y-m-d H:i:s', $row['start']),
            'old_last_end' => $this->info[$pm]['last_end'] ?? null,
            'seed'         => $seed,
            'group'        => $group,
            'date'         => $date,
            'round'        => $round,
            'top'          => $row['stage'],
            'stage'        => $row['stage'],
            'method'       => $method,
        ];
        unset($this->skipped[$pm]);
    }

    /**
     * Số giây muốn lùi một lô.
     *
     * Lô đã có lịch rút ra phía sau: lùi sao cho công đoạn nguồn chạy xong cộng
     * thời gian chờ kiểm nghiệm vừa chạm lúc rút, tức vào kho vừa kịp lúc dùng.
     * Lô chưa có lịch rút (công đoạn sau chưa sắp được): lùi qua hết đợt vượt Max
     * liên tục bắt đầu từ ngày này.
     */
    private function shiftFor(array $lot, int $pm, int $consumer, string $at, array $v, array $measure, int $horizonEnd): int
    {
        $entryTs = strtotime($lot['entry']);

        $nextExit = null;
        foreach ($lot['exits'] as $exit) {
            $ts = strtotime($exit['start']);
            if ($exit['start'] > $at && ($nextExit === null || $ts < $nextExit)) {
                $nextExit = $ts;
            }
        }

        if ($nextExit !== null) {
            $sourceDuration = ! empty($lot['entry_end']) ? max(0, strtotime($lot['entry_end']) - $entryTs) : 0;

            // Chừa khoảng đệm: phòng chưa chắc trống đúng giờ, sắp lại lệch một chút
            // là trễ công đoạn sau và lô phải trả về lịch cũ
            $buffer = (int) (config('wip_control.safety_buffer_hours', 24) * 3600);

            return ($nextExit - $sourceDuration - $this->waitSeconds($consumer, $pm) - $buffer) - $entryTs;
        }

        $series = $measure['series'][$v['group']];
        $runEnd = $horizonEnd;
        for ($i = $v['index'] + 1; $i < count($series); $i++) {
            if ((float) $series[$i]['stock_dvl'] <= $v['max']) {
                $runEnd = $measure['days'][$i]['start']->getTimestamp();
                break;
            }
        }

        return $runEnd - $entryTs;
    }

    /**
     * Lô chưa có lịch rút ra (công đoạn sau chưa sắp được, thường do chưa có ngày
     * nhận bao bì) thì không có mốc "vừa kịp" nào để bám; chặn không cho phần đã
     * lùi xong muộn hơn ngày cần hàng.
     */
    private function dueShift(int $pm, array $lot, string $at): int
    {
        foreach ($lot['exits'] as $exit) {
            if ($exit['start'] > $at) {
                return PHP_INT_MAX;
            }
        }

        $due = $this->info[$pm]['expected_date'] ?? null;
        $end = $lot['entry_end'] ?? null;
        if ($due === null || $end === null) {
            return PHP_INT_MAX;
        }

        return Carbon::parse($due)->endOfDay()->getTimestamp() - strtotime($end);
    }

    private function waitSeconds(int $stage, int $pm): int
    {
        $isVal = $this->info[$pm]['is_val'] ?? false;

        return (int) (($this->waits[$stage][$isVal ? 'val' : 'normal'] ?? 0) * 60);
    }

    /**
     * Mở rộng mỗi lô được chọn ra cả campaign và nhóm lô con đóng gói, kiểm tra
     * khoá và hạn, rồi ghi mốc "không sớm hơn" cho từng lô.
     *
     * @return array{0: array<int, int>, 1: array<int, int>}
     *         [plan_master_id => công đoạn cuối được lùi, plan_master_id => lô gốc của nhóm]
     */
    private function expandAndHint(array $picks, int $round): array
    {
        $minShift = (int) config('wip_control.min_shift_minutes', 60) * 60;
        $maxGroup = (int) config('wip_control.max_group_lots', 40);
        $movedNow = [];
        $groupOf = [];

        foreach ($picks as $seed => $pick) {
            if (isset($movedNow[$seed])) {
                continue;
            }

            $top = $pick['top'];

            $members = $this->closure($seed, $top, $maxGroup);
            if ($members === null) {
                $this->skip($seed, 'Campaign / nhóm lô con quá lớn (> ' . $maxGroup . ' lô)');
                continue;
            }

            // Cả nhóm đi cùng nhau nên lùi theo thành viên chịu được ít nhất
            $blocked = null;
            $delta = $pick['delta'];
            foreach ($members as $m) {
                if (isset($movedNow[$m])) {
                    $blocked = 'Cùng campaign với lô vừa lùi ở nhóm khác';
                    break;
                }
                $lock = $this->lockOf($m, $top);
                if ($lock !== null) {
                    $blocked = $m === $seed ? $lock : 'Cùng campaign với lô ' . $this->info[$m]['batch'] . ' (' . $lock . ')';
                    break;
                }
                $delta = min($delta, $this->maxShift($m, $top));
            }
            if ($blocked !== null) {
                $this->skip($seed, $blocked);
                continue;
            }
            if ($delta < $minShift) {
                $this->skip($seed, 'Lùi sẽ quá hạn công đoạn của lô cùng campaign');
                continue;
            }

            // Cả nhóm dùng CHUNG một mốc = lô bắt đầu sớm nhất + số giờ lùi. scheduleCampaign
            // của lõi lấy mốc lớn nhất trong các lô làm mốc bắt đầu cả campaign, nên nếu mỗi
            // lô một mốc riêng thì lô đầu bị đẩy theo mốc của lô cuối, các lô sau trễ công
            // đoạn tiêu thụ và cả nhóm phải trả về lịch cũ.
            $groupHead = null;
            foreach ($members as $m) {
                $head = $this->headStart($m, $top);
                if ($groupHead === null || $head->lt($groupHead)) {
                    $groupHead = $head;
                }
            }

            foreach ($members as $m) {
                $hint = $groupHead->copy()->addSeconds($delta);
                if (! isset($this->hints[$m]) || $hint->gt($this->hints[$m])) {
                    $this->hints[$m] = $hint;
                }

                $this->moved[$m] ??= [
                    'old_head'     => $this->headStart($m, $top)->format('Y-m-d H:i:s'),
                    'old_last_end' => $this->info[$m]['last_end'],
                    'seed'         => $m === $seed,
                    'group'        => $pick['group'],
                    'date'         => $pick['date'],
                    'round'        => $round,
                    'top'          => $top,
                ];

                $this->moved[$m]['top'] = max($this->moved[$m]['top'], $top);
                $movedNow[$m] = $top;
                $groupOf[$m] = $seed;
                unset($this->skipped[$m]);
            }
        }

        return [$movedNow, $groupOf];
    }

    /**
     * Các lô phải đi cùng một lô: cùng campaign ở các công đoạn được lùi, và cùng
     * nhóm lô con đóng gói (main_parkaging_id).
     *
     * @return int[]|null null nếu nhóm vượt trần
     */
    private function closure(int $seed, int $top, int $maxGroup): ?array
    {
        $members = [$seed => true];
        $queue = [$seed];
        $seenCampaigns = [];
        $seenMains = [];

        while ($queue !== []) {
            $this->loadInfo($queue);

            $campaigns = [];
            $mains = [];
            foreach ($queue as $m) {
                foreach ($this->info[$m]['rows'] as $row) {
                    $code = $row->campaign_code;
                    if ($code && (int) $row->stage_code <= $top && (int) $row->finished === 0 && ! isset($seenCampaigns[$code])) {
                        $seenCampaigns[$code] = true;
                        $campaigns[] = $code;
                    }
                }
                $main = $this->info[$m]['main_id'];
                if ($main && ! isset($seenMains[$main])) {
                    $seenMains[$main] = true;
                    $mains[] = $main;
                }
            }

            $found = [];
            if ($campaigns !== []) {
                $found = DB::table('stage_plan')
                    ->whereIn('campaign_code', $campaigns)
                    ->where('active', 1)
                    ->where('finished', 0)
                    ->whereBetween('stage_code', [3, $top])
                    ->where('deparment_code', $this->productionCode)
                    ->distinct()
                    ->pluck('plan_master_id')
                    ->all();
            }
            if ($mains !== []) {
                $found = array_merge($found, DB::table('plan_master')
                    ->where(fn($q) => $q->whereIn('main_parkaging_id', $mains)->orWhereIn('id', $mains))
                    ->where('active', 1)
                    ->pluck('id')
                    ->all());
            }

            $queue = [];
            foreach ($found as $pm) {
                $pm = (int) $pm;
                if (! isset($members[$pm])) {
                    $members[$pm] = true;
                    $queue[] = $pm;
                }
            }

            if (count($members) > $maxGroup) {
                return null;
            }
        }

        $this->loadInfo(array_keys($members));

        return array_keys($members);
    }

    // ------------------------------------------------------------------
    // Thông tin lô
    // ------------------------------------------------------------------

    private function loadInfo(array $pmIds): void
    {
        $pmIds = array_values(array_filter($pmIds, fn($id) => ! isset($this->info[$id])));
        if ($pmIds === []) {
            return;
        }

        foreach (array_chunk($pmIds, 1000) as $chunk) {
            $rows = DB::table('stage_plan as sp')
                ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                ->leftJoin('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                ->leftJoin('intermediate_category as ic', 'fpc.intermediate_code', '=', 'ic.intermediate_code')
                ->leftJoin('product_name as pn', 'ic.product_name_id', '=', 'pn.id')
                ->whereIn('sp.plan_master_id', $chunk)
                ->where('sp.active', 1)
                ->whereBetween('sp.stage_code', [3, 7])
                ->select(
                    'sp.id',
                    'sp.code',
                    'sp.predecessor_code',
                    'sp.plan_master_id',
                    'sp.stage_code',
                    'sp.start',
                    'sp.end',
                    'sp.finished',
                    'sp.actual_start',
                    'sp.campaign_code',
                    'sp.required_room_code',
                    'sp.resourceId',
                    'pm.batch',
                    'pm.is_val',
                    'pm.expected_date',
                    'pm.main_parkaging_id',
                    'pm.expired_material_date',
                    'pm.allow_weight_before_date',
                    'pm.preperation_before_date',
                    'pm.blending_before_date',
                    'pm.forming_before_date',
                    'pm.coating_before_date',
                    'pm.parkaging_before_date',
                    'pm.expired_packing_date',
                    'ic.quarantine_total',
                    'fpc.intermediate_code',
                    'pn.name as product_name'
                )
                ->get()
                ->groupBy('plan_master_id');

            foreach ($chunk as $pm) {
                $this->info[$pm] = $this->buildInfo((int) $pm, $rows[$pm] ?? collect());
            }
        }
    }

    private function buildInfo(int $pm, $rows): array
    {
        $first = $rows->first();
        $lock = null;
        $lastEnd = null;

        foreach ($rows as $row) {
            if ((int) $row->finished === 0 && ! empty($row->campaign_code)
                && in_array($row->campaign_code, $this->overdueCampaigns, true)) {
                $lock ??= 'Campaign quá hạn biệt trữ (VIP)';
            }
            if ((int) $row->finished === 0 && ! empty($row->end) && ($lastEnd === null || $row->end > $lastEnd)) {
                $lastEnd = $row->end;
            }
        }

        if ($first !== null) {
            if ((float) ($first->quarantine_total ?? 0) > 0) {
                $lock ??= 'Sản phẩm nhạy cảm';
            }
            if ($this->lockValidation && (int) $first->is_val === 1) {
                $lock ??= 'Lô thẩm định';
            }
        }

        // Hạn bắt đầu từng công đoạn, 06:00 như scanOverdueTasks
        $deadlines = [];
        foreach (self::STAGE_DEADLINES as $stage => $fields) {
            foreach ($fields as $field) {
                if ($first !== null && ! empty($first->$field)) {
                    $d = Carbon::parse($first->$field)->setTime(6, 0, 0)->getTimestamp();
                    $deadlines[$stage] = isset($deadlines[$stage]) ? min($deadlines[$stage], $d) : $d;
                }
            }
        }
        // Ngày NL/BB không được vi phạm: hạn là hết ngày đó (cảnh báo so theo ngày), mốc sớm nhất là đầu ngày
        $earliest = [];
        $ruleDates = [];
        foreach ($this->hardRules as $key) {
            [$field, $stage, $kind] = self::DATE_RULES[$key];
            if ($first === null || empty($first->$field)) {
                continue;
            }
            $day = Carbon::parse($first->$field)->format('Y-m-d');
            $ruleDates[$key] = $day;
            if ($kind === 'max') {
                $d = strtotime($day . ' 23:59:59');
                $deadlines[$stage] = isset($deadlines[$stage]) ? min($deadlines[$stage], $d) : $d;
            } else {
                $earliest[$stage] = max($earliest[$stage] ?? 0, strtotime($day . ' 00:00:00'));
            }
        }

        return [
            'batch'         => $first->batch ?? (string) $pm,
            'product_name'  => $first->product_name ?? null,
            'intermediate'  => $first->intermediate_code ?? null,
            'is_val'        => (int) ($first->is_val ?? 0) === 1,
            'expected_date' => $first->expected_date ?? null,
            'main_id'       => $first !== null && $first->main_parkaging_id ? (int) $first->main_parkaging_id : null,
            // Giữ gọn các cột cần cho từng dòng, thông tin cấp lô đã lấy ở trên
            'rows'          => $rows->map(fn($r) => (object) [
                'id'                 => (int) $r->id,
                'stage_code'         => (int) $r->stage_code,
                'start'              => $r->start,
                'finished'           => (int) $r->finished,
                'actual_start'       => $r->actual_start,
                'campaign_code'      => $r->campaign_code,
                'required_room_code' => $r->required_room_code,
                'resourceId'         => $r->resourceId ? (int) $r->resourceId : null,
            ])->values()->all(),
            'deadlines'     => $deadlines,
            'earliest'      => $earliest,
            'rule_dates'    => $ruleDates,
            'last_end'      => $lastEnd,
            'lock'          => $lock,
        ];
    }

    /** Lý do không lùi được phần công đoạn 3..top của lô, null nếu lùi được */
    private function lockOf(int $pm, int $top): ?string
    {
        $info = $this->info[$pm] ?? null;
        if ($info === null) {
            return 'Không tìm thấy lô';
        }
        if ($info['lock'] !== null) {
            return $info['lock'];
        }

        // Công đoạn nguồn = công đoạn lớn nhất trong phần 3..top. Nó đã chạy thì hàng
        // đã (hoặc đang) vào kho, không lùi được nữa. Còn chỉ các công đoạn trước nó
        // đã chạy (vd PC/THT xong, ĐH chưa) thì vẫn lùi được phần chưa chạy.
        $source = null;
        $lastStarted = null;
        foreach ($info['rows'] as $row) {
            $stage = (int) $row->stage_code;
            if ($stage > $top) {
                continue;
            }
            $source = max($source ?? 0, $stage);
            if ($this->started($row)) {
                $lastStarted = max($lastStarted ?? 0, $stage);
            }
        }

        if ($lastStarted !== null && $lastStarted >= $source) {
            return 'Đã chạy / đang chạy (hàng đã vào kho)';
        }

        foreach ($info['rows'] as $row) {
            if ((int) $row->stage_code <= $top && ! $this->started($row) && ! empty($row->required_room_code)) {
                return 'Phòng chỉ định';
            }
        }

        $head = $this->headStart($pm, $top);
        if ($head === null) {
            return 'Chưa có lịch';
        }
        if ($head->lt($this->at)) {
            return 'Bắt đầu trước ngày sắp lịch';
        }

        return null;
    }

    private function started($row): bool
    {
        return (int) $row->finished === 1 || ! empty($row->actual_start);
    }

    /** Mốc bắt đầu sớm nhất của các công đoạn 3..top chưa chạy */
    private function headStart(int $pm, int $top): ?Carbon
    {
        $head = null;
        foreach ($this->info[$pm]['rows'] as $row) {
            if ((int) $row->stage_code <= $top && ! $this->started($row) && ! empty($row->start)
                && ($head === null || $row->start < $head)) {
                $head = $row->start;
            }
        }

        return $head === null ? null : Carbon::parse($head);
    }

    /** Lùi được tối đa bao nhiêu giây mà công đoạn 3..top không quá hạn bắt đầu */
    private function maxShift(int $pm, int $top): int
    {
        $max = PHP_INT_MAX;
        $deadlines = $this->info[$pm]['deadlines'];

        foreach ($this->info[$pm]['rows'] as $row) {
            $stage = (int) $row->stage_code;
            if ($stage > $top || $this->started($row) || empty($row->start) || ! isset($deadlines[$stage])) {
                continue;
            }
            $max = min($max, $deadlines[$stage] - strtotime($row->start));
        }

        return $max;
    }

    /** @return array<int, array{0: int, 1: int, 2: int}> id dòng => [pm, công đoạn, giờ bắt đầu] (dòng chưa xong của xưởng) */
    private function startsSnapshot(): array
    {
        if ($this->hardRules === []) {
            return [];
        }
        $out = [];
        foreach (DB::table('stage_plan')->where('deparment_code', $this->productionCode)->where('active', 1)
            ->where('finished', 0)->whereBetween('stage_code', [3, 7])->whereNotNull('start')
            ->get(['id', 'plan_master_id', 'stage_code', 'start']) as $r) {
            $out[(int) $r->id] = [(int) $r->plan_master_id, (int) $r->stage_code, strtotime($r->start)];
        }

        return $out;
    }

    /** Dòng đã dời so với $before mà phạm ngày NL/BB không được vi phạm: id => mô tả */
    private function hardNet(array $before): array
    {
        if ($this->hardRules === []) {
            return [];
        }
        $out = [];
        foreach ($this->startsSnapshot() as $id => [$pm, $stage, $ts]) {
            $old = $before[$id][2] ?? null;
            if ($old === $ts) {
                continue;
            }
            if ($v = $this->hardViolation($pm, $stage, $ts, $old)) {
                $out[$id] = $v;
            }
        }

        return $out;
    }

    /**
     * Mô tả vi phạm nếu công đoạn $stage của lô bắt đầu lúc $ts phạm một ngày NL/BB không được vi phạm.
     * Chỉ tính vi phạm MỚI: lô đã vi phạm sẵn ngày đó ở giờ $oldTs thì bỏ qua.
     */
    private function hardViolation(int $pm, int $stage, int $ts, ?int $oldTs = null): ?string
    {
        if ($this->hardRules === []) {
            return null;
        }
        $this->loadInfo([$pm]);
        $day = date('Y-m-d', $ts);
        foreach ($this->info[$pm]['rule_dates'] ?? [] as $key => $limit) {
            [, $ruleStage, $kind] = self::DATE_RULES[$key];
            if ($ruleStage !== $stage || ($kind === 'max' ? $day <= $limit : $day >= $limit)) {
                continue;
            }
            // Lịch trước đã vi phạm sẵn ngày này (lỗi của lịch sắp xuôi, cần xử lý tay) thì không tính cho plugin
            if ($oldTs !== null && ($kind === 'max' ? date('Y-m-d', $oldTs) > $limit : date('Y-m-d', $oldTs) < $limit)) {
                continue;
            }
            return 'Lô ' . ($this->info[$pm]['batch'] ?? $pm) . ' (' . (self::STAGE_NAMES[$stage] ?? 'CĐ ' . $stage) . ' '
                . date('d/m H:i', $ts) . '): ' . self::DATE_RULE_LABELS[$key] . ' ' . date('d/m/Y', strtotime($limit));
        }

        return null;
    }

    private const DATE_RULE_LABELS = [
        'allow_weight'     => 'trước Ngày được phép cân',
        'expired_material' => 'sau Ngày HH NL chính',
        'expired_packing'  => 'sau Ngày HH BB',
        'preperation'      => 'sau hạn PC trước',
        'blending'         => 'sau hạn THT trước',
        'forming'          => 'sau hạn ĐH trước',
        'coating'          => 'sau hạn BP trước',
    ];

    private function debug(...$entry): void
    {
        if (config('wip_control.debug')) {
            $this->trace[] = array_map(fn($v) => $v instanceof Carbon ? $v->format('Y-m-d H:i') : $v, $entry);
        }
    }

    private function skip(int $pm, string $reason): void
    {
        if (isset($this->moved[$pm])) {
            return;
        }

        $this->skipped[$pm] = $reason;
    }

    // ------------------------------------------------------------------
    // Ghi lịch
    // ------------------------------------------------------------------

    private function freshScheduler(?int $maxStep = null): WipAwareScheduler
    {
        return app(WipAwareScheduler::class)->configure($this->request, $maxStep ?? $this->selectedStep, $this->hints);
    }

    /**
     * Sắp lại phần đầu nguồn vừa xoá, lần lượt theo công đoạn cuối được lùi tăng
     * dần, mỗi lượt đặt max_Step bằng đúng công đoạn đó.
     *
     * Lý do: sheduleNotCampaing của lõi xếp xong một công đoạn thì xếp lại luôn các
     * công đoạn sau tới max_Step, kể cả công đoạn đã có lịch. Chặn max_Step ở đây
     * giữ nguyên lịch công đoạn tiêu thụ trở về sau, và cũng không đụng tới các dòng
     * chưa có lịch ở công đoạn cao hơn.
     */
    private function reschedule(array $movedNow, array $snapshot): void
    {
        $tops = array_values(array_unique($movedNow));
        sort($tops);

        // Bộ sắp lịch xếp mọi dòng chưa có lịch của phân xưởng. Các dòng vốn chưa có
        // lịch từ trước không phải việc của plugin (xếp chúng vào chỉ làm tồn tăng),
        // nên tạm đánh not_schedule = 1 rồi trả lại ngay sau khi sắp xong.
        $parked = $this->parkPendingRows($snapshot, max($tops));

        try {
            foreach ($tops as $top) {
                $this->freshScheduler($top)->rescheduleUnscheduled($this->request, $this->startDate, $top);
            }
        } finally {
            foreach (array_chunk($parked, 1000) as $chunk) {
                DB::table('stage_plan')->whereIn('id', $chunk)->update(['not_schedule' => 0]);
            }
        }
    }

    /** @return int[] id các dòng đã tạm đánh not_schedule = 1 */
    private function parkPendingRows(array $snapshot, int $maxStage): array
    {
        $own = [];
        foreach ($snapshot as $rows) {
            foreach ($rows as $id => $_) {
                $own[$id] = true;
            }
        }

        $ids = [];
        foreach (DB::table('stage_plan')
            ->where('deparment_code', $this->productionCode)
            ->where('active', 1)
            ->where('finished', 0)
            ->where('not_schedule', 0)
            ->whereNull('start')
            ->whereBetween('stage_code', [3, $maxStage])
            ->pluck('id') as $id) {
            if (! isset($own[$id])) {
                $ids[] = (int) $id;
            }
        }

        foreach (array_chunk($ids, 1000) as $chunk) {
            DB::table('stage_plan')->whereIn('id', $chunk)->update(['not_schedule' => 1]);
        }

        return $ids;
    }

    /** Sao lưu lịch trước lần xoá lịch đầu tiên, khôi phục bằng nút "Khôi phục" của modal */
    private function createUndoPoint(): ?string
    {
        return app(WipAwareScheduler::class)->makeBackup();
    }

    /**
     * Chụp lại rồi xoá lịch các công đoạn 3..top của các lô, giống deActive.
     *
     * @return array<int, array<int, object>> plan_master_id => các dòng đã chụp
     */
    private function unschedule(array $movedNow): array
    {
        $snapshot = [];

        foreach ($this->byTop($movedNow) as $top => $pmIds) {
            if (config('wip_control.debug')) {
                $this->trace[] = ['unschedule', $top, $pmIds];
            }
            foreach (array_chunk($pmIds, 500) as $chunk) {
                $scope = fn() => DB::table('stage_plan')
                    ->whereIn('plan_master_id', $chunk)
                    ->where('active', 1)
                    ->where('finished', 0)
                    ->whereNull('actual_start')
                    ->whereBetween('stage_code', [3, $top]);

                foreach ($scope()->select(array_merge(['id', 'plan_master_id', 'stage_code', 'code'], self::SNAPSHOT_FIELDS))->get() as $row) {
                    $snapshot[(int) $row->plan_master_id][(int) $row->id] = $row;
                }

                $scope()->update([
                    'start' => null,
                    'end' => null,
                    'start_clearning' => null,
                    'end_clearning' => null,
                    'resourceId' => null,
                    'title' => null,
                    'title_clearning' => null,
                    'accept_quarantine' => 0,
                    'schedualed' => 0,
                    'blister_mold_id' => null,
                    'schedualed_by' => session('user.fullName'),
                    'schedualed_at' => now(),
                    'submit' => 0,
                    'comfirm_of_lead' => 0,
                    'comfirm_of_lead_by' => null,
                    'comfirm_of_lead_at' => null,
                ]);
            }
        }

        return $snapshot;
    }

    /** @return array<int, int[]> top => plan_master_id[] */
    private function byTop(array $movedNow): array
    {
        $byTop = [];
        foreach ($movedNow as $pm => $top) {
            $byTop[$top][] = $pm;
        }

        return $byTop;
    }

    /**
     * Kiểm tra lô vừa sắp lại: mọi công đoạn được lùi phải có lịch và xong (cộng
     * thời gian chờ) trước khi công đoạn tiêu thụ đã giữ nguyên bắt đầu. Nhóm nào
     * sai thì trả cả nhóm về lịch cũ; lịch cũ bị lô khác chiếm thì trả tiếp lô đó.
     *
     * @return int[] các lô đã trả về lịch cũ
     */
    private function validateRound(array $movedNow, array $groupOf, array $snapshot, array $hintsBefore, int $round): array
    {
        $members = [];
        foreach ($groupOf as $pm => $seed) {
            $members[$seed][] = $pm;
        }

        $bad = [];
        foreach ($this->brokenLots($movedNow, $snapshot) as $pm) {
            $bad[$groupOf[$pm]] = true;
        }

        $reverted = [];
        for ($guard = 0; $bad !== [] && $guard < 50; $guard++) {
            $restored = [];
            foreach (array_keys($bad) as $seed) {
                foreach ($members[$seed] as $pm) {
                    if (isset($reverted[$pm])) {
                        continue;
                    }
                    $this->restore($snapshot[$pm] ?? []);
                    if (config('wip_control.debug')) {
                        $this->trace[] = ['revert', $pm];
                    }
                    $reverted[$pm] = true;
                    $restored[] = $pm;

                    $this->failures[$pm] = ($this->failures[$pm] ?? 0) + 1;
                    if ($this->failures[$pm] >= self::MAX_FAILURES) {
                        $this->info[$pm]['lock'] = 'Sắp lại không kịp trước công đoạn sau, giữ lịch cũ';
                    }
                    if (isset($hintsBefore[$pm])) {
                        $this->hints[$pm] = $hintsBefore[$pm];
                    } else {
                        unset($this->hints[$pm]);
                    }
                    if (($this->moved[$pm]['round'] ?? null) === $round) {
                        unset($this->moved[$pm]);
                    }
                    $this->skip($pm, 'Sắp lại không kịp trước công đoạn sau, giữ lịch cũ');
                }
            }

            $bad = [];
            foreach ($this->conflictsWith($restored, $snapshot, $movedNow, $reverted) as $pm) {
                $bad[$groupOf[$pm]] = true;
            }
        }

        // Lô vẫn ở lại lịch mới thì lịch của nó đã đổi: nạp lại để vòng sau tính đúng
        $keep = array_values(array_diff(array_keys($movedNow), array_keys($reverted)));
        foreach ($keep as $pm) {
            unset($this->info[$pm]);
        }
        $this->loadInfo($keep);

        return array_keys($reverted);
    }

    /** Lô có công đoạn lùi chưa sắp được hoặc xong trễ hơn lúc công đoạn sau bắt đầu */
    private function brokenLots(array $movedNow, array $snapshot): array
    {
        $rows = DB::table('stage_plan')
            ->whereIn('plan_master_id', array_keys($movedNow))
            ->where('active', 1)
            ->where('finished', 0)
            ->whereBetween('stage_code', [3, 7])
            ->select('id', 'plan_master_id', 'stage_code', 'code', 'predecessor_code', 'start', 'end', 'end_clearning', 'resourceId', 'overlap')
            ->get();

        $shifted = [];   // code => dòng đã lùi
        $broken = [];
        foreach ($rows as $row) {
            $pm = (int) $row->plan_master_id;
            if ((int) $row->stage_code <= $movedNow[$pm] && isset($snapshot[$pm][(int) $row->id])) {
                if (empty($row->start)) {
                    $broken[$pm] = true;
                    $this->debug('broken_unscheduled', $pm, (int) $row->stage_code);
                    continue;
                }

                // Không được đè giờ lô khác trong cùng phòng (trừ phòng cho chạy song song, cờ overlap).
                // Bộ sắp lịch đôi khi kéo vệ sinh qua ngày nghỉ, đè lên lô kế tiếp.
                if ($row->resourceId && ! $row->overlap) {
                    $clash = DB::table('stage_plan')
                        ->where('resourceId', $row->resourceId)->where('id', '!=', $row->id)->where('active', 1)
                        ->where('overlap', 0)->where('finished', 0)->whereNotNull('start')
                        ->where(fn($q) => $q->where('stage_code', '!=', 8)->orWhereNotNull('actual_start'))
                        ->where('start', '<', $row->end_clearning ?: $row->end)
                        ->whereRaw('COALESCE(end_clearning, end) > ?', [$row->start])
                        ->value('id');
                    if ($clash) {
                        $broken[$pm] = true;
                        $this->debug('broken_overlap', $pm, (int) $row->stage_code, $row->start, $clash);
                        continue;
                    }
                }

                // Mốc lùi chỉ là chặn dưới, phòng bận thì bộ sắp lịch còn đẩy muộn hơn nữa
                $deadline = $this->info[$pm]['deadlines'][(int) $row->stage_code] ?? null;
                $old = $snapshot[$pm][(int) $row->id];
                if ($deadline !== null && strtotime($row->start) > $deadline
                    && (empty($old->start) || strtotime($old->start) <= $deadline)) {
                    $broken[$pm] = true;
                    $this->debug('broken_deadline', $pm, (int) $row->stage_code, $row->start);
                }
                if ($this->hardViolation($pm, (int) $row->stage_code, strtotime($row->start), empty($old->start) ? null : strtotime($old->start))) {
                    $broken[$pm] = true;
                    $this->debug('broken_date_rule', $pm, (int) $row->stage_code, $row->start);
                }
                if ($row->code) {
                    $shifted[$row->code] = $row;
                }
            }
        }

        if ($shifted === []) {
            return array_keys($broken);
        }

        // Công đoạn ngay sau phần đã lùi, kể cả lô con đóng gói thuộc plan_master khác
        $successors = DB::table('stage_plan')
            ->whereIn('predecessor_code', array_keys($shifted))
            ->where('active', 1)
            ->where('finished', 0)
            ->whereNotNull('start')
            ->select('id', 'plan_master_id', 'stage_code', 'predecessor_code', 'start')
            ->get();

        foreach ($successors as $succ) {
            $pred = $shifted[$succ->predecessor_code];
            $predPm = (int) $pred->plan_master_id;
            $succPm = (int) $succ->plan_master_id;

            // Công đoạn sau cũng vừa được lùi thì bộ sắp lịch đã lo thứ tự
            if (isset($movedNow[$succPm]) && (int) $succ->stage_code <= $movedNow[$succPm]) {
                continue;
            }
            if (empty($pred->end)) {
                continue;   // đã đánh dấu hỏng ở trên
            }

            $ready = strtotime($pred->end) + $this->waitSeconds((int) $succ->stage_code, $predPm);
            if ($ready <= strtotime($succ->start)) {
                continue;
            }

            // Lịch gốc vốn đã sát hơn thời gian chờ thì chỉ cần không tệ hơn trước
            $old = $snapshot[$predPm][(int) $pred->id] ?? null;
            if ($old !== null && ! empty($old->end) && strtotime($pred->end) <= strtotime($old->end)) {
                continue;
            }

            $broken[$predPm] = true;
            $this->debug('broken_precedence', $predPm, (int) $pred->stage_code, $pred->end, (int) $succ->stage_code, $succ->start, $old->end ?? null, $this->hints[$predPm] ?? null);
        }

        return array_keys($broken);
    }

    /** Trả các dòng về đúng giá trị đã chụp */
    private function restore(array $rows): void
    {
        foreach ($rows as $id => $row) {
            $values = [];
            foreach (self::SNAPSHOT_FIELDS as $field) {
                $values[$field] = $row->$field;
            }
            DB::table('stage_plan')->where('id', $id)->update($values);
        }
    }

    /**
     * Lô vừa sắp lại đang chiếm chỗ của các dòng vừa trả về lịch cũ.
     *
     * @return int[] các lô đã lùi trong vòng cần trả tiếp về lịch cũ
     */
    private function conflictsWith(array $restoredPms, array $snapshot, array $movedNow, array $reverted): array
    {
        $intervals = [];
        foreach ($restoredPms as $pm) {
            foreach ($snapshot[$pm] ?? [] as $row) {
                if (! empty($row->resourceId) && ! empty($row->start)) {
                    $intervals[] = [(int) $row->resourceId, $row->start, $row->end_clearning ?: $row->end];
                }
            }
        }
        if ($intervals === []) {
            return [];
        }

        $newIds = [];
        foreach ($movedNow as $pm => $top) {
            if (! isset($reverted[$pm])) {
                foreach ($snapshot[$pm] ?? [] as $id => $_) {
                    $newIds[] = $id;
                }
            }
        }
        if ($newIds === []) {
            return [];
        }

        $bad = [];
        foreach (array_chunk($newIds, 1000) as $chunk) {
            $rows = DB::table('stage_plan')
                ->whereIn('id', $chunk)
                ->whereIn('resourceId', array_values(array_unique(array_column($intervals, 0))))
                ->whereNotNull('start')
                ->select('id', 'plan_master_id', 'resourceId', 'start', 'end', 'end_clearning')
                ->get();

            foreach ($rows as $row) {
                $end = $row->end_clearning ?: $row->end;
                foreach ($intervals as [$room, $start, $stop]) {
                    if ((int) $row->resourceId === $room && $row->start < $stop && $end > $start) {
                        $bad[(int) $row->plan_master_id] = true;
                        break;
                    }
                }
            }
        }

        return array_keys($bad);
    }

    // ------------------------------------------------------------------
    // Báo cáo
    // ------------------------------------------------------------------

    private function finish(string $status, array $rounds, ?array $before, ?array $after, float $began, ?string $undoCode, array $extra): array
    {
        return [
            'status'            => $status,
            'iterations'        => count($rounds),
            'rounds'            => $rounds,
            'before'            => $before,
            'after'             => $after,
            'delayed'           => $this->delayedReport(),
            'pulled'            => $this->pulledReport(),
            'skipped'           => $this->skippedReport(),
            'skipped_by_reason' => array_count_values($this->skipped),
            'bp_shifted'        => $this->bpShiftedReport(),
            'gate_error'        => $this->gateError,
            'gate_warnings'     => array_values($this->gateWarnings),
            'date_rules'        => $this->hardRules,
            'hard_forced'       => count($this->gateForced),
            'hard_pinned'       => count($this->gatePinned),
            'hard_blocked'      => array_values(array_slice($this->hardBlocked, 0, 20)),
            'undo_code'         => $undoCode,
            'duration_seconds'  => (int) round(microtime(true) - $began),
        ] + $extra + (config('wip_control.debug') ? ['trace' => $this->trace] : []);
    }

    private function delayedReport(): array
    {
        if ($this->moved === []) {
            return [];
        }

        $rows = DB::table('stage_plan')
            ->whereIn('plan_master_id', array_keys($this->moved))
            ->where('active', 1)
            ->where('finished', 0)
            ->whereBetween('stage_code', [3, 7])
            ->select('plan_master_id', 'stage_code', 'start', 'end')
            ->get()
            ->groupBy('plan_master_id');

        $report = [];
        foreach ($this->moved as $pm => $m) {
            $info = $this->info[$pm] ?? ['batch' => (string) $pm, 'product_name' => null, 'intermediate' => null, 'expected_date' => null];
            $newHead = null;
            $lastEnd = null;
            $unscheduled = false;

            foreach ($rows[$pm] ?? [] as $row) {
                if (isset($m['stage']) && (int) $row->stage_code === $m['stage'] && ! empty($row->start)) {
                    $stageStart = $row->start;
                }
                if (empty($row->start)) {
                    // Chỉ tính phần plugin đã lùi; công đoạn sau vốn chưa có lịch thì không phải lỗi của plugin
                    $unscheduled = $unscheduled || (int) $row->stage_code <= $m['top'];
                    continue;
                }
                if ($newHead === null || $row->start < $newHead) {
                    $newHead = $row->start;
                }
                if ($lastEnd === null || $row->end > $lastEnd) {
                    $lastEnd = $row->end;
                }
            }

            $due = $info['expected_date'] !== null ? Carbon::parse($info['expected_date'])->endOfDay() : null;
            $lateBefore = $due !== null && $m['old_last_end'] !== null && Carbon::parse($m['old_last_end'])->gt($due);
            $lateNow = $due !== null && $lastEnd !== null && Carbon::parse($lastEnd)->gt($due);

            $noConsumer = true;
            foreach ($rows[$pm] ?? [] as $row) {
                if ((int) $row->stage_code > $m['top'] && ! empty($row->start)) {
                    $noConsumer = false;
                }
            }

            if (isset($stageStart)) {
                $newHead = $stageStart;
                unset($stageStart);
            }

            $report[] = [
                'plan_master_id' => $pm,
                'method'         => $m['method'] ?? 'reschedule',
                'stage'          => $m['stage'] ?? null,
                'no_consumer'    => $noConsumer,   // công đoạn sau chưa có lịch, không có mốc rút hàng
                'batch'          => $info['batch'],
                'product_name'   => $info['product_name'],
                'intermediate'   => $info['intermediate'],
                'group'          => $m['group'],
                'violation_date' => $m['date'],
                'seed'           => $m['seed'],
                'old_start'      => $m['old_head'],
                'new_start'      => $newHead,
                'old_last_end'   => $m['old_last_end'],
                'last_end'       => $lastEnd,
                'expected_date'  => $info['expected_date'],
                'late'           => $lateNow && ! $lateBefore,   // trễ do bị lùi
                'late_before'    => $lateBefore,
                'unscheduled'    => $unscheduled,
            ];
        }

        usort($report, fn($a, $b) => [$b['late'], $b['unscheduled'], $a['old_start']] <=> [$a['late'], $a['unscheduled'], $b['old_start']]);

        return $report;
    }

    /** Lô không bao phim (hoặc đi nhóm khác) được kéo lên sớm khi đổi chỗ */
    private function pulledReport(): array
    {
        $report = [];
        foreach ($this->pulled as $pm => $p) {
            $this->loadInfo([$pm]);
            $row = DB::table('stage_plan')->where('plan_master_id', $pm)->where('stage_code', $p['stage'])
                ->where('active', 1)->value('start');
            $report[] = [
                'plan_master_id' => $pm,
                'batch'          => $this->info[$pm]['batch'],
                'product_name'   => $this->info[$pm]['product_name'],
                'stage'          => $p['stage'],
                'method'         => $p['method'] ?? 'swap',
                'old_start'      => $p['old_start'] ?: null,
                'new_start'      => $row,
                'expected_date'  => $this->info[$pm]['expected_date'],
            ];
        }
        usort($report, fn($a, $b) => strcmp($a['new_start'] ?? '', $b['new_start'] ?? ''));

        return $report;
    }

    private function skippedReport(): array
    {
        $report = [];
        foreach ($this->skipped as $pm => $reason) {
            $info = $this->info[$pm] ?? null;
            $report[] = [
                'plan_master_id' => $pm,
                'batch'          => $info['batch'] ?? (string) $pm,
                'product_name'   => $info['product_name'] ?? null,
                'reason'         => $reason,
            ];
        }

        usort($report, fn($a, $b) => strcmp($a['reason'], $b['reason']));

        // Lô đã chạy trong kho có thể rất nhiều; danh sách chỉ cần đủ để tra cứu
        return array_slice($report, 0, 300);
    }
}
