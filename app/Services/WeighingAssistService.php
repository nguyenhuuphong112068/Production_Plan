<?php

namespace App\Services;

use App\Support\LeadConfirmation;
use App\Support\StagePlanHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sắp lịch cân BÁN TỰ ĐỘNG trên Gantt (yêu cầu user 20/11/2026).
 *
 * Liên kết cân ↔ công đoạn sau đi theo nextcessor_code của DÒNG CÂN (không theo số công đoạn):
 *  - Cân NL (stage_code 1)      → PC (3), hoặc THT (4) khi lô không có PC.
 *  - Cân NL khác (stage_code 2) → ĐH (5) / BP (6), vài lô tới ĐG (7).
 * PC có predecessor_code trỏ về dòng cân, nhưng ĐH/BP thì không (predecessor là THT/ĐH), nên phải
 * dò ngược từ dòng cân: w.nextcessor_code = n.code.
 *
 * 1. status():  lô công đoạn sau còn công đoạn cân chưa xong → 'need' (còn dòng cân chưa có lịch)
 *               hoặc 'planned' (đã có lịch cân, chưa cân xong). Gantt dùng để ẩn lô không cần chuẩn bị cân.
 * 2. suggest(): với các lô công đoạn sau người dùng chọn, xếp từng dòng cân chưa có lịch vào phòng cân
 *               có giờ trống SỚM NHẤT (theo định mức cân của BTP), tránh ngày nghỉ, không ghi DB.
 *               Không cân sớm hơn MAX_DAYS_BEFORE (3) ngày trước ngày bắt đầu công đoạn sau. $merge = người dùng
 *               chọn ghép: mọi lô cùng BTP + loại cân đang chọn cân chung một khối; mốc 3 ngày tính theo lô cần
 *               dùng SỚM NHẤT, lô cần dùng muộn được cân sớm hơn 3 ngày (cảnh báo).
 * 3. apply():   ghi các khối người dùng đã duyệt (kiểm tra lại phòng còn trống ngay lúc ghi).
 */
class WeighingAssistService
{
    public const WEIGH_STAGES = [1, 2];

    /** Bước làm tròn giờ bắt đầu (phút), khớp lưới 15 phút của Gantt */
    private const STEP = 15 * 60;

    /** Không tìm quá số ngày này kể từ mốc sớm nhất */
    private const HORIZON_DAYS = 120;

    /** Cân sớm nhất N ngày (theo lịch) trước NGÀY bắt đầu công đoạn sau (yêu cầu user 25/11/2026) */
    public const MAX_DAYS_BEFORE = 3;

    private string $tz;

    /** @var array<int, array{0:int,1:int}> khoảng ngày nghỉ [06:00, 06:00 hôm sau), đã gộp, tăng dần */
    private array $offRanges = [];

    /** @var array<int, array<int, array{0:int,1:int}>> room id => khoảng bận (timestamp), tăng dần */
    private array $busy = [];

    /** @var array<int, object> */
    private array $rooms = [];

    public function __construct()
    {
        $this->tz = config('app.timezone');
    }

    /* ------------------------------------------------------------------
     | 1. Trạng thái "lô cần cân" cho chế độ tập trung trên Gantt
     * ------------------------------------------------------------------ */

    /**
     * @return array<int, string> id dòng công đoạn sau => 'need' | 'planned'
     */
    public function status(string $production): array
    {
        return DB::table('stage_plan as w')
            ->join('stage_plan as n', 'n.code', '=', 'w.nextcessor_code')
            ->whereIn('w.stage_code', self::WEIGH_STAGES)
            ->where('w.active', 1)
            ->where('w.finished', 0)
            ->where('w.deparment_code', $production)
            ->where('n.active', 1)
            ->where('n.finished', 0)
            ->whereNull('n.actual_start')
            ->whereNotNull('n.start')
            ->groupBy('n.id')
            ->select('n.id', DB::raw('SUM(w.start IS NULL) as unscheduled'))
            ->get()
            ->mapWithKeys(fn($r) => [(int) $r->id => ((int) $r->unscheduled > 0 ? 'need' : 'planned')])
            ->all();
    }

    /* ------------------------------------------------------------------
     | 2. Gợi ý lịch cân (không ghi DB)
     * ------------------------------------------------------------------ */

