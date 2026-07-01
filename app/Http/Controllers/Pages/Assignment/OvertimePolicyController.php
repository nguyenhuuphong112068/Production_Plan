<?php

namespace App\Http\Controllers\Pages\Assignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OvertimePolicy;

class OvertimePolicyController extends Controller
{
    /**
     * Lấy cấu hình policy hiện tại của 1 phân xưởng
     */
    public function index(Request $request)
    {
        $production_code = $request->production_code;
        if (!$production_code) {
            return response()->json(['success' => false, 'message' => 'Missing production_code']);
        }

        $policies = OvertimePolicy::where('overtime_policies.production_code', $production_code)
            ->where('overtime_policies.active', 1)
            ->leftJoin('stage_groups', 'overtime_policies.group_id', '=', 'stage_groups.code')
            ->select('overtime_policies.*', 'stage_groups.code as group_code_value', 'stage_groups.name as group_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $policies
        ]);
    }

    /**
     * Lưu cấu hình policy mới
     */
    public function store(Request $request)
    {
        $production_code = $request->production_code;
        $policiesData = $request->policies; // Mảng các policy: [['group_id' => null, 'max_personnel' => 10, 'max_hours' => 20], ...]
        $user = session('user')['fullName'] ?? session('user')['name'] ?? 'System';

        if (!$production_code || !is_array($policiesData)) {
            return response()->json(['success' => false, 'message' => 'Invalid data']);
        }

        // Đánh dấu tất cả policy cũ của xưởng này thành lịch sử
        OvertimePolicy::where('production_code', $production_code)
            ->where('active', 1)
            ->update(['active' => 0]);

        // Tạo các policy mới
        foreach ($policiesData as $p) {
            OvertimePolicy::create([
                'production_code' => $production_code,
                'group_id' => $p['group_id'] ?? null,
                'max_personnel_per_day' => $p['max_personnel'] ?? 0,
                'max_hours_per_day' => $p['max_hours'] ?? 0,
                'active' => 1,
                'created_by' => $user
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Xem lịch sử thay đổi policy
     */
    public function history(Request $request)
    {
        $production_code = $request->production_code;
        if (!$production_code) {
            return response()->json(['success' => false, 'message' => 'Missing production_code']);
        }

        $history = OvertimePolicy::where('production_code', $production_code)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($item) {
                return $item->created_at->format('Y-m-d H:i:s');
            });

        $formattedHistory = [];
        foreach ($history as $time => $items) {
            $formattedHistory[] = [
                'time' => $time,
                'created_by' => $items->first()->created_by,
                'active' => $items->first()->active,
                'policies' => $items
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $formattedHistory
        ]);
    }
}
