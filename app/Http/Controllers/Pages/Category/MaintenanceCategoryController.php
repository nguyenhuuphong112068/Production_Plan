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
                $filter_block = $request->block; // B1 hoặc B2
                $filter_type = $request->type;   // 1: Hiệu chuẩn, 2: Bảo trì, 3: Tiện ích

                // Định nghĩa tên danh mục
                $type_names = [
                        1 => 'Hiệu Chuẩn',
                        2 => 'Bảo Trì',
                        3 => 'Tiện Ích'
                ];

                $instruments = collect();
                $connections = ['cal1', 'cal2'];
                $suffixes = $filter_type ? [$filter_type] : [1, 2, 3];

                foreach ($connections as $conn) {
                        $block_val = ($conn === 'cal1') ? 'B1' : 'B2';

                        // Nếu có filter block mà không khớp thì bỏ qua
                        if ($filter_block && $filter_block !== $block_val) {
                                continue;
                        }

                        foreach ($suffixes as $suffix) {
                                // Định dạng block mới để lưu vào quota_maintenance: ví dụ HC-B1, BT-B2...
                                $type_code = ($suffix == 1) ? 'HC' : (($suffix == 2) ? 'BT' : 'TI');
                                $internal_block = "{$type_code}-{$block_val}";

                                $result = DB::connection($conn)
                                        ->table("Schedule_Master_{$suffix} as Sch")
                                        ->leftJoin("Inst_Master_{$suffix} as Ins", 'Sch.Inst_ID', '=', 'Ins.Inst_id')
                                        ->leftJoin("Eqp_mst_{$suffix} as Eqp", 'Eqp.Eqp_ID', '=', 'Ins.Parent_Equip_id')
                                        ->where('Ins.Inst_Status', 'Active')
                                        ->select(
                                                'Sch.Inst_id',
                                                'Sch.Sch_Type',
                                                'Ins.Inst_Name',
                                                'Ins.Inst_Installed_Location',
                                                'Ins.Inst_Type',
                                                'Ins.Inst_Status',
                                                'Ins.Created_By',
                                                'Ins.Created_On',
                                                'Ins.Parent_Equip_id',
                                                'Eqp.Eqp_ID as Parent_Eqp_ID',
                                                'Eqp.Eqp_name'
                                        )
                                        ->get()
                                        ->unique(function ($item) {
                                                return $item->Inst_id . '_' . $item->Sch_Type;
                                        })
                                        ->map(function ($item) use ($internal_block) {
                                                $item->internal_block = $internal_block;
                                                $item->block_display = explode('-', $internal_block)[1]; // B1 hoặc B2
                                                return $item;
                                        });

                                $instruments = $instruments->merge($result);
                        }
                }

                // Lấy danh sách định mức hiện có
                $quota_maintenance = DB::table('quota_maintenance')->get();
                $existing_lookup = $quota_maintenance->mapWithKeys(function ($q) {
                        return [$q->inst_id . '_' . $q->Inst_sch_type . '_' . $q->block => $q];
                })->toArray();

                // Kiểm tra và tạo mới hoặc cập nhật Eqp_name cho định mức
                $new_quotas = [];
                foreach ($instruments as $inst) {
                        $key = $inst->Inst_id . '_' . ($inst->Sch_Type ?? '') . '_' . $inst->internal_block;
                        if (!isset($existing_lookup[$key])) {
                                $new_quotas[] = [
                                        'inst_id' => $inst->Inst_id,
                                        'Eqp_name' => $inst->Eqp_name ?? '',
                                        'inst_name' => $inst->Inst_Name ?? '',
                                        'parent_eqp_id' => $inst->Parent_Equip_id ?? '',
                                        'Inst_sch_type' => $inst->Sch_Type ?? '',
                                        'exe_time' => '00:00',
                                        'block' => $inst->internal_block,
                                        'is_HVAC' => str_starts_with($inst->internal_block, 'TI') ? 1 : 0,
                                        'created_by' => session('user')['fullName'] ?? 'System',
                                        'created_time' => now(),
                                ];
                        } else {
                                // Cập nhật Eqp_name, inst_name, parent_eqp_id nếu cũ bị rỗng hoặc khác
                                $existing = $existing_lookup[$key];
                                $update_data = [];
                                if (empty($existing->Eqp_name) && !empty($inst->Eqp_name)) {
                                        $update_data['Eqp_name'] = $inst->Eqp_name;
                                }
                                if (empty($existing->inst_name) && !empty($inst->Inst_Name)) {
                                        $update_data['inst_name'] = $inst->Inst_Name;
                                }
                                if (empty($existing->parent_eqp_id) && !empty($inst->Parent_Equip_id)) {
                                        $update_data['parent_eqp_id'] = $inst->Parent_Equip_id;
                                }

                                if (!empty($update_data)) {
                                        DB::table('quota_maintenance')
                                                ->where('id', $existing->id)
                                                ->update($update_data);
                                }
                        }
                }

                if (!empty($new_quotas)) {
                        foreach (array_chunk($new_quotas, 500) as $chunk) {
                                DB::table('quota_maintenance')->insert($chunk);
                        }
                }

                // Lấy dữ liệu hiển thị
                $query = DB::table('quota_maintenance')->where('active', 1);

                if ($filter_block && $filter_type) {
                        $type_code = ($filter_type == 1) ? 'HC' : (($filter_type == 2) ? 'BT' : 'TI');
                        $query->where('block', "{$type_code}-{$filter_block}");
                } elseif ($filter_block) {
                        $query->where('block', 'like', "%-{$filter_block}");
                }

                $quota_maintenance = $query->get();

                $rooms = DB::table('room')->select('id', 'name', 'code', 'deparment_code')->get();
                $room_names = $rooms->pluck('full_name_with_code', 'id')->toArray();
                if (empty($room_names)) {
                        $room_names = $rooms->mapWithKeys(function ($room) {
                                return [$room->id => $room->code . ' - ' . $room->name];
                        })->toArray();
                }

                $inst_lookup = [];
                foreach ($instruments as $inst) {
                        $inst_lookup[$inst->Inst_id . '_' . ($inst->Sch_Type ?? '') . '_' . $inst->internal_block] = $inst;
                }

                $quota_rooms = DB::table('quota_maintenance_rooms')->get()->groupBy('quota_maintenance_id');

                $datas = collect();
                foreach ($quota_maintenance as $quota) {
                        $inst = $inst_lookup[$quota->inst_id . '_' . ($quota->Inst_sch_type ?? '') . '_' . $quota->block] ?? null;
                        if (!$inst && !$filter_block) {
                                // Nếu không lọc mà không tìm thấy inst trong đợt quét này, có thể quét thêm nếu cần
                                // Nhưng thường sẽ có vì chúng ta vừa quét ở trên
                        }

                        $assigned_room_ids = $quota_rooms->get($quota->id, collect())->pluck('room_id')->toArray();
                        $exe_room_names = array_intersect_key($room_names, array_flip($assigned_room_ids));

                        $datas->push((object)[
                                'id' => $quota->id,
                                'code' => $quota->inst_id,
                                'block' => explode('-', $quota->block)[1] ?? 'B1',
                                'internal_block' => $quota->block,
                                'parent_code' => $quota->parent_eqp_id ?? $inst->Parent_Equip_id ?? '',
                                'Eqp_name' => $quota->Eqp_name ?? $inst->Eqp_name ?? '',
                                'Inst_Name' => $quota->inst_name ?? $inst->Inst_Name ?? '',
                                'room_ids' => $assigned_room_ids,
                                'exe_room_name' => implode(', ', $exe_room_names),
                                'room_code' => $inst->Inst_Installed_Location ?? '',
                                'sch_type' => $inst->Sch_Type ?? '',
                                'deparment_code' => $quota->deparment_code,
                                'quota' => $quota->exe_time,
                                'is_HVAC' => $quota->is_HVAC,
                                'active' =>  $quota->active,
                                'created_by' => $quota->created_by,
                                'created_at' => $quota->created_time,
                        ]);
                }

                $typeName = $filter_type ? $type_names[$filter_type] : 'Bảo Trì - Hiệu Chuẩn';
                $title = strtoupper("DANH MỤC {$typeName}" . ($filter_block ? " {$filter_block}" : ""));
                session()->put(['title' => $title]);

                return view('pages.category.maintenance.list', [
                        'datas' => $datas,
                        'rooms' => $rooms,
                        'filter_block' => $filter_block,
                        'filter_type' => $filter_type
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
                $quota_id = $request->id;
                $room_ids = $request->room_ids; // Mảng các room_id

                DB::beginTransaction();
                try {
                        // Xóa các liên kết cũ
                        DB::table('quota_maintenance_rooms')->where('quota_maintenance_id', $quota_id)->delete();

                        // Thêm các liên kết mới
                        if (!empty($room_ids) && is_array($room_ids)) {
                                $insert_data = [];
                                foreach ($room_ids as $room_id) {
                                        $insert_data[] = [
                                                'quota_maintenance_id' => $quota_id,
                                                'room_id' => $room_id,
                                        ];
                                }
                                DB::table('quota_maintenance_rooms')->insert($insert_data);

                                // Cập nhật lại room_id đầu tiên vào bảng chính để tương thích ngược nếu cần
                                DB::table('quota_maintenance')
                                        ->where('id', $quota_id)
                                        ->update([
                                                'room_id' => $room_ids[0],
                                                'created_by' => session('user')['fullName'],
                                                'created_time' => now(),
                                        ]);
                        } else {
                                // Nếu không chọn phòng nào
                                DB::table('quota_maintenance')
                                        ->where('id', $quota_id)
                                        ->update([
                                                'room_id' => null,
                                                'created_by' => session('user')['fullName'],
                                                'created_time' => now(),
                                        ]);
                        }

                        DB::commit();
                        return response()->json(['success' => true]);
                } catch (\Exception $e) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
                }
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
                $dept_code = $request->department_code ?? $request->deparment_code;
                DB::table('quota_maintenance')
                        ->where('id', $request->id)
                        ->update([
                                'deparment_code' => $dept_code,
                                'created_by' => session('user')['fullName'],
                                'created_time' => now(),
                        ]);
                return response()->json(['success' => true]);
        }


        public function Auto_updateDepartment(Request $request)
        {
                $dept_code = $request->department_code ?? $request->deparment_code;
                DB::table('quota_maintenance')
                        ->where('id', $request->id)
                        ->update([
                                'deparment_code' => $dept_code,
                        ]);
                return response()->json(['success' => true]);
        }
}