    /**
     * @param  int[]  $nextIds  id stage_plan của các lô công đoạn sau đang chọn trên Gantt
     * @param  array  $reserved  khối cân NHÁP chưa lưu trên Gantt [{ids, room_id, start, end_clearning}]:
     *                           lô trong đó không gợi ý lại, phòng bị giữ chỗ đúng khung đó
     * @param  bool  $merge  true = người dùng chọn GHÉP: mọi lô cùng BTP + cùng loại cân cân chung một khối
     *                       (không theo mã chiến dịch, không giới hạn số lô / ngày cần dùng — chỉ cảnh báo)
     * @return array{blocks: array, skipped: array, notes: array}
     */
    public function suggest(string $production, array $nextIds, array $reserved = [], bool $merge = false): array
    {
        $draftIds = [];
        foreach ($reserved as $r) {
            foreach ((array) ($r['ids'] ?? []) as $id) {
                $draftIds[(int) $id] = true;
            }
            try {
                $this->addBusy((int) ($r['room_id'] ?? 0),
                    Carbon::parse($r['start'], $this->tz)->getTimestamp(),
                    Carbon::parse($r['end_clearning'], $this->tz)->getTimestamp());
            } catch (\Throwable $e) {
                // khối nháp lỗi giờ: bỏ qua giữ chỗ
            }
        }

        $nextIds = array_values(array_unique(array_filter(array_map('intval', $nextIds))));

        $nexts = DB::table('stage_plan')
            ->whereIn('id', $nextIds ?: [0])
            ->where('deparment_code', $production)
            ->get(['id', 'code', 'stage_code', 'start', 'finished', 'actual_start']);

        $byCode = $nexts->keyBy('code');

        $weighs = $this->weighQuery($production)
            ->whereIn('w.nextcessor_code', $byCode->keys()->all() ?: ['-'])
            ->get();

        $skipped = [];
        $notes = [];

        $linked = $weighs->pluck('nextcessor_code')->unique();
        $noWeigh = $nexts->reject(fn($n) => $linked->contains($n->code))->count();
        if ($noWeigh > 0) {
            $notes[] = "{$noWeigh} lô đang chọn không có công đoạn cân liên kết (bỏ qua).";
        }

        $doneCount = $weighs->where('finished', 1)->count();
        if ($doneCount > 0) {
            $notes[] = "{$doneCount} công đoạn cân đã hoàn thành (bỏ qua).";
        }

        $roomCodes = DB::table('room')->pluck('code', 'id');

        $todo = collect();
        foreach ($weighs as $w) {
            if ((int) $w->finished === 1) {
                continue;
            }

            $next = $byCode->get($w->nextcessor_code);
            $w->next_id = (int) $next->id;
            $w->next_stage = (int) $next->stage_code;
            $w->next_start = $next->start;

            if (isset($draftIds[(int) $w->id])) {
                $skipped[] = ['title' => $w->title, 'reason' => 'Đang có khối cân nháp chưa lưu trên lịch'];
                continue;
            }
            if ($w->start !== null) {
                $skipped[] = [
                    'title' => $w->title,
                    'reason' => 'Đã có lịch cân ' . ($roomCodes[$w->resourceId] ?? '') . ' lúc '
                        . Carbon::parse($w->start)->format('H:i d/m/y'),
                ];
                continue;
            }
            if ((int) $next->finished === 1 || $next->actual_start !== null) {
                $skipped[] = ['title' => $w->title, 'reason' => 'Công đoạn sau đã chạy / hoàn thành'];
                continue;
            }
            if ($next->start === null) {
                $skipped[] = ['title' => $w->title, 'reason' => 'Công đoạn sau chưa có lịch'];
                continue;
            }
            if ((int) $w->not_schedule === 1) {
                $skipped[] = ['title' => $w->title, 'reason' => 'Lô đang đánh dấu "Không sắp lịch"'];
                continue;
            }

            $todo->push($w);
        }

        $blocks = [];

        if ($todo->isNotEmpty()) {
            $this->loadOffRanges();
            $now = $this->ceilStep(Carbon::now()->getTimestamp());

            // Mặc định: lô cùng chiến dịch + cùng loại cân + cùng BTP cân chung một khối (tối đa "Số lô tối đa"
            // của định mức, chỉ gom lô có ngày cần dùng gần nhau). Ghép: mọi lô đang chọn cùng BTP + loại cân.
            $groups = $todo
                ->groupBy(fn($w) => $merge
                    ? "m|{$w->stage_code}|{$w->intermediate_code}"
                    : ($w->campaign_code
                        ? "c|{$w->stage_code}|{$w->campaign_code}|{$w->intermediate_code}"
                        : "s|{$w->id}"))
                ->map(fn($g) => $g->sortBy(fn($w) => [$w->next_start, $w->batch])->values())
                ->sortBy(fn($g) => $g->first()->next_start); // lô công đoạn sau chạy sớm nhất xếp trước

            if ($merge) {
                $multi = $groups->filter(fn($g) => $g->count() > 1)->count();
                $single = $groups->count() - $multi;
                if ($groups->count() > 1) {
                    $notes[] = 'Chỉ ghép được lô cùng BTP và cùng loại cân: '
                        . ($multi ? "{$multi} khối ghép" : '') . ($multi && $single ? ', ' : '')
                        . ($single ? "{$single} lô cân riêng (khác BTP / loại cân với các lô khác)" : '') . '.';
                }
            }

            foreach ($groups as $key => $rows) {
                $mode = $key[0]; // m = ghép, c = chiến dịch có sẵn, s = lô lẻ

                while ($rows->isNotEmpty()) {
                    $first = $rows->first();
                    $quotas = $this->quotasFor($production, $first);

                    if ($quotas->isEmpty()) {
                        foreach ($rows as $w) {
                            $skipped[] = [
                                'title' => $w->title,
                                'reason' => 'Chưa có định mức ' . ($w->stage_code == 1 ? 'Cân NL' : 'Cân NL khác')
                                    . ($w->required_room_code ? " tại phòng chỉ định {$w->required_room_code}" : '')
                                    . ' cho BTP ' . $w->intermediate_code,
                            ];
                        }
                        break;
                    }

                    $best = null;
                    foreach ($quotas as $q) {
                        $chunk = match ($mode) {
                            'm' => $rows,
                            'c' => $this->campaignChunk($rows, max(1, (int) $q->maxofbatch_campaign)),
                            default => $rows->take(1),
                        };
                        $plan = $this->planChunk($q, $chunk, $now, $mode === 'm');

                        if ($plan && ($best === null
                            || $plan['start'] < $best['start']
                            || ($plan['start'] === $best['start'] && $chunk->count() > $best['chunk']->count())
                            || ($plan['start'] === $best['start'] && $chunk->count() === $best['chunk']->count() && $plan['end'] < $best['end']))) {
                            $best = $plan + ['chunk' => $chunk, 'quota' => $q];
                        }
                    }

                    if ($best === null) {
                        foreach ($rows as $w) {
                            $skipped[] = ['title' => $w->title, 'reason' => 'Không tìm được giờ trống trong ' . self::HORIZON_DAYS . ' ngày tới'];
                        }
                        break;
                    }

                    // Giữ chỗ để khối sau không chồng lên
                    $this->addBusy((int) $best['quota']->room_id, $best['start'], $best['end_clearning']);
                    $block = $this->blockOut($best, count($blocks) + 1);
                    $max = (int) $best['quota']->maxofbatch_campaign;
                    if ($mode === 'm' && $max > 0 && $best['chunk']->count() > $max) {
                        $block['warnings'][] = "Ghép {$best['chunk']->count()} lô, vượt Số lô tối đa / chiến dịch của định mức ({$max} lô).";
                    }
                    $blocks[] = $block;
                    $rows = $rows->slice($best['chunk']->count())->values();
                }
            }
        }

        return ['blocks' => $blocks, 'skipped' => $skipped, 'notes' => $notes];
    }

