<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Services\WeighingAssistService;
use App\Support\WeighingScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sắp lịch cân bán tự động trên Gantt Lịch Sản Xuất (nút "Lô cần cân" / "Gợi ý lịch cân").
 * Kế thừa SchedualController chỉ để trả lại events / KHSX chờ sắp / sản lượng giống thao tác thả lịch (store).
 * Nghiệp vụ: App\Services\WeighingAssistService.
 */
class WeighingAssistController extends SchedualController
{
    /** Lô công đoạn sau còn công đoạn cân chưa xong: id => need | planned */
    public function status(WeighingAssistService $service)
    {
        return response()->json(['targets' => $service->status(session('user.production_code'))]);
    }

    /** Gợi ý khối cân cho các lô công đoạn sau đang chọn (không ghi DB); merge = ghép các lô cân chung */
    public function suggest(Request $request, WeighingAssistService $service)
    {
        $request->validate([
            'ids' => 'required|array|max:500',
            'ids.*' => 'integer',
            'reserved' => 'nullable|array',
            'merge' => 'nullable|boolean',
        ]);

        return response()->json($service->suggest(
            session('user.production_code'),
            $request->input('ids'),
            $request->input('reserved', []),
            $request->boolean('merge')
        ));
    }

    /** Ghi các khối cân đã duyệt, trả lại lịch như store() */
    public function apply(Request $request, WeighingAssistService $service)
    {
        if (! $this->canEdit()) {
            return response()->json(['message' => 'Bạn không có quyền sắp lịch của phân xưởng này.'], 403);
        }

        $request->validate([
            'blocks' => 'required|array|max:200',
            'blocks.*.room_id' => 'required|integer',
            'blocks.*.ids' => 'required|array',
            'blocks.*.start' => 'required|date',
            'blocks.*.end' => 'required|date',
            'blocks.*.start_clearning' => 'nullable|date',
            'blocks.*.end_clearning' => 'required|date',
        ]);

        $production = session('user.production_code');

        try {
            $result = $service->apply($production, $request->input('blocks'));
        } catch (\Throwable $e) {
            Log::error('Gợi ý lịch cân - lỗi ghi', ['error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 500);
        }

        $this->theory = (int) ($request->theory ?? 0);
        $result['plan'] = $this->getPlanWaiting($production);

        // Gantt còn thay đổi khác cần lưu tiếp qua /Schedual/update thì không gửi khung xem: không tải lịch 2 lần
        if ($request->startDate && $request->endDate) {
            $result['events'] = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
            $result['sumBatchByStage'] = $this->yield($request->startDate, $request->endDate, 'stage_code');
        }

        return response()->json($result);
    }

    /** Cùng điều kiện với nút sửa lịch trên Gantt (authorization trong FullCalender.jsx) */
    private function canEdit(): bool
    {
        $user = session('user') ?? [];
        $department = DB::table('user_management')->where('userName', $user['userName'] ?? null)->value('deparment');

        return $department === 'BOD'
            || (in_array($user['userGroup'] ?? null, ['Admin', 'Schedualer', WeighingScheduler::ROLE], true)
                && $department === ($user['production_code'] ?? null));
    }
}
