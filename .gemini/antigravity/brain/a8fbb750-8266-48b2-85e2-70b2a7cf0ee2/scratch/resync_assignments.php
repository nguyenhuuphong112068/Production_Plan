<?php

use Illuminate\Support\Facades\DB;

try {
    echo "Truncating employee_assignments...\n";
    DB::table('employee_assignments')->truncate();

    // 1. Chuyển từ employee_productions (Workshop level)
    echo "Processing employee_productions...\n";
    $productions = DB::table('employee_productions')->get();
    foreach ($productions as $p) {
        try {
            DB::table('employee_assignments')->insert([
                'employees_id' => $p->employees_id,
                'production_code' => $p->production_code ?? '',
                'is_main' => $p->is_main ?? 0,
                'group_id' => 0,
                'room_id' => 0,
                'active' => $p->active ?? 1,
                'created_by' => $p->created_by ?? 'System Migration',
                'created_at' => $p->created_at ?? now(),
                'updated_at' => $p->updated_at ?? now(),
            ]);
        } catch (\Exception $e) {
            echo "Skipping production record for employee {$p->employees_id} (duplicate or error): " . $e->getMessage() . "\n";
        }
    }

    // 2. Chuyển từ employee_groups (Group level)
    echo "Processing employee_groups...\n";
    $groups = DB::table('employee_groups')->get();
    foreach ($groups as $g) {
        $groupCode = DB::table('stage_groups')->where('id', $g->group_id)->value('code');
        $productionCode = '';
        if ($groupCode) {
            $productionCode = DB::table('room')->where('group_code', $groupCode)->value('deparment_code') ?? '';
        }

        try {
            DB::table('employee_assignments')->insert([
                'employees_id' => $g->employees_id,
                'production_code' => $productionCode,
                'is_main' => 0,
                'group_id' => $g->group_id,
                'room_id' => 0,
                'active' => $g->active ?? 1,
                'created_by' => $g->created_by ?? 'System Migration',
                'created_at' => $g->created_at ?? now(),
                'updated_at' => $g->updated_at ?? now(),
            ]);
        } catch (\Exception $e) {
             echo "Skipping group record for employee {$g->employees_id} group {$g->group_id}: " . $e->getMessage() . "\n";
        }
    }

    // 3. Chuyển từ employee_rooms (Room level)
    echo "Processing employee_rooms...\n";
    $rooms = DB::table('employee_rooms')->get();
    foreach ($rooms as $r) {
        $room = DB::table('room')->where('id', $r->room_id)->first();
        if (!$room) {
            echo "Room ID {$r->room_id} not found in room table.\n";
            continue;
        }

        $productionCode = $room->deparment_code ?? '';
        $groupCode = $room->group_code ?? '';
        $groupId = DB::table('stage_groups')->where('code', $groupCode)->value('id') ?? 0;

        try {
            DB::table('employee_assignments')->insert([
                'employees_id' => $r->employees_id,
                'production_code' => $productionCode,
                'is_main' => 0,
                'group_id' => $groupId,
                'room_id' => $r->room_id,
                'level' => $r->level ?? 1,
                'active' => $r->active ?? 1,
                'created_by' => $r->created_by ?? 'System Migration',
                'created_at' => $r->created_at ?? now(),
                'updated_at' => $r->updated_at ?? now(),
            ]);
        } catch (\Exception $e) {
            echo "Skipping room record for employee {$r->employees_id} room {$r->room_id}: " . $e->getMessage() . "\n";
        }
    }

    echo "Successfully updated employee_assignments from source tables.\n";
} catch (\Exception $e) {
    echo "Critical Error: " . $e->getMessage() . "\n";
}