    /**
     * Lô đầu của chiến dịch + các lô kế tiếp (tối đa $size) mà cân chung vẫn kịp lô đầu: ngày cần dùng của lô
     * thêm vào trừ MAX_DAYS_BEFORE ngày không được muộn hơn giờ cần dùng của lô đầu.
     */
    private function campaignChunk(Collection $rows, int $size): Collection
    {
        $firstNeed = Carbon::parse($rows->first()->next_start, $this->tz)->getTimestamp();
        $chunk = collect();
        foreach ($rows as $w) {
            if ($chunk->count() >= $size || ($chunk->isNotEmpty() && $this->earliestFor($w) > $firstNeed)) {
                break;
            }
            $chunk->push($w);
        }

        return $chunk;
    }

    /**
     * Mốc cân sớm nhất theo ngày cần dùng: 06:00 của (ngày sản xuất bắt đầu công đoạn sau − MAX_DAYS_BEFORE ngày).
     * Ngày sản xuất tính từ 06:00 như ngày nghỉ (CĐ sau bắt đầu 03:00 ngày 04 thuộc ngày 03).
     */
    private function earliestFor(object $w): int
    {
        return Carbon::parse($w->next_start, $this->tz)->subHours(6)->startOfDay()->addHours(6)
            ->subDays(self::MAX_DAYS_BEFORE)->getTimestamp();
    }

