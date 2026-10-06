<?php

namespace App\Plugins\WipControl\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dời lịch trong MỘT phòng mà không đụng các công đoạn khác.
 *
 * Bộ sắp lịch tự động chỉ đặt được một lô vào chỗ trống sớm nhất sau mốc cho
 * trước. Phòng ĐH chạy kín thì lùi một lô đồng nghĩa với đẩy nó xuống cuối hàng
 * đợi, trễ công đoạn sau. Lớp này thao tác thẳng trên trình tự lô của phòng:
 *
 *  - Giãn lịch (stretch): dời một khối lô và mọi lô phía sau trong phòng trễ đi
 *    Δ; khoảng trống giữa các lô hấp thụ dần độ dời (giống ScheduleRerouteService
 *    nhưng KHÔNG lan sang công đoạn sau).
 *  - Đổi chỗ (swap): đưa một khối lô phía sau (vd lô không bao phim) lên đúng chỗ
 *    của khối lô đang gây tồn, các lô ở giữa dời trễ đúng bằng độ dài khối được
 *    đưa lên; chỗ cũ của khối đó hấp thụ phần dời.
 *
 * Mọi phương án đều kiểm tra trước, không cần sắp lại rồi trả về:
 *  - lô dời trễ không được trễ công đoạn sau (cộng thời gian chờ kiểm nghiệm) và
 *    không quá hạn bắt đầu của công đoạn; lô đã vi phạm sẵn thì không được dời thêm;
 *  - lô kéo sớm phải có công đoạn trước xong (cộng thời gian chờ) trước giờ mới;
 *  - lô cố định (đã chạy, bảo trì, công đoạn khác, bị khoá) là bức tường, độ dời
 *    phải được khoảng trống hấp thụ hết trước khi chạm tới.
 *
 * Độ dài lô giữ nguyên khi dời; lô không được BẮT ĐẦU trong ngày nghỉ (nhảy tới
 * cuối khoảng nghỉ như skipOffTime của lõi) nhưng được chạy xuyên qua. Loại vệ sinh không tính lại khi đổi chỗ.
 */
class RoomSequencer
{
    /** Chỉ dời các công đoạn sinh tồn bán thành phẩm */
    private const MOVABLE_STAGES = [3, 4, 5, 6];

    /** @var callable(int $pm): ?string lý do khoá lô, null nếu được dời */
    private $lockOf;

    /** @var callable(int $pm, int $stage): ?int hạn bắt đầu (timestamp) */
    private $deadline;

    /** @var callable(int $stage, int $pm): int giây chờ trước khi vào công đoạn */
    private $wait;

    private string $productionCode;
    private int $at;
    private int $windowEnd;

    /** @var array<int, array> roomId => trình tự đã nạp */
    private array $cache = [];

    /** @var array<int, array{0: int, 1: int}> khoảng nghỉ: không được BẮT ĐẦU lô trong đó */
    private array $offRanges;

    public function __construct(string $productionCode, int $at, int $windowEnd, callable $lockOf, callable $deadline, callable $wait, array $offRanges = [])
    {
        $this->offRanges = $offRanges;
        $this->productionCode = $productionCode;
        $this->at = $at;
        $this->windowEnd = $windowEnd;
        $this->lockOf = $lockOf;
        $this->deadline = $deadline;
        $this->wait = $wait;
    }

    /** Giờ bắt đầu rơi vào ngày nghỉ thì nhảy tới cuối khoảng nghỉ (giống skipOffTime của lõi: được chạy xuyên qua, không được bắt đầu) */
    private function skipOff(int $ts): int
    {
        do {
            $moved = false;
            foreach ($this->offRanges as [$from, $to]) {
                if ($ts >= $from && $ts < $to) {
                    $ts = $to;
                    $moved = true;
                }
            }
        } while ($moved);

        return $ts;
    }

    public function forget(int $roomId): void
    {
        unset($this->cache[$roomId]);
    }

