<?php
$file = 'resources/views/pages/assignment/production/dataTable.blade.php';
$lines = file($file);
foreach ($lines as $num => $line) {
    if (stripos($line, 'sidebar') !== false) {
        echo ($num + 1) . ': ' . trim($line) . "\n";
    }
}