    /**
     * Giờ sớm nhất của một khối cân tại một phòng.
     *
     * @return array{start:int,end:int,end_clearning:int,clearning_type:string}|null
     */
    private function planChunk(object $q, Collection $chunk, int $now, bool $merge = false): ?array
    {
        // Khối GHÉP: mốc 3 ngày theo lô cần dùng SỚM NHẤT (lô cần dùng muộn được cân sớm hơn 3 ngày, chỉ cảnh báo —
        // yêu cầu user 25/11/2026). Khối chiến dịch: theo lô cần dùng muộn nhất (campaignChunk đã tách để vẫn kịp lô đầu).
        $anchorId = $merge ? (int) $chunk->sortBy('next_start')->first()->id : null;
        $n = $chunk->count();
        // Lô đơn = p + m; mỗi lô cân chung thêm (Hệ số chiến dịch − 1) × m
        $factor = $n <= 1 ? 1 : 1 + (max(1.0, (float) $q->campaign_index) - 1) * ($n - 1);
        $dur = (int) round((float) $q->p_minutes + (float) $q->m_minutes * $factor) * 60;
        $c2 = (float) $q->c2_minutes;
        $clean = (int) round($c2 > 0 ? $c2 : (float) $q->c1_minutes) * 60;

        $earliest = $now;
        foreach ($chunk as $w) {
            // Không cân sớm hơn MAX_DAYS_BEFORE ngày trước ngày cần dùng (khối ghép: chỉ theo lô cần dùng sớm nhất)
            if ($anchorId === null || (int) $w->id === $anchorId) {
                $earliest = max($earliest, $this->earliestFor($w));
            }
            foreach (['after_weigth_date', 'allow_weight_before_date'] as $f) {
                if (! empty($w->$f)) {
                    $earliest = max($earliest, Carbon::parse($w->$f, $this->tz)->startOfDay()->getTimestamp());
                }
            }
            // Không cân sớm quá thời gian biệt trữ sau cân (BTP) tính tới lúc công đoạn sau bắt đầu
            if ($w->hold_hours > 0) {
                $earliest = max($earliest, Carbon::parse($w->next_start, $this->tz)->getTimestamp() - (int) round($w->hold_hours * 3600));
            }
        }

        $slot = $this->findSlot($this->room((int) $q->room_id), $this->ceilStep($earliest), $dur, $clean);

        return $slot ? $slot + ['clearning_type' => $c2 > 0 ? 'VS-II' : 'VS-I'] : null;
    }

