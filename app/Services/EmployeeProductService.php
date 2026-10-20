<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Khai báo sản phẩm nhân viên vận hành được phép thực hiện (bảng employee_products)
 * và liên kết ô "Hoạt động" của Lịch công tác sản xuất với stage_plan (bảng assignment_activities).
 *
 * Quy ước:
 * - BTP (intermediate_code) áp cho công đoạn 1-6, TP (finished_product_code) áp cho công đoạn 7.
 * - Phòng được làm một sản phẩm KHÔNG lưu mà suy ra = phòng của NV (employee_assignments.room_id, active)
 *   ∩ phòng định mức của sản phẩm (quota.room_id, active).
 * - Xét mọi NV (từ 15/10/2026): NV xếp vào lô mà chưa khai báo SP của lô thì bị cảnh báo, kể cả NV chưa khai báo gì.
 * - Chỉ cảnh báo trên Lịch công tác, không chặn xếp lịch. Không áp dụng cho Lịch công tác Bảo Trì.
 */
class EmployeeProductService
{
    public const TYPE_BTP = 'BTP';
    public const TYPE_TP = 'TP';

    public const TYPES = [
        self::TYPE_BTP => 'Bán thành phẩm (CĐ 1-6)',
        self::TYPE_TP => 'Thành phẩm (CĐ 7)',
    ];

    public const KIND_PRODUCTION = 'SX';
    public const KIND_CLEANING = 'VS';

    /**
     * Tên sản phẩm của lô hiển thị ở Lịch công tác: CĐ 1-6 theo BTP (pn = qua intermediate_category),
     * CĐ 7 (Đóng gói) theo thành phẩm (pn_tp = fpc.product_name_id) vì đóng gói xét theo mã TP.
     * Truy vấn dùng phải join stage_plan as sp, product_name as pn và product_name as pn_tp.
     */
    private const NAME_SQL = 'CASE WHEN sp.stage_code = 7 THEN COALESCE(pn_tp.name, pn.name) ELSE COALESCE(pn.name, pn_tp.name) END';

    // Mã tổ của phân xưởng sản xuất (employee_assignments.group_id), giống trang Danh sách nhân viên
    public const PROD_GROUPS = [
        1 => 'Trung Tâm Cân',
        3 => 'Pha Chế',
        4 => 'Văn Phòng',
        5 => 'Định Hình',
        6 => 'Bao Phim',
        7 => 'ĐGSC',
        8 => 'ĐGTC',
        9 => 'VSCN + Kho BTP',
        10 => 'Mã Hoá BB',
    ];

    /** Người đang đăng nhập được sửa khai báo của NV thuộc $department: Admin, hoặc người cùng bộ phận */
    public static function userCanEdit(?string $department): bool
    {
        $user = session('user') ?? [];
        if (($user['userGroup'] ?? '') === 'Admin') {
            return true;
        }
        return $department !== null && ($user['department'] ?? null) === $department;
    }

    /** Loại khai báo áp cho một công đoạn; null = công đoạn không xét sản phẩm (Bảo trì...) */
    public static function typeForStage($stageCode): ?string
    {
        $stageCode = (int) $stageCode;
        if ($stageCode >= 1 && $stageCode <= 6) {
            return self::TYPE_BTP;
        }
        return $stageCode === 7 ? self::TYPE_TP : null;
    }

    /** Phòng sản xuất đang hiệu lực của các NV: [employees_id => [room_id, ...]] */
    public function employeeRooms(array $employeeIds): array
    {
        if (!$employeeIds) {
            return [];
        }

        $map = [];
        foreach (array_chunk($employeeIds, 1000) as $chunk) {
            DB::table('employee_assignments')
                ->whereIn('employees_id', $chunk)
                ->where('active', 1)
                ->where('room_id', '>', 0)
                ->select('employees_id', 'room_id')
                ->distinct()
                ->get()
                ->each(function ($r) use (&$map) {
                    $map[$r->employees_id][] = (int) $r->room_id;
                });
        }
        return $map;
    }