    /**
     * Trình tự lô của một phòng, sắp theo giờ bắt đầu, kèm cận trên độ dời trễ
     * (bound) và mốc sẵn sàng sớm nhất (ready) của từng dòng.
     *
     * @return array<int, array>
     */
    public function room(int $roomId): array
    {
        if (isset($this->cache[$roomId])) {
            return $this->cache[$roomId];
        }

        $rows = DB::table('stage_plan')
            ->where('resourceId', $roomId)
            ->where('active', 1)
            ->whereNotNull('start')
            ->where('start', '<=', date('Y-m-d H:i:s', $this->windowEnd))
            ->whereRaw('COALESCE(end_clearning, end) >= ?', [date('Y-m-d H:i:s', $this->at - 86400)])
            ->orderBy('start')
            ->select('id', 'plan_master_id', 'stage_code', 'code', 'predecessor_code', 'start', 'end',
                'start_clearning', 'end_clearning', 'finished', 'actual_start', 'campaign_code', 'deparment_code')
            ->get();

        // Công đoạn sau của các dòng: mốc phải xong trước (đã trừ thời gian chờ)
        $codes = $rows->pluck('code')->filter()->unique()->values()->all();
        $successorLimit = [];
        foreach (array_chunk($codes, 1000) as $chunk) {
            foreach (DB::table('stage_plan')
                ->whereIn('predecessor_code', $chunk)
                ->where('active', 1)
                ->where('finished', 0)
                ->whereNotNull('start')
                ->select('predecessor_code', 'stage_code', 'plan_master_id', 'start')
                ->get() as $succ) {
                $limit = strtotime($succ->start) - ($this->wait)((int) $succ->stage_code, (int) $succ->plan_master_id);
                $successorLimit[$succ->predecessor_code] = min($successorLimit[$succ->predecessor_code] ?? PHP_INT_MAX, $limit);
            }
        }

        // Công đoạn trước của các dòng: mốc sẵn sàng khi kéo sớm
        $predCodes = $rows->pluck('predecessor_code')->filter()->unique()->values()->all();
        $predEnd = [];
        foreach (array_chunk($predCodes, 1000) as $chunk) {
            foreach (DB::table('stage_plan')
                ->whereIn('code', $chunk)
                ->where('active', 1)
                ->whereNotIn('stage_code', [1, 2])
                ->select('code', 'start', 'end', 'actual_end', 'finished')
                ->get() as $pred) {
                $end = (int) $pred->finished === 1 && $pred->actual_end ? $pred->actual_end : $pred->end;
                // Công đoạn trước chưa có lịch thì không kéo sớm được
                $predEnd[$pred->code] = max($predEnd[$pred->code] ?? 0, $end ? strtotime($end) : PHP_INT_MAX);
            }
        }

        $seq = [];
        foreach ($rows as $r) {
            $start = strtotime($r->start);
            $end = strtotime($r->end);
            $occEnd = $r->end_clearning ? max($end, strtotime($r->end_clearning)) : $end;
            $stage = (int) $r->stage_code;
            $pm = (int) $r->plan_master_id;

            $lock = null;
            if (! in_array($stage, self::MOVABLE_STAGES, true)) {
                $lock = 'Công đoạn khác / bảo trì';
            } elseif ((int) $r->finished === 1 || ! empty($r->actual_start)) {
                $lock = 'Đã chạy / đang chạy';
            } elseif ($start < $this->at) {
                $lock = 'Bắt đầu trước ngày sắp lịch';
            } elseif ($r->deparment_code !== $this->productionCode) {
                $lock = 'Phân xưởng khác';
            } else {
                $lock = ($this->lockOf)($pm);
            }

            // Cận trên độ dời trễ: công đoạn sau và hạn bắt đầu; đang vi phạm sẵn thì 0
            $bound = PHP_INT_MAX;
            if ($r->code && isset($successorLimit[$r->code])) {
                $bound = min($bound, $successorLimit[$r->code] - $end);
            }
            $deadline = ($this->deadline)($pm, $stage);
            if ($deadline !== null) {
                $bound = min($bound, $deadline - $start);
            }

            $ready = $this->at;
            if ($r->predecessor_code && isset($predEnd[$r->predecessor_code])) {
                $pe = $predEnd[$r->predecessor_code];
                $ready = $pe === PHP_INT_MAX ? PHP_INT_MAX : max($ready, $pe + ($this->wait)($stage, $pm));
            }

            $seq[] = [
                'id'       => (int) $r->id,
                'pm'       => $pm,
                'stage'    => $stage,
                'start'    => $start,
                'end'      => $end,
                'occ_end'  => $occEnd,
                'campaign' => $r->campaign_code,
                'lock'     => $lock,
                'bound'    => max(0, $bound),
                'ready'    => $ready,
            ];
        }

        return $this->cache[$roomId] = $seq;
    }

    /** Vị trí dòng của lô ở công đoạn cho trước trong trình tự phòng */
    public function indexOf(array $seq, int $pm, int $stage): ?int
    {
        foreach ($seq as $i => $row) {
            if ($row['pm'] === $pm && $row['stage'] === $stage) {
                return $i;
            }
        }

        return null;
    }

    /** Khối = các dòng liền nhau cùng campaign; lô lẻ là khối một dòng. @return array{0:int,1:int} */
    public function block(array $seq, int $i): array
    {
        $first = $last = $i;
        $code = $seq[$i]['campaign'];
        if ($code) {
            while ($first > 0 && $seq[$first - 1]['campaign'] === $code && $seq[$first - 1]['stage'] === $seq[$i]['stage']) {
                $first--;
            }
            while ($last < count($seq) - 1 && $seq[$last + 1]['campaign'] === $code && $seq[$last + 1]['stage'] === $seq[$i]['stage']) {
                $last++;
            }
        }

        return [$first, $last];
    }

