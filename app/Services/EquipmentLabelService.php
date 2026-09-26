<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Nhãn hiệu chuẩn / bảo trì / tiện ích của thiết bị trong từng phòng sản xuất (trang Thực Thi Sản Xuất).
 *
 * - Thiết bị thuộc phòng nào: theo danh mục Bảo trì hiệu chuẩn (quota_maintenance + quota_maintenance_rooms).
 * - Tình trạng: đọc CSDL hiệu chuẩn (cal1 = khối B1, cal2 = khối B2), bảng Inst_Master_{1,2,3} / Schedule_Master_{1,2,3}
 *   (1 Hiệu chuẩn, 2 Bảo trì, 3 Tiện ích), cùng quy tắc với nhãn bên eBMR:
 *     HC: hạn = Next_cal_date của lịch gần nhất có Next_cal_date; quá hạn khi hạn < hôm nay.
 *     BT/TI: mỗi lịch Pending, hạn = Sch_DueDate; quá hạn tới 7 ngày (Monthly) / 21 ngày (chu kỳ khác) là "Đến hạn",
 *            quá hơn là "Không đạt"; nhãn của thiết bị lấy theo chu kỳ dài nhất đang chờ.
 * - Kết quả cả phân xưởng được cache vài phút vì CSDL hiệu chuẩn nằm ở máy chủ khác.
 */
class EquipmentLabelService
{
    const TYPES = [
        1 => ['key' => 'HC', 'label' => 'Hiệu chuẩn'],
        2 => ['key' => 'BT', 'label' => 'Bảo trì'],
        3 => ['key' => 'TI', 'label' => 'Tiện ích'],
    ];

    const CYCLE_PRIORITY = ['yearly' => 5, 'half yearly' => 4, 'quaterly' => 3, 'monthly' => 2, 'weekly' => 1, 'daily' => 0];

    const CACHE_MINUTES = 10;

    // SQL Server giới hạn 2100 tham số mỗi câu lệnh
    const CHUNK = 1000;

    /**
     * Nhãn thiết bị của mọi phòng sản xuất (công đoạn 1-7) trong phân xưởng.
     *
     * @return array{rooms: array<int, array>, errors: string[], generated_at: string}
     */
    public function forDepartment(string $deparmentCode, bool $refresh = false): array
    {
        $key = "execution.equipment_labels.$deparmentCode";
        if (!$refresh && ($cached = Cache::get($key))) {
            return $cached;
        }

        $data = $this->build($deparmentCode);
        // Lỗi kết nối CSDL hiệu chuẩn thì chỉ giữ 1 phút để lần sau thử lại sớm
        Cache::put($key, $data, now()->addMinutes($data['errors'] ? 1 : self::CACHE_MINUTES));

        return $data;
    }

    /**
     * Tóm tắt cho card phòng (không kèm danh sách dụng cụ).
     * equipments: mỗi thiết bị lớn 1 dòng {code, name, level xấu nhất, levels theo loại}; thiết bị có vấn đề lên đầu,
     * rồi tới máy móc, sau cùng là thiết bị chỉ có Tiện ích (AHU, quạt hút...).
     */
    public function summaries(array $data): array
    {
        return collect($data['rooms'])->map(fn($room) => [
            'count'      => count($room['equipments']),
            'equipments' => collect($room['equipments'])
                ->map(function ($e) {
                    $levels = collect(self::TYPES)->mapWithKeys(fn($t) => [$t['key'] => $e[$t['key']]['level'] ?? null])
                        ->filter(fn($l) => $l !== null);
                    return [
                        'code'   => $e['code'],
                        'name'   => $e['name'],
                        'level'  => $levels->max(),
                        'levels' => $levels->all(),
                    ];
                })
                ->sortBy([
                    fn($a, $b) => $b['level'] <=> $a['level'],
                    fn($a, $b) => (array_keys($a['levels']) === ['TI']) <=> (array_keys($b['levels']) === ['TI']),
                    fn($a, $b) => strcmp($a['code'], $b['code']),
                ])
                ->values()
                ->all(),
            'issues'     => collect($room['equipments'])
                ->flatMap(fn($e) => collect(self::TYPES)->map(fn($t) => $e[$t['key']] ?? null)
                    ->filter(fn($l) => $l && $l['level'] > 0)
                    ->map(fn($l, $s) => $e['code'] . ' (' . self::TYPES[$s]['key'] . ': ' . $l['text'] . ')'))
                ->values()
                ->all(),
        ])->all();
    }