    private function blockOut(array $best, int $no): array
    {
        $q = $best['quota'];
        $room = $this->room((int) $q->room_id);
        $fmt = fn(int $ts) => Carbon::createFromTimestamp($ts, $this->tz)->format('Y-m-d H:i:s');

        $warnings = [];
        $lots = [];
        foreach ($best['chunk'] as $w) {
            $nextTs = Carbon::parse($w->next_start, $this->tz)->getTimestamp();
            $late = $best['end'] > $nextTs ? (int) ceil(($best['end'] - $nextTs) / 60) : 0;
            $lots[] = [
                'id' => (int) $w->id,
                'code' => $w->code,
                'plan_master_id' => (int) $w->plan_master_id,
                'product_name' => $w->product_name,
                'batch' => $w->batch,
                'title' => $w->title,
                'next_id' => $w->next_id,
                'next_stage' => $w->next_stage,
                'next_start' => $w->next_start,
                'late_minutes' => $late,
            ];
            if (! empty($w->expired_material_date)
                && Carbon::createFromTimestamp($best['start'], $this->tz)->toDateString() > $w->expired_material_date) {
                $warnings[] = "{$w->title}: bắt đầu cân sau Ngày HH NL chính " . Carbon::parse($w->expired_material_date)->format('d/m/y');
            }
            if ($best['start'] < $this->earliestFor($w)) {
                $warnings[] = "{$w->title}: cân sớm hơn " . self::MAX_DAYS_BEFORE . ' ngày trước ngày cần dùng ('
                    . Carbon::parse($w->next_start)->format('H:i d/m/y') . ')';
            }
        }

        return [
            'key' => 'b' . $no,
            'stage_code' => (int) $best['chunk']->first()->stage_code,
            'campaign_code' => $best['chunk']->first()->campaign_code,
            'intermediate_code' => $best['chunk']->first()->intermediate_code,
            'room_id' => (int) $q->room_id,
            'room' => trim($room->code . ' - ' . $room->name),
            'start' => $fmt($best['start']),
            'end' => $fmt($best['end']),
            'start_clearning' => $fmt($best['end']),
            'end_clearning' => $fmt($best['end_clearning']),
            'clearning_type' => $best['clearning_type'],
            'lots' => $lots,
            'warnings' => $warnings,
        ];
    }

    /* ------------------------------------------------------------------
     | 3. Ghi các khối đã duyệt
     * ------------------------------------------------------------------ */

    /**
     * @param  array<int, array>  $blocks  khối do suggest() trả về (người dùng có thể bỏ bớt khối)
     * @return array{saved_blocks:int, saved_lots:int, problems: string[]}
     */
    public function apply(string $production, array $blocks): array
    {
        $problems = [];
        $savedBlocks = 0;
        $savedLots = 0;
        $occupancy = app(RoomOccupancyService::class);

        DB::transaction(function () use ($production, $blocks, $occupancy, &$problems, &$savedBlocks, &$savedLots) {
            foreach ($blocks as $b) {
                $roomId = (int) ($b['room_id'] ?? 0);
                $ids = array_values(array_filter(array_map('intval', (array) ($b['ids'] ?? []))));
                $room = DB::table('room')->where('id', $roomId)->where('deparment_code', $production)->first();

                try {
                    $start = Carbon::parse($b['start']);
                    $end = Carbon::parse($b['end']);
                    $startClean = Carbon::parse($b['start_clearning'] ?? $b['end']);
                    $endClean = Carbon::parse($b['end_clearning']);
                } catch (\Throwable $e) {
                    $problems[] = 'Khối có giờ không hợp lệ, bỏ qua.';
                    continue;
                }

                if (! $room || ! $ids || $start->gte($end) || $startClean->lt($end) || $startClean->gt($endClean)) {
                    $problems[] = 'Khối ' . ($room->code ?? $roomId) . ' không hợp lệ, bỏ qua.';
                    continue;
                }

                $rows = $this->weighQuery($production)
                    ->whereIn('w.id', $ids)
                    ->get();

                $valid = $rows->filter(fn($w) => (int) $w->finished === 0 && $w->start === null);
                foreach ($rows->diffKeys($valid) as $w) {
                    $problems[] = "{$w->title}: vừa được xếp lịch / hoàn thành ở nơi khác, bỏ qua.";
                }
                // Phòng phải có định mức cân của BTP
                $valid = $valid->filter(function ($w) use ($production, $roomId, &$problems) {
                    $ok = $this->quotasFor($production, $w)->contains(fn($q) => (int) $q->room_id === $roomId);
                    if (! $ok) {
                        $problems[] = "{$w->title}: phòng không có định mức cân, bỏ qua.";
                    }
                    return $ok;
                });
                if ($valid->isEmpty()) {
                    continue;
                }

                // Phòng còn trống đúng khung giờ này? (người khác có thể vừa xếp)
                $clash = DB::table('stage_plan')
                    ->where('resourceId', $roomId)
                    ->where('active', 1)
                    ->where('finished', 0)
                    ->whereNotNull('start')
                    ->whereNotIn('id', $ids)
                    ->where('start', '<', $endClean)
                    ->whereRaw('GREATEST(`end`, COALESCE(end_clearning, `end`)) > ?', [$start])
                    ->value('title');
                $held = $occupancy->heldUntil([$roomId])[$roomId] ?? null;
                if ($clash || ($held && $held[0]->lt($endClean) && $held[1]->gt($start))) {
                    $problems[] = "Phòng {$room->code} lúc " . $start->format('H:i d/m') . ' vừa có lịch khác ('
                        . ($clash ?: 'lô đang giữ phòng') . '), bấm Gợi ý lại.';
                    continue;
                }

                foreach ($valid as $w) {
                    DB::table('stage_plan')->where('id', $w->id)->update([
                        'resourceId' => $roomId,
                        'start' => $start,
                        'end' => $end,
                        'start_clearning' => $startClean,
                        'end_clearning' => $endClean,
                        'title' => $w->title,
                        'title_clearning' => ($b['clearning_type'] ?? '') === 'VS-I' ? 'VS-I' : 'VS-II',
                        'first_in_campaign' => 1, // khối cân đã gồm thời gian chuẩn bị
                        'AHU_group' => $room->AHU_group ?? 0,
                        'schedualed' => 1,
                        'schedualed_by' => session('user')['fullName'] ?? null,
                        'schedualed_at' => now(),
                    ]);

                    $row = DB::table('stage_plan')->where('id', $w->id)->first();
                    if ((int) $row->submit === 1) {
                        StagePlanHistory::record($row, 'Gợi ý lịch cân');
                    }
                    DB::table('stage_plan')->where('id', $w->id)->update(['submit' => 0]);
                    $savedLots++;
                }

                LeadConfirmation::reset($valid->pluck('id')->all());
                $savedBlocks++;
            }
        });

        return ['saved_blocks' => $savedBlocks, 'saved_lots' => $savedLots, 'problems' => $problems];
    }

