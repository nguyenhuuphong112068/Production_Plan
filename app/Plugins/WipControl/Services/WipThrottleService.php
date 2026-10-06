<?php

namespace App\Plugins\WipControl\Services;

use App\Services\WipCoverageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lùi đầu nguồn để tồn bán thành phẩm không vượt Max.
 *
 * Mỗi vòng:
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
    /** Công đoạn tiêu thụ tồn của từng nhóm đích */
    public const CONSUMER_STAGE = ['DH' => 5, 'BP' => 6, 'DG' => 7];

    /** Hạn bắt đầu của từng công đoạn trên plan_master, giống scanOverdueTasks */
    private const STAGE_DEADLINES = [
        3 => ['expired_material_date', 'preperation_before_date'],
        4 => ['blending_before_date'],
        5 => ['forming_before_date'],
        6 => ['coating_before_date'],
        7 => ['parkaging_before_date', 'expired_packing_date'],
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
    private bool $prioritizeNonCoated = false;
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

    /** @var array<int, array<int, int>> lô kéo lên không được xếp muộn hơn cũ: pm => [stage => giờ bắt đầu cũ] */
    private array $noLater = [];

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
        $this->prioritizeNonCoated = (bool) ($opt['prioritize_non_coated'] ?? false);
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

            // Cả vòng trong một transaction: lỗi giữa chừng (kể cả PHP chết) thì vòng
            // này tự huỷ, lịch quay về đúng như trước vòng, không để dòng nào bị khoá treo
            DB::beginTransaction();
            try {
                // Bước 0 (chỉ vòng đầu): ưu tiên lô không bao phim, ngưng nguồn lô bao phim
                $mix = $round === 1 ? $this->mixStep($measure, $round) : ['delayed' => 0, 'pulled' => 0, 'reverted' => 0];
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

        return $this->finish($status, $rounds, $before, $last, $began, $undoCode, [
            'out_of_scope' => $outOfScope,
            'new_overdue'  => array_values(array_diff($overdueAfter, $overdueBefore)),
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
            foreach ($measure['series'][$group] ?? [] as $point) {
                if ((float) $point['stock_dvl'] > $peak) {
                    $peak = (float) $point['stock_dvl'];
                    $peakDate = $point['date'];
                }
            }

            $days = array_values(array_filter($measure['violations'], fn($v) => $v['group'] === $group));

            $groups[] = [
                'group'          => $group,
                'name'           => WipCoverageService::groupName($group),
                'max'            => $max,
                'peak'           => round($peak, 2),
                'peak_date'      => $peakDate,
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
            $this->freshScheduler()->offRanges()
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
     * Bước 0: ưu tiên lô KHÔNG bao phim, ngưng nguồn lô bao phim (chỉ khi cài Max chờ BP).
     *
     *  1. Lô bao phim có ĐH rơi vào những ngày chờ BP vượt Max: lùi PC → ĐH (phần chưa chạy)
     *     tới mốc vừa kịp trước giờ BP của chính nó; BP, ĐG giữ nguyên. Ngày cần hàng
     *     muộn nhất trước, đủ bù phần vượt lớn nhất.
     *  2. Lô không bao phim chưa chạy công đoạn nào, đã có ngày NL và ngày nhận bao bì,
     *     ĐH đang xếp sau những ngày vượt (hoặc chưa có lịch): kéo cả chuỗi PC → ĐG lên,
     *     ngày cần hàng sớm nhất trước, chỉ đủ lấp số giờ PC vừa giải phóng ở bước 1.
     *  3. Sắp lại lô không bao phim TRƯỚC (lô bao phim tạm khoá), rồi lô bao phim với mốc
     *     "vừa kịp". Nhóm nào sắp lại không kịp / không xếp được thì trả về lịch cũ.
     *
     * @return array{delayed: int, pulled: int, reverted: int}
     */
    private function mixStep(array $measure, int $round): array
    {
        $result = ['delayed' => 0, 'pulled' => 0, 'reverted' => 0];
        if (! $this->prioritizeNonCoated || ! isset($this->limits['BP'])) {
            return $result;
        }

        $days = array_values(array_filter($measure['violations'], fn($v) => $v['group'] === 'BP'));
        if ($days === []) {
            return $result;
        }

        $from = $days[0]['day_start'];
        $to = date('Y-m-d H:i:s', strtotime(end($days)['day_start']) + 86400);
        $peakExcess = max(array_column($days, 'excess'));
        $minShift = (int) config('wip_control.min_shift_minutes', 60) * 60;
        $maxGroup = (int) config('wip_control.max_group_lots', 40);
        $buffer = (int) (config('wip_control.safety_buffer_hours', 24) * 3600);

        $coated = DB::table('stage_plan')->where('deparment_code', $this->productionCode)
            ->where('stage_code', 6)->where('active', 1)->distinct()->pluck('plan_master_id')->flip()->all();

        // ---------------- 1. Lô bao phim cần ngưng nguồn
        $dhRows = DB::table('stage_plan')
            ->where('deparment_code', $this->productionCode)->where('stage_code', 5)->where('active', 1)
            ->where('finished', 0)->whereNull('actual_start')
            ->where('start', '>=', max($from, $this->at->format('Y-m-d H:i:s')))->where('start', '<', $to)
            ->get(['plan_master_id', 'Theoretical_yields as qty']);
        $seeds = $dhRows->filter(fn($r) => isset($coated[$r->plan_master_id]))->keyBy('plan_master_id');
        $this->loadInfo($seeds->keys()->all());

        $bpStart = DB::table('stage_plan')->whereIn('plan_master_id', $seeds->keys()->all())->where('stage_code', 6)
            ->where('active', 1)->whereNotNull('start')->pluck('start', 'plan_master_id');
        $dhEnd = DB::table('stage_plan')->whereIn('plan_master_id', $seeds->keys()->all())->where('stage_code', 5)
            ->where('active', 1)->pluck('end', 'plan_master_id');

        // Lùi được bao nhiêu mà ĐH vẫn xong (cộng thời gian chờ + đệm) trước giờ BP
        $slack = function (int $pm) use (&$bpStart, &$dhEnd, $buffer) {
            if (! isset($bpStart[$pm]) || empty($dhEnd[$pm])) {
                return 0;
            }
            $s = strtotime($bpStart[$pm]) - $this->waitSeconds(6, $pm) - $buffer - strtotime($dhEnd[$pm]);
            return min($s, $this->maxShift($pm, 5));
        };

        $hintsBefore = $this->hints;

        $order = $seeds->keys()->all();
        usort($order, fn($a, $b) => strcmp($this->info[$b]['expected_date'] ?? '9999', $this->info[$a]['expected_date'] ?? '9999'));

        $movedA = [];
        $groupOf = [];
        $groupPlan = [];
        $covered = 0.0;
        foreach ($order as $seed) {
            if ($covered >= $peakExcess) {
                break;
            }
            if (isset($movedA[$seed])) {
                continue;
            }
            if (($lock = $this->lockOf($seed, 5)) !== null) {
                $this->skip($seed, $lock);
                continue;
            }

            $members = $this->closure($seed, 5, $maxGroup);
            if ($members === null) {
                $this->skip($seed, 'Campaign / nhóm lô con quá lớn (> ' . $maxGroup . ' lô)');
                continue;
            }

            $missing = array_diff($members, array_keys($bpStart->all()));
            if ($missing !== []) {
                $bpStart = $bpStart->union(DB::table('stage_plan')->whereIn('plan_master_id', $missing)->where('stage_code', 6)
                    ->where('active', 1)->whereNotNull('start')->pluck('start', 'plan_master_id'));
                $dhEnd = $dhEnd->union(DB::table('stage_plan')->whereIn('plan_master_id', $missing)->where('stage_code', 5)
                    ->where('active', 1)->pluck('end', 'plan_master_id'));
            }

            $delta = PHP_INT_MAX;
            $blocked = null;
            foreach ($members as $m) {
                if (isset($movedA[$m]) || ! isset($coated[$m])) {
                    $blocked = 'Cùng campaign với lô không bao phim / lô đã chọn';
                    break;
                }
                if (($lock = $this->lockOf($m, 5)) !== null) {
                    $blocked = $m === $seed ? $lock : 'Cùng campaign với lô ' . $this->info[$m]['batch'] . ' (' . $lock . ')';
                    break;
                }
                $delta = min($delta, $slack($m));
            }
            if ($blocked !== null) {
                $this->skip($seed, $blocked);
                continue;
            }
            if ($delta < $minShift) {
                $this->skip($seed, 'Ngưng nguồn: không còn dư thời gian trước giờ BP');
                continue;
            }

            $groupHead = null;
            foreach ($members as $m) {
                $head = $this->headStart($m, 5);
                $groupHead = $groupHead === null || $head->lt($groupHead) ? $head : $groupHead;
            }
            $groupPlan[$seed] = ['head' => $groupHead, 'delta' => $delta, 'members' => $members];
            foreach ($members as $m) {
                $this->hints[$m] = $groupHead->copy()->addSeconds($delta);
                $movedA[$m] = 5;
                $groupOf[$m] = $seed;
                $covered += (float) ($seeds[$m]->qty ?? 0);
                $this->moved[$m] ??= [
                    'old_head'     => (string) DB::table('stage_plan')->where('plan_master_id', $m)->where('stage_code', 5)->where('active', 1)->value('start'),
                    'old_last_end' => $this->info[$m]['last_end'],
                    'seed'         => $m === $seed,
                    'group'        => 'BP',
                    'date'         => $days[0]['date'],
                    'round'        => $round,
                    'top'          => 5,
                    'stage'        => 5,
                    'method'       => 'mix',
                ];
            }
        }

        if ($movedA === []) {
            return $result;
        }

        // ---- Lùi lô bao phim TRƯỚC và kiểm tra ngay: chỉ lô lùi được mới giải phóng năng lực
        $snapA = $this->unschedule($movedA);
        $this->reschedule($movedA, $snapA);
        $revA = $this->validateRound($movedA, $groupOf, $snapA, $hintsBefore, $round);
        $keptA = array_diff(array_keys($movedA), $revA);
        $result['delayed'] = count($keptA);
        $result['reverted'] = count($revA);
        if ($keptA === []) {
            return $result;
        }

        // Ngân sách: số giờ PC (theo lịch cũ) của các lô bao phim vừa lùi; PC đã chạy hết thì tính giờ ĐH
        $oldHours = function (int $stage) use ($snapA, $keptA) {
            $h = 0.0;
            foreach ($keptA as $pm) {
                foreach ($snapA[$pm] ?? [] as $row) {
                    if ((int) $row->stage_code === $stage && $row->start && $row->end) {
                        $h += (strtotime($row->end) - strtotime($row->start)) / 3600;
                    }
                }
            }
            return $h;
        };
        $budgetStage = 3;
        $budget = $oldHours(3);
        if ($budget <= 0) {
            $budgetStage = 5;
            $budget = $oldHours(5);
        }

        // ---------------- 2. Lô không bao phim kéo lên
        $toDate = substr($to, 0, 10);
        $pool = DB::table('stage_plan as sp')
            ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->where('sp.deparment_code', $this->productionCode)->where('sp.stage_code', 5)->where('sp.active', 1)
            ->where('pm.active', 1)->where('sp.finished', 0)->whereNull('sp.actual_start')->where('sp.not_schedule', 0)
            ->where(fn($q) => $q->whereNull('sp.start')->orWhere('sp.start', '>=', $to))
            ->whereNotNull('pm.after_weigth_date')->where('pm.after_weigth_date', '<=', $toDate)
            ->whereNotNull('pm.after_parkaging_date')
            ->orderBy('pm.expected_date')
            ->pluck('sp.plan_master_id')
            ->reject(fn($pm) => isset($coated[$pm]))
            ->values()->all();
        $this->loadInfo($pool);

        // Giờ của một lô ở công đoạn tính ngân sách: theo lịch đang có, chưa có lịch thì theo định mức
        $cost = function (int $pm) use ($budgetStage) {
            $row = DB::table('stage_plan as sp')->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                ->leftJoin('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                ->where('sp.plan_master_id', $pm)->where('sp.stage_code', $budgetStage)->where('sp.active', 1)
                ->first(['sp.start', 'sp.end', 'fpc.intermediate_code']);
            if ($row === null) {
                return 0.0;   // lô không có công đoạn này thì không tốn giờ ở đó
            }
            if ($row->start && $row->end) {
                return (strtotime($row->end) - strtotime($row->start)) / 3600;
            }
            $min = DB::table('quota')->where('intermediate_code', $row->intermediate_code)->where('stage_code', $budgetStage)
                ->selectRaw('MIN(TIME_TO_SEC(p_time) + TIME_TO_SEC(m_time)) / 3600 AS h')->value('h');
            // Không ước tính được thì coi như không vừa ngân sách, để không kéo quá tay
            return $min === null ? INF : (float) $min;
        };

        $movedB = [];
        $groupB = [];
        $used = 0.0;
        foreach ($pool as $seed) {
            if ($used >= $budget) {
                break;
            }
            if (isset($movedB[$seed]) || isset($movedA[$seed])) {
                continue;
            }
            $members = $this->closure($seed, 7, $maxGroup);
            if ($members === null) {
                continue;
            }

            $ok = true;
            $groupCost = 0.0;
            foreach ($members as $m) {
                $info = $this->info[$m];
                if (isset($coated[$m]) || isset($movedA[$m]) || isset($movedB[$m]) || $info['lock'] !== null) {
                    $ok = false;
                    break;
                }
                foreach ($info['rows'] as $row) {
                    if ($this->started($row)) {
                        $ok = false;
                        break 2;
                    }
                }
                $groupCost += $cost($m);
            }
            if (! $ok || $groupCost <= 0 || $used + $groupCost > $budget * 1.1) {
                continue;
            }

            $used += $groupCost;
            foreach ($members as $m) {
                $movedB[$m] = 7;
                $groupB[$m] = $seed;
            }
        }

        $this->debug('mix', count($keptA), count($movedB), round($budget, 1), round($used, 1), $budgetStage);
        if ($movedB === []) {
            return $result;
        }

        // ---------------- 3. Sắp lại cả chuỗi PC → ĐG của lô không bao phim, các dòng khác tạm khoá
        $hintsBeforeB = $this->hints;
        $snapB = $this->unschedule($movedB);
        foreach ($snapB as $pm => $rows) {
            foreach ($rows as $row) {
                if ($row->start && in_array((int) $row->stage_code, [5, 7], true)) {
                    $this->noLater[$pm][(int) $row->stage_code] = strtotime($row->start);
                }
            }
        }

        $parked = $this->parkPendingRows($snapB, 7);
        try {
            $this->freshScheduler(7)->rescheduleUnscheduled($this->request, $this->startDate, 7);
        } finally {
            foreach (array_chunk($parked, 1000) as $chunk) {
                DB::table('stage_plan')->whereIn('id', $chunk)->update(['not_schedule' => 0]);
            }
        }

        $revB = $this->validateRound($movedB, $groupB, $snapB, $hintsBeforeB, $round);
        foreach (array_diff(array_keys($movedB), $revB) as $m) {
            $old = null;
            foreach ($snapB[$m] ?? [] as $row) {
                if ((int) $row->stage_code === 5) {
                    $old = $row->start;
                }
            }
            $this->pulled[$m] = ['stage' => 5, 'old_start' => $old, 'method' => 'mix', 'round' => $round];
        }
        $this->noLater = [];

        $result['pulled'] = count($movedB) - count($revB);
        $result['reverted'] += count($revB);

        return $result;
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
                        ->where('overlap', 0)->whereNotNull('start')
                        ->where('start', '<', $row->end_clearning ?: $row->end)
                        ->whereRaw('COALESCE(end_clearning, end) > ?', [$row->start])
                        ->value('id');
                    if ($clash) {
                        $broken[$pm] = true;
                        $this->debug('broken_overlap', $pm, (int) $row->stage_code, $row->start, $clash);
                        continue;
                    }
                }

                // Lô được kéo lên mà bị xếp muộn hơn lịch cũ thì không có lợi gì
                $notLater = $this->noLater[$pm][(int) $row->stage_code] ?? null;
                if ($notLater !== null && strtotime($row->start) > $notLater) {
                    $broken[$pm] = true;
                    $this->debug('broken_later', $pm, (int) $row->stage_code, $row->start);
                    continue;
                }

                // Mốc lùi chỉ là chặn dưới, phòng bận thì bộ sắp lịch còn đẩy muộn hơn nữa
                $deadline = $this->info[$pm]['deadlines'][(int) $row->stage_code] ?? null;
                $old = $snapshot[$pm][(int) $row->id];
                if ($deadline !== null && strtotime($row->start) > $deadline
                    && (empty($old->start) || strtotime($old->start) <= $deadline)) {
                    $broken[$pm] = true;
                    $this->debug('broken_deadline', $pm, (int) $row->stage_code, $row->start);
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
