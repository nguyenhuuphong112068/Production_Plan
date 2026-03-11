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
                        // Cập nhật lại danh sách định mức sau khi thêm mới
                        $quota_maintenance = DB::table('quota_maintenance')->get();
                }

                $rooms = DB::table('room')->where('deparment_code', session('user')['production_code'])->select('id', 'name', 'code')->get();

                // Map dữ liệu thành Collection $datas, sử dụng quota_maintenance làm gốc (left join)
                $datas = collect();

                // Build lookup array cho instruments để map nhanh hơn O(1)
                $inst_lookup = [];
                foreach ($instruments as $inst) {
                        $inst_lookup[$inst->Inst_id] = $inst;
                }

                foreach ($quota_maintenance as $quota) {
                        $inst = $inst_lookup[$quota->inst_id] ?? null;

                        $item = (object)[
                                'id' => $quota->id,
                                'code' => $quota->inst_id, // Mã thiết bị con
                                'parent_code' => $inst ? ($inst->Parent_Eqp_ID ?? ($inst->Parent_Equip_id ?? '')) : '',
                                'name' => $inst ? $inst->Inst_Name : '',
                                'room_name' => '',
                                'room_code' => $inst ? $inst->Inst_Installed_Location : '',
                                'sch_type' => $inst ? $inst->Inst_sch_type : '',
                                'quota' => $quota->exe_time,
                                'note' => $inst ? ($inst->Inst_Type ?? '') : '',
                                'is_HVAC' => 0,
                                'active' => 1,
                                'created_by' => $inst ? $inst->Created_By : $quota->created_by,
                                'created_at' => $inst ? $inst->Created_On : $quota->created_time,
                        ];

                        $datas->push($item);
                }

                session()->put(['title' => 'DANH MỤC BẢO TRÌ - HIỆU CHUẨN']);
                return view('pages.category.maintenance.list', [
                        'datas' => $datas,
                        'rooms' => $rooms,
                ]);
        }



        public function update(Request $request)
        {
                //dd ($request->all());
                $validator = Validator::make($request->all(), [
                        'quota' => 'required',
                ], [
                        'quota.required' => 'Vui lòng nhập thời gian thực hiện',
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                }

                DB::table('maintenance_category')->where('code', $request->code)->update([

                        'quota' => $request->quota,
                        'note' => $request->note,
                        'created_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Cập nhật thành công!');
        }

        public function deActive(Request $request)
        {
                //dd ($request->all());
                DB::table('maintenance_category')->where('id', $request->id)->update([
                        'Active' => !$request->active,
                        'created_by' => session('user')['fullName'],
                        'updated_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