    /* ------------------------------------------------------------------
     | Dữ liệu
     * ------------------------------------------------------------------ */

    /** Dòng cân kèm mốc NL, BTP, tên (tên theo BTP như danh sách chờ sắp) */
    private function weighQuery(string $production)
    {
        return DB::table('stage_plan as w')
            ->leftJoin('plan_master as pm', 'pm.id', '=', 'w.plan_master_id')
            ->leftJoin('finished_product_category as fpc', 'fpc.id', '=', 'w.product_caterogy_id')
            ->leftJoin('intermediate_category as ic', 'ic.intermediate_code', '=', 'fpc.intermediate_code')
            ->leftJoin('product_name as pn', 'pn.id', '=', 'ic.product_name_id')
            ->whereIn('w.stage_code', self::WEIGH_STAGES)
            ->where('w.active', 1)
            ->where('w.deparment_code', $production)
            ->select(
                'w.id',
                'w.code',
                'w.plan_master_id',
                'w.stage_code',
                'w.campaign_code',
                'w.nextcessor_code',
                'w.start',
                'w.resourceId',
                'w.finished',
                'w.not_schedule',
                'w.required_room_code',
                'pm.after_weigth_date',
                'pm.allow_weight_before_date',
                'pm.expired_material_date',
                'fpc.intermediate_code',
                DB::raw('COALESCE(pm.actual_batch, pm.batch) as batch'),
                DB::raw('COALESCE(pn.name, fpc.intermediate_code) as product_name'),
                DB::raw("CONCAT(COALESCE(pn.name, fpc.intermediate_code), '-', COALESCE(pm.actual_batch, pm.batch)) as title"),
                // Biệt trữ sau Cân NL của BTP (giờ); Cân NL khác không có khai báo riêng
                DB::raw('CASE WHEN w.stage_code = 1 AND ic.quarantine_weight > 0
                              THEN ic.quarantine_weight * (CASE WHEN ic.quarantine_time_unit = 1 THEN 24 ELSE 1 END)
                              ELSE 0 END as hold_hours')
            )
            ->orderBy('w.id');
    }

    /** Định mức cân (phòng cân đang hoạt động của phân xưởng) cho BTP + loại cân; tôn trọng phòng chỉ định */
    private function quotasFor(string $production, object $w): Collection
    {
        return DB::table('quota as q')
            ->join('room as r', 'r.id', '=', 'q.room_id')
            ->where('q.intermediate_code', $w->intermediate_code)
            ->where('q.stage_code', $w->stage_code)
            ->where('q.finished_product_code', 'NA')
            ->where('q.active', 1)
            ->where('r.active', 1)
            ->where('r.deparment_code', $production)
            ->when($w->required_room_code, fn($q) => $q->where('r.code', $w->required_room_code))
            ->orderBy('r.order_by')
            ->select(
                'q.room_id',
                'q.campaign_index',
                'q.maxofbatch_campaign',
                DB::raw('TIME_TO_SEC(q.p_time)/60 as p_minutes'),
                DB::raw('TIME_TO_SEC(q.m_time)/60 as m_minutes'),
                DB::raw('TIME_TO_SEC(q.C1_time)/60 as c1_minutes'),
                DB::raw('TIME_TO_SEC(q.C2_time)/60 as c2_minutes')
            )
            ->get()
            ->unique('room_id')
            ->values();
    }

    private function room(int $id): object
    {
        return $this->rooms[$id] ??= DB::table('room')->where('id', $id)
            ->first(['id', 'code', 'name', 'sheet_regular', 'sheet_1', 'sheet_2', 'sheet_3']);
    }

    /** Ngày nghỉ (off_days, đồng bộ eO2 + mọi Chủ nhật) thành khoảng 06:00 → 06:00 hôm sau, gộp ngày liền nhau */
    private function loadOffRanges(): void
    {
        $dates = DB::table('off_days')
            ->where('off_date', '>=', Carbon::today()->subDay()->toDateString())
            ->orderBy('off_date')
            ->pluck('off_date');

        $ranges = [];
        foreach ($dates as $d) {
            $s = Carbon::parse($d, $this->tz)->setTime(6, 0)->getTimestamp();
            $e = $s + 86400;
            if ($ranges && $ranges[count($ranges) - 1][1] >= $s) {
                $ranges[count($ranges) - 1][1] = max($ranges[count($ranges) - 1][1], $e);
            } else {
                $ranges[] = [$s, $e];
            }
        }
        $this->offRanges = $ranges;
    }

    /** Khoảng bận của phòng: lịch chưa hoàn thành (kể cả BT-HC-TI) + lô đang giữ phòng */
    private function busyOf(int $roomId): array
    {
        if (isset($this->busy[$roomId])) {
            return $this->busy[$roomId];
        }

        $now = Carbon::now();
        $list = DB::table('stage_plan')
            ->where('resourceId', $roomId)
            ->where('active', 1)
            ->where('finished', 0)
            ->whereNotNull('start')
            ->whereRaw('GREATEST(`end`, COALESCE(end_clearning, `end`)) > ?', [$now])
            ->selectRaw('start, GREATEST(`end`, COALESCE(end_clearning, `end`)) as stop')
            ->get()
            ->map(fn($r) => [Carbon::parse($r->start, $this->tz)->getTimestamp(), Carbon::parse($r->stop, $this->tz)->getTimestamp()])
            ->all();

        $held = app(RoomOccupancyService::class)->heldUntil([$roomId])[$roomId] ?? null;
        if ($held) {
            $list[] = [$held[0]->getTimestamp(), max($held[1]->getTimestamp(), $now->getTimestamp())];
        }

        usort($list, fn($a, $b) => $a[0] <=> $b[0]);

        return $this->busy[$roomId] = $list;
    }

    private function addBusy(int $roomId, int $start, int $stop): void
    {
        $list = $this->busyOf($roomId);
        $list[] = [$start, $stop];
        usort($list, fn($a, $b) => $a[0] <=> $b[0]);
        $this->busy[$roomId] = $list;
    }

    /* ------------------------------------------------------------------
     | Tìm giờ trống
     * ------------------------------------------------------------------ */

    /**
     * Giờ bắt đầu sớm nhất >= $from sao cho cả khối cân + vệ sinh nằm trong ca làm việc của phòng,
     * không chạm ngày nghỉ và không chồng lịch khác của phòng.
     *
     * @return array{start:int,end:int,end_clearning:int}|null
     */
    private function findSlot(object $room, int $from, int $dur, int $clean): ?array
    {
        $windows = $this->shiftWindows($room);
        $limit = $from + self::HORIZON_DAYS * 86400;
        $busy = $this->busyOf((int) $room->id);
        $t = $from;

        for ($i = 0; $i < 5000 && $t < $limit; $i++) {
            $t = $this->ceilStep($this->alignToShift($t, $windows));

            $end = $this->walk($t, $dur, $windows);
            $stop = $this->walk($end, $clean, $windows);

            // Khối không được chạm ngày nghỉ: dời tới hết ngày nghỉ
            $off = $this->firstOverlap($this->offRanges, $t, $stop);
            if ($off) {
                $t = $off[1];
                continue;
            }

            $hit = $this->firstOverlap($busy, $t, $stop);
            if ($hit) {
                $t = $hit[1];
                continue;
            }

            return ['start' => $t, 'end' => $end, 'end_clearning' => $stop];
        }

        return null;
    }

    /** Ca làm việc của phòng, phút tính từ 00:00 (có thể > 1440 = qua ngày), giống addWorkingMinutes */
    private function shiftWindows(object $room): array
    {
        if ((int) $room->sheet_regular === 1) {
            return [[7 * 60, 16 * 60]];
        }

        $shifts = [];
        if ((int) $room->sheet_1 === 1) $shifts[] = [6 * 60, 14 * 60];
        if ((int) $room->sheet_2 === 1) $shifts[] = [14 * 60, 22 * 60];
        if ((int) $room->sheet_3 === 1) $shifts[] = [22 * 60, 30 * 60];
        if (! $shifts) {
            return [[6 * 60, 30 * 60]];
        }

        $merged = [];
        foreach ($shifts as [$a, $b]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $merged[$last][1] >= $a) {
                $merged[$last][1] = max($merged[$last][1], $b);
            } else {
                $merged[] = [$a, $b];
            }
        }

        // 3 ca liền = chạy suốt ngày đêm
        if (count($merged) === 1 && $merged[0][1] - $merged[0][0] >= 1440) {
            return [];
        }

        return $merged;
    }

