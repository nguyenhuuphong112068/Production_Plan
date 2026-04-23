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
        $mergedGroup = null;

        foreach ($rawGroups as $g) {
            if ($g->code == 12 || $g->code == 13) {
                if (!$mergedGroup) {
                    $mergedGroup = $g;
                    $mergedGroup->name = "Tổ Điện Lạnh - Nước Tình Khiết (B1)";
                    $groups[] = $mergedGroup;
                }
                continue;
            }
            $groups[] = $g;
        }

        // Thêm lại Tổ HC Thiết Bị (QA) - nếu không có trong DB
        $hasQA = collect($groups)->contains('code', 17);
        if (!$hasQA) {
            $groups[] = (object)[
                'id' => 17,
                'code' => 17,
                'name' => 'Tổ HC Thiết Bị (QA)',
                'type' => 2
            ];
        }

        session()->put(['title' => 'CỔNG PHÂN CÔNG BẢO TRÌ']);
        return view('pages.assignment.maintenance.portal', compact('groups'));
    }

    public function index(Request $request)
    {
        $production_code = session('user')['production_code'];
        $reportedDate = $request->reportedDate ?? Carbon::now()->format('Y-m-d');
        $group_code   = $request->group_code ?? null;

        $startDate = Carbon::parse($reportedDate)->setTime(6, 0, 0);
        $endDate   = $startDate->copy()->addDays(1);

        // 0. Lấy danh sách tổ bảo trì (type = 2)
        $stage_groups = DB::table('stage_groups')->where('type', 2)->orderBy('id')->get();

        // 1. Lấy danh sách các công việc bảo trì lý thuyết
        $rawTasksQuery = DB::table('stage_plan as sp')
            ->join('room as r', 'sp.resourceId', '=', 'r.id')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
            ->where('sp.stage_code', 8)
            ->where('sp.tank', 1)
            ->whereBetween('sp.start', [$startDate, $endDate]);

        if ($group_code) {
            $rawTasksQuery->where(function ($q) use ($group_code) {
                switch ($group_code) {
                    case 11: // Tổ Bảo Trì (B1)
                        $q->where('sp.code', 'like', '%TB%')->whereIn('r.deparment_code', ['PXV1', 'PXDN']);
                        break;
                    case 12: // Tổ Điện Lạnh (B1)
                    case 13: // Tổ HT Nước Tinh Khiết (B1)
                        $q->where('sp.code', 'like', '%TI%')->whereIn('r.deparment_code', ['PXV1', 'PXDN']);
                        break;
                    case 15: // Tổ Bảo Trì (PXV2-PXDN)
                        $q->where('sp.code', 'like', '%TB%')->whereIn('r.deparment_code', ['PXV2', 'PXDN']);
                        break;
                    case 16: // Tổ Bảo Trì (PXVH)
                        $q->where('sp.code', 'like', '%TB%')->where('r.deparment_code', 'PXVH');
                        break;
                    case 14: // Tổ Điện Lạnh - Nước Tinh Khiết (B2)
                        $q->where('sp.code', 'like', '%TI%')->whereIn('r.deparment_code', ['PXV2', 'PXDN', 'PXVH']);
                        break;
                    case 17: // Tổ HC Thiết Bị (QA)
                        $q->where('sp.code', 'like', '%HC%')->whereIn('r.deparment_code', ['PXV1', 'PXV2', 'PXDN', 'PXVH', 'PXTN']);
                        break;
                }
            });
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
        })->map(function ($group) use ($group_code) {
            $first      = $group->first();
            $allSpIds   = $group->pluck('sp_id')->sort()->toArray();
            $spIdString = implode(',', $allSpIds);

            $minStart    = $group->min('start');
            $maxEnd      = $group->max('end');
            $timeDisplay = Carbon::parse($minStart)->format('H:i') . ' - ' . Carbon::parse($maxEnd)->format('H:i');
            $theoryDisplay = '';
            foreach ($group as $item) {
                $typeCode  = explode('-', $item->block ?? '')[0];
                $typeLabel = match ($typeCode) {
                    'HC'    => 'HC',
                    'BT'    => 'BT',
                    'TI'    => 'TI',
                    default => 'BT'
                };
                $theoryDisplay .= "<div><b>[{$typeLabel}]</b> {$item->Eqp_name} ({$item->inst_name}) " . ($item->inst_id ? "- Mã: {$item->inst_id}" : "") . "</div>";
            }
            $theoryDisplay .= "<div class='mt-1 text-primary'><i>($timeDisplay)</i></div>";

            $assignmentQuery = DB::table('assignments')
                ->where('stage_plan_id', $spIdString)
                ->where('deparment_code', 'MMS')
                ->where('active', 1)
                ->orderBy('Sheet');

            if ($group_code) {
                if ($group_code == 12 || $group_code == 13) {
                    $assignmentQuery->whereIn('stage_groups_code', [12, 13]);
                } else {
                    $assignmentQuery->where('stage_groups_code', $group_code);
                }
            }

            $assignments = $assignmentQuery->get();

            foreach ($assignments as $a) {
                $a->personnel_data      = DB::table('assignment_personnel')
                    ->where('assignment_id', $a->id)
                    ->select('personnel_id', 'notification')->get();
                $a->start_time_display  = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display    = $a->end   ? Carbon::parse($a->end)->format('H:i')   : null;
            }

            return (object)[
                'sp_id'          => $spIdString,
                'room_id'        => $first->room_id,
                'room_code'      => $first->room_code,
                'room_name'      => $first->room_name,
                'theory_display' => $theoryDisplay,
                'assignments'    => $assignments,
                'theory_start'   => Carbon::parse($minStart)->format('H:i'),
                'theory_end'     => Carbon::parse($maxEnd)->format('H:i'),
            ];
        })->values();

        // 2b. Lấy thêm các công việc "Ngoài lịch" (stage_plan_id is NULL/Empty)
        $extraQuery = DB::table('assignments as ma')
            ->leftJoin('room', 'ma.room_id', '=', 'room.id')
            ->where('ma.deparment_code', 'MMS')
            ->where(function ($q) {
                $q->whereNull('ma.stage_plan_id')->orWhere('ma.stage_plan_id', '');
            })
            ->whereDate('ma.start', $reportedDate)
            ->where('ma.active', 1)
            ->select('ma.*', 'room.name as room_name', 'room.code as room_code');

        if ($group_code) {
            $extraQuery->where(function ($q) use ($group_code) {
                if ($group_code == 12 || $group_code == 13) {
                    $q->whereIn('ma.stage_groups_code', [12, 13]);
                } else {
                    $q->where('ma.stage_groups_code', $group_code);
                }
            });
        }

        $extraAssignments = $extraQuery->get()->groupBy('room_id');

        foreach ($extraAssignments as $roomId => $group) {
            $first = $group->first();
            foreach ($group as $a) {
                $a->personnel_data     = DB::table('assignment_personnel')
                    ->where('assignment_id', $a->id)
                    ->select('personnel_id', 'notification')->get();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display   = $a->end   ? Carbon::parse($a->end)->format('H:i')   : null;
            }

            $tasks->push((object)[
                'sp_id'          => '',
                'room_id'        => $roomId,
                'room_code'      => $first->room_code,
                'room_name'      => $first->room_name,
                'theory_display' => '<span class="text-danger font-weight-bold">NA</span>',
                'assignments'    => $group,
                'theory_start'   => '07:15',
                'theory_end'     => '16:00',
            ]);
        }

        $personnel = DB::table('personnel')->where('active', 1)->orderBy('name')->get();
        $rooms     = DB::table('room')->orderBy('code')->get();
        session()->put(['title' => 'PHÂN CÔNG CÔNG VIỆC']);
        return view('pages.assignment.maintenance.index', [
            'tasks'        => $tasks,
            'reportedDate' => $reportedDate,
            'personnel'    => $personnel,
            'rooms'        => $rooms,
            'stage_groups' => $stage_groups,
            'group_code'   => $group_code,  // giá trị đang chọn (là stage_groups.code)
        ]);
    }

    public function store(Request $request)
    {
        $spIdString       = $request->sp_id;
        $room_id          = $request->room_id;
        $reportedDate     = $request->reportedDate;
        $stage_groups_code = $request->stage_groups_code ?? null;
        $assignments_data = $request->assignments ?? [];

        try {
            DB::beginTransaction();

            // Xóa mềm dữ liệu cũ của CHÍNH nhóm ID này, CHÍNH phòng này, và CHÍNH tổ này
            $deleteQuery = DB::table('assignments')
                ->where('stage_plan_id', $spIdString)
                ->where('room_id', $room_id)
                ->where('deparment_code', 'MMS')
                ->where('active', 1);
            if ($stage_groups_code) {
                if ($stage_groups_code == 12 || $stage_groups_code == 13) {
                    $deleteQuery->whereIn('stage_groups_code', [12, 13]);
                } else {
                    $deleteQuery->where('stage_groups_code', $stage_groups_code);
                }
            }
            $deleteQuery->update(['active' => 0, 'updated_at' => now()]);

            if (!empty($assignments_data)) {
                foreach ($assignments_data as $row) {
                    $p_data = $row['personnel_list'] ?? [];
                    if (empty($p_data)) continue;

                    // Xử lý thời gian
                    $startDt = $reportedDate . ' ' . $row['start_time'];
                    $endDt = $reportedDate . ' ' . $row['end_time'];
                    if ($row['end_time'] < $row['start_time']) {
                        $endDt = Carbon::parse($endDt)->addDay()->format('Y-m-d H:i:s');
                    }

                    $assignmentId = DB::table('assignments')->insertGetId([
                        'stage_plan_id'     => $spIdString,
                        'room_id'           => $room_id,
                        'deparment_code'    => 'MMS',
                        'stage_groups_code' => $stage_groups_code,
                        'Sheet'             => $row['shift'],
                        'start'             => $startDt,
                        'end'               => $endDt,
                        'Job_description'   => $row['job_description'] ?? null,
                        'assigned_by'       => session('user')['userName'] ?? 'System',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                        'active'            => 1
                    ]);

                    // Lọc để đảm bảo không chèn trùng personnel_id cho cùng một assignment
                    $unique_p_data = collect($p_data)->unique('personnel_id');

                    foreach ($unique_p_data as $p) {
                        if (empty($p['personnel_id'])) continue;
                        DB::table('assignment_personnel')->insert([
                            'assignment_id' => $assignmentId,
                            'personnel_id' => $p['personnel_id'],
                            'notification' => $p['notification'] ?? null
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
        // For public view, allow filtering by production_code, default to PXV1
        $production_code = $request->production_code ?? 'PXV1';
        $reportedDate = $request->reportedDate ?? Carbon::now()->format('Y-m-d');

        $startDate = Carbon::parse($reportedDate)->setTime(6, 0, 0);
        $endDate = $startDate->copy()->addDays(1);

        // 1. Lấy danh sách các công việc bảo trì lý thuyết
        $rawTasks = DB::table('stage_plan as sp')
            ->join('room as r', 'sp.resourceId', '=', 'r.id')
            ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->leftJoin('quota_maintenance as qm', 'sp.product_caterogy_id', '=', 'qm.id')
            ->where('r.deparment_code', $production_code)
            ->where('sp.stage_code', 8)
            ->where('sp.tank', 1)
            ->whereBetween('sp.start', [$startDate, $endDate])
            ->select(
                'sp.id as sp_id',
                'sp.start',
                'sp.end',
                'r.id as room_id',
                'r.code as room_code',
                'r.name as room_name',
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
        })->map(function ($group) {
            $first = $group->first();
            $allSpIds = $group->pluck('sp_id')->sort()->toArray();
            $spIdString = implode(',', $allSpIds);

            // ... (giữ nguyên logic tính toán thời gian và theoryDisplay)
            $minStart = $group->min('start');
            $maxEnd = $group->max('end');
            $timeDisplay = Carbon::parse($minStart)->format('H:i') . ' - ' . Carbon::parse($maxEnd)->format('H:i');
            $theoryDisplay = '';
            foreach ($group as $item) {
                $typeCode = explode('-', $item->block ?? '')[0];
                $typeLabel = match ($typeCode) {
                    'HC' => 'HC',
                    'BT' => 'BT',
                    'TI' => 'TI',
                    default => 'BT'
                };
                $theoryDisplay .= "<div><b>[{$typeLabel}]</b> {$item->Eqp_name} ({$item->inst_name}) " . ($item->inst_id ? "- Mã: {$item->inst_id}" : "") . "</div>";
            }
            $theoryDisplay .= "<div class='mt-1 text-primary'><i>($timeDisplay)</i></div>";

            $assignments = DB::table('assignments')
                ->where('stage_plan_id', $spIdString)
                ->where('deparment_code', 'MMS')
                ->where('active', 1)
                ->orderBy('Sheet')
                ->get();

            foreach ($assignments as $a) {
                $a->personnel_data = DB::table('assignment_personnel')
                    ->where('assignment_id', $a->id)
                    ->select('personnel_id', 'notification')->get();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
            }

            return (object)[
                'sp_id' => $spIdString,
                'room_id' => $first->room_id,
                'room_code' => $first->room_code,
                'room_name' => $first->room_name,
                'theory_display' => $theoryDisplay,
                'assignments' => $assignments,
                'theory_start' => Carbon::parse($minStart)->format('H:i'),
                'theory_end' => Carbon::parse($maxEnd)->format('H:i'),
            ];
        })->values();

        // 2b. Lấy thêm các công việc "Ngoài lịch" (stage_plan_id is NULL/Empty)
        $extraAssignments = DB::table('assignments as ma')
            ->leftJoin('room', 'ma.room_id', '=', 'room.id')
            ->where('ma.deparment_code', 'MMS')
            ->where(function ($q) {
                $q->whereNull('ma.stage_plan_id')->orWhere('ma.stage_plan_id', '');
            })
            ->whereDate('ma.start', $reportedDate)
            ->where('ma.active', 1)
            ->select('ma.*', 'room.name as room_name', 'room.code as room_code')
            ->get()
            ->groupBy('room_id');

        foreach ($extraAssignments as $roomId => $group) {
            $first = $group->first();
            foreach ($group as $a) {
                $a->personnel_data = DB::table('assignment_personnel')
                    ->where('assignment_id', $a->id)
                    ->select('personnel_id', 'notification')->get();
                $a->start_time_display = $a->start ? Carbon::parse($a->start)->format('H:i') : null;
                $a->end_time_display = $a->end ? Carbon::parse($a->end)->format('H:i') : null;
            }

            $tasks->push((object)[
                'sp_id' => '', // Đánh dấu là ngoài lịch
                'room_id' => $roomId,
                'room_code' => $first->room_code,
                'room_name' => $first->room_name,
                'theory_display' => '<span class="text-danger font-weight-bold">NA</span>',
                'assignments' => $group,
                'theory_start' => '07:15',
                'theory_end' => '16:00',
            ]);
        }

        $personnel = DB::table('personnel')->where('active', 1)->orderBy('name')->get();

        return view('pages.assignment.maintenance.publicView', [
            'tasks' => $tasks,
            'reportedDate' => $reportedDate,
            'personnel' => $personnel,
            'production_code' => $production_code
        ]);
    }
}
