<?php

namespace App\Http\Controllers\Pages\Quota;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Pages\Schedual\SchedualController;


class ProductionQuotaController extends Controller
{
        public function index(Request $request)
        {

                $stage_code = $request->stage_code ?? 1;
                $production = session('user')['production_code'];

                $stage_code_room = $stage_code;
                if ($stage_code == 2) {
                        $stage_code_room = 1;
                }


                $room = DB::table('room')
                        ->where('deparment_code', $production)
                        ->where('stage_code', $stage_code_room)
                        ->where('active', true)
                        ->get();

                // map stage -> column boolean trong intermediate_category
                if ($stage_code <= 6) {

                        if ($stage_code == 1) {
                                $stage_name = "weight_1";
                        } elseif ($stage_code == 2) {
                                $stage_name = "weight_2";
                        } elseif ($stage_code == 3) {
                                $stage_name = "prepering";
                        } elseif ($stage_code == 4) {
                                $stage_name = "blending";
                        } elseif ($stage_code == 5) {
                                $stage_name = "forming";
                        } elseif ($stage_code == 6) {
                                $stage_name = "coating";
                        }

                        $category = "intermediate_category";
                        $joinField = "intermediate_code";


                        $datas = DB::table($category)
                                ->select(
                                        "{$category}.{$joinField} as intermediate_code",
                                        "{$category}.product_name_id",
                                        "{$category}.batch_size",
                                        "{$category}.unit_batch_size",
                                        "{$category}.batch_qty",
                                        "{$category}.unit_batch_qty",
                                        DB::raw("'NA' as finished_product_code"),
                                        'product_name.name as product_name',
                                        'room.name as room_name',
                                        'room.code as room_code',
                                        'quota.room_id',
                                        'quota.p_time',
                                        'quota.m_time',
                                        'quota.C1_time',
                                        'quota.C2_time',
                                        'quota.maxofbatch_campaign',
                                        'quota.campaign_index',
                                        'quota.note',
                                        'quota.prepared_by',
                                        DB::raw("DATE_FORMAT(quota.created_at, '%d/%m/%Y') as created_at"),
                                        'quota.id',
                                        'quota.active',
                                        'quota.tank'
                                )
                                ->where("{$category}.{$stage_name}", 1)
                                ->where("{$category}.active", true)
                                ->where("{$category}.deparment_code", $production)
                                // Lọc chỉ lấy 1 dòng duy nhất cho mỗi mã sản phẩm để tránh nhân dòng khi bảng danh mục bị trùng
                                ->whereIn("{$category}.id", function ($q) use ($category, $joinField, $production, $stage_name) {
                                        $q->select(DB::raw("MIN(id)"))
                                                ->from($category)
                                                ->where('active', true)
                                                ->where('deparment_code', $production)
                                                ->where($stage_name, 1)
                                                ->groupBy($joinField);
                                })
                                // join product_name (sử dụng product_name.id = category.product_name_id)
                                ->leftJoin('product_name', 'product_name.id', '=', "{$category}.product_name_id")
                                // join quota: đảm bảo đồng thời stage_code và deparment_code trên quota khớp
                                ->leftJoin('quota', function ($join) use ($stage_code, $joinField, $production, $category) {
                                        $join->on("{$category}.{$joinField}", '=', "quota.{$joinField}")
                                                ->where('quota.stage_code', '=', $stage_code)
                                                ->where('quota.deparment_code', '=', $production);
                                })
                                ->leftJoin('room', 'quota.room_id', '=', 'room.id')
                                ->orderBy('quota.id', 'desc')
                                ->get();
                } elseif ($stage_code == 7) {
                        $category = "finished_product_category";
                        $joinField = "finished_product_code";

                        $query = DB::table($category)
                                ->select(
                                        "{$category}.{$joinField} as finished_product_code",
                                        "{$category}.product_name_id",
                                        "{$category}.batch_qty",
                                        "{$category}.unit_batch_qty",
                                        "{$category}.intermediate_code",
                                        'product_name.name as product_name',
                                        'room.name as room_name',
                                        'room.code as room_code',
                                        'quota.room_id',
                                        'quota.p_time',
                                        'quota.m_time',
                                        'quota.C1_time',
                                        'quota.C2_time',
                                        'quota.maxofbatch_campaign',
                                        'quota.campaign_index',
                                        'quota.note',
                                        'quota.prepared_by',
                                        DB::raw("DATE_FORMAT(quota.created_at, '%d/%m/%Y') as created_at"),
                                        'quota.id',
                                        'quota.active',
                                        'quota.keep_dry'
                                )
                                ->leftJoin('product_name', 'product_name.id', '=', "{$category}.product_name_id")
                                ->leftJoin('quota', function ($join) use ($stage_code, $production, $category, $joinField) {
                                        $join->on("{$category}.{$joinField}", '=', "quota.{$joinField}")
                                                ->on("{$category}.intermediate_code", '=', "quota.intermediate_code")
                                                ->where('quota.stage_code', '=', $stage_code)
                                                ->where('quota.deparment_code', '=', $production);
                                })
                                ->leftJoin('room', 'quota.room_id', '=', 'room.id')
                                ->where("{$category}.active", true)
                                ->where("{$category}.deparment_code", $production)
                                // Lọc chỉ lấy 1 dòng duy nhất cho mỗi mã sản phẩm để tránh nhân dòng khi bảng danh mục bị trùng
                                ->whereIn("{$category}.id", function ($q) use ($category, $joinField, $production) {
                                        $q->select(DB::raw("MIN(id)"))
                                                ->from($category)
                                                ->where('active', true)
                                                ->where('deparment_code', $production)
                                                ->groupBy($joinField, 'intermediate_code');
                                })
                                ->orderBy('quota.id', 'desc');

                        $datas = $query->get();
                } else {
                        $datas = collect(); // an toàn nếu stage_code ngoài dự kiến
                }

                $historyCounts = DB::table('quota_history')
                        ->select('quota_id', DB::raw('count(*) as total'))
                        ->groupBy('quota_id')
                        ->get()
                        ->keyBy('quota_id');

                session()->put(['title' => "ĐỊNH MỨC THỜI GIAN SẢN XUẤT"]);
                //dd ($datas);
                return view('pages.quota.production.list', [
                        'datas' => $datas,
                        'stage_code' => $stage_code,
                        'room' => $room,
                        'historyCounts' => $historyCounts
                ]);
        }


