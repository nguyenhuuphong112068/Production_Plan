<?php

namespace App\Http\Controllers\Pages\MaintenanceSchedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaintenanceAssessmentController extends Controller
{
    private const TABLE = 'calibration_maintenance_assessment';

    public const PERMISSION_VIEW = 'maintenance_assessment_view';
    public const PERMISSION_CREATE = 'maintenance_assessment_create';
    public const PERMISSION_UPDATE_EMPLOYEES = 'maintenance_assessment_update_employees';

    private function can(string $permission): bool
    {
        return (bool) user_has_permission(session('user')['userId'] ?? null, $permission, 'boolean');
    }

    private function denied(string $message = 'Bạn không có quyền thực hiện chức năng này.')
    {
        return response()->json(['success' => false, 'message' => $message], 403);
    }

    public function index()
    {
        abort_unless($this->can(self::PERMISSION_VIEW), 403, 'Bạn không có quyền xem đánh giá bảo trì - hiệu chuẩn.');

        session()->put(['title' => 'ĐÁNH GIÁ BẢO TRÌ - HIỆU CHUẨN']);

        return view('app');
    }

    /**
     * Danh sách nhân viên để chọn trong modal đánh giá.
     * Bảng employees không có cột bộ phận nên EN (bảo trì) / QA (hiệu chuẩn)
     * được suy ra từ phân công trong employee_assignments.
     */
    public function employees()
    {
        if (!$this->can(self::PERMISSION_VIEW) && !$this->can(self::PERMISSION_CREATE) && !$this->can(self::PERMISSION_UPDATE_EMPLOYEES)) {
            return $this->denied();
        }

        $departments = DB::table('employee_assignments')
            ->whereIn('production_code', ['EN', 'QA'])
            ->select('employees_id', 'production_code')
            ->distinct()
            ->get()
            ->groupBy('employees_id')
            ->map(fn($group) => $group->pluck('production_code')->unique()->values()->all());

        $employees = DB::table('employees')
            ->where('active', 1)
            ->where('resign', 0)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get()
            ->map(function ($employee) use ($departments) {
                $employee->departments = $departments[$employee->id] ?? [];

                return $employee;
            });

        return response()->json(['employees' => $employees]);
    }

    /**
     * Đánh giá hiện có của một sự kiện trên lịch.
     * Một sự kiện có thể gộp nhiều stage_plan (bảo trì nhiều thiết bị cùng lúc)
     * nên nhận vào danh sách id và lấy đánh giá đầu tiên tìm được.
     */
    public function detail(Request $request)
    {
        if (!$this->can(self::PERMISSION_VIEW) && !$this->can(self::PERMISSION_CREATE)) {
            return $this->denied();
        }

        $ids = $this->stagePlanIds($request->input('stage_plan_ids'));

        if (empty($ids)) {
            return response()->json(['assessment' => null]);
        }

        $assessment = DB::table(self::TABLE)
            ->whereIn('stage_plan_id', $ids)
            ->orderBy('stage_plan_id')
            ->first();

        if ($assessment) {
            $assessment->employees_code = json_decode($assessment->employees_code ?? '[]', true) ?: [];
        }

        return response()->json(['assessment' => $assessment]);
    }

