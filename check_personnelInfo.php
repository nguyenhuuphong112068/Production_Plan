<?php
$handle = fopen('c:\PMS\Production_Plan\resources\views\pages\assignment\production\dataTable.blade.php', 'r');
$count = 0;
while (($line = fgets($handle)) !== false) {
    $count++;
    if (strpos($line, '$personnelInfo') !== false) {
        echo "Line $count: " . trim($line) . "\n";
    }
}
fclose($handle);
