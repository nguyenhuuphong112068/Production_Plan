<?php

namespace App\Http\Controllers\Pages\Schedual;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedualWarningController extends Controller
{
    public function index(Request $request)
    {
        $production = session('user')['production_code'];

        // 1. Không Đáp Ứng Ngày Cần Hàng Theo Kế Hoạch
        // Lô có stage_code == 7, start không null, và end > expected_date - 5 ngày
        $unmetPlans = DB::table('stage_plan')
            ->select(
                'plan_master.id',
                'plan_master.batch',
                'plan_master.expected_date',
                'plan_master.expected_date_change',
                'finished_product_category.finished_product_code',
                'product_name.name as product_name',
                DB::raw('MAX(stage_plan.end) as max_end'),
                DB::raw('MIN(stage_plan.start) as min_start')
            )
            ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->where('stage_plan.active', 1)
            ->where('stage_plan.finished', 0)
            ->where('stage_plan.stage_code', 7)
            ->whereNotNull('stage_plan.start')
            ->where('stage_plan.deparment_code', $production)
            ->whereRaw('DATE(stage_plan.end) > DATE_SUB(DATE(plan_master.expected_date), INTERVAL 5 DAY)')
            ->groupBy('plan_master.id', 'plan_master.batch', 'plan_master.expected_date', 'plan_master.expected_date_change', 'finished_product_category.finished_product_code', 'product_name.name')
            ->orderBy('plan_master.expected_date', 'asc')
            ->get();

        // 1b. Xem xét đề nghị đổi ngày KSC (đã đánh dấu expected_date_change)
        $proposedChanges = DB::table('stage_plan')
            ->select(
                'plan_master.id',
                'plan_master.batch',
                'plan_master.expected_date',
                'plan_master.expected_date_change',
                'finished_product_category.finished_product_code',
                'product_name.name as product_name',
                DB::raw('MAX(stage_plan.end) as max_end'),
                DB::raw('MIN(stage_plan.start) as min_start')
            )
            ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->where('stage_plan.active', 1)
            ->where('stage_plan.finished', 0)
            ->where('stage_plan.stage_code', 7)
            ->whereNotNull('stage_plan.start')
            ->where('stage_plan.deparment_code', $production)
            ->where('plan_master.expected_date_change', 1)
            ->whereRaw('DATE(stage_plan.end) > DATE_SUB(DATE(plan_master.expected_date), INTERVAL 5 DAY)')
            ->groupBy('plan_master.id', 'plan_master.batch', 'plan_master.expected_date', 'plan_master.expected_date_change', 'finished_product_category.finished_product_code', 'product_name.name')
            ->orderBy('plan_master.expected_date', 'asc')
            ->get();


        // 2. Cảnh Báo Ngày Đáp Ứng NL/BB
        $criticalChecks = [
            // [1,  3,  'after_weigth_date',         'Ngày có đủ NL',  '>'],
            [1,  3,  'allow_weight_before_date',  'Ngày được phép cân',  '>'],
            [1,  3,  'expired_material_date',     'Ngày hết hạn NL chính',  '<'],
            [7,  7,  'expired_packing_date',     'Ngày hết hạn BB',  '<'],
            [3,  3,  'preperation_before_date',  'Phải PC trước ngày',  '<'],
            [4,  4,  'blending_before_date',    'Phải THT trước ngày',  '<'],
            [5,  5,  'forming_before_date',     'Phải ĐH trước ngày',  '<'],
            [6,  6,  'coating_before_date',     'Phải BP trước ngày',  '<'],
            [7,  7,  'parkaging_before_date',     'Phải ĐG trước ngày',  '<'],
            // [7,  7,  'after_parkaging_date',    'Ngày có đủ BB',  '>'],
        ];

        $activePlans = DB::table('stage_plan')
            ->select(
                'plan_master.id',
                'plan_master.batch',
                'plan_master.responsed_date',
                'plan_master.after_weigth_date',
                'plan_master.after_parkaging_date',
                'plan_master.allow_weight_before_date',
                'plan_master.expired_material_date',
                'plan_master.expired_packing_date',
                'plan_master.preperation_before_date',
                'plan_master.blending_before_date',
                'plan_master.forming_before_date',
                'plan_master.coating_before_date',
                'plan_master.parkaging_before_date',
                'plan_master.responsed_date_change',
                'finished_product_category.finished_product_code',
                'product_name.name as product_name',
                'stage_plan.stage_code',
                'stage_plan.start'
            )
            ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->where('stage_plan.active', 1)
            ->where('stage_plan.finished', 0)
            ->whereNotNull('stage_plan.start')
            ->where('stage_plan.deparment_code', $production)
            ->whereRaw('DATE(stage_plan.start) >= CURDATE()')
            ->orderBy('stage_plan.start', 'asc')
            ->get();

        $planViolations = [];
        $materialWarningsMap = [];
        $proposedMaterialMap = [];

        foreach ($activePlans as $plan) {
            $isViolation = false;
            foreach ($criticalChecks as $check) {
                $from = $check[0];
                $to = $check[1];
                $field = $check[2];
                $label = $check[3];
                $operator = $check[4];

                if ($plan->stage_code >= $from && $plan->stage_code <= $to && !empty($plan->$field)) {
                    $left = \Carbon\Carbon::parse($plan->$field)->startOfDay();
                    $right = \Carbon\Carbon::parse($plan->start)->startOfDay();

                    $matched = false;
                    if ($operator === '<') $matched = $left->lt($right);
                    if ($operator === '>') $matched = $left->gt($right);

                    if ($matched) {
                        $isViolation = true;
                        $planViolations[$plan->id][$field] = [
                            'label' => $label,
                            'field' => $field,
                            'date' => $plan->$field,
                            'stage_code' => $plan->stage_code
                        ];
                    }
                }
            }

            if ($isViolation) {
                if (!isset($materialWarningsMap[$plan->id]) && !isset($proposedMaterialMap[$plan->id])) {
                    $planData = (object) [
                        'id' => $plan->id,
                        'batch' => $plan->batch,
                        'finished_product_code' => $plan->finished_product_code,
                        'product_name' => $plan->product_name,
                        'min_start' => $plan->start,
                        'responsed_date' => $plan->responsed_date,
                        'responsed_date_change' => $plan->responsed_date_change,
                        'violations' => []
                    ];

                    if ($plan->responsed_date_change) {
                        $proposedMaterialMap[$plan->id] = $planData;
                    } else {
                        $materialWarningsMap[$plan->id] = $planData;
                    }
                } else {
                    $targetMap = $plan->responsed_date_change ? $proposedMaterialMap : $materialWarningsMap;
                    if ($plan->start < $targetMap[$plan->id]->min_start) {
                        $targetMap[$plan->id]->min_start = $plan->start;
                    }
                }
            }
        }

        foreach ($materialWarningsMap as $id => $plan) {
            $plan->violations = $planViolations[$id];
        }
        foreach ($proposedMaterialMap as $id => $plan) {
            $plan->violations = $planViolations[$id];
        }

        $materialWarnings = collect(array_values($materialWarningsMap))->sortBy('min_start');
        $proposedMaterialChanges = collect(array_values($proposedMaterialMap))->sortBy('min_start');

        $planMasterIds = $unmetPlans->pluck('id')
            ->merge($proposedChanges->pluck('id'))
            ->merge($materialWarnings->pluck('id'))
            ->merge($proposedMaterialChanges->pluck('id'))
            ->unique()->toArray();

        $commentsRaw = DB::table('plan_master_comments')
            ->join('user_management', 'plan_master_comments.user_id', '=', 'user_management.id')
            ->whereIn('plan_master_id', $planMasterIds)
            ->select('plan_master_comments.*', 'user_management.fullName as user_name', 'user_management.deparment')
            ->orderBy('plan_master_comments.created_at', 'asc')
            ->get();

        $commentsGrouped = $commentsRaw->groupBy('plan_master_id');

        $proposalHistories = DB::table('plan_master_proposals')
            ->join('user_management', 'plan_master_proposals.user_id', '=', 'user_management.id')
            ->join('plan_master', 'plan_master_proposals.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
            ->select('plan_master_proposals.*', 'user_management.fullName as user_name', 'plan_master.batch', 'finished_product_category.finished_product_code as product_code', 'product_name.name as product_name')
            ->orderBy('plan_master_proposals.created_at', 'desc')
            ->get();

        $proposalHistoryCounts = $proposalHistories->groupBy('plan_master_id')->map->count();

        session()->put(['title' => 'CẢNH BÁO LỊCH SẢN XUẤT']);

        return view('pages.Schedual.warning.index', compact('unmetPlans', 'materialWarnings', 'proposedChanges', 'proposedMaterialChanges', 'commentsGrouped', 'proposalHistories', 'proposalHistoryCounts'));
    }

    public function proposeDateChange(Request $request)
    {
        $ids = $request->input('plan_master_ids');
        if (!empty($ids) && is_array($ids)) {
            DB::table('plan_master')
                ->whereIn('id', $ids)
                ->update(['expected_date_change' => 1]);

            // Tìm danh sách người nhận thông báo
            $productionCode = session('user')['production_code'] ?? null;
            $productionName = session('user')['production_name'] ?? $productionCode;
            $senderName = session('user')['fullName'] ?? 'Một người dùng';

            $targetUserIds = DB::table('user_management')
                ->where('isActive', 1)
                ->where(function ($query) use ($productionCode) {
                    $query->whereIn('deparment', ['PL', 'COMP']);
                    if ($productionCode) {
                        $query->orWhere(function ($sub) use ($productionCode) {
                            $sub->where('deparment', $productionCode)
                                ->where('userGroup', 'Schedualer');
                        });
                    }
                })
                ->pluck('id')
                ->toArray();

            $count = count($ids);
            $message = "{$senderName} ({$productionName}) đã gửi đề nghị chấp nhận ngày đáp ứng cho {$count} lô Kế hoạch Sản xuất.";
            $url = route('pages.Schedual.warning.index');

            if (!empty($targetUserIds)) {
                \App\Http\Controllers\General\NotificationController::sendNotification(
                    $message,
                    'Đề nghị đổi ngày KCS',
                    null,
                    $targetUserIds,
                    [],
                    $url
                );
            }

            $userId = session('user')['userId'];

            foreach ($ids as $id) {
                DB::table('plan_master_proposals')->insert([
                    'plan_master_id' => $id,
                    'type' => 'KCS',
                    'action' => 'PROPOSE',
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Đã gửi đề nghị chấp nhận ngày đáp ứng.']);
        }
        return response()->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'], 400);
    }

    public function acceptDateChange(Request $request)
    {
        $ids = $request->input('plan_master_ids');
        $newDate = $request->input('new_date');
        $rowDates = $request->input('row_dates');

        if (!empty($ids) && is_array($ids)) {
            foreach ($ids as $id) {
                // Lấy thông tin plan hiện tại
                $plan = DB::table('plan_master')->where('id', $id)->first();
                if (!$plan) continue;

                // Xác định ngày sẽ được gán
                $targetDate = null;
                if (!empty($newDate)) {
                    $targetDate = $newDate;
                } else if (!empty($rowDates) && isset($rowDates[$id]) && !empty($rowDates[$id])) {
                    $targetDate = $rowDates[$id];
                }

                if ($targetDate) {
                    // Update bảng chính
                    DB::table('plan_master')->where('id', $id)->update([
                        'expected_date' => $targetDate,
                        'expected_date_change' => 0,
                        'updated_at' => now()
                    ]);

                    // Update lịch sử
                    $lastVersion = DB::table('plan_master_history')
                        ->where('plan_master_id', $id)
                        ->max('version');
                    $newVersion = $lastVersion ? $lastVersion + 1 : 1;

                    DB::table('plan_master_history')->insert([
                        'plan_master_id' => $plan->id,
                        'plan_list_id' => $plan->plan_list_id,
                        'product_caterogy_id' => $plan->product_caterogy_id,
                        'version' => $newVersion,
                        'level' => $plan->level,
                        'batch' => $plan->batch,
                        'expected_date' => $targetDate,
                        'is_val' => $plan->is_val,
                        'after_weigth_date' => $plan->after_weigth_date,
                        'after_parkaging_date' => $plan->after_parkaging_date,
                        'material_source_id' => $plan->material_source_id,
                        'percent_parkaging' => $plan->percent_parkaging,
                        'only_parkaging' => $plan->only_parkaging,
                        'number_parkaging' => $plan->number_parkaging,
                        'note' => $plan->note ?? "NA",
                        'reason' => "Cập nhật ngày KCS theo đề nghị",
                        'deparment_code' => $plan->deparment_code,
                        'prepared_by' => session('user')['fullName'] ?? 'System',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $userId = session('user')['userId'] ?? 1;
                    DB::table('plan_master_proposals')->insert([
                        'plan_master_id' => $id,
                        'type' => 'KCS',
                        'action' => 'ACCEPT',
                        'field_name' => 'expected_date',
                        'old_date' => $plan->expected_date,
                        'new_date' => $targetDate,
                        'user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            return response()->json(['success' => true, 'message' => 'Đã chấp nhận và cập nhật ngày KCS thành công.']);
        }
        return response()->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'], 400);
    }

    public function rejectDateChange(Request $request)
    {
        $id = $request->input('plan_master_id');
        $reason = $request->input('reason');
        $userId = session('user')['userId'];

        if (empty($id) || empty($reason)) {
            return response()->json(['success' => false, 'message' => 'Thiếu thông tin.']);
        }

        $plan = DB::table('plan_master')->where('id', $id)->first();
        if ($plan) {
            DB::table('plan_master')->where('id', $id)->update(['expected_date_change' => 0]);

            DB::table('plan_master_proposals')->insert([
                'plan_master_id' => $id,
                'type' => 'KCS',
                'action' => 'REJECT',
                'reason' => $reason,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['success' => true, 'message' => 'Đã từ chối đề nghị KCS.']);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy kế hoạch.']);
    }

    public function getComments($planMasterId)
    {
        $comments = DB::table('plan_master_comments')
            ->join('user_management', 'plan_master_comments.user_id', '=', 'user_management.id')
            ->where('plan_master_comments.plan_master_id', $planMasterId)
            ->select('plan_master_comments.*', 'user_management.fullName as user_name', 'user_management.id as sender_id')
            ->orderBy('plan_master_comments.created_at', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $comments]);
    }

    public function postComment(Request $request)
    {
        $planMasterId = $request->input('plan_master_id');
        $message = $request->input('message');
        $userId = session('user')['userId'];

        if (empty($planMasterId) || empty($message)) {
            return response()->json(['success' => false, 'message' => 'Thiếu thông tin.'], 400);
        }

        $user = DB::table('user_management')->where('id', $userId)->first();
        $department = $user->deparment ?? '';
        $userName = $user->fullName ?? 'Unknown';

        DB::table('plan_master_comments')->insert([
            'plan_master_id' => $planMasterId,
            'user_id' => $userId,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'user_name' => $userName,
            'time' => now()->format('d/m H:i'),
            'message' => $message,
            'department' => $department
        ]);
    }

    public function proposeMaterialDateChange(Request $request)
    {
        $ids = $request->input('plan_master_ids');
        if (!empty($ids) && is_array($ids)) {
            DB::table('plan_master')
                ->whereIn('id', $ids)
                ->update(['responsed_date_change' => 1]);

            $userId = session('user')['userId'];
            $production = session('user')['production_code'];

            foreach ($ids as $id) {
                DB::table('plan_master_proposals')->insert([
                    'plan_master_id' => $id,
                    'type' => 'NL_BB',
                    'action' => 'PROPOSE',
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            \App\Http\Controllers\General\NotificationController::sendNotification(
                'Có một số đề nghị thay đổi ngày đáp ứng NL/BB.',
                'Phòng Kế Hoạch Đề Nghị Thay Đổi Ngày Đáp Ứng NL/BB',
                null,
                'all',
                [],
                route('pages.Schedual.warning.index')
            );
            return response()->json(['success' => true, 'message' => 'Đã gửi đề nghị thành công.']);
        }
        return response()->json(['success' => false, 'message' => 'Vui lòng chọn ít nhất 1 dòng.']);
    }

    public function acceptBulkMaterialDateChange(Request $request)
    {
        $items = $request->input('items');
        $userId = session('user')['userId'];
        $userName = session('user')['fullName'] ?? 'Unknown';

        if (empty($items) || !is_array($items)) {
            return response()->json(['success' => false, 'message' => 'Thiếu dữ liệu.']);
        }

        $production = session('user')['production_code'];
        $count = 0;

        foreach ($items as $item) {
            $id = $item['id'] ?? null;
            $field = $item['field'] ?? null;
            $newDate = $item['date'] ?? null;

            if (!$id || !$field || !$newDate) continue;

            $plan = DB::table('plan_master')->where('id', $id)->first();
            if ($plan) {
                DB::table('plan_master_proposals')->insert([
                    'plan_master_id' => $id,
                    'type' => 'NL_BB',
                    'action' => 'ACCEPT',
                    'field_name' => $field,
                    'old_date' => $plan->$field,
                    'new_date' => $newDate,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $lastVersion = DB::table('plan_master_history')->where('plan_master_id', $id)->max('version');
                $newVersion = $lastVersion ? $lastVersion + 1 : 1;

                DB::table('plan_master_history')->insert([
                    'plan_master_id' => $plan->id,
                    'plan_list_id' => $plan->plan_list_id,
                    'product_caterogy_id' => $plan->product_caterogy_id,
                    'version' => $newVersion,
                    'level' => $plan->level,
                    'batch' => $plan->batch,
                    'expected_date' => $plan->expected_date,
                    'is_val' => $plan->is_val,
                    'after_weigth_date' => $plan->after_weigth_date,
                    'after_parkaging_date' => $plan->after_parkaging_date,
                    'allow_weight_before_date' => $plan->allow_weight_before_date ?? null,
                    'expired_material_date' => $plan->expired_material_date ?? null,
                    'preperation_before_date' => $plan->preperation_before_date ?? null,
                    'blending_before_date' => $plan->blending_before_date ?? null,
                    'forming_before_date' => $plan->forming_before_date ?? null,
                    'coating_before_date' => $plan->coating_before_date ?? null,
                    'material_source_id' => $plan->material_source_id,
                    'percent_parkaging' => $plan->percent_parkaging,
                    'only_parkaging' => $plan->only_parkaging,
                    'number_parkaging' => $plan->number_parkaging,
                    'note' => $plan->note ?? "NA",
                    'reason' => "Cập nhật ngày NL/BB theo đề nghị ($field)",
                    'deparment_code' => $plan->deparment_code,
                    'prepared_by' => session('user')['fullName'] ?? 'System',
                    $field => $newDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('plan_master')
                    ->where('id', $id)
                    ->update([
                        $field => $newDate,
                        'responsed_date_change' => 0
                    ]);

                \App\Http\Controllers\General\NotificationController::sendNotification(
                    'Lô ' . $plan->batch . ' đã được cập nhật ' . $field . ' thành: ' . \Carbon\Carbon::parse($newDate)->format('d/m/Y'),
                    'Đã Chấp Nhận Thay Đổi Ngày Đáp Ứng NL/BB (Hàng loạt)',
                    null,
                    'all',
                    [],
                    route('pages.Schedual.warning.index')
                );
                $count++;
            }
        }

        if ($count > 0) {
            return response()->json(['success' => true, 'message' => 'Đã cập nhật thành công ' . $count . ' mục.']);
        }

        return response()->json(['success' => false, 'message' => 'Không có mục nào được cập nhật.']);
    }

    public function acceptMaterialDateChange(Request $request)
    {
        $id = $request->input('plan_master_id');
        $newDate = $request->input('new_date');
        $field = $request->input('field_name');
        $userId = session('user')['userId'];
        $userName = session('user')['fullName'] ?? 'Unknown';

        if (empty($id) || empty($newDate) || empty($field)) {
            return response()->json(['success' => false, 'message' => 'Thiếu thông tin.']);
        }

        $plan = DB::table('plan_master')->where('id', $id)->first();
        if ($plan) {
            DB::table('plan_master_proposals')->insert([
                'plan_master_id' => $id,
                'type' => 'NL_BB',
                'action' => 'ACCEPT',
                'field_name' => $field,
                'old_date' => $plan->$field,
                'new_date' => $newDate,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $lastVersion = DB::table('plan_master_history')->where('plan_master_id', $id)->max('version');
            $newVersion = $lastVersion ? $lastVersion + 1 : 1;

            DB::table('plan_master_history')->insert([
                'plan_master_id' => $plan->id,
                'plan_list_id' => $plan->plan_list_id,
                'product_caterogy_id' => $plan->product_caterogy_id,
                'version' => $newVersion,
                'level' => $plan->level,
                'batch' => $plan->batch,
                'expected_date' => $plan->expected_date,
                'is_val' => $plan->is_val,
                'after_weigth_date' => $plan->after_weigth_date,
                'after_parkaging_date' => $plan->after_parkaging_date,
                'allow_weight_before_date' => $plan->allow_weight_before_date ?? null,
                'expired_material_date' => $plan->expired_material_date ?? null,
                'preperation_before_date' => $plan->preperation_before_date ?? null,
                'blending_before_date' => $plan->blending_before_date ?? null,
                'forming_before_date' => $plan->forming_before_date ?? null,
                'coating_before_date' => $plan->coating_before_date ?? null,
                'material_source_id' => $plan->material_source_id,
                'percent_parkaging' => $plan->percent_parkaging,
                'only_parkaging' => $plan->only_parkaging,
                'number_parkaging' => $plan->number_parkaging,
                'note' => $plan->note ?? "NA",
                'reason' => "Cập nhật ngày NL/BB theo đề nghị ($field)",
                'deparment_code' => $plan->deparment_code,
                'prepared_by' => session('user')['fullName'] ?? 'System',
                $field => $newDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('plan_master')
                ->where('id', $id)
                ->update([
                    $field => $newDate,
                    'responsed_date_change' => 0
                ]);

            $production = session('user')['production_code'];
            \App\Http\Controllers\General\NotificationController::sendNotification(
                'Lô ' . $plan->batch . ' đã được cập nhật ' . $field . ' thành: ' . Carbon::parse($newDate)->format('d/m/Y'),
                'Đã Chấp Nhận Thay Đổi Ngày Đáp Ứng NL/BB',
                null,
                'all',
                [],
                route('pages.Schedual.warning.index')
            );

            return response()->json(['success' => true, 'message' => 'Đã cập nhật dữ liệu thành công.']);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy kế hoạch.']);
    }

    public function rejectMaterialDateChange(Request $request)
    {
        $id = $request->input('plan_master_id');
        $reason = $request->input('reason');
        $userId = session('user')['userId'];

        if (empty($id) || empty($reason)) {
            return response()->json(['success' => false, 'message' => 'Thiếu thông tin.']);
        }

        $plan = DB::table('plan_master')->where('id', $id)->first();
        if ($plan) {
            DB::table('plan_master')->where('id', $id)->update(['responsed_date_change' => 0]);

            DB::table('plan_master_proposals')->insert([
                'plan_master_id' => $id,
                'type' => 'NL_BB',
                'action' => 'REJECT',
                'reason' => $reason,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['success' => true, 'message' => 'Đã từ chối đề nghị NL/BB.']);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy kế hoạch.']);
    }

    public function getProposalHistory($planMasterId)
    {
        $history = DB::table('plan_master_proposals')
            ->join('user_management', 'plan_master_proposals.user_id', '=', 'user_management.id')
            ->where('plan_master_proposals.plan_master_id', $planMasterId)
            ->select('plan_master_proposals.*', 'user_management.fullName as user_name')
            ->orderBy('plan_master_proposals.created_at', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $history]);
    }
}