    public function store(Request $request)
    {
        if (!$this->can(self::PERMISSION_CREATE)) {
            return $this->denied('Bạn không có quyền đánh giá công tác bảo trì - hiệu chuẩn.');
        }

        $validated = $request->validate([
            'stage_plan_ids' => 'nullable|array',
            'star_rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'employees_code' => 'nullable|array',
            'employees_code.*' => 'string|max:50',
        ]);

        $ids = $this->stagePlanIds($validated['stage_plan_ids'] ?? []);

        if (empty($ids)) {
            return $this->storeManual($request, $validated);
        }

        $stagePlans = DB::table('stage_plan')->whereIn('id', $ids)->get(['id', 'plan_master_id']);

        if ($stagePlans->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Lịch bảo trì không còn tồn tại.'], 404);
        }

        $user = session('user')['fullName'] ?? 'System';
        $employeesCode = json_encode(array_values($validated['employees_code'] ?? []), JSON_UNESCAPED_UNICODE);

        DB::beginTransaction();
        try {
            foreach ($stagePlans as $plan) {
                $existing = DB::table(self::TABLE)->where('stage_plan_id', $plan->id)->first();

                $payload = [
                    'plan_master_id' => $plan->plan_master_id,
                    'star_rating' => $validated['star_rating'],
                    'comment' => $validated['comment'] ?? null,
                    'employees_code' => $employeesCode,
                    'updated_by' => $user,
                    'updated_at' => now(),
                ];

                if ($existing) {
                    DB::table(self::TABLE)->where('id', $existing->id)->update($payload);
                } else {
                    DB::table(self::TABLE)->insert($payload + [
                        'stage_plan_id' => $plan->id,
                        'created_by' => $user,
                        'created_at' => now(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi lưu đánh giá bảo trì:', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Đã lưu đánh giá công tác bảo trì.']);
    }

    /** Cập nhật lại danh sách nhân sự liên quan của một đánh giá đã có */
    public function updateEmployees(Request $request)
    {
        if (!$this->can(self::PERMISSION_UPDATE_EMPLOYEES)) {
            return $this->denied('Bạn không có quyền cập nhật nhân sự liên quan.');
        }

        $validated = $request->validate([
            'id' => 'required|integer',
            'employees_code' => 'nullable|array',
            'employees_code.*' => 'string|max:50',
        ]);

        if (!DB::table(self::TABLE)->where('id', $validated['id'])->exists()) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đánh giá cần cập nhật.'], 404);
        }

        DB::table(self::TABLE)
            ->where('id', $validated['id'])
            ->update([
                'employees_code' => json_encode(array_values($validated['employees_code'] ?? []), JSON_UNESCAPED_UNICODE),
                'updated_by' => session('user')['fullName'] ?? 'System',
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true, 'message' => 'Đã cập nhật nhân sự liên quan.']);
    }

    /** Đánh giá ngoài kế hoạch: công việc bảo trì không có trên lịch */
    private function storeManual(Request $request, array $validated)
    {
        // Ngoài nhân sự liên quan, các thông tin còn lại đều bắt buộc
        $manual = $request->validate([
            'id' => 'nullable|integer',
            'assessment_date' => 'required|date',
            'room_id' => 'nullable|integer|required_without:room_name',
            'room_name' => 'nullable|string|max:255|required_without:room_id',
            'equipment_name' => 'required|string|max:255',
            'type_name' => 'required|string|max:50',
            'comment' => 'required|string|max:2000',
        ]);

        $user = session('user')['fullName'] ?? 'System';

        $payload = [
            'assessment_date' => Carbon::parse($manual['assessment_date']),
            'room_id' => $manual['room_id'] ?? null,
            'room_name' => $manual['room_name'] ?? null,
            'equipment_name' => $manual['equipment_name'],
            'type_name' => $manual['type_name'] ?? 'Bảo trì',
            'deparment_code' => session('user')['production_code'] ?? null,
            'star_rating' => $validated['star_rating'],
            'comment' => $validated['comment'] ?? null,
            'employees_code' => json_encode(array_values($validated['employees_code'] ?? []), JSON_UNESCAPED_UNICODE),
            'updated_by' => $user,
            'updated_at' => now(),
        ];

        try {
            if (!empty($manual['id'])) {
                $updated = DB::table(self::TABLE)
                    ->where('id', $manual['id'])
                    ->whereNull('stage_plan_id')
                    ->update($payload);

                if (!$updated) {
                    return response()->json(['success' => false, 'message' => 'Không tìm thấy đánh giá cần sửa.'], 404);
                }
            } else {
                DB::table(self::TABLE)->insert($payload + [
                    'created_by' => $user,
                    'created_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi lưu đánh giá ngoài kế hoạch:', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Đã lưu đánh giá ngoài kế hoạch.']);
    }

    /** Dữ liệu cho trang xem đánh giá theo tháng */
    public function monthly(Request $request)
    {
        if (!$this->can(self::PERMISSION_VIEW)) {
            return $this->denied('Bạn không có quyền xem đánh giá bảo trì - hiệu chuẩn.');
        }

        $month = $request->input('month') ?: Carbon::now()->format('Y-m');

        try {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception $e) {
            $start = Carbon::now()->startOfMonth();
            $month = $start->format('Y-m');
        }
        $end = $start->copy()->endOfMonth();

        $production = session('user')['production_code'];

        // Đánh giá ngoài kế hoạch không có stage_plan nên mọi thông tin lịch đều lấy từ chính dòng đánh giá
        $rows = DB::table(self::TABLE . ' as a')
            ->leftJoin('stage_plan as sp', 'a.stage_plan_id', '=', 'sp.id')
            ->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
            ->leftJoin('room as r', function ($join) {
                $join->on(DB::raw('COALESCE(sp.resourceId, a.room_id)'), '=', 'r.id');
            })
            ->whereRaw('COALESCE(sp.start, a.assessment_date) BETWEEN ? AND ?', [$start, $end])
            ->where(function ($q) use ($production) {
                $q->where('sp.deparment_code', $production)
                    ->orWhere('r.deparment_code', $production)
                    ->orWhere('a.deparment_code', $production);
            })
            ->select(
                'a.id',
                'a.stage_plan_id',
                'a.plan_master_id',
                'a.star_rating',
                'a.comment',
                'a.employees_code',
                'a.equipment_name',
                'a.created_by',
                'a.created_at',
                'a.updated_by',
                'a.updated_at',
                DB::raw('COALESCE(sp.start, a.assessment_date) as planned_start'),
                DB::raw('COALESCE(sp.end, a.assessment_date) as planned_end'),
                'sp.finished',
                'sp.code',
                DB::raw("COALESCE(r.code, CASE WHEN sp.resourceId = 0 OR a.room_id = 0 THEN 'PX' ELSE NULL END, a.room_name) as room_code"),
                DB::raw("COALESCE(r.name, CASE WHEN sp.resourceId = 0 OR a.room_id = 0 THEN 'Toàn phân xưởng' ELSE NULL END, a.room_name) as room_name"),
                DB::raw('COALESCE(qm.inst_id, a.equipment_name) as inst_id'),
                'qm.inst_name',
                'qm.Eqp_name',
                'qm.parent_eqp_id',
                DB::raw("CASE WHEN a.stage_plan_id IS NULL THEN a.type_name
                              WHEN qm.block LIKE 'TI-%' THEN 'Tiện ích'
                              WHEN qm.block LIKE 'HC-%' THEN 'Hiệu chuẩn'
                              ELSE 'Bảo trì' END as type_name")
            )
            ->orderByRaw('COALESCE(sp.start, a.assessment_date)')
            ->get();

        $employeeNames = DB::table('employees')->pluck('name', 'code');

        $rows = $rows->map(function ($row) use ($employeeNames) {
            $codes = json_decode($row->employees_code ?? '[]', true) ?: [];
            $row->employees_code = $codes;
            $row->is_manual = $row->stage_plan_id === null;
            $row->employees_detail = collect($codes)
                ->map(fn($code) => ['code' => $code, 'name' => $employeeNames[$code] ?? $code])
                ->values();
            $row->employees = $row->employees_detail
                ->map(fn($emp) => $emp['name'] . ' (' . $emp['code'] . ')')
                ->values();

            return $row;
        });

        return response()->json([
            'month' => $month,
            'rows' => $rows,
            'summary' => [
                'total' => $rows->count(),
                'average' => $rows->count() ? round($rows->avg('star_rating'), 2) : 0,
                'distribution' => collect(range(1, 5))
                    ->mapWithKeys(fn($star) => [$star => $rows->where('star_rating', $star)->count()])
                    ->all(),
            ],
            'by_employee' => $this->statsByEmployee($rows),
            'can_update_employees' => $this->can(self::PERMISSION_UPDATE_EMPLOYEES),
        ]);
    }

    /** Thống kê theo từng nhân sự có tham gia các lần bảo trì được đánh giá */
    private function statsByEmployee($rows)
    {
        return $rows
            ->flatMap(fn($row) => collect($row->employees_detail)->map(fn($emp) => [
                'code' => $emp['code'],
                'name' => $emp['name'],
                'star_rating' => $row->star_rating,
            ]))
            ->groupBy('code')
            ->map(function ($items, $code) {
                $stars = collect($items)->pluck('star_rating');

                return [
                    'code' => $code,
                    'name' => $items[0]['name'],
                    'total' => $stars->count(),
                    'average' => round($stars->avg(), 2),
                    'min' => $stars->min(),
                    'max' => $stars->max(),
                    'distribution' => collect(range(1, 5))
                        ->mapWithKeys(fn($star) => [$star => $stars->filter(fn($s) => (int) $s === $star)->count()])
                        ->all(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    /** Id sự kiện trên lịch có dạng "12,13-maintenance" nên cần tách về danh sách id số */
    private function stagePlanIds($input): array
    {
        $raw = is_array($input) ? $input : explode(',', (string) $input);

        return collect($raw)
            ->flatMap(fn($value) => explode(',', (string) $value))
            ->map(fn($value) => (int) explode('-', trim($value))[0])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