    /** Phòng sản xuất (CĐ 1-7) đang hiệu lực của các NV, đã sắp tăng dần: [employees_id => [room_id, ...]] */
    public function productionRoomIds(array $employeeIds): array
    {
        $production = DB::table('room')
            ->whereBetween('stage_code', [1, 7])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $map = [];
        foreach ($this->employeeRooms($employeeIds) as $employeeId => $rooms) {
            $ids = array_values(array_unique(array_filter($rooms, fn ($r) => isset($production[$r]))));
            sort($ids);
            $map[$employeeId] = $ids;
        }
        return $map;
    }

    /**
     * Khai báo đang hiệu lực của các NV: [employees_id => ['BTP' => [code => true], 'TP' => [...]]]
     */
    public function declarations(array $employeeIds): array
    {
        if (!$employeeIds) {
            return [];
        }

        $map = [];
        foreach (array_chunk($employeeIds, 1000) as $chunk) {
            DB::table('employee_products')
                ->whereIn('employees_id', $chunk)
                ->where('active', 1)
                ->select('employees_id', 'product_type', 'product_code')
                ->get()
                ->each(function ($r) use (&$map) {
                    $map[$r->employees_id][$r->product_type][$r->product_code] = true;
                });
        }
        return $map;
    }

    /**
     * Khai báo đang hiệu lực kèm tên SP và người / lúc cập nhật, sắp theo mã:
     * [employees_id => ['BTP' => [['code','name','by','at']], 'TP' => [...]]]
     */
    public function declaredItems(array $employeeIds): array
    {
        if (!$employeeIds) {
            return [];
        }

        $rows = collect();
        foreach (array_chunk($employeeIds, 1000) as $chunk) {
            $rows = $rows->merge(DB::table('employee_products')
                ->whereIn('employees_id', $chunk)
                ->where('active', 1)
                ->select('employees_id', 'product_type', 'product_code', 'created_by', 'updated_by', 'created_at', 'updated_at')
                ->get());
        }

        $labels = [];
        foreach ($rows->groupBy('product_type') as $type => $typeRows) {
            $labels[$type] = $this->productLabels($type, $typeRows->pluck('product_code')->map(fn ($c) => (string) $c)->unique()->values()->all());
        }

        $map = [];
        foreach ($rows as $r) {
            $code = (string) $r->product_code;
            $at = $r->updated_at ?? $r->created_at;
            $map[$r->employees_id][$r->product_type][] = [
                'code' => $code,
                'name' => $labels[$r->product_type][$code]['name'] ?? '',
                'by' => $r->updated_by ?: $r->created_by,
                'at' => $at ? date('d/m/Y H:i', strtotime($at)) : '',
            ];
        }
        foreach ($map as &$byType) {
            foreach ($byType as &$items) {
                usort($items, fn ($a, $b) => strcmp($a['code'], $b['code']));
            }
        }
        unset($byType, $items);

        return $map;
    }

    /**
     * Tổng hợp cho bảng "Sản phẩm được phép": gắn vào từng NV các thuộc tính
     * group_names (tổ đang hoạt động trong bộ phận), room_codes / room_count (phòng sản xuất CĐ 1-7),
     * btp_items / tp_items (khai báo đang hiệu lực, xem declaredItems) và btp_count, tp_count.
     */
    public function attachSummaries(Collection $employees, string $department): Collection
    {
        $ids = $employees->pluck('id')->all();
        $roomsByEmp = $this->employeeRooms($ids);
        $itemsByEmp = $this->declaredItems($ids);
        $productionRooms = DB::table('room')
            ->whereBetween('stage_code', [1, 7])
            ->select('id', 'code', 'stage_code', 'order_by')
            ->get()
            ->keyBy('id');

        $groupsByEmp = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            DB::table('employee_assignments')
                ->whereIn('employees_id', $chunk)
                ->where('active', 1)
                ->where('group_id', '>', 0)
                ->where('production_code', $department)
                ->select('employees_id', 'group_id')
                ->distinct()
                ->get()
                ->each(function ($r) use (&$groupsByEmp) {
                    $groupsByEmp[$r->employees_id][(int) $r->group_id] = true;
                });
        }