        public function check_code_room_id(Request $request)
        {


                $room_id = $request->room_id;
                $intermediate_code = $request->intermediate_code;
                $finished_product_code = $request->finished_product_code;

                $process_code = $intermediate_code . "_" . $finished_product_code . "_" . $room_id;

                if ($request->stage_code == 2) {
                        $process_code = $process_code . "_" . $request->stage_code;
                }

                $exists = DB::table('quota')
                        ->where('process_code', $process_code) // bỏ khoảng trắng
                        ->exists();

                return response()->json([
                        'exists' => $exists,
                ]);
        }

        public function store(Request $request)
        {

                $selectedRooms = $request->input('room_id');

                $validator = Validator::make($request->all(), [
                        'intermediate_code' => 'required|string',
                        'room_id'   => 'required|array',
                        'room_id.*' => 'integer|exists:room,id',
                        'p_time' => ['required', 'string', 'regex:/^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$/'],
                        'm_time' => ['required', 'string', 'regex:/^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$/'],
                        'C1_time' => ['required', 'string', 'regex:/^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$/'],
                        'C2_time' =>  ['required', 'string', 'regex:/^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$/'],
                        'maxofbatch_campaign' => 'required',
                ], [
                        'intermediate_code.required' => 'Vui lòng chọn sản phẩm.',
                        'room_id.required' => 'Vui lòng chọn phòng sản xuất',
                        'p_time.required' => 'Vui lòng nhập thời gian chuẩn bị',
                        'p_time.regex' => 'Thời gian chuẩn bị phải đúng định dạng HH:mm (phút là 00, 15, 30 hoặc 45)',
                        'm_time.required' => 'Vui lòng nhập thời gian sản xuất',
                        'm_time.regex' => 'Thời gian sản xuất phải đúng định dạng HH:mm (phút là 00, 15, 30 hoặc 45)',
                        'C1_time.required' => 'Vui lòng nhập thời gian vệ sinh cấp I',
                        'C1_time.regex' => 'Thời gian vệ sinh cấp I phải đúng định dạng HH:mm (phút là 00, 15, 30 hoặc 45)',
                        'C2_time.required' => 'Vui lòng nhập thời gian vệ sinh cấp II',
                        'C2_time.regex' => 'Thời gian vệ sinh cấp II phải đúng định dạng HH:mm (phút là 00, 15, 30 hoặc 45)',
                        'maxofbatch_campaign.required' => 'Vui lòng nhập số lô tối đa',
                ]);

                if ($validator->fails()) {
                        if ($request->expectsJson() || $request->ajax()) {
                                $errorBag = $request->stage_code <= 6 ? 'create_inter_Errors' : 'create_finished_Errors';
                                return response()->json([
                                        'errors' => [
                                                $errorBag => $validator->errors()
                                        ]
                                ], 422);
                        }

                        if ($request->stage_code <= 6) {
                                return redirect()->back()->withErrors($validator, 'create_inter_Errors')->withInput();
                        }
                        return redirect()->back()->withErrors($validator, 'create_finished_Errors')->withInput();
                }

                if ($request->stage_code <= 6) {
                        $process_code = $request->intermediate_code . "_NA";
                        $finished_product_code = "NA";
                } else {
                        $process_code = $request->intermediate_code . "_" . $request->finished_product_code;
                        $finished_product_code = $request->finished_product_code;
                }

                $process_code_index = NULL;
                if ($request->stage_code == 2) {
                        $process_code_index = "_2";
                }

                $dataToInsert = [];
                foreach ($selectedRooms as $selectedRoom) {
                        $dataToInsert[] = [
                                'process_code' => $process_code . "_" . $selectedRoom . $process_code_index,
                                'intermediate_code' => $request->intermediate_code,
                                'finished_product_code' => $finished_product_code,
                                'room_id' => $selectedRoom,
                                'p_time' => $request->p_time,
                                'm_time' => $request->m_time,
                                'C1_time' => $request->C1_time,
                                'C2_time' => $request->C2_time,
                                'stage_code' => $request->stage_code,
                                'maxofbatch_campaign' => $request->maxofbatch_campaign,
                                'campaign_index' => $request->campaign_index ?? 1,
                                'note' => $request->note ?? "NA",
                                'deparment_code' =>  session('user')['production_code'],
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                        ];
                }

                DB::table('quota')->insert($dataToInsert);
                if (!isset($request->quotaView)) {
                        $SchedualController = new SchedualController();
                        return response()->json([
                                'plan' => $SchedualController->getPlanWaiting(session('user')['production_code'])
                        ]);
                }


                return redirect()->back()->with('success', 'Đã thêm thành công!');
        }

