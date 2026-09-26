<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use App\Services\EquipmentLabelService;
use App\Services\ProductionExecutionException;
use App\Services\ProductionExecutionService;
use App\Services\RealtimeRerouteSwitch;
use App\Services\ScheduleRerouteService;
use App\Services\SchedulingLock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Trang "Thực Thi Sản Xuất": điều khiển trạng thái phòng sản xuất theo thực tế
 * (sẽ thay trang Xác nhận hoàn thành và phần nhập tay hoạt động ở Báo cáo ngày).
 *
 * Mọi mốc thời gian (BĐSX, BĐCM, KT, vệ sinh, hoạt động) lấy theo giờ hệ thống lúc bấm nút: không nhận giờ từ
 * trình duyệt, service nhận null = now. BĐCM của mỗi lần xác nhận = lúc bắt đầu / bắt đầu lại.
 */
class ProductionExecutionController extends Controller
{
        public function __construct(private ProductionExecutionService $service) {}

        public function index(Request $request)
        {
                $production = session('user')['production_code'];
                $rooms = $this->service->board($production);

                $data = [
                        'stages'     => $rooms->groupBy('stage_group'),
                        'production' => $production,
                ];

                // Tự làm mới định kỳ chỉ cần phần lưới phòng
                if ($request->boolean('partial')) {
                        return view('pages.Schedual.execution._board', $data);
                }

                // Gợi ý tên hoạt động: các hoạt động hay nhập ở Báo cáo ngày của phân xưởng
                $activitySuggestions = DB::table('room_status')
                        ->where('is_daily_report', 1)
                        ->where('active', 1)
                        ->where('deparment_code', $production)
                        ->where('created_at', '>=', now()->subDays(90))
                        ->whereNotNull('in_production')
                        ->groupBy('in_production')
                        ->orderByRaw('COUNT(*) DESC')
                        ->limit(25)
                        ->pluck('in_production');

                session()->put(['title' => 'THỰC THI SẢN XUẤT']);

                $rerouteSetting = RealtimeRerouteSwitch::get($production);

                return view('pages.Schedual.execution.index', $data + [
                        'activitySuggestions' => $activitySuggestions,
                        'stateCounts'         => $rooms->countBy(fn($r) => $r->st->display),
                        'rerouteEnabled'      => (bool) ($rerouteSetting->realtime_reroute ?? false),
                        'rerouteLastChange'   => RealtimeRerouteSwitch::lastChange($rerouteSetting),
                        'canReroute'          => ScheduleRerouteService::canUse(),
                ]);
        }

        /**
         * Trang công khai "Trạng Thái Sản Xuất" (không cần đăng nhập, nút trên trang đăng nhập): xem trạng thái hiện tại
         * các phòng của 1 phân xưởng, chỉ đọc. Phân xưởng chọn qua ?production_code= (chỉ các phân xưởng có phòng sản xuất).
         */
        public function publicView(Request $request)
        {
                $departments = DB::table('deparments')
                        ->where('active', 1)
                        ->whereIn('shortName', DB::table('room')->where('active', 1)->whereBetween('stage_code', [1, 7])->distinct()->pluck('deparment_code'))
                        ->orderBy('id')
                        ->pluck('name', 'shortName');
                $production = $departments->has($request->production_code) ? $request->production_code : ($departments->keys()->first() ?? 'PXV1');

                $rooms = $this->service->board($production);
                $data = [
                        'stages'     => $rooms->groupBy('stage_group'),
                        'production' => $production,
                        'readonly'   => true,
                ];

                if ($request->boolean('partial')) {
                        return view('pages.Schedual.execution._board', $data);
                }

                return view('pages.Schedual.execution.public', $data + [
                        'departments' => $departments,
                        'stateCounts' => $rooms->countBy(fn($r) => $r->st->display),
                ]);
        }