        foreach ($employees as $e) {
            $e->group_names = array_values(array_filter(array_map(
                fn ($g) => self::PROD_GROUPS[$g] ?? null,
                array_keys($groupsByEmp[$e->id] ?? [])
            )));

            // Mã các phòng sản xuất (CĐ 1-7) của NV, theo thứ tự công đoạn rồi thứ tự phòng
            $rooms = collect(array_unique($roomsByEmp[$e->id] ?? []))
                ->map(fn ($id) => $productionRooms->get($id))
                ->filter()
                ->sortBy(fn ($r) => sprintf('%02d-%05d-%s', $r->stage_code, $r->order_by ?? 0, $r->code))
                ->values();
            $e->room_codes = $rooms->pluck('code')->all();
            $e->room_count = count($e->room_codes);
            // Khóa so khớp "cùng phòng SX" khi nhân bản khai báo (giống productionRoomIds)
            $roomIds = $rooms->pluck('id')->map(fn ($id) => (int) $id)->all();
            sort($roomIds);
            $e->room_key = implode(',', $roomIds);

            $e->btp_items = $itemsByEmp[$e->id][self::TYPE_BTP] ?? [];
            $e->tp_items = $itemsByEmp[$e->id][self::TYPE_TP] ?? [];
            $e->btp_count = count($e->btp_items);
            $e->tp_count = count($e->tp_items);
        }