        public function update(Request $request)
        {
                //dd ($request->all());
                $validator = Validator::make($request->all(), [
                        'p_time' => ['required', 'string', 'regex:/^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$/'],
                        'm_time' => ['required', 'string', 'regex:/^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$/'],
                        'C1_time' => ['required', 'string', 'regex:/^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$/'],
                        'C2_time' =>  ['required', 'string', 'regex:/^(?:\d{1,2}|1\d{2}|200):(00|15|30|45)$/'],
                        'maxofbatch_campaign' => 'required',
                ], [
                        'p_time.required' => 'Vui lòng nhập thời gian chuẩn bị',
                        'p_time.regex' => 'Thời gian chuẩn bị phải đúng định dạng HH:mm (phút là 00, 15, 30 hoặc 45)',
                        'm_time.required' => 'Vui lòng nhập thời gian sản xuất',
                        'm_time.regex' => 'Thời gian sản xuất phải đúng định dạng HH:mm (phút là 00, 15, 30 hoặc 45)',
                        'C1_time.required' => 'Vui lòng nhập thời gian vệ sinh cấp I',
                        'C1_time.regex' => 'Thời gian vệ sinh cấp I phải đúng định dạng HH:mm (phút là 00, 15, 30 hoặc 45)',
                        'C2_time.required' => 'Vui lòng nhập thời gian vệ sinh cấp II',
                        'C2_time.regex' => 'Thời gian vệ sinh cấp II phải đúng định dạng HH:mm (phút là 00, 15, 30 hoặc 45)',
                        'maxofbatch_campaign.required' => 'Vui lòng nhập số lô tối đa',
                ]);

                if ($validator->fails()) {
                        if ($request->expectsJson() || $request->ajax()) {
                                return response()->json([
                                        'errors' => [
                                                'updateErrors' => $validator->errors()
                                        ]
                                ], 422);
                        }
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                }

                $this->logQuotaHistory($request->id);

                DB::table('quota')->where('id', $request->id)->update([

                        'p_time' => $request->p_time,
                        'm_time' => $request->m_time,
                        'C1_time' => $request->C1_time,
                        'C2_time' => $request->C2_time,
                        'maxofbatch_campaign' => $request->maxofbatch_campaign,
                        'note' => $request->note,
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(Request $request)
        {

                $quota = DB::table('quota')->where('id', $request->id)->first();

                if (!$quota) {
                        return response()->json(['success' => false, 'message' => 'Không tìm thấy định mức']);
                }

                if ($quota->stage_code == 7) {
                        $quota_count = DB::table('quota')->where('finished_product_code', $quota->finished_product_code)->where('stage_code', $quota->stage_code)->where('active', 1)->count();
                } else {
                        $quota_count = DB::table('quota')->where('intermediate_code', $quota->intermediate_code)->where('stage_code', $quota->stage_code)->where('active', 1)->count();
                }

                $currentActive = (int) $quota->active;
                $newActive = $currentActive ? 0 : 1;

                // Nếu đang muốn vô hiệu hóa mà chỉ còn 1 định mức active → chặn
                if ($newActive === 0 && $quota_count <= 1) {
                        return response()->json([
                                'success' => false,
                                'message' => 'Hiện tại chỉ còn một định mức cho sản phẩm này, không được vô hiệu hóa!'
                        ]);
                }

                $this->logQuotaHistory($request->id);

                DB::table('quota')->where('id', $request->id)->update([
                        'active' => $newActive,
                        'prepared_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);

                return response()->json([
                        'success' => true,
                        'active' => $newActive
                ]);
        }
        public function tank_keepDry(Request $request)
        {
                $this->logQuotaHistory($request->id);

                DB::table('quota')
                        ->where('id', $request->id)
                        ->when($request->stage_code == 3 || $request->stage_code == 4, function ($query) use ($request) {
                                $query->update(['tank' => $request->checked == "false" ? 0 : 1]);
                        })
                        ->when($request->stage_code == 5, function ($query) use ($request) {
                                $query->update(['keep_dry' => $request->checked == "false" ? 0 : 1]);
                        });

                return response()->json(['success' => true]);
        }

        public function updateTime(Request $request)
        {
                $this->logQuotaHistory($request->id);

                DB::table('quota')
                        ->where('id', $request->id)
                        ->update([
                                $request->name => $request->time
                        ]);

                return response()->json(['success' => true]);
        }

        public function history(Request $request)
        {
                $histories = DB::table('quota_history')
                        ->where('quota_id', $request->quota_id)
                        ->orderBy('id', 'desc')
                        ->get();

                $current = DB::table('quota')
                        ->where('id', $request->quota_id)
                        ->first();

                return response()->json([
                        'current' => $current,
                        'history' => $histories
                ]);
        }

        private function logQuotaHistory($quota_id)
        {
                $quota = DB::table('quota')->where('id', $quota_id)->first();
                if ($quota) {
                        DB::table('quota_history')->insert([
                                'quota_id' => $quota->id,
                                'process_code' => $quota->process_code . '_' . time() . '_' . rand(100, 999),
                                'intermediate_code' => $quota->intermediate_code ?? '',
                                'finished_product_code' => $quota->finished_product_code ?? '',
                                'room_id' => $quota->room_id ?? 0,
                                'p_time' => $quota->p_time ?? '0',
                                'm_time' => $quota->m_time ?? '0',
                                'C1_time' => $quota->C1_time ?? '0',
                                'C2_time' => $quota->C2_time ?? '0',
                                'stage_code' => $quota->stage_code ?? 0,
                                'maxofbatch_campaign' => $quota->maxofbatch_campaign ?? 0,
                                'note' => $quota->note ?? '',
                                'deparment_code' => $quota->deparment_code ?? '',
                                'active' => $quota->active ?? 1,
                                'prepared_by' => session('user')['fullName'] ?? '',
                                'created_at' => now(),
                                'updated_at' => now(),
                        ]);
                }
        }
}
