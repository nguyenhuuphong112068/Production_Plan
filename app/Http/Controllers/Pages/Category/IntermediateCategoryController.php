<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IntermediateCategoryController extends Controller
{
        private function logHistory($id)
        {
                $cat = DB::table('intermediate_category')->where('id', $id)->first();
                if ($cat) {
                        $data = (array) $cat;
                        $data['category_id'] = $data['id'];
                        unset($data['id']);
                        // Handle nulls for booleans if needed, but they are set in original table.
                        DB::table('intermediate_category_history')->insert($data);
                }
        }

        public function history(Request $request)
        {
                $history = DB::table('intermediate_category_history')
                        ->select('intermediate_category_history.*', 'dosage.name as dosage_name', 'product_name.name as product_name', 'pharmacist.fullName as pharmacist_name')
                        ->leftJoin('product_name', 'intermediate_category_history.product_name_id', 'product_name.id')
                        ->leftJoin('dosage', 'intermediate_category_history.dosage_id', 'dosage.id')
                        ->leftJoin('user_management as pharmacist', 'intermediate_category_history.pharmacist_id', 'pharmacist.id')
                        ->where('intermediate_category_history.category_id', $request->category_id)
                        ->orderBy('intermediate_category_history.id', 'desc')
                        ->get();

                $current = DB::table('intermediate_category')
                        ->select('intermediate_category.*', 'dosage.name as dosage_name', 'product_name.name as product_name', 'pharmacist.fullName as pharmacist_name')
                        ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                        ->leftJoin('dosage', 'intermediate_category.dosage_id', 'dosage.id')
                        ->leftJoin('user_management as pharmacist', 'intermediate_category.pharmacist_id', 'pharmacist.id')
                        ->where('intermediate_category.id', $request->category_id)
                        ->first();

                return response()->json([
                        'current' => $current,
                        'history' => $history
                ]);
        }

        public function index()
        {

                $productNames = DB::table('product_name')->where('active', true)->orderBy('name', 'asc')->get();
                $dosages = DB::table('dosage')->where('active', true)->get();
                $units = DB::table('unit')->where('active', true)->get();
                $pharmacists = pharmacist_options();

                $datas = DB::table('intermediate_category')->select('intermediate_category.*', 'dosage.name as dosage_name', 'product_name.name as product_name', 'pharmacist.fullName as pharmacist_name')
                        ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                        ->leftJoin('dosage', 'intermediate_category.dosage_id', 'dosage.id')
                        ->leftJoin('user_management as pharmacist', 'intermediate_category.pharmacist_id', 'pharmacist.id')
                        ->where('intermediate_category.deparment_code', session('user')['production_code'])
                        ->when(
                                !user_has_permission(session('user')['userId'], 'view_Hypothesis_category', 'boolean'),
                                function ($q) {
                                        return $q->where('intermediate_category.IsHypothesis', 0);
                                }
                        )
                        ->where('cancel', 0)
                        ->orderBy('intermediate_category.IsHypothesis', 'desc')
                        ->orderBy('product_name.name', 'asc')->get();

                $historyCounts = DB::table('intermediate_category_history')
                        ->select('category_id', DB::raw('count(*) as total'))
                        ->groupBy('category_id')
                        ->get()
                        ->keyBy('category_id');

                // Ấn bản công thức MMS của từng mã BTP: mã không có ở đây là mã chưa có công thức trên MMS.
                $mmsRevisions = mms_bom_revisions($datas->pluck('intermediate_code'));
                $hypothesisBomCounts = hypothesis_bom_counts(0);

                session()->put(['title' => 'DANH MỤC BÁN THÀNH PHẨM']);

                return view('pages.category.intermediate.list', [
                        'datas' => $datas,
                        'productNames' => $productNames,
                        'dosages' => $dosages,
                        'units' => $units,
                        'pharmacists' => $pharmacists,
                        'historyCounts' => $historyCounts,
                        'mmsRevisions' => $mmsRevisions,
                        'hypothesisBomCounts' => $hypothesisBomCounts
                ]);
        }

        public function store(Request $request)
        {
                //dd ($request->all());


                $validator = Validator::make($request->all(), [
                        'intermediate_code' => 'required|unique:intermediate_category,intermediate_code',
                        'product_name_id' => 'required',
                        'dosage_id' => 'required',
                        'batch_size' => 'required',
                        'batch_qty' => 'required',
                        'unit_batch_qty' => 'required',
                ], [
                        'intermediate_code.required' => 'Vui lòng nhập mã bán thành phẩm.',
                        'intermediate_code.unique' => 'Mã bán thành phẩm đã tồn tại.',
                        'product_name_id.required' => 'Vui lòng chọn tên sản phẩm',
                        'dosage_id.required' => 'Vui lòng chọn dạng bào chế',
                        'batch_size.required' => 'Vui lòng nhập cỡ lô',
                        'batch_qty.required' => 'Vui lòng nhập cỡ lô',
                        'unit_batch_qty.required' => 'Vui lòng chọn đơn vị '
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                $dosage_name = DB::table('dosage')->where('id', $request->dosage_id)->value('name');


                if (Str::contains(Str::lower($dosage_name), ['phim', 'nang'])) {
                        $weight_2 = true;
                } else {
                        $weight_2 = false;
                };

                if (session('user')['production_code'] == 'PXTN' && $request->has_packaging_process == '1') {
                        $weight_2 = 2;
                }

                DB::table('intermediate_category')->insert([
                        'intermediate_code' => $request->intermediate_code,
                        'product_name_id' => $request->product_name_id,
                        'batch_size' => $request->batch_size,
                        'unit_batch_size' => $request->unit_batch_size,
                        'batch_qty' => $request->batch_qty,
                        'unit_batch_qty' => $request->unit_batch_qty,

                        'dosage_id' => $request->dosage_id,
                        'weight_1' => $request->has('weight_1_chk') ? ($request->weight_1 ?? '1') : '0',
                        'weight_2' => $weight_2,
                        'prepering' => $request->has('prepering_chk') ? ($request->prepering ?? '1') : '0',
                        'blending' => $request->has('blending_chk') ? ($request->blending ?? '1') : '0',
                        'forming' => $request->has('forming_chk') ? ($request->forming ?? '1') : '0',
                        'coating' => $request->has('coating_chk') ? ($request->coating ?? '1') : '0',

                        'quarantine_total' => $request->quarantine_total ?? 0,
                        'quarantine_weight' => $request->quarantine_weight ?? 0,
                        'quarantine_preparing' => $request->quarantine_preparing ?? 0,
                        'quarantine_blending' => $request->quarantine_blending ?? 0,
                        'quarantine_forming' => $request->quarantine_forming ?? 0,
                        'quarantine_coating' => $request->quarantine_coating ?? 0,
                        'quarantine_time_unit' => $request->quarantine_time_unit === "on" ? true : false,
                        'IsHypothesis' => $request->is_Hypothesis ?? 0,
                        'deparment_code' => session('user')['production_code'],
                        'pharmacist_id' => $request->pharmacist_id ?: null,
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                return redirect()->back()->with('success', 'Đã thêm thành công!');
        }

        public function update(Request $request)
        {

                $validator = Validator::make($request->all(), [
                        //'intermediate_code' => 'required|unique:intermediate_category,intermediate_code',
                        'product_name_id' => 'required',
                        'dosage_id' => 'required',
                        'batch_size' => 'required',
                        'batch_qty' => 'required',
                        'unit_batch_qty' => 'required',
                ], [
                        //'intermediate_code.required' => 'Vui lòng nhập mã bán thành phẩm.',
                        'intermediate_code.unique' => 'Mã bán thành phẩm đã tồn tại.',
                        'product_name_id.required' => 'Vui lòng chọn tên sản phẩm',
                        'dosage_id.required' => 'Vui lòng chọn dạng bào chế',
                        'batch_size.required' => 'Vui lòng nhập cỡ lô',
                        'batch_qty.required' => 'Vui lòng nhập cỡ lô',
                        'unit_batch_qty.required' => 'Vui lòng chọn đơn vị '
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                }
                $dosage_name = DB::table('dosage')->where('id', $request->dosage_id)->value('name');
                if (Str::contains(Str::lower($dosage_name), ['phim', 'nang'])) {
                        $weight_2 = true;
                } else {
                        $weight_2 = false;
                }

                if (session('user')['production_code'] == 'PXTN' && $request->has_packaging_process == '1') {
                        $weight_2 = 2;
                }

                // Lấy mã BTP đang lưu để tìm các mã TP gắn với nó (ô mã trên form là readonly nên mã không đổi).
                $intermediate_code = DB::table('intermediate_category')->where('id', $request->id)->value('intermediate_code');

                // Sửa cỡ lô BTP và đồng bộ cỡ lô cho các mã TP phải cùng thành công hoặc cùng huỷ.
                $synced = DB::transaction(function () use ($request, $weight_2, $intermediate_code) {

                        $this->logHistory($request->id);

                        DB::table('intermediate_category')->where('id', $request->id)->update([

                                'intermediate_code' => $request->intermediate_code,
                                'product_name_id' => $request->product_name_id,
                                'batch_size' => $request->batch_size,
                                'unit_batch_size' => $request->unit_batch_size,
                                'batch_qty' => $request->batch_qty,
                                'unit_batch_qty' => $request->unit_batch_qty,

                                'dosage_id' => $request->dosage_id,
                                'weight_1' => $request->has('weight_1_chk') ? ($request->weight_1 ?? '1') : '0',
                                'weight_2' => $weight_2,
                                'prepering' => $request->has('prepering_chk') ? ($request->prepering ?? '1') : '0',
                                'blending' => $request->has('blending_chk') ? ($request->blending ?? '1') : '0',
                                'forming' => $request->has('forming_chk') ? ($request->forming ?? '1') : '0',
                                'coating' => $request->has('coating_chk') ? ($request->coating ?? '1') : '0',

                                'quarantine_total' => $request->quarantine_total ?? 0,
                                'quarantine_weight' => $request->quarantine_weight ?? 0,
                                'quarantine_preparing' => $request->quarantine_preparing ?? 0,
                                'quarantine_blending' => $request->quarantine_blending ?? 0,
                                'quarantine_forming' => $request->quarantine_forming ?? 0,
                                'quarantine_coating' => $request->quarantine_coating ?? 0,
                                'quarantine_time_unit' => $request->quarantine_time_unit === "on" ? true : false,

                                'deparment_code' => session('user')['production_code'],
                                'pharmacist_id' => $request->pharmacist_id ?: null,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(),
                        ]);

                        return $this->syncFinishedProductBatch($intermediate_code, $request->batch_qty, $request->unit_batch_qty);
                });

                $message = 'Đã cập nhật thành công!';
                if ($synced > 0) {
                        $message .= ' Đã cập nhật cỡ lô cho ' . $synced . ' mã thành phẩm tương ứng.';
                }

                return redirect()->back()->with('success', $message);
        }

        /**
         * Cỡ lô của mã TP luôn bằng cỡ lô của mã BTP tương ứng, nên sửa cỡ lô bên BTP
         * thì mọi mã TP đang gắn với mã BTP đó phải đổi theo.
         * Cỡ lô theo khối lượng (batch_size) không cần đồng bộ: danh mục thành phẩm
         * không lưu cột này mà lấy trực tiếp từ intermediate_category khi hiển thị.
         * Bỏ qua các dòng đã huỷ (cancel = 1) vì đó là dữ liệu không còn dùng.
         *
         * @return int Số mã TP đã được cập nhật
         */
        private function syncFinishedProductBatch($intermediate_code, $batch_qty, $unit_batch_qty)
        {
                if (empty($intermediate_code)) {
                        return 0;
                }

                $products = DB::table('finished_product_category')
                        ->where('intermediate_code', $intermediate_code)
                        ->where('cancel', 0)
                        ->get();

                $synced = 0;

                foreach ($products as $product) {
                        // Chỉ ghi lịch sử và cập nhật những dòng thực sự lệch cỡ lô.
                        if ((float) $product->batch_qty == (float) $batch_qty && (string) $product->unit_batch_qty === (string) $unit_batch_qty) {
                                continue;
                        }

                        $history = (array) $product;
                        $history['category_id'] = $history['id'];
                        unset($history['id']);
                        DB::table('finished_product_category_history')->insert($history);

                        DB::table('finished_product_category')->where('id', $product->id)->update([
                                'batch_qty' => $batch_qty,
                                'unit_batch_qty' => $unit_batch_qty,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(),
                        ]);

                        $synced++;
                }

                return $synced;
        }

        public function deActive(Request $request)
        {

                $this->logHistory($request->id);

                if ($request->IsHypothesis == 1) {
                        DB::table('intermediate_category')->where('id', $request->id)->update([
                                'cancel' => 1,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(),
                        ]);
                } else {
                        DB::table('intermediate_category')->where('id', $request->id)->update([
                                'Active' => !$request->active,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(),
                        ]);
                }

                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }

        public function recipe(Request $request)
        {

                $datas = collect();

                if ($request->IsHypothesis != 1) {
                        $datas = DB::connection('mms')
                                ->table('yfBOM_BOMItemHP')
                                ->where('PrdID', $request->intermediate_code)
                                ->where('Revno', function ($q) use ($request) {
                                        $q->selectRaw('MAX(Revno)')
                                                ->from('yfBOM_BOMItemHP')
                                                ->where('PrdID', $request->intermediate_code);
                                })
                                ->distinct()
                                ->orderBy('PrdStage')
                                ->orderBy('MatID')
                                ->get();
                }

                // Mã chưa có công thức chính thức trên MMS thì dùng công thức giả định của danh mục.
                // Không có product_caterogy_id nghĩa là bên gọi chỉ muốn tra đúng công thức MMS
                // (ví dụ nút "lấy công thức từ MMS" trong màn hình tạo công thức giả định).
                if ($datas->isEmpty() && $request->filled('product_caterogy_id')) {
                        $datas = DB::table('bom_item')
                                ->select([
                                        'code as MatID',
                                        'name as MaterialName',
                                        'qty as MatQty',
                                        'uom',
                                        'Revno'
                                ])
                                ->where('active', 1)
                                ->where('product_caterogy_id', $request->product_caterogy_id)
                                // id của intermediate_category và finished_product_category có thể trùng nhau,
                                // mat_par_type là thứ tách công thức nguyên liệu (0) khỏi công thức bao bì (1).
                                ->when($request->filled('mat_par_type'), function ($q) use ($request) {
                                        return $q->where('mat_par_type', $request->mat_par_type);
                                })
                                ->get();
                }

                return response()->json($datas);
        }

        public function getMaterialName(Request $request)
        {
                $matId = trim($request->mat_id);
                if (empty($matId)) {
                        return response()->json(['success' => false, 'message' => 'Missing mat_id']);
                }

                try {
                        $material = DB::connection('mms')
                                ->table('mstmaterial')
                                ->where('MatID', $matId)
                                ->first();

                        if ($material) {
                                return response()->json(['success' => true, 'mat_name' => $material->MatNM, 'mat_uom' => $material->MatUOM]);
                        }
                        return response()->json(['success' => false, 'message' => 'Not found']);
                } catch (\Exception $e) {
                        return response()->json(['success' => false, 'message' => $e->getMessage()]);
                }
        }
}