        public function plans(Request $request)
        {
                $room = $this->ownRoom($request->room_id);
                if (!$room) {
                        return response()->json(['message' => '❌ Phòng không thuộc phân xưởng đang chọn'], 403);
                }

                $plans = $this->service->candidatePlans($room, $request->scope === 'stage' ? 'stage' : 'room');
                $fmt = fn($t) => $t ? Carbon::parse($t)->format('H:i d/m/Y') : null;

                return response()->json($plans->map(fn($p) => [
                        'id'        => $p->id,
                        'product'   => $p->product_name ?? $p->title,
                        'market'    => $p->stage_code == 7 ? $p->market : null,
                        'batch'     => $p->batch,
                        'codes'     => trim(($p->intermediate_code ?? '') . ' / ' . ($p->finished_product_code ?? ''), ' /'),
                        'start'     => $fmt($p->start),
                        'end'       => $fmt($p->end),
                        'room_code' => $p->room_code,
                        'same_room' => (int) $p->resourceId === (int) $room->id,
                        'theory'    => round((float) $p->Theoretical_yields, 2),
                        'confirmed' => round((float) $p->total_confirmed, 2),
                        'unit'      => $p->unit,
                        'partial'   => (bool) $p->actual_start,
                        'is_val'    => (bool) $p->is_val,
                ])->values());
        }

        /**
         * Tóm tắt nhãn HC-BT-TI của thiết bị từng phòng (card phòng), thiết bị theo danh mục Bảo trì hiệu chuẩn.
         */
        public function equipment(Request $request, EquipmentLabelService $labels)
        {
                $data = $labels->forDepartment(session('user')['production_code'], $request->boolean('refresh'));

                return response()->json([
                        'rooms'        => $labels->summaries($data),
                        'errors'       => $data['errors'],
                        'generated_at' => $data['generated_at'],
                ]);
        }

        /**
         * Nhãn chi tiết (từng dụng cụ / chu kỳ) của các thiết bị trong 1 phòng.
         */
        public function equipmentLabel(Request $request, EquipmentLabelService $labels)
        {
                $room = $this->ownRoom($request->room_id);
                if (!$room) {
                        return response()->json(['message' => '❌ Phòng không thuộc phân xưởng đang chọn'], 403);
                }

                $data = $labels->forDepartment($room->deparment_code, $request->boolean('refresh'));

                return response()->json([
                        'equipments'   => $data['rooms'][$room->id]['equipments'] ?? [],
                        'errors'       => $data['errors'],
                        'generated_at' => $data['generated_at'],
                ]);
        }

        public function history(Request $request)
        {
                $room = $this->ownRoom($request->room_id);
                if (!$room) {
                        return response()->json(['message' => '❌ Phòng không thuộc phân xưởng đang chọn'], 403);
                }

                return response()->json($this->service->history($room->id));
        }

        public function start(Request $request)
        {
                return $this->act($request, fn($user) => $this->service->start(
                        (int) $request->room_id,
                        $request->token,
                        (int) $request->stage_plan_id,
                        $request->mode, // 'prepare' = Chuẩn bị, 'execute' = Thực thi sản xuất ngay
                        null, // giờ hệ thống
                        $user
                ));
        }

        /** Đang chuẩn bị → Đang SX: BĐCM = giờ hệ thống lúc bấm */
        public function execute(Request $request)
        {
                return $this->act($request, fn($user) => $this->service->execute(
                        (int) $request->room_id,
                        $request->token,
                        null, // giờ hệ thống
                        $user
                ));
        }

        public function pause(Request $request)
        {
                return $this->act($request, fn($user) => $this->service->pause(
                        (int) $request->room_id,
                        $request->token,
                        $request->only(['yields', 'number_of_boxes', 'note', 'reason', 'actual_batch']),
                        $user
                ), true);
        }

        public function resume(Request $request)
        {
                return $this->act($request, fn($user) => $this->service->resume(
                        (int) $request->room_id,
                        $request->token,
                        null, // giờ hệ thống
                        $user
                ));
        }

        public function finish(Request $request)
        {
                return $this->act($request, fn($user) => $this->service->finish(
                        (int) $request->room_id,
                        $request->token,
                        $request->only(['yields', 'number_of_boxes', 'note', 'actual_batch']),
                        $user
                ), true);
        }

        public function cleanStart(Request $request)
        {
                return $this->act($request, fn($user) => $this->service->startCleaning(
                        (int) $request->room_id,
                        $request->token,
                        null, // giờ hệ thống
                        $request->level,
                        $user
                ));
        }

