<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class MaintenanceAssignmentController extends Controller
{
    public function portal()
    {
        $rawGroups = DB::table('stage_groups')->where('type', 2)->orderBy('id')->get();
        $groups = [];
        // Thêm thẻ "Kỹ thuật bảo trì" (Tất cả EN) lên đầu
        $groups[] = (object)[
            'code' => 'EN_ALL',
            'name' => 'Kỹ thuật bảo trì',
            'type' => 2
        ];

        foreach ($rawGroups as $g) {
            // Loại bỏ thẻ nhóm Tiện ích (13) theo yêu cầu, giữ lại 12 và 14
            if ($g->code == 13) {
                continue;
            }
            $groups[] = $g;
        }

        session()->put(['title' => 'CỔNG PHÂN CÔNG BẢO TRÌ']);
        return view('pages.assignment.maintenance.portal', compact('groups'));
    }

    public function index(Request $request)
    {
        //dd($request->all());
        $production_code = session('user')['production_code'];
        $reportedDate = $request->reportedDate ?? Carbon::now()->format('Y-m-d');
        $group_code   = $request->group_code ?? null;
        $dept_code    = ($group_code == 20) ? 'QA' : 'EN';
        $canEdit      = ($group_code !== 'EN_ALL');

        $startDate = Carbon::parse($reportedDate)->setTime(6, 0, 0);
        $endDate   = $startDate->copy()->addDays(1);

        // Pre-fetch tất cả assignment_personnel của ngày để tránh N+1 Query
        $allPersonnelData = DB::table('assignment_personnel')
            ->join('assignments as a', 'a.id', '=', 'assignment_personnel.assignment_id')
            ->whereDate('a.start', $reportedDate)
            ->where('a.deparment_code', $dept_code)
            ->where('a.active', 1)
            ->select('assignment_personnel.assignment_id', 'assignment_personnel.personnel_id', 'assignment_personnel.notification')
            ->get()
            ->groupBy('assignment_id');

        // 0. Lấy danh sách tổ bảo trì (type = 2)
        $rawGroups = DB::table('stage_groups')->where('type', 2)->orderBy('id')->get();
        $stage_groups = [];
        $stage_groups[] = (object)['code' => 'EN_ALL', 'name' => 'Kỹ thuật bảo trì'];
        foreach ($rawGroups as $g) {
            if ($g->code == 13) continue;
            $stage_groups[] = $g;
        }

        $currentGroup = collect($stage_groups)->where('code', $group_code)->first();

        // 1. Lấy danh sách các công việc bảo trì
        $rawTasksQuery = DB::table('stage_plan as sp')
            ->join('room as r', 'sp.resourceId', '=', 'r.id')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
            ->where('sp.stage_code', 8)
            ->where('sp.active', 1)
            ->whereBetween('sp.start', [$startDate, $endDate]);

        if ($group_code && $group_code !== 'EN_ALL') {
            $rawTasksQuery->where(function ($q) use ($group_code) {
                switch ($group_code) {
                    case 11: // Tổ Bảo Trì 1
                        $q->where(function ($sub) {
                            $sub->where('sp.code', 'like', '%TB%')->orWhere('sp.code', 'like', '%BT%')->orWhere('sp.code', 'like', '%\_8');
                        })->whereIn('r.deparment_code', ['PXV1', 'PXTN']);
                        break;
                    case 12: // Tổ Điện Lạnh B1
                        $q->where('sp.code', 'like', '%TI%')->whereIn('r.deparment_code', ['PXV1', 'PXTN']);
                        break;
                    case 14: // Tổ Điện Lạnh - Nước Tinh Khiết (B2)
                        $q->where('sp.code', 'like', '%TI%')->whereIn('r.deparment_code', ['PXV2', 'PXDN', 'PXVH']);
                        break;
                    case 15: // Tổ Bảo Trì (PXV2-PXDN)
                        $q->where(function ($sub) {
                            $sub->where('sp.code', 'like', '%TB%')->orWhere('sp.code', 'like', '%BT%')->orWhere('sp.code', 'like', '%\_8');
                        })->whereIn('r.deparment_code', ['PXV2', 'PXDN']);
                        break;
                    case 16: // Tổ Bảo Trì (PXVH)
                        $q->where(function ($sub) {
                            $sub->where('sp.code', 'like', '%TB%')->orWhere('sp.code', 'like', '%BT%')->orWhere('sp.code', 'like', '%\_8');
                        })->where('r.deparment_code', 'PXVH');
                        break;
                    case 20: // Tổ Hiệu chuẩn QA
                        $q->where('sp.code', 'like', '%HC%');
                        break;
                }
            });
        } elseif ($group_code === 'EN_ALL') {
            $rawTasksQuery->where('r.deparment_code', '!=', 'QA');
        } else if ($production_code) {
            $rawTasksQuery->where('r.deparment_code', $production_code);
        }

        $rawTasks = $rawTasksQuery->select(
            'sp.id as sp_id',
            'sp.start',
            'sp.end',
            'r.id as room_id',
            'r.code as room_code',
            'r.name as room_name',
            'sp.deparment_code as workshop_code',
            'r.group_code as group_code',
            'r.number_of_employes_on_sheet1',
            'r.number_of_employes_on_sheet2',
            'r.number_of_employes_on_sheet3',
            'r.number_of_employes_on_sheet4',
            'r.number_of_employes_on_sheet_regular',
            'qm.inst_id',
            'qm.inst_name',
            'qm.Eqp_name',
            'qm.block',
            'pm.batch'
        )
            ->orderBy('sp.start')
            ->orderBy('r.name')
            ->get();

        // 2. Gộp các task và lấy phân công kèm danh sách nhân viên
        $tasks = collect($rawTasks)->groupBy(function ($item) {
            return $item->start . '_' . $item->room_id;
        })->map(function ($group) use ($group_code, $dept_code, $canEdit, $allPersonnelData) {
            $first      = $group->first();
            $allSpIds   = $group->pluck('sp_id')->sort()->toArray();
            $spIdString = implode(',', $allSpIds);

            $minStart    = $group->min('start');
            $maxEnd      = $group->max('end');
            $timeDisplay = Carbon::parse($minStart)->format('H:i') . ' - ' . Carbon::parse($maxEnd)->format('H:i');
            $theoryDisplay = '';
            foreach ($group as $index => $item) {
                $stt = $index + 1;
                $typeCode  = explode('-', $item->block ?? '')[0];
                $typeLabel = match ($typeCode) {
                    'HC'    => 'HC',
                    'BT'    => 'BT',
                    'TI'    => 'TI',
                    default => 'BT'
                };
                $contentStr = "[{$typeLabel}] {$item->Eqp_name} ({$item->inst_name})" . ($item->inst_id ? " - Mã: {$item->inst_id}" : "");
                $timeDisp = Carbon::parse($item->start)->format('H:i') . '-' . Carbon::parse($item->end)->format('H:i');

                $copyBtn = $canEdit ? "<button class='btn btn-xs btn-primary btn-copy-plan' title='Chép mục này' style='position: absolute; right: 0; top: 0; padding: 0 4px; font-size: 10px; display: none;'> >></button>" : "";
                $theoryDisplay .= "<div class='plan-item mb-1 pb-1 border-bottom position-relative hover-show-btn' data-start='{$item->start}'><div class='plan-text' style='font-size: 0.8rem; line-height: 1.2;'><b>{$stt}. {$contentStr} <span class='time-text'>| ({$timeDisp})</span></b></div>{$copyBtn}</div>";
            }

            $assignments = DB::table('assignments')
                ->select('*')
                ->where('stage_plan_id', $spIdString)
                ->where('deparment_code', $dept_code)
                ->where('active', 1)
                ->orderBy('Sheet');

            if ($group_code && $group_code !== 'EN_ALL') {
                if ($group_code == 12 || $group_code == 13) {
                    $assignments->whereIn('stage_groups_code', [12, 13]);
                } else {
                    $assignments->where('stage_groups_code', $group_code);
                }
            }

            $assignments = $assignments->get();

            foreach ($assignments as $a) {
                $a->personnel_data      = $allPersonnelData->get($a->id) ?? collect();
                $a->start_time_display  = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display    = $a->end   ? Carbon::parse($a->end)->format('H:i')   : null;
            }

            // Đối với Kỹ Thuật Bảo Trì (EN_ALL), chỉ lọc và hiển thị các lịch đã có nhân sự
            if ($group_code === 'EN_ALL') {
                $assignments = $assignments->filter(function ($a) {
                    return count($a->personnel_data) > 0;
                });
                if ($assignments->isEmpty()) return null;
            }

            // Tự động tạo gợi ý nếu chưa có phân công (Không áp dụng cho EN_ALL)
            if ($group_code !== 'EN_ALL' && $assignments->isEmpty() && $group->isNotEmpty()) {
                $jobDescription = "";
                foreach ($group as $index => $item) {
                    $stt = $index + 1;
                    $typeCode = explode('-', $item->block ?? '')[0];
                    $typeLabel = match ($typeCode) {
                        'HC' => 'HC',
                        'BT' => 'BT',
                        'TI' => 'TI',
                        default => 'BT'
                    };
                    $contentStr = "[{$typeLabel}] {$item->Eqp_name} ({$item->inst_name})" . ($item->inst_id ? " - Mã: {$item->inst_id}" : "");
                    $jobDescription .= "{$stt}. {$contentStr}\n";
                }

                $startHour = (int)Carbon::parse($minStart)->format('H');
                $startMin = (int)Carbon::parse($minStart)->format('i');
                if ($startHour == 6 && $startMin == 0) {
                    $sheetCode = 1;
                } elseif ($startHour >= 14 && $startHour < 22) {
                    $sheetCode = 2;
                } elseif ($startHour >= 22 || $startHour < 6) {
                    $sheetCode = 3;
                } else {
                    $sheetCode = 4;
                }

                $assignments->push((object)[
                    'id' => null,
                    'Sheet' => $sheetCode,
                    'start' => Carbon::parse($minStart)->toDateTimeString(),
                    'end' => Carbon::parse($maxEnd)->toDateTimeString(),
                    'start_time_display' => Carbon::parse($minStart)->format('H:i'),
                    'end_time_display' => Carbon::parse($maxEnd)->format('H:i'),
                    'Job_description' => trim($jobDescription),
                    'personnel_data' => collect([(object)['personnel_id' => null, 'notification' => null]]),
                    'number_of_employes' => 1,
                    'is_scheduled' => false,
                    'is_foreign' => false
                ]);
            }

            return (object)[
                'sp_id'          => $spIdString,
                'room_id'        => $first->room_id,
                'room_code'      => $first->room_code,
                'room_name'      => $first->room_name,
                'workshop_code'  => $first->workshop_code,
                'group_code'     => $first->group_code,
                'number_of_employes_on_sheet1' => $first->number_of_employes_on_sheet1,
                'number_of_employes_on_sheet2' => $first->number_of_employes_on_sheet2,
                'number_of_employes_on_sheet3' => $first->number_of_employes_on_sheet3,
                'number_of_employes_on_sheet4' => $first->number_of_employes_on_sheet4,
                'number_of_employes_on_sheet_regular' => $first->number_of_employes_on_sheet_regular,
                'theory_display' => $theoryDisplay ?: '<span class="text-muted italic">Không có lịch</span>',
                'assignments'    => $assignments,
                'theory_start'   => Carbon::parse($minStart)->format('H:i'),
                'theory_end'     => Carbon::parse($maxEnd)->format('H:i'),
            ];
        })->filter()->values();

        // 2b. Lấy thêm các công việc "Ngoài lịch" (stage_plan_id is NULL/Empty)
        $extraQuery = DB::table('assignments as ma')
            ->leftJoin('room', 'ma.room_id', '=', 'room.id')
            ->where('ma.deparment_code', $dept_code)
            ->where(function ($q) {
                $q->whereNull('ma.stage_plan_id')
                    ->orWhere('ma.stage_plan_id', '')
                    ->orWhere('ma.stage_plan_id', 'like', 'EXT_%');
            })
            ->whereDate('ma.start', $reportedDate)
            ->where('ma.active', 1)
            ->select('ma.*', 'room.name as room_name', 'room.code as room_code', 'room.deparment_code as workshop_code', 'room.group_code as group_code');

        if ($group_code && $group_code !== 'EN_ALL') {
            $extraQuery->where(function ($q) use ($group_code) {
                if ($group_code == 12 || $group_code == 13) {
                    $q->whereIn('ma.stage_groups_code', [12, 13]);
                } else {
                    $q->where('ma.stage_groups_code', $group_code);
                }
            });
        } elseif ($group_code === 'EN_ALL') {
            // EN_ALL đã có dept_code = EN, không cần filter thêm group_code
        }

        // Group by stage_plan_id (EXT_...) if it exists, otherwise by room_id
        $extraAssignments = $extraQuery->get()->groupBy(function ($item) {
            return $item->stage_plan_id ?: ('ROOM_' . $item->room_id);
        });

        $mappedExtra = $extraAssignments->map(function ($group, $groupKey) use ($group_code, $allPersonnelData) {
            $first = $group->first();
            foreach ($group as $a) {
                $a->personnel_data     = $allPersonnelData->get($a->id) ?? collect();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display   = $a->end   ? Carbon::parse($a->end)->format('H:i')   : null;
            }

            // Đối với Kỹ Thuật Bảo Trì (EN_ALL), chỉ lọc và hiển thị các lịch đã có nhân sự
            if ($group_code === 'EN_ALL') {
                $group = $group->filter(function ($a) {
                    return count($a->personnel_data) > 0;
                });
                if ($group->isEmpty()) return null;
            }

            return (object)[
                'sp_id'          => str_starts_with($groupKey, 'ROOM_') ? 'EXT_' . $groupKey : $groupKey,
                'room_id'        => $first->room_id,
                'room_code'      => $first->room_code,
                'room_name'      => $first->work_location ?? ($first->room_name ?? 'Công tác khác'),
                'workshop_code'  => $first->workshop_code,
                'group_code'     => $first->group_code,
                'number_of_employes_on_sheet1' => $first->number_of_employes_on_sheet1 ?? 1,
                'number_of_employes_on_sheet2' => $first->number_of_employes_on_sheet2 ?? 1,
                'number_of_employes_on_sheet3' => $first->number_of_employes_on_sheet3 ?? 1,
                'number_of_employes_on_sheet4' => $first->number_of_employes_on_sheet4 ?? 1,
                'number_of_employes_on_sheet_regular' => $first->number_of_employes_on_sheet_regular ?? 1,
                'theory_display' => '<span class="text-danger font-weight-bold">NA</span>',
                'assignments'    => $group,
                'theory_start'   => '07:15',
                'theory_end'     => '16:00',
            ];
        })->filter()->values();

        $tasks = $tasks->concat($mappedExtra);

        $personnelQuery = DB::table('employees as e')
            ->where('e.active', 1)
            ->where(function ($q) {
                $q->whereNull('e.resign')
                  ->orWhere('e.resign', 0);
            });

        if ($group_code) {
            $personnelQuery->whereExists(function ($query) use ($group_code) {
                $query->select(DB::raw(1))
                    ->from('employee_assignments as ea2')
                    ->leftJoin('stage_groups as sg', 'ea2.group_id', '=', 'sg.id')
                    ->whereColumn('ea2.employees_id', 'e.id')
                    ->where(function ($q) use ($group_code) {
                        if ($group_code === 'EN_ALL') {
                            $q->where('ea2.production_code', 'EN');
                        } elseif ($group_code == 20) {
                            $q->where('ea2.production_code', 'QA');
                        } else {
                            $q->where('sg.code', $group_code)
                                ->orWhere('ea2.group_id', $group_code);
                        }
                    })
                    ->where('ea2.active', 1);
            });
        }

        $personnel = $personnelQuery->select('e.*')
            ->addSelect(DB::raw("(SELECT GROUP_CONCAT(CONCAT(room_id, ':', level) SEPARATOR ',') FROM employee_assignments WHERE employees_id = e.id AND active = 1 AND room_id IS NOT NULL AND room_id > 0) as allowed_rooms_with_levels"))
            ->orderBy('e.name')
            ->get();

        $personnelInfo = $personnel->keyBy('id');
        $personnelSkills = $personnel->pluck('allowed_rooms_with_levels', 'id');
        $allowedPersonnelCodes = $personnel->pluck('code')->toArray();

        $rooms     = DB::table('room')->orderBy('code')->get();
        session()->put(['title' => 'PHÂN CÔNG CÔNG VIỆC']);

        $dbAssignments = $this->getDbAssignments($reportedDate);

        return view('pages.assignment.maintenance.index', [
            'tasks'        => $tasks,
            'reportedDate' => $reportedDate,
            'personnel'    => $personnel,
            'rooms'        => $rooms,
            'stage_groups' => $stage_groups,
            'group_code'   => $group_code,
            'personnelInfo' => $personnelInfo,
            'personnelSkills' => $personnelSkills,
            'allowedPersonnelCodes' => $allowedPersonnelCodes,
            'canEdit'      => $canEdit,
            'currentGroup' => $currentGroup,
            'dbAssignments' => $dbAssignments,
        ]);
    }

    public function getPersonnelShifts(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $departmentId = $request->department;

        $url = "http://s-webdev:5070/api/shifts/by-department?month={$month}&year={$year}&department={$departmentId}";

        try {
            $data = file_get_contents($url);
            $personnelData = json_decode($data, true) ?: [];

            if (is_array($personnelData)) {
                // Filter out resigned employees
                $resignedCodes = DB::table('employees')->where('resign', 1)->pluck('code')->toArray();
                $resignedCodesSet = array_flip($resignedCodes);
                $filteredPersonnelData = [];
                foreach ($personnelData as $person) {
                    $code = $person['employeeId'] ?? $person['code'] ?? null;
                    if ($code && isset($resignedCodesSet[$code])) {
                        continue;
                    }
                    $filteredPersonnelData[] = $person;
                }
                $personnelData = $filteredPersonnelData;
                // Lấy dữ liệu đi ca của tháng sau (month + 1) để điền cho day21 - day31
                $nextMonth = intval($month) + 1;
                $nextYear = intval($year);
                if ($nextMonth > 12) {
                    $nextMonth = 1;
                    $nextYear += 1;
                }

                $urlNext = "http://s-webdev:5070/api/shifts/by-department?month={$nextMonth}&year={$nextYear}&department={$departmentId}";
                $personnelDataNext = [];
                try {
                    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                    $dataNext = @file_get_contents($urlNext, false, $ctx);
                    if ($dataNext) {
                        $personnelDataNext = json_decode($dataNext, true) ?: [];
                    }
                } catch (\Exception $exNext) {
                    // Bỏ qua lỗi lấy dữ liệu tháng tiếp theo nếu chưa có lịch
                }

                // Index nhân sự tháng tiếp theo theo employeeId / code
                $nextMonthEmployees = [];
                foreach ($personnelDataNext as $person) {
                    $code = $person['employeeId'] ?? $person['code'] ?? null;
                    if ($code) {
                        $nextMonthEmployees[$code] = $person;
                    }
                }

                // Lấy thông tin hasAssignment từ bảng employees local
                $localEmployees = DB::table('employees')->select('code', 'hasAssignment')->get()->keyBy('code');

                foreach ($personnelData as &$person) {
                    $code = $person['employeeId'] ?? $person['code'] ?? null;

                    // Ghép logic: day1-day20 từ tháng $month, day21-day31 từ tháng $month+1
                    $originalDays = $person['days'] ?? [];
                    $newDays = [];

                    for ($i = 1; $i <= 20; $i++) {
                        $dayKey = 'day' . $i;
                        $newDays[$dayKey] = $originalDays[$dayKey] ?? null;
                    }

                    if ($code && isset($nextMonthEmployees[$code])) {
                        $nextPersonDays = $nextMonthEmployees[$code]['days'] ?? [];
                        for ($i = 21; $i <= 31; $i++) {
                            $dayKey = 'day' . $i;
                            $newDays[$dayKey] = $nextPersonDays[$dayKey] ?? null;
                        }
                    } else {
                        // Fallback: nếu không lấy được tháng sau thì giữ nguyên dữ liệu gốc
                        for ($i = 21; $i <= 31; $i++) {
                            $dayKey = 'day' . $i;
                            $newDays[$dayKey] = $originalDays[$dayKey] ?? null;
                        }
                    }

                    $person['days'] = $newDays;

                    // Gán hasAssignment
                    if ($code && isset($localEmployees[$code])) {
                        $person['hasAssignment'] = $localEmployees[$code]->hasAssignment;
                    } else {
                        $person['hasAssignment'] = 1; // Mặc định là 1 (có sắp lịch)
                    }
                }
            }

            return response()->json($personnelData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateHasAssignment(Request $request)
    {
        try {
            $code = $request->code;
            $hasAssignment = $request->hasAssignment ? 1 : 0;

            DB::table('employees')
                ->where('code', $code)
                ->update(['hasAssignment' => $hasAssignment, 'updated_at' => now()]);

            return response()->json(['success' => true, 'message' => 'Đã cập nhật trạng thái']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $spIdString       = $request->sp_id;
        $room_id          = $request->room_id ?: null;
        $work_location    = null;

        if ($room_id !== null && !is_numeric($room_id)) {
            $work_location = $room_id;
            $room_id = null;
        }

        $reportedDate     = $request->reportedDate;
        $group_code        = $request->group_code;
        $stage_groups_code = $request->stage_groups_code ?? null;

        // Ưu tiên dùng group_code truyền từ view để đảm bảo lưu đúng tổ đang làm việc
        $final_group_code  = $group_code ?: $stage_groups_code;
        $dept_code         = ($final_group_code == 20) ? 'QA' : 'EN';
        $assignments_data  = $request->assignments ?? [];

        try {
            DB::beginTransaction();

            $deleteQuery = DB::table('assignments')
                ->where('stage_plan_id', $spIdString)
                ->where('room_id', $room_id)
                ->where('deparment_code', $dept_code)
                ->where('active', 1);
            if ($final_group_code) {
                if ($final_group_code == 12 || $final_group_code == 13) {
                    $deleteQuery->whereIn('stage_groups_code', [12, 13]);
                } else {
                    $deleteQuery->where('stage_groups_code', $final_group_code);
                }
            }
            $deleteQuery->update(['active' => 0, 'updated_at' => now()]);

            $prodGroups = [
                1 => "Trung Tâm Cân",
                3 => "Pha Chế",
                4 => "Văn Phòng",
                5 => "Định Hình",
                6 => "Bao Phim",
                7 => "ĐGSC",
                8 => "ĐGTC",
                9 => "VSCN + Kho BTP"
            ];

            if (!empty($assignments_data)) {
                foreach ($assignments_data as $row) {
                    $p_data = $row['personnel_list'] ?? [];
                    if (empty($p_data)) continue;

                    $startDt = $reportedDate . ' ' . $row['start_time'];
                    $endDt = $reportedDate . ' ' . $row['end_time'];
                    if ($row['end_time'] < $row['start_time']) {
                        $endDt = Carbon::parse($endDt)->addDay()->format('Y-m-d H:i:s');
                    }

                    // Kiểm tra trùng lịch của từng nhân sự
                    foreach ($p_data as $p) {
                        if (empty($p['personnel_id'])) continue;

                        $overlap = DB::table('assignments as a')
                            ->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')
                            ->leftJoin('room as r', 'a.room_id', '=', 'r.id')
                            ->leftJoin('stage_groups as sg', 'a.stage_groups_code', '=', 'sg.code')
                            ->leftJoin('employees as e', 'ap.personnel_id', '=', 'e.id')
                            ->where('ap.personnel_id', $p['personnel_id'])
                            ->where('a.active', 1)
                            ->where('a.start', '<', $endDt)
                            ->where('a.end', '>', $startDt)
                            ->select('a.start', 'a.end', 'a.stage_groups_code', 'r.name as room_name', 'sg.name as group_name', 'a.deparment_code', 'e.name as employee_name')
                            ->first();

                        if ($overlap) {
                            $grpName = $overlap->group_name;
                            if ($overlap->deparment_code == 'EN') {
                                $grpName = 'Bảo trì';
                            } elseif ($overlap->deparment_code == 'QA') {
                                $grpName = 'Hiệu chuẩn';
                            } else {
                                $grpName = $prodGroups[$overlap->stage_groups_code] ?? $overlap->group_name ?? ('Tổ ' . $overlap->stage_groups_code);
                            }
                            $roomName = $overlap->room_name ?: 'Công tác khác';
                            $timeRange = Carbon::parse($overlap->start)->format('H:i') . ' - ' . Carbon::parse($overlap->end)->format('H:i');
                            
                            throw new \Exception("Nhân sự {$overlap->employee_name} đã được phân công tại {$grpName} ({$roomName}) trong khoảng thời gian {$timeRange}.");
                        }
                    }

                    $assignmentId = DB::table('assignments')->insertGetId([
                        'stage_plan_id'     => $spIdString,
                        'room_id'           => $room_id,
                        'work_location'     => $work_location,
                        'deparment_code'    => $dept_code,
                        'stage_groups_code' => $final_group_code,
                        'Sheet'             => $row['shift'],
                        'start'             => $startDt,
                        'end'               => $endDt,
                        'Job_description'   => isset($row['job_description']) ? trim($row['job_description']) : null,
                        'assigned_by'       => session('user')['userName'] ?? 'System',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                        'active'            => 1
                    ]);

                    $unique_p_data = collect($p_data)->unique('personnel_id');

                    foreach ($unique_p_data as $p) {
                        if (empty($p['personnel_id'])) continue;
                        DB::table('assignment_personnel')->insert([
                            'assignment_id' => $assignmentId,
                            'personnel_id' => $p['personnel_id'],
                            'notification' => $p['notification'] ?? null,
                            'operation_type' => $p['operation_type'] ?? 'thủ công'
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã lưu phân công']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('assignments')
                ->where('id', $id)
                ->update(['active' => 0, 'updated_at' => now()]);

            return response()->json(['success' => true, 'message' => 'Đã xóa ca này']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    public function publicView(Request $request)
    {
        $group_code   = $request->group_code ?? 'EN_ALL'; // Mặc định Kỹ thuật bảo trì (Tất cả EN)
        $reportedDate = $request->reportedDate ?? Carbon::now()->format('Y-m-d');

        $rawGroups = DB::table('stage_groups')->where('type', 2)->orderBy('id')->get();
        $stage_groups = [];
        $stage_groups[] = (object)['code' => 'EN_ALL', 'name' => 'Kỹ thuật bảo trì'];
        foreach ($rawGroups as $g) {
            if ($g->code == 13) continue;
            $stage_groups[] = $g;
        }

        $dept_code    = ($group_code == 20) ? 'QA' : 'EN';

        $startDate = Carbon::parse($reportedDate)->setTime(6, 0, 0);
        $endDate = $startDate->copy()->addDays(1);

        // Pre-fetch tất cả assignment_personnel của ngày để tránh N+1 Query
        $allPersonnelData = DB::table('assignment_personnel')
            ->join('assignments as a', 'a.id', '=', 'assignment_personnel.assignment_id')
            ->whereDate('a.start', $reportedDate)
            ->where('a.deparment_code', $dept_code)
            ->where('a.active', 1)
            ->select('assignment_personnel.assignment_id', 'assignment_personnel.personnel_id', 'assignment_personnel.notification')
            ->get()
            ->groupBy('assignment_id');

        $rawTasksQuery = DB::table('stage_plan as sp')
            ->join('room as r', 'sp.resourceId', '=', 'r.id')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
            ->where('sp.stage_code', 8)
            ->where('sp.active', 1)
            ->whereBetween('sp.start', [$startDate, $endDate]);

        if ($group_code && $group_code !== 'EN_ALL') {
            $rawTasksQuery->where(function ($q) use ($group_code) {
                switch ($group_code) {
                    case 11: // Tổ Bảo Trì 1
                        $q->where(function ($sub) {
                            $sub->where('sp.code', 'like', '%TB%')->orWhere('sp.code', 'like', '%BT%')->orWhere('sp.code', 'like', '%\_8');
                        })->whereIn('r.deparment_code', ['PXV1', 'PXTN']);
                        break;
                    case 12: // Tổ Điện Lạnh B1
                        $q->where('sp.code', 'like', '%TI%')->whereIn('r.deparment_code', ['PXV1', 'PXTN']);
                        break;
                    case 14: // Tổ Điện Lạnh - Nước tinh khiết B2
                        $q->where('sp.code', 'like', '%TI%')->whereIn('r.deparment_code', ['PXV2', 'PXDN', 'PXVH']);
                        break;
                    case 15: // Tổ Bảo Trì (PXV2-PXDN)
                        $q->where(function ($sub) {
                            $sub->where('sp.code', 'like', '%TB%')->orWhere('sp.code', 'like', '%BT%')->orWhere('sp.code', 'like', '%\_8');
                        })->whereIn('r.deparment_code', ['PXV2', 'PXDN']);
                        break;
                    case 16: // Tổ Bảo Trì (PXVH)
                        $q->where(function ($sub) {
                            $sub->where('sp.code', 'like', '%TB%')->orWhere('sp.code', 'like', '%BT%')->orWhere('sp.code', 'like', '%\_8');
                        })->where('r.deparment_code', 'PXVH');
                        break;
                    case 20: // Tổ Hiệu chuẩn QA
                        $q->where('sp.code', 'like', '%HC%');
                        break;
                }
            });
        }

        // Nếu là EN_ALL, lọc theo deparment_code của assignments
        if ($group_code === 'EN_ALL') {
            $rawTasksQuery->where('r.deparment_code', '!=', 'QA');
        }

        $rawTasks = $rawTasksQuery->select(
            'sp.id as sp_id',
            'sp.start',
            'sp.end',
            'r.id as room_id',
            'r.code as room_code',
            'r.name as room_name',
            'r.group_code as group_code',
            'sp.deparment_code as workshop_code',
            'qm.inst_id',
            'qm.inst_name',
            'qm.Eqp_name',
            'qm.block',
            'pm.batch'
        )
            ->orderBy('sp.start')
            ->orderBy('r.name')
            ->get();

        $tasks = collect($rawTasks)->groupBy(function ($item) {
            return $item->start . '_' . $item->room_id;
        })->map(function ($group) use ($group_code, $dept_code, $allPersonnelData) {
            $first = $group->first();
            $allSpIds = $group->pluck('sp_id')->sort()->toArray();
            $spIdString = implode(',', $allSpIds);

            $minStart = $group->min('start');
            $maxEnd = $group->max('end');
            $timeDisplay = Carbon::parse($minStart)->format('H:i') . ' - ' . Carbon::parse($maxEnd)->format('H:i');
            $theoryDisplay = '';
            foreach ($group as $index => $item) {
                $stt = $index + 1;
                $typeCode = explode('-', $item->block ?? '')[0];
                $typeLabel = match ($typeCode) {
                    'HC' => 'HC',
                    'BT' => 'BT',
                    'TI' => 'TI',
                    default => 'BT'
                };
                $contentStr = "[{$typeLabel}] {$item->Eqp_name} ({$item->inst_name})" . ($item->inst_id ? " - Mã: {$item->inst_id}" : "");
                $timeDisp = Carbon::parse($item->start)->format('H:i') . '-' . Carbon::parse($item->end)->format('H:i');

                $copyBtn = ($group_code !== 'EN_ALL') ? "<button class='btn btn-xs btn-primary btn-copy-plan' title='Chép mục này' style='position: absolute; right: 0; top: 0; padding: 0 4px; font-size: 10px; display: none;'> >></button>" : "";
                $theoryDisplay .= "<div class='plan-item mb-1 pb-1 border-bottom position-relative hover-show-btn' data-start='{$item->start}'><div class='plan-text' style='font-size: 0.8rem; line-height: 1.2;'><b>{$stt}. {$contentStr} <span class='time-text'>| ({$timeDisp})</span></b></div>{$copyBtn}</div>";
            }

            $assignments = DB::table('assignments')
                ->select('*')
                ->where('stage_plan_id', $spIdString)
                ->where('deparment_code', $dept_code)
                ->where('active', 1)
                ->orderBy('Sheet');

            if ($group_code && $group_code !== 'EN_ALL') {
                if ($group_code == 12 || $group_code == 13) {
                    $assignments->whereIn('stage_groups_code', [0, 12, 13]);
                } else {
                    $assignments->whereIn('stage_groups_code', [0, $group_code]);
                }
            }

            $assignments = $assignments->get();

            foreach ($assignments as $a) {
                $a->personnel_data = $allPersonnelData->get($a->id) ?? collect();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
            }

            // Chỉ giữ lại các phân công đã có nhân sự
            $assignments = $assignments->filter(function ($a) {
                return count($a->personnel_data) > 0;
            });

            // Bỏ qua gợi ý tự động, chỉ hiển thị lịch thực tế đã có nhân sự
            if ($assignments->isEmpty()) {
                return null;
            }

            return (object)[
                'sp_id'          => $spIdString,
                'room_id'        => $first->room_id,
                'room_code'      => $first->room_code,
                'room_name'      => $first->room_name,
                'workshop_code'  => $first->workshop_code,
                'group_code'     => $first->group_code,
                'theory_display' => $theoryDisplay,
                'assignments'    => $assignments,
                'theory_start'   => Carbon::parse($minStart)->format('H:i'),
                'theory_end'     => Carbon::parse($maxEnd)->format('H:i'),
            ];
        })->filter()->values();

        $extraAssignmentsQuery = DB::table('assignments as ma')
            ->leftJoin('room', 'ma.room_id', '=', 'room.id')
            ->where('ma.deparment_code', $dept_code)
            ->where(function ($q) {
                $q->whereNull('ma.stage_plan_id')
                    ->orWhere('ma.stage_plan_id', '')
                    ->orWhere('ma.stage_plan_id', 'like', 'EXT_%');
            })
            ->whereDate('ma.start', $reportedDate)
            ->where('ma.active', 1)
            ->select('ma.*', 'room.name as room_name', 'room.code as room_code', 'room.deparment_code as workshop_code', 'room.group_code as group_code');

        if ($group_code !== 'EN_ALL') {
            $extraAssignmentsQuery->whereIn('ma.stage_groups_code', [0, $group_code]);
        }

        $extraAssignments = $extraAssignmentsQuery->get()
            ->groupBy(function ($item) {
                return $item->stage_plan_id ?: ('ROOM_' . $item->room_id);
            });

        foreach ($extraAssignments as $roomId => $group) {
            $first = $group->first();
            foreach ($group as $a) {
                $a->personnel_data = $allPersonnelData->get($a->id) ?? collect();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
            }

            // Chỉ giữ lại các phân công đã có nhân sự
            $group = $group->filter(function ($a) {
                return count($a->personnel_data) > 0;
            });

            if ($group->isEmpty()) continue;

            $tasks->push((object)[
                'sp_id'          => $first->stage_plan_id ?? '',
                'room_id'        => $first->room_id,
                'room_code' => $first->room_code,
                'room_name' => $first->work_location ?? ($first->room_name ?? 'Công tác khác'),
                'workshop_code' => $first->workshop_code,
                'group_code' => $first->group_code,
                'theory_display' => '<span class="text-danger font-weight-bold">NA</span>',
                'assignments' => $group,
                'theory_start' => '07:15',
                'theory_end' => '16:00',
            ]);
        }

        $personnelQuery = DB::table('employees as e')
            ->where('e.active', 1)
            ->whereExists(function ($query) use ($dept_code) {
                $query->select(DB::raw(1))
                    ->from('employee_assignments as ea')
                    ->whereColumn('ea.employees_id', 'e.id')
                    ->where('ea.production_code', $dept_code)
                    ->where('ea.active', 1);
            });

        if ($group_code && $group_code !== 'EN_ALL') {
            $personnelQuery->whereExists(function ($query) use ($group_code) {
                $query->select(DB::raw(1))
                    ->from('employee_assignments as ea2')
                    ->leftJoin('stage_groups as sg', 'ea2.group_id', '=', 'sg.id')
                    ->whereColumn('ea2.employees_id', 'e.id')
                    ->where(function ($q) use ($group_code) {
                        $q->where('sg.code', $group_code)
                            ->orWhere('ea2.group_id', $group_code);
                    })
                    ->where('ea2.active', 1);
            });
        }

        $allowedPersonnelCodes = $personnelQuery->pluck('e.code')->toArray();

        $personnel = DB::table('employees')->where('active', 1)->orderBy('name')->get();

        $dbAssignments = $this->getDbAssignments($reportedDate);

        return view('pages.assignment.maintenance.publicView', [
            'tasks'                 => $tasks,
            'reportedDate'          => $reportedDate,
            'group_code'            => $group_code,
            'stage_groups'          => $stage_groups,
            'personnel'             => $personnel,
            'allowedPersonnelCodes' => $allowedPersonnelCodes,
            'dbAssignments'         => $dbAssignments,
        ]);
    }

    private function getDbAssignments($reportedDate)
    {
        $dailyAssignments = DB::table('assignments as a')
            ->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')
            ->leftJoin('room as r', 'a.room_id', '=', 'r.id')
            ->leftJoin('stage_groups as sg', 'a.stage_groups_code', '=', 'sg.code')
            ->whereDate('a.start', $reportedDate)
            ->where('a.active', 1)
            ->select(
                'ap.personnel_id',
                'a.id as assignment_id',
                'a.start',
                'a.end',
                'a.stage_groups_code',
                'a.deparment_code',
                'a.stage_plan_id',
                'a.work_location',
                'sg.name as group_name',
                'r.name as room_name',
                'r.code as room_code'
            )
            ->get();

        $prodGroups = [
            1 => "Trung Tâm Cân",
            3 => "Pha Chế",
            4 => "Văn Phòng",
            5 => "Định Hình",
            6 => "Bao Phim",
            7 => "ĐGSC",
            8 => "ĐGTC",
            9 => "VSCN + Kho BTP"
        ];

        $dbAssignments = [];
        foreach ($dailyAssignments as $ass) {
            $pId = $ass->personnel_id;
            $startDisplay = $ass->start ? Carbon::parse($ass->start)->format('H:i') : '';
            $endDisplay = $ass->end ? Carbon::parse($ass->end)->format('H:i') : '';

            $groupName = '';
            if ($ass->deparment_code == 'EN') {
                $groupName = 'Bảo trì';
            } elseif ($ass->deparment_code == 'QA') {
                $groupName = 'Hiệu chuẩn';
            } else {
                $groupName = $prodGroups[$ass->stage_groups_code] ?? $ass->group_name ?? ('Tổ ' . $ass->stage_groups_code);
            }

            $spId = $ass->stage_plan_id ?: ('EXT_EXISTING_' . $ass->assignment_id);
            $roomName = $ass->work_location ?? ($ass->room_name ?: 'Công tác khác');

            $dbAssignments[$pId][] = (object) [
                'assignment_id' => $ass->assignment_id,
                'sp_id' => $spId,
                'room_code' => $ass->room_code,
                'room_name' => $roomName,
                'start' => $startDisplay,
                'end' => $endDisplay,
                'stage_groups_code' => $ass->stage_groups_code,
                'deparment_code' => $ass->deparment_code,
                'group_name' => $groupName
            ];
        }

        return $dbAssignments;
    }
}
