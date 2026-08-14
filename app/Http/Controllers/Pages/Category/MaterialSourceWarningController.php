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
        /**
         * Các công đoạn được khai báo thiết bị trong ma trận.
         * Bỏ 2 công đoạn cân (1 - Cân Nguyên Liệu, 2 - Cân Nguyên Liệu Khác) vì nguồn NL
         * không ràng buộc theo buồng cân.
         */
        private const STAGE_CODES = [3, 4, 5, 6, 7];

        public function index()
        {
                session()->put(['title' => 'CẢNH BÁO NGUỒN NL']);

                $deparmentCode = session('user')['production_code'];

                $datas = DB::table('material_source_warning as w')
                        ->select(
                                'w.*',
                                'product_name.name as product_name',
                                'intermediate_category.batch_qty',
                                'intermediate_category.unit_batch_qty',
                                'intermediate_category.batch_size',
                                'intermediate_category.unit_batch_size'
                        )
                        ->leftJoin('intermediate_category', function ($join) {
                                $join->on('intermediate_category.intermediate_code', '=', 'w.intermediate_code')
                                        ->on('intermediate_category.deparment_code', '=', 'w.deparment_code')
                                        ->where('intermediate_category.cancel', 0);
                        })
                        ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                        ->where('w.deparment_code', $deparmentCode)
                        ->orderBy('product_name.name', 'asc')
                        ->orderBy('w.intermediate_code', 'asc')
                        ->orderBy('w.material_code', 'asc')
                        ->get();

                $roomsByWarning = $this->roomsByWarning($datas->pluck('id'), $deparmentCode);

                return view('pages.category.material_source_warning.list', [
                        'datas' => $datas,
                        'roomsByWarning' => $roomsByWarning,
                        'marketsByWarning' => $this->marketsByWarning($datas->pluck('id')),
                        'stageColumns' => $this->stageColumns($roomsByWarning),
                        'intermediates' => $this->intermediateOptions($deparmentCode),
                        'markets' => DB::table('market')->where('active', 1)->orderBy('code', 'asc')->get(),
                ]);
        }

        /**
         * Các công đoạn cần hiện thành cột riêng trên bảng ma trận
         * (Pha chế, Định hình, Bao phim...) - chỉ những công đoạn đang thực sự có khai báo.
         *
         * @param  array $roomsByWarning Kết quả của roomsByWarning()
         * @return array [stage_code => stage_name] đã sắp theo thứ tự công đoạn
         */
        private function stageColumns(array $roomsByWarning): array
        {
                $columns = [];

                foreach ($roomsByWarning as $stages) {
                        foreach ($stages as $stageCode => $stage) {
                                $columns[$stageCode] = $stage['stage_name'];
                        }
                }

                ksort($columns);

                return $columns;
        }

        /**
         * Dữ liệu dựng form theo 1 mã BTP:
         * - Danh sách mã NL lấy từ BOM ấn bản mới nhất trên MMS
         * - Cỡ lô của mã BTP
         * - Ma trận thiết bị: toàn bộ thiết bị của phân xưởng theo từng công đoạn
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
                        [
                                'stages' => $this->stageRooms($deparmentCode),
                                'batch' => $this->batchInfo($intermediateCode, $deparmentCode),
                        ]
                ));
        }

        /**
         * Dữ liệu cảnh báo dùng ở màn tạo/sửa lô: ma trận đang hiệu lực của 1 mã BTP,
         * đối chiếu với thị trường của mã TP đang tạo lô.
         *
         * Nhận vào plan_master_id (màn sửa lô) hoặc product_caterogy_id (màn tạo lô).
         */
        public function marketCheck(Request $request)
        {
                $deparmentCode = session('user')['production_code'];
                $productCategoryId = $request->product_caterogy_id;

                if ($request->plan_master_id) {
                        $productCategoryId = DB::table('plan_master')
                                ->where('id', $request->plan_master_id)
                                ->value('product_caterogy_id');
                }

                $product = $productCategoryId
                        ? DB::table('finished_product_category as f')
                                ->select('f.intermediate_code', 'f.market_id', 'market.code', 'market.name')
                                ->leftJoin('market', 'market.id', 'f.market_id')
                                ->where('f.id', $productCategoryId)
                                ->first()
                        : null;

                $intermediateCode = trim((string) $request->intermediate_code) ?: (string) ($product->intermediate_code ?? '');

                if ($intermediateCode === '') {
                        return response()->json([
                                'market' => null,
                                'warnings' => (object) [],
                                'general_warnings' => [],
                        ]);
                }

                $warnings = DB::table('material_source_warning')
                        ->where('deparment_code', $deparmentCode)
                        ->where('intermediate_code', $intermediateCode)
                        ->where('active', 1)
                        ->get();

                $marketsByWarning = $this->marketsByWarning($warnings->pluck('id'));
                $result = [];
                $general = [];

                foreach ($warnings as $warning) {
                        $markets = $marketsByWarning[$warning->id] ?? [];

                        // Dòng không khai thị trường thì chỉ ràng buộc thiết bị -> không cảnh báo ở màn tạo lô
                        if (!$markets) {
                                continue;
                        }

                        // Lô chỉ mang thị trường ở cấp quốc gia nên đối chiếu theo market_id;
                        // kênh/khách hàng (vd: Tender) chỉ đưa vào nhãn để người dùng tự đối chiếu.
                        $allowed = $product && $product->market_id
                                && collect($markets)->contains(fn($market) => (int) $market['market_id'] === (int) $product->market_id);

                        $entry = [
                                'material_name' => $warning->material_name,
                                'allowed' => $allowed,
                                'markets' => collect($markets)
                                        ->map(fn($market) => $market['code'] . ($market['channel'] !== '' ? ' (' . $market['channel'] . ')' : ''))
                                        ->values()
                                        ->all(),
                                'note' => $warning->note,
                        ];

                        $materialCode = trim((string) $warning->material_code);

                        // Không khai mã NL -> ràng buộc thị trường cho cả mã BTP, không gắn vào dòng NL nào
                        if ($materialCode === '') {
                                if (!$allowed) {
                                        $general[] = $entry;
                                }

                                continue;
                        }

                        $result[$materialCode] = $entry;
                }

                return response()->json([
                        'market' => $product && $product->market_id
                                ? ['id' => $product->market_id, 'code' => $product->code, 'name' => $product->name]
                                : null,
                        'warnings' => (object) $result,
                        'general_warnings' => $general,
                ]);
        }

        /** Cỡ lô của mã BTP - chỉ để hiển thị trên form, không lưu lại trong ma trận */
        private function batchInfo(string $intermediateCode, string $deparmentCode): ?array
        {
                $row = DB::table('intermediate_category')
                        ->select('batch_qty', 'unit_batch_qty', 'batch_size', 'unit_batch_size')
                        ->where('intermediate_code', $intermediateCode)
                        ->where('deparment_code', $deparmentCode)
                        ->where('cancel', 0)
                        ->first();

                if (!$row) {
                        return null;
                }

                return [
                        'batch_qty' => $row->batch_qty,
                        'unit_batch_qty' => $row->unit_batch_qty,
                        'batch_size' => $row->batch_size,
                        'unit_batch_size' => $row->unit_batch_size,
                ];
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
         * Toàn bộ thiết bị đang hoạt động của phân xưởng, gom theo công đoạn.
         * Ma trận khai theo thiết bị có thể chạy được về mặt kỹ thuật nên không lọc
         * theo định mức (bảng quota) của mã BTP.
         */
        private function stageRooms(string $deparmentCode): array
        {
                $rows = DB::table('room')
                        ->select(
                                'room.stage_code',
                                'stages.name as stage_name',
                                'room.code',
                                'room.name',
                                'room.main_equiment_name',
                                'room.order_by'
                        )
                        ->leftJoin('stages', 'stages.code', 'room.stage_code')
                        ->where('room.deparment_code', $deparmentCode)
                        ->where('room.active', 1)
                        ->whereIn('room.stage_code', self::STAGE_CODES)
                        ->distinct()
                        ->orderBy('room.stage_code')
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

                if ($duplicated = $this->duplicatedMarkets($request, $deparmentCode)) {
                        return redirect()->back()
                                ->withErrors(
                                        ['markets' => 'Thị trường đã được khai báo ở dòng ma trận khác của mã BTP - mã NL này: ' . implode(', ', $duplicated)],
                                        'createErrors'
                                )
                                ->withInput();
                }

                if ($outside = $this->roomsOutsideDeparment($request, $deparmentCode)) {
                        return redirect()->back()
                                ->withErrors(
                                        ['rooms' => 'Thiết bị không thuộc danh sách thiết bị của phân xưởng: ' . implode(', ', $outside)],
                                        'createErrors'
                                )
                                ->withInput();
                }

                DB::transaction(function () use ($request, $deparmentCode) {
                        $warningId = DB::table('material_source_warning')->insertGetId([
                                'intermediate_code' => trim($request->intermediate_code),
                                'material_code' => $this->materialCode($request),
                                'material_name' => $request->material_name ?: null,
                                'bom_revision' => $request->bom_revision ?: null,
                                'note' => $request->note ?: null,
                                'deparment_code' => $deparmentCode,
                                'active' => 1,
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                                'updated_at' => now(),
                        ]);

                        $this->syncRooms($warningId, $request->input('rooms', []), $request->input('room_reminders', []));
                        $this->syncMarkets($warningId, $request->input('markets', []));
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

                if ($duplicated = $this->duplicatedMarkets($request, $deparmentCode, $warning->id)) {
                        return redirect()->back()
                                ->withErrors(
                                        ['markets' => 'Thị trường đã được khai báo ở dòng ma trận khác của mã BTP - mã NL này: ' . implode(', ', $duplicated)],
                                        'updateErrors'
                                )
                                ->withInput();
                }

                if ($outside = $this->roomsOutsideDeparment($request, $deparmentCode)) {
                        return redirect()->back()
                                ->withErrors(
                                        ['rooms' => 'Thiết bị không thuộc danh sách thiết bị của phân xưởng: ' . implode(', ', $outside)],
                                        'updateErrors'
                                )
                                ->withInput();
                }

                DB::transaction(function () use ($request, $warning) {
                        DB::table('material_source_warning')->where('id', $warning->id)->update([
                                'intermediate_code' => trim($request->intermediate_code),
                                'material_code' => $this->materialCode($request),
                                'material_name' => $request->material_name ?: null,
                                'bom_revision' => $request->bom_revision ?: null,
                                'note' => $request->note ?: null,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(),
                        ]);

                        DB::table('material_source_warning_room')->where('warning_id', $warning->id)->delete();
                        DB::table('material_source_warning_market')->where('warning_id', $warning->id)->delete();

                        $this->syncRooms($warning->id, $request->input('rooms', []), $request->input('room_reminders', []));
                        $this->syncMarkets($warning->id, $request->input('markets', []));
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
                $validator = Validator::make($request->all(), [
                        'intermediate_code' => 'required',
                        'material_code' => 'nullable',
                        'markets' => 'nullable|array',
                        'markets.*.market_id' => 'nullable|exists:market,id',
                        'markets.*.channel' => 'nullable|string|max:50',
                        'rooms' => 'nullable|array',
                ], [
                        'intermediate_code.required' => 'Vui lòng chọn mã bán thành phẩm.',
                        'markets.*.market_id.exists' => 'Thị trường không hợp lệ.',
                        'markets.*.channel.max' => 'Kênh/khách hàng tối đa 50 ký tự.',
                ]);

                // Mã NL, thị trường và thiết bị đều có thể bỏ trống - phần nào bỏ trống
                // thì không ràng buộc phần đó. Nhưng bỏ trống cả thị trường lẫn thiết bị
                // thì dòng khai báo không cảnh báo được gì.
                $validator->after(function ($validator) use ($request) {
                        if (!$this->selectedRooms($request) && !$this->normalizeMarkets($request->input('markets', []))) {
                                $validator->errors()->add(
                                        'rooms',
                                        'Phải khai báo ít nhất thị trường được phép hoặc thiết bị được phép, nếu không dòng ma trận sẽ không cảnh báo được gì.'
                                );
                        }
                });

                return $validator;
        }

        /** Mã NL của form - để trống nghĩa là ma trận áp dụng cho mọi nguồn NL của mã BTP */
        private function materialCode(Request $request): ?string
        {
                return trim((string) $request->material_code) ?: null;
        }

        /**
         * Mã thiết bị đã tick trên form, gom theo công đoạn (đã bỏ giá trị rỗng).
         *
         * @return array [stage_code => [mã thiết bị, ...]]
         */
        private function selectedRooms(Request $request): array
        {
                $selected = [];

                foreach ((array) $request->input('rooms', []) as $stageCode => $roomCodes) {
                        foreach ((array) $roomCodes as $roomCode) {
                                $roomCode = trim((string) $roomCode);

                                if ($roomCode !== '') {
                                        $selected[$stageCode][] = $roomCode;
                                }
                        }
                }

                return $selected;
        }

        /**
         * Cùng 1 mã BTP + mã NL thì mỗi thị trường (kèm kênh) chỉ được khai 1 lần trong phân xưởng,
         * kể cả khi khai rải ra nhiều dòng ma trận có bộ thiết bị khác nhau.
         *
         * @return array Nhãn thị trường bị trùng - gồm cả trùng ngay trong form đang gửi lên
         */
        private function duplicatedMarkets(Request $request, string $deparmentCode, $exceptId = null): array
        {
                $submitted = [];
                $duplicated = [];

                foreach ($this->normalizeMarkets($request->input('markets', [])) as $market) {
                        $key = $market['market_id'] . '|' . mb_strtolower($market['channel']);

                        if (isset($submitted[$key])) {
                                $duplicated[$key] = $market;

                                continue;
                        }

                        $submitted[$key] = $market;
                }

                if (!$submitted) {
                        return [];
                }

                $materialCode = $this->materialCode($request);

                $existing = DB::table('material_source_warning_market as m')
                        ->join('material_source_warning as w', 'w.id', 'm.warning_id')
                        ->where('w.deparment_code', $deparmentCode)
                        ->where('w.intermediate_code', trim((string) $request->intermediate_code))
                        ->when(
                                $materialCode === null,
                                fn($q) => $q->whereNull('w.material_code'),
                                fn($q) => $q->where('w.material_code', $materialCode)
                        )
                        ->when($exceptId, fn($q) => $q->where('w.id', '!=', $exceptId))
                        ->select('m.market_id', 'm.channel')
                        ->get();

                foreach ($existing as $row) {
                        $key = $row->market_id . '|' . mb_strtolower((string) $row->channel);

                        if (isset($submitted[$key])) {
                                $duplicated[$key] = $submitted[$key];
                        }
                }

                if (!$duplicated) {
                        return [];
                }

                $codes = DB::table('market')
                        ->whereIn('id', collect($duplicated)->pluck('market_id'))
                        ->pluck('code', 'id');

                return collect($duplicated)
                        ->map(fn($market) => trim(
                                ($codes[$market['market_id']] ?? $market['market_id'])
                                        . ($market['channel'] !== '' ? ' (' . $market['channel'] . ')' : '')
                        ))
                        ->values()
                        ->all();
        }

        /**
         * Chuẩn hoá danh sách thị trường từ form: markets[<số thứ tự>][market_id|channel].
         *
         * @return array [['market_id' => int, 'channel' => string], ...]
         */
        private function normalizeMarkets($markets): array
        {
                $result = [];

                foreach ((array) $markets as $market) {
                        $marketId = (int) ($market['market_id'] ?? 0);

                        if ($marketId <= 0) {
                                continue;
                        }

                        $result[] = [
                                'market_id' => $marketId,
                                'channel' => trim((string) ($market['channel'] ?? '')),
                        ];
                }

                return $result;
        }

        /** Ghi danh sách thị trường được phép của ma trận */
        private function syncMarkets($warningId, $markets): void
        {
                $records = [];

                foreach ($this->normalizeMarkets($markets) as $market) {
                        // Unique của bảng là (warning_id, market_id, channel) nên phải khử trùng trước khi insert
                        $records[$market['market_id'] . '|' . mb_strtolower($market['channel'])] = [
                                'warning_id' => $warningId,
                                'market_id' => $market['market_id'],
                                'channel' => $market['channel'],
                                'created_at' => now(),
                                'updated_at' => now(),
                        ];
                }

                if ($records) {
                        DB::table('material_source_warning_market')->insert(array_values($records));
                }
        }

        /**
         * Thị trường được phép của từng ma trận.
         *
         * @return array [warning_id => [['market_id' => ..., 'code' => ..., 'name' => ..., 'channel' => ...], ...]]
         */
        private function marketsByWarning(Collection $warningIds): array
        {
                if ($warningIds->isEmpty()) {
                        return [];
                }

                $rows = DB::table('material_source_warning_market as m')
                        ->select('m.warning_id', 'm.market_id', 'm.channel', 'market.code', 'market.name')
                        ->leftJoin('market', 'market.id', 'm.market_id')
                        ->whereIn('m.warning_id', $warningIds)
                        ->orderBy('market.code')
                        ->orderBy('m.channel')
                        ->get();

                $grouped = [];

                foreach ($rows as $row) {
                        $grouped[$row->warning_id][] = [
                                'market_id' => $row->market_id,
                                'code' => $row->code,
                                'name' => $row->name,
                                'channel' => (string) $row->channel,
                        ];
                }

                return $grouped;
        }

        /**
         * Thiết bị người dùng gửi lên nhưng không thuộc phân xưởng / công đoạn được khai báo.
         * Form chỉ hiện thiết bị hợp lệ, đây là lớp chặn phía server.
         *
         * @return array Nhãn "công đoạn - thiết bị" của các dòng không hợp lệ
         */
        private function roomsOutsideDeparment(Request $request, string $deparmentCode): array
        {
                $allowed = [];

                foreach ($this->stageRooms($deparmentCode) as $stage) {
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
        private function syncRooms($warningId, $rooms, $reminders = []): void
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

                                // Nhắc nhở khai theo từng thiết bị: room_reminders[<công đoạn>][<mã thiết bị>]
                                $reminder = trim((string) (((array) $reminders)[$stageCode][$roomCode] ?? ''));

                                $records[] = [
                                        'warning_id' => $warningId,
                                        'stage_code' => (int) $stageCode,
                                        'room_code' => $roomCode,
                                        'reminder' => $reminder !== '' ? mb_substr($reminder, 0, 255) : null,
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
                                'r.reminder',
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
                                'reminder' => (string) $row->reminder,
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
