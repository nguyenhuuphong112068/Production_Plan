<?php
$codes = ['19173', '19567', '19552', '19151', '19386', '19587', '19716', '19822', '19828', '19866', '19921', '19918', '19989', '19997', '19998', '23008', '23011', '23023', '23025', '23017', '23040', '23059', '23060'];
$employeeIds = DB::table('employees')->whereIn('code', $codes)->pluck('id')->toArray();
if (!empty($employeeIds)) {
    $affected = DB::table('employee_assignments')->whereIn('employees_id', $employeeIds)->update(['group_id' => 1]);
    echo "Updated " . $affected . " rows for " . count($employeeIds) . " employees.\n";
} else {
    echo "No employees found.\n";
}