    private function build(string $deparmentCode): array
    {
        $today = now()->startOfDay();
        $errors = [];

        $links = DB::table('quota_maintenance_rooms as qr')
            ->join('room as r', 'r.id', '=', 'qr.room_id')
            ->join('quota_maintenance as q', 'q.id', '=', 'qr.quota_maintenance_id')
            ->where('r.deparment_code', $deparmentCode)
            ->where('r.active', 1)
            ->whereBetween('r.stage_code', [1, 7])
            ->where('q.active', 1)
            ->select('qr.room_id', 'q.inst_id', 'q.inst_name', 'q.parent_eqp_id', 'q.Eqp_name', 'q.block')
            ->distinct()
            ->get()
            ->map(function ($l) {
                // block dạng HC-B1, BT-B2, TI-B1: loại + khối (B1 → cal1, B2 → cal2)
                [$typeKey, $blockKey] = array_pad(explode('-', (string) $l->block), 2, 'B1');
                $l->suffix = ['HC' => 1, 'BT' => 2, 'TI' => 3][$typeKey] ?? null;
                $l->conn = $blockKey === 'B2' ? 'cal2' : 'cal1';
                return $l;
            })
            ->filter(fn($l) => $l->suffix);

        // Tình trạng từng dụng cụ, đọc theo từng (kết nối, loại)
        $status = [];
        foreach ($links->groupBy(fn($l) => $l->conn . '|' . $l->suffix) as $group => $rows) {
            [$conn, $suffix] = explode('|', $group);
            try {
                $status[$group] = $this->instrumentStatus($conn, (int) $suffix, $rows->pluck('inst_id')->unique()->values()->all(), $today);
            } catch (\Throwable $e) {
                Log::warning("[EquipmentLabel] Không đọc được $conn Schedule_Master_$suffix: " . $e->getMessage());
                $errors[] = 'Không kết nối được CSDL ' . self::TYPES[$suffix]['label'] . ' khối ' . ($conn === 'cal1' ? 'B1' : 'B2');
                $status[$group] = null;
            }
        }

        $rooms = [];
        foreach ($links->groupBy('room_id') as $roomId => $roomLinks) {
            $equipments = [];

            foreach ($roomLinks as $l) {
                $st = $status[$l->conn . '|' . $l->suffix] ?? null;
                if ($st === null || !isset($st[$l->inst_id])) {
                    continue; // lỗi kết nối, hoặc dụng cụ không còn Active bên CSDL hiệu chuẩn
                }

                $code = $l->parent_eqp_id ?: $l->inst_id;
                $typeKey = self::TYPES[$l->suffix]['key'];
                $equipments[$code] ??= ['code' => $code, 'name' => null, 'names' => []];
                $equipments[$code]['names'][$l->suffix] ??= $l->Eqp_name ?: $l->inst_name;
                $equipments[$code][$typeKey]['items'][$l->inst_id] = $st[$l->inst_id];
            }

            foreach ($equipments as &$e) {
                // Tên thiết bị: ưu tiên tên bên Bảo trì (chính thiết bị), rồi Tiện ích, Hiệu chuẩn
                $e['name'] = $e['names'][2] ?? $e['names'][3] ?? $e['names'][1] ?? $e['code'];
                unset($e['names']);

                foreach (self::TYPES as $suffix => $t) {
                    if (!isset($e[$t['key']])) {
                        continue;
                    }
                    $items = array_values($e[$t['key']]['items']);
                    $e[$t['key']] = $suffix === 1 ? $this->calibrationLevel($items) : $this->maintenanceLevel($items);
                }
            }
            unset($e);

            ksort($equipments);
            $rooms[$roomId] = ['equipments' => array_values($equipments)];
        }

        return [
            'rooms'        => $rooms,
            'errors'       => array_values(array_unique($errors)),
            'generated_at' => now()->format('H:i d/m/Y'),
        ];
    }

