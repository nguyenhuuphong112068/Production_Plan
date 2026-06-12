import re

file_path = r'C:\PMS\Production_Plan\app\Http\Controllers\Pages\Category\MaintenanceCategoryController.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

log_history_func = """
        private function logHistory($id)
        {
                $current = DB::table('quota_maintenance')->where('id', $id)->first();
                if ($current) {
                        $history_id = DB::table('quota_maintenance_history')->insertGetId([
                                'category_id' => $current->id,
                                'inst_id' => $current->inst_id,
                                'parent_eqp_id' => $current->parent_eqp_id,
                                'inst_name' => $current->inst_name,
                                'Eqp_name' => $current->Eqp_name,
                                'exe_time' => $current->exe_time,
                                'Inst_sch_type' => $current->Inst_sch_type,
                                'block' => $current->block,
                                'is_HVAC' => $current->is_HVAC,
                                'deparment_code' => $current->deparment_code,
                                'active' => $current->active,
                                'created_by' => $current->created_by,
                                'created_time' => $current->created_time,
                                'created_at' => now(),
                                'updated_at' => now()
                        ]);

                        $rooms = DB::table('quota_maintenance_rooms')->where('quota_maintenance_id', $id)->get();
                        foreach ($rooms as $room) {
                                DB::table('quota_maintenance_rooms_history')->insert([
                                        'history_id' => $history_id,
                                        'quota_maintenance_id' => $id,
                                        'room_id' => $room->room_id,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                ]);
                        }
                }
        }

        public function history(Request $request)
        {
                $histories = DB::table('quota_maintenance_history')
                        ->where('category_id', $request->id)
                        ->orderBy('id', 'desc')
                        ->get();
                
                foreach($histories as $h) {
                        $room_names = DB::table('quota_maintenance_rooms_history')
                                ->join('room', 'quota_maintenance_rooms_history.room_id', '=', 'room.id')
                                ->where('quota_maintenance_rooms_history.history_id', $h->id)
                                ->pluck('room.name')
                                ->toArray();
                        $h->room_names = implode(', ', $room_names);
                }

                $current = DB::table('quota_maintenance')->where('id', $request->id)->first();
                if ($current) {
                        $room_names = DB::table('quota_maintenance_rooms')
                                ->join('room', 'quota_maintenance_rooms.room_id', '=', 'room.id')
                                ->where('quota_maintenance_rooms.quota_maintenance_id', $current->id)
                                ->pluck('room.name')
                                ->toArray();
                        $current->room_names = implode(', ', $room_names);
                }

                return response()->json([
                        'current' => $current,
                        'history' => $histories
                ]);
        }
"""

if 'logHistory' not in content:
    # Insert before private function checkPermission
    content = content.replace('private function checkPermission', log_history_func + '\n        private function checkPermission')
    
    # Inject into updateTime
    content = re.sub(r'(public function updateTime[^{]*{.*?if\s*\(!\$this->checkPermission\(\$request->id\)\)\s*{.*?})', r'\1\n\n                $this->logHistory($request->id);', content, flags=re.DOTALL)
    
    # Inject into is_HVAC
    content = re.sub(r'(public function is_HVAC[^{]*{.*?if\s*\(!\$this->checkPermission\(\$request->id\)\)\s*{.*?})', r'\1\n\n                $this->logHistory($request->id);', content, flags=re.DOTALL)
    
    # Inject into updateRoom
    content = re.sub(r'(public function updateRoom[^{]*{.*?if\s*\(!\$this->checkPermission\(\$request->id\)\)\s*{.*?})', r'\1\n\n                $this->logHistory($request->id);', content, flags=re.DOTALL)
    
    # Inject into updateDepartment
    content = re.sub(r'(public function updateDepartment[^{]*{.*?if\s*\(!\$this->checkPermission\(\$request->id\)\)\s*{.*?})', r'\1\n\n                $this->logHistory($request->id);', content, flags=re.DOTALL)
    
    # Inject into delete
    content = re.sub(r'(public function delete[^{]*{.*?if\s*\(!\$this->checkPermission\(\$request->id\)\)\s*{.*?})', r'\1\n\n                $this->logHistory($request->id);', content, flags=re.DOTALL)
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print('Updated controller')
else:
    print('Already updated')