        public function cleanEnd(Request $request)
        {
                return $this->act($request, fn($user) => $this->service->endCleaning(
                        (int) $request->room_id,
                        $request->token,
                        null, // giờ hệ thống
                        $request->note,
                        $user
                ), true);
        }

        public function markDirty(Request $request)
        {
                return $this->act($request, fn($user) => $this->service->markDirty(
                        (int) $request->room_id,
                        $request->token,
                        null, // giờ hệ thống
                        $request->note,
                        $user
                ));
        }

        public function undo(Request $request)
        {
                return $this->act($request, fn($user) => $this->service->undo(
                        (int) $request->room_id,
                        $request->token,
                        $user
                ));
        }

        public function storeActivity(Request $request)
        {
                return $this->act($request, fn($user) => $this->service->addActivity(
                        (int) $request->room_id,
                        $request->token,
                        $request->only(['in_production', 'notification']),
                        $user
                ));
        }

        public function endActivity(Request $request)
        {
                return $this->act($request, function () use ($request) {
                        $activity = DB::table('room_status')
                                ->where('id', $request->activity_id)
                                ->where('room_id', $request->room_id)
                                ->where('is_daily_report', 1)
                                ->where('active', 1)
                                ->whereNull('end')
                                ->first();

                        if (!$activity) {
                                throw new ProductionExecutionException('❌ Hoạt động không tồn tại hoặc đã kết thúc', 409);
                        }

                        return $this->service->endActivity($activity, null);
                });
        }

        /**
         * Chạy 1 thao tác và trả về thông báo + HTML card phòng mới để trình duyệt thay tại chỗ.
         * $writesSchedule: thao tác ghi vào stage_plan/yields thì chờ nếu phân xưởng đang sắp lịch tự động.
         */
        private function act(Request $request, callable $fn, bool $writesSchedule = false)
        {
                $room = $this->ownRoom($request->room_id);
                if (!$room) {
                        return response()->json(['message' => '❌ Phòng không thuộc phân xưởng đang chọn'], 403);
                }

                if ($writesSchedule) {
                        $lock = SchedulingLock::active(session('user.production_code'));
                        if ($lock) {
                                return response()->json(['message' => SchedulingLock::message($lock)], 423);
                        }
                }

                try {
                        $result = $fn(session('user')['fullName']);
                } catch (ProductionExecutionException $e) {
                        return response()->json([
                                'message' => $e->getMessage(),
                                'html'    => $e->getCode() === 409 ? $this->cardHtml($room->id) : null,
                        ], $e->getCode());
                }

                // Thao tác trả về chuỗi thông báo, hoặc mảng có 'message' + dữ liệu thêm (vd. kết quả tịnh tuyến)
                $payload = is_array($result) ? $result : ['message' => $result];

                return response()->json($payload + ['html' => $this->cardHtml($room->id)]);
        }

        /**
         * Bật/tắt công tắc tịnh tuyến lịch theo thời gian thực của phân xưởng (dùng chung với trang Xác nhận hoàn thành).
         */
        public function rerouteSwitch(Request $request)
        {
                if (!ScheduleRerouteService::canUse()) {
                        return response()->json(['message' => '❌ Bạn không có quyền bật/tắt tịnh tuyến lịch'], 403);
                }

                $production = session('user')['production_code'];
                RealtimeRerouteSwitch::set($production, $request->boolean('enabled'), session('user')['fullName']);
                $setting = RealtimeRerouteSwitch::get($production);

                return response()->json([
                        'enabled'     => (bool) $setting->realtime_reroute,
                        'last_change' => RealtimeRerouteSwitch::lastChange($setting),
                        'message'     => $setting->realtime_reroute
                                ? "✅ Đã BẬT tịnh tuyến lịch theo thời gian thực cho $production"
                                : "✅ Đã TẮT tịnh tuyến lịch theo thời gian thực cho $production",
                ]);
        }

        private function ownRoom($roomId): ?object
        {
                return DB::table('room')
                        ->where('id', (int) $roomId)
                        ->where('deparment_code', session('user')['production_code'])
                        ->where('active', 1)
                        ->first();
        }

        private function cardHtml(int $roomId): string
        {
                $room = $this->service->room($roomId);

                return $room ? view('pages.Schedual.execution._room_card', ['room' => $room])->render() : '';
        }
}