    /**
     * Phương án dời trễ từ dòng $from trở đi một khoảng $delta giây.
     *
     * @param int|null $stopBefore dừng trước dòng này (chỗ trống chắc chắn hấp thụ hết)
     * @return array<int, int>|null [id => giây dời], null nếu không dời được
     */
    public function shiftPlan(array $seq, int $from, int $delta, ?int $stopBefore = null): ?array
    {
        $changes = [];
        $n = $stopBefore ?? count($seq);
        $prevOccEnd = null;

        for ($j = $from; $j < $n; $j++) {
            // Lô đầu dời Δ, lô sau chỉ dời khi bị lô trước (đã dời) đè lên; rơi vào ngày nghỉ thì nhảy qua
            $newStart = $j === $from ? $seq[$j]['start'] + $delta : max($seq[$j]['start'], $prevOccEnd);
            $s = $this->skipOff($newStart) - $seq[$j]['start'];
            if ($s <= 0) {
                return $changes;
            }
            if ($seq[$j]['lock'] !== null || $s > $seq[$j]['bound']) {
                return null;
            }
            $changes[$seq[$j]['id']] = $s;
            $prevOccEnd = $seq[$j]['occ_end'] + $s;
        }

        return $changes;
    }

    /** Độ dời trễ lớn nhất (≤ $wanted) khả thi từ dòng $from, làm tròn 15 phút */
    public function maxShift(array $seq, int $from, int $wanted): int
    {
        $step = 900;
        $lo = 0;
        $hi = intdiv(max(0, $wanted), $step);

        if ($hi > 0 && $this->shiftPlan($seq, $from, $hi * $step) !== null) {
            return $hi * $step;
        }

        while ($lo < $hi - 1) {
            $mid = intdiv($lo + $hi, 2);
            if ($this->shiftPlan($seq, $from, $mid * $step) !== null) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }

        return $lo * $step;
    }

    /**
     * Phương án đưa khối [$n1..$n2] lên đúng chỗ dòng $c (đầu khối đang gây tồn).
     *
     * @return array<int, int>|null [id => giây dời, âm là kéo sớm]
     */
    public function swapPlan(array $seq, int $c, int $n1, int $n2): ?array
    {
        if ($n1 <= $c) {
            return null;
        }

        // Khối đưa lên bắt đầu đúng chỗ dòng $c, rơi vào ngày nghỉ thì nhảy qua
        $offset = $seq[$c]['start'] - $seq[$n1]['start'];   // âm
        $changes = [];
        $prevOccEnd = null;
        for ($k = $n1; $k <= $n2; $k++) {
            $newStart = $seq[$k]['start'] + $offset;
            if ($prevOccEnd !== null) {
                $newStart = max($newStart, $prevOccEnd);
            }
            $newStart = $this->skipOff($newStart);
            if ($seq[$k]['lock'] !== null || $newStart < $seq[$k]['ready'] || $newStart < $this->at || $newStart >= $seq[$k]['start']) {
                return null;
            }
            $changes[$seq[$k]['id']] = $newStart - $seq[$k]['start'];
            $prevOccEnd = $seq[$k]['occ_end'] + $changes[$seq[$k]['id']];
        }

        // Các dòng từ $c tới trước $n1 dời trễ ra sau khối vừa đưa lên
        $shift = $this->shiftPlan($seq, $c, $prevOccEnd - $seq[$c]['start'], $n1);
        if ($shift === null) {
            return null;
        }

        // Nhảy qua ngày nghỉ có thể làm các dòng đó dài ra, không được đè dòng ngay sau chỗ cũ của khối
        if ($n2 + 1 < count($seq)) {
            for ($j = $c; $j < $n1; $j++) {
                if ($seq[$j]['occ_end'] + ($shift[$seq[$j]['id']] ?? 0) > $seq[$n2 + 1]['start']) {
                    return null;
                }
            }
        }

        return $changes + $shift;
    }

    /**
     * Ghi phương án vào stage_plan, giữ nguyên độ dài và giờ vệ sinh tương đối.
     *
     * @param array<int, int> $changes [id => giây dời]
     */
    public function apply(array $changes, string $typeOfChange): void
    {
        if ($changes === []) {
            return;
        }

        $rows = DB::table('stage_plan')->whereIn('id', array_keys($changes))->get()->keyBy('id');
        $user = session('user.fullName');
        $fmt = fn($value, int $d) => $value ? date('Y-m-d H:i:s', strtotime($value) + $d) : null;

        foreach ($changes as $id => $d) {
            $row = $rows[$id] ?? null;
            if ($row === null || $d === 0) {
                continue;
            }

            DB::table('stage_plan')->where('id', $id)->update([
                'start'             => $fmt($row->start, $d),
                'end'               => $fmt($row->end, $d),
                'start_clearning'   => $fmt($row->start_clearning, $d),
                'end_clearning'     => $fmt($row->end_clearning, $d),
                'schedualed_by'     => $user,
                'schedualed_at'     => now(),
                'accept_quarantine' => 0,
            ]);

            // Ghi lịch sử như tịnh tuyến: lô đã submit hoặc đã có phiên bản
            if ((int) $row->submit === 1 || DB::table('stage_plan_history')->where('stage_plan_id', $id)->exists()) {
                \App\Support\StagePlanHistory::record(DB::table('stage_plan')->where('id', $id)->first(), $typeOfChange);
            }
        }
    }
}
