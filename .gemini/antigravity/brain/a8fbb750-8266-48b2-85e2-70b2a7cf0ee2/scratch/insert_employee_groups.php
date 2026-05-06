<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$codes = [
    '19120', '19024', '19043', '19029', '19471', '19098', '19409', '19892', '19904', '15076', '19245',
    '19041', '19048', '19057', '19062', '19074', '19103', '19117', '19119', '19142', '19168', '19192', 
    '19195', '19203', '19223', '19238', '19257', '19264', '19272', '19273', '19284', '19291', '19293', 
    '19295', '19296', '19297', '19345', '19360', '19367', '19370', '19393', '19394', '19103', '19132', 
    '19141', '19144', '19145', '19160', '19199', '19512', '19533', '19550', '19566', '19596', '19603', 
    '21094', '19656', '19663', '19664', '19671', '19676', '19709', '19712', '19713', '19714', '19728', 
    '19729', '19738', '19751', '19758', '19759', '19768', '19779', '19784', '19792', '19805', '19860',
    '19861', '19862', '19863',
    '19208', '19239', '19240', '19290', '19303', '19515', '19541', '19542', '19583', '19695',
    '19722', '19724', '19732', '19802', '19839', '19840', '19841'
];

$groupId = 7;
$userName = 'Antigravity_System';

$results = [
    'success' => [],
    'not_found' => [],
    'already_exists' => []
];

foreach ($codes as $code) {
    $employee = DB::table('employees')->where('code', $code)->first();
    
    if (!$employee) {
        $results['not_found'][] = $code;
        continue;
    }
    
    $exists = DB::table('employee_groups')
        ->where('employees_id', $employee->id)
        ->where('group_id', $groupId)
        ->exists();
        
    if ($exists) {
        $results['already_exists'][] = $code;
        continue;
    }
    
    DB::table('employee_groups')->insert([
        'employees_id' => $employee->id,
        'group_id' => $groupId,
        'active' => 1,
        'created_by' => $userName,
        'created_at' => now()
    ]);
    
    $results['success'][] = $code;
}

echo "Summary for Group ID $groupId:\n";
echo "Inserted: " . count($results['success']) . "\n";
echo "Not found: " . count($results['not_found']) . " (" . implode(',', $results['not_found']) . ")\n";
echo "Already exists: " . count($results['already_exists']) . "\n";