    /** Khung ca chứa $t hoặc khung ca kế tiếp: [bắt đầu, kết thúc] (timestamp) */
    private function windowAt(int $t, array $windows): array
    {
        $day = Carbon::createFromTimestamp($t, $this->tz)->startOfDay()->subDay();
        for ($d = 0; $d < 4; $d++, $day->addDay()) {
            $base = $day->getTimestamp();
            foreach ($windows as [$a, $b]) {
                $ws = $base + $a * 60;
                $we = $base + $b * 60;
                if ($we > $t) {
                    return [$ws, $we];
                }
            }
        }

        return [$t, $t + 86400];
    }

    private function alignToShift(int $t, array $windows): int
    {
        if (! $windows) {
            return $t;
        }
        [$ws] = $this->windowAt($t, $windows);

        return max($t, $ws);
    }

    /** Cộng $seconds thời gian làm việc từ $t theo ca của phòng (ngoài ca thì tạm dừng) */
    private function walk(int $t, int $seconds, array $windows): int
    {
        if (! $windows) {
            return $t + $seconds;
        }

        $remain = $seconds;
        for ($i = 0; $remain > 0 && $i < 400; $i++) {
            [$ws, $we] = $this->windowAt($t, $windows);
            $t = max($t, $ws);
            $take = min($remain, $we - $t);
            $t += $take;
            $remain -= $take;
        }

        return $t;
    }

    /** Khoảng đầu tiên (danh sách tăng dần) giao với [$s, $e) */
    private function firstOverlap(array $ranges, int $s, int $e): ?array
    {
        foreach ($ranges as $r) {
            if ($r[0] >= $e) {
                break;
            }
            if ($r[1] > $s) {
                return $r;
            }
        }

        return null;
    }

    private function ceilStep(int $ts): int
    {
        return (int) (ceil($ts / self::STEP) * self::STEP);
    }
}
