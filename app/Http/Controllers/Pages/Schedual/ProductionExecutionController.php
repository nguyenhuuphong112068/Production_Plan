<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RestrictExecutor;
use App\Services\ProductionExecutionException;
use App\Services\RealtimeRerouteSwitch;
use App\Services\RoomOccupancyService;
use App\Services\ScheduleRerouteService;
use App\Services\SchedulingLock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Trang "Thực Thi Sản Xuất" (bản tinh gọn): phòng Sẵn Sàng / Phòng Bận, 2 thao tác Nhận phòng (stage_plan.actual_start)
 * và Trả phòng (stage_plan.actual_end_clearning). Mốc thời gian = giờ hệ thống lúc bấm nút, không nhận giờ từ trình duyệt.
 * Bản cũ (room_execution_log, sản lượng, vệ sinh, kiểm tra...) xem lịch sử git trước 10/2026.
 */
class ProductionExecutionController extends Controller
{
        public function __construct(private RoomOccupancyService $service) {}

        public function index(Request $request)
        {
                $production = session('user')['production_code'];
                $rooms = $this->service->board($production);

                // Nhân viên phân xưởng khác: chỉ xem, card phòng không có nút thao tác
                $foreign = !$this->canOperate($production);

                $data = [
                        'stages'     => $rooms->groupBy('stage_group'),
                        'production' => $production,
                        'readonly'   => $foreign,
                ];

                // Tự làm mới định kỳ chỉ cần phần lưới phòng
                if ($request->boolean('partial')) {
                        return view('pages.Schedual.execution._board', $data);
                }

                session()->put(['title' => 'THỰC THI SẢN XUẤT']);

                return view('pages.Schedual.execution.index', $data + [
                        'foreignDepartment' => $foreign ? session('user')['department'] : null,
                        'stateCounts'       => $rooms->countBy(fn($r) => $r->st->state),
                ]);
        }