        return $employees;
    }

    /**
     * Phòng định mức của sản phẩm: [code => [room_id, ...]].
     * $roomIds giới hạn trong các phòng này (null = mọi phòng).
     */
    public function quotaRooms(string $type, ?array $codes = null, ?array $roomIds = null): array
    {
        $codeCol = $type === self::TYPE_TP ? 'finished_product_code' : 'intermediate_code';

        $query = DB::table('quota')
            ->where('active', 1)
            ->select($codeCol . ' as code', 'room_id')
            ->distinct();

        if ($type === self::TYPE_TP) {
            $query->where('stage_code', 7);
        } else {
            $query->whereBetween('stage_code', [1, 6]);
        }
        if ($codes !== null) {
            if (!$codes) {
                return [];
            }
            $query->whereIn($codeCol, $codes);
        }
        if ($roomIds !== null) {
            if (!$roomIds) {
                return [];
            }
            $query->whereIn('room_id', $roomIds);
        }

        $map = [];
        foreach ($query->get() as $r) {
            $map[$r->code][] = (int) $r->room_id;
        }
        return $map;
    }

    /**
     * Tên hiển thị của sản phẩm: [code => ['name' => ..., 'extra' => ...]].
     * Tên lấy theo BTP (intermediate_category → product_name) như mọi chỗ khác của hệ thống.
     */
    public function productLabels(string $type, array $codes): array
    {
        if (!$codes) {
            return [];
        }

        $labels = [];
        foreach (array_chunk(array_values(array_unique($codes)), 1000) as $chunk) {
            if ($type === self::TYPE_TP) {
                $rows = DB::table('finished_product_category as fpc')
                    ->leftJoin('intermediate_category as ic', 'ic.intermediate_code', '=', 'fpc.intermediate_code')
                    ->leftJoin('product_name as pn', 'pn.id', '=', 'ic.product_name_id')
                    ->leftJoin('market as m', 'm.id', '=', 'fpc.market_id')
                    ->leftJoin('specification as s', 's.id', '=', 'fpc.specification_id')
                    ->whereIn('fpc.finished_product_code', $chunk)
                    ->orderBy('fpc.active', 'desc')
                    ->select('fpc.finished_product_code as code', 'pn.name', 'm.code as market', 's.name as spec', 'fpc.intermediate_code')
                    ->get();
                foreach ($rows as $r) {
                    if (isset($labels[$r->code])) {
                        continue;
                    }
                    $labels[$r->code] = [
                        'name' => $r->name ?? '',
                        'extra' => trim(implode(' · ', array_filter([$r->spec, $r->market, 'BTP ' . $r->intermediate_code]))),
                    ];
                }
            } else {
                $rows = DB::table('intermediate_category as ic')
                    ->leftJoin('product_name as pn', 'pn.id', '=', 'ic.product_name_id')
                    ->leftJoin('dosage as d', 'd.id', '=', 'ic.dosage_id')
                    ->whereIn('ic.intermediate_code', $chunk)
                    ->select('ic.intermediate_code as code', 'pn.name', 'd.name as dosage', 'ic.batch_size', 'ic.unit_batch_size')
                    ->get();
                foreach ($rows as $r) {
                    $labels[$r->code] = [
                        'name' => $r->name ?? '',
                        'extra' => trim(implode(' · ', array_filter([
                            $r->dosage,
                            $r->batch_size ? rtrim(rtrim(number_format((float) $r->batch_size, 2, '.', ''), '0'), '.') . ' ' . $r->unit_batch_size : null,
                        ]))),
                    ];
                }
            }
        }
        return $labels;
    }

    /**
     * Danh sách sản phẩm của 1 NV cho màn hình khai báo: mọi sản phẩm có định mức ở phòng của NV
     * + các sản phẩm đã khai báo nhưng không còn phòng phù hợp.
     */
    public function productsForEmployee(int $employeeId, string $type): array
    {
        $roomIds = $this->employeeRooms([$employeeId])[$employeeId] ?? [];
        $eligible = $this->quotaRooms($type, null, $roomIds);

        $declared = DB::table('employee_products')
            ->where('employees_id', $employeeId)
            ->where('product_type', $type)
            ->get()
            ->keyBy('product_code');

        $codes = array_unique(array_merge(
            array_keys($eligible),
            $declared->where('active', 1)->keys()->all()
        ));
        $labels = $this->productLabels($type, $codes);

        $items = [];
        foreach ($codes as $code) {
            $code = (string) $code;
            $d = $declared->get($code);
            $isDeclared = $d && $d->active;
            $items[] = [
                'code' => $code,
                'name' => $labels[$code]['name'] ?? '',
                'extra' => $labels[$code]['extra'] ?? '',
                'rooms' => array_values(array_unique($eligible[$code] ?? [])),
                'declared' => (bool) $isDeclared,
                'by' => $isDeclared ? ($d->updated_by ?: $d->created_by) : null,
                'at' => $isDeclared ? ($d->updated_at ?? $d->created_at) : null,
            ];
        }

        usort($items, function ($a, $b) {
            return [$b['declared'], $a['name'], $a['code']] <=> [$a['declared'], $b['name'], $b['code']];
        });

        return $items;
    }

    /**
     * Ghi lại toàn bộ khai báo loại $type của 1 NV theo danh sách mã mới.
     * Mã bị bỏ thì active = 0 (giữ lại dòng để biết ai đã khai báo trước đó).
     *
     * @return array{added:int, removed:int}
     */
    public function saveDeclarations(int $employeeId, string $type, array $codes, string $user, ?string $auditUser = null, ?string $auditAction = null): array
    {
        $codes = array_values(array_unique(array_filter(array_map('strval', $codes), 'strlen')));
        $now = now();

        return DB::transaction(function () use ($employeeId, $type, $codes, $user, $auditUser, $auditAction, $now) {
            $existing = DB::table('employee_products')
                ->where('employees_id', $employeeId)
                ->where('product_type', $type)
                ->lockForUpdate()
                ->get()
                ->keyBy('product_code');

            $before = $existing->where('active', 1)->keys()->map(fn ($c) => (string) $c)->all();
            $added = array_values(array_diff($codes, $before));
            $removed = array_values(array_diff($before, $codes));

            // Chỉ nhận mã có trong danh mục BTP / TP
            if ($added) {
                $known = $type === self::TYPE_TP
                    ? DB::table('finished_product_category')->whereIn('finished_product_code', $added)->pluck('finished_product_code')
                    : DB::table('intermediate_category')->whereIn('intermediate_code', $added)->pluck('intermediate_code');
                $added = array_values(array_intersect($added, $known->map(fn ($c) => (string) $c)->all()));
            }

            $reactivate = [];
            $insert = [];
            foreach ($added as $code) {
                if ($existing->has($code)) {
                    $reactivate[] = $existing->get($code)->id;
                } else {
                    $insert[] = [
                        'employees_id' => $employeeId,
                        'product_type' => $type,
                        'product_code' => $code,
                        'active' => 1,
                        'created_by' => $user,
                        'updated_by' => $user,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($insert, 500) as $chunk) {
                DB::table('employee_products')->insert($chunk);
            }
            if ($reactivate) {
                DB::table('employee_products')->whereIn('id', $reactivate)
                    ->update(['active' => 1, 'updated_by' => $user, 'updated_at' => $now]);
            }
            if ($removed) {
                DB::table('employee_products')
                    ->where('employees_id', $employeeId)
                    ->where('product_type', $type)
                    ->whereIn('product_code', $removed)
                    ->update(['active' => 0, 'updated_by' => $user, 'updated_at' => $now]);
            }

            if ($added || $removed) {
                DB::table('audittriallog')->insert([
                    'userName' => mb_substr($auditUser ?? $user, 0, 10),
                    'action' => mb_substr($auditAction ?? 'Khai báo sản phẩm ' . $type, 0, 255),
                    'table_Audit' => 'employee_products',
                    'record_Id_AuditTrial' => $employeeId,
                    'old_values' => json_encode(['removed' => $removed], JSON_UNESCAPED_UNICODE),
                    'new_values' => json_encode(['added' => $added], JSON_UNESCAPED_UNICODE),
                    'ip_address' => request()->ip(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return ['added' => count($added), 'removed' => count($removed)];
        });
    }

    /**
     * Nhân bản khai báo (cả BTP và TP) của NV nguồn sang các NV đích.
     * $replace = false: cộng thêm SP của nguồn, giữ SP đích đang có; true: đích giống hệt nguồn.
     * Kiểm tra cùng phòng SX do controller làm trước khi gọi.
     *
     * @return array<int, array{added:int, removed:int}> theo employees_id đích
     */
    public function copyDeclarations(int $sourceId, array $targetIds, bool $replace, string $user, ?string $auditUser = null, ?string $sourceCode = null): array
    {
        $source = $this->declarations([$sourceId])[$sourceId] ?? [];
        $current = $this->declarations($targetIds);

        return DB::transaction(function () use ($source, $current, $targetIds, $replace, $user, $auditUser, $sourceCode, $sourceId) {
            $result = [];
            foreach ($targetIds as $targetId) {
                $added = 0;
                $removed = 0;
                foreach (array_keys(self::TYPES) as $type) {
                    $codes = array_map('strval', array_keys($source[$type] ?? []));
                    if (!$replace) {
                        $codes = array_merge(array_map('strval', array_keys($current[$targetId][$type] ?? [])), $codes);
                    }
                    $action = 'Nhân bản khai báo sản phẩm ' . $type . ' từ NV ' . ($sourceCode ?? $sourceId) . ($replace ? ' (ghi đè)' : '');
                    $r = $this->saveDeclarations($targetId, $type, $codes, $user, $auditUser, $action);
                    $added += $r['added'];
                    $removed += $r['removed'];
                }
                $result[$targetId] = ['added' => $added, 'removed' => $removed];
            }
            return $result;
        });
    }

    /**
     * Thông tin sản phẩm của các lô (chỉ kế hoạch sản xuất, plan_list.type = 1):
     * [sp_id => ['stage_code', 'room_id', 'btp', 'tp', 'name', 'batch']]
     */
    public function planProducts(array $spIds): array
    {
        $spIds = array_values(array_unique(array_filter(array_map('intval', $spIds))));
        if (!$spIds) {
            return [];
        }

        $map = [];
        foreach (array_chunk($spIds, 1000) as $chunk) {
            DB::table('stage_plan as sp')
                ->join('plan_master as pm', 'pm.id', '=', 'sp.plan_master_id')
                ->join('plan_list as pl', 'pl.id', '=', 'pm.plan_list_id')
                ->join('finished_product_category as fpc', 'fpc.id', '=', 'sp.product_caterogy_id')
                ->leftJoin('intermediate_category as ic', 'ic.intermediate_code', '=', 'fpc.intermediate_code')
                ->leftJoin('product_name as pn', 'pn.id', '=', 'ic.product_name_id')
                ->leftJoin('product_name as pn_tp', 'pn_tp.id', '=', 'fpc.product_name_id')
                ->where('pl.type', 1)
                ->whereIn('sp.id', $chunk)
                ->select('sp.id', 'sp.stage_code', 'sp.resourceId', 'fpc.intermediate_code', 'fpc.finished_product_code', 'pm.batch',
                    DB::raw(self::NAME_SQL . ' as name'))
                ->get()
                ->each(function ($r) use (&$map) {
                    $map[$r->id] = [
                        'stage_code' => (int) $r->stage_code,
                        'room_id' => $r->resourceId ? (int) $r->resourceId : null,
                        'btp' => $r->intermediate_code,
                        'tp' => $r->finished_product_code,
                        'name' => $r->name ?? '',
                        'batch' => $r->batch,
                    ];
                });
        }
        return $map;
    }

    /**
     * Lô sản xuất đã sắp lịch ở một phòng, chưa hoàn thành và chưa nhận phòng (actual_start NULL), theo giờ bắt đầu.
     * Dùng cho nút [+] ở ô Hoạt động của Lịch công tác: người lập lịch chọn lô từ đây nên dòng thêm vào luôn có
     * data-sp-id đúng. Mỗi lô cùng hình dạng planProducts() + giờ sản xuất / vệ sinh.
     */
    public function pendingLots(int $roomId, int $limit = 200): array
    {
        return DB::table('stage_plan as sp')
            ->join('plan_master as pm', 'pm.id', '=', 'sp.plan_master_id')
            ->join('plan_list as pl', 'pl.id', '=', 'pm.plan_list_id')
            ->join('finished_product_category as fpc', 'fpc.id', '=', 'sp.product_caterogy_id')
            ->leftJoin('intermediate_category as ic', 'ic.intermediate_code', '=', 'fpc.intermediate_code')
            ->leftJoin('product_name as pn', 'pn.id', '=', 'ic.product_name_id')
            ->leftJoin('product_name as pn_tp', 'pn_tp.id', '=', 'fpc.product_name_id')
            ->where('pl.type', 1)
            ->where('sp.resourceId', $roomId)
            ->where('sp.active', 1)
            ->where('sp.finished', 0)
            ->whereNull('sp.actual_start')
            ->whereNotNull('sp.start')
            ->whereBetween('sp.stage_code', [1, 7])
            ->orderBy('sp.start')
            ->limit($limit)
            ->select(
                'sp.id', 'sp.stage_code', 'sp.resourceId', 'sp.start', 'sp.end',
                'sp.start_clearning', 'sp.end_clearning', 'sp.title_clearning',
                'fpc.intermediate_code', 'fpc.finished_product_code', 'pm.batch',
                DB::raw(self::NAME_SQL . ' as name')
            )
            ->get()
            ->map(fn ($r) => [
                'sp_id' => (int) $r->id,
                'stage_code' => (int) $r->stage_code,
                'room_id' => (int) $r->resourceId,
                'btp' => $r->intermediate_code,
                'tp' => $r->finished_product_code,
                'name' => $r->name ?? '',
                'batch' => $r->batch,
                'start' => $r->start,
                'end' => $r->end,
                'start_clearning' => $r->start_clearning,
                'end_clearning' => $r->end_clearning,
                'title_clearning' => $r->title_clearning,
            ])
            ->all();
    }

    /** Tiêu đề lô có phải là vệ sinh không (cùng quy tắc với cột lịch lý thuyết của trang Lịch công tác) */
    public static function isCleaningTitle(?string $title): bool
    {
        $title = (string) $title;
        return stripos($title, 'vệ sinh') !== false || stripos($title, 'VS-') !== false || stripos($title, 'VS ') !== false;
    }

    /**
     * Đọc các mục lịch (.plan-item) trong HTML của ô Hoạt động.
     * Mỗi mục: ['sp_id' => ?int, 'kind' => ?string, 'start' => ?string]. Mục chép trước khi có liên kết
     * chỉ có data-start.
     */
    public static function parseActivities(?string $html): array
    {
        if (!$html || stripos($html, 'plan-item') === false) {
            return [];
        }

        preg_match_all('/<[a-z][a-z0-9]*\b[^>]*>/i', $html, $tags);

        $items = [];
        foreach ($tags[0] as $tag) {
            if (!preg_match('/\bclass\s*=\s*(["\'])[^"\']*\bplan-item\b/i', $tag)) {
                continue;
            }
            $attr = function ($name) use ($tag) {
                return preg_match('/\b' . $name . '\s*=\s*(["\'])(.*?)\1/i', $tag, $m) ? html_entity_decode($m[2]) : null;
            };
            $spId = $attr('data-sp-id');
            $kind = strtoupper((string) $attr('data-kind'));
            $items[] = [
                'sp_id' => ctype_digit((string) $spId) ? (int) $spId : null,
                'kind' => in_array($kind, [self::KIND_PRODUCTION, self::KIND_CLEANING], true) ? $kind : null,
                'start' => $attr('data-start'),
            ];
        }
        return $items;
    }

    /**
     * Ghi liên kết ca công tác ↔ stage_plan theo nội dung ô Hoạt động.
     * Mục cũ chỉ có data-start được dò lại theo phòng: trùng giờ bắt đầu sản xuất → SX, trùng giờ vệ sinh → VS.
     */
    public function syncActivities(int $assignmentId, ?string $html, $roomId = null): int
    {
        $items = self::parseActivities($html);

        DB::table('assignment_activities')->where('assignment_id', $assignmentId)->delete();
        if (!$items) {
            return 0;
        }

        $rows = [];
        $legacyStarts = [];
        foreach ($items as $it) {
            if ($it['sp_id']) {
                $rows[$it['sp_id'] . '|' . ($it['kind'] ?? self::KIND_PRODUCTION)] = [$it['sp_id'], $it['kind'] ?? self::KIND_PRODUCTION];
            } elseif ($it['start'] && $roomId) {
                $legacyStarts[] = $it['start'];
            }
        }

        if ($legacyStarts) {
            $legacyStarts = array_values(array_unique($legacyStarts));
            $plans = DB::table('stage_plan')
                ->where('resourceId', $roomId)
                ->where('active', 1)
                ->where(function ($q) use ($legacyStarts) {
                    $q->whereIn('start', $legacyStarts)->orWhereIn('start_clearning', $legacyStarts);
                })
                ->select('id', 'start', 'start_clearning', 'title')
                ->get();
            foreach ($legacyStarts as $start) {
                $ts = strtotime($start);
                foreach ($plans as $p) {
                    if ($p->start && strtotime($p->start) === $ts) {
                        $kind = self::isCleaningTitle($p->title) ? self::KIND_CLEANING : self::KIND_PRODUCTION;
                        $rows[$p->id . '|' . $kind] = [(int) $p->id, $kind];
                        break;
                    }
                    if ($p->start_clearning && strtotime($p->start_clearning) === $ts) {
                        $rows[$p->id . '|' . self::KIND_CLEANING] = [(int) $p->id, self::KIND_CLEANING];
                        break;
                    }
                }
            }
        }

        $now = now();
        $insert = [];
        foreach ($rows as [$spId, $kind]) {
            $insert[] = [
                'assignment_id' => $assignmentId,
                'stage_plan_id' => $spId,
                'activity_type' => $kind,
                'created_at' => $now,
            ];
        }
        if ($insert) {
            DB::table('assignment_activities')->insertOrIgnore($insert);
        }
        return count($insert);
    }

    /**
     * Gỡ lớp đánh dấu cảnh báo (`paw-flag`, badge "Nhân viên chưa được đào tạo sản phẩm") mà giao diện gắn vào
     * ô Hoạt động trước khi lưu Job_description: cảnh báo luôn được tính lại lúc mở trang, không lưu vào DB.
     */
    public static function stripFlags(?string $html): ?string
    {
        if ($html === null || !str_contains($html, 'paw-flag')) {
            return $html;
        }

        // Chỉ sửa trong giá trị thuộc tính class: class="plan-text paw-flag" -> class="plan-text"
        return preg_replace_callback(
            '/(\bclass\s*=\s*)(["\'])(.*?)\2/i',
            fn ($m) => $m[1] . $m[2] . trim(preg_replace('/\s*\bpaw-flag\b/', '', $m[3])) . $m[2],
            $html
        ) ?? $html;
    }

    /** Mã sp_id xuất hiện trong các ô Hoạt động (để tra sản phẩm của mục chép từ ngày khác) */
    public static function spIdsInDescriptions(Collection|array $descriptions): array
    {
        $ids = [];
        foreach ($descriptions as $html) {
            foreach (self::parseActivities($html) as $it) {
                if ($it['sp_id']) {
                    $ids[$it['sp_id']] = true;
                }
            }
        }
        return array_keys($ids);
    }
}
