<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MaterialSourceWarningController extends Controller
{
        /** Các công đoạn sản xuất được phép khai báo thiết bị trong ma trận */
        private const STAGE_CODES = [1, 2, 3, 4, 5, 6, 7];

        public function index()
        {
                session()->put(['title' => 'CẢNH BÁO NGUỒN NL']);

                $deparmentCode = session('user')['production_code'];

                $datas = DB::table('material_source_warning as w')
                        ->select(
                                'w.*',
                                'product_name.name as product_name',
                                'market.code as market_code',
                                'market.name as market_name'
                        )
                        ->leftJoin('intermediate_category', function ($join) {
                                $join->on('intermediate_category.intermediate_code', '=', 'w.intermediate_code')
                                        ->on('intermediate_category.deparment_code', '=', 'w.deparment_code')
                                        ->where('intermediate_category.cancel', 0);
                        })
                        ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                        ->leftJoin('market', 'w.market_id', 'market.id')
                        ->where('w.deparment_code', $deparmentCode)
                        ->orderBy('w.intermediate_code', 'asc')
                        ->orderBy('w.material_code', 'asc')
                        ->get();

                return view('pages.category.material_source_warning.list', [
                        'datas' => $datas,
                        'roomsByWarning' => $this->roomsByWarning($datas->pluck('id'), $deparmentCode),
                        'intermediates' => $this->intermediateOptions($deparmentCode),
                        'markets' => DB::table('market')->where('active', 1)->orderBy('code', 'asc')->get(),
                ]);
        }

        /**
         * Dữ liệu dựng form theo 1 mã BTP:
         * - Danh sách mã NL lấy từ BOM ấn bản mới nhất trên MMS
         * - Ma trận thiết bị: chỉ những thiết bị đã có định mức (bảng quota) của chính mã BTP đó
         *
         * MMS là hệ thống ngoài nên phần thiết bị vẫn phải dựng được khi MMS mất kết nối.
         */
        public function intermediateData(Request $request)
        {
                $intermediateCode = trim((string) $request->intermediate_code);

                if ($intermediateCode === '') {
                        return response()->json([
                                'material_success' => false,
                                'material_message' => 'Vui lòng chọn mã bán thành phẩm.',
                                'materials' => [],
                                'stages' => [],
                        ]);
                }

                $deparmentCode = session('user')['production_code'];

                return response()->json(array_merge(
                        $this->bomMaterials($intermediateCode),
                        ['stages' => $this->quotaStageRooms($intermediateCode, $deparmentCode)]
                ));
        }

        /** Danh sách mã NL của 1 mã BTP theo công thức ấn bản mới nhất trên MMS */
        private function bomMaterials(string $intermediateCode): array
        {
                try {
                        $materials = DB::connection('mms')
                                ->table('yfBOM_BOMItemHP')
                                ->select('MatID', 'MaterialName', 'uom', 'Revno1')
                                ->where('PrdID', $intermediateCode)
                                ->where('Revno', function ($q) use ($intermediateCode) {
                                        $q->selectRaw('MAX(Revno)')
                                                ->from('yfBOM_BOMItemHP')
                                                ->where('PrdID', $intermediateCode);
                                })
                                ->distinct()
                                ->orderBy('MatID')
                                ->get();
                } catch (\Throwable $e) {
                        Log::error('Không lấy được công thức MMS cho ma trận cảnh báo nguồn NL: ' . $e->getMessage());

                        return [
                                'material_success' => false,
                                'material_message' => 'Không lấy được công thức từ MMS: ' . $e->getMessage(),
                                'materials' => [],
                        ];
                }

                if ($materials->isEmpty()) {
                        return [
                                'material_success' => false,
                                'material_message' => 'Mã ' . $intermediateCode . ' chưa có công thức trên MMS.',
                                'materials' => [],
                        ];
                }

                return [
                        'material_success' => true,
                        'material_message' => 'Danh sách NL của công thức ấn bản mới nhất trên MMS.',
                        'revision' => (int) round((float) $materials->first()->Revno1),
                        'materials' => $materials->map(fn($item) => [
                                'mat_id' => trim((string) $item->MatID),
                                'mat_name' => trim((string) $item->MaterialName),
                                'uom' => trim((string) $item->uom),
                        ])->values(),
                ];
        }

        /**
         * Thiết bị đã được định mức (bảng quota) của 1 mã BTP, gom theo công đoạn.
         * Chỉ những thiết bị này mới được phép khai báo trong ma trận.
         */
        private function quotaStageRooms(string $intermediateCode, string $deparmentCode): array
        {
                $rows = DB::table('quota')
                        ->select(
                                'quota.stage_code',
                                'stages.name as stage_name',
                                'room.code',
                                'room.name',
                                'room.main_equiment_name',
                                'room.order_by'
                        )
                        ->join('room', 'room.id', 'quota.room_id')
                        ->leftJoin('stages', 'stages.code', 'quota.stage_code')
                        ->where('quota.intermediate_code', $intermediateCode)
                        ->where('quota.deparment_code', $deparmentCode)
                        ->where('quota.active', 1)
                        ->where('room.active', 1)
                        ->whereIn('quota.stage_code', self::STAGE_CODES)
                        ->distinct()
                        ->orderBy('quota.stage_code')
                        ->orderBy('room.order_by')
                        ->orderBy('room.code')
                        ->get();

                $stages = [];

                foreach ($rows as $row) {
                        $stages[$row->stage_code]['stage_code'] = (int) $row->stage_code;
                        $stages[$row->stage_code]['stage_name'] = $row->stage_name ?: ('Công đoạn ' . $row->stage_code);
                        $stages[$row->stage_code]['rooms'][] = [
                                'code' => $row->code,
                                'name' => $row->name,
                                'main_equiment_name' => $row->main_equiment_name,
                        ];
                }

                return array_values($stages);
        }

        public function store(Request $request)
        {
                $validator = $this->validateMatrix($request);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                $deparmentCode = session('user')['production_code'];

                if ($this->matrixExists($request, $deparmentCode)) {
                        return redirect()->back()
                                ->withErrors(
                                        ['material_code' => 'Ma trận của mã BTP - mã NL - thị trường này đã được khai báo.'],
                                        'createErrors'
                                )
                                ->withInput();
                }

                if ($outside = $this->roomsOutsideQuota($request, $deparmentCode)) {
                        return redirect()->back()
                                ->withErrors(
                                        ['rooms' => 'Thiết bị chưa có định mức cho mã BTP này: ' . implode(', ', $outside)],
                                        'createErrors'
                                )
                                ->withInput();
                }

                DB::transaction(function () use ($request, $deparmentCode) {
                        $warningId = DB::table('material_source_warning')->insertGetId([
                                'intermediate_code' => trim($request->intermediate_code),
                                'material_code' => trim($request->material_code),
                                'material_name' => $request->material_name ?: null,
                                'market_id' => $request->market_id,
                                'bom_revision' => $request->bom_revision ?: null,
                                'note' => $request->note ?: null,
                                'deparment_code' => $deparmentCode,
                                'active' => 1,
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                                'updated_at' => now(),
                        ]);

                        $this->syncRooms($warningId, $request->input('rooms', []));
                });

                return redirect()->back()->with('success', 'Đã thêm ma trận cảnh báo nguồn NL!');
        }

        public function update(Request $request)
        {
                $validator = $this->validateMatrix($request);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                }

                $deparmentCode = session('user')['production_code'];

                $warning = DB::table('material_source_warning')
                        ->where('id', $request->id)
                        ->where('deparment_code', $deparmentCode)
                        ->first();

                if (!$warning) {
                        return redirect()->back()->with('error', 'Không tìm thấy ma trận cần cập nhật.');
                }

                if ($this->matrixExists($request, $deparmentCode, $warning->id)) {
                        return redirect()->back()
                                ->withErrors(
                                        ['material_code' => 'Ma trận của mã BTP - mã NL - thị trường này đã được khai báo.'],
                                        'updateErrors'
                                )
                                ->withInput();
                }

                if ($outside = $this->roomsOutsideQuota($request, $deparmentCode)) {
                        return redirect()->back()
                                ->withErrors(
                                        ['rooms' => 'Thiết bị chưa có định mức cho mã BTP này: ' . implode(', ', $outside)],
                                        'updateErrors'
                                )
                                ->withInput();
                }

                DB::transaction(function () use ($request, $warning) {
                        DB::table('material_source_warning')->where('id', $warning->id)->update([
                                'intermediate_code' => trim($request->intermediate_code),
                                'material_code' => trim($request->material_code),
                                'material_name' => $request->material_name ?: null,
                                'market_id' => $request->market_id,
                                'bom_revision' => $request->bom_revision ?: null,
                                'note' => $request->note ?: null,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(),
                        ]);

                        DB::table('material_source_warning_room')->where('warning_id', $warning->id)->delete();

                        $this->syncRooms($warning->id, $request->input('rooms', []));
                });

                return redirect()->back()->with('success', 'Đã cập nhật ma trận cảnh báo nguồn NL!');
        }

        public function deActive(Request $request)
        {
                DB::table('material_source_warning')
                        ->where('id', $request->id)
                        ->where('deparment_code', session('user')['production_code'])
                        ->update([
                                'active' => !$request->active,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(),
                        ]);

                return redirect()->back()->with('success', 'Đã đổi trạng thái ma trận!');
        }

        /** Ràng buộc dùng chung cho cả thêm mới và cập nhật */
        private function validateMatrix(Request $request)
        {
                return Validator::make($request->all(), [
                        'intermediate_code' => 'required',
                        'material_code' => 'required',
                        'market_id' => 'required|exists:market,id',
                        'rooms' => 'required|array|min:1',
                ], [
                        'intermediate_code.required' => 'Vui lòng chọn mã bán thành phẩm.',
                        'material_code.required' => 'Vui lòng chọn mã nguyên liệu từ công thức MMS.',
                        'market_id.required' => 'Vui lòng chọn thị trường.',
                        'market_id.exists' => 'Thị trường không hợp lệ.',
                        'rooms.required' => 'Vui lòng chọn ít nhất 1 thiết bị được phép thực hiện.',
                        'rooms.min' => 'Vui lòng chọn ít nhất 1 thiết bị được phép thực hiện.',
                ]);
        }

        /** Một mã BTP + mã NL + thị trường chỉ được khai báo 1 lần trong cùng phân xưởng */
        private function matrixExists(Request $request, string $deparmentCode, $exceptId = null): bool
        {
                return DB::table('material_source_warning')
                        ->where('deparment_code', $deparmentCode)
                        ->where('intermediate_code', trim($request->intermediate_code))
                        ->where('material_code', trim($request->material_code))
                        ->where('market_id', $request->market_id)
                        ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
                        ->exists();
        }

        /**
         * Thiết bị người dùng gửi lên nhưng chưa có định mức của mã BTP đó.
         * Form chỉ hiện thiết bị đã có định mức, đây là lớp chặn phía server.
         *
         * @return array Nhãn "công đoạn - thiết bị" của các dòng không hợp lệ
         */
        private function roomsOutsideQuota(Request $request, string $deparmentCode): array
        {
                $allowed = [];

                foreach ($this->quotaStageRooms(trim($request->intermediate_code), $deparmentCode) as $stage) {
                        foreach ($stage['rooms'] as $room) {
                                $allowed[$stage['stage_code'] . '|' . $room['code']] = true;
                        }
                }

                $outside = [];

                foreach ((array) $request->input('rooms', []) as $stageCode => $roomCodes) {
                        foreach ((array) $roomCodes as $roomCode) {
                                if (!isset($allowed[(int) $stageCode . '|' . trim((string) $roomCode)])) {
                                        $outside[] = 'CĐ ' . $stageCode . ' - ' . $roomCode;
                                }
                        }
                }

                return $outside;
        }

        /**
         * Ghi danh sách thiết bị được phép của ma trận.
         * Dữ liệu từ form có dạng rooms[<mã công đoạn>][] = <mã thiết bị>.
         */
        private function syncRooms($warningId, $rooms): void
        {
                $records = [];

                foreach ((array) $rooms as $stageCode => $roomCodes) {
                        if (!in_array((int) $stageCode, self::STAGE_CODES, true)) {
                                continue;
                        }

                        foreach (array_unique((array) $roomCodes) as $roomCode) {
                                $roomCode = trim((string) $roomCode);

                                if ($roomCode === '') {
                                        continue;
                                }

                                $records[] = [
                                        'warning_id' => $warningId,
                                        'stage_code' => (int) $stageCode,
                                        'room_code' => $roomCode,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                ];
                        }
                }

                if ($records) {
                        DB::table('material_source_warning_room')->insert($records);
                }
        }

        /**
         * Thiết bị đã khai báo của từng ma trận, gom theo công đoạn.
         *
         * @return array [warning_id => [stage_code => ['stage_name' => ..., 'rooms' => [...]]]]
         */
        private function roomsByWarning(Collection $warningIds, string $deparmentCode): array
        {
                if ($warningIds->isEmpty()) {
                        return [];
                }

                $rows = DB::table('material_source_warning_room as r')
                        ->select(
                                'r.warning_id',
                                'r.stage_code',
                                'r.room_code',
                                'room.name as room_name',
                                'room.main_equiment_name',
                                'room.order_by',
                                'stages.name as stage_name'
                        )
                        ->leftJoin('room', function ($join) use ($deparmentCode) {
                                $join->on('room.code', '=', 'r.room_code')
                                        ->where('room.deparment_code', $deparmentCode);
                        })
                        ->leftJoin('stages', 'stages.code', 'r.stage_code')
                        ->whereIn('r.warning_id', $warningIds)
                        ->orderBy('r.stage_code')
                        ->orderBy('room.order_by')
                        ->orderBy('r.room_code')
                        ->get();

                $grouped = [];

                foreach ($rows as $row) {
                        $grouped[$row->warning_id][$row->stage_code]['stage_name'] = $row->stage_name ?: ('CĐ ' . $row->stage_code);
                        $grouped[$row->warning_id][$row->stage_code]['rooms'][] = [
                                'code' => $row->room_code,
                                'name' => $row->room_name,
                                'main_equiment_name' => $row->main_equiment_name,
                        ];
                }

                return $grouped;
        }

        /** Thiết bị đang hoạt động của phân xưởng, gom theo công đoạn - dùng dựng ma trận trên form */
        /** Mã BTP đang hiệu lực của phân xưởng */
        private function intermediateOptions(string $deparmentCode): Collection
        {
                return DB::table('intermediate_category')
                        ->select('intermediate_category.intermediate_code', 'product_name.name as product_name')
                        ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                        ->where('intermediate_category.deparment_code', $deparmentCode)
                        ->where('intermediate_category.cancel', 0)
                        ->where('intermediate_category.active', 1)
                        ->orderBy('intermediate_category.intermediate_code', 'asc')
                        ->get();
        }
}
