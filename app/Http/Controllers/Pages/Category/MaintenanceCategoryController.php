<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MaintenanceCategoryController extends Controller
{

        public function index()
        {

                // Tối ưu hóa truy vấn: Join trực tiếp trên database cal1 và lọc Inst_Status = Active
                $instruments = DB::connection('cal1')
                        ->table('Inst_Master_1 as Ins')
                        ->leftJoin('Eqp_mst_1 as Eqp', 'Eqp.Eqp_ID', '=', 'Ins.Parent_Equip_id')
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
                        ->get();

                // Lấy danh sách định mức hiện có
                $quota_maintenance = DB::table('quota_maintenance')->get();
                $existing_quota_inst_ids = $quota_maintenance->pluck('inst_id')->toArray();

                // Kiểm tra và tạo mới định mức cho các thiết bị con chưa có
                $new_quotas = [];
                foreach ($instruments as $inst) {
                        if (!in_array($inst->Inst_id, $existing_quota_inst_ids)) {
                                $new_quotas[] = [
                                        'inst_id' => $inst->Inst_id,
                                        'exe_time' => '00:00', // Default time
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
                $quota_maintenance = DB::table('quota_maintenance')->where('active', 1)->get();

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
                                'parent_code' => $inst->Parent_Equip_id,
                                'name' => $inst->Inst_Name,
                                'room_id' => $quota->room_id,
                                'exe_room_name' => $room_names[$quota->room_id] ?? null,
                                'room_code' => $inst->Inst_Installed_Location,
                                'sch_type' => $inst->Inst_sch_type,
                                'quota' => $quota->exe_time,
                                'is_HVAC' => $quota->is_HVAC,
                                'active' =>  $quota->active,
                                'created_by' => $quota->created_by,
                                'created_at' => $quota->created_time,
                        ];

                        $datas->push($item);
                }
                //dd($datas);
                session()->put(['title' => 'DANH MỤC BẢO TRÌ - HIỆU CHUẨN']);
                return view('pages.category.maintenance.list', [
                        'datas' => $datas,
                        'rooms' => $rooms,
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
}
