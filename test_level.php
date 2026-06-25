<?php
$personnel = DB::table('employees as e')->select('e.*', DB::raw("(SELECT GROUP_CONCAT(CONCAT(room_id, ':', level, ':', COALESCE(priority_level, 1)) SEPARATOR '|') FROM employee_assignments WHERE employees_id = e.id AND active = 1 AND room_id IS NOT NULL AND room_id > 0) as allowed_rooms_with_levels"))->where('e.id', 1)->first();
print_r($personnel);