        /**
         * Trang "Ghi Nhận Sản Xuất": chỉ các card phòng mà người đăng nhập đang được phân công lúc này (Lịch Công Tác →
         * Sản Xuất, MSNV = userName). Role Executor đăng nhập vào thẳng trang này và không vào được trang nào khác
         * (RestrictExecutor). Hết ca thì phòng tự biến mất ở lần làm mới kế tiếp.
         */
        public function record(Request $request)
        {
                // Role Executor luôn vào được (đây là trang duy nhất của họ); người khác cần quyền
                if (!RestrictExecutor::active() && !user_has_permission(session('user')['userId'], 'layout_production_record', 'boolean')) {
                        abort(403, 'Bạn không có quyền vào trang Ghi Nhận Sản Xuất');
                }

                $production = session('user')['production_code'];
                $employee = DB::table('employees')->where('code', session('user')['userName'])->first(['id', 'code', 'name']);
                $assignments = $employee ? $this->service->assignmentsOf($employee->code, $production) : collect();
                // Phòng đang được phân công + phòng mình đã Nhận phòng mà chưa Trả phòng (dù đã hết ca)
                $rooms = $this->service->board($production, $assignments->pluck('room_id')
                        ->merge($this->service->heldRoomIdsOf(session('user')['userName'], $production))
                        ->unique()->values()->all());

                $data = [
                        'stages'      => $rooms->groupBy('stage_group'),
                        'production'  => $production,
                        'readonly'    => !$this->canOperate($production),
                        'employee'    => $employee,
                        'assignments' => $assignments,
                ];

                // Tự làm mới: lưới phòng + dải ca phân công (đổi ca thì cả hai cùng đổi)
                if ($request->boolean('partial')) {
                        return view('pages.Schedual.execution._record_body', $data);
                }

                return view('pages.Schedual.execution.record', $data + [
                        'executorOnly' => RestrictExecutor::active(),
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
                        'publicView' => true, // không đăng nhập: không có nút Lịch sử
                ];

                if ($request->boolean('partial')) {
                        return view('pages.Schedual.execution._board', $data);
                }

                return view('pages.Schedual.execution.public', $data + [
                        'departments' => $departments,
                        'stateCounts' => $rooms->countBy(fn($r) => $r->st->state),
                ]);
        }

        /**
         * Lô / lịch bảo trì nhận phòng được (modal Nhận phòng), theo phòng ban người đăng nhập:
         * EN chỉ lịch BT/TI, QA chỉ lịch HC, phòng ban khác chỉ lô sản xuất (RoomOccupancyService::MAINTENANCE_TYPES_BY_DEPARTMENT).
         */
        public function plans(Request $request)
        {
                $room = $this->ownRoom($request->room_id);
                if (!$room) {
                        return response()->json(['message' => $this->roomDeniedMessage()], 403);
                }

                $fmt = fn($t) => $t ? Carbon::parse($t)->format('H:i d/m/Y') : null;

                return response()->json($this->service->candidatePlans($room, $this->department())->map(fn($p) => [
                        'id'          => $p->id,
                        // Lịch bảo trì: mỗi dòng 1 thiết bị → tên thiết bị; tiêu đề nhóm + ngày tới hạn ở dòng phụ
                        'product'     => $p->maintenance ? $p->equipment_label : ($p->product_name ?? $p->title),
                        'maintenance' => $p->maintenance,
                        'type_label'  => $p->maintenance ? (RoomOccupancyService::MAINTENANCE_TYPE_LABELS[$p->maintenance_type] ?? $p->maintenance_type) : null,
                        'group'       => $p->maintenance ? $p->title . ($p->due ? ' · Tới hạn ' . $p->due : '') : null,
                        'market'      => $p->stage_code == 7 ? $p->market : null,
                        'batch'       => $p->batch,
                        'codes'       => trim(($p->intermediate_code ?? '') . ' / ' . ($p->finished_product_code ?? ''), ' /'),
                        'btp'         => $p->intermediate_code,
                        // Chọn theo nhau: Cân NL - lô cùng lịch lý thuyết + cùng BTP; bảo trì - thiết bị cùng nhóm lịch
                        'plan_key'    => !$p->start ? null : ($p->maintenance
                                ? 'M|' . $p->start . '|' . $p->end . '|' . $p->title
                                : $p->start . '|' . $p->end . '|' . $p->intermediate_code),
                        'start'       => $fmt($p->start),
                        'end'         => $fmt($p->end),
                        'theory'      => round((float) $p->Theoretical_yields, 2),
                        'unit'        => $p->unit,
                        'is_val'      => (bool) $p->is_val,
                ])->values());
        }

        /** Lịch sử Nhận / Trả phòng của 1 phòng (modal Lịch sử trên card phòng, chỉ xem) */
        public function history(Request $request)
        {
                $room = $this->ownRoom($request->room_id);
                if (!$room) {
                        return response()->json(['message' => $this->roomDeniedMessage()], 403);
                }

                $fmt = fn($t) => $t ? Carbon::parse($t)->format('H:i d/m/Y') : null;

                return response()->json([
                        'room'  => $room->code . ' - ' . $room->name,
                        'since' => Carbon::parse(RoomOccupancyService::TRACK_FROM)->format('d/m/Y'),
                        'rows'  => $this->service->history($room->id)->map(fn($h) => [
                                'labels'         => $h->labels,
                                'maintenance'    => $h->maintenance,
                                'received_at'    => $fmt($h->received_at),
                                'received_by'    => $h->received_by,
                                'maint_end_at'   => $fmt($h->maint_end_at),
                                'maint_end_by'   => $h->maint_end_by,
                                'clean_start_at' => $fmt($h->clean_start_at),
                                'clean_start_by' => $h->clean_start_by,
                                'released_at'    => $fmt($h->released_at),
                                'released_by'    => $h->released_by,
                                // Thời gian giữ phòng (đang giữ thì tới hiện tại), phút
                                'minutes'        => (int) Carbon::parse($h->received_at)->diffInMinutes($h->released_at ? Carbon::parse($h->released_at) : now()),
                        ]),
                ]);
        }

        /** Nhận phòng: actual_start = giờ hệ thống */
        public function receive(Request $request)
        {
                return $this->act($request, fn() => $this->service->receive(
                        (int) $request->room_id,
                        (array) $request->input('stage_plan_ids', []),
                        $this->department()
                ));
        }

        /** Nhận phòng vệ sinh sau bảo trì: actual_start_clearning = giờ hệ thống */
        public function receiveCleaning(Request $request)
        {
                return $this->act($request, fn() => $this->service->receiveCleaning((int) $request->room_id));
        }

        /** Trả phòng: actual_end_clearning (bảo trì: actual_end) = giờ hệ thống; công tắc phân xưởng bật thì tịnh tuyến lịch */
        public function release(Request $request)
        {
                return $this->act($request, fn() => $this->service->release((int) $request->room_id));
        }

        /**
         * Chạy 1 thao tác và trả về thông báo + HTML card phòng mới để trình duyệt thay tại chỗ.
         * Thao tác ghi vào stage_plan nên chờ nếu phân xưởng đang sắp lịch tự động.
         */
        private function act(Request $request, callable $fn)
        {
                $room = $this->ownRoom($request->room_id);
                if (!$room) {
                        return response()->json(['message' => $this->roomDeniedMessage(), 'gone' => $this->recordMode()], 403);
                }

                if (!$this->canOperate($room->deparment_code)) {
                        return response()->json(['message' => '❌ Bạn thuộc phân xưởng ' . session('user')['department']
                                . ', không được thao tác trên phòng của phân xưởng ' . $room->deparment_code], 403);
                }

                $lock = SchedulingLock::active(session('user.production_code'));
                if ($lock) {
                        return response()->json(['message' => SchedulingLock::message($lock)], 423);
                }

                try {
                        $result = $fn();
                } catch (ProductionExecutionException $e) {
                        return response()->json([
                                'message' => $e->getMessage(),
                                'html'    => $e->getCode() === 409 ? $this->cardHtml($room->id) : null,
                        ], $e->getCode() ?: 422);
                }

                // Thao tác trả về chuỗi thông báo, hoặc mảng có 'message' + dữ liệu thêm (Trả phòng: kết quả tịnh tuyến lịch)
                $payload = is_array($result) ? $result : ['message' => $result];

                return response()->json($payload + ['html' => $this->cardHtml($room->id)]);
        }

        /**
         * Bật/tắt công tắc tịnh tuyến lịch theo thời gian thực của phân xưởng (nút ở trang Xác nhận hoàn thành).
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

        /**
         * Nhân viên của 1 phân xưởng chỉ được thao tác trên phòng của phân xưởng mình (các phân xưởng khác chỉ xem).
         * Phòng ban không phải phân xưởng (QA, PL, EN...) và nhóm quyền Admin không bị giới hạn này.
         */
        private function canOperate(string $production): bool
        {
                $department = session('user')['department'] ?? null;

                if ($department === $production || !DB::table('production')->where('code', $department)->exists()) {
                        return true;
                }

                return DB::table('user_role')->where('user_id', session('user')['userId'] ?? null)->where('role_id', 1)->exists();
        }

        private function ownRoom($roomId): ?object
        {
                $room = DB::table('room')
                        ->where('id', (int) $roomId)
                        ->where('deparment_code', session('user')['production_code'])
                        ->where('active', 1)
                        ->first();

                // Trang Ghi Nhận / role Executor: chỉ phòng đang được phân công lúc bấm nút, hoặc phòng mình đã Nhận phòng
                // mà chưa Trả phòng (để trả được phòng khi đã hết ca) — không tin danh sách trên trình duyệt
                if ($room && $this->recordMode()) {
                        $assigned = $this->service->assignmentsOf(session('user')['userName'], $room->deparment_code)->pluck('room_id')
                                ->merge($this->service->heldRoomIdsOf(session('user')['userName'], $room->deparment_code));
                        if (!$assigned->contains($room->id)) {
                                return null;
                        }
                }

                return $room;
        }

        private function department(): ?string
        {
                return session('user')['department'] ?? null;
        }

        private function recordMode(): bool
        {
                return request()->routeIs('pages.Schedual.record.*') || RestrictExecutor::active();
        }

        private function roomDeniedMessage(): string
        {
                return $this->recordMode()
                        ? '❌ Bạn không được phân công tại phòng này vào lúc này (Lịch Công Tác → Sản Xuất)'
                        : '❌ Phòng không thuộc phân xưởng đang chọn';
        }

        private function cardHtml(int $roomId): string
        {
                $room = $this->service->room($roomId);

                return $room ? view('pages.Schedual.execution._room_card', ['room' => $room])->render() : '';
        }
}
