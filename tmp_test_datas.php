<?php
$Eqp_mst_1 = DB::connection('cal1')
        ->table('Eqp_mst_1 as Eqp')
        ->get();

$Inst_Master_1 = DB::connection('cal1')
        ->table('Inst_Master_1 as Ins')
        ->get();

// Lấy danh sách định mức hiện có
$quota_maintenance = DB::table('quota_maintenance')->get();
$existing_quota_inst_ids = $quota_maintenance->pluck('inst_id')->toArray();

// Kiểm tra và tạo mới định mức cho các thiết bị con chưa có
$new_quotas = [];
foreach ($Inst_Master_1 as $inst) {
        if (!in_array($inst->Inst_id, $existing_quota_inst_ids)) {
                $new_quotas[] = [
                        'inst_id' => $inst->Inst_id,
                        'exe_time' => '00:00', // Default time
                        'created_by' => 'System',
                        'created_time' => now(),
                ];
        }
}

// Chèn hàng loạt nếu có dữ liệu mới
if (!empty($new_quotas)) {
        DB::table('quota_maintenance')->insert($new_quotas);
        $quota_maintenance = DB::table('quota_maintenance')->get();
}

$datas = collect();

foreach ($Inst_Master_1 as $inst) {
        $parent_eqp = $Eqp_mst_1->firstWhere('Eqp_ID', $inst->Parent_Equip_id);
        $quota = $quota_maintenance->firstWhere('inst_id', $inst->Inst_id);

        $item = (object)[
                'id' => $inst->Inst_id, 
                'code' => $inst->Inst_id, 
                'parent_code' => $parent_eqp ? $parent_eqp->Eqp_ID : ($inst->Parent_Equip_id ?? ''), 
                'name' => $inst->Inst_Name,
                'room_name' => '', 
                'room_code' => $inst->Inst_Installed_Location, 
                'sch_type' => $inst->Inst_sch_type,
                'quota' => $quota ? $quota->exe_time : '00:00',
                'note' => $inst->Inst_Type ?? '',
                'is_HVAC' => 0,
                'active' => ($inst->Inst_Status == 'Active' || $inst->Inst_Status == 'A') ? 1 : 0,
                'created_by' => $inst->Created_By,
                'created_at' => $inst->Created_On,
        ];
        
        $datas->push($item);
}
echo "Total records in datas: " . $datas->count() . "\n";
echo "First item:\n";
print_r($datas->first());