    /**
     * Tình trạng từng dụng cụ Active của 1 CSDL + loại.
     * HC: 1 dòng/dụng cụ. BT/TI: mỗi lịch Pending là 1 dòng (1 dụng cụ có nhiều chu kỳ).
     *
     * @return array<string, array> inst_id => dữ liệu nhãn
     */
    private function instrumentStatus(string $conn, int $suffix, array $instIds, Carbon $today): array
    {
        $db = DB::connection($conn);
        $result = [];

        foreach (array_chunk($instIds, self::CHUNK) as $ids) {
            $masters = $db->table("Inst_Master_$suffix")
                ->whereIn('Inst_id', $ids)
                ->where('Inst_Status', 'Active')
                ->pluck('Inst_Name', 'Inst_id');

            if ($masters->isEmpty()) {
                continue;
            }
            $activeIds = $masters->keys()->all();

            // Lần Pass gần nhất (HC: theo dụng cụ; BT/TI: theo dụng cụ + chu kỳ)
            $lastPass = $this->latestRows($db, $suffix, $activeIds, $suffix === 1 ? 'Inst_ID' : 'Inst_ID, Sch_Type',
                fn($q) => $q->where('Sch_Result_Status', 'Pass'))
                ->keyBy(fn($r) => $suffix === 1 ? $r->Inst_ID : $r->Inst_ID . '|' . strtolower(trim((string) $r->Sch_Type)));

            if ($suffix === 1) {
                $latest = $this->latestRows($db, $suffix, $activeIds, 'Inst_ID', fn($q) => $q->whereNotNull('Next_cal_date'))
                    ->keyBy('Inst_ID');

                foreach ($masters as $id => $name) {
                    $s = $latest->get($id);
                    $pass = $lastPass->get($id);
                    $exp = $s && $s->Next_cal_date ? Carbon::parse($s->Next_cal_date)->startOfDay() : null;
                    $result[$id] = [
                        'id'       => $id,
                        'name'     => $name,
                        'done_on'  => $this->date($pass->Sch_CalDone_On ?? $s->Sch_CalDone_On ?? null),
                        'due'      => $exp ? $exp->format('d/m/Y') : null,
                        'level'    => $exp && $exp->lt($today) ? 2 : 0,
                    ];
                }
                continue;
            }

            $pending = $db->table("Schedule_Master_$suffix")
                ->whereIn('Inst_ID', $activeIds)
                ->where('Sch_Result_Status', 'Pending')
                ->orderBy('Sch_DueDate')
                ->get(['Inst_ID', 'Sch_Type', 'Sch_DueDate', 'Next_cal_date', 'Sch_CalDone_On']);

            foreach ($masters as $id => $name) {
                $result[$id] = ['id' => $id, 'name' => $name, 'schedules' => []];
            }

            foreach ($pending as $p) {
                $dueRaw = $p->Sch_DueDate ?: $p->Next_cal_date;
                if (!$dueRaw) {
                    continue;
                }
                $due = Carbon::parse($dueRaw)->startOfDay();
                $cycle = trim((string) $p->Sch_Type);
                $grace = strtolower($cycle) === 'monthly' ? 7 : 21;
                $pass = $lastPass->get($p->Inst_ID . '|' . strtolower($cycle));

                $result[$p->Inst_ID]['schedules'][] = [
                    'cycle'   => $cycle,
                    'done_on' => $this->date($pass->Sch_CalDone_On ?? $p->Sch_CalDone_On ?? null),
                    'due'     => $due->format('d/m/Y'),
                    'level'   => $today->gt($due) ? ($today->lte($due->copy()->addDays($grace)) ? 1 : 2) : 0,
                ];
            }
        }

        return $result;
    }

    /**
     * Dòng Schedule_Master mới nhất (SCH_ID lớn nhất) theo nhóm cột $partition.
     */
    private function latestRows($db, int $suffix, array $ids, string $partition, callable $where): Collection
    {
        $inner = $db->table("Schedule_Master_$suffix")
            ->whereIn('Inst_ID', $ids)
            ->where($where)
            ->select('Inst_ID', 'Sch_Type', 'Next_cal_date', 'Sch_CalDone_On',
                DB::raw("ROW_NUMBER() OVER (PARTITION BY $partition ORDER BY SCH_ID DESC) AS rn"));

        return $db->query()->fromSub($inner, 't')->where('t.rn', 1)->get();
    }

    /** HC: không đạt nếu có dụng cụ quá hạn */
    private function calibrationLevel(array $items): array
    {
        $level = collect($items)->max('level') ?? 0;

        return ['items' => $items, 'level' => $level, 'text' => $level ? 'Không đạt' : 'Đạt'];
    }

    /**
     * BT/TI: mức cảnh báo của chu kỳ dài nhất đang chờ (giống eBMR), mỗi lịch Pending thành 1 dòng nhãn.
     */
    private function maintenanceLevel(array $items): array
    {
        $rows = [];
        $maxPriority = -1;
        $level = 0;

        foreach ($items as $item) {
            foreach ($item['schedules'] as $s) {
                $rows[] = ['id' => $item['id'], 'name' => $item['name']] + $s;

                $priority = self::CYCLE_PRIORITY[strtolower($s['cycle'])] ?? 0;
                if ($priority > $maxPriority) {
                    $maxPriority = $priority;
                    $level = $s['level'];
                } elseif ($priority === $maxPriority) {
                    $level = max($level, $s['level']);
                }
            }
        }

        return ['items' => $rows, 'level' => $level, 'text' => [0 => 'Đạt', 1 => 'Đến hạn', 2 => 'Không đạt'][$level]];
    }

    private function date($value): ?string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : null;
    }
}
