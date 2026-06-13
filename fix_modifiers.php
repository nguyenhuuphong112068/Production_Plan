<?php

$files = [
    'app/Http/Controllers/Pages/MaterData/BlisterTypeController.php' => ['col' => 'created_by'],
    'app/Http/Controllers/Pages/MaterData/DepartmentController.php' => ['col' => 'prepareBy'],
    'app/Http/Controllers/Pages/MaterData\RoomController.php' => ['col' => 'prepareBy'],
    'app/Http/Controllers/Pages/MaterData/SourceMaterialController.php' => ['col' => 'prepared_by'],
    'app/Http/Controllers/Pages/MaterData/StageGroupController.php' => ['col' => 'create_by'],
    'app/Http/Controllers/Pages/Category/IntermediateCategoryController.php' => ['col' => 'prepared_by'],
    'app/Http/Controllers/Pages/Category/MaintenanceCategoryController.php' => ['col' => 'created_by']
];

foreach ($files as $file => $info) {
    $path = "c:/PMS/Production_Plan/" . $file;
    if (!file_exists($path)) {
        echo "Missing: $path\n";
        continue;
    }
    $content = file_get_contents($path);
    $col = $info['col'];
    $val = "session('user')['fullName'] ?? 'Admin'";

    // replace ->update([  with ->update([\n '$col' => $val,
    // only if '$col' => ... is not already there inside the array.
    
    // We can do this with regex:
    // Find DB::table(...)->where(...)->update([ ... ])
    // But be careful not to match too much.
    
    $content = preg_replace_callback('/(DB::table\([^)]+\)->where\([^)]+\)->update\(\[)(.*?)(\]\);)/s', function($m) use ($col, $val) {
        $inner = $m[2];
        if (strpos($inner, "'$col'") === false && strpos($inner, "\"$col\"") === false) {
            // add it
            return $m[1] . "\n            '$col' => $val," . $inner . $m[3];
        }
        return $m[0];
    }, $content);
    
    file_put_contents($path, $content);
    echo "Updated: $file\n";
}
