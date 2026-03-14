<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MaintenanceCategoryController extends Controller
{

        public function index(Request $request)
        {
                $filter_block = $request->block; // Lấy block từ request (B1 hoặc B2)

                // Tối ưu hóa truy vấn: Join trực tiếp trên database cal1 và lọc Inst_Status = Active
                // Gộp 6 bảng từ 2 khu vực (cal1, cal2) x 3 bảng (1, 2, 3) thành 1 collection
                $instruments = collect();
                $connections = ['cal1', 'cal2'];
                $suffixes = [1, 2, 3];

                foreach ($connections as $conn) {
                        $block = ($conn === 'cal1') ? 'B1' : 'B2';
                        
                        // Nếu có filter block mà không khớp thì bỏ qua connection này
                        if ($filter_block && $filter_block !== $block) {
                                continue;
                        }

                        foreach ($suffixes as $suffix) {
                                $result = DB::connection($conn)
                                        ->table("Inst_Master_{$suffix} as Ins")
                                        ->leftJoin("Eqp_mst_{$suffix} as Eqp", 'Eqp.Eqp_ID', '=', 'Ins.Parent_Equip_id')
                                        ->where('Ins.Inst_Status', 'Active')
                                        ->select(
                                                'Ins.Inst_id',
                                                'Ins.Inst_Name',
                                                'Ins.Inst_sch_type',
                                                'Ins.Inst_Installed_Location',
                                                'Ins.Inst_Type',
                                                'Ins.Inst_Status',
                                                'Ins.Created_By',
                                                'Ins.Created_On',
                                                'Ins.Parent_Equip_id',
                                                'Eqp.Eqp_ID as Parent_Eqp_ID'
                                        )
                                        ->get()
                                        ->map(function ($item) use ($block) {
                                                $item->block = $block;
                                                return $item;
                                        });

                                $instruments = $instruments->merge($result);
                        }
                }

                // Lấy danh sách định mức hiện có
                $quota_maintenance = DB::table('quota_maintenance')->get();
                $existing_quota_inst_ids = $quota_maintenance->pluck('inst_id')->toArray();

                // Kiểm tra và tạo mới định mức cho các thiết bị con chưa có
                $new_quotas = [];
                foreach ($instruments as $inst) {
                        if (!in_array($inst->Inst_id, $existing_quota_inst_ids)) {
                                $new_quotas[] = [
                                        'inst_id' => $inst->Inst_id,
                                        'exe_time' => '00:00',
                                        'block' => $inst->block,
                                        'created_by' => session('user')['fullName'] ?? 'System',
                                        'created_time' => now(),
                                ];
                        }
                }

                // Chèn hàng loạt nếu có dữ liệu mới (Sử dụng array chunk để tối ưu thêm nếu dữ liệu quá lớn)
                if (!empty($new_quotas)) {
                        foreach (array_chunk($new_quotas, 1000) as $chunk) {
                                DB::table('quota_maintenance')->insert($chunk);
                        }
                }

                $query = DB::table('quota_maintenance')->where('active', 1);
                if ($filter_block) {
                        $query->where('block', $filter_block);
                }
                $quota_maintenance = $query->get();

                $rooms = DB::table('room')->where('deparment_code', session('user')['production_code'])->select('id', 'name', 'code')->get();
                $room_names = $rooms->mapWithKeys(function ($room) {
                        return [$room->id => $room->code . ' - ' . $room->name];
                })->toArray();



                $datas = collect();

                //Build lookup array cho instruments để map nhanh hơn O(1)
                $inst_lookup = [];
                foreach ($instruments as $inst) {
                        $inst_lookup[$inst->Inst_id] = $inst;
                }

                foreach ($quota_maintenance as $quota) {
                        $inst = $inst_lookup[$quota->inst_id] ?? null;

                        $item = (object)[
                                'id' => $quota->id,
                                'code' => $quota->inst_id,
                                'block' => $quota->block ?? '',
                                'parent_code' => $inst->Parent_Equip_id ?? '',
                                'name' => $inst->Inst_Name ?? '',
                                'room_id' => $quota->room_id,
                                'exe_room_name' => $room_names[$quota->room_id] ?? null,
                                'room_code' => $inst->Inst_Installed_Location ?? '',
                                'sch_type' => $inst->Inst_sch_type ?? '',
                                'deparment_code' => $quota->deparment_code,
                                'quota' => $quota->exe_time,
                                'is_HVAC' => $quota->is_HVAC,
                                'active' =>  $quota->active,
                                'created_by' => $quota->created_by,
                                'created_at' => $quota->created_time,
                        ];

                        $datas->push($item);
                }
                //dd($datas);
                $title = 'DANH MỤC BẢO TRÌ - HIỆU CHUẨN' . ($filter_block ? ' ' . $filter_block : '');
                session()->put(['title' => $title]);
                return view('pages.category.maintenance.list', [
                        'datas' => $datas,
                        'rooms' => $rooms,
                        'filter_block' => $filter_block,
                ]);
        }

        public function updateTime(Request $request)
        {
                DB::table('quota_maintenance')
                        ->where('id', $request->id)
                        ->update([
                                'exe_time' => $request->time,
                                'created_by' => session('user')['fullName'],
                                'created_time' => now(),
                        ]);
                return response()->json(['success' => true]);
        }

        public function is_HVAC(Request $request)
        {
                DB::table('quota_maintenance')
                        ->where('id', $request->id)
                        ->update([
                                'is_HVAC' => filter_var($request->checked, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                                'created_by' => session('user')['fullName'],
                                'created_time' => now(),
                        ]);
                return response()->json(['success' => true]);
        }

        public function updateRoom(Request $request)
        {
                DB::table('quota_maintenance')
                        ->where('id', $request->id)
                        ->update([
                                'room_id' => $request->room_id,
                                'created_by' => session('user')['fullName'],
                                'created_time' => now(),
                        ]);
                return response()->json(['success' => true]);
        }

        public function deActive(Request $request)
        {
                DB::table('quota_maintenance')->where('id', $request->id)->update([
                        'active' => 0,
                        'created_by' => session('user')['fullName'],
                        'created_time' => now(),
                ]);
                return response()->json(['success' => true]);
        }

        public function updateDepartment(Request $request)
        {
                DB::table('quota_maintenance')
                        ->where('id', $request->id)
                        ->update([
                                'deparment_code' => $request->deparment_code,
                                'created_by' => session('user')['fullName'],
                                'created_time' => now(),
                        ]);
                return response()->json(['success' => true]);
        }
}
